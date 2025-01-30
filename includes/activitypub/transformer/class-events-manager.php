<?php
/**
 * ActivityPub Transformer for the plugin Very Simple Event List.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event as Event_Transformer;
use DateTime;
use DateTimeZone;
use EM_Event;

use function Activitypub\esc_hashtag;

/**
 * ActivityPub Transformer for events from the WordPress plugin 'Events Manager'
 *
 * @see https://wordpress.org/plugins/events-manager/
 *
 * @since 1.0.0
 */
final class Events_Manager extends Event_Transformer {

	/**
	 * Holds the EM_Event object.
	 *
	 * @var EM_Event
	 */
	protected $em_event;

	/**
	 * Extend the constructor, to also set the Events Manager objects.
	 *
	 * This is a special class object form The Events Calendar which
	 * has a lot of useful functions, we make use of our getter functions.
	 *
	 * @param \WP_Post $wp_object The WordPress object.
	 * @param string   $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object, $wp_taxonomy );
		$this->em_event = new EM_Event( $this->wp_object->ID, 'post_id' );
	}

	/**
	 * Returns whether the even is online
	 *
	 * @return bool
	 */
	protected function get_is_online(): bool {
		return 'url' === $this->em_event->event_location_type;
	}

	/**
	 * Get the event location.
	 *
	 * @return ?Place The Place.
	 */
	public function get_location(): ?Place {
		if ( 'url' === $this->em_event->event_location_type ) {
			return null;
		}

		$em_location = $this->em_event->get_location();

		if ( '' === $em_location->location_id ) {
			return null;
		}

		$location = new Place();
		$location->set_name( $em_location->location_name );

		$address = array(
			'type'            => 'PostalAddress',
			'addressCountry'  => $em_location->location_country,
			'addressLocality' => $em_location->location_town,
			'streetAddress'   => $em_location->location_address,
			'postalCode'      => $em_location->location_postcode,
			'name'            => $em_location->location_name,
		);
		if ( $em_location->location_state ) {
			$address['addressRegion'] = $em_location->location_state;
		}

		$location->set_address( $address );
		return $location;
	}

	/**
	 * Get the end time from the events metadata.
	 */
	public function get_end_time(): ?string {
		return null;
	}

	/**
	 * Get the end time from the events metadata.
	 */
	public function get_start_time(): string {
		$date_string     = $this->em_event->event_start_date;
		$time_string     = $this->em_event->event_start_time;
		$timezone_string = $this->em_event->event_timezone;

		// Create a DateTime object with the given date, time, and timezone.
		$datetime = new DateTime( $date_string . ' ' . $time_string, new DateTimeZone( $timezone_string ) );

		// Set the timezone for proper formatting.
		$datetime->setTimezone( new DateTimeZone( 'UTC' ) );

		// Format the DateTime object as 'Y-m-d\TH:i:s\Z'.
		$formatted_date = $datetime->format( 'Y-m-d\TH:i:s\Z' );
		return $formatted_date;
	}

	/**
	 * Returns the maximum attendee capacity.
	 *
	 * @return ?int
	 */
	public function get_maximum_attendee_capacity(): ?int {
		return $this->em_event->event_spaces;
	}

	/**
	 * Return the remaining attendee capacity
	 *
	 * @return ?int
	 */
	public function get_remaining_attendee_capacity(): ?int {
		$em_bookings_count = $this->get_participant_count();
		$max_bookings      = $this->em_event->event_spaces;

		if ( is_int( $max_bookings ) && is_int( $em_bookings_count ) ) {
			return $this->em_event->event_spaces - $em_bookings_count;
		}

		return null;
	}

	/**
	 * Returns the current participant count.
	 *
	 * @return int
	 */
	public function get_participant_count(): int {
		$em_bookings = $this->em_event->get_bookings()->get_bookings();
		return count( $em_bookings->bookings );
	}

	/**
	 * Get the event link as an ActivityPub Link object, but as an associative array.
	 *
	 * @return array
	 */
	private function get_event_link_attachment(): array {
		$event_link_url  = $this->em_event->event_location->data['url'];
		$event_link_text = $this->em_event->event_location->data['text'];
		return array(
			'type'      => 'Link',
			'name'      => $event_link_text ?? 'Website',
			'href'      => \esc_url( $event_link_url ),
			'mediaType' => 'text/html',
		);
	}

	/**
	 * Overrides/extends the get_attachments function to also add the event Link.
	 */
	protected function get_attachment(): array {
		// Get attachments via parent function.
		$attachments = parent::get_attachment();

		// The first attachment is the featured image, make sure it is compatible with Mobilizon.
		if ( count( $attachments ) ) {
			$attachments[0]['type'] = 'Document';
			$attachments[0]['name'] = 'Banner';
		}

		if ( 'url' === $this->em_event->event_location_type ) {
			$attachments[] = $this->get_event_link_attachment();
		}
		return $attachments;
	}

	/**
	 * Compose the events tags.
	 */
	public function get_tag(): array {
		// The parent tag function also fetches the mentions.
		$tags = parent::get_tag();

		$post_tags = \wp_get_post_terms( $this->wp_object->ID, 'event-tags' );

		if ( $post_tags ) {
			foreach ( $post_tags as $post_tag ) {
				$tag    = array(
					'type' => 'Hashtag',
					'href' => \esc_url( \get_tag_link( $post_tag->term_id ) ),
					'name' => esc_hashtag( $post_tag->name ),
				);
				$tags[] = $tag;
			}
		}
		return $tags;
	}

	/**
	 * Get the events title/name.
	 *
	 * @return string
	 */
	protected function get_name(): string {
		return $this->em_event->event_name;
	}
}
