<?php
/**
 * ActivityPub Transformer for VS Event.
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

use Activitypub\Activity\Event;
use Activitypub\Activity\Place;
use Activitypub\Transformer\Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ActivityPub Transformer for VS Event
 *
 * @since 1.0.0
 */
class VS_Event extends Post {
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
		return 'activitypub-event-transformers/vs-event';
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
		return 'VS Event';
	}

	/**
	 * Returns the ActivityStreams 2.0 Object-Type for an Event.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-event
	 * @since 1.0.0
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
		return array( 'event' );
	}

	/**
	 * Get the event location.
	 *
	 * @param int $post_id The WordPress post ID.
	 * @returns array The Place.
	 */
	public function get_event_location( $post_id ) {
		$address = get_post_meta( $post_id, 'event-location', true );
		return ( new Place() )
			->set_type( 'Place' )
			->set_name( $address )
			->set_address( $address );
	}

	/**
	 * Get the end time from the events metadata.
	 */
	protected function get_end_time() {
		$end_time = get_post_meta( $this->object->ID, 'event-date', true );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $end_time );
	}

	/**
	 * Get the end time from the events metadata.
	 */
	protected function get_start_time() {
		$end_time = get_post_meta( $this->object->ID, 'event-start-date', true );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $end_time );
	}

	/**
	 * Get the event link from the events metadata.
	 */
	private function get_event_link() {
		$event_link = get_post_meta( $this->object->ID, 'event-link', true );
		if ( $event_link ) {
			return array(
				'type' => 'Link',
				'name' => 'Website',
				'href' => \esc_url( $event_link ),
				'mediaType' => 'text/html',
			);
		}
	}

	/**
	 * Overrides/extends the get_attachments function to also add the event Link.
	 */
	protected function get_attachment() {
		$attachments = parent::get_attachment();
		$attachments[0]['type'] = 'Document';
		$attachments[0]['name'] = 'Banner';
		$event_link = $this->get_event_link();
		if ( $event_link ) {
			$attachments[] = $this->get_event_link();
		}
		return $attachments;
	}

	protected function get_replies_moderation_option() {
		return 'allow_all';
	}

	protected function get_status() {
		return 'CONFIRMED';
	}

	/**
	 * Transform the WordPress Object into an ActivityPub Object.
	 *
	 * @return Activitypub\Activity\Event
	 */
	public function to_object() {
		$object = new Event();

		$vars = $object->get_object_var_keys();

		foreach ( $vars as $var ) {
			$getter = 'get_' . $var;

			if ( method_exists( $this, $getter ) ) {
				$value = call_user_func( array( $this, $getter ) );

				if ( isset( $value ) ) {
					$setter = 'set_' . $var;

					call_user_func( array( $object, $setter ), $value );
				}
			}
		}

		return $object
			->set_replies_moderation_option( 'allow_all' )
			->set_join_mode( 'external' )
			->set_external_participation_url( $this->get_url() )
			->set_status( 'CONFIRMED' )
			->set_category( 'MEETING' )
			->set_name( get_the_title( $this->object->ID ) )
			->set_timezone( 'Europe/Vienna' )
			->set_is_online( false )
			->set_in_language( 'de ' )
			->set_actor ('http://wp.lan/@blog')
			->set_to ( [
				"https://www.w3.org/ns/activitystreams#Public"
			]);
	}
}
