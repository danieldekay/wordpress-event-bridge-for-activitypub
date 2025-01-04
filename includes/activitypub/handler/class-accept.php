<?php
/**
 * Accept handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

use Activitypub\Collection\Actors;
use Activitypub\Model\Blog;
use Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source;
use Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources as Event_Sources_Collection;

use function Activitypub\object_to_uri;

/**
 * Handle Accept requests.
 */
class Accept {
	/**
	 * Initialize the class, registering the handler for incoming `Accept` activities to the ActivityPub plugin.
	 */
	public static function init() {
		\add_action(
			'activitypub_inbox_accept',
			array( self::class, 'handle_accept' ),
			15,
			2
		);
	}

	/**
	 * Handle incoming "Accept" activities.
	 *
	 * @param array $activity The activity-object.
	 * @param int   $user_id  The id of the local blog-user.
	 */
	public static function handle_accept( $activity, $user_id ) {
		// We only process activities that are target to the blog actor.
		if ( Actors::BLOG_USER_ID !== $user_id ) {
			return;
		}

		// Check that we are actually following/or have a pending follow request this actor.
		$event_source = Event_Source::get_by_id( $activity['actor'] );
		if ( ! $event_source ) {
			return;
		}

		// This is what the ID of the follow request would look like.
		$application = new Blog();
		$follow_id   = Event_Sources_Collection::compose_follow_id( $application->get_id(), $activity['actor'] );

		// Check if the object of the `Accept` is indeed the `Follow` request we sent to that actor.
		if ( object_to_uri( $activity['object'] ) !== $follow_id ) {
			return;
		}

		// Get the WordPress post ID of the Event Source. This should not be able to fail here.
		$post_id = $event_source->get__id();

		if ( ! $post_id ) {
			return;
		}

		// Save the accept status of the follow request to the event source post.
		\update_post_meta( $post_id, '_event_bridge_for_activitypub_accept_of_follow', $activity['id'] );
		\wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);

		// Trigger the backfilling of events from this actor.
		\do_action( 'event_bridge_for_activitypub_backfill_events', $activity['actor'] );
	}
}
