<?php
/**
 * Event Organiser.
 *
 * Defines all the necessary meta information for the Event Organiser plugin.
 *
 * @link    https://wordpress.org/plugins/event-organiser/
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event_Organiser as Event_Organiser_Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Interface for a supported event plugin.
 *
 * This interface defines which information is necessary for a supported event plugin.
 *
 * @since 1.0.0
 */
final class Event_Organiser extends Event_Plugin_Integration {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_relative_plugin_file(): string {
		return 'event-organiser/event-organiser.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return 'event';
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page urls.
	 */
	public static function get_settings_pages(): array {
		return array( 'event-organiser' );
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return 'event-category';
	}

	/**
	 * Returns the ActivityPub transformer for a Event_Organiser event post.
	 *
	 * @param WP_Post $post The WordPress post object of the Event.
	 * @return Event_Organiser_Transformer
	 */
	public static function get_activitypub_event_transformer( $post ): Event_Organiser_Transformer {
		return new Event_Organiser_Transformer( $post, self::get_event_category_taxonomy() );
	}
}
