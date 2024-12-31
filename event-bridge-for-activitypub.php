<?php
/**
 * Plugin Name:  Event Bridge for ActivityPub
 * Description:  Integrating popular event plugins with the ActivityPub plugin.
 * Plugin URI:   https://event-federation.eu/
 * Version:      0.3.4
 * Author:       André Menrath
 * Author URI:   https://graz.social/@linos
 * Text Domain:  event-bridge-for-activitypub
 * License:      AGPL-3.0-or-later
 * License URI:  https://www.gnu.org/licenses/agpl-3.0.html
 * Requires PHP: 7.4
 *
 * Requires at least ActivityPub plugin with version >= 3.2.2. ActivityPub plugin tested up to: 4.3.0.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

define( 'EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE', plugin_dir_path( __FILE__ ) . '/' . basename( __FILE__ ) );
define( 'EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_VERSION', current( get_file_data( __FILE__, array( 'Version' ), 'plugin' ) ) );
define( 'EVENT_BRIDGE_FOR_ACTIVITYPUB_DOMAIN', 'event-bridge-for-activitypub' );
define( 'EVENT_BRIDGE_FOR_ACTIVITYPUB_ACTIVITYPUB_PLUGIN_MIN_VERSION', '3.2.2' );
define( 'EVENT_BRIDGE_FOR_ACTIVITYPUB_CUSTOM_SUMMARY', "<ul>\n  <li>[ap_start_time]</li>\n  <li>[ap_end_time]</li>\n  <li>[ap_location]</li>\n</ul>\n[ap_hashcats] [ap_hashtags]" );
define( 'EVENT_BRIDGE_FOR_ACTIVITYPUB_DEFAULT_SUMMARY_TYPE', 'preset' );

// Include and register the autoloader class for automatic loading of plugin classes.
require_once EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . '/includes/class-autoloader.php';
Event_Bridge_For_ActivityPub\Autoloader::register();

// Initialize the plugin.
Event_Bridge_For_ActivityPub\Setup::get_instance();
