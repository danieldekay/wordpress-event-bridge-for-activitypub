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
final class Events_Manager extends Base_Post_Place {
	/**
	 * The EM Location object.
	 *
	 * @var ?\EM_Location
	 */
	protected $em_location;

	/**
	 * Set the EM Location object on construction.
	 *
	 * @param \WP_Post $post The WordPress post object of the EM Location.
	 */
	public function __construct( $post ) {
		parent::__construct( $post );
		// We check for WP_Post to indicate that this might change in the future, to also e.g. allow for locations stored in terms.

		// @phpstan-ignore-next-line
		if ( $post instanceof \WP_Post && EM_POST_TYPE_LOCATION === $post->post_type ) {
			$this->em_location = em_get_location( $post );
		}
	}

	/**
	 * Get the name of the location.
	 *
	 * @return ?string
	 */
	public function get_name(): ?string {
		if ( isset( $this->em_location->location_name ) ) {
			return \wp_strip_all_tags(
				\html_entity_decode(
					$this->em_location->location_name
				)
			);
		}

		return null;
	}

	/**
	 * Get the event location.
	 *
	 * @return ?array The place/venue if one is set.
	 */
	public function get_address(): ?array {
		$postal_address = array();

		if ( isset( $this->em_location->location_country ) && $this->em_location->location_country ) {
			$postal_address['addressCountry'] = $this->em_location->location_country;
		}

		if ( isset( $this->em_location->location_town ) && $this->em_location->location_town ) {
			$postal_address['addressLocality'] = $this->em_location->location_town;
		}

		if ( isset( $this->em_location->location_address ) && $this->em_location->location_address ) {
			$postal_address['streetAddress'] = $this->em_location->location_address;
		}

		if ( isset( $this->em_location->location_state ) && $this->em_location->location_state ) {
			$postal_address['addressRegion'] = $this->em_location->location_state;
		}

		if ( isset( $this->em_location->location_postcode ) && $this->em_location->location_postcode ) {
			$postal_address['postalCode'] = $this->em_location->location_postcode;
		}

		if ( ! empty( $postal_address ) ) {
			return array_merge( array( 'type' => 'PostalAddress' ), $postal_address );
		}

		return null;
	}
}
