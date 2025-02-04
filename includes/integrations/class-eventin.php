<?php
/**
 * Eventin.
 *
 * Defines all the necessary meta information and methods for the integration of the
 * WordPress plugin "Eventin".
 *
 * @link    https://wordpress.org/plugins/eventin/
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Eventin as Eventin_Transformer;

/**
 * Eventin.
 *
 * Defines all the necessary meta information and methods for the integration of the
 * WordPress plugin "Eventin".
 *
 * @since 1.0.0
 */
final class Eventin extends Event_Plugin_Integration {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_relative_plugin_file(): string {
		return 'wp-event-solution/eventin.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return 'etn';
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page url.
	 */
	public static function get_settings_pages(): array {
		return array( 'eventin' ); // Base always is wp-admin/admin.php?page=eventin.
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return 'etn_category';
	}

	/**
	 * Returns the ActivityPub transformer for a Eventin event post.
	 *
	 * @param \WP_Post $post The WordPress post object of the Event.
	 * @return Eventin_Transformer
	 */
	public static function get_activitypub_event_transformer( $post ): Eventin_Transformer {
		return new Eventin_Transformer( $post, self::get_event_category_taxonomy() );
	}
}
