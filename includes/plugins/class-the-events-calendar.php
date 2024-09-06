<?php
/**
 * The Events Calendar.
 *
 * Defines all the necessary meta information for the events calendar.
 *
 * @link    https://wordpress.org/plugins/the-events-calendar/
 * @package Activitypub_Event_Extensions
 * @since   1.0.0
 */

namespace Activitypub_Event_Extensions\Plugins;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

require_once __DIR__ . '/interface-event-plugin.php';

use Activitypub_Event_Extensions\Plugins\Event_Plugin;

/**
 * Interface for a supported event plugin.
 *
 * This interface defines which information is necessary for a supported event plugin.
 *
 * @since 1.0.0
 */
class The_Events_Calendar implements Event_plugin {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file(): string {
		return 'the-events-calendar/the-events-calendar.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return \Tribe__Events__Main::POSTTYPE;
	}

	const POST_TYPE = class_exists( 'Tribe__Events__Main' ) ? \Tribe__Events__Main::POSTTYPE : 'tribe_event';

	/**
	 * Returns the ID of the main settings page of the plugin.
	 *
	 * @return string The settings page url.
	 */
	public static function get_settings_page(): string {
		// TODO: Tribe\Events\Admin\Settings::settings_page_id.
		return 'edit.php?post_type=tribe_events&page=tec-events-settings';
	}

	/**
	 * Returns the ActivityPub transformer class.
	 *
	 * @return string
	 */
	public static function get_activitypub_transformer_class_name(): string {
		return 'Tribe';
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_taxonomy(): string {
		return Tribe__Events__Main::TAXONOMY;
	}
}
