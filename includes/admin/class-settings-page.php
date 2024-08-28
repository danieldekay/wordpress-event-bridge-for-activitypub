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

use Activitypub_Event_Extensions\Setup;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Class responsible for the ActivityPui Event Extension related Settings.
 *
 * Class responsible for the ActivityPui Event Extension related Settings.
 *
 * @since 1.0.0
 */
class Settings_Page {
	const STATIC = 'Activitypub_Event_Extensions\Admin\Settings_Page';

	const SETTINGS_SLUG = 'activitypub-event-extensions';
	/**
	 * Warning if the plugin is Active and the ActivityPub plugin is not.
	 */
	public static function admin_menu() {
		\add_options_page(
			'Activitypub Event Extension',
			__( 'ActivityPub Events', 'activitypub_event_extensions' ),
			'manage_options',
			self::SETTINGS_SLUG,
			array( self::STATIC, 'settings_page' )
		);
	}

	/**
	 * Adds Link to the settings page in the plugin page.
	 * It's called via apply_filter('plugin_action_links_' . PLUGIN_NAME).
	 *
	 * @param array $links    Already added links.
	 * @return array          Original links but with link to setting page added.
	 */
	public static function settings_link( $links ) {
		return array_merge(
			$links,
			array(
				'<a href="' . admin_url( 'options-general.php?page=' . self::SETTINGS_SLUG ) . '">Settings</a>',
			)
		);
	}

	/**
	 * Receive the event categories (terms) used by the event plugin.
	 *
	 * @param array $event_plugin Contains info about a certain event plugin.
	 *
	 * @return array An array of Terms.
	 */
	private static function get_event_terms( $event_plugin ) {
		if ( isset( $event_plugin['taxonomy'] ) ) {
			$event_terms = get_terms(
				array(
					'taxonomy'   => $event_plugin['taxonomy'],
					'hide_empty' => true,
				)
			);
			return $event_terms;
		} else {
			return array();
		}
	}

	/**
	 * Settings page.
	 */
	public static function settings_page() {
		$plugin_setup = Setup::get_instance();

		$event_plugins = $plugin_setup->get_active_event_plugins();

		$event_terms = array();

		foreach ( $event_plugins as $event_plugin_name => $events_plugin_info ) {
			$event_terms = array_merge( $event_terms, self::get_event_terms( $events_plugin_info ) );
		}

		$args = array(
			'slug'        => self::SETTINGS_SLUG,
			'event_terms' => $event_terms,
		);

		\load_template( ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_DIR . 'templates/settings.php', true, $args );
	}
}
