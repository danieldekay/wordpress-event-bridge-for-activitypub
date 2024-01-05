<?php
/**
 * ActivityPub Transformer for the plugin Very Simple Event List.
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

use EM_Event;

use Activitypub\Activity\Event;
use Activitypub\Activity\Place;
use Activitypub\Transformer\Post;
use Activitypub\Model\Blog_user;
use function Activitypub\get_rest_url_by_path;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ActivityPub Transformer for events from the WordPress plugin 'Events Manager'
 * 
 * @see https://wordpress.org/plugins/events-manager/
 *
 * @since 1.0.0
 */
class Events_Manager extends Post {
	/**
	 * Holds the EM_Event object.
	 * 
	 * @var EM_Event
	 */
	protected $em_event;

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
		return 'activitypub-event-transformers/events-manager';
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
		return 'Events Manager';
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
		return array();
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
	 * @param int $post_id The WordPress post ID.
	 * @return array The Place.
	 */
	public function get_location() {
		return null;
	}

	/**
	 * Get the end time from the events metadata.
	 */
	protected function get_end_time() {
		return null;
	}

	/**
	 * Get the end time from the events metadata.
	 */
	protected function get_start_time() {
		return null;
	}

	/**
	 * Overrides/extends the get_attachments function to also add the event Link.
	 */
	protected function get_attachment() {
		return null;
	}

	/**
	 * This function tries to map VS-Event categories to Mobilizon event categories.
	 *
	 * @return string $category
	 */
	protected function get_category() {
		return null;
	}

	/**
	 * Transform the WordPress Object into an ActivityPub Object.
	 *
	 * @return Activitypub\Activity\Event
	 */
	public function to_object() {
		$this->em_event = new EM_Event( $this->wp_object->ID, 'post_id');
		$activtiypub_object = new Event();

		return $activtiypub_object;
	}
}
