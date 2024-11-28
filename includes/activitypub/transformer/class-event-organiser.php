<?php
/**
 * ActivityPub Transformer for the plugin Event Organiser.
 *
 * @package ActivityPub_Event_Bridge
 * @license AGPL-3.0-or-later
 */

namespace ActivityPub_Event_Bridge\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use ActivityPub_Event_Bridge\Activitypub\Transformer\Event;

/**
 * ActivityPub Transformer for Event Organiser.
 *
 * @since 1.0.0
 */
final class Event_Organiser extends Event {
	/**
	 * Get the end time from the event object.
	 */
	protected function get_end_time(): ?string {
		return eo_get_the_end( 'Y-m-d\TH:i:s\Z', $this->wp_object->ID );
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_start_time(): string {
		return eo_get_the_start( 'Y-m-d\TH:i:s\Z', $this->wp_object->ID );
	}

	/**
	 * Get location from the event object.
	 */
	protected function get_location(): ?Place {
		$venue_id = eo_get_venue( $this->wp_object->ID );

		if ( ! $venue_id ) {
			return null;
		}

		$address = eo_get_venue_address( $venue_id );

		$address['name'] = eo_get_venue_name( $this->wp_object->ID );

		$address['streetAddress'] = $address['address'];
		unset( $address['address'] );

		$address['postalCode'] = $address['postcode'];
		unset( $address['postcode'] );

		$address['addressRegion'] = $address['state'];
		unset( $address['state'] );

		$address['addressLocality'] = $address['city'];
		unset( $address['city'] );

		$address['addressCountry'] = $address['country'];
		unset( $address['country'] );

		$address['type'] = 'PostalAddress';

		$location = new Place();
		$location->set_name( eo_get_venue_name( $this->wp_object->ID ) );
		$location->set_latitude( eo_get_venue_lat( $this->wp_object->ID ) );
		$location->set_longitude( eo_get_venue_lng( $this->wp_object->ID ) );
		$location->set_address( $address );

		return $address;
	}
}
