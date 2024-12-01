<?php
/**
 * ActivityPub Transformer for the WordPress plugin Event Post.
 *
 * @link    https://wordpress.org/plugins/event-post
 * @package ActivityPub_Event_Bridge
 * @license AGPL-3.0-or-later
 */

namespace ActivityPub_Event_Bridge\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Activitypub\Transformer\Post;

use ActivityPub_Event_Bridge\Activitypub\Transformer\Event;

/**
 * ActivityPub Transformer for Event Organiser.
 *
 * @since 1.0.0
 */
final class Event_Post extends Post {
	/**
	 * Whether this is an event post.
	 *
	 * @var bool
	 */
	private $is_event_post = false;

	/**
	 * Constructor
	 *
	 * @param WP_Post $wp_object The WordPress object.
	 * @param string  $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object, $wp_taxonomy );
		$is_event_post = ! empty( get_post_meta( $wp_object->ID, 'event_begin', true ) );
	}

	/**
	 * Get the type.
	 */
	protected function get_type() {
		return $this->is_event_post ? 'Event' : Post::get_type();
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_end_time(): ?string {
		$end_time = get_post_meta( $this->wp_object->ID, 'event_end', true );
		return $end_time;
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_start_time(): string {
		$start_time = get_post_meta( $this->wp_object->ID, 'event_begin', true );
		return $start_time;
	}

	/**
	 * Get location from the event object.
	 */
	protected function get_location(): ?Place {
		return null;
	}
}
