<?php
/**
 * Plugin Name:  ActivityPub Event Extensions
 * Description:  Custom ActivityPub Transformers and Integrations for common Event Plugins.
 * Plugin URI:   https://event-federation.eu/
 * Version:      0.1.0
 * Author:       André Menrath
 * Author URI:   https://graz.social/@linos
 * Text Domain:  activitypub-event-extensions
 * License:      AGPL-3.0-or-later
 * License URI:  https://www.gnu.org/licenses/agpl-3.0.de.html
 * Requires PHP: 8.1
 *
 * Requires at least ActivityPub plugin with version >= 3.2.2. ActivityPub plugin tested up to: 3.2.2.
 *
 * @package Activitypub_Event_Extensions
 * @license AGPL-3.0-or-later
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

define( 'ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_FILE', plugin_dir_path( __FILE__ ) . '/' . basename( __FILE__ ) );
define( 'ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_VERSION', current( get_file_data( __FILE__, array( 'Version' ), 'plugin' ) ) );
define( 'ACTIVITYPUB_EVENT_EXTENSIONS_DOMAIN', 'activitypub-event-extensions' );
define( 'ACTIVITYPUB_EVENT_EXTENSIONS_ACTIVITYPUB_PLUGIN_MIN_VERSION', '3.2.2' );

// Include and register the autoloader class for automatic loading of plugin classes.
require_once ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_DIR . '/includes/class-autoloader.php';
Activitypub_Event_Extensions\Autoloader::register();

// Initialize the plugin.
Activitypub_Event_Extensions\Setup::get_instance();

// BeforeFirstRelease: Remove everything after this after here.

/**
 * Add a filter for http_request_host_is_external
 *
 * BeforeFirstRelease: Remove this for release.
 */
add_filter( 'http_request_host_is_external', 'activitypub_event_extensions_custom_http_request_host_is_external', 10, 3 );

/**
 * Add a filter for http_request_host_is_external
 *
 * BeforeFirstRelease: Remove this for release.
 *
 * @param bool $is_external Whether the request is external.
 */
function activitypub_event_extensions_custom_http_request_host_is_external( $is_external ) {
	$is_external = true;

	return $is_external;
}

/**
 * Don't verify ssl certs for testing.
 *
 * BeforeFirstRelease: Remove this for release.
 */
add_filter( 'https_ssl_verify', 'activitypub_event_extensions_dont_verify_local_dev_https', 10, 3 );

/**
 * BeforeFirstRelease: remove it.
 */
function activitypub_event_extensions_dont_verify_local_dev_https() {
	return false;
}
