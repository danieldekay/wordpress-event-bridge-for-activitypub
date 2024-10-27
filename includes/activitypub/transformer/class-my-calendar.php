<?php
/**
 * ActivityPub Transformer for the WordPress plugin "My Calendar – Accessible Event Manager".
 *
 * @see https://wordpress.org/plugins/my-calendar/
 *
 * @package Activitypub_Event_Extensions
 * @license AGPL-3.0-or-later
 */

namespace ActivityPub_Event_Bridge\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use ActivityPub_Event_Bridge\Activitypub\Transformer\Event as Event_Transformer;
use DateTime;
use DateTimeZone;

/**
 * ActivityPub Transformer for events from the WordPress plugin  "My Calendar – Accessible Event Manager".
 *
 * @see https://wordpress.org/plugins/my-calendar/
 *
 * @since 1.0.0
 */
final class My_Calendar extends Event_Transformer {
	/**
	 * Holds the My Calendar post object.
	 *
	 * @var object $event Event object.
	 */
	protected $mc_event;

	/**
	 * Holds the My Calendar Schema object.
	 *
	 * @var array JSON/LD Schema for event.
	 */
	protected $mc_event_schema;

	/**
	 * Extend the constructor, to also set the Event plugins API objects.
	 *
	 * This is a special class object form The Events Calendar which
	 * has a lot of useful functions, we make use of our getter functions.
	 *
	 * @param WP_Post $wp_object    The WordPress object.
	 * @param string  $wp_taxonomy  The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object, $wp_taxonomy );
		$mc_event_id           = get_post_meta( $this->wp_object->ID, '_mc_event_id', true );
		$this->mc_event        = mc_get_event( $mc_event_id );
		$this->mc_event_schema = mc_event_schema( $this->mc_event );
	}

	/**
	 * Formats time from the plugin to the activitypub standard.
	 *
	 * @param string $date_string  The plugins string representation for a date without time.
	 * @param string $time_string   The plugins string representation for a time.
	 *
	 * @return string
	 */
	private function convert_time( $date_string, $time_string ): string {
		// Create a DateTime object with the given date, time, and timezone.
		$datetime = new DateTime( $date_string . ' ' . $time_string );

		// Set the timezone for proper formatting.
		$datetime->setTimezone( new DateTimeZone( 'UTC' ) );

		// Format the DateTime object as 'Y-m-d\TH:i:s\Z'.
		$formatted_date = $datetime->format( 'Y-m-d\TH:i:s\Z' );
		return $formatted_date;
	}
	/**
	 * Get the start time from the events metadata.
	 *
	 * @return string The events start date-time.
	 */
	public function get_start_time(): string {
		return $this->convert_time( $this->mc_event->event_begin, $this->mc_event->event_time );
	}

	/**
	 * Get the end time from the events metadata.
	 *
	 * @return string The events start end-time.
	 */
	public function get_end_time(): ?string {
		return $this->convert_time( $this->mc_event->event_end, $this->mc_event->event_endtime );
	}

	/**
	 * Get the event location.
	 *
	 * @return Place|null The place/venue if one is set.
	 */
	public function get_location(): ?Place {
		if ( array_key_exists( 'location', $this->mc_event_schema ) && 'Place' === $this->mc_event_schema['location']['@type'] ) {
			$mc_place = $this->mc_event_schema['location'];

			$place = new Place();
			$place->set_name( $mc_place['name'] );
			$place->set_url( $mc_place['url'] );
			$place->set_address( $mc_place['address'] );

			if ( ! empty( $mc_place['geo'] ) ) {
				$place->set_latitude( $mc_place['geo']['latitude'] );
				$place->set_longitude( $mc_place['geo']['longitude'] );
			}
			return $place;
		}
		return null;
	}

	/**
	 * Get status of the event
	 *
	 * @return string status of the event
	 */
	public function get_status(): ?string {
		return 'CONFIRMED'; // My Calendar doesn't implement canceled events.
	}

	/**
	 * Extract the external participation url.
	 *
	 * @return ?string The external participation URL.
	 */
	public function get_external_participation_url(): ?string {

		return $this->mc_event->event_tickets ? $this->mc_event->event_tickets : null;
	}
}
