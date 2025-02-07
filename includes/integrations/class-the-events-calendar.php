<?php
/**
 * The Events Calendar.
 *
 * Defines all the necessary meta information for the integration of the
 * WordPress plugin "The Events Calendar".
 *
 * @link    https://wordpress.org/plugins/the-events-calendar/
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\The_Events_Calendar as The_Events_Calendar_Event_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place\The_Events_Calendar as The_Events_Calendar_Place_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\The_Events_Calendar as The_Events_Calendar_Transmogrifier;

/**
 * The Events Calendar.
 *
 * Defines all the necessary meta information for the integration of the
 * WordPress plugin "The Events Calendar".
 *
 * @since 1.0.0
 */
final class The_Events_Calendar extends Event_Plugin_Integration implements Feature_Event_Sources {
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
	 * @param \WP_Post $post The WordPress post object of the Event.
	 * @return The_Events_Calendar_Event_Transformer
	 */
	public static function get_activitypub_event_transformer( $post ): The_Events_Calendar_Event_Transformer {
		return new The_Events_Calendar_Event_Transformer( $post, self::get_event_category_taxonomy() );
	}

	/**
	 * Return the location/venue post type used by tribe.
	 *
	 * @return string
	 */
	public static function get_place_post_type(): string {
		return class_exists( '\Tribe__Events__Venue' ) ? \Tribe__Events__Venue::POSTTYPE : 'tribe_venue';
	}

	/**
	 * Return the organizers post type used by tribe.
	 *
	 * @return string
	 */
	public static function get_organizer_post_type(): string {
		return class_exists( '\Tribe__Events__Organizer' ) ? \Tribe__Events__Organizer::POSTTYPE : 'tribe_organizer';
	}

	/**
	 * Returns the ActivityPub transformer for a The_Events_Calendar venue post.
	 *
	 * @param \WP_Post $post The WordPress post object of the venue.
	 * @return The_Events_Calendar_Place_Transformer
	 */
	public static function get_activitypub_place_transformer( $post ): The_Events_Calendar_Place_Transformer {
		return new The_Events_Calendar_Place_Transformer( $post );
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
	public static function get_transmogrifier(): string {
		return The_Events_Calendar_Transmogrifier::class;
	}

	/**
	 * Get a list of Post IDs of events that have ended.
	 *
	 * @param int $ends_before_time Filter to only get events that ended before that datetime as unix-time.
	 *
	 * @return array<int>
	 */
	public static function get_cached_remote_events( $ends_before_time ): array {
		add_filter(
			'tribe_repository_events_apply_modifier_schema_entry',
			array( self::class, 'add_is_activitypub_remote_cached_to_query' ),
			10,
			1
		);

		$events = tribe_events()->where( 'ends_before', $ends_before_time )->get_ids();

		remove_filter(
			'tribe_repository_events_apply_modifier_schema_entry',
			array( self::class, 'add_is_activitypub_remote_cached_to_query' )
		);

		return $events;
	}

	/**
	 * Only show remote cached ActivityPub events in Tribe query.
	 *
	 * @param array $schema_entry The current schema entry.
	 * @return array The modified schema entry.
	 */
	public static function add_is_activitypub_remote_cached_to_query( $schema_entry ) {
		$schema_entry['meta_query']['is-remote-cached'] = array(
			'key'     => '_event_bridge_for_activitypub_event_source',
			'compare' => 'EXISTS',
		);
		return $schema_entry;
	}

	/**
	 * Get upcoming events.
	 */
	public static function upcoming_events() {
		$events = \tribe_get_events( array( 'ends_after' => 'now' ) );

		$event_objects = array();
		foreach ( $events as $event ) {
			$event_objects[] = The_Events_Calendar_Event_Transformer::transform( $event )->to_object()->to_array( false );
		}
	}
}
