<?php
/**
 * Update handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Collection\Actors;
use Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source;
use Event_Bridge_For_ActivityPub\Event_Sources;
use Event_Bridge_For_ActivityPub\Setup;

use function Activitypub\is_activity_public;

/**
 * Handle Update requests.
 */
class Update {
	/**
	 * Initialize the class, registering the handler for incoming `Update` activities to the ActivityPub plugin.
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
	 * Handle incoming "Update" activities..
	 *
	 * @param array $activity The activity-object.
	 * @param int   $user_id  The id of the local blog-user.
	 */
	public static function handle_update( $activity, $user_id ) {
		// We only process activities that are target to the application user.
		if ( Actors::BLOG_USER_ID !== $user_id ) {
			return;
		}

		// Check if Activity is public or not.
		if ( ! is_activity_public( $activity ) ) {
			return;
		}

		// Check if an object is set and it is an object of type `Event`.
		if ( ! isset( $activity['object']['type'] ) || 'Event' !== $activity['object']['type'] ) {
			return;
		}

		// Check that we are actually following/or have a pending follow request this actor.
		$event_source_post_id = Event_Source::get_post_id_by_activitypub_id( $activity['actor'] );
		if ( ! $event_source_post_id ) {
			return;
		}

		if ( Event_Sources::is_time_passed( $activity['object']['startTime'] ) ) {
			return;
		}

		$transmogrifier = Setup::get_transmogrifier();

		if ( ! $transmogrifier ) {
			return;
		}

		$transmogrifier::save( $activity['object'], $event_source_post_id );
	}
}
