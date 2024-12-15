<?php
/**
 * Create handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

use Activitypub\Collection\Actors;
use DateTime;
use DateTimeZone;
use Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources;
use Event_Bridge_For_ActivityPub\Setup;

use function Activitypub\is_activity_public;

/**
 * Handle Create requests.
 */
class Create {
	/**
	 * Initialize the class, registering WordPress hooks.
	 */
	public static function init() {
		\add_action(
			'activitypub_inbox_create',
			array( self::class, 'handle_create' ),
			15,
			2
		);
		\add_filter(
			'activitypub_validate_object',
			array( self::class, 'validate_object' ),
			12,
			3
		);
	}

	/**
	 * Handle "Create" requests.
	 *
	 * @param array $activity The activity-object.
	 * @param int   $user_id  The id of the local blog-user.
	 */
	public static function handle_create( $activity, $user_id ) {
		// We only process activities that are target to the application user.
		if ( Actors::BLOG_USER_ID !== $user_id ) {
			return;
		}

		if ( ! self::actor_is_event_source( $activity['actor'] ) ) {
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

		if ( self::is_time_passed( $activity['object']['startTime'] ) ) {
			return;
		}

		$transmogrifier = Setup::get_transmogrifier();

		if ( ! $transmogrifier ) {
			return;
		}

		$transmogrifier->save( $activity['object'] );
	}

	/**
	 * Validate the object.
	 *
	 * @param bool             $valid   The validation state.
	 * @param string           $param   The object parameter.
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return bool The validation state: true if valid, false if not.
	 */
	public static function validate_object( $valid, $param, $request ) {
		$json_params = $request->get_json_params();

		if ( isset( $json_params['object']['type'] ) && 'Event' === $json_params['object']['type'] ) {
			$valid = true;
		} else {
			return $valid;
		}

		if ( empty( $json_params['type'] ) ) {
			return false;
		}

		if ( empty( $json_params['actor'] ) ) {
			return false;
		}

		if ( ! in_array( $json_params['type'], array( 'Create', 'Update', 'Delete', 'Announce' ), true ) || is_wp_error( $request ) ) {
			return $valid;
		}

		$object = $json_params['object'];

		if ( ! is_array( $object ) ) {
			return false;
		}

		$required = array(
			'id',
			'startTime',
			'name',
		);

		if ( array_intersect( $required, array_keys( $object ) ) !== $required ) {
			return false;
		}

		return $valid;
	}

	/**
	 * Check if a given DateTime is already passed.
	 *
	 * @param string $time_string The ActivityPub like time string.
	 * @return bool
	 */
	private static function is_time_passed( $time_string ) {
		// Create a DateTime object from the ActivityPub time string.
		$time = new DateTime( $time_string, new DateTimeZone( 'UTC' ) );

		// Get the current time in UTC.
		$current_time = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

		// Compare the event time with the current time.
		return $time < $current_time;
	}

	/**
	 * Check if an ActivityPub actor is an event source.
	 *
	 * @param string $actor_id The actor ID.
	 * @return bool
	 */
	public static function actor_is_event_source( $actor_id ) {
		$event_sources = Event_Sources::get_event_sources();
		foreach ( $event_sources as $event_source ) {
			if ( $actor_id === $event_source->get_id() ) {
				return true;
			}
		}
		return false;
	}
}
