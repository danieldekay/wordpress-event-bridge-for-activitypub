<?php
/**
 * Update handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Handle Update requests.
 */
class Update {
	/**
	 * Initialize the class, registering the handler for incoming `Update` activities to the ActivityPub plugin.
	 */
	public static function init(): void {
		\add_action(
			'activitypub_inbox_update',
			array( self::class, 'handle_update' ),
			15,
			2
		);
	}

	/**
	 * Handle incoming "Update" activities..
	 *
	 * @param array $activity The activity-object.
	 * @param int   $user_id  The id of the local blog-user.
	 */
	public static function handle_update( $activity, $user_id ): void {
		// We handle updates the same as we handle creates for now (specification though says we should ignore it).
		Create::handle_create( $activity, $user_id );
	}
}
