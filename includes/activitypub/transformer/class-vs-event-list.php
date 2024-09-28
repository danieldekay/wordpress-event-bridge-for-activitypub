<?php
/**
 * ActivityPub Transformer for the plugin Very Simple Event List.
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

/**
 * ActivityPub Transformer for VS Event.
 *
 * This transformer tries a different principle: The setters are chainable.
 *
 * @since 1.0.0
 */
final class VS_Event_List extends Event_Transformer {
	/**
	 * The target transformer ActivityPub Event object.
	 *
	 * @var Event
	 */
	protected $ap_object;

	/**
	 * Returns the ActivityStreams 2.0 Object-Type for an Event.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-event
	 * @since 1.0.0
	 * @return string The Event Object-Type.
	 */
	protected function get_type(): string {
		return 'Event';
	}

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
		return $end_time ? \gmdate( 'Y-m-d\TH:i:s\Z', $end_time ) : null;
	}

	/**
	 * Get the end time from the events metadata.
	 */
	protected function get_start_time(): string {
		$start_time = get_post_meta( $this->wp_object->ID, 'event-start-date', true );
		return $start_time ? \gmdate( 'Y-m-d\TH:i:s\Z', $start_time ) : null;
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
	 * Create a custom summary.
	 *
	 * It contains also the most important meta-information. The summary is often used when the
	 * ActivityPub object type 'Event' is not supported, e.g. in Mastodon.
	 *
	 * @return string $summary The custom event summary.
	 */
	public function get_summary(): ?string {
		if ( $this->wp_object->excerpt ) {
			$excerpt = $this->wp_object->post_excerpt;
		} elseif ( get_post_meta( $this->wp_object->ID, 'event-summary', true ) ) {
			$excerpt = get_post_meta( $this->wp_object->ID, 'event-summary', true );
		} else {
			$excerpt = $this->get_content();
		}

		$address           = get_post_meta( $this->wp_object->ID, 'event-location', true );
		$start_time        = get_post_meta( $this->wp_object->ID, 'event-start-date', true );
		$datetime_format   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$start_time_string = wp_date( $datetime_format, $start_time );
		$summary           = "📍 {$address}\n📅 {$start_time_string}\n\n{$excerpt}";
		return $summary;
	}
}
