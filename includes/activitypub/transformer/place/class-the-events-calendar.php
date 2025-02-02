<?php
/**
 * Class file for the ActivityPub transformer of the venues of The Events Calendar to `as:Place`.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Activitypub\Transformer\Place;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Activitypub\Transformer\Post;

/**
 * Class for the ActivityPub transformer of the venues of The Events Calendar to `as:Place`.
 *
 * @since 1.0.0
 */
final class The_Events_Calendar extends Post {
	/**
	 * Set the type of the object.
	 */
	public function get_type(): string {
		return 'Place';
	}

	/**
	 * Set the type of the object.
	 */
	public function get_replies() {
		return null;
	}

	/**
	 * Set the type of the object.
	 */
	public function get_sensitive() {
		return null;
	}

	/**
	 * Get the event location.
	 *
	 * @return array|string|null The place/venue if one is set.
	 */
	public function get_address() {
		$address = array();

		if ( ! empty( $this->wp_object->country ) ) {
			$address['addressCountry'] = $this->wp_object->country;
		}

		if ( ! empty( $this->wp_object->city ) ) {
			$address['addressLocality'] = $this->wp_object->city;
		}

		if ( ! empty( $this->wp_object->province ) ) {
			$address['addressRegion'] = $this->wp_object->province;
		}

		if ( ! empty( $this->wp_object->zip ) ) {
			$address['postalCode'] = $this->wp_object->zip;
		}

		if ( ! empty( $this->wp_object->address ) ) {
			$address['streetAddress'] = $this->wp_object->address;
		}
		if ( ! empty( $this->wp_object->post_title ) ) {
			$address['name'] = $this->wp_object->post_title;
		}
		$address['type'] = 'PostalAddress';

		if ( count( $address ) > 1 ) {
			return $address;
		} else {
			return $this->get_name();
		}
	}

	/**
	 * Generic function that converts an WP-Event object to an ActivityPub-Event object.
	 *
	 * @param bool $full_object bool Return an object with all properties set, or a minimal one as used within an `as:Event`s location.
	 * @return Place
	 */
	public function to_object( $full_object = true ): Place {
		$activitypub_object = new Place();
		$activitypub_object = $this->transform_object_properties( $activitypub_object );

		if ( ! empty( $activitypub_object->get_content() ) ) {
			$activitypub_object->set_content_map(
				array(
					$this->get_locale() => $this->get_content(),
				)
			);
		}

		if ( $full_object ) {
			$published = \strtotime( $this->wp_object->post_date_gmt );

			$activitypub_object->set_published( \gmdate( 'Y-m-d\TH:i:s\Z', $published ) );

			$updated = \strtotime( $this->wp_object->post_modified_gmt );

			if ( $updated > $published ) {
				$activitypub_object->set_updated( \gmdate( 'Y-m-d\TH:i:s\Z', $updated ) );
			}

			$activitypub_object->set_to(
				array(
					'https://www.w3.org/ns/activitystreams#Public',
					$this->get_actor_object()->get_followers(),
				)
			);
		}

		$activitypub_object->set_address( $this->get_address() );

		return $activitypub_object;
	}
}
