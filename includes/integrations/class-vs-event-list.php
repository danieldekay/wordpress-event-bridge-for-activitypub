<?php
/**
 * VS Events LIst.
 *
 * Defines all the necessary meta information for the integration of the WordPress event plugin
 * "Very Simple Events List".
 *
 * @link    https://de.wordpress.org/plugins/very-simple-event-list/
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\VS_Event_List as VS_Event_List_Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * VS Events LIst.
 *
 * Defines all the necessary meta information for the integration of the WordPress event plugin
 * "Very Simple Events List".
 *
 * @since 1.0.0
 */
final class VS_Event_List extends Event_Plugin_Integration {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_relative_plugin_file(): string {
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
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return 'event_cat';
	}

	/**
	 * Returns the ActivityPub transformer for a VS_Event_List event post.
	 *
	 * @param WP_Post $post The WordPress post object of the Event.
	 * @return VS_Event_List_Transformer
	 */
	public static function get_activitypub_event_transformer( $post ): VS_Event_List_Transformer {
		return new VS_Event_List_Transformer( $post, self::get_event_category_taxonomy() );
	}
}
