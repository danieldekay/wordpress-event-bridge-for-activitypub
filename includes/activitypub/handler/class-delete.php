<?php
/**
 * Delete handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Collection\Actors;
use Event_Bridge_For_ActivityPub\Event_Sources;
use Event_Bridge_For_ActivityPub\Setup;

use function Activitypub\object_to_uri;

/**
 * Handle Delete requests.
 */
class Delete {
	/**
	 * Initialize the class, registering the handler for incoming `Delete` activities to the ActivityPub plugin.
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
	public static function handle_delete( $activity, $user_id ): void {
		// We only process activities that are target to the application user.
		if ( Actors::BLOG_USER_ID !== $user_id ) {
			return;
		}

		// Check that we are actually following this actor.
		if ( ! Event_Sources::actor_is_event_source( $activity['actor'] ) ) {
			return;
		}

		$id = object_to_uri( $activity['object'] );

		$transmogrifier = Setup::get_transmogrifier();

		if ( ! $transmogrifier ) {
			return;
		}

		$transmogrifier::delete( $id );
	}
}
