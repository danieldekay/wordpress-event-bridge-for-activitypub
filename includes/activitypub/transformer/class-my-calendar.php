<?php
/**
 * ActivityPub Transformer for the plugin My Calendar.
 *
 * @package Activitypub_Event_Extensions
 * @license AGPL-3.0-or-later
 */

namespace Activitypub_Event_Extensions\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Event;
use Activitypub\Activity\Extended_Object\Place;
use Activitypub_Event_Extensions\Activitypub\Transformer\Event as Event_Transformer;
use DateTime;
use DateTimeZone;
use EM_Event;

use function Activitypub\esc_hashtag;

/**
 * ActivityPub Transformer for events from the WordPress plugin 'My Calendar'
 *
 * @see https://wordpress.org/plugins/my-calendar/
 *
 * @since 1.0.0
 */
final class My_Calendar extends Event_Transformer {
	/**
	 * Holds the mycalendar post object.
	 *
	 * @var array
	 */
	protected $mc_event;

	/**
	 * Extend the constructor, to also set the Eventsmanager objects.
	 *
	 * This is a special class object form The Events Calendar which
	 * has a lot of useful functions, we make use of our getter functions.
	 *
	 * @param WP_Post $wp_object The WordPress object.
	 * @param string $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object, $wp_taxonomy );
		$this->mc_event = get_post_meta( $wp_object->ID, '_mc_event_data', true);
	}


	/**
	 * Formats time from the plugin to the activitypub standard
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
	 */
	public function get_start_time(): string {
		return $this->convert_time( $this->mc_event['event_begin'], $this->mc_event['event_time']);
	}

	/**
	 * Get the end time from the events metadata.
	 */
	public function get_end_time(): ?string {
		return $this->convert_time( $this->mc_event['event_end'], $this->mc_event['event_endtime']);
	}

	public function to_object(): Event {
		$activitypub_object = parent::to_object();

		return $activitypub_object;
	}


}
