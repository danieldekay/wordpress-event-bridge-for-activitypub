<?php
/**
 * Plugin Name: ActivityPub Event Extensions
 * Description: Custom ActivityPub Transformers and Integrations for common Event Plugins.
 * Plugin URI:  https://event-federation.eu/
 * Version:     0.1.0
 * Author:      André Menrath
 * Author URI:  https://graz.social/@linos
 * Text Domain: activitypub-event-extensions
 * License:     AGPL-3.0-or-later
 *
 * ActivityPub tested up to: 2.4.0
 *
 * @package activitypub-event-extensions
 * @license AGPL-3.0-or-later
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

define( 'ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_FILE', plugin_dir_path( __FILE__ ) . '/' . basename( __FILE__ ) );
define( 'ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_VERSION', current( get_file_data( __FILE__, array( 'Version' ), 'plugin' ) ) );

// Include and register the autoloader class for automatic loading of plugin classes.
require_once ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_DIR . '/includes/class-autoloader.php';
Activitypub_Event_Extensions\Autoloader::register();

// Initialize the plugin.
Activitypub_Event_Extensions\Setup::get_instance();






// For local development purposes: TODO. Remove everything after here.

/**
 * Add a filter for http_request_host_is_external
 *
 * TODO: Remove this for release.
 *
 * @todo This filter is temporary code needed to do local testing.
 */
add_filter( 'http_request_host_is_external', 'custom_http_request_host_is_external', 10, 3 );

/**
 * Add a filter for http_request_host_is_external
 *
 * TODO: Remove this for release.
 *
 * @todo This filter is temporary code needed to do local testing.
 */
function custom_http_request_host_is_external( $is_external, $host, $url ) {
	$is_external = true;

	return $is_external;
}

/**
 * Don't verify ssl certs for testing.
 *
 * TODO: Remove this for release.
 *
 * @todo This filter is temporary code needed to do local testing.
 */
add_filter( 'https_ssl_verify', 'dont_verify_local_dev_https', 10, 3 );

function dont_verify_local_dev_https( $url ) {
	return false;
}
