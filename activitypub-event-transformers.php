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
 * ActivityPub tested up to: 1.2.0
 *
 * @package activitypub-event-transformer
 * @license AGPL-3.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register Event Transformers.
 *
 * Include fransformer class file and register the transformer class.
 *
 * @since 1.0.0
 * @param \Activitypub\Transformer\Factory $factory ActivtiyPub transformers factory.
 * @return void
 */
function register_event_transformers( $transformers_factory ) {
	require_once __DIR__ . '/activitypub/transformer/class-tribe.php';
	$transformers_factory->register( new \Tribe() );

	require_once __DIR__ . '/activitypub/transformer/class-vs-event.php';
	$transformers_factory->register( new \VS_Event() );
}

add_action( 'activitypub_transformers_register', 'register_event_transformers' );

// Filter be object class
add_filter( 'activitypub_transformer', function( $transformer, $object, $object_class ) {
	if ( $object->post_type === 'event' ) {
		require_once __DIR__ . '/activitypub/transformer/class-vs-event.php';
        return new \VS_Event( $object );
    }
    return $transformer;
}, 10, 3 );

// TODO Below here is temporary code needed to do local testing atm.

// Add a filter for http_request_host_is_external
add_filter( 'http_request_host_is_external', 'custom_http_request_host_is_external', 10, 3 );

// Your custom callback function
function custom_http_request_host_is_external( $is_external, $host, $url ) {
	$is_external = true;

	return $is_external;
}

// add_filter( 'rest_request_before_callbacks', 'change_relay_actor_to_blog_actor', 10, 3 );

// function change_relay_actor_to_blog_actor( $response, $handler, $request ) {

// }

// add_filters( 'https_ssl_verify', 'dont_verify_local_dev_https', 10, 3);


// function dont_verify_local_dev_https($url) {
// return false;
// }
