<?php
/**
 * Test file for the Join Handler.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests\ActivityPub\Handler;

use WP_REST_Request;
use WP_REST_Server;

/**
 * Test class for the Event Sources Feature.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\Event_Sources
 */
class Test_Join extends \WP_UnitTestCase {
	/**
	 * Users.
	 *
	 * @var array[] $users
	 */
	public static $users = array(
		'username@example.org' => array(
			'id'                => 'https://example.org/users/username',
			'url'               => 'https://example.org/users/username',
			'inbox'             => 'https://example.org/users/username/inbox',
			'name'              => 'username',
			'preferredUsername' => 'username',
		),
		'jon@example.com'      => array(
			'id'                => 'https://example.com/author/jon',
			'url'               => 'https://example.com/author/jon',
			'inbox'             => 'https://example.com/author/jon/inbox',
			'name'              => 'jon',
			'preferredUsername' => 'jon',
		),
	);

	/**
	 * REST Server.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Set up the test.
	 */
	public function set_up() {
		if ( ! defined( 'GATHERPRESS_CORE_FILE' ) ) {
			self::markTestSkipped( 'GatherPress plugin is not active.' );
		}

		\add_option( 'permalink_structure', '/%postname%/' );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		\Activitypub\Rest\Server::add_hooks();

		// Mock the plugin activation.
		\GatherPress\Core\Setup::get_instance()->activate_gatherpress_plugin( false );

		// Make sure that ActivityPub support is enabled for GatherPress.
		$aec = \Event_Bridge_For_ActivityPub\Setup::get_instance();
		$aec->activate_activitypub_support_for_active_event_plugins();

		add_filter( 'pre_get_remote_metadata_by_actor', array( get_called_class(), 'pre_get_remote_metadata_by_actor' ), 10, 2 );

		// Add event source (ActivityPub follower).
		_delete_all_posts();

		\update_option( 'activitypub_actor_mode', ACTIVITYPUB_BLOG_MODE );
	}

	/**
	 * Tear down the test.
	 */
	public function tear_down() {
		remove_filter( 'pre_get_remote_metadata_by_actor', array( get_called_class(), 'pre_get_remote_metadata_by_actor' ) );
		\delete_option( 'permalink_structure' );
		parent::tear_down();
	}

	/**
	 * Test handling of incoming Join.
	 */
	public function test_handle_via_sending_ignore() {
		// Mock GatherPress Event.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Unit Test Event',
				'post_type'    => 'gatherpress_event',
				'post_content' => 'Unit Test description.',
				'post_status'  => 'publish',
			)
		);
		$event   = new \GatherPress\Core\Event( $post_id );
		$params  = array(
			'datetime_start' => '+10 days 15:00:00',
			'datetime_end'   => '+10 days 16:00:00',
			'timezone'       => \wp_timezone_string(),
		);
		$event->save_datetimes( $params );

		$event_activitypub_id = \Activitypub\Transformer\Factory::get_transformer( $event->event )->to_object()->get_id();

		\add_filter( 'activitypub_defer_signature_verification', '__return_true' );

		$join_object = array(
			'id'     => 'https://example.org/users/username/activities/1',
			'type'   => 'Join',
			'actor'  => 'https://example.org/users/username',
			'object' => $event_activitypub_id,
		);

		$request = new WP_REST_Request( 'POST', '/activitypub/1.0/users/0/inbox' );
		$request->set_header( 'Content-Type', 'application/activity+json' );
		$request->set_body( \wp_json_encode( $join_object ) );

		// Be ready to catch response.
		$pre_http_request = new \MockAction();
		\add_filter( 'pre_http_request', array( $pre_http_request, 'filter' ), 10, 3 );

		// Dispatch the request.
		$response = \rest_do_request( $request );
		$this->assertEquals( 202, $response->get_status() );

		// Check our response.
		$all_args        = $pre_http_request->get_args();
		$first_call_args = $all_args[0];

		$this->assertSame( 1, $pre_http_request->get_call_count() );

		$json = json_decode( $first_call_args[1]['body'], true );
		$this->assertEquals( 'Ignore', $json['type'] );
		$this->assertEquals( $json['object'], $join_object['id'] );
		$this->assertEquals( $json['to'], $join_object['actor'] );

		\remove_filter( 'pre_http_request', array( $pre_http_request, 'filter' ), 10 );
		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}

	/**
	 * Test handling of incoming Join.
	 */
	public function test_not_sending_ignore_on_invalid_join() {
		$event_activitypub_id = 'https://some.domain/object';

		\add_filter( 'activitypub_defer_signature_verification', '__return_true' );

		$join_object = array(
			'id'     => 'https://example.org/useros/username/activities/1',
			'type'   => 'Join',
			'actor'  => 'https://example.org/users/username',
			'object' => $event_activitypub_id,
		);

		$request = new WP_REST_Request( 'POST', '/activitypub/1.0/users/0/inbox' );
		$request->set_header( 'Content-Type', 'application/activity+json' );
		$request->set_body( \wp_json_encode( $join_object ) );
		// Dispatch the request.
		$response = \rest_do_request( $request );
		$this->assertEquals( 202, $response->get_status() );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}

	/**
	 * Filters remote metadata by actor.
	 *
	 * @param array|bool $pre The metadata for the given URL.
	 * @param string     $actor The URL of the actor.
	 * @return array|bool
	 */
	public static function pre_get_remote_metadata_by_actor( $pre, $actor ) {
		if ( isset( self::$users[ $actor ] ) ) {
			return self::$users[ $actor ];
		}
		foreach ( self::$users as $data ) {
			if ( $data['url'] === $actor ) {
				return $data;
			}
		}
		return $pre;
	}
}
