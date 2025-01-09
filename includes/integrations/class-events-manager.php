<?php
/**
 * Events Manager.
 *
 * Defines all the necessary meta information and methods for the integration of the
 * WordPress plugin "Events Manager".
 *
 * @link    https://wordpress.org/plugins/events-manager/
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Events_Manager as Events_Manager_Transformer;

/**
 * Events Manager.
 *
 * Defines all the necessary meta information and methods for the integration of the
 * WordPress plugin "Events Manager".
 *
 * @since 1.0.0
 */
final class Events_Manager extends Event_Plugin_Integration {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_relative_plugin_file(): string {
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
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page urls.
	 */
	public static function get_settings_page(): array {
		return array();
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return defined( 'EM_TAXONOMY_CATEGORY' ) ? constant( 'EM_TAXONOMY_CATEGORY' ) : 'event-categories';
	}

	/**
	 * Returns the ActivityPub transformer for a Events_Manager event post.
	 *
	 * @param WP_Post $post The WordPress post object of the Event.
	 * @return Events_Manager_Transformer
	 */
	public static function get_activitypub_event_transformer( $post ): Events_Manager_Transformer {
		return new Events_Manager_Transformer( $post, self::get_event_category_taxonomy() );
	}
}
