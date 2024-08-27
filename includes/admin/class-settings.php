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

namespace Activitypub_Event_Extensions\Admin;

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
	const STATIC = 'Activitypub_Event_Extensions\Admin\Settings_Page';

	const SETTINGS_SLUG = 'activitypub-event-extensions';

	/**
	 * Register the settings for the ActivityPub Event Extensions plugin.
	 */
	public static function register_settings() {
		\register_setting(
			'activitypub-event-extensions',
			'activitypub_event_extensions_default_event_category',
			array(
				'type'         => 'string',
				'description'  => \__( 'Define your own custom post template', 'activitypub' ),
				'show_in_rest' => true,
				'default'      => 'MEETING',
			)
		);

		\register_setting(
			'activitypub-event-extensions',
			'activitypub_event_extensions_event_category_mappings',
			array(
				'type'              => 'array',
				'description'       => \__( 'Define your own custom post template', 'activitypub' ),
				'default'           => array(),
				'sanitize_callback' => function ( $event_category_mappings ) {
					$allowed_mappings = Event::DEFAULT_EVENT_CATEGORIES;
					foreach ( $event_category_mappings as $key => $value ) {
						if ( ! in_array( $value, $allowed_mappings, true ) ) {
							unset( $event_category_mappings[ $key ] );
						}
					}
					return $event_category_mappings;
				},
			)
		);
	}
}
