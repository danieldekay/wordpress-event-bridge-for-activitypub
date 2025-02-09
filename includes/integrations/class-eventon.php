<?php
/**
 * EventON – Events Calendar
 *
 * Defines all the necessary meta information for the integration of the WordPress event plugin
 * "EventON – Events Calendar".
 *
 * @link    https://wordpress.org/plugins/eventon-lite
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\EventOn as EventOn_Event_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place\EventOn as EventOn_Location_Transformer;

/**
 * EventON – Events Calendar
 *
 * Defines all the necessary meta information for the integration of the WordPress event plugin
 * "EventON – Events Calendar".
 *
 * @since 1.0.0
 */
final class EventOn extends Event_Plugin_Integration {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_relative_plugin_file(): string {
		return 'eventon-lite/eventon.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return 'ajde_events';
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page urls.
	 */
	public static function get_settings_pages(): array {
		return array( 'admin.php?page=eventon' );
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return '';
	}

	/**
	 * Returns the ActivityPub transformer for a VS_Event_List event post.
	 *
	 * @param \WP_Post $post The WordPress post object of the Event.
	 * @return EventOn_Event_Transformer
	 */
	public static function get_activitypub_event_transformer( $post ): EventOn_Event_Transformer {
		return new EventOn_Event_Transformer( $post, self::get_event_category_taxonomy() );
	}

	/**
	 * In case an event plugin uses a custom taxonomy for storing locations/venues return it here.
	 *
	 * @return string
	 */
	public static function get_place_taxonomy() {
		return 'event_location';
	}

	/**
	 * Returns the ActivityPub transformer for a Event_Organiser event venue which is stored in a taxonomy.
	 *
	 * @param \WP_Term $term The WordPress Term/Taxonomy of the venue.
	 * @return EventOn_Location_Transformer
	 */
	public static function get_activitypub_place_transformer( $term ): EventOn_Location_Transformer {
		return new EventOn_Location_Transformer( $term );
	}
}
