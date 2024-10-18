<?php
/**
 * Health_Check class.
 *
 * @package Activitypub_Event_Bridge
 */

namespace ActivityPub_Event_Bridge\Admin;

use ActivityPub_Event_Bridge\Setup;
use WP_Error;

/**
 * ActivityPub Health_Check Class.
 */
class Health_Check {
	/**
	 * Initialize health checks.
	 */
	public static function init() {
		\add_filter( 'site_status_tests', array( self::class, 'add_tests' ) );
		\add_filter( 'debug_information', array( self::class, 'add_debug_information' ) );
	}

	/**
	 * Add tests to the Site Health Check.
	 *
	 * @param array $tests The test array.
	 *
	 * @return array The filtered test array.
	 */
	public static function add_tests( $tests ) {
		$tests['direct']['activitypub_event_bridge_test'] = array(
			'label' => __( 'ActivityPub Event Transformer Test', 'activitypub-event-bridge' ),
			'test'  => array( self::class, 'test_event_transformation' ),
		);

		return $tests;
	}

	/**
	 * The the transformation of the most recent event posts.
	 *
	 * @return array
	 */
	public static function test_event_transformation() {
		$result = array(
			'label'       => \__( 'Transformation of Events to a valid ActivityStreams representation.', 'activitypub' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => \__( 'ActivityPub Event Bridge', 'activitypub-event-bridge' ),
				'color' => 'green',
			),
			'description' => \sprintf(
				'<p>%s</p>',
				\__( 'The transformation of your most recent events was successful.', 'activitypub-event-bridge' )
			),
			'actions'     => '',
			'test'        => 'test_event_transformation',
		);

		$check = self::transform_most_recent_event_posts();

		if ( true === $check ) {
			return $result;
		}

		$result['status']         = 'critical';
		$result['label']          = \__( 'One or more of your most recent events failed to transform to ActivityPub', 'activitypub-event-bridge' );
		$result['badge']['color'] = 'red';
		$result['description']    = \sprintf(
			'<p>%s</p>',
			$check->get_error_message()
		);

		return $result;
	}

	/**
	 * Transform the most recent event posts.
	 */
	public static function transform_most_recent_event_posts() {
		return true;
	}

	/**
	 * Retrieves information like name and version from active event plugins.
	 */
	private static function get_info_about_active_event_plugins() {
		$active_event_plugins = Setup::get_instance()->get_active_event_plugins();
		$info                 = array();
		foreach ( $active_event_plugins as $active_event_plugin ) {
			$event_plugin_file    = $active_event_plugin->get_plugin_file();
			$event_plugin_data    = \get_plugin_data( $event_plugin_file );
			$event_plugin_name    = isset( $event_plugin_data['Plugin Name'] ) ? $event_plugin_data['Plugin Name'] : 'Name not found';
			$event_plugin_version = isset( $event_plugin_version['Plugin Version'] ) ? $event_plugin_version['Plugin Version'] : 'Version not found';

			$info[] = array(
				'event_plugin_name'    => $event_plugin_name,
				'event_plugin_version' => $event_plugin_version,
				'event_plugin_file'    => $event_plugin_file,
			);
		}
	}

	/**
	 * Static function for generating site debug data when required.
	 *
	 * @param  array $info  The debug information to be added to the core information page.
	 * @return array        The extended information.
	 */
	public static function add_debug_information( $info ) {
		$info['activitypub_event_bridge'] = array(
			'label'  => __( 'ActivityPub Event Bridge', 'activitypub-event-bridge' ),
			'fields' => array(
				'plugin_version'       => array(
					'label'   => __( 'Plugin Version', 'activitypub' ),
					'value'   => ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_VERSION,
					'private' => true,
				),
				'active_event_plugins' => self::get_info_about_active_event_plugins(),
			),
		);

		return $info;
	}
}
