<?php
/**
 * EventPrime – Events Calendar, Bookings and Tickets
 *
 * @link    https://wordpress.org/plugins/eventprime-event-calendar-management/
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\EventPrime as EventPrime_Event_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place\EventPrime as EventPrime_Place_Transformer;
use Eventprime_Basic_Functions;

use function Activitypub\is_activitypub_request;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * This class defines which information is necessary for the EventPrime event plugin.
 *
 * @since 1.0.0
 */
final class EventPrime extends Event_Plugin_Integration {
	/**
	 * Add filter for the template inclusion.
	 */
	public static function init() {
		// Forcefully enable 'activitypub' post type support for EventPrime, because it is not public and cannot be done in the admin UI.
		\add_post_type_support( self::get_post_type(), 'activitypub' );
		\add_filter( 'activitypub_transformer', array( self::class, 'register_activitypub_transformer' ), 10, 3 );
	}

	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_relative_plugin_file(): string {
		return 'eventprime-event-calendar-management/event-prime.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return 'em_event';
	}

	/**
	 * Returns the taxonomy used for storing venues.
	 *
	 * @return string
	 */
	public static function get_place_taxonomy(): string {
		return 'em_venue';
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page urls.
	 */
	public static function get_settings_pages(): array {
		return array( 'ep-settings' );
	}

	/**
	 * Returns the ActivityPub transformer.
	 *
	 * @param \WP_Post $post The WordPress post object of the Event.
	 * @return EventPrime_Event_Transformer
	 */
	public static function get_activitypub_event_transformer( $post ): EventPrime_Event_Transformer {
		return new EventPrime_Event_Transformer( $post, self::get_event_category_taxonomy() );
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return 'em_event_type';
	}

	/**
	 * Maybe use the custom transformer for the EventPrime.
	 *
	 * @param mixed  $transformer  The transformer to use.
	 * @param mixed  $data         The data to transform.
	 * @param string $object_class The class of the object to transform.
	 *
	 * @return mixed
	 */
	public static function register_activitypub_transformer( $transformer, $data, $object_class ) {
		if ( 'WP_Post' !== $object_class ) {
			return $transformer;
		}

		$object_type = self::post_contains_eventprime_object( $data );

		if ( 'event' === $object_type ) {
			$post = get_post( self::get_object_id( $object_type ) );
			if ( $post && self::get_post_type() === $post->post_type ) {
				return new EventPrime_Event_Transformer( $post );
			}
		}

		if ( 'venue' === $object_type ) {
			$term = get_term( self::get_object_id( $object_type ) );
			if ( $term && self::get_place_taxonomy() === $term->taxonomy ) {
				return new EventPrime_Place_Transformer( $term );
			}
		}

		return $transformer;
	}

	/**
	 * Determine if the current post is actually just a shortcode Wrapper linking to an EventPrime event.
	 *
	 * @param \WP_Post $post The WordPress post object.
	 * @return string|bool
	 */
	private static function post_contains_eventprime_object( $post ) {
		if ( 'page' !== $post->post_type ) {
			return false;
		}

		if ( '[em_event]' === $post->post_content || '[em_events]' === $post->post_content ) {
			return 'event';
		}

		if ( '[em_sites]' === $post->post_content ) {
			return 'venue';
		}

		return false;
	}

	/**
	 * Extract the post id for events and term id for venues for an EventPrime event query.
	 *
	 * @param string $type 'event' or 'venue'.
	 * @return bool|int The post ID, or term ID if found, false otherwise.
	 */
	private static function get_object_id( $type = 'event' ) {
		if ( ! in_array( $type, array( 'venue', 'event' ), true ) ) {
			return false;
		}

		$event = get_query_var( $type );
		if ( ! $event ) {
			if ( ! empty( filter_input( INPUT_GET, $type, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) ) {
				$event = rtrim( filter_input( INPUT_GET, $type, FILTER_SANITIZE_FULL_SPECIAL_CHARS ), '/\\' );
			}
		}

		if ( $event ) {
			$ep_basic_functions = new Eventprime_Basic_Functions();
			return $ep_basic_functions->ep_get_id_by_slug( $event, "em_{$type}" );
		}

		return false;
	}
}
