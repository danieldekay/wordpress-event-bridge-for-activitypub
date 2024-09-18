<?php
/**
 * The Events Calendar.
 *
 * Defines all the necessary meta information for the events calendar.
 *
 * @link    https://wordpress.org/plugins/the-events-calendar/
 * @package Activitypub_Event_Extensions
 * @since   1.0.0
 */

namespace Activitypub_Event_Extensions\Plugins;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Interface for a supported event plugin.
 *
 * This interface defines which information is necessary for a supported event plugin.
 *
 * @since 1.0.0
 */
final class The_Events_Calendar extends Event_plugin {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file(): string {
		return 'the-events-calendar/the-events-calendar.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return class_exists( '\Tribe__Events__Main' ) ? \Tribe__Events__Main::POSTTYPE : 'tribe_event';
	}

	/**
	 * Returns the ID of the main settings page of the plugin.
	 *
	 * @return string The settings page url.
	 */
	public static function get_settings_page(): string {
		// TODO: Tribe\Events\Admin\Settings::settings_page_id.
		return 'edit.php?post_type=tribe_events&page=tec-events-settings';
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return class_exists( '\Tribe__Events__Main' ) ? \Tribe__Events__Main::TAXONOMY : 'tribe_events_cat';
	}
}
