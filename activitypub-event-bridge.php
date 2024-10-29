<?php
/**
 * Plugin Name:  ActivityPub Event Bridge
 * Description:  Integrating popular event plugins with the ActivityPub plugin.
 * Plugin URI:   https://event-federation.eu/
 * Version:      0.2.0
 * Author:       André Menrath
 * Author URI:   https://graz.social/@linos
 * Text Domain:  activitypub-event-bridge
 * License:      AGPL-3.0-or-later
 * License URI:  https://www.gnu.org/licenses/agpl-3.0.html
 * Requires PHP: 8.1
 *
 * Requires at least ActivityPub plugin with version >= 3.2.2. ActivityPub plugin tested up to: 4.0.1.
 *
 * @package ActivityPub_Event_Bridge
 * @license AGPL-3.0-or-later
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

define( 'ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_FILE', plugin_dir_path( __FILE__ ) . '/' . basename( __FILE__ ) );
define( 'ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_VERSION', current( get_file_data( __FILE__, array( 'Version' ), 'plugin' ) ) );
define( 'ACTIVITYPUB_EVENT_BRIDGE_DOMAIN', 'activitypub-event-bridge' );
define( 'ACTIVITYPUB_EVENT_BRIDGE_ACTIVITYPUB_PLUGIN_MIN_VERSION', '3.2.2' );

// Include and register the autoloader class for automatic loading of plugin classes.
require_once ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_DIR . '/includes/class-autoloader.php';
ActivityPub_Event_Bridge\Autoloader::register();

// Initialize the plugin.
ActivityPub_Event_Bridge\Setup::get_instance();
