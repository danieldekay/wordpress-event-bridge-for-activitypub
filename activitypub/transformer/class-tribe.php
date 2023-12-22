<?php
/**
 * ActivityPub Tribe Transformer
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Activitypub\Transformer\Post;
use Activitypub\Activity\Event;
use Activitypub\Activity\Place;

/**
 * ActivityPub Tribe Transformer
 *
 * @since 1.0.0
 */
class Tribe extends Post {
	/**
	 * The Tribe Event object.
	 *
	 * @var WP_Post
	 */
	protected $tribe_event;

	// /**
	//  * resolve the tribe metadata in the setter of wp_post.
	//  *
	//  * @param WP_Post $wp_post The WP_Post object.
	//  * @return void
	//  */
	// public function set_wp_post( WP_Post $wp_post ) {
	// 	parent::set_wp_post( $wp_post );
	// 	$this->tribe_event = tribe_get_event( $wp_post->ID );
	// }

	/**
	 * Get widget name.
	 *
	 * Retrieve oEmbed widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'activitypub-event-transformers/tribe';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve Transformer title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget title.
	 */
	public function get_label() {
		return 'The Events Calendar';
	}

	/**
	 * Returns the ActivityStreams 2.0 Object-Type for an Event.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-event
	 *
	 * @return string The Event Object-Type.
	 */
	protected function get_object_type() {
		return 'Event';
	}

	/**
	 * Get supported post types.
	 *
	 * Retrieve the list of supported WordPress post types this transformer widget can handle.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Widget categories.
	 */
	public static function get_supported_post_types() {
		return array( 'tribe_events' );
	}

	/**
	 * Get tribe category of wp_post
	 *
	 * @return string|null tribe category if it exists
	 */
	public function get_tribe_category() {
		// todo make it possible that one event can have multiple categories?

		// using cat_slugs isn't 100% nice way to do this, don't know if it's a good idea
		$categories = tribe_get_event_cat_slugs( $this->object->ID );

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
			return 'CANCELLED'; // this will be reflected in the cancelled reason
		}
		if ( '' === $this->tribe_event->event_status ) {
			return 'CONFIRMED';
		}

		return new WP_Error( 'invalid event_status value', __( 'invalid event_status', 'activitypub' ), array( 'status' => 404 ) );
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
		// todo remove link at the end of the content

		// todo add organizer
		// $this->tribe_event->organizers[0]

		// todo add Canclled reason in the content (maybe at the end)

		return $content;
	}

	/**
	 * Get the event location.
	 *
	 * @param int $post_id The WordPress post ID.
	 * @returns array The Place.
	 */
	public function get_event_location() {
		/*
			   'post_title' => 'testvenue',
			   'post_name' => 'testvenue',
			   'guid' => 'http://localhost/venue/testvenue/',
			   'post_type' => 'tribe_venue',
			   'address' => 'testaddr',
			   'country' => 'Austria',
			   'city' => 'testcity',
			   'state_province' => 'testprovince',
			   'state' => '',
			   'province' => 'testprovince',
			   'zip' => '8000',
			   'phone' => '+4312343',
			   'permalink' => 'http://localhost/venue/testvenue/',
			   'directions_link' => 'https://maps.google.com/maps?f=q&#038;source=s_q&#038;hl=en&#038;geocode=&#038;q=testaddr+testcity+testprovince+8000+Austria',
			   'website' => 'https://test.at',
		 */
		$venue = $this->tribe_event->venues[0];
		return ( new Place() )
			->set_type( 'Place' )
			->set_name( $venue->post_name )
			->set_address(
				$venue->address . "\n" .
						   $venue->zip . ', ' . $venue->city . "\n" .
						   $venue->country
			); // todo add checks that everything exists here (lol)
	}
}
