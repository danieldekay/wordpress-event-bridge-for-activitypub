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
use DateTime;
use DateTimeZone;

/**
 * ActivityPub Transformer for events from the WordPress plugin 'Events Manager'
 *
 * @see https://wordpress.org/plugins/events-manager/
 *
 * @since 1.0.0
 */
final class WP_Event_Manager extends Event_Transformer {
	/**
	 * Returns whether the even is online
	 *
	 * @return bool
	 */
	protected function get_is_online(): bool {
		$is_online_text = get_post_meta( $this->item->ID, '_event_online', true );
		$is_online      = false;
		// Radio buttons.
		if ( 'yes' === $is_online_text ) {
			$is_online = true;
		}
		// Checkbox.
		if ( '1' === $is_online_text ) {
			$is_online = true;
		}
		return $is_online;
	}

	/**
	 * Get the event location.
	 *
	 * @return ?Place The Place.
	 */
	public function get_location(): ?Place {
		$location_name = get_post_meta( $this->item->ID, '_event_location', true );

		if ( $location_name ) {
			$location = new Place();
			$location->set_name( $location_name );
			$location->set_sensitive( null );
			$location->set_address( $location_name );

			return $location;
		}
		return null;
	}

	/**
	 * Get the end time from the events metadata.
	 *
	 * @return ?string The events end-datetime if is set, null otherwise.
	 */
	public function get_end_time(): ?string {
		$end_date = get_post_meta( $this->item->ID, '_event_end_date', true );
		if ( ! $end_date ) {
			return null;
		}
		$timezone = new DateTimeZone( $this->get_timezone() );

		if ( is_numeric( $end_date ) ) {
			$end_date = '@' . $end_date;
		}

		$end_datetime = new DateTime( $end_date, $timezone );

		return $end_datetime->format( 'Y-m-d\TH:i:sP' );
	}

	/**
	 * Get timezone.
	 *
	 * @return string
	 */
	public function get_timezone(): string {
		$time_zone = get_post_meta( $this->item->ID, '_event_timezone', true );
		if ( $time_zone ) {
			return $time_zone;
		}
		return parent::get_timezone();
	}

	/**
	 * Get the end time from the events metadata.
	 */
	public function get_start_time(): string {
		$start_date = get_post_meta( $this->item->ID, '_event_start_date', true );
		$timezone   = new DateTimeZone( $this->get_timezone() );

		if ( is_numeric( $start_date ) ) {
			$start_date = '@' . $start_date;
		}

		$start_datetime = new DateTime( $start_date, $timezone );

		return $start_datetime->format( 'Y-m-d\TH:i:sP' );
	}

	/**
	 * Get the event link as an ActivityPub Link object, but as an associative array.
	 *
	 * @return ?array
	 */
	private function get_event_link_attachment(): ?array {
		$event_link_url = get_post_meta( $this->item->ID, '_event_video_url', true );

		if ( str_starts_with( $event_link_url, 'http' ) ) {
			return array(
				'type'      => 'Link',
				'name'      => \esc_html__( 'Video URL', 'event-bridge-for-activitypub' ),
				'href'      => \esc_url( $event_link_url ),
				'mediaType' => 'text/html',
			);
		} else {
			return null;
		}
	}

	/**
	 * Overrides/extends the get_attachments function to also add the event Link.
	 */
	protected function get_attachment() {
		// Get attachments via parent function.
		$attachments = parent::get_attachment();

		// The first attachment is the featured image, make sure it is compatible with Mobilizon.
		if ( count( $attachments ) ) {
			$attachments[0]['type'] = 'Document';
			$attachments[0]['name'] = 'Banner';
		}

		if ( $this->get_event_link_attachment() ) {
			$attachments[] = $this->get_event_link_attachment();
		}
		return $attachments;
	}

	/**
	 * Get the events title/name.
	 *
	 * @return string
	 */
	protected function get_name(): string {
		return $this->item->post_title;
	}
}
