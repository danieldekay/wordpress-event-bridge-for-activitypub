<?php
/**
 * Plugin Name: ActivityPub Event Extensions
 * Description: Custom ActivityPub Transformers and Integretions for common Event Plugins
 * Plugin URI:  https://event-federation.eu/
 * Version:     1.0.0
 * Author:      André Menrath
 * Author URI:  https://graz.social/@linos
 * Text Domain: activitypub-event-extensions
 * License:     AGPL-3.0-or-later
 *
 * ActivityPub tested up to: 2.2.0
 * 
 * @package activitypub-event-extensions
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
		if ( 'WP_Post' != $object_class ) {
			return $transformer;
		}

		/**
		 * VS Event List
		 * @see https://wordpress.org/plugins/very-simple-event-list/
		 */
		if ( class_exists( 'vsel_widget' ) && $wp_object->post_type === 'event' ) {
			require_once __DIR__ . '/includes/activitypub/transformer/class-vs-event.php';
			return new \VS_Event( $wp_object );
		}

		/**
		 * Events manager
		 * @see https://wordpress.org/plugins/events-manager/
		 */
		if ( class_exists( 'EM_Events' ) && $wp_object->post_type === 'event' ) {
			require_once __DIR__ . '/includes/activitypub/transformer/class-events-manager.php';
			return new \Events_Manager( $wp_object );
		}

		/**
		 * Events manager
		 * @see https://wordpress.org/plugins/events-manager/
		 */
		if ( class_exists( 'GatherPress\Core\Event' ) && $wp_object->post_type === 'gp_event' ) {
			require_once __DIR__ . '/includes/activitypub/transformer/class-gatherpress.php';
			return new \GatherPress( $wp_object );
		}

		// Return the default transformer.

		return $transformer;
	},
	10,
	3
);


/**
 * Activate the plugin.
 */
function activitypub_event_extensions_activate() { 
	// Don't allow plugin activation, when the ActivityPub plugin is not activated yet.
	if( ! class_exists( 'ActivtiyPub' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __( 'Please install and Activate ActivityPub.', 'activitypub-event-extensions' ), 'Plugin dependency check', array( 'back_link' => true ) );
    }
}

register_activation_hook( __FILE__, 'activitypub_event_extensions_activate' );


// TODO:
// require_once __DIR__ . '/admin/class-admin-notices.php';
// new \Admin_Notices();

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
