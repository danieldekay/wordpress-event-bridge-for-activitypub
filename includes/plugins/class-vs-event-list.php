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
final class VS_Event_List extends Event_Plugin {
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
	public static function get_event_category_taxonomy(): string {
		return 'event_cat';
	}
}
