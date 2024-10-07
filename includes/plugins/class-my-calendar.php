<?php
/**
 * My Calendar.
 *
 * Defines all the necessary meta information for the WordPress event plugin
 * "My Calendar".
 *
 * @link    https://wordpress.org/plugins/my-calendar/
 * @package Activitypub_Event_Extensions
 * @since   1.0.0
 */

namespace Activitypub_Event_Extensions\Plugins;

use Activitypub_Event_Extensions\Event_Plugins;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Interface for a supported event plugin.
 *
 * This interface defines which information is necessary for a supported event plugin.
 *
 * @since 1.0.0
 */
final class My_Calendar extends Event_Plugin {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file(): string {
		return 'my-calendar/my-calendar.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return 'mc-events';
	}

	/**
	 * Returns the ID of the main settings page of the plugin.
	 *
	 * @return string The settings page url.
	 */
	public static function get_settings_page(): string {
		return 'my-calendar-config';
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return 'mc-event-category';
	}
}
