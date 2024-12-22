<?php
/**
 * GatherPress.
 *
 * Defines all the necessary meta information and methods for the integration
 * of the WordPress event plugin "GatherPress".
 *
 * @link    https://wordpress.org/plugins/gatherpress/
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\GatherPress as GatherPress_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\GatherPress as GatherPress_Transmogrifier;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * GatherPress.
 *
 * Defines all the necessary meta information and methods for the integration
 * of the WordPress event plugin "GatherPress".
 *
 * @since 1.0.0
 */
final class GatherPress extends Event_Plugin_Integration implements Feature_Event_Sources {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_relative_plugin_file(): string {
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
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return class_exists( '\GatherPress\Core\Topic' ) ? \GatherPress\Core\Topic::TAXONOMY : 'gatherpress_topic';
	}

	/**
	 * Returns the ActivityPub transformer for a GatherPress event post.
	 *
	 * @param WP_Post $post The WordPress post object of the Event.
	 * @return GatherPress_Transformer
	 */
	public static function get_activitypub_event_transformer( $post ): GatherPress_Transformer {
		return new GatherPress_Transformer( $post, self::get_event_category_taxonomy() );
	}

	/**
	 * Returns the Transmogrifier for GatherPress.
	 */
	public static function get_transmogrifier(): GatherPress_Transmogrifier {
		return new GatherPress_Transmogrifier();
	}

	/**
	 * Get a list of Post IDs of events that have ended.
	 *
	 * @param int $ends_before_time Filter: only get events that ended before that datetime as unix-time.
	 *
	 * @return array
	 */
	public static function get_cached_remote_events( $ends_before_time ): array {
		global $wpdb;

		$ends_before_time_string = gmdate( 'Y-m-d H:i:s', $ends_before_time );

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->prefix}gatherpress_events WHERE datetime_end < %s",
				$ends_before_time_string
			)
		);

		return $results;
	}

	/**
	 * Init function.
	 */
	public static function init() {
		\add_filter(
			'gatherpress_force_online_event_link',
			function ( $force_online_event_link ) {
				// Get the current post object.
				$post = get_post();

				// Check if we are in a valid context and the post type is 'gatherpress'.
				if ( $post && 'gatherpress_event' === $post->post_type ) {
					// Add your custom logic here to decide whether to force the link.
					// For example, force it only if a specific meta field exists.
					if ( get_post_meta( $post->ID, '_event_bridge_for_activitypub_is_remote_cached', true ) ) {
						return true; // Force the online event link.
					}
				}

				return $force_online_event_link; // Default behavior.
			},
			10,
			1
		);
	}
}
