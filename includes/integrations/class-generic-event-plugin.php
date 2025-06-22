<?php
/**
 * Generic Event Plugin.
 *
 * Provides support for any generic event plugin through configurable field mappings.
 * Users can specify the post type and map fields to ActivityPub event properties.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Generic_Event as Generic_Event_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\Generic_Event as Generic_Event_Transmogrifier;
use WP_Post;
use WP_Query;

/**
 * Generic Event Plugin.
 *
 * Provides support for any generic event plugin through configurable field mappings.
 * Users can specify the post type and map fields to ActivityPub event properties.
 *
 * @since 1.0.0
 */
final class Generic_Event_Plugin extends Event_Plugin_Integration implements Feature_Event_Sources {

	/**
	 * Returns the plugin file relative to the plugins dir.
	 * For generic plugin, this returns a placeholder since it's not tied to a specific plugin.
	 *
	 * @return string
	 */
	public static function get_relative_plugin_file(): string {
		return 'generic-event-plugin/generic-event-plugin.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 * This is configurable via settings.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return \get_option( 'event_bridge_for_activitypub_generic_post_type', 'event' );
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 * This is configurable via settings.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return \get_option( 'event_bridge_for_activitypub_generic_category_taxonomy', 'category' );
	}

	/**
	 * Returns the ActivityPub transformer for a generic event post.
	 *
	 * @param WP_Post $post The WordPress post object of the Event.
	 * @return Generic_Event_Transformer
	 */
	public static function get_activitypub_event_transformer( $post ): Generic_Event_Transformer {
		return new Generic_Event_Transformer( $post, self::get_event_category_taxonomy() );
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 * Since this is generic, it returns the generic plugin settings page.
	 *
	 * @return array The settings page IDs.
	 */
	public static function get_settings_pages(): array {
		return array( 'event-bridge-for-activitypub-generic' );
	}

	/**
	 * Get the plugin name.
	 * Since this is a generic integration, return a descriptive name.
	 *
	 * @return string
	 */
	public static function get_plugin_name(): string {
		return __( 'Generic Event Plugin', 'event-bridge-for-activitypub' );
	}

	/**
	 * Check if the generic event plugin is enabled.
	 * The generic plugin is "active" if it's enabled in settings.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return (bool) \get_option( 'event_bridge_for_activitypub_generic_enabled', false );
	}

	/**
	 * Returns the Transmogrifier for Generic Event Plugin.
	 *
	 * @return string
	 */
	public static function get_transmogrifier(): string {
		return Generic_Event_Transmogrifier::class;
	}

	/**
	 * Get a list of Post IDs of events that have ended.
	 *
	 * @param int $ends_before_time Filter to only get events that ended before that datetime as unix-time.
	 *
	 * @return array<int>
	 */
	public static function get_cached_remote_events( $ends_before_time ): array {
		$post_type = self::get_post_type();
		$field_mappings = \get_option( 'event_bridge_for_activitypub_generic_field_mappings', array() );
		
		// If no end time field is configured, we can't filter by end time
		if ( ! isset( $field_mappings['end_time'] ) ) {
			return array();
		}

		$end_time_config = $field_mappings['end_time'];
		$meta_query_args = array(
			'relation' => 'AND',
			array(
				'key'     => '_event_bridge_for_activitypub_event_source',
				'compare' => 'EXISTS',
			),
		);

		// Add end time filter based on source type
		if ( $end_time_config['source_type'] === 'meta' && ! empty( $end_time_config['field_name'] ) ) {
			$meta_query_args[] = array(
				'key'     => $end_time_config['field_name'],
				'value'   => $ends_before_time,
				'type'    => 'NUMERIC',
				'compare' => '<',
			);
		}

		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => $meta_query_args,
		);

		$query = new WP_Query( $args );
		return $query->posts;
	}
}