<?php
/**
 * ActivityPub Transformer for VS Event.
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

require_once __DIR__ . '/../object/class-event.php';

use Activitypub\Activity\Base_Object;
use Place;
use function Activitypub\get_rest_url_by_path;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ActivityPub Transformer for VS Event
 *
 * @since 1.0.0
 */
class VS_Event extends \Activitypub\Transformer\Base {
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
	private function get_end_time() {
		$end_time = get_post_meta( $this->wp_post->ID, 'event-date', true );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $end_time );
	}

	/**
	 * Get the event link from the events metadata.
	 */
	private function get_event_link() {
		$event_link = get_post_meta( $this->wp_post->ID, 'event-link', true );
		if ( $event_link ) {
			return [
				'type' => 'Link',
				'name' => 'Website',
				'href' => \esc_url( get_post_meta( $post_id, 'event-location', true ) ),
				'mediaType' => 'text/html',
			];
		}
	}

	/**
	 * Overrides/extends the get_attachments function to also add the event Link.
	 */
	protected function get_attachments() {
		$attachments = parent::get_attachments();
		$event_link = $this->get_event_link();
		if ( $event_link ) {
			$attachments[] = $this->get_event_link();
		}
		return $attachments;
	}

	private function get_category() {
		return 'MEETING';
	}

	/**
	 * Transforms the VS Event WP_Post object to an ActivityPub Event Object.
	 *
	 * @see \Activitypub\Activity\Base_Object
	 *
	 * @return \Activitypub\Activity\Base_Object The ActivityPub Object.
	 */
	public function transform() {
		// todo make tranform nicer
		$context = Event::get_context();
		$object  = new Event();
		$object
			->set_id( $this->get_id() )
			->set_url( $this->get_url() )
			->set_type( $this->get_object_type() );

		$published = \strtotime( $this->wp_post->post_date_gmt );

		$object->set_published( \gmdate( 'Y-m-d\TH:i:s\Z', $published ) );

		$updated = \strtotime( $this->wp_post->post_modified_gmt );

		if ( $updated > $published ) {
			$object->set_updated( \gmdate( 'Y-m-d\TH:i:s\Z', $updated ) );
		}

		$object
			->set_attributed_to( $this->get_attributed_to() )
			->set_content( $this->get_content() )
			->set_content_map( $this->get_content_map );

		$summary = get_post_meta( $this->wp_post->ID, 'event-summary', true );
		if ( $summary ) {
			$object->set_summary( $summary );
		} else {
			$object->set_summary( $this->content );
		}

		$start_time = get_post_meta( $this->wp_post->ID, 'event-start-date', true );
		$object->set_start_time( \gmdate( 'Y-m-d\TH:i:s\Z', $start_time ) );

		$hide_end_time = get_post_meta( $this->wp_post->ID, 'event-hide-end-time', true);

		if ( $hide_end_time != 'yes' ) {
			$object->set_end_time( $this->get_end_time() );
		}

		$path = sprintf( 'users/%d/followers', intval( $this->wp_post->post_author ) );

		$object
			->set_location( $this->get_event_location( $this->wp_post->ID )->to_array() )
			->set_comments_enabled( comments_open( $this->wp_post->ID ) )
			->set_to(
				array(
					'https://www.w3.org/ns/activitystreams#Public',
					get_rest_url_by_path( $path ),
				)
			)
			->set_cc( $this->get_cc() )
			->set_attachment( $this->get_attachments() )
			->set_tag( $this->get_tags() )
			->set_replies_moderation_option( 'allow_all' )
			->set_join_mode( 'external' )
			->set_external_participation_url( $this->get_url() )
			->set_status( 'CONFIRMED' )
			->set_category( 'MEETING' );

		return $object;
	}
}
