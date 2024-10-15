<?php
/**
 * Modern Events Calendar (Lite)
 *
 * Defines all the necessary meta information for the Modern Events Calendar (Lite).
 *
 * @link    https://webnus.net/modern-events-calendar/
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
final class Modern_Events_Calendar_Lite extends Event_plugin {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file(): string {
		return 'modern-events-calendar-lite/modern-events-calendar-lite.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		// See MEC_feature_events->get_main_post_type().
		return apply_filters( 'mec_post_type_name', 'mec-events' ); // phpcs:ignore
	}

	/**
	 * Returns the ID of the main settings page of the plugin.
	 *
	 * @return string The settings page url.
	 */
	public static function get_settings_page(): string {
		return 'mec-event';
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return 'mec_category';
	}
}
