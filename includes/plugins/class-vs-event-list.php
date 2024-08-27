<?php
/**
 * VS Events LIst.
 *
 * Defines all the necessary meta information for the WordPress event plugin
 * "Very Simple Events List".
 *
 * @link    https://de.wordpress.org/plugins/very-simple-event-list/
 * @package Activitypub_Event_Extensions
 * @since   1.0.0
 */

namespace Activitypub_Event_Extensions\Plugins;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

require_once __DIR__ . '/interface-event-plugin.php';

/**
 * Interface for a supported event plugin.
 *
 * This interface defines which information is necessary for a supported event plugin.
 *
 * @since 1.0.0
 */
class VS_Event_List implements Event_Plugin {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file(): string {
		return 'very-simple-event-list/vsel.php';
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
	 * Returns the ID of the main settings page of the plugin.
	 *
	 * @return string The settings page url.
	 */
	public static function get_settings_page(): string {
		return 'settings_page_vsel';
	}

	/**
	 * Returns the ActivityPub transformer class.
	 *
	 * @return string
	 */
	public static function get_activitypub_transformer_class_name(): string {
		return 'VS_Event';
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_taxonomy(): string {
		return 'event_cat';
	}
}
