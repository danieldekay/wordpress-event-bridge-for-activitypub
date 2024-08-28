<?php
/**
 * ActivityPub Transformer for the plugin Very Simple Event List.
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

namespace Activitypub_Event_Extensions\Activitypub\Transformer;

use Activitypub_Event_Extensions\Activitypub\Transformer\Event as Event_Transformer;
use Activitypub\Activity\Extended_Object\Event;
use Activitypub\Activity\Extended_Object\Place;

use WP_Error;
use function Activitypub\get_rest_url_by_path;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ActivityPub Transformer for VS Event.
 *
 * This transformer tries a different principle: The setters are chainable.
 *
 * @since 1.0.0
 */
class VS_Event_List extends Event_Transformer {

	/**
	 * The target transformer ActivityPub Event object.
	 *
	 * @var Event
	 */
	protected $ap_object;

	/**
	 * Get transformer name.
	 *
	 * Retrieve the transformers name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget name.
	 */
	public function get_transformer_name() {
		return 'activitypub-event-transformers/vs-event';
	}

	/**
	 * Get transformer title.
	 *
	 * Retrieve the transformers label.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget title.
	 */
	public function get_transformer_label() {
		return 'VS Event';
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
		return array( 'event' );
	}

	/**
	 * Returns the ActivityStreams 2.0 Object-Type for an Event.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-event
	 * @since 1.0.0
	 * @return string The Event Object-Type.
	 */
	protected function get_type() {
		return 'Event';
	}

	/**
	 * Get the event location.
	 *
	 * @return array The Place.
	 */
	public function get_location() {
		$address = get_post_meta( $this->wp_object->ID, 'event-location', true );
		$place   = new Place();
		$place->set_type( 'Place' );
		$place->set_name( $address );
		$place->set_address( $address );
		return $place;
	}

	/**
	 * Get the end time from the events metadata.
	 */
	protected function get_end_time() {
		$end_time = get_post_meta( $this->wp_object->ID, 'event-date', true );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $end_time );
	}

	/**
	 * Get the end time from the events metadata.
	 */
	protected function get_start_time() {
		$start_time = get_post_meta( $this->wp_object->ID, 'event-start-date', true );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $start_time );
	}

	/**
	 * Get the event link from the events metadata.
	 */
	private function get_event_link() {
		$event_link = get_post_meta( $this->wp_object->ID, 'event-link', true );
		if ( $event_link ) {
			return array(
				'type'      => 'Link',
				'name'      => 'Website',
				'href'      => \esc_url( $event_link ),
				'mediaType' => 'text/html',
			);
		}
	}

	/**
	 * Overrides/extends the get_attachments function to also add the event Link.
	 */
	protected function get_attachment() {
		$attachments = parent::get_attachment();
		if ( count( $attachments ) ) {
			$attachments[0]['type'] = 'Document';
			$attachments[0]['name'] = 'Banner';
		}
		$event_link = $this->get_event_link();
		if ( $event_link ) {
			$attachments[] = $event_link;
		}
		return $attachments;
	}

	/**
	 * Create a custom summary.
	 *
	 * It contains also the most important meta-information. The summary is often used when the
	 * ActivityPub object type 'Event' is not supported, e.g. in Mastodon.
	 *
	 * @return string $summary The custom event summary.
	 */
	public function get_summary() {
		if ( $this->wp_object->excerpt ) {
			$excerpt = $this->wp_object->post_excerpt;
		} elseif ( get_post_meta( $this->wp_object->ID, 'event-summary', true ) ) {
			$excerpt = get_post_meta( $this->wp_object->ID, 'event-summary', true );
		} else {
			$excerpt = $this->get_content();
		}

		$address           = get_post_meta( $this->wp_object->ID, 'event-location', true );
		$start_time        = get_post_meta( $this->wp_object->ID, 'event-start-date', true );
		$datetime_format   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$start_time_string = wp_date( $datetime_format, $start_time );
		$summary           = "📍 {$address}\n📅 {$start_time_string}\n\n{$excerpt}";
		return $summary;
	}

	/**
	 * Generic setter.
	 *
	 * @param string $key   The key to set.
	 * @param string $value The value to set.
	 *
	 * @return mixed The value.
	 */
	public function set( $key, $value ) {

		if ( ! $this->ap_object->has( $key ) ) {
			return new WP_Error( 'invalid_key', __( 'Invalid key', 'activitypub' ), array( 'status' => 404 ) );
		}

		$setter_function = 'set_' . $key;
		$getter_function = 'get_' . $key;

		if ( in_array( $getter_function, get_class_methods( $this ), true ) ) {
			$this->ap_object->$setter_function( $this->$getter_function() );
		} else {
			$this->ap_object->$setter_function( $value );
		}

		return $this;
	}

	/**
	 * Magic function to implement setter
	 *
	 * @param string $method The method name.
	 * @param string $params The method params.
	 *
	 * @return void|this
	 */
	public function __call( $method, $params ) {

		$var = \strtolower( \substr( $method, 4 ) );

		if ( \strncasecmp( $method, 'set', 3 ) === 0 ) {
			return $this->set( $var, $params[0] );
		}

		// TODO: When do we need: call_user_func( array( $activitypub_object, $setter ), $value ).

		return $this;
	}

	/**
	 * Transform the WordPress Object into an ActivityPub Object.
	 *
	 * @return Activitypub\Activity\Event
	 */
	public function to_object() {

		$this->ap_object = new Event();

		$this
			->set_content()
			->set_content_map()
			->set_attributed_to()
			->set_published()
			->set_start_time()
			->set_end_time()
			->set_type()
			->set_category()
			->set_attachment()
			->set_comments_enabled( true )
			->set_external_participation_url( $this->get_url() )
			->set_status( 'CONFIRMED' )
			->set_name( get_the_title( $this->wp_object->ID ) )
			->set_is_online( false )
			->set_in_language( $this->get_locale() )
			->set_actor( get_rest_url_by_path( 'application' ) )
			->set_to( array( 'https://www.w3.org/ns/activitystreams#Public' ) )
			->set_location()
			->set_id();
		return $this->ap_object;
	}
}
