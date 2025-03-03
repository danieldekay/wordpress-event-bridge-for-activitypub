<?php
/**
 * Event Post scheduler class file.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Scheduler;

use Event_Bridge_For_ActivityPub\Setup;

use function Activitypub\add_to_outbox;

/**
 * Event Post scheduler class.
 */
class Event {
	/**
	 * Initialize the class, registering WordPress hooks.
	 */
	public static function init() {
		\add_action( 'wp_after_insert_post', array( self::class, 'maybe_schedule_event_post_activity' ), 50, 3 );
		\add_action( 'event_bridge_for_activitypub_add_event_post_to_outbox', array( self::class, 'add_event_post_to_outbox' ), 10, 3 );
		\add_filter( 'activitypub_is_post_disabled', array( self::class, 'is_post_disabled_for_the_activitypub_plugin' ), 50, 2 );
	}

	/**
	 * Prevent the ActivityPub plugin from dealing with event posts.
	 *
	 * @param bool     $disabled Whether the post is already marked as disabled.
	 * @param \WP_Post $post     The WordPress post.
	 * @return bool
	 */
	public static function is_post_disabled_for_the_activitypub_plugin( $disabled, $post ): bool {
		if ( $disabled ) {
			return true;
		}

		if ( Setup::get_instance()->is_event_post_type_of_active_event_plugin( $post->post_type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Handle post updates and determine the appropriate Activity type.
	 *
	 * @param int      $post_id     Post ID.
	 * @param \WP_Post $post        Post object.
	 * @param bool     $update      Whether this is an existing post being updated.
	 * @param \WP_Post $post_before Post object before the update.
	 */
	public static function maybe_schedule_event_post_activity( $post_id, $post, $update, $post_before ) {
		if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
			return;
		}

		if ( ! Setup::get_instance()->is_event_post_type_of_active_event_plugin( $post->post_type ) ) {
			return;
		}

		if ( Setup::is_post_disabled( $post ) ) {
			return;
		}

		// Bail on bulk edits, unless post author or post status changed.
		if ( isset( $_REQUEST['bulk_edit'] ) && -1 === (int) $_REQUEST['post_author'] && -1 === (int) $_REQUEST['_status'] ) { // phpcs:ignore WordPress
			return;
		}

		$new_status = get_post_status( $post );
		$old_status = $post_before ? get_post_status( $post_before ) : null;

		switch ( $new_status ) {
			case 'publish':
				$type = $update ? 'Update' : 'Create';
				break;

			case 'draft':
				$type = ( 'publish' === $old_status ) ? 'Update' : false;
				break;

			case 'trash':
				$type = 'Delete';
				break;

			default:
				$type = false;
		}

		// Do not send Activities if `$type` is not set or unknown.
		if ( empty( $type ) ) {
			return;
		}

		$hook = 'event_bridge_for_activitypub_add_event_post_to_outbox';
		$args = array( $post, $type, $post->post_author );

		if ( false === \wp_next_scheduled( $hook, $args ) ) {
			\wp_schedule_single_event( \time() + 10, $hook, $args );
		}
	}

	/**
	 * Add an event post to the outbox.
	 *
	 * @param \WP_Post $post               The WP_Post object to add to the outbox.
	 * @param string   $activity_type      The type of the Activity.
	 * @param integer  $user_id            The User-ID.
	 */
	public static function add_event_post_to_outbox( $post, $activity_type, $user_id ): void {
		$post = get_post( $post->ID );
		add_to_outbox( $post, $activity_type, $user_id );
	}
}
