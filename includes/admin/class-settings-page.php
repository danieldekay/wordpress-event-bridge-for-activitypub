<?php
/**
 * Class responsible for Event Plugin related admin notices.
 *
 * Notices for guiding to proper configuration of ActivityPub with event plugins.
 *
 * @package Activitypub_Event_Extensions
 * @since 1.0.0
 */

namespace Activitypub_Event_Extensions\Admin;

use Activitypub_Event_Extensions\Setup;

/**
 * Class responsible for Event Plugin related admin notices.
 *
 * Notices for guiding to proper configuration of ActivityPub with event plugins.
 *
 * @since 1.0.0
 */
class Settings_Page {

	/*
	 * TODO:
     *  - [ ] create settings page
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

	const static = 'Activitypub_Event_Extensions\Admin\Settings_Page';
	const settings_slug = 'activitypub-events';

	/**
	 * Warning if the plugin is Active and the ActivityPub plugin is not.
	 */
	public static function admin_menu() {
		\add_options_page(
			'Activitypub Event Extension',
			'Activitypub Events',
			'manage_options',
			self::settings_slug,
			array( self::static, 'settings_page' )
		);
	}

	/**
	 * Adds Link to the settings page in the plugin page.
	 * It's called via apply_filter('plugin_action_links_' . PLUGIN_NAME).
	 *
	 * @param links already added links
	 * @return array original links but with link to setting page added
	 */
	public static function settings_link( $links ) {
		return array_merge(
			$links,
			array(
				'<a href="' . admin_url( 'options-general.php?page=' . self::settings_slug ) . '">Settings</a>',
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

		// todo generate this somehow
		// maybe with filters, similar as with the settings
		$submenu_options = array(
			'general'             => array(
				'name' => 'General',
				'active' => false
			),
			'events_manager'      => array(
				'name' => 'Events Manager',
				'active' => false,
			),
			'gatherpress'         => array(
				'name' => 'Gatherpress',
				'active' => false,
			),
			'the_events_calendar' => array(
				'name' => 'The Events Calendar',
				'active' => false,
			),
			'vsel'                => array(
				'name' => 'VS Event',
				'active' => false,
			),
		);

		$submenu_options[$tab]['active'] = true;

		$args = array(
			'slug' => settings_slug,
			'options' => $submenu_options,
		);

		switch ( $tab ) {
			case 'general':
				\load_template( ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_DIR . 'templates/settings-general.php' , true, $args );
				break;
			default:
				\load_template( ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_DIR . 'templates/settings-extractor.php', true, $args );
				break;
		}

	}

}
