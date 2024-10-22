<?php
/**
 * General settings class.
 *
 * This file contains the General class definition, which handles the "General" settings
 * page for the Activitypub Event Bridge Plugin, providing options for configuring various general settings.
 *
 * @package ActivityPub_Event_Bridge
 * @since 1.0.0
 */

namespace ActivityPub_Event_Bridge\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use ActivityPub_Event_Bridge\Plugins\Event_Plugin;
use ActivityPub_Event_Bridge\Setup;

/**
 * Class responsible for the Activitypub Event Bridge related Settings.
 *
 * Class which handles the "General" settings page for the Activitypub Event Bridge Plugin,
 * providing options for configuring various general settings.
 *
 * @since 1.0.0
 */
class Settings_Page {
	const STATIC = 'ActivityPub_Event_Bridge\Admin\Settings_Page';

	const SETTINGS_SLUG = 'activitypub-event-bridge';
	/**
	 * Warning if the plugin is Active and the ActivityPub plugin is not.
	 *
	 * @return void
	 */
	public static function admin_menu(): void {
		\add_options_page(
			'Activitypub Event Bridge',
			__( 'ActivityPub Event Bridge', 'activitypub-event-bridge' ),
			'manage_options',
			self::SETTINGS_SLUG,
			array( self::STATIC, 'settings_page' ),
		);
	}

	/**
	 * Adds Link to the settings page in the plugin page.
	 * It's called via apply_filter('plugin_action_links_' . PLUGIN_NAME).
	 *
	 * @param array $links    Already added links.
	 *
	 * @return array          Original links but with link to setting page added.
	 */
	public static function settings_link( $links ): array {
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
	 * @param Event_Plugin $event_plugin Contains info about a certain event plugin.
	 *
	 * @return array An array of Terms.
	 */
	private static function get_event_terms( $event_plugin ): array {
		$taxonomy = $event_plugin::get_event_category_taxonomy();
		if ( $taxonomy ) {
			$event_terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => true,
				)
			);
			return ! is_wp_error( $event_terms ) ? $event_terms : array();
		} else {
			return array();
		}
	}

	/**
	 * Preparing the data and loading the template for the settings page.
	 *
	 * @return void
	 */
	public static function settings_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['tab'] ) ) {
			$tab = 'welcome';
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab = sanitize_key( $_GET['tab'] );
		}

		switch ( $tab ) {
			case 'settings':
				$plugin_setup = Setup::get_instance();

				$event_plugins = $plugin_setup->get_active_event_plugins();

				$event_terms = array();

				foreach ( $event_plugins as $event_plugin ) {
					$event_terms = array_merge( $event_terms, self::get_event_terms( $event_plugin ) );
				}

				$args = array(
					'slug'        => self::SETTINGS_SLUG,
					'event_terms' => $event_terms,
				);

				\load_template( ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_DIR . 'templates/settings.php', true, $args );
				break;
			case 'welcome':
			default:
				wp_enqueue_script( 'plugin-install' );
				add_thickbox();
				wp_enqueue_script( 'updates' );

				\load_template( ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_DIR . 'templates/welcome.php', true );
				break;
		}
	}
}
