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

use Activitypub\Activity\Extended_Object\Place as Place_Object;

/**
 * Class for the ActivityPub transformer of the venues of The Events Calendar to `as:Place`.
 *
 * @since 1.0.0
 */
final class EventOn extends Base_Term_Place {
	/**
	 * The location meta for all locations.
	 *
	 * @var ?array
	 */
	protected $tax_meta = null;

	/**
	 * Extend the construction to get the taxonomy meta for this term from options.
	 *
	 * @param \WP_Term $item The WordPress post object (event).
	 */
	public function __construct( $item ) {
		parent::__construct( $item );

		$evo_tax_meta = \get_option( 'evo_tax_meta' );
		if ( isset( $evo_tax_meta['event_location'][ $item->term_id ] ) ) {
			$this->tax_meta = $evo_tax_meta['event_location'][ $item->term_id ];
		}
	}

	/**
	 * Generic function that converts an WordPress location object to an ActivityPub-Place object.
	 *
	 * @return Place_Object|\WP_Error
	 */
	public function to_object() {
		$object = parent::to_object();

		if ( \is_wp_error( $object ) ) {
			return $object;
		}

		$object->set_longitude( $this->get_longitude() );
		$object->set_latitude( $this->get_latitude() );

		return $object;
	}

	/**
	 * Get the type, either Place or VirtualLocation, both is stored in the same taxonomy.
	 *
	 * @return string
	 */
	public function get_type(): string {
		if ( $this->is_virtual_location() ) {
			return 'VirtualLocation';
		}
		return 'Place';
	}

	/**
	 * Get the longitute.
	 *
	 * @return float|null
	 */
	public function get_longitude(): ?float {
		$longitude = null;

		if ( isset( $this->tax_meta['location_lon'] ) ) {
			$longitude = $this->tax_meta['location_lon'];
		}

		return $longitude ? (float) $longitude : null;
	}

	/**
	 * Get the latitude.
	 *
	 * @return float|null
	 */
	public function get_latitude(): ?float {
		$latitude = null;

		if ( isset( $this->tax_meta['location_lat'] ) ) {
			$latitude = $this->tax_meta['location_lat'];
		}

		return $latitude ? (float) $latitude : null;
	}

	/**
	 * Get the events address.
	 *
	 * @return ?array The place/venue if one is set, or null if no valid address data exists.
	 */
	public function get_address(): ?array {

		if ( $this->is_virtual_location() ) {
			return null;
		}

		// Map the values to a schema.org PostalAddress.
		$postal_address = array(
			'streetAddress'  => isset( $this->tax_meta['location_address'] ) ? (string) $this->tax_meta['location_address'] : null,
			'addressRegion'  => isset( $this->tax_meta['location_state'] ) ? (string) $this->tax_meta['location_state'] : null,
			'addressCountry' => isset( $this->tax_meta['location_country'] ) ? (string) $this->tax_meta['location_country'] : null,
		);

		if ( isset( $this->tax_meta['location_city'] ) ) {
			$locality_and_postal_code = $this->parse_city_for_postal_code( $this->tax_meta['location_city'] );
			if ( isset( $locality_and_postal_code['addressLocality'] ) ) {
				$postal_address['addressLocality'] = (string) $locality_and_postal_code['addressLocality'];
			}
			if ( isset( $locality_and_postal_code['locality'] ) ) {
				$postal_address['postalCode'] = (string) $locality_and_postal_code['postalCode'];
			}
		}

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
		$postal_address = array_merge(
			array(
				'type' => 'PostalAddress',
			),
			$postal_address
		);

		return $postal_address;
	}

	/**
	 * Check if this term represents a virtual location.
	 *
	 * @return bool
	 */
	private function is_virtual_location(): bool {
		if ( isset( $this->tax_meta['location_type'] ) && 'virtual' === $this->tax_meta['location_type'] ) {
			return true;
		}
		return false;
	}

	/**
	 * Parse a string whether it contains a postal code and seperates both.
	 *
	 * @param string $input The input string of the locality which might contain the postal code too.
	 * @return array{city: string, zipcode: string}
	 */
	private function parse_city_for_postal_code( $input ): array {
		$input = trim( $input );

		if ( empty( $input ) ) {
			return array(
				'locality'   => '',
				'postalCode' => '',
			);
		}

		$parts       = explode( ' ', $input );
		$postal_code = '';
		$locality    = array();

		foreach ( $parts as $part ) {
			if ( preg_match( '/^\d{4,5}$/', $part ) ) {
				// Match postal codes (assuming 4-5 digit codes).
				$postal_code = $part;
			} else {
				// Assume everything else is part of the name of the city.
				$locality[] = $part;
			}
		}

		return array(
			'addressLocality' => implode( ' ', $locality ),
			'postalCode'      => $postal_code,
		);
	}
}
