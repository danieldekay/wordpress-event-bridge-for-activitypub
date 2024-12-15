<?php
/**
 * Update handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

use Activitypub\Collection\Actors;
use Event_Bridge_For_ActivityPub\Setup;

use function Activitypub\is_activity_public;

/**
 * Handle Update requests.
 */
class Update {
	/**
	 * Initialize the class, registering WordPress hooks.
	 */
	public static function init() {
		\add_action(
			'activitypub_inbox_update',
			array( self::class, 'handle_update' ),
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
	public static function handle_update( $activity, $user_id ) {
		// We only process activities that are target the application user.
		if ( Actors::BLOG_USER_ID !== $user_id ) {
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

		$transmogrifier_class = Setup::get_transmogrifier();

		if ( ! $transmogrifier_class ) {
			return;
		}

		$transmogrifier = new $transmogrifier_class( $activity['object'] );
		$transmogrifier->save();
	}
}
