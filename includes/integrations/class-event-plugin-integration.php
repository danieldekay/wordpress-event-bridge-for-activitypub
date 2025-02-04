<?php
/**
 * Abstract base class for a basic integration of a WordPress event plugin.
 *
 * Basic information and methods that each supported event needs for this plugin to work.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Event as ActivityPub_Event_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place\Base_Post_Place;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place\Base_Term_Place;
use WP_Post;

require_once EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . 'includes/integrations/interface-feature-event-sources.php';

/**
 * Abstract base class for a basic integration of a WordPress event plugin.
 *
 * Basic information and methods that each supported event needs for this plugin to work.
 *
 * @since 1.0.0
 */
abstract class Event_Plugin_Integration {
	/**
	 * Returns the plugin file relative to the plugins dir.
	 *
	 * @return string
	 */
	abstract public static function get_relative_plugin_file(): string;

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	abstract public static function get_post_type(): string;

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	abstract public static function get_event_category_taxonomy(): string;

	/**
	 * Returns the Activitypub transformer for events of the event plugins event post type.
	 *
	 * @param WP_Post $post           The WordPress post object of the Event.
	 * @return ActivityPub_Event_Transformer
	 */
	abstract public static function get_activitypub_event_transformer( $post ): ActivityPub_Event_Transformer;

	/**
	 * In case an event plugin uses a custom post type for the locations/venues return it here.
	 *
	 * @return ?string
	 */
	public static function get_place_post_type() {
		return null;
	}

	/**
	 * In case an event plugin uses a custom taxonomy for storing locations/venues return it here.
	 *
	 * @return ?string
	 */
	public static function get_place_taxonomy() {
		return null;
	}

	/**
	 * Returns the Activitypub transformer for places of the event plugins location post type.
	 *
	 * @param WP_Post $post           The WordPress post object of the Event.
	 * @return Base_Post_Place|Base_Term_Place|null
	 */
	public static function get_activitypub_place_transformer( $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		return null;
	}

	/**
	 * In case an event plugin used a custom post type for organizers return it here.
	 *
	 * @return ?string
	 */
	public static function get_organizer_post_type() {
		return null;
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The IDs of one or several admin/settings pages.
	 */
	public static function get_settings_pages(): array {
		return array();
	}

	/**
	 * Get the plugins name from the main plugin-file's top-level-file-comment.
	 */
	public static function get_plugin_name(): string {
		$all_plugins = array_merge( get_plugins(), get_mu_plugins() );
		if ( isset( $all_plugins[ static::get_relative_plugin_file() ]['Name'] ) ) {
			return $all_plugins[ static::get_relative_plugin_file() ]['Name'];
		} else {
			return '';
		}
	}

	/**
	 * Detects whether the current screen is a admin page of the event plugin.
	 */
	public static function is_plugin_page(): bool {
		// Get the current page.
		$screen = get_current_screen();

		// Check if we are on a edit page for the event, or on the settings page of the event plugin.
		$is_event_plugins_edit_page     = 'edit' === $screen->base && static::get_post_type() === $screen->post_type;
		$is_event_plugins_settings_page = in_array( $screen->id, static::get_settings_pages(), true );

		return $is_event_plugins_edit_page || $is_event_plugins_settings_page;
	}
}
