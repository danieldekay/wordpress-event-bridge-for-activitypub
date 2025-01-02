<?php
/**
 * Create handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

use Activitypub\Collection\Actors;
use Event_Bridge_For_ActivityPub\Event_Sources;
use Event_Bridge_For_ActivityPub\Setup;

use function Activitypub\is_activity_public;

/**
 * Handle Create requests.
 */
class Create {
	/**
	 * Initialize the class, registering the handler for incoming `Create` activities to the ActivityPub plugin.
	 */
	public static function init() {
		\add_action(
			'activitypub_inbox_create',
			array( self::class, 'handle_create' ),
			15,
			2
		);
	}

	/**
	 * Handle incoming "Create" activities.
	 *
	 * @param array $activity The activity-object.
	 * @param int   $user_id  The id of the local blog-user.
	 */
	public static function handle_create( $activity, $user_id ) {
		// We only process activities that are target to the blog actor.
		if ( Actors::BLOG_USER_ID !== $user_id ) {
			return;
		}

		// Check that we are actually following this actor.
		if ( ! Event_Sources::actor_is_event_source( $activity['actor'] ) ) {
			return false;
		}

		// Check if Activity is public or not.
		if ( ! is_activity_public( $activity ) ) {
			return;
		}

		// Check if an object is set and it is an object of type `Event`.
		if ( ! isset( $activity['object']['type'] ) || 'Event' !== $activity['object']['type'] ) {
			return;
		}

		if ( Event_Sources::is_time_passed( $activity['object']['startTime'] ) ) {
			return new \WP_Error(
				'event_bridge_for_activitypub_not_accepting_events_from_the_past',
				__( 'We do not accept this event because it took place in the past.', 'event-bridge-for-activitypub' ),
				array( 'status' => 403 )
			);
		}

		$transmogrifier = Setup::get_transmogrifier();

		if ( ! $transmogrifier ) {
			return;
		}

		$transmogrifier->save( $activity['object'], $activity['actor'] );
	}
}
