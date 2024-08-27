<?php
/**
 * GatherPress.
 *
 * Defines all the necessary meta information for the GatherPress plugin.
 *
 * @link    https://wordpress.org/plugins/gatherpress/
 * @package Activitypub_Event_Extensions
 * @since   1.0.0
 */

namespace Activitypub_Event_Extensions\Plugins;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

require_once __DIR__ . '/interface-event-plugin.php';

use Activitypub_Event_Extensions\Plugins\Event_Plugin;
use GatherPress\Core\Event;
use GatherPress\Core\Topic;
use GatherPress\Core\Utility;

/**
 * Interface for a supported event plugin.
 *
 * This interface defines which information is necessary for a supported event plugin.
 *
 * @since 1.0.0
 */
class Gatherpress implements Event_Plugin {
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
		return Event::POST_TYPE;
	}

	/**
	 * Returns the ID of the main settings page of the plugin.
	 *
	 * @return string The settings page url.
	 */
	public static function get_settings_page(): string {
		return Utility::prefix_key( 'general' );
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
	public static function get_taxonomy(): string {
		return Topic::TAXONOMY;
	}
}
