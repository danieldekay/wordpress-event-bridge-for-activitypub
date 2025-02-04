<?php
/**
 * ActivityPub Transformer for the plugin EventPrime.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Event as Base_Event_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place\EventPrime as EventPrime_Location_Transformer;

/**
 * ActivityPub Transformer for VS Event
 *
 * @since 1.0.0
 */
final class EventPrime extends Base_Event_Transformer {
	/**
	 * Get the end time from the event object.
	 */
	public function get_end_time(): ?string {
		$timestamp = \get_post_meta( $this->wp_object->ID, 'em_end_date', true );
		if ( $timestamp ) {
			return \gmdate( 'Y-m-d\TH:i:s\Z', $timestamp );
		} else {
			return null;
		}
	}

	/**
	 * Get the end time from the event object.
	 */
	public function get_start_time(): string {
		$timestamp = \get_post_meta( $this->wp_object->ID, 'em_start_date', true );
		if ( $timestamp ) {
			return \gmdate( 'Y-m-d\TH:i:s\Z', $timestamp );
		} else {
			return '';
		}
	}

	/**
	 * Get location from the event object.
	 */
	public function get_location(): ?Place {
		$venue_term_id = \get_post_meta( $this->item->ID, 'em_venue', true );
		if ( ! $venue_term_id ) {
			return null;
		}

		$venue = \get_the_terms( $this->item->ID, 'em_venue' );

		if ( empty( $venue ) || is_wp_error( $venue ) ) {
			return null;
		}

		$venue = array_pop( $venue );

		$location_transformer = new EventPrime_Location_Transformer( $venue );
		$location             = $location_transformer->to_object();

		return $location;
	}
}
