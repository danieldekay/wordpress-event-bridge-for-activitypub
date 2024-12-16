<?php
/**
 * Class responsible for registering handlers for incoming activities to the ActivityPub plugin.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use DateTime;
use DateTimeZone;
use Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources;
use Event_Bridge_For_ActivityPub\ActivityPub\Handler\Accept;
use Event_Bridge_For_ActivityPub\ActivityPub\Handler\Update;
use Event_Bridge_For_ActivityPub\ActivityPub\Handler\Create;
use Event_Bridge_For_ActivityPub\ActivityPub\Handler\Delete;

/**
 *  Class responsible for registering handlers for incoming activities to the ActivityPub plugin.
 */
class Handler {
	/**
	 * Register all ActivityPub handlers.
	 */
	public static function register_handlers() {
		Accept::init();
		Update::init();
		Create::init();
		Delete::init();
		\add_filter(
			'activitypub_validate_object',
			array( self::class, 'validate_object' ),
			12,
			3
		);
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
	public static function is_time_passed( $time_string ) {
		// Create a DateTime object from the ActivityPub time string.
		$time = new DateTime( $time_string, new DateTimeZone( 'UTC' ) );

		// Get the current time in UTC.
		$current_time = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

		// Compare the event time with the current time.
		return $time < $current_time;
	}

	/**
	 * Check that an ActivityPub actor is an event source (i.e. it is followed by the ActivityPub blog actor).
	 *
	 * @param string $actor_id The actor ID.
	 * @return bool True if the ActivityPub actor ID is followed, false otherwise.
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
