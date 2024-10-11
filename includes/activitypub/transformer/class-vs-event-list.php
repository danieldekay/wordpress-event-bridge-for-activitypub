<?php
/**
 * ActivityPub Transformer for the plugin Very Simple Event List.
 *
 * @package ActivityPub_Event_Bridge
 * @license AGPL-3.0-or-later
 */

namespace ActivityPub_Event_Bridge\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use ActivityPub_Event_Bridge\Activitypub\Transformer\Event as Event_Transformer;

/**
 * ActivityPub Transformer for VS Event.
 *
 * This transformer tries a different principle: The setters are chainable.
 *
 * @since 1.0.0
 */
final class VS_Event_List extends Event_Transformer {

	/**
	 * Get the event location.
	 *
	 * @return Place The Place.
	 */
	public function get_location(): ?Place {
		$address = get_post_meta( $this->wp_object->ID, 'event-location', true );
		if ( $address ) {
			$place = new Place();
			$place->set_type( 'Place' );
			$place->set_name( $address );
			$place->set_address( $address );
			return $place;
		} else {
			return null;
		}
	}

	/**
	 * Get the end time from the events metadata.
	 */
	protected function get_end_time(): ?string {
		if ( 'yes' === get_post_meta( $this->wp_object->ID, 'event-hide-end-time', true ) ) {
			return null;
		}
		$end_time = get_post_meta( $this->wp_object->ID, 'event-date', true );
		if ( is_null( $end_time ) || empty( $end_time ) || 'no' === $end_time ) {
			return null;
		}
		return $end_time ? \gmdate( 'Y-m-d\TH:i:s\Z', $end_time ) : null;
	}

	/**
	 * Get the end time from the events metadata.
	 */
	public function get_start_time(): string {
		$start_time = get_post_meta( $this->wp_object->ID, 'event-start-date', true );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $start_time );
	}

	/**
	 * Get the event link from the events metadata.
	 *
	 * @return ?array Associated array of an ActivityStreams Link object with the events URL.
	 */
	private function get_event_link(): ?array {
		$event_link       = get_post_meta( $this->wp_object->ID, 'event-link', true );
		$event_link_label = get_post_meta( $this->wp_object->ID, 'event-link-label', true ) ?? 'Event Link';
		if ( $event_link ) {
			return array(
				'type'      => 'Link',
				'name'      => $event_link_label,
				'href'      => \esc_url( $event_link ),
				'mediaType' => 'text/html',
			);
		}
		return null;
	}

	/**
	 * Overrides/extends the get_attachments function to also add the event Link.
	 */
	protected function get_attachment(): ?array {
		$attachments = parent::get_attachment();
		if ( count( $attachments ) ) {
			$attachments[0]['type'] = 'Document';
			$attachments[0]['name'] = 'Banner';
		}
		$event_link = $this->get_event_link();
		if ( $event_link ) {
			$attachments[] = $event_link;
		}
		return $attachments;
	}

	/**
	 * Retrieves the excerpt text (may be HTML). Used for constructing the summary.
	 *
	 * @return ?string
	 */
	protected function retrieve_excerpt(): ?string {
		if ( get_post_meta( $this->wp_object->ID, 'event-summary', true ) ) {
			return get_post_meta( $this->wp_object->ID, 'event-summary', true );
		} else {
			return parent::retrieve_excerpt();
		}
	}
}
