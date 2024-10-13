<?php
/**
 * The Events Calendar.
 *
 * Defines all the necessary meta information for the events calendar.
 *
 * @link    https://wordpress.org/plugins/the-events-calendar/
 * @package ActivityPub_Event_Bridge
 * @since   1.0.0
 */

namespace ActivityPub_Event_Bridge\Plugins;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Interface for a supported event plugin.
 *
 * This interface defines which information is necessary for a supported event plugin.
 *
 * @since 1.0.0
 */
final class Eventin extends Event_plugin {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file(): string {
		return 'wp-event-solution/eventin.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return 'etn';
	}

	/**
	 * Returns the ID of the main settings page of the plugin.
	 *
	 * @return string The settings page url.
	 */
	public static function get_settings_page(): string {
		return 'eventin'; // Base always is wp-admin/admin.php?page=eventin.
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return 'etn_category';
	}
}
