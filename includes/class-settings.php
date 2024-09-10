<?php
/**
 * General settings class.
 *
 * This file contains the General class definition, which handles the "General" settings
 * page for the ActivityPub Event Extension Plugin, providing options for configuring various general settings.
 *
 * @package Activitypub_Event_Extensions
 * @since 1.0.0
 */

namespace Activitypub_Event_Extensions;

use Activitypub\Activity\Extended_Object\Event;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Class responsible for the ActivityPui Event Extension related Settings.
 *
 * Class responsible for the ActivityPui Event Extension related Settings.
 *
 * @since 1.0.0
 */
class Settings {
	const SETTINGS_SLUG = 'activitypub-event-extensions';

	const DEFAULT_EVENT_CATEGORY = 'MEETING';

	/**
	 * Register the settings for the ActivityPub Event Extensions plugin.
	 */
	public static function register_settings() {
		\register_setting(
			'activitypub-event-extensions',
			'activitypub_event_extensions_default_event_category',
			array(
				'type'              => 'string',
				'description'       => \__( 'Define your own custom post template', 'activitypub' ),
				'show_in_rest'      => true,
				'default'           => self::DEFAULT_EVENT_CATEGORY,
				'sanitize_callback' => array( self::class, 'sanitize_mapped_event_category' ),
			)
		);

		\register_setting(
			'activitypub-event-extensions',
			'activitypub_event_extensions_event_category_mappings',
			array(
				'type'              => 'array',
				'description'       => \__( 'Define your own custom post template', 'activitypub' ),
				'default'           => array(),
				'sanitize_callback' => array( self::class, 'sanitize_event_category_mappings' ),
			)
		);
	}

	/**
	 * Sanitize the target ActivityPub Event category.
	 *
	 * @param string $event_category The ActivityPUb event category.
	 */
	public static function sanitize_mapped_event_category( $event_category ) {
		return self::is_allowed_event_category( $event_category ) ? $event_category : self::DEFAULT_EVENT_CATEGORY;
	}

	/**
	 * Sanitization function for the event category mapping setting.
	 *
	 * Currently only the default event categories are allowed to be target of a mapping.
	 *
	 * @param array $event_category_mappings The settings value.
	 */
	public static function sanitize_event_category_mappings( $event_category_mappings ) {
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
	private static function is_allowed_event_category( $event_category ) {
		$allowed_event_categories = Event::DEFAULT_EVENT_CATEGORIES;
		return in_array( $event_category, $allowed_event_categories, true );
	}
}
