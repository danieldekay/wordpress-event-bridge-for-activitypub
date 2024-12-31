<?php
/**
 * ActivityPub Tribe Transformer
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Event_Bridge_For_ActivityPub\Activitypub\Transformer\Event;
use WP_Post;

use function Activitypub\esc_hashtag;

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
	 * Get the tags, including also the set categories from The Events Calendar.
	 *
	 * @return ?array The array if tags,
	 */
	public function get_tag(): ?array {
		$tags         = array();
		$category_ids = tribe_get_event_cat_ids();
		if ( $category_ids ) {
			foreach ( $category_ids as $category_id ) {
				$term   = \get_term( $category_id );
				$tag    = array(
					'type' => 'Hashtag',
					'href' => \esc_url( \get_term_link( $term ) ),
					'name' => esc_hashtag( $term->name ),
				);
				$tags[] = $tag;
			}
		}
		$tags = array_merge( $tags, parent::get_tag() );

		return $tags;
	}

	/**
	 * Get the end time from the event object.
	 */
	public function get_end_time(): ?string {
		if ( empty( $this->tribe_event->end_date ) ) {
			return null;
		}
		$date = date_create( $this->tribe_event->end_date, wp_timezone() );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $date->getTimestamp() );
	}

	/**
	 * Get the end time from the event object.
	 */
	public function get_start_time(): string {
		$date = date_create( $this->tribe_event->start_date, wp_timezone() );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $date->getTimestamp() );
	}

	/**
	 * Get status of the tribe event
	 *
	 * @return string status of the event
	 */
	public function get_status(): ?string {
		if ( 'canceled' === $this->tribe_event->event_status ) {
			return 'CANCELLED';
		}
		if ( 'postponed' === $this->tribe_event->event_status ) {
			return 'CANCELLED'; // This will be reflected in the cancelled reason.
		}
		return 'CONFIRMED';
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
	 * Get the event location.
	 *
	 * @return ?Place The place/venue if one is set.
	 */
	public function get_location(): ?Place {
		// Get short handle for the venues.
		$venues = $this->tribe_event->venues;

		// Get first venue. We currently only support a single venue.
		if ( $venues instanceof \Tribe\Events\Collections\Lazy_Post_Collection ) {
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
		if ( ! empty( $venue->post_title ) ) {
			$address['name'] = $venue->post_title;
		}
		$address['type'] = 'PostalAddress';

		$location = new Place();
		if ( count( $address ) > 1 ) {
			$location->set_address( $address );
		} else {
			$location->set_address( $venue->post_title );
		}
		$location->set_id( $venue->ID );
		$location->set_name( $venue->post_title );

		return $location;
	}

	/**
	 * Get the timezone of the event.
	 *
	 * @return string  The timezone string of the site.
	 */
	public function get_timezone(): string {
		return $this->tribe_event->timezone;
	}
}
