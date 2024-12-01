<?php
/**
 * Event Post.
 *
 * Defines all the necessary meta information for the integration of
 * the WordPress plugin "Event Post".
 *
 * @link    https://wordpress.org/plugins/event-post.
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
final class Event_Post extends Event_plugin {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file(): string {
		return 'event-post/eventpost.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return 'post';
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page urls.
	 */
	public static function get_settings_pages(): array {
		return array( 'event-post-settings' );
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return 'category';
	}
}
