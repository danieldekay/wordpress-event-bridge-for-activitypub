<?php
/**
 * The Events Calendar.
 *
 * Defines all the necessary meta information for the events calendar.
 *
 * @link    https://wordpress.org/plugins/the-events-calendar/
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\The_Events_Calendar as The_Events_Calendar_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\The_Events_Calendar as The_Events_Calendar_Transmogrifier;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Interface for a supported event plugin.
 *
 * This interface defines which information is necessary for a supported event plugin.
 *
 * @since 1.0.0
 */
final class The_Events_Calendar extends Event_plugin_Integration implements Feature_Event_Sources {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_relative_plugin_file(): string {
		return 'the-events-calendar/the-events-calendar.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return class_exists( '\Tribe__Events__Main' ) ? \Tribe__Events__Main::POSTTYPE : 'tribe_event';
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return class_exists( '\Tribe__Events__Main' ) ? \Tribe__Events__Main::TAXONOMY : 'tribe_events_cat';
	}

	/**
	 * Returns the ActivityPub transformer for a The_Events_Calendar event post.
	 *
	 * @param WP_Post $post The WordPress post object of the Event.
	 * @return The_Events_Calendar_Transformer
	 */
	public static function get_activitypub_event_transformer( $post ): The_Events_Calendar_Transformer {
		return new The_Events_Calendar_Transformer( $post, self::get_event_category_taxonomy() );
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page urls.
	 */
	public static function get_settings_pages(): array {
		if ( class_exists( '\Tribe\Events\Admin\Settings' ) ) {
			$page = \Tribe\Events\Admin\Settings::$settings_page_id;
		} else {
			$page = 'tec-events-settings';
		}
		return array( $page );
	}

	/**
	 * Returns the Transmogrifier for The_Events_Calendar.
	 */
	public static function get_transmogrifier(): The_Events_Calendar_Transmogrifier {
		return new The_Events_Calendar_Transmogrifier();
	}

	/**
	 * Get a list of Post IDs of events that have ended.
	 *
	 * @param int $ended_before_time Filter: only get events that ended before that datetime as unix-time.
	 *
	 * @return array
	 */
	public static function get_cached_remote_events( $ended_before_time ): array {
		return array();
	}
}
