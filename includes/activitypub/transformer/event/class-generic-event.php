<?php
/**
 * ActivityPub Transformer for Generic Event Plugin.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Event as Event_Transformer;

/**
 * ActivityPub Transformer for Generic Event.
 *
 * This transformer uses configurable field mappings to extract event data
 * from any post type configured by the user.
 *
 * @since 1.0.0
 */
final class Generic_Event extends Event_Transformer {

	/**
	 * Get the configured field mappings.
	 *
	 * @return array
	 */
	private function get_field_mappings(): array {
		return \get_option( 'event_bridge_for_activitypub_generic_field_mappings', array() );
	}

	/**
	 * Get value from a configured field mapping.
	 *
	 * @param string $field_type The field type (start_time, end_time, location, etc.).
	 * @return mixed
	 */
	private function get_mapped_field_value( string $field_type ) {
		$mappings = $this->get_field_mappings();
		
		if ( ! isset( $mappings[ $field_type ] ) || empty( $mappings[ $field_type ] ) ) {
			return null;
		}

		$field_config = $mappings[ $field_type ];
		$source_type = $field_config['source_type'] ?? 'meta';
		$field_name = $field_config['field_name'] ?? '';

		if ( empty( $field_name ) ) {
			return null;
		}

		switch ( $source_type ) {
			case 'meta':
				return \get_post_meta( $this->item->ID, $field_name, true );
			
			case 'post_field':
				return $this->item->{$field_name} ?? null;
			
			case 'taxonomy':
				$terms = \get_the_terms( $this->item->ID, $field_name );
				return is_array( $terms ) && ! empty( $terms ) ? $terms[0]->name : null;
			
			case 'custom_field':
				return \get_field( $field_name, $this->item->ID ); // ACF support
			
			default:
				return null;
		}
	}

	/**
	 * Get the event location.
	 *
	 * @return Place|null The Place.
	 */
	public function get_location(): ?Place {
		$location_value = $this->get_mapped_field_value( 'location' );
		
		if ( empty( $location_value ) ) {
			return null;
		}

		// Handle different location value formats
		if ( is_array( $location_value ) ) {
			// Structured location data
			$place = new Place();
			$place->set_type( 'Place' );
			
			if ( isset( $location_value['name'] ) ) {
				$place->set_name( $location_value['name'] );
			}
			
			if ( isset( $location_value['address'] ) ) {
				$place->set_address( $location_value['address'] );
			}
			
			return $place;
		} else {
			// Simple string location
			$place = new Place();
			$place->set_type( 'Place' );
			$place->set_name( $location_value );
			$place->set_address( $location_value );
			return $place;
		}
	}

	/**
	 * Get the end time from the configured field mapping.
	 *
	 * @return string|null
	 */
	public function get_end_time(): ?string {
		$end_time_value = $this->get_mapped_field_value( 'end_time' );
		
		if ( empty( $end_time_value ) ) {
			return null;
		}

		// Handle different time formats
		if ( is_numeric( $end_time_value ) ) {
			// Unix timestamp
			return \gmdate( 'Y-m-d\TH:i:s\Z', (int) $end_time_value );
		} elseif ( is_string( $end_time_value ) ) {
			// Try to parse as date string
			$timestamp = \strtotime( $end_time_value );
			if ( $timestamp !== false ) {
				return \gmdate( 'Y-m-d\TH:i:s\Z', $timestamp );
			}
		}

		return null;
	}

	/**
	 * Get the start time from the configured field mapping.
	 *
	 * @return string|null
	 */
	public function get_start_time(): ?string {
		$start_time_value = $this->get_mapped_field_value( 'start_time' );
		
		if ( empty( $start_time_value ) ) {
			// Fallback to post date if no start time is configured
			return \gmdate( 'Y-m-d\TH:i:s\Z', \strtotime( $this->item->post_date_gmt ) );
		}

		// Handle different time formats
		if ( is_numeric( $start_time_value ) ) {
			// Unix timestamp
			return \gmdate( 'Y-m-d\TH:i:s\Z', (int) $start_time_value );
		} elseif ( is_string( $start_time_value ) ) {
			// Try to parse as date string
			$timestamp = \strtotime( $start_time_value );
			if ( $timestamp !== false ) {
				return \gmdate( 'Y-m-d\TH:i:s\Z', $timestamp );
			}
		}

		// Fallback to post date
		return \gmdate( 'Y-m-d\TH:i:s\Z', \strtotime( $this->item->post_date_gmt ) );
	}

	/**
	 * Get the event URL/link from the configured field mapping.
	 *
	 * @return array|null Associated array of an ActivityStreams Link object.
	 */
	private function get_event_link(): ?array {
		$event_link = $this->get_mapped_field_value( 'event_link' );
		$event_link_label = $this->get_mapped_field_value( 'event_link_label' );
		
		if ( empty( $event_link ) ) {
			return null;
		}

		return array(
			'type'      => 'Link',
			'name'      => $event_link_label ?: __( 'Event Link', 'event-bridge-for-activitypub' ),
			'href'      => \esc_url( $event_link ),
			'mediaType' => 'text/html',
		);
	}

	/**
	 * Get the event description/summary from configured field mapping.
	 *
	 * @return string
	 */
	public function get_summary(): string {
		$summary_value = $this->get_mapped_field_value( 'summary' );
		
		if ( ! empty( $summary_value ) ) {
			return $summary_value;
		}

		// Fallback to default behavior
		return parent::get_summary();
	}

	/**
	 * Extends the get_attachment function to also add the event link.
	 *
	 * @return array
	 */
	protected function get_attachment(): array {
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
}