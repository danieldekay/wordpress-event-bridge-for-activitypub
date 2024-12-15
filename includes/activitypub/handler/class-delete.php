<?php
/**
 * Delete handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

use Activitypub\Collection\Actors;
use Event_Bridge_For_ActivityPub\Setup;

/**
 * Handle Delete requests.
 */
class Delete {
	/**
	 * Initialize the class, registering WordPress hooks.
	 */
	public static function init() {
		\add_action(
			'activitypub_inbox_delete',
			array( self::class, 'handle_delete' ),
			15,
			2
		);
	}

	/**
	 * Handle "Follow" requests.
	 *
	 * @param array $activity The activity-object.
	 * @param int   $user_id  The id of the local blog-user.
	 */
	public static function handle_delete( $activity, $user_id ) {
		// We only process activities that are target to the application user.
		if ( Actors::BLOG_USER_ID !== $user_id ) {
			return;
		}

		if ( ! Create::actor_is_event_source( $activity['actor'] ) ) {
			return;
		}

		// Check if an object is set.
		if ( ! isset( $activity['object']['type'] ) || 'Event' !== $activity['object']['type'] ) {
			return;
		}

		$transmogrifier = Setup::get_transmogrifier();

		if ( ! $transmogrifier ) {
			return;
		}

		$transmogrifier->delete( $activity['object'] );
	}
}
