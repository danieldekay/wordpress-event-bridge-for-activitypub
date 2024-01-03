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
	function( $transformer, $object, $object_class ) {
		/**
		 * VS Event List
		 * @see https://wordpress.org/plugins/very-simple-event-list/
		 */
		if ( $object->post_type === 'event' ) {
			require_once __DIR__ . '/activitypub/transformer/class-vs-event.php';
			return new \VS_Event( $object );
		}
		// Return the default transformer.
		return $transformer;
	},
	10,
	3
);

// /**
//  * Add a filter for http_request_host_is_external
//  *
//  * TODO: Remove this.
//  *
//  * @todo This filter is temporary code needed to do local testing.
//  */
// add_filter( 'http_request_host_is_external', 'custom_http_request_host_is_external', 10, 3 );

// // Your custom callback function
// function custom_http_request_host_is_external( $is_external, $host, $url ) {
// 	$is_external = true;

// 	return $is_external;
// }

// /**
//  * Don't verify ssl certs for testing.
//  *
//  * TODO: Remove this.
//  *
//  * @todo This filter is temporary code needed to do local testing.
//  */
// add_filter( 'https_ssl_verify', 'dont_verify_local_dev_https', 10, 3 );

// function dont_verify_local_dev_https( $url ) {
// 	return false;
// }
