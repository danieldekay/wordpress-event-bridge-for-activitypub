<?php
/**
 * Event Organiser.
 *
 * Defines all the necessary meta information and methods for the integration
 * of the WordPress "Event Organiser" plugin.
 *
 * @link    https://wordpress.org/plugins/event-organiser/
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Query;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Event_Organiser as Event_Organiser_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place\Event_Organiser as Event_Organiser_Place_Transformer;

/**
 * Event Organiser.
 *
 * Defines all the necessary meta information and methods for the integration
 * of the WordPress "Event Organiser" plugin.
 *
 * @since 1.0.0
 */
final class Event_Organiser extends Event_Plugin_Integration {
	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_relative_plugin_file(): string {
		return 'event-organiser/event-organiser.php';
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
		return array( 'event-organiser' );
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return 'event-category';
	}

	/**
	 * In case an event plugin uses a custom taxonomy for storing locations/venues return it here.
	 *
	 * @return string
	 */
	public static function get_place_taxonomy() {
		return 'event-venue';
	}

	/**
	 * Returns the ActivityPub transformer for a Event_Organiser event post.
	 *
	 * @param \WP_Post $post The WordPress post object of the Event.
	 * @return Event_Organiser_Transformer
	 */
	public static function get_activitypub_event_transformer( $post ): Event_Organiser_Transformer {
		return new Event_Organiser_Transformer( $post, self::get_event_category_taxonomy() );
	}

	/**
	 *  Returns the ActivityPub transformer for a Event_Organiser event venue which is stored in a taxonomy.
	 *
	 * @param \WP_Term $term The WordPress Term/Taxonomy of the venue.
	 * @return Event_Organiser_Place_Transformer
	 */
	public static function get_activitypub_place_transformer( $term ): Event_Organiser_Place_Transformer {
		if ( Query::get_instance()->is_activitypub_request() && defined( 'EVENT_ORGANISER_DIR' ) ) {
			$class_path = constant( EVENT_ORGANISER_DIR ) . 'includes/class-eo-theme-compatability.php';

			if ( file_exists( $class_path ) ) {
				require_once $class_path;

				$eo = \EO_Theme_Compatabilty::get_instance();
				if ( $eo instanceof \EO_Theme_Compatabilty ) {
					$eo->remove_filter( 'template_include', PHP_INT_MAX - 1 );
				}
			}
		}

		return new Event_Organiser_Place_Transformer( $term );
	}
}
