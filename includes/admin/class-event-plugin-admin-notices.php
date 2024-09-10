<?php
/**
 * Class responsible for Event Plugin related admin notices.
 *
 * Notices for guiding to proper configuration of ActivityPub with event plugins.
 *
 * @package Activitypub_Event_Extensions
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Activitypub_Event_Extensions\Admin;

use Activitypub_Event_Extensions\Plugins\Event_Plugin;

/**
 * Class responsible for Event Plugin related admin notices.
 *
 * Notices for guiding to proper configuration of ActivityPub with event plugins.
 *
 * @since 1.0.0
 */
class Event_Plugin_Admin_Notices {
	/**
	 * Information about the event plugin.
	 *
	 * @var Event_Plugin
	 */
	protected $event_plugin;

	/**
	 * Adds admin notices to an active supported event plugin.
	 *
	 * @param Event_Plugin $event_plugin Class that has implements functions to handle a certain supported activate event plugin.
	 */
	public function __construct( $event_plugin ) {
		$this->event_plugin = $event_plugin;
		if ( $this->event_post_type_is_not_activitypub_enabled() ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_activitypub_not_enabled_for_post_type' ), 10, 1 );
		}
	}

	/**
	 * Check if ActivityPub is enabled for the custom post type of the event plugin.
	 *
	 * @return bool
	 */
	private function event_post_type_is_not_activitypub_enabled(): bool {
		return ! in_array( $this->event_plugin::get_post_type(), get_option( 'activitypub_support_post_types', array() ), true );
	}

	/**
	 * Display the admin notices for the plugins.
	 *
	 * @return void
	 */
	public function admin_notice_activitypub_not_enabled_for_post_type(): void {
		if ( $this->event_plugin::is_plugin_page() ) {
			$this->do_admin_notice_post_type_not_activitypub_enabled();
		}
	}

	/**
	 * Print admin notice that the current post type is not enabled in the ActivityPub plugin.
	 *
	 * @return void
	 */
	private function do_admin_notice_post_type_not_activitypub_enabled(): void {
		$event_plugin_data       = get_plugin_data( WP_PLUGIN_DIR . '/' . $this->event_plugin::get_plugin_file() );
		$activitypub_plugin_data = get_plugin_data( ACTIVITYPUB_PLUGIN_FILE );
		$notice                  = sprintf(
			/* translators: 1: the name of the event plugin a admin notice is shown. 2: The name of the ActivityPub plugin. */
			_x(
				'You have installed the <i>%1$s</i> plugin, but the event post type of the plugin <i>%2$s</i> is <b>not enabled</b> in the <a href="%3$s">%1$s settings</a>.',
				'admin notice',
				'activitypub-event-extensions'
			),
			esc_html( $activitypub_plugin_data['Name'] ),
			esc_html( $event_plugin_data['Name'] ),
			admin_url( 'options-general.php?page=activitypub&tab=settings' )
		);
		$allowed_html = array(
			'a' => array(
				'href'  => true,
				'title' => true,
			),
			'b' => array(),
			'i' => array(),
		);
		echo '<div class="notice notice-warning is-dismissible"><p>' . \wp_kses( $notice, $allowed_html ) . '</p></div>';
	}
}
