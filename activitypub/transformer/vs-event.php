<?php
use Activitypub\Activity\Base_Object;
use function Activitypub\get_rest_url_by_path;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ActivityPub Tribe Transformer
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
	public function get_supported_post_types() {
		return [ 'event' ];
	}

	/**
	 * Get the event location
	 */
	public function get_event_location( $post_id ) {
		$object = new Base_Object();
		$object->set_type( 'Place' );
		$object->set_name( get_post_meta( $post_id, 'event-location', true ) );
		$array = $object->to_array();
		return $array;
	}

	/**
	 * Transforms the VS Event WP_Post object to an ActivityPub Event Object
	 *
	 * @see \Activitypub\Activity\Base_Object
	 *
	 * @return \Activitypub\Activity\Base_Object The ActivityPub Object
	 */
	public function to_object() {
		$wp_post = $this->wp_post;
		$object  = new Base_Object();

		$object->set_id( $this->get_id() );
		$object->set_url( $this->get_url() );
		$object->set_type( $this->get_object_type() );

		$published = \strtotime( $wp_post->post_date_gmt );

		$object->set_published( \gmdate( 'Y-m-d\TH:i:s\Z', $published ) );

		$updated = \strtotime( $wp_post->post_modified_gmt );

		if ( $updated > $published ) {
			$object->set_updated( \gmdate( 'Y-m-d\TH:i:s\Z', $updated ) );
		}

		$object->set_attributed_to( $this->get_attributed_to() );
		$object->set_content( $this->get_content() );
		$object->set_content_map( $this->get_content_map );

		$summary = get_post_meta( $wp_post->ID, 'event-summary', true );
		if ( $summary ) {
			$object->set_summary( $summary );
		} else {
			$object->set_summary( $this->content );
		}

		$start_time = get_post_meta( $wp_post->ID, 'event-start-date', true );
		$object->set_start_time( \gmdate( 'Y-m-d\TH:i:s\Z', $start_time ) );

		$end_time = get_post_meta( $wp_post->ID, 'event-date', true );
		$object->set_end_time( \gmdate( 'Y-m-d\TH:i:s\Z', $end_time ) );

		$path = sprintf( 'users/%d/followers', intval( $wp_post->post_author ) );

		$location = get_post_meta( $wp_post->ID, 'event-link', true );
		$object->set_location( $this->get_event_location( $wp_post->ID ) );

		$object->set_to(
			array(
				'https://www.w3.org/ns/activitystreams#Public',
				get_rest_url_by_path( $path ),
			)
		);
		$object->set_cc( $this->get_cc() );

		$attachments = $this->get_attachments();

		$object->set_attachment( $this->get_attachments() );
		$object->set_tag( $this->get_tags() );

		return $object;
	}

}
