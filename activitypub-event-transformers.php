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
 *
 * @package activitypub-event-transformer
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
 * @param \Activitypub\Transformer\Transformer_Factory $transformers_manager ActivtiyPub transformers manager.
 * @return void
 */
function register_event_transformers( $transformers_manager ) {
	// require_once __DIR__ . '/activitypub/transformer/class-tribe.php';
	// $transformers_manager->register( new \Tribe() );

	require_once __DIR__ . '/activitypub/transformer/class-vs-event.php';
	$transformers_manager->register( new \VS_Event() );
}

add_filter(
	'activitypub_json_context',
	function ( $context ) {
		$context[2]['commentsEnabled'] = array(
			'@id'   => 'pt:commentsEnabled',
			'@type' => 'sc:Boolean',
		);
		return $context;
	},
	10,
	2
);

add_action( 'activitypub_transformers_register', 'register_event_transformers' );
