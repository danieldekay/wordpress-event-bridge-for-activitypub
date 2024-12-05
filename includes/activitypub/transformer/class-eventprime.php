<?php
/**
 * ActivityPub Transformer for the plugin EventPrime.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Event_Bridge_For_ActivityPub\Activitypub\Transformer\Event;

/**
 * ActivityPub Transformer for VS Event
 *
 * @since 1.0.0
 */
final class EventPrime extends Event {
	/**
	 * Get the end time from the event object.
	 */
	protected function get_end_time(): ?string {
		$timestamp = get_post_meta( $this->wp_object->ID, 'em_end_date', true );
		if ( $timestamp ) {
			return \gmdate( 'Y-m-d\TH:i:s\Z', $timestamp );
		} else {
			return null;
		}
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_start_time(): string {
		$timestamp = get_post_meta( $this->wp_object->ID, 'em_start_date', true );
		if ( $timestamp ) {
			return \gmdate( 'Y-m-d\TH:i:s\Z', $timestamp );
		} else {
			return '';
		}
	}

	/**
	 * Get location from the event object.
	 */
	protected function get_location(): ?Place {
		$venue_term_id = get_post_meta( $this->wp_object->ID, 'em_venue', true );
		if ( ! $venue_term_id ) {
			return null;
		}

		$venue = wp_get_post_terms( $this->wp_object->ID, 'em_venue' );

		if ( empty( $venue ) ) {
			return null;
		} else {
			$venue = $venue[0];
		}

		$place = new Place();

		$place->set_name( $venue->name );
		$place->set_content( $venue->description );

		$address         = get_term_meta( $venue->term_id, 'em_address', true );
		$display_address = get_term_meta( $venue->term_id, 'em_display_address_on_frontend', true );

		if ( $address && $display_address ) {
			$place->set_address( get_term_meta( $venue->term_id, 'em_address', true ) );
		}

		return $place;
	}
}
