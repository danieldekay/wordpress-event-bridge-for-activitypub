<?php
/**
 * Admin Notices for guiding to proper configuration of ActivityPub with event plugins.
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

// TODO: Modularize after we know what we want.
class Admin_Notices {
	const VSEL_PLUGIN_FILE = 'very-simple-event-list/vsel.php';
	const VSEL_POST_TYPE = 'event';
	const EVENTS_MANAGER_PLUGIN_FILE = 'events-manager/events-manager.php';
	const EVENTS_MANAGER_POTS_TYPE = 'event';
    const TRIBE_POST_TYPE = 'tribe_events';
    const TRIBE_PLUGIN_FILE = 'the-events-calendar/the-events-calendar.php';
	const ACTIVITYPUB_PLUGIN_FILE = 'activitypub/activitypub.php';

	/**
	 * Add actions and filters.
	 */
	public function __construct() {
		add_action( 'admin_init', array( self::class, 'check_for_admin_notices' ) );
	}

	/**
	 * Check the conditions for admin notices
	 *
	 * These should mainly improve usability.
	 */
	public static function check_for_admin_notices() {

		if ( is_admin() && is_plugin_active( self::ACTIVITYPUB_PLUGIN_FILE ) ) {
			// Check for VSEL
			if ( is_plugin_active( self::VSEL_PLUGIN_FILE ) && self::post_type_is_not_activitypub_enabled( self::VSEL_POST_TYPE ) ) {
				add_action( 'admin_notices', array( self::class, 'vsel_admin_notices' ) );
			}
            // Check for Events Manager
			if ( is_plugin_active( self::EVENTS_MANAGER_PLUGIN_FILE ) && self::post_type_is_not_activitypub_enabled( self::EVENTS_MANAGER_POTS_TYPE ) ) {
				add_action( 'admin_notices', array( self::class, 'events_manager_admin_notices' ) );
			}
            // Check for The Events Calendar
            if ( is_plugin_active( self::TRIBE_PLUGIN_FILE ) && self::post_type_is_not_activitypub_enabled( self::TRIBE_POST_TYPE ) ) {
				add_action( 'admin_notices', array( self::class, 'the_events_calendar_admin_notices' ) );
			}
		}
	}

	/**
	 * Check if ActivityPub is enabled for the custom post type of the event plugin.
	 *
	 * @param string $post_type The post type of the event plugin.
	 * @return bool
	 */
	private static function post_type_is_not_activitypub_enabled( $post_type ) {
		return ! in_array( $post_type, get_option( 'activitypub_support_post_types' ) );
	}

	/**
	 * Check whether to do any admin notices for VSEL
	 */
	public static function vsel_admin_notices() {
		$is_vsel_edit_page = isset( $_GET['post_type'] ) && $_GET['post_type'] === self::VSEL_POST_TYPE;
		$is_vsel_settings_page = strpos( $_SERVER['REQUEST_URI'], '/wp-admin/options-general.php?page=vsel' ) !== false;
		$is_vsel_page = $is_vsel_edit_page || $is_vsel_settings_page;
		if ( $is_vsel_page ) {
			self::do_admin_notice_post_type_not_activitypub_enabled( self::VSEL_PLUGIN_FILE );
		}
	}

    /**
	 * Check whether to do any admin notices for Events Manager
	 */
	public static function events_manager_admin_notices() {
		$is_events_manager_page = isset( $_GET['post_type'] ) && $_GET['post_type'] === self::EVENTS_MANAGER_POTS_TYPE;
		if ( $is_events_manager_page ) {
			self::do_admin_notice_post_type_not_activitypub_enabled( self::EVENTS_MANAGER_PLUGIN_FILE );
		}
	}

    /**
	 * Check whether to do any admin notices for The Events Calendar
	 */
	public static function the_events_calendar_admin_notices() {
		$is_events_manager_page = isset( $_GET['post_type'] ) && $_GET['post_type'] === self::TRIBE_POST_TYPE;
		if ( $is_events_manager_page ) {
			self::do_admin_notice_post_type_not_activitypub_enabled( self::TRIBE_PLUGIN_FILE );
		}
	}

	/**
	 * Print admin notice that the current post type is not enabled in the ActivityPub plugin.
	 */
	private static function do_admin_notice_post_type_not_activitypub_enabled( $event_plugin_file ) {
		$vsel_plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $event_plugin_file );
		$activitypub_plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . self::ACTIVITYPUB_PLUGIN_FILE );
		$notice = sprintf(
			_x(
				'You have installed the %1$s plugin, but the event post type of %2$s is not enabled in the <a href="%3$s">%1$s settings</a>.',
				'admin notice',
				'your-text-domain'
			),
			$activitypub_plugin_data['Name'],
			$vsel_plugin_data['Name'],
			admin_url( 'options-general.php?page=activitypub&tab=settings' )
		);
		wp_admin_notice(
			$notice,
			array(
				'type' => 'warning',
				'dismissible' => true,
			)
		);
	}
}
