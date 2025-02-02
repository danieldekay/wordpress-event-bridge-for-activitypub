<?php
/**
 * Class file for Event Reminders.
 *
 * Automatic announcing or sending of reminders before the events start time.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 * @since 1.0.0
 */

namespace Event_Bridge_For_ActivityPub;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Activity;
use Activitypub\Activity_Dispatcher;
use Activitypub\Transformer\Factory as Transformer_Factory;
use Event_Bridge_For_ActivityPub\Setup;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Event as Event_Transformer;
use DateTime;
use WP_Post;

use function Activitypub\is_user_disabled;
use function Activitypub\safe_remote_post;

/**
 * Adds automatic announcing or sending of reminders before the events start time.
 */
class Reminder {
	/**
	 * Initialize the class, registering WordPress hooks.
	 */
	public static function init() {
		// Post transitions.
		\add_action( 'transition_post_status', array( self::class, 'maybe_schedule_event_reminder' ), 33, 3 );
		\add_action( 'delete_post', array( self::class, 'unschedule_event_reminder' ), 33, 1 );

		// Send an event reminder.
		\add_action( 'event_bridge_for_activitypub_send_event_reminder', array( self::class, 'send_event_reminder' ), 10, 1 );

		// Load the block which allows overriding the reminder time for an individual event in the post settings.
		\add_action( 'enqueue_block_editor_assets', array( self::class, 'enqueue_editor_assets' ) );

		// Register the post-meta which stores per-event overrides of the side-wide default of the reminder time gap.
		\add_action( 'init', array( self::class, 'register_postmeta' ), 11 );
	}

	/**
	 * Register post meta for controlling whether and when a reminder is scheduled for an individual event.
	 */
	public static function register_postmeta() {
		$ap_post_types = \get_post_types_by_support( 'activitypub' );
		foreach ( $ap_post_types as $post_type ) {
			\register_post_meta(
				$post_type,
				'event_bridge_for_activitypub_reminder_time_gap',
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				)
			);
		}
	}

	/**
	 * Enqueue the block editor assets.
	 */
	public static function enqueue_editor_assets() {
		// Check for our supported post types.
		$current_screen   = \get_current_screen();
		$event_post_types = Setup::get_instance()->get_active_event_plugins_post_types();
		if ( ! $current_screen || ! in_array( $current_screen->post_type, $event_post_types, true ) ) {
			return;
		}
		$asset_data = include EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . 'build/reminder/plugin.asset.php';
		$plugin_url = plugins_url( 'build/reminder/plugin.js', EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE );
		wp_enqueue_script( 'event-bridge-for-activitypub-reminder', $plugin_url, $asset_data['dependencies'], $asset_data['version'], true );

		// Pass the the default site wide time gap option to the settings block on the events edit page.
		wp_localize_script(
			'event-bridge-for-activitypub-reminder',
			'activityPubEventBridge',
			array(
				'reminderTypeGap' => \get_option( 'event_bridge_for_activitypub_reminder_time_gap', 0 ),
			)
		);
	}

	/**
	 * Schedule Activities.
	 *
	 * @param string   $new_status  New post status.
	 * @param string   $old_status  Old post status.
	 * @param ?WP_Post $post        Post object.
	 */
	public static function maybe_schedule_event_reminder( $new_status, $old_status, $post ): void {
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		// At first always unschedule the reminder for this event, it will be added again, in case.
		self::unschedule_event_reminder( $post->ID );

		// Do not set reminders if post is password protected.
		if ( \post_password_required( $post ) ) {
			return;
		}

		// Only schedule an reminder for event post types.
		if ( ! Setup::get_instance()->is_post_type_event_of_active_event_plugin( $post->post_type ) ) {
			return;
		}

		// Do not schedule a reminder if the event is not published.
		if ( 'publish' !== $new_status ) {
			return;
		}

		// See if a reminder time gap is set for the event individually in the events post-meta.
		$reminder_time_gap = (int) get_post_meta( $post->ID, 'event_bridge_for_activitypub_reminder_time_gap', true );

		// If not fallback to the global reminder time gap.
		if ( ! $reminder_time_gap ) {
			$reminder_time_gap = \get_option( 'event_bridge_for_activitypub_reminder_time_gap', 0 );
		}

		// Any non positive integer means that this feature is not active for this event post.
		if ( 0 === $reminder_time_gap || ! is_int( $reminder_time_gap ) ) {
			return;
		}

		// Get start time of the event.
		$event_transformer = Transformer_Factory::get_transformer( $post );

		if ( \is_wp_error( $event_transformer ) || ! $event_transformer instanceof Event_Transformer ) {
			return;
		}

		$start_time      = $event_transformer->get_start_time();
		$start_datetime  = new DateTime( $start_time );
		$start_timestamp = $start_datetime->getTimestamp();

		// Get the time when the reminder of the event's start should be sent.
		$schedule_time = $start_timestamp - $reminder_time_gap;

		// If the reminder time has already passed "now" skip it.
		if ( $schedule_time < \time() ) {
			return;
		}

		// All checks passed: schedule a single event which will trigger the sending of the reminder for this event post.
		\wp_schedule_single_event( $schedule_time, 'event_bridge_for_activitypub_send_event_reminder', array( $post->ID ) );
	}

	/**
	 * Unschedule the event reminder.
	 *
	 * @param int $post_id The WordPress post ID of the event post.
	 */
	public static function unschedule_event_reminder( $post_id ): void {
		\wp_clear_scheduled_hook( 'event_bridge_for_activitypub_send_event_reminder', array( $post_id ) );
	}

	/**
	 * Send a reminder for an event post.
	 *
	 * This currently sends an Announce activity.
	 *
	 * @param int $post_id The WordPress post ID of the event post.
	 */
	public static function send_event_reminder( $post_id ): void {
		$post = \get_post( $post_id );

		$transformer = Transformer_Factory::get_transformer( $post );

		if ( \is_wp_error( $transformer ) || ! $transformer instanceof Event_Transformer ) {
			return;
		}

		$actor      = $transformer->get_actor_object();
		$wp_user_id = $actor->get__id();

		if ( $wp_user_id > 0 && is_user_disabled( $wp_user_id ) ) {
			return;
		}

		// Compose `Announce` activity.
		$activity = new Activity();
		$activity->set_type( 'Announce' );
		$activity->set_actor( $actor->get_id() );
		$activity->set_object( $transformer->get_id() );
		$activity->set_sensitive( null );

		self::send_activity_to_followers( $activity, $wp_user_id );
	}

	/**
	 * Send an Activity to all followers.
	 *
	 * @param Activity $activity  The ActivityPub Activity.
	 * @param int      $user_id   The user ID.
	 */
	public static function send_activity_to_followers( $activity, $user_id ) {
		$inboxes = Activity_Dispatcher::add_inboxes_of_follower( array(), $user_id );
		$inboxes = array_unique( $inboxes );

		if ( empty( $inboxes ) ) {
			return;
		}

		$json = $activity->to_json();

		foreach ( $inboxes as $inbox ) {
			safe_remote_post( $inbox, $json, $user_id );
		}
	}
}
