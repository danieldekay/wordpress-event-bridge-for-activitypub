<?php
/**
 * Class responsible for general admin notices.
 *
 * Notices for guiding to proper configuration of this plugin.
 *
 * @package Activitypub_Event_Extensions
 * @since 1.0.0
 */

namespace Activitypub_Event_Extensions\Admin;

/**
 * Class responsible for general admin notices.
 *
 * Notices for guiding to proper configuration of this plugin.
 *   - ActivityPub plugin not installed and activated
 *   - No supported Event Plugin installed and activated
 *
 * @since 1.0.0
 */
class General_Admin_Notices {
	/**
	 * URL of the ActivityPub plugin. Needed when the ActivityPub plugin is not installed.
	 */
	const ACTIVITYPUB_PLUGIN_URL = 'https://wordpress.org/plugins/activitypub';

	const ACTIVITYPUB_EVENT_EXTENSIONS_SUPPORTED_EVENT_PLUGINS_URL = 'https://code.event-federation.eu/Event-Federation/wordpress-activitypub-event-extensions#events-plugin-that-will-be-supported-at-first';

	/**
	 * Allowed HTML for admin notices.
	 *
	 * @var array
	 */
	const ALLOWED_HTML = array(
		'a' => array(
			'href'  => true,
			'title' => true,
		),
		'br',
		'i',
	);

	/**
	 * Admin notice when the ActivityPub plugin is not enabled.
	 *
	 * @return string
	 */
	public static function get_admin_notice_activitypub_plugin_not_enabled(): string {
		return sprintf(
			/* translators: 1: the name of the event plugin a admin notice is shown. 2: The name of the ActivityPub plugin. */
			_x(
				'For the ActivityPub Event Extensions to work, you will need to install and activate the <a href="%1$s">ActivityPub</a> plugin.',
				'admin notice',
				'activitypub-event-extensions'
			),
			esc_html( self::ACTIVITYPUB_PLUGIN_URL ),
			admin_url( 'options-general.php?page=activitypub&tab=settings' )
		);
	}

	/**
	 * Warning that no supported event plugin can be found.
	 *
	 * @return string
	 */
	public static function get_admin_notice_no_supported_event_plugin_active(): string {
		return sprintf(
			/* translators: 1: the name of the event plugin a admin notice is shown. 2: The name of the ActivityPub plugin. */
			_x(
				'The Plugin <i>ActivityPub Event Extensions</i> is of no use, because you do not have installed and activated a supported Event Plugin.
				<br> For a list of supported Event Plugins see  <a href="%1$s">here</a>.',
				'admin notice',
				'activitypub-event-extensions'
			),
			esc_html( self::ACTIVITYPUB_EVENT_EXTENSIONS_SUPPORTED_EVENT_PLUGINS_URL ),
			admin_url( 'options-general.php?page=activitypub&tab=settings' )
		);
	}

	/**
	 * Warning if the plugin is Active and the ActivityPub plugin is not.
	 *
	 * @return void
	 */
	public static function activitypub_plugin_not_enabled() {
		$notice = self::get_admin_notice_activitypub_plugin_not_enabled();
		echo '<div class="notice notice-warning"><p>' . \wp_kses( $notice, self::ALLOWED_HTML ) . '</p></div>';
	}

	/**
	 * Warning when no supported Even Plugin is installed and active.
	 *
	 * @return void
	 */
	public static function no_supported_event_plugin_active() {
		$notice = self::get_admin_notice_no_supported_event_plugin_active();
		echo '<div class="notice notice-warning"><p>' . \wp_kses( $notice, self::ALLOWED_HTML ) . '</p></div>';
	}
}
