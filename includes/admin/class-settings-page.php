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

namespace Event_Bridge_For_ActivityPub\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Webfinger;
use Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source;
use Event_Bridge_For_ActivityPub\Event_Sources;
use Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources as Event_Source_Collection;
use Event_Bridge_For_ActivityPub\Integrations\Event_Plugin;
use Event_Bridge_For_ActivityPub\Setup;

/**
 * Class responsible for the Event Bridge for ActivityPub related Settings.
 *
 * Class which handles the "General" settings page for the Event Bridge for ActivityPub Plugin,
 * providing options for configuring various general settings.
 *
 * @since 1.0.0
 */
class Settings_Page {
	const STATIC = 'Event_Bridge_For_ActivityPub\Admin\Settings_Page';

	const SETTINGS_SLUG = 'event-bridge-for-activitypub';
	/**
	 * Warning if the plugin is Active and the ActivityPub plugin is not.
	 *
	 * @return void
	 */
	public static function admin_menu(): void {
		add_action(
			'admin_init',
			array( self::STATIC, 'maybe_add_event_source' ),
		);
		\add_options_page(
			'Event Bridge for ActivityPub',
			__( 'Event Bridge for ActivityPub', 'event-bridge-for-activitypub' ),
			'manage_options',
			self::SETTINGS_SLUG,
			array( self::STATIC, 'settings_page' ),
		);
	}

	/**
	 * Checks whether the current request wants to add an event source (ActivityPub follow) and passed on to actual handler.
	 */
	public static function maybe_add_event_source() {
		if ( ! isset( $_POST['event_bridge_for_activitypub_event_source'] ) ) {
			return;
		}

		// Check and verify request and check capabilities.
		if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'event-bridge-for-activitypub-event-sources-options' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$event_source = sanitize_text_field( $_POST['event_bridge_for_activitypub_event_source'] );

		$actor_url = false;

		$url = wp_parse_url( $event_source );

		if ( isset( $url['path'] ) && isset( $url['host'] ) && isset( $url['scheme'] ) ) {
			$actor_url = $event_source;
		} elseif ( preg_match( '/^@?' . Event_Source::ACTIVITYPUB_USER_HANDLE_REGEXP . '$/i', $event_source ) ) {
			$actor_url = Webfinger::resolve( $event_source );
			if ( is_wp_error( $actor_url ) ) {
				return;
			}
		} else {
			if ( ! isset( $url['path'] ) && isset( $url['host'] ) ) {
				$actor_url = Event_Sources::get_application_actor( $url['host'] );
			}
			if ( self::is_domain( $event_source ) ) {
				$actor_url = Event_Sources::get_application_actor( $event_source );
			}
		}

		if ( ! $actor_url ) {
			return;
		}

		Event_Source_Collection::add_event_source( $actor_url );
	}

	/**
	 * Check if a string is a valid domain name.
	 *
	 * @param string $domain The input string which might be a domain.
	 * @return bool
	 */
	private static function is_domain( $domain ): bool {
		$pattern = '/^(?!\-)(?:(?:[a-zA-Z\d](?:[a-zA-Z\d\-]{0,61}[a-zA-Z\d])?)\.)+(?!\d+$)[a-zA-Z\d]{2,63}$/';
		return 1 === preg_match( $pattern, $domain );
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

				\load_template( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . 'templates/settings.php', true, $args );
				break;
			case 'event-sources':
				wp_enqueue_script( 'thickbox' );
				wp_enqueue_style( 'thickbox' );
				\load_template( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . 'templates/event-sources.php', true );
				break;
			case 'welcome':
			default:
				wp_enqueue_script( 'plugin-install' );
				add_thickbox();
				wp_enqueue_script( 'updates' );

				\load_template( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . 'templates/welcome.php', true );
				break;
		}
	}
}
