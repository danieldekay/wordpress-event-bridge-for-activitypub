<?php
/**
 * Plugin Name: ActivityPub Transformers for Events
 * Description: Custom ActivityPub Transformers for Events
 * Plugin URI:  https://event-federation.eu/
 * Version:     1.0.0
 * Author:      André Menrath
 * Author URI:  https://graz.social/@linos
 * Text Domain: activitypub-event-transformers
 * License:     AGPL-3.0-or-later
 *
 * ActivityPub tested up to: 1.3.0
 *
 * @package activitypub-event-transformer
 * @license AGPL-3.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Add the custom transformers for the events of several WordPress event plugins.
 */
add_filter(
	'activitypub_transformer',
	function( $transformer, $wp_object, $object_class ) {
		if ( 'WP_Post' != $object_class) {
			return $transformer;
		}

		/**
		 * VS Event List
		 * @see https://wordpress.org/plugins/very-simple-event-list/
		 */
		// if ( $wp_object->post_type === 'event' ) {
		// 	require_once __DIR__ . '/includes/activitypub/transformer/class-vs-event.php';
		// 	return new \VS_Event( $object );
		// }

		/**
		 * Events manager
		 * @see https://wordpress.org/plugins/events-manager/
		 */
		if ( class_exists( 'EM_Events') && $wp_object->post_type === 'event' ) {
			require_once __DIR__ . '/includes/activitypub/transformer/class-events-manager.php';
			return new \Events_Manager( $wp_object );
		}

		// Return the default transformer.
		return $transformer;
	},
	10,
	3
);

/**
 * Add admin notices for improved usability.
 */
function check_some_other_plugin() {
	if ( is_plugin_active( 'activitypub/activitypub.php' ) ) {
		if ( is_plugin_active( 'very-simple-event-list/vsel.php' ) ) {
			add_action( 'admin_notices', 'vsel_admin_notices' );
		}
	}
}

add_action( 'admin_init', 'check_some_other_plugin' );

function vsel_admin_notices() {
	$is_vsel_edit_page = isset( $_GET['post_type'] ) && $_GET['post_type'] === 'event';
	$is_vsel_settings_page = strpos( $_SERVER['REQUEST_URI'], '/wp-admin/options-general.php?page=vsel' ) !== false;
	$is_vsel_page = $is_vsel_edit_page || $is_vsel_settings_page;
	$vsel_post_type_is_activitypub_enabeld = in_array( 'event', get_option( 'activitypub_support_post_types' ) );
	if ( $is_vsel_page && ! $vsel_post_type_is_activitypub_enabeld ) {
		$vsel_plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/very-simple-event-list/vsel.php' );
		$activitypub_plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/activitypub/activitypub.php' );
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

/**
 * Add a filter for http_request_host_is_external
 *
 * TODO: Remove this.
 *
 * @todo This filter is temporary code needed to do local testing.
 */
add_filter( 'http_request_host_is_external', 'custom_http_request_host_is_external', 10, 3 );

// Your custom callback function
function custom_http_request_host_is_external( $is_external, $host, $url ) {
	$is_external = true;

	return $is_external;
}

/**
 * Don't verify ssl certs for testing.
 *
 * TODO: Remove this.
 *
 * @todo This filter is temporary code needed to do local testing.
 */
add_filter( 'https_ssl_verify', 'dont_verify_local_dev_https', 10, 3 );

function dont_verify_local_dev_https( $url ) {
	return false;
}
