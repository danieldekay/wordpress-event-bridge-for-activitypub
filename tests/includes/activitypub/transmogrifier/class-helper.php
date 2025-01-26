<?php
/**
 * Helpers for Transmogrifier tests.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests\ActivityPub\Transmogrifier;

/**
 * Helpers for Transmogrifier tests.
 */
class Helper {
	/**
	 * Get an Gancio mockup event.
	 *
	 * @return array The ActivityPub event object.
	 */
	public static function get_gancio_event() {
		$event = json_decode( file_get_contents( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . 'tests/fixtures/events/gancio-v1.22.json' ), true );

		$args = array(
			'offset'       => '+1 hour',
			'milliseconds' => true,
		);

		$event = self::event_set_dates( $event, $args );

		// Set mockup attachment url.
		$event['attachment'][0]['url'] = EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_URL . '.wordpress-org/banner-772x250.jpg';

		return $event;
	}

	/**
	 * Set startDate
	 *
	 * @param array $event The ActivityPub event object as an associative array.
	 * @param array $args  Optional arguments:
	 *                     - 'start_date' (int)    The start date timestamp (default: time() + WEEK_IN_SECONDS).
	 *                     - 'duration' (int)      The duration of the event in seconds (default: HOUR_IN_SECONDS).
	 *                     - 'offset' (string)     A relative time offset (e.g., '+1 hour', '-30 minutes').
	 *                     - 'timezone' (string)   The optional time zone (default: '').
	 *                     - 'format' (string)     The date format (default: 'Y-m-d\TH:i:sP').
	 *                     - 'milliseconds' (bool) Whether to include milliseconds.
	 *
	 * @return array Modified event object with start and end times.
	 */
	public static function event_set_dates( $event, $args = array() ) {
		$args = array_merge(
			array(
				'start_date'   => time() + WEEK_IN_SECONDS,
				'duration'     => HOUR_IN_SECONDS,
				'offset'       => '', // No offset by default.
				'timezone'     => null, // No timezone information available by default.
				'format'       => 'Y-m-d\TH:i:sP', // Default format includes offset.
				'milliseconds' => false, // Do not include milliseconds.
			),
			$args
		);

		$format = $args['format'];

		if ( $args['milliseconds'] ) {
			$format = str_replace( 'H:i:s', 'H:i:s.000', $format );
		}

		$published_format = str_replace( 'P', 'Z', $format );

		// Create DateTime object.
		$timezone       = $args['timezone'] ? new \DateTimeZone( $args['timezone'] ) : new \DateTimeZone( 'UTC' );
		$start_datetime = new \DateTime( '@' . $args['start_date'], $timezone );

		// Apply offset without changing the resulting UTC time, only works if timezone is not passed in args.
		if ( ! $args['timezone'] && ! empty( $args['offset'] ) ) {
			$offset_datetime = clone $start_datetime;
			$offset_datetime->modify( $args['offset'] );

			// Calculate the difference caused by the offset.
			$offset_seconds = $offset_datetime->getTimestamp() - $start_datetime->getTimestamp();
			$offset_hours   = floor( $offset_seconds / 3600 );
			$offset_minutes = ( $offset_seconds % 3600 ) / 60;

			// Manually set the offset to the display time, keeping the actual time same.
			$offset_string = sprintf( '%+03d:%02d', $offset_hours, abs( $offset_minutes ) );
			$start_datetime->setTimezone( new \DateTimeZone( $offset_string ) );
		}

		// End time calculation based on duration.
		$end_datetime = clone $start_datetime;
		$end_datetime->modify( '+' . $args['duration'] . ' seconds' );

		// Published time in UTC.
		$published_datetime = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );

		// Format times.
		$event['startTime'] = $start_datetime->format( $format );
		$event['endTime']   = $end_datetime->format( $format );
		$event['published'] = $published_datetime->format( $published_format ); // Always in UTC.

		// Add timezone information if available.
		if ( ! empty( $args['timezone'] ) ) {
			$event['timezone'] = $args['timezone'];
		}

		return $event;
	}
}
