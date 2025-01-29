<?php
/**
 * ActivityPub Transformer for the plugin Event Organiser.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event;
use WP_Post;

/**
 * ActivityPub Transformer for Event Organiser.
 *
 * @since 1.0.0
 */
final class Event_Organiser extends Event {
	/**
	 * Extended constructor.
	 *
	 * The wp_object is overridden with a the wp_object with filters. This object
	 * also contains attributes specific to the Event organiser plugin like the
	 * occurrence id.
	 *
	 * @param WP_Post $wp_object The WordPress object.
	 * @param string  $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object, $wp_taxonomy );
		$this->wp_object = get_posts(
			array(
				'ID'               => $wp_object->ID,
				'post_type'        => 'event',
				'suppress_filters' => false,
			)
		)[0];
	}

	/**
	 * Get the end time from the event object.
	 */
	public function get_end_time(): string {
		return eo_get_the_end( 'Y-m-d\TH:i:s\Z', $this->wp_object->ID, $this->wp_object->occurrence_id );
	}

	/**
	 * Get the end time from the event object.
	 */
	public function get_start_time(): string {
		return eo_get_the_start( 'Y-m-d\TH:i:s\Z', $this->wp_object->ID, $this->wp_object->occurrence_id );
	}

	/**
	 * Get location from the event object.
	 */
	public function get_location(): ?Place {
		$venue_id = eo_get_venue( $this->wp_object->ID );

		if ( ! $venue_id ) {
			return null;
		}

		$address = eo_get_venue_address( $venue_id );

		$venue_name = eo_get_venue_name( $venue_id );

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

		$longitude = eo_get_venue_lng( $this->wp_object->ID );
		$latitude  = eo_get_venue_lat( $this->wp_object->ID );

		$location = new Place();
		$location->set_name( eo_get_venue_name( $this->wp_object->ID ) );
		if ( 0 !== $latitude ) {
			$location->set_latitude( $latitude );
		}
		if ( 0 !== $longitude ) {
			$location->set_longitude( $longitude );
		}
		$location->set_address( $address );
		$location->set_name( $venue_name );
		$location->set_content( eo_get_venue_description( $venue_id ) );

		return $location;
	}
}
