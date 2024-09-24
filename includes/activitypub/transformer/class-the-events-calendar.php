<?php
/**
 * ActivityPub Tribe Transformer
 *
 * @package Activitypub_Event_Extensions
 * @license AGPL-3.0-or-later
 */

namespace Activitypub_Event_Extensions\Activitypub\Transformer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Activitypub_Event_Extensions\Activitypub\Transformer\Event;
use Activitypub\Activity\Extended_Object\Place;
use WP_Error;
use WP_Post;

/**
 * ActivityPub Tribe Transformer
 *
 * @since 1.0.0
 */
final class The_Events_Calendar extends Event {

	/**
	 * The Tribe Event object.
	 *
	 * @var array|WP_Post|null
	 */
	protected $tribe_event;

	/**
	 * Extend the constructor, to also set the tribe object.
	 *
	 * This is a special class object form The Events Calendar which
	 * has a lot of useful functions, we make use of our getter functions.
	 *
	 * @param WP_Post $wp_object The WordPress object.
	 * @param string  $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object, $wp_taxonomy );
		$this->tribe_event = \tribe_get_event( $wp_object );
	}

	/**
	 * Get tribe category of wp_post
	 *
	 * @return string|null tribe category if it exists
	 */
	public function get_tribe_category() {
		$categories = tribe_get_event_cat_slugs( $this->wp_object->ID );

		if ( count( $categories ) === 0 ) {
			return null;
		}

		return $categories[0];
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_end_time() {
		$date = date_create( $this->tribe_event->end_date, wp_timezone() );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $date->getTimestamp() );
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_start_time() {
		$date = date_create( $this->tribe_event->start_date, wp_timezone() );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $date->getTimestamp() );
	}

	/**
	 * Get status of the tribe event
	 *
	 * @return string status of the event
	 */
	public function get_tribe_status() {

		if ( 'canceled' === $this->tribe_event->event_status ) {
			return 'CANCELLED';
		}
		if ( 'postponed' === $this->tribe_event->event_status ) {
			return 'CANCELLED'; // This will be reflected in the cancelled reason.
		}
		if ( '' === $this->tribe_event->event_status ) {
			return 'CONFIRMED';
		}

		return new WP_Error( 'invalid event_status value', __( 'invalid event_status', 'activitypub' ), array( 'status' => 404 ) );
	}

	/**
	 * Extract the join mode.
	 *
	 * If the ticket sale is active set it to restricted.
	 *
	 * @return string
	 */
	public function get_join_mode() {
		return empty( $this->tribe_event->tickets ) ? 'free' : 'restricted';
	}

	/**
	 * Check if the comments are enabled for the current event.
	 */
	public function get_comments_enabled(): bool {
		return ( 'open' === $this->tribe_event->comment_status ) ? true : false;
	}

	/**
	 * Check if the event is an online event.
	 */
	public function get_is_online(): bool {
		return false;
	}

	/**
	 * Returns the content for the ActivityPub Item with
	 *
	 * The content will be generated based on the user settings.
	 *
	 * @return string The content.
	 */
	protected function get_content() {

		$content = parent::get_content();
		// /BeforeFirstRelease:
		// * remove link at the end of the content.
		// * add organizer.
		// * do add Cancelled reason in the content.s

		return $content;
	}

	/**
	 * Get the event location.
	 *
	 * @return Place|array The place/venue if one is set.
	 */
	public function get_location(): Place|null {
		// Get short handle for the venues.
		$venues = $this->wp_object->venues;

		// Get first venue. We currently only support a single venue.
		if ( $venues instanceof \Tribe\Events\Collections\Lazy_Post_Collection ){
			$venue = $venues->first();
		} elseif ( empty( $this->wp_object->venues ) || ! empty( $this->wp_object->venues[0] ) ) {
			return null;
		} else {
			$venue = $venues[0];
		}

		if ( ! $venue ) {
			return null;
		}

		// Set the address.
		$address = array();

		if ( ! empty( $venue->country ) ) {
			$address['addressCountry'] = $venue->country;
		}

		if ( ! empty( $venue->city ) ) {
			$address['addressLocality'] = $venue->city;
		}

		if ( ! empty( $venue->province ) ) {
			$address['addressRegion'] = $venue->province;
		}

		if ( ! empty( $venue->zip ) ) {
			$address['postalCode'] = $venue->zip;
		}

		if ( ! empty( $venue->address ) ) {
			$address['streetAddress'] = $venue->address;
		}
		$address['type'] = 'PostalAddress';

		$location = new Place();
		if ( count( $address ) > 1 ) {
			$location->set_address( $address );
		}
		$location->set_id( $venue->permalink );
		$location->set_name( $venue->post_title );

		return $location;
	}

	/**
	 * Extend the default event transformers to_object function.
	 *
	 * This is the heart of the ActivityPub transformer.
	 *
	 * @return Event_Object
	 */
	public function to_object() {
		$activitypub_object = parent::to_object();

		return $activitypub_object;
	}
}
