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

/**
 * Class responsible for Event Plugin related admin notices.
 *
 * Notices for guiding to proper configuration of ActivityPub with event plugins.
 *
 * @since 1.0.0
 */
class General_Admin_Notices {
	/**
	 * Warning if the plugin is Active and the ActivityPub plugin is not.
	 */
	public static function do_admin_notice_activitypub_plugin_not_enabled() {
		$activitypub_plugin_url = 'https://wordpress.org/plugins/activitypub/';

		$notice = sprintf(
			/* translators: 1: the name of the event plugin a admin notice is shown. 2: The name of the ActivityPub plugin. */
			_x(
				'For the ActivityPub Event Extensions to work, you will need to install and activate the <a href="%1$s">ActivityPub</a> plugin.',
				'admin notice',
				'activitypub-event-extensions'
			),
			esc_html( $activitypub_plugin_url ),
			admin_url( 'options-general.php?page=activitypub&tab=settings' )
		);
		$allowed_html = array(
			'a' => array(
				'href'  => true,
				'title' => true,
			),
		);
		echo '<div class="notice notice-warning"><p>' . \wp_kses( $notice, $allowed_html ) . '</p></div>';
	}
}
