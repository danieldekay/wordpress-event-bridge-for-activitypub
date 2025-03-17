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
use Event_Bridge_For_ActivityPub\Integrations\Event_Plugin_Integration;
use Event_Bridge_For_ActivityPub\Integrations\Feature_Event_Sources;
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
	 * Init settings pages.
	 *
	 * @return void
	 */
	public static function init() {
		\add_filter( 'activitypub_admin_settings_tabs', array( self::class, 'add_settings_tab' ) );
		\add_action(
			'admin_init',
			array( self::class, 'maybe_add_event_source' ),
		);
	}

	/**
	 * Adds a custom tab to the ActivityPub settings.
	 *
	 * @param  array $tabs The existing tabs array.
	 * @return array The modified tabs array.
	 */
	public static function add_settings_tab( $tabs ): array {
		$tabs['event-bridge-for-activitypub'] = array(
			'label'    => __( 'Event Bridge', 'event-bridge-for-activitypub' ),
			'template' => EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . 'templates/settings/tab.php',
		);

		return $tabs;
	}

	/**
	 * Checks whether the current request wants to add an event source (ActivityPub follow) and passed on to actual handler.
	 *
	 * @return void
	 */
	public static function maybe_add_event_source() {
		if ( ! isset( $_POST['event_bridge_for_activitypub_add_event_source'] ) ) {
			return;
		}

		// Check and verify request and check capabilities.
		if ( ! \wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'event-bridge-for-activitypub_add-event-source-options' ) ) {
			return;
		}

		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$event_source = \sanitize_text_field( $_POST['event_bridge_for_activitypub_add_event_source'] );

		$actor_url = false;
		$url       = \wp_parse_url( $event_source );

		$error_message = \esc_html__( 'Failed to add Event Source', 'event-bridge-for-activitypub' );

		// Check if URL is a Collection or a single Actor.
		$maybe_collection = \wp_safe_remote_get( $event_source );

		if ( ! \is_wp_error( $maybe_collection ) ) {
			$maybe_collection = \json_decode( \wp_remote_retrieve_body( $maybe_collection ), true );
		}

		$event_sources = array();

		if ( isset( $maybe_collection['type'] ) && in_array( $maybe_collection['type'], array( 'Collection', 'OrderedCollection' ), true ) ) {
			// Return only the IDs of the items in the collection.
			$event_sources = \wp_list_pluck( $maybe_collection['items'], 'id' );
		} else {
			$event_sources[] = $event_source;
		}

		// Iterate over all event sources and add them to the collection.
		foreach ( $event_sources as $event_source ) {
			$url = \wp_parse_url( $event_source );

			if ( isset( $url['path'], $url['host'], $url['scheme'] ) ) {
				$actor_url = \sanitize_url( $event_source );
			} elseif ( preg_match( '/^@?' . Event_Source::ACTIVITYPUB_USER_HANDLE_REGEXP . '$/i', $event_source ) ) {
				$actor_url = Webfinger::resolve( $event_source );
				if ( \is_wp_error( $actor_url ) ) {
					\add_settings_error(
						'event-bridge-for-activitypub_add-event-source',
						'event_bridge_for_activitypub_cannot_follow_actor',
						$error_message . ': ' . esc_html__( 'Cannot find an ActivityPub actor for this user handle via Webfinger.', 'event-bridge-for-activitypub' ),
						'error'
					);
					continue;
				}
			} else {
				if ( ! isset( $url['path'] ) && isset( $url['host'] ) ) {
					$actor_url = Event_Sources::get_application_actor( $url['host'] );
				} elseif ( self::is_domain( $event_source ) ) {
					$actor_url = Event_Sources::get_application_actor( $event_source );
				}
				if ( ! $actor_url ) {
					\add_settings_error(
						'event-bridge-for-activitypub_add-event-source',
						'event_bridge_for_activitypub_cannot_follow_actor',
						$error_message . ': ' . \esc_html__( 'Unable to identify the ActivityPub relay actor to follow for this domain.', 'event-bridge-for-activitypub' ),
						'error'
					);
					continue;
				}
			}

			if ( ! $actor_url ) {
				\add_settings_error(
					'event-bridge-for-activitypub_add-event-source',
					'event_bridge_for_activitypub_cannot_follow_actor',
					$error_message . ': ' . \esc_html__( 'ActivityPub actor does not exist.', 'event-bridge-for-activitypub' ),
					'error'
				);
				continue;
			}

			// Don't proceed if on the same host!
			if ( \wp_parse_url( \home_url(), PHP_URL_HOST ) === \wp_parse_url( $actor_url, PHP_URL_HOST ) ) {
				\add_settings_error(
					'event-bridge-for-activitypub_add-event-source',
					'event_bridge_for_activitypub_cannot_follow_actor',
					$error_message . ': ' . \esc_html__( 'Cannot follow own actor on own domain.', 'event-bridge-for-activitypub' ),
					'error'
				);
				continue;
			}

			Event_Source_Collection::add_event_source( $actor_url );
		}
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
		$links[] = \sprintf(
			'<a href="%1s">%2s</a>',
			\add_query_arg( 'tab', 'event-bridge-for-activitypub', \menu_page_url( 'activitypub', false ) ),
			\__( 'Settings', 'event-bridge-for-activitypub' )
		);

		return $links;
	}

	/**
	 * Receive the event categories (terms) used by the event plugin.
	 *
	 * @param Event_Plugin_Integration $event_plugin Contains info about a certain event plugin.
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
	public static function do_settings_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['subpage'] ) ) {
			$tab = 'welcome';
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab = \sanitize_key( $_GET['subpage'] );
		}

		// Fallback to always re-scan active event plugins, when user visits admin area of this plugin.
		$plugin_setup = Setup::get_instance();
		$plugin_setup->redetect_active_event_plugins();
		$event_plugins = $plugin_setup->get_active_event_plugins();

		switch ( $tab ) {
			case 'settings':
				$event_terms = array();

				foreach ( $event_plugins as $event_plugin_integration ) {
					$event_terms = array_merge( $event_terms, self::get_event_terms( $event_plugin_integration ) );
				}

				$args = array(
					'slug'        => self::SETTINGS_SLUG,
					'event_terms' => $event_terms,
				);

				\load_template( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . 'templates/settings/subpages/settings.php', true, $args );
				break;
			case 'event-sources':
				$supports_event_sources = array();

				foreach ( $event_plugins as $event_plugin_integration ) {
					if ( is_a( $event_plugin_integration, Feature_Event_Sources::class ) ) {
						$class_name                            = get_class( $event_plugin_integration );
						$supports_event_sources[ $class_name ] = $event_plugin_integration::get_plugin_name();
					}
				}

				$args = array(
					'supports_event_sources' => $supports_event_sources,
				);

				\wp_enqueue_script( 'thickbox' );
				\wp_enqueue_style( 'thickbox' );
				\load_template( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . 'templates/settings/subpages/event-sources.php', true, $args );
				break;
			case 'welcome':
			default:
				\wp_enqueue_script( 'plugin-install' );
				\add_thickbox();
				\wp_enqueue_script( 'updates' );

				\load_template( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . 'templates/settings/subpages/welcome.php', true );
				break;
		}
	}
}
