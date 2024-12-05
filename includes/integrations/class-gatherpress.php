<?php
/**
 * GatherPress.
 *
 * Defines all the necessary meta information for the GatherPress plugin.
 *
 * @link    https://wordpress.org/plugins/gatherpress/
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
final class GatherPress extends Event_Plugin {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file(): string {
		return 'gatherpress/gatherpress.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return class_exists( '\GatherPress\Core\Event' ) ? \GatherPress\Core\Event::POST_TYPE : 'gatherpress_event';
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page urls.
	 */
	public static function get_settings_pages(): array {
		return array( class_exists( '\GatherPress\Core\Utility' ) ? \GatherPress\Core\Utility::prefix_key( 'general' ) : 'gatherpress_general' );
	}

	/**
	 * Returns the ActivityPub transformer class.
	 *
	 * @return string
	 */
	public static function get_activitypub_transformer_class_name(): string {
		return 'GatherPress';
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return class_exists( '\GatherPress\Core\Topic' ) ? \GatherPress\Core\Topic::TAXONOMY : 'gatherpress_topic';
	}
}
