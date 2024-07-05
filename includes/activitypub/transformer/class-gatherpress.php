<?php
/**
 * ActivityPub Transformer for the plugin Very Simple Event List.
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

use Activitypub\Transformer\Post;
use Activitypub\Model\Blog_user;
use Activitypub\Activity\Extended_Object\Event as Event_Object;
use Activitypub\Activity\Extended_Object\Place;
use GatherPress\Core\Event as GatherPress_Event;

use function Activitypub\get_rest_url_by_path;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ActivityPub Transformer for VS Event
 *
 * @since 1.0.0
 */
class GatherPress extends Post {

	/**
	 * The target ActivityPub Event object of the transformer.
	 *
	 * @var Event
	 */
	protected $ap_object;

	/**
	 * The current GatherPress Event object.
	 *
	 * @var Event
	 */
	protected $gp_event;

	/**
	 * The current GatherPress Venue object.
	 *
	 * @var Event
	 */
	protected $gp_venue;

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

		return 'gatherpress/gp-event';
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

		return 'GatherPress Event';
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

		return array( GatherPress_Event::POST_TYPE );
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

		$address = $this->gp_venue['full_address'];
		$place   = new Place();
		$place->set_type( 'Place' );
		$place->set_name( $address );
		$place->set_address( $address );
		return $place;
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_end_time() {

		return $this->gp_event->get_datetime_end( 'Y-m-d\TH:i:s\Z' );
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_start_time() {

		return $this->gp_event->get_datetime_start( 'Y-m-d\TH:i:s\Z' );
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
			$attachments[] = $this->get_event_link();
		}
		return $attachments;
	}

	/**
	 * TODO:
	 *
	 * @return string $category
	 */
	protected function get_category() {

		return 'MEETING';
	}

	/**
	 * Returns the User-URL of the Author of the Post.
	 *
	 * If `single_user` mode is enabled, the URL of the Blog-User is returned.
	 *
	 * @return string The User-URL.
	 */
	protected function get_attributed_to() {

		$user = new Blog_User();
		return $user->get_url();
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
	 * Transform the WordPress Object into an ActivityPub Object.
	 *
	 * @return Activitypub\Activity\Event
	 */
	public function to_object() {

		$this->ap_object = new Event();
		$this->gp_event  = new GatherPress_Event( $this->wp_object->ID );
		$this->gp_venue  = $this->gp_event->get_venue_information();

		$this->ap_object = parent::to_object();

		$this->ap_object->set_comments_enabled( 'open' === $this->wp_object->comment_status ? true : false );

		$this->ap_object->set_external_participation_url( $this->get_url() );

		$online_event_link = $this->gp_event->maybe_get_online_event_link();

		if ( $online_event_link ) {
			$this->ap_object->set_is_online( true );
		} else {
			$this->ap_object->set_is_online( false );
		}

		$this->ap_object->set_status( 'CONFIRMED' );

		$this->ap_object->set_name( get_the_title( $this->wp_object->ID ) );

		$this->ap_object->set_actor( get_rest_url_by_path( 'application' ) );
		$this->ap_object->set_to( array( 'https://www.w3.org/ns/activitystreams#Public' ) );

		$this->ap_object->set_location();
		return $this->ap_object;
	}
}
