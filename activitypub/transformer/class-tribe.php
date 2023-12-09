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

/**
 * ActivityPub Tribe Transformer
 *
 * @since 1.0.0
 */
class Tribe extends \Activitypub\Transformer\Base {

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
	 * Transforms the VS Event WP_Post object to an ActivityPub Event Object.
	 *
	 * @see \Activitypub\Activity\Base_Object
	 *
	 * @return \Activitypub\Activity\Base_Object The ActivityPub Object.
	 */
	public function transform() {
		$object = new Event();

		$this->set_timeformat( 'Y-m-d\TH:i:s\Z' );


		$object
			->set_id( $this->get_id() )
			->set_url( $this->get_url() )
			->set_external_participation_url( $this->get_url() )

			->set_published( $this->get_published() )
			->if( $this->get_updated() > $this->get_published() )
			->set_updated( $this->get_updated() )

			->set_attributed_to( $this->get_attributed_to() )
			->set_content( $this->get_content() )
			->set_content_map( $this->get_basic_content_map() ) // todo rename to basic

			->set_summary( $this->get_post_meta( 'post_excerpt', true, $this->get_content() ) ) // todo second argument is fallback / default
			->set_start_time( $this->get_post_meta_time( '_EventStartDateUTC' ) )
			->set_end_time( $this->get_post_meta_time( '_EventEndDateUTC' ) )
			->set_to( $this->get_followers_stream() )
			// ->set_location( $this->get_event_location()->to_array())
			->set_cc( $this->get_cc() )
			->set_attachment( $this->get_attachments() )
			->set_tag( $this->get_tags() )

			->set_comments_enabled( $this->get_comments_open() )
			->set_replies_moderation_option( 'allow_all' )
			->set_join_mode( 'external' )
			->set_status( 'CONFIRMED' )
			->set_category( 'MEETING' );

		assert( $object->get_type() === $this->get_object_type() );
		return $object;
	}
}
