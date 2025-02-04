<?php
/**
 * Class file for the ActivityPub transformer of the venues of The Events Calendar to `as:Place`.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Class for the ActivityPub transformer of the venues of The Events Calendar to `as:Place`.
 *
 * @since 1.0.0
 */
final class Event_Organiser extends Base_Term_Place {
	/**
	 * Get the longitute.
	 *
	 * @return float|null
	 */
	public function get_longitude() {
		$longitude = \eo_get_venue_lng( $this->item->ID );
		return 0.0 !== $longitude ? $longitude : null;
	}

	/**
	 * Get the latitude.
	 *
	 * @return float|null
	 */
	public function get_latitude() {
		$latitude = \eo_get_venue_lat( $this->item->ID );
		return 0.0 !== $latitude ? $latitude : null;
	}

	/**
	 * Get the description of the venue as the ActivityPub content.
	 *
	 * @return string|null
	 */
	public function get_content() {
		$description = \eo_get_venue_description( $this->item->term_id );

		if ( empty( $description ) ) {
			return null;
		}

		return $description;
	}

	/**
	 * Get the events address.
	 *
	 * @return ?array The place/venue if one is set, or null if no valid address data exists.
	 */
	public function get_address(): ?array {
		$address = \eo_get_venue_address( $this->item->term_id );

		// Map the values to a schema.org PostalAddress.
		$postal_address = array(
			'streetAddress'   => isset( $address['address'] ) ? $address['address'] : null,
			'postalCode'      => isset( $address['address'] ) ? $address['postcode'] : null,
			'addressRegion'   => isset( $address['address'] ) ? $address['state'] : null,
			'addressLocality' => isset( $address['address'] ) ? $address['city'] : null,
			'addressCountry'  => isset( $address['address'] ) ? $address['country'] : null,
		);

		// Filter out empty values.
		foreach ( $postal_address as $key => $value ) {
			if ( empty( $value ) ) {
				unset( $postal_address[ $key ] );
			}
		}

		// If no valid address data remains, return null.
		if ( empty( $postal_address ) ) {
			return null;
		}

		// Add the type.
		$postal_address['type'] = 'PostalAddress';

		return $postal_address;
	}
}
