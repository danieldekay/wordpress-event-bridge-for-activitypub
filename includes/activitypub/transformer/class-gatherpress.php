<?php
/**
 * ActivityPub Transformer for the plugin Very Simple Event List.
 *
 * @package Activitypub_Event_Extensions
 * @license AGPL-3.0-or-later
 */

namespace Activitypub_Event_Extensions\Activitypub\Transformer;

use Activitypub_Event_Extensions\Activitypub\Transformer\Event;
use Activitypub\Model\Blog;
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
final class GatherPress extends Event {

	/**
	 * The target ActivityPub Event object of the transformer.
	 *
	 * @var Event
	 */
	protected $ap_object;

	/**
	 * The current GatherPress Event object.
	 *
	 * @var GatherPress_Event
	 */
	protected $gp_event;

	/**
	 * The current GatherPress Venue object.
	 *
	 * @var Event
	 */
	protected $gp_venue;

	/**
	 * Extend the constructor, to also set the GatherPress objects.
	 *
	 * This is a special class object form The Events Calendar which
	 * has a lot of useful functions, we make use of our getter functions.
	 *
	 * @param WP_Post $wp_object The WordPress object.
	 * @param string  $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object, $wp_taxonomy );
		$this->gp_event = new GatherPress_Event( $this->wp_object->ID );
		$this->gp_venue = $this->gp_event->get_venue_information();
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
	 * Get the event location.
	 *
	 * @return Place|null The place objector null if not place set.
	 */
	public function get_location(): ?Place {
		$address = $this->gp_venue['full_address'];
		if ( $address ) {
			$place = new Place();
			$place->set_type( 'Place' );
			$place->set_name( $address );
			$place->set_address( $address );
			return $place;
		} else {
			return null;
		}
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_end_time(): ?string {
		return $this->gp_event->get_datetime_end( 'Y-m-d\TH:i:s\Z' );
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_start_time(): string {
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
	protected function get_attachment(): array {
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
	 * Returns the User-URL of the Author of the Post.
	 *
	 * If `single_user` mode is enabled, the URL of the Blog-User is returned.
	 *
	 * @return string The User-URL.
	 */
	protected function get_attributed_to(): string {
		$user = new Blog();
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
	public function get_summary(): string {
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
	 * Get the content.
	 */
	public function get_content(): string {
		return $this->wp_object->post_content;
	}

	/**
	 * Determine whether the event is online.
	 *
	 * @return bool
	 */
	public function get_is_online(): bool {
		return $this->gp_event->maybe_get_online_event_link() ? true : false;
	}


	/**
	 * Transform the WordPress Object into an ActivityPub Object.
	 *
	 * @return Activitypub\Activity\Event
	 */
	public function to_object(): Event_Object {
		$activitypub_object = parent::to_object();

		return $activitypub_object;
	}
}
