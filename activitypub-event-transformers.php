<?php
/**
 * Plugin Name: ActivityPub Transformers for Events
 * Description: Custom ActivityPub Transformers for Events
 * Plugin URI:  https://event-federation.eu/
 * Version:     1.0.0
 * Author:      André Menrath
 * Author URI:  https://graz.social/@linos
 * Text Domain: activitypub-event-transformers
 *
 * ActivityPub tested up to: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register Tribe Transformer.
 *
 * Include fransformer file and register transformer class.
 *
 * @since 1.0.0
 * @param \Activitypub\Transformer\Transformers_Manager $transformers_manager ActivtiyPub transformers manager.
 * @return void
 */
function register_event_transformers( $transformers_manager ) {
	// if ( ! function_exists( 'is_plugin_active' ) ) {
    //  	require_once __DIR__ . '/wp-admin/includes/plugin.php';
	// }

	// if ( is_plugin_active( 'the-events-calendar/the-events-calendar.php' ) ) {
	// 	require_once __DIR__ . '/activitypub/transformer/tribe.php';
	// 	$transformers_manager->register( new \Tribe() );
	// }

	// if ( is_plugin_active( 'vsel/vsel.php' ) ) {
		require_once __DIR__ . '/activitypub/transformer/vs-event.php';
		$transformers_manager->register( new \VS_Event() );
	// }
}

add_action( 'activitypub_transformers_register', 'register_event_transformers' );
