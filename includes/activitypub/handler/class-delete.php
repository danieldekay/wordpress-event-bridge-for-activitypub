<?php
/**
 * Delete handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

use Activitypub\Notification;
use Activitypub\Collection\Actors;

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
			array( self::class, 'handle_delete' )
		);
	}

	/**
	 * Handle "Follow" requests.
	 *
	 * @param array $activity The activity object.
	 */
	public static function handle_delete( $activity ) {
		if ( ! isset( $activity['object'] ) ) {
			return;
		}

		$object = Actors::get_by_resource( $activity['object'] );

		if ( ! $object || is_wp_error( $object ) ) {
			// If we can not find a actor, we handle the `Delete` activity.
			return;
		}

		// We only expect `Delete` activities being answers to follow requests by the application actor.
		if ( Actors::APPLICATION_USER_ID !== $object->get__id() ) {
			return;
		}
	}
}
