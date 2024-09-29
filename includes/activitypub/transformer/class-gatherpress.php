<?php
/**
 * ActivityPub Transformer for the plugin Very Simple Event List.
 *
 * @package Activitypub_Event_Extensions
 * @license AGPL-3.0-or-later
 */

namespace Activitypub_Event_Extensions\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Event as Event_Object;
use Activitypub\Activity\Extended_Object\Place;
use Activitypub\Model\Blog;
use Activitypub_Event_Extensions\Activitypub\Transformer\Event;
use GatherPress\Core\Event as GatherPress_Event;

/**
 * ActivityPub Transformer for VS Event
 *
 * @since 1.0.0
 */
final class GatherPress extends Event {

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
		$user = new Blog(); // todo is this correct? feels not right.
		return $user->get_url();
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
}
