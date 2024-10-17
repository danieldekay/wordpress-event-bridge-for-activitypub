<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package ActivityPub_Event_Bridge
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested and its integrations.
 */
function _manually_load_plugin() {
	$plugin_dir = ABSPATH . '/wp-content/plugins/';

	// Always manually load the ActivityPub plugin.
	require_once $plugin_dir . 'activitypub/activitypub.php';

	// Capture the --filter argument.
	$activitypub_event_extension_integration_filter = null;
	foreach ( $_SERVER['argv'] as $arg ) {
		if ( strpos( $arg, '--filter=' ) === 0 ) {
			$activitypub_event_extension_integration_filter = substr( $arg, strlen( '--filter=' ) );
			break;
		}
	}

	// Hot fixes for eventin.
	update_option( 'purchase_history_table_structure_migration_done', true );
	update_option( 'etn_wizard', 'active' );

	$plugin_file = null;
	// See if we want to run integration tests for a specific event-plugin.
	switch ( $activitypub_event_extension_integration_filter ) {
		case 'the_events_calendar':
			$plugin_file = 'the-events-calendar/the-events-calendar.php';
			break;
		case 'vs_event_list':
			$plugin_file = 'very-simple-event-list/vsel.php';
			break;
		case 'events_manager':
			$plugin_file = 'events-manager/events-manager.php';
			break;
		case 'eventin':
			$plugin_file = 'wp-event-solution/eventin.php';
			break;
		case 'modern_events_calendar_lite':
			$plugin_file = 'modern-events-calendar-lite/modern-events-calendar-lite.php';
			break;
		case 'gatherpress':
			$plugin_file = 'gatherpress/gatherpress.php';
			break;
		case 'wp_event_manager':
			$plugin_file = 'wp-event-manager/wp-event-manager.php';
			break;
	}

	if ( $plugin_file ) {
		// Manually load the event plugin.
		require_once $plugin_dir . $plugin_file;
		update_option( 'purchase_history_table_structure_migration_done', true );
		$current   = get_option( 'active_plugins', array() );
		$current[] = $plugin_file;
		sort( $current );
		update_option( 'active_plugins', $current );
	}

	// Hot fix that allows using Events Manager within unit tests, because the em_init() is later not run as admin.
	if ( 'events_manager' === $activitypub_event_extension_integration_filter ) {
		require_once $plugin_dir . 'events-manager/em-install.php';
		em_create_events_table();
		em_create_events_meta_table();
		em_create_locations_table();
	}

	// At last manually load our WordPress plugin.
	require dirname( __DIR__ ) . '/activitypub-event-bridge.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
