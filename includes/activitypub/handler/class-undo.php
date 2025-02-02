<?php
/**
 * Undo handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Collection\Actors;
use Event_Bridge_For_ActivityPub\Event_Sources;

use function Activitypub\object_to_uri;
use function Activitypub\sanitize_url;

/**
 * Handle Uno requests.
 */
class Undo {
	/**
	 * Initialize the class, registering the handler for incoming `Uno` activities to the ActivityPub plugin.
	 */
	public static function init(): void {
		\add_action(
			'activitypub_inbox_undo',
			array( self::class, 'handle_undo' ),
			15,
			2
		);
	}

	/**
	 * Handle incoming "Undo" activities.
	 *
	 * @param array $activity The activity-object.
	 * @param int   $user_id  The id of the local blog-user.
	 */
	public static function handle_undo( $activity, $user_id ): void {
		// We only process activities that are target to the blog actor.
		if ( Actors::BLOG_USER_ID !== $user_id ) {
			return;
		}

		// Check that we are actually following/or have a pending follow request for this actor.
		if ( ! Event_Sources::actor_is_event_source( $activity['actor'] ) ) {
			return;
		}

		$accept_id = sanitize_url( object_to_uri( $activity['object'] ) );

		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
				'_event_bridge_for_activitypub_accept_of_follow',
				$accept_id
			)
		);

		// If no event source with that accept ID is found return.
		if ( empty( $results ) ) {
			return;
		}

		$post_id = reset( $results )->post_id;

		\delete_post_meta( $post_id, '_event_bridge_for_activitypub_accept_of_follow' );
	}
}
