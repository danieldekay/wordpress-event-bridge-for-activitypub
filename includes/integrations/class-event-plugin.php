<?php
/**
 * Interface for defining supported Event Plugins.
 *
 * Basic information that each supported event needs for this plugin to work.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

use Event_Bridge_For_ActivityPub\Activitypub\Transformer\Event as Event_Transformer;

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
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The IDs of one or several admin/settings pages.
	 */
	public static function get_settings_pages(): array {
		return array();
	}

	/**
	 * By default event sources are not supported by an event plugin integration.
	 *
	 * @return bool True if event sources are supported.
	 */
	public static function supports_event_sources(): bool {
		return false;
	}

	/**
	 * Get the plugins name from the main plugin-file's top-level-file-comment.
	 */
	final public static function get_plugin_name(): string {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . static::get_plugin_file() );
		if ( isset( $plugin_data['Name'] ) ) {
			return $plugin_data['Name'];
		} else {
			return '';
		}
	}

	/**
	 * Detects whether the current screen is a admin page of the event plugin.
	 */
	public static function is_plugin_page(): bool {
		// Get the current page.
		$screen = get_current_screen();

		// Check if we are on a edit page for the event, or on the settings page of the event plugin.
		$is_event_plugins_edit_page     = 'edit' === $screen->base && static::get_post_type() === $screen->post_type;
		$is_event_plugins_settings_page = in_array( $screen->id, static::get_settings_pages(), true );

		return $is_event_plugins_edit_page || $is_event_plugins_settings_page;
	}

	/**
	 * Returns the Activitypub transformer for the event plugins event post type.
	 */
	public static function get_activitypub_event_transformer_class(): string {
		return str_replace( 'Integrations', 'Activitypub\Transformer', static::class );
	}

	/**
	 * Returns the class used for transmogrifying an Event (ActivityStreams to Event plugin transformation).
	 */
	public static function get_transmogrifier_class(): ?string {
		if ( ! self::supports_event_sources() ) {
			return null;
		}
		return str_replace( 'Integrations', 'Activitypub\Transmogrifier', static::class );
	}
}
