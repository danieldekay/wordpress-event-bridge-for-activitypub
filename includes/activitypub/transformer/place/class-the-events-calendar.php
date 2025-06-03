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

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place\Base_Post_Place;

/**
 * Class for the ActivityPub transformer of the venues of The Events Calendar to `as:Place`.
 *
 * @since 1.0.0
 */
final class The_Events_Calendar extends Base_Post_Place {
	/**
	 * Get the event location.
	 *
	 * @return ?array The place/venue if one is set.
	 */
	public function get_address(): ?array {
		$postal_address = array();

		$country = \tribe_get_country( $this->item->ID );
		if ( $country ) {
			$postal_address['addressCountry'] = $country;
		}

		$city = \tribe_get_city( $this->item->ID );
		if ( $city ) {
			$postal_address['addressLocality'] = $city;
		}

		$province = \tribe_get_province( $this->item->ID );
		if ( $province ) {
			$postal_address['addressRegion'] = $province;
		}

		$zip = \tribe_get_zip( $this->item->ID );
		if ( $zip ) {
			$postal_address['postalCode'] = $zip;
		}

		$address = \tribe_get_address( $this->item->ID );
		if ( $city ) {
			$postal_address['streetAddress'] = $address;
		}

		if ( empty( $postal_address ) ) {
			return null;
		}

		$postal_address = array_merge( array( 'type' => 'PostalAddress' ), $postal_address );

		return $postal_address;
	}

	/**
	 * Get the latitude of the place.
	 *
	 * @return ?float The latitude if it is known.
	 */
	public function get_latitude() {
		return tribe_get_coordinates( $this->item->ID )['lat'] ?? null;
	}

	/**
	 * Get the longitude of the place.
	 *
	 * @return ?float The longitude if it is known.
	 */
	public function get_longitude() {
		return tribe_get_coordinates( $this->item->ID )['lng'] ?? null;
	}
}
