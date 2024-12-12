<?php
/**
 * Modern Events Calendar (Lite)
 *
 * Defines all the necessary meta information for the Modern Events Calendar (Lite).
 *
 * @link    https://webnus.net/modern-events-calendar/
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

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
	public static function get_relative_plugin_file(): string {
		return 'modern-events-calendar-lite/modern-events-calendar-lite.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		// See MEC_feature_events->get_main_post_type().
		return 'mec-events';
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page urls.
	 */
	public static function get_settings_pages(): array {
		return array( 'MEC-settings', 'MEC-support', 'MEC-ix', 'MEC-wizard', 'MEC-addons', 'mec-intro' );
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
