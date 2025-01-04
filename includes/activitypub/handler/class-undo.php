<?php
/**
 * Undo handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

use Activitypub\Collection\Actors;
use Event_Bridge_For_ActivityPub\Event_Sources;
use Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources as Event_Sources_Collection;

use function Activitypub\object_to_uri;

/**
 * Handle Uno requests.
 */
class Undo {
	/**
	 * Initialize the class, registering the handler for incoming `Uno` activities to the ActivityPub plugin.
	 */
	public static function init() {
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
	public static function handle_undo( $activity, $user_id ) {
		// We only process activities that are target to the blog actor.
		if ( Actors::BLOG_USER_ID !== $user_id ) {
			return;
		}

		// Check that we are actually following/or have a pending follow request for this actor.
		if ( ! Event_Sources::actor_is_event_source( $activity['actor'] ) ) {
			return;
		}

		$id = object_to_uri( $activity['object'] );

		// This is what the ID of the follow request would look like.
		$args  = array(
			'post_type'  => Event_Sources_Collection::POST_TYPE,
			'meta_key'   => '_event_bridge_for_activitypub_accept_of_follow',
			'meta_query' => array(
				array(
					'key'     => '_event_bridge_for_activitypub_accept_of_follow',
					'value'   => $id,
					'compare' => '=',
				),
			),
		);
		$query = new \WP_Query( $args );

		// If no event source with that accept ID is found return.
		if ( ! $query->have_posts() ) {
			return;
		}

		$post = $query->get_posts()[0];

		$post_id = is_a( $post, 'WP_Post' ) ? $post->ID : $post;

		\delete_post_meta( $post_id, '_event_bridge_for_activitypub_accept_of_follow' );
	}
}
