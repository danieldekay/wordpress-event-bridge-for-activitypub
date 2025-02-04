<?php
/**
 * ActivityPub Transformer for the plugin Very Simple Event List.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Event as Event_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place\Events_Manager as Events_Manager_Place_Transformer;
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
	 * @param \WP_Post $item The WordPress object.
	 * @param string   $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $item, $wp_taxonomy ) {
		parent::__construct( $item, $wp_taxonomy );
		$this->em_event = new EM_Event( $this->item->ID, 'post_id' );
	}

	/**
	 * Returns whether the even is online
	 *
	 * @return bool
	 */
	protected function is_online(): bool {
		return \EM_Event_Locations\Event_Locations::is_enabled( 'url' ) && 'url' === $this->em_event->event_location_type;
	}

	/**
	 * Get the event location.
	 *
	 * @return array|Place|null The Place.
	 */
	public function get_location() {
		if ( $this->is_online() ) {
			if ( property_exists( $this->em_event->event_location, 'data' ) ) {
				$event_location = $this->em_event->event_location->data;
			} else {
				$event_location = array();
			}

			$event_link_url  = isset( $event_location['url'] ) ? $event_location['url'] : null;
			$event_link_text = isset( $event_location['text'] ) ? $event_location['text'] : esc_html__( 'Link', 'event-bridge-for-activitypub' );

			if ( empty( $event_link_url ) ) {
				return null;
			}

			return array(
				'type' => 'VirtualLocation',
				'url'  => \esc_url( $event_link_url ),
				'name' => \esc_html( $event_link_text ),
			);
		}

		if ( ! \EM_Locations::is_enabled() ) {
			return null;
		}

		$em_location = $this->em_event->get_location();

		if ( ! isset( $em_location->post_id ) || ! $em_location->post_id ) {
			return null;
		}

		$location_transformer = new Events_Manager_Place_Transformer( get_post( $em_location->post_id ) );
		$full_location_object = false;
		$location             = $location_transformer->to_object( $full_location_object );
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
	public function get_maximum_attendee_capacity() {
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

		if ( $max_bookings && $em_bookings_count ) {
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
	 * @return array|null
	 */
	private function get_event_link_attachment(): ?array {
		if ( $this->is_online() ) {
			if ( property_exists( $this->em_event->event_location, 'data' ) ) {
				$event_location = $this->em_event->event_location->data;
			} else {
				$event_location = array();
			}

			$event_link_url  = isset( $event_location['url'] ) ? $event_location['url'] : null;
			$event_link_text = isset( $event_location['text'] ) ? $event_location['text'] : __( 'Link', 'event-bridge-for-activitypub' );

			if ( empty( $event_link_url ) ) {
				return null;
			}

			return array(
				'type'      => 'Link',
				'name'      => \esc_html( $event_link_text ),
				'href'      => \esc_url( $event_link_url ),
				'mediaType' => 'text/html',
			);
		}

		return null;
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

		$event_link_attachment = $this->get_event_link_attachment();

		if ( $event_link_attachment ) {
			$attachments[] = $event_link_attachment;
		}
		return $attachments;
	}

	/**
	 * Compose the events tags.
	 */
	public function get_tag(): array {
		// The parent tag function also fetches the mentions.
		$tags = parent::get_tag();

		$post_tags = \wp_get_post_terms( $this->item->ID, 'event-tags' );

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
