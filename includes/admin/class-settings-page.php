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

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Class responsible for Event Plugin related admin notices.
 *
 * Notices for guiding to proper configuration of ActivityPub with event plugins.
 *
 * @since 1.0.0
 */
class Settings_Page {

	/**
	 * TODO:
     * 	- [ ] create settings page
	 *      - [ ] skeleton
	 *      - [ ] Autoloader
	 *  - [ ] Common settings?
	 *  - [ ] Hook points
	 *      - [ ] let transformers hook settings into the page
	 *  - [ ] provide setting-type-classes for hooks
	 *      - [ ] True/False
	 *      - [ ] Number
	 *      - [ ] advanced for mapping
	 */

	const STATIC = 'Activitypub_Event_Extensions\Admin\Settings_Page';

	const SETTINGS_SLUG = 'activitypub-events';

	/**
	 * Warning if the plugin is Active and the ActivityPub plugin is not.
	 */
	public static function admin_menu() {
		\add_options_page(
			'Activitypub Event Extension',
			'Activitypub Events',
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

	public static function settings_page() {
		if ( empty( $_GET['tab'] ) ) {
			$tab = 'general';
		} else {
			$tab = sanitize_key( $_GET['tab'] );
		}

		/*
		submenu_options = {
			tab => {name => ''
					active => true|false}
		}
		 */

		// TODO: generate this somehow.
		// Maybe with filters, similar as with the settings!
		$submenu_options = array(
			'general'             => array(
				'name'   => 'General',
				'active' => false,
			),
			'events_manager'      => array(
				'name'   => 'Events Manager',
				'active' => false,
			),
			'gatherpress'         => array(
				'name'   => 'Gatherpress',
				'active' => false,
			),
			'the_events_calendar' => array(
				'name'   => 'The Events Calendar',
				'active' => false,
			),
			'vsel'                => array(
				'name'   => 'VS Event',
				'active' => false,
			),
		);

		$submenu_options[ $tab ]['active'] = true;

		$args = array(
			'slug'    => self::SETTINGS_SLUG,
			'options' => $submenu_options,
		);

		switch ( $tab ) {
			case 'general':
				\load_template( ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_DIR . 'templates/settings-general.php', true, $args );
				break;
			default:
				\load_template( ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_DIR . 'templates/settings-extractor.php', true, $args );
				break;
		}
	}
}
