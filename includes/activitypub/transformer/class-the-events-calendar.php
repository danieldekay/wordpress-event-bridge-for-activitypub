<?php
/**
 * ActivityPub Tribe Transformer
 *
 * @package activity-event-transformers
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
class The_Events_Calendar extends Event {

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
	 * @param string  $tribe_event_taxonomy The taxonomy of the events.
	 */
	public function __construct( $wp_object, $tribe_event_taxonomy ) {
		parent::__construct( $wp_object, $tribe_event_taxonomy );
		$this->tribe_event = \tribe_get_event( $wp_object );
	}

	/**
	 * Get tribe category of wp_post
	 *
	 * @return string|null tribe category if it exists
	 */
	public function get_tribe_category() {
		// TODO: make it possible that one event can have multiple categories?
		// Using cat_slugs isn't the best way to do this, don't know if it's a good idea.
		$categories = tribe_get_event_cat_slugs( $this->wp_object->ID );

		if ( count( $categories ) === 0 ) {
			return null;
		}

		return $categories[0];
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
		return 'CONFIRMED';
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
		// TODO: remove link at the end of the content.

		// TODO: add organizer
		// $this->tribe_event->organizers[0].

		// TODO: do add Cancelled reason in the content (maybe at the end).

		return $content;
	}

	/**
	 * Get the event location.
	 *
	 * @return Place|array The place/venue if one is set.
	 */
	public function get_location(): Place|null {
		if ( empty( $this->wp_object->venues ) || ! empty( $this->wp_object->venues[0] ) ) {
			return null;
		}
		// We currently only support a single venue.
		$event_venue = $this->wp_object->venues[0];

		$address = array(
			'addressCountry'  => $event_venue->country,
			'addressLocality' => $event_venue->city,
			'addressRegion'   => $event_venue->province,
			'postalCode'      => $event_venue->zip,
			'streetAddress'   => $event_venue->address,
			'type'            => 'PostalAddress',
		);

		$location = new Place();
		$location->set_address( $address );
		$location->set_id( $event_venue->permalink );
		$location->set_name( $event_venue->post_name );

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

		if ( ! empty( $this->tribe_event->tickets ) ) {
			$activitypub_object->set_external_participation_url( $this->tribe_event->tickets['link']->anchor );
			$activitypub_object->set_anonymous_participation_enabled( false );
			if ( function_exists( '\tribe_get_event_capacity' ) ) {
				$activitypub_object->set_maximum_attendee_capacity( call_user_func( '\tribe_get_event_capacity', $this->tribe_event ) );
			}
			if ( function_exists( '\tribe_get_event_capacity' ) ) {
				$activitypub_object->set_participant_count( count( call_user_func( '\tribe_tickets_get_attendees', $this->tribe_event->ID ) ) );
			}
			if ( function_exists( '\tribe_events_count_available_tickets' ) ) {
				$activitypub_object->set_remaining_attendee_capacity( call_user_func( '\tribe_events_count_available_tickets', $this->tribe_event ) );
			}
		}
		return $activitypub_object;
	}
}
