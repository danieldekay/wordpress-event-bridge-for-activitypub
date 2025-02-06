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

use Event_Bridge_For_ActivityPub\Integrations\Feature_Event_Sources;

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
				'description'       => \__( 'Default standardized federated event category.', 'event-bridge-for-activitypub' ),
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
			'event_bridge_for_activitypub_reminder_time_gap',
			array(
				'type'              => 'array',
				'description'       => \__( 'Time gap in seconds when a reminder is triggered that the event is about to start.', 'event-bridge-for-activitypub' ),
				'default'           => 0, // Zero leads to this feature being deactivated.
				'sanitize_callback' => 'absint',
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
			'event-bridge-for-activitypub',
			'event_bridge_for_activitypub_summary_type',
			array(
				'type'         => 'string',
				'description'  => \__( 'Summary type to use for ActivityStreams', 'event-bridge-for-activitypub' ),
				'show_in_rest' => true,
				'default'      => 'preset',
			)
		);

		\register_setting(
			'event-bridge-for-activitypub',
			'event_bridge_for_activitypub_summary_format',
			array(
				'type'         => 'string',
				'description'  => \__( 'Summary format to use for ActivityStreams', 'event-bridge-for-activitypub' ),
				'show_in_rest' => true,
				'default'      => 'html',
			)
		);

		\register_setting(
			'event-bridge-for-activitypub',
			'event_bridge_for_activitypub_custom_summary',
			array(
				'type'         => 'string',
				'description'  => \__( 'Define your own custom summary template for events', 'event-bridge-for-activitypub' ),
				'show_in_rest' => true,
				'default'      => EVENT_BRIDGE_FOR_ACTIVITYPUB_SUMMARY_TEMPLATE,
			)
		);

		\register_setting(
			'event-bridge-for-activitypub',
			'event_bridge_for_activitypub_event_sources_active',
			array(
				'type'         => 'boolean',
				'show_in_rest' => true,
				'description'  => \__( 'Whether the event sources feature is activated.', 'event-bridge-for-activitypub' ),
				'default'      => 0,
			)
		);

		\register_setting(
			'event-bridge-for-activitypub',
			'event_bridge_for_activitypub_event_source_cache_retention',
			array(
				'type'              => 'integer',
				'show_in_rest'      => true,
				'description'       => \__( 'The cache retention period for external event sources.', 'event-bridge-for-activitypub' ),
				'default'           => WEEK_IN_SECONDS,
				'sanitize_callback' => 'absint',
			)
		);

		\register_setting(
			'event-bridge-for-activitypub',
			'event_bridge_for_activitypub_integration_used_for_event_sources_feature',
			array(
				'type'              => 'string',
				'description'       => \__( 'Define which plugin/integration is used for the event sources feature', 'event-bridge-for-activitypub' ),
				'default'           => array(),
				'sanitize_callback' => array( self::class, 'sanitize_event_plugin_integration_used_for_event_sources' ),
			)
		);

		\register_setting(
			'event-bridge-for-activitypub-event-sources',
			'event_bridge_for_activitypub_event_sources',
			array(
				'type'              => 'array',
				'description'       => \__( 'Dummy setting', 'event-bridge-for-activitypub' ),
				'default'           => array(),
				'sanitize_callback' => 'is_array',
			)
		);
	}

	/**
	 * Sanitize the option which event plugin.
	 *
	 * @param mixed $event_plugin_integration The setting.
	 * @return string
	 */
	public static function sanitize_event_plugin_integration_used_for_event_sources( $event_plugin_integration ): string {
		if ( ! is_string( $event_plugin_integration ) ) {
			return '';
		}
		$setup                = Setup::get_instance();
		$active_event_plugins = $setup->get_active_event_plugins();

		$valid_options = array();
		foreach ( $active_event_plugins as $active_event_plugin ) {
			if ( $active_event_plugin instanceof Feature_Event_Sources ) {
				$valid_options[] = get_class( $active_event_plugin );
			}
		}
		if ( in_array( $event_plugin_integration, $valid_options, true ) ) {
			return $event_plugin_integration;
		}
		return Setup::get_default_integration_class_name_used_for_event_sources_feature();
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
		require_once EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . '/includes/event-categories.php';
		$allowed_event_categories = array_keys( EVENT_BRIDGE_FOR_ACTIVITYPUB_EVENT_CATEGORIES );
		return in_array( $event_category, $allowed_event_categories, true );
	}
}
