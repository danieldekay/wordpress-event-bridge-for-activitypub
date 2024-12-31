<?php
/**
 * WP Event Manager.
 *
 * Defines all the necessary meta information for the WordPress event plugin
 * "WP Event Manager"
 *
 * @link    https://de.wordpress.org/plugins/wp-event-manager
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

use Event_Bridge_For_ActivityPub\Integrations\Event_Plugin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Interface for a supported event plugin.
 *
 * This interface defines which information is necessary for a supported event plugin.
 *
 * @since 1.0.0
 */
final class WP_Event_Manager extends Event_Plugin {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_relative_plugin_file(): string {
		return 'wp-event-manager/wp-event-manager.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return 'event_listing';
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page urls.
	 */
	public static function get_settings_pages(): array {
		return array( 'event-manager-settings' );
	}

	/**
	 * Returns the ActivityPub transformer class.
	 *
	 * @return string
	 */
	public static function get_activitypub_transformer_class_name(): string {
		return 'WP_Event_Manager';
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return 'event_listing_category';
	}
}
