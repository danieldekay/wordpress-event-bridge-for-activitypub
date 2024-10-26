<?php
/**
 * VS Events LIst.
 *
 * Defines all the necessary meta information for the WordPress event plugin
 * "Very Simple Events List".
 *
 * @link    https://de.wordpress.org/plugins/very-simple-event-list/
 * @package ActivityPub_Event_Bridge
 * @since   1.0.0
 */

namespace ActivityPub_Event_Bridge\Plugins;

use ActivityPub_Event_Bridge\Event_Plugins;

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
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page urls.
	 */
	public static function get_settings_pages(): array {
		return array( 'settings_page_vsel' );
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
