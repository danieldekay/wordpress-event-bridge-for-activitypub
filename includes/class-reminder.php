<?php
/**
 * General settings class.
 *
 * This file contains the General class definition, which handles the "General" settings
 * page for the ActivityPub Event Extension Plugin, providing options for configuring various general settings.
 *
 * @package ActivityPub_Event_Bridge
 * @since 1.0.0
 */

namespace ActivityPub_Event_Bridge;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity_Dispatcher;
use Activitypub\Transformer\Factory as Transformer_Factory;
use ActivityPub_Event_Bridge\Setup;
use ActivityPub_Event_Bridge\Activitypub\Transformer\Event as Event_Transformer;
use DateTime;

use function Activitypub\is_user_disabled;

/**
 * Adds automatic announcing or sending of reminders before the events start time.
 */
class Reminder {
	/**
	 * Initialize the class, registering WordPress hooks.
	 */
	public static function init() {
		// Post transitions.
		\add_action( 'transition_post_status', array( self::class, 'maybe_schedule_event_post_announcement' ), 33, 3 );

		// Send an event reminder.
		\add_action( 'activitypub_event_bridge_send_event_reminder', array( self::class, 'send_event_reminder' ), 10, 1 );

		\add_action( 'enqueue_block_editor_assets', array( self::class, 'enqueue_editor_assets' ) );
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
				'activitypub_event_bridge_reminder_time_gap',
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
		$asset_data = include ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_DIR . 'build/reminder/plugin.asset.php';
		$plugin_url = plugins_url( 'build/reminder/plugin.js', ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_FILE );
		wp_enqueue_script( 'activitypub-event-bridge-reminder', $plugin_url, $asset_data['dependencies'], $asset_data['version'], true );

		// Pass the the default site wide time gap option to the settings block on the events edit page.
		wp_localize_script(
			'activitypub-event-bridge-reminder',
			'activityPubEventBridge',
			array(
				'reminderTypeGap' => \get_option( 'activitypub_event_bridge_reminder_time_gap', 0 ),
			)
		);
	}

	/**
	 * Schedule Activities.
	 *
	 * @param string  $new_status  New post status.
	 * @param string  $old_status  Old post status.
	 * @param WP_Post $post        Post object.
	 */
	public static function maybe_schedule_event_post_announcement( $new_status, $old_status, $post ): void {
		$reminder_time_gap = (int) get_post_meta( $post->ID, 'activitypub_event_bridge_reminder_time_gap', true );

		if ( '' === $reminder_time_gap ) {
			$reminder_time_gap = \get_option( 'activitypub_event_bridge_reminder_time_gap', 0 );
		}

		if ( 0 === $reminder_time_gap || ! is_int( $reminder_time_gap ) ) {
			return;
		}

		$post = get_post( $post );

		if ( ! $post ) {
			return;
		}

		// Do not send activities if post is password protected.
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

		if ( $schedule_time < \time() ) {
			return;
		}

		$hook = 'activitypub_event_bridge_send_event_reminder';
		$args = array( $post->ID );

		\wp_clear_scheduled_hook( $hook, $args );
		\wp_schedule_single_event( $schedule_time, $hook, $args );
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

		$user_id = $transformer->get_wp_user_id();

		if ( is_user_disabled( $user_id ) ) {
			return;
		}

		$activity = $transformer->to_announce_self_activity( 'Announce' );

		Activity_Dispatcher::send_activity_to_followers( $activity, $user_id, $post );
	}
}
