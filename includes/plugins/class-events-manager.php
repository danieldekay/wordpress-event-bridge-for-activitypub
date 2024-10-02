<?php
/**
 * Events Manager.
 *
 * Defines all the necessary meta information for the Events Manager WordPress Plugin.
 *
 * @link    https://wordpress.org/plugins/events-manager/
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
final class Events_Manager extends Event_Plugin {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file(): string {
		return 'events-manager/events-manager.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return defined( 'EM_POST_TYPE_EVENT' ) ? constant( 'EM_POST_TYPE_EVENT' ) : 'event';
	}

	/**
	 * Returns the ID of the main settings page of the plugin.
	 *
	 * @return string The settings page url.
	 */
	public static function get_settings_page(): string {
		return 'wp-admin/edit.php?post_type=event&page=events-manager-options#general';
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return defined( 'EM_TAXONOMY_CATEGORY' ) ? constant( 'EM_TAXONOMY_CATEGORY' ) : 'event-categories';
	}
}
