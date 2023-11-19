<?php
/**
 * Plugin Name: ActivityPub Transformer for The Events Calendar
 * Description: ActivityPub Transformer for The Events Calendar.
 * Plugin URI:  https://event-federation.eu/
 * Version:     1.0.0
 * Author:      André Menrath
 * Author URI:  https://graz.social/@linos
 * Text Domain: activitypub-tribe
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
 * @param \ActivityPUb\Transformers_Manager $transformers_manager ActivtiyPub transformers manager.
 * @return void
 */
function register_tribe_transformer( $transformers_manager ) {

	require_once( __DIR__ . '/transformers/tribe-transformer.php' );

	$transformers_manager->register( new \Activitypub_Tribe_Transformer() );

}
add_action( 'activitypub/transformers/register', 'register_tribe_transformer' );
