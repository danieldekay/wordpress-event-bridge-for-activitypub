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

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\VS_Event_List as VS_Event_List_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\VS_Event_List as VS_Event_List_Transmogrifier;
use WP_Query;

/**
 * VS Events LIst.
 *
 * Defines all the necessary meta information for the integration of the WordPress event plugin
 * "Very Simple Events List".
 *
 * @since 1.0.0
 */
final class VS_Event_List extends Event_Plugin_Integration implements Feature_Event_Sources {
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

	/**
	 * Returns the Transmogrifier for The_Events_Calendar.
	 */
	public static function get_transmogrifier(): VS_Event_List_Transmogrifier {
		return new VS_Event_List_Transmogrifier();
	}

	/**
	 * Get a list of Post IDs of events that have ended.
	 *
	 * @param int $ends_before_time Filter to only get events that ended before that datetime as unix-time.
	 *
	 * @return array<int>
	 */
	public static function get_cached_remote_events( $ends_before_time ): array {
		$args = array(
			'post_type'      => 'event',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_event_bridge_for_activitypub_event_source',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'event-date',
					'value'   => $ends_before_time,
					'type'    => 'NUMERIC',
					'compare' => '<',
				),
			),
		);

		$query = new WP_Query( $args );

		$post_ids = $query->posts;

		return $post_ids;
	}
}
