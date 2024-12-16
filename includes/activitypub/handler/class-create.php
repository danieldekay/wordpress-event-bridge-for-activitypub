<?php
/**
 * Create handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

use Activitypub\Collection\Actors;
use Event_Bridge_For_ActivityPub\Setup;
use Event_Bridge_For_ActivityPub\ActivityPub\Handler;

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
		// We only process activities that are target to the application user.
		if ( Actors::BLOG_USER_ID !== $user_id ) {
			return;
		}

		if ( ! Handler::actor_is_event_source( $activity['actor'] ) ) {
			return;
		}

		// Check if Activity is public or not.
		if ( ! is_activity_public( $activity ) ) {
			return;
		}

		// Check if an object is set.
		if ( ! isset( $activity['object']['type'] ) || 'Event' !== $activity['object']['type'] ) {
			return;
		}

		if ( Handler::is_time_passed( $activity['object']['startTime'] ) ) {
			return;
		}

		$transmogrifier = Setup::get_transmogrifier();

		if ( ! $transmogrifier ) {
			return;
		}

		$transmogrifier->save( $activity['object'] );
	}
}
