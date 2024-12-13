<?php
/**
 * Health_Check class.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\Admin;

use Activitypub\Transformer\Factory as Transformer_Factory;
use Event_Bridge_For_ActivityPub\Integrations\Event_Plugin;
use Event_Bridge_For_ActivityPub\Setup;
use WP_Query;

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
		$tests['direct']['event_bridge_for_activitypub_test'] = array(
			'label' => __( 'ActivityPub Event Transformer Test', 'event-bridge-for-activitypub' ),
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
			'label'       => \__( 'Transformation of Events to a valid ActivityStreams representation.', 'event-bridge-for-activitypub' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => \__( 'Event Bridge for ActivityPub', 'event-bridge-for-activitypub' ),
				'color' => 'green',
			),
			'description' => \sprintf(
				'<p>%s</p>',
				\__( 'The transformation of your most recent events was successful.', 'event-bridge-for-activitypub' )
			),
			'actions'     => '',
			'test'        => 'test_event_transformation',
		);

		$check = self::transform_most_recent_event_posts();

		if ( true === $check ) {
			return $result;
		}

		$result['status']         = 'critical';
		$result['label']          = \__( 'One or more of your most recent events failed to transform to ActivityPub', 'event-bridge-for-activitypub' );
		$result['badge']['color'] = 'red';
		$result['description']    = \sprintf(
			'<p>%s</p>',
			$check->get_error_message()
		);

		return $result;
	}

	/**
	 * Test if right transformer gets applied.
	 *
	 * @param Event_Plugin $event_plugin  The event plugin definition.
	 *
	 * @return bool True if the check passed.
	 */
	public static function test_if_event_transformer_is_used( $event_plugin ) {
		// Get a (random) event post.
		$event_posts = self::get_most_recent_event_posts( $event_plugin->get_post_type(), 1 );

		// If no post is found, we can not do this test.
		if ( ! $event_posts || is_wp_error( $event_posts ) || empty( $event_posts ) ) {
			return true;
		}

		// Call the transformer Factory.
		$transformer = Transformer_Factory::get_transformer( $event_posts[0] );
		// Check that we got the right transformer.
		$desired_transformer_class = $event_plugin::get_activitypub_event_transformer_class();
		if ( $transformer instanceof $desired_transformer_class ) {
			return true;
		}
		return false;
	}

	/**
	 * Retrieves the most recently published event posts of a certain event post type.
	 *
	 * @param ?string $event_post_type  The post type of the events.
	 * @param ?int    $number_of_posts  The maximum number of events to return.
	 *
	 * @return WP_Post[]|false         Array of event posts, or false if none are found.
	 */
	public static function get_most_recent_event_posts( $event_post_type = null, $number_of_posts = 5 ) {
		if ( ! $event_post_type ) {
			$active_event_plugins = Setup::get_instance()->get_active_event_plugins();
			$active_event_plugin  = reset( $active_event_plugins );
			$event_post_type      = $active_event_plugin->get_post_type();
		}

		$args = array(
			'numberposts'      => $number_of_posts,
			'category'         => 0,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'include'          => array(),
			'exclude'          => array(),
			'meta_key'         => '',
			'meta_value'       => '',
			'post_type'        => $event_post_type,
			'suppress_filters' => true,
		);

		$query = new WP_Query();
		return $query->query( $args );
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
			$event_plugin_file    = $active_event_plugin->get_relative_plugin_file();
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
		$info['event_bridge_for_activitypub'] = array(
			'label'  => __( 'Event Bridge for ActivityPub', 'event-bridge-for-activitypub' ),
			'fields' => array(
				'plugin_version'       => array(
					'label'   => __( 'Plugin Version', 'event-bridge-for-activitypub' ),
					'value'   => EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_VERSION,
					'private' => true,
				),
				'active_event_plugins' => self::get_info_about_active_event_plugins(),
			),
		);

		return $info;
	}
}
