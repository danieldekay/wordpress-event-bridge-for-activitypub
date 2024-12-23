<?php
/**
 * Test file for the Transmogrifier (import of ActivityPub Event objects) of GatherPress.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests\Integrations;

use Event_Bridge_For_ActivityPub\Integrations\GatherPress;

use WP_REST_Request;
use WP_REST_Server;

/**
 * Test class for the Transmogrifier (import of ActivityPub Event objects) of GatherPress.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\Integrations\GatherPress
 */
class Test_GatherPress extends \WP_UnitTestCase {
	const FOLLOWED_ACTOR = array(
		'id'      => 'https://remote.example/@organizer',
		'type'    => 'Person',
		'inbox'   => 'https://remote.example/@organizer/inbox',
		'outbox'  => 'https://remote.example/@organizer/outbox',
		'name'    => 'The Organizer',
		'summary' => 'Just a random organizer of events in the Fediverse',
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

		// Make sure that ActivityPub support is enabled for The Events Calendar.
		$aec = \Event_Bridge_For_ActivityPub\Setup::get_instance();
		$aec->activate_activitypub_support_for_active_event_plugins();

		// Add event source (ActivityPub follower).
		_delete_all_posts();
		\Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source::init_from_array( self::FOLLOWED_ACTOR )->save();

		\update_option( 'event_bridge_for_activitypub_event_sources_active', true );
		\update_option(
			'event_bridge_for_activitypub_integration_used_for_event_sources_feature',
			\Event_Bridge_For_ActivityPub\Integrations\GatherPress::class
		);
		\update_option( 'activitypub_actor_mode', ACTIVITYPUB_BLOG_MODE );
	}

	/**
	 * Tear down the test.
	 */
	public function tear_down() {
		\delete_option( 'permalink_structure' );
	}

	public static function get_all_posts() {
		global $wpdb;

		return $wpdb->get_results( "SELECT ID, post_type from {$wpdb->posts}", ARRAY_A );
	}

	/**
	 * Test receiving event from followed actor.
	 */
	public function test_getting_past_remote_events() {
		\add_filter( 'activitypub_defer_signature_verification', '__return_true' );

		// Receive an federated event.
		$json = array(
			'id'     => 'https://remote.example/@organizer/events/new-year-party#create',
			'type'   => 'Create',
			'actor'  => 'https://remote.example/@organizer',
			'object' => array(
				'id'        => 'https://remote.example/@organizer/events/new-year-party',
				'type'      => 'Event',
				'startTime' => \gmdate( 'Y-m-d\TH:i:s\Z', time() + WEEK_IN_SECONDS ),
				'endTime'   => \gmdate( 'Y-m-d\TH:i:s\Z', time() + WEEK_IN_SECONDS + HOUR_IN_SECONDS ),
				'name'      => 'Fediverse Party for GatherPress',
				'to'        => 'https://www.w3.org/ns/activitystreams#Public',
				'published' => '2020-01-01T00:00:00Z',
				'location'  => array(
					'type'    => 'Place',
					'name'    => 'Fediverse Concert Hall',
					'address' => 'Fedistreet 13, Feditown 1337',
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/activitypub/1.0/users/0/inbox' );
		$request->set_header( 'Content-Type', 'application/activity+json' );
		$request->set_body( \wp_json_encode( $json ) );

		// Dispatch the request.
		$response = \rest_do_request( $request );
		$this->assertEquals( 202, $response->get_status() );

		// Mock local GatherPress Event.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Locally created GatherPress event',
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

		$this->assertNotEquals( false, $post_id );

		// Check if we now have two tribe events.

		$query = \GatherPress\Core\Event_Query::get_instance();
		$query->get_past_events();

		$events = GatherPress::get_cached_remote_events( time() + MONTH_IN_SECONDS );
		$this->assertEquals( 1, count( $events ) );
		$this->assertEquals( $json['object']['id'], get_post( $events[0] )->guid );

		$events = GatherPress::get_cached_remote_events( time() - WEEK_IN_SECONDS );
		$this->assertEquals( 0, count( $events ) );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}
}
