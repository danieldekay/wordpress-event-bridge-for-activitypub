<?php
/**
 * General settings class.
 *
 * This file contains the General class definition, which handles the "General" settings
 * page for the Event Bridge for ActivityPub Plugin, providing options for configuring various general settings.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 */

namespace Event_Bridge_For_ActivityPub;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Event;

/**
 * Class responsible for the ActivityPui Event Extension related Settings.
 *
 * Class responsible for the ActivityPui Event Extension related Settings.
 *
 * @since 1.0.0
 */
class Settings {
	const SETTINGS_SLUG = 'event-bridge-for-activitypub';

	/**
	 * The default ActivityPub event category.
	 *
	 * @var string
	 */
	const DEFAULT_EVENT_CATEGORY = 'MEETING';

	/**
	 * Register the settings for the Event Bridge for ActivityPub plugin.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		\register_setting(
			'event-bridge-for-activitypub',
			'event_bridge_for_activitypub_default_event_category',
			array(
				'type'              => 'string',
				'description'       => \__( 'Define your own custom post template', 'event-bridge-for-activitypub' ),
				'show_in_rest'      => true,
				'default'           => self::DEFAULT_EVENT_CATEGORY,
				'sanitize_callback' => array( self::class, 'sanitize_mapped_event_category' ),
			)
		);

		\register_setting(
			'event-bridge-for-activitypub',
			'event_bridge_for_activitypub_event_category_mappings',
			array(
				'type'              => 'array',
				'description'       => \__( 'Define your own custom post template', 'event-bridge-for-activitypub' ),
				'default'           => array(),
				'sanitize_callback' => array( self::class, 'sanitize_event_category_mappings' ),
			)
		);

		\register_setting(
			'event-bridge-for-activitypub',
			'event_bridge_for_activitypub_initially_activated',
			array(
				'type'        => 'boolean',
				'description' => \__( 'Whether the plugin just got activated for the first time.', 'event-bridge-for-activitypub' ),
				'default'     => 1,
			)
		);

		\register_setting(
			'activitypub-event-bridge',
			'activitypub_summary_type',
			array(
				'type'         => 'string',
				'description'  => \__( 'Summary type to use for ActivityStreams', 'activitypub-event-bridge' ),
				'show_in_rest' => true,
				'default'      => 'preset',
			)
		);

		\register_setting(
			'activitypub-event-bridge',
			'event_bridge_for_activitypub_custom_summary',
			array(
				'type'         => 'string',
				'description'  => \__( 'Define your own custom summary template for events', 'activitypub-event-bridge' ),
				'show_in_rest' => true,
				'default'      => EVENT_BRIDGE_FOR_ACTIVITYPUB_CUSTOM_SUMMARY,
			)
		);
	}

	/**
	 * Sanitize the target ActivityPub Event category.
	 *
	 * @param string $event_category The ActivityPUb event category.
	 */
	public static function sanitize_mapped_event_category( $event_category ): string {
		return self::is_allowed_event_category( $event_category ) ? $event_category : self::DEFAULT_EVENT_CATEGORY;
	}

	/**
	 * Sanitization function for the event category mapping setting.
	 *
	 * Currently only the default event categories are allowed to be target of a mapping.
	 *
	 * @param array $event_category_mappings The settings value.
	 *
	 * @return array An array that contains only valid mapping pairs.
	 */
	public static function sanitize_event_category_mappings( $event_category_mappings ): array {
		if ( empty( $event_category_mappings ) ) {
			return array();
		}
		foreach ( $event_category_mappings as $taxonomy_slug => $event_category ) {
			if ( ! self::is_allowed_event_category( $event_category ) ) {
				unset( $event_category_mappings[ $taxonomy_slug ] );
			}
		}
		return $event_category_mappings;
	}

	/**
	 * Checks if the given event category is allowed to be target of a mapping.
	 *
	 * @param string $event_category The event category to check.
	 *
	 * @return bool True if allowed, false otherwise.
	 */
	private static function is_allowed_event_category( $event_category ): bool {
		$allowed_event_categories = Event::DEFAULT_EVENT_CATEGORIES;
		return in_array( $event_category, $allowed_event_categories, true );
	}
}
