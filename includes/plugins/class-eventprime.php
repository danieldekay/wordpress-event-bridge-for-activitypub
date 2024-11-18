<?php
/**
 * EventPrime – Events Calendar, Bookings and Tickets
 *
 * @link    https://wordpress.org/plugins/eventprime-event-calendar-management/
 * @package ActivityPub_Event_Bridge
 * @since   1.0.0
 */

namespace ActivityPub_Event_Bridge\Plugins;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * This class defines which information is necessary for the EventPrime event plugin.
 *
 * @since 1.0.0
 */
final class EventPrime extends Event_Plugin {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file(): string {
		return 'eventprime-event-calendar-management/event-prime.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return 'em_event';
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page urls.
	 */
	public static function get_settings_pages(): array {
		return array( 'ep-settings' );
	}

	/**
	 * Returns the ActivityPub transformer class.
	 *
	 * @return string
	 */
	public static function get_activitypub_transformer_class_name(): string {
		return 'EventPrime';
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return 'em_event_type';
	}
}
