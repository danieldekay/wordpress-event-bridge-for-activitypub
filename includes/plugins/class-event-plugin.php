<?php
/**
 * Interface for defining supported Event Plugins.
 *
 * Basic information that each supported event needs for this plugin to work.
 *
 * @package Activitypub_Event_Extensions
 * @since 1.0.0
 */

namespace Activitypub_Event_Extensions\Plugins;

use Activitypub_Event_Extensions\Activitypub\Transformer\Event as Event_Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Interface for a supported event plugin.
 *
 * This interface defines which information is necessary for a supported event plugin.
 *
 * @since 1.0.0
 */
abstract class Event_Plugin {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	abstract public static function get_plugin_file(): string;

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	abstract public static function get_post_type(): string;

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	abstract public static function get_event_category_taxonomy(): string;

	/**
	 * Returns the ID of the main settings page of the plugin.
	 *
	 * @return string The settings page url.
	 */
	public static function get_settings_page(): string {
		return '';
	}

	/**
	 * Detects whether the current screen is a admin page of the event plugin.
	 */
	public static function is_plugin_page(): bool {
		// Get the current page.
		$screen = get_current_screen();

		// Check if we are on a edit page for the event, or on the settings page of the event plugin.
		$is_event_plugins_edit_page     = 'edit' === $screen->base && static::get_post_type() === $screen->post_type;
		$is_event_plugins_settings_page = static::get_settings_page() === $screen->id;

		return $is_event_plugins_edit_page || $is_event_plugins_settings_page;
	}

	/**
	 * Returns the Activitypub transformer for the event plugins event post type.
	 */
	public static function get_activitypub_event_transformer_class(): string {
		return str_replace( 'Plugins', 'Activitypub\Transformer', static::class );
	}
}
