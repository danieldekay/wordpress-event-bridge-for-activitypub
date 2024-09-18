<?php
/**
 * Replace the default ActivityPub Transformer
 *
 * @package Activitypub_Event_Extensions
 * @license AGPL-3.0-or-later
 */

namespace Activitypub_Event_Extensions\Activitypub\Transformer;

use Activitypub\Activity\Extended_Object\Event as Event_Object;
use Activitypub\Transformer\Post;

use function Activitypub\get_rest_url_by_path;

/**
 * Base transformer for WordPress event post types to ActivityPub events.
 *
 * Everything that transforming several WordPress post types that represent events
 * have in common, as well as sane defaults for events should be defined here.
 */
class Event extends Post {

	/**
	 * The WordPress event taxonomy.
	 *
	 * @var string
	 */
	protected $wp_taxonomy;

	/**
	 * Returns the User-URL of the Author of the Post.
	 *
	 * If `single_user` mode is enabled, the URL of the Blog-User is returned.
	 *
	 * @return string The User-URL.
	 */
	protected function get_actor() {
		return $this->get_attributed_to();
	}

	/**
	 * Returns the ActivityStreams 2.0 Object-Type for an Event.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-event
	 *
	 * @return string The Event Object-Type.
	 */
	protected function get_type() {
		return 'Event';
	}

	/**
	 * Returns the title of the event.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-name
	 *
	 * @return string The name.
	 */
	protected function get_name() {
		return $this->wp_object->post_title;
	}

	/**
	 * Extend the construction of the Post Transformer to also set the according taxonomy of the event post type.
	 *
	 * @param WP_Post $wp_object The WordPress post object (event).
	 * @param string  $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object );
		$this->wp_taxonomy = $wp_taxonomy;
	}

	/**
	 * Set the event category, via the mapping setting.
	 */
	public function get_category() {
		$current_category_mapping = \get_option( 'activitypub_event_extensions_event_category_mappings', array() );
		$terms                    = \get_the_terms( $this->wp_object, $this->wp_taxonomy );

		// Check if the event has a category set and if that category has a specific mapping return that one.
		if ( ! is_wp_error( $terms ) && $terms && array_key_exists( $terms[0]->slug, $current_category_mapping ) ) {
			return sanitize_text_field( $current_category_mapping[ $terms[0]->slug ] );
		} else {
			// Return the default event category.
			return sanitize_text_field( \get_option( 'activitypub_event_extensions_default_event_category', 'MEETING' ) );
		}
	}

	/**
	 * Generic function that converts an WP-Event object to an ActivityPub-Event object.
	 *
	 * @return Event_Object
	 */
	public function to_object() {
		$activitypub_object = new Event_Object();
		$activitypub_object = $this->transform_object_properties( $activitypub_object );

		$published = \strtotime( $this->wp_object->post_date_gmt );

		$activitypub_object->set_published( \gmdate( 'Y-m-d\TH:i:s\Z', $published ) );

		$updated = \strtotime( $this->wp_object->post_modified_gmt );

		if ( $updated > $published ) {
			$activitypub_object->set_updated( \gmdate( 'Y-m-d\TH:i:s\Z', $updated ) );
		}

		$activitypub_object->set_content_map(
			array(
				$this->get_locale() => $this->get_content(),
			)
		);

		$activitypub_object->set_to(
			array(
				'https://www.w3.org/ns/activitystreams#Public',
				$this->get_actor_object()->get_followers(),
			)
		);

		return $activitypub_object;
	}
}
