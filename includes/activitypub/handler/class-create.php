<?php
/**
 * Create handler file.
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
 * Handle Create requests.
 */
class Create {
	/**
	 * Initialize the class, registering the handler for incoming `Create` activities to the ActivityPub plugin.
	 */
	public static function init(): void {
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
	public static function handle_create( $activity, $user_id ): void {
		// We only process activities that are target to the blog actor.
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

		// Apply custom filters whether an Event should be ignored.
		if ( \apply_filters( 'event_bridge_for_activitypub_ignore_incoming_event', false, $activity['object'], $event_source_post_id ) ) {
			return;
		}

		$transmogrifier = Setup::get_transmogrifier();

		if ( ! $transmogrifier ) {
			return;
		}

		$transmogrifier::save( $activity['object'], $event_source_post_id );
	}
}
