<?php
/**
 * Test file for the main Integration of VS Events List.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests\Integrations;

use Event_Bridge_For_ActivityPub\Integrations\VS_Event_List;

use WP_REST_Request;
use WP_REST_Server;

/**
 * Test class for the main Integration of VS Events List.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\Integration\VS_Event_List
 */
class Test_VS_Event_List extends \WP_UnitTestCase {
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
		if ( ! function_exists( 'vsel_custom_post_type' ) ) {
			self::markTestSkipped( 'VS Event List plugin is not active.' );
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
			\Event_Bridge_For_ActivityPub\Integrations\VS_Event_List::class
		);
		\update_option( 'activitypub_actor_mode', ACTIVITYPUB_BLOG_MODE );
	}

	/**
	 * Tear down the test.
	 */
	public function tear_down() {
		\delete_option( 'permalink_structure' );
	}

	/**
	 * Test receiving event from followed actor.
	 */
	public function test_getting_past_remote_events() {
		\add_filter( 'activitypub_defer_signature_verification', '__return_true' );

		// Federated event 1: starts in one week.
		$event_in_one_week = array(
			'id'     => 'https://remote.example/@organizer/events/in-one-week#create',
			'type'   => 'Create',
			'actor'  => 'https://remote.example/@organizer',
			'object' => array(
				'id'        => 'https://remote.example/@organizer/events/in-one-week',
				'type'      => 'Event',
				'startTime' => \gmdate( 'Y-m-d\TH:i:s\Z', time() + WEEK_IN_SECONDS ),
				'endTime'   => \gmdate( 'Y-m-d\TH:i:s\Z', time() + WEEK_IN_SECONDS + HOUR_IN_SECONDS ),
				'name'      => 'Remote Event in One Week',
				'to'        => 'https://www.w3.org/ns/activitystreams#Public',
				'published' => '2020-01-01T00:00:00Z',
				'location'  => array(
					'type'    => 'Place',
					'name'    => 'Fediverse Concert Hall',
					'address' => 'Fedistreet 13, Feditown 1337',
				),
			),
		);

		// Federated event 1: starts in two months.
		$event_in_two_months = array(
			'id'     => 'https://remote.example/@organizer/events/in-two-months#create',
			'type'   => 'Create',
			'actor'  => 'https://remote.example/@organizer',
			'object' => array(
				'id'        => 'https://remote.example/@organizer/events/in-two-months',
				'type'      => 'Event',
				'startTime' => \gmdate( 'Y-m-d\TH:i:s\Z', time() + 2 * MONTH_IN_SECONDS ),
				'endTime'   => \gmdate( 'Y-m-d\TH:i:s\Z', time() + 2 * MONTH_IN_SECONDS + HOUR_IN_SECONDS ),
				'name'      => 'Remote Event in Two Months',
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

		// Receive both events.
		$request->set_body( \wp_json_encode( $event_in_one_week ) );
		$response = \rest_do_request( $request );
		$this->assertEquals( 202, $response->get_status() );
		$request->set_body( \wp_json_encode( $event_in_two_months ) );
		$response = \rest_do_request( $request );
		$this->assertEquals( 202, $response->get_status() );

		// Create a local event in VS Event List.
		$post_id = \wp_insert_post(
			array(
				'post_title'  => 'VSEL Local Test Event',
				'post_status' => 'publish',
				'post_type'   => 'event',
				'meta_input'  => array(
					'event-start-date' => \strtotime( '+10 days 15:00:00' ),
					'event-date'       => \strtotime( '+10 days 16:00:00' ),
					'event-link'       => 'https://event-federation.eu/vsel-test-event',
					'event-link-label' => 'Website',
				),
			)
		);

		$this->assertNotEquals( false, $post_id );

		// Only one event should show up in the remote events query.
		$events = VS_Event_List::get_cached_remote_events( time() + MONTH_IN_SECONDS );
		$this->assertEquals( 1, count( $events ) );
		$this->assertEquals( $event_in_one_week['object']['id'], get_post( $events[0] )->guid );

		// Include the even in two months in the time_span.
		$events = VS_Event_List::get_cached_remote_events( time() + 3 * MONTH_IN_SECONDS );
		$this->assertEquals( 2, count( $events ) );

		// All events are in the future, so no events should be in past.
		$events = VS_Event_List::get_cached_remote_events( time() );
		$this->assertEquals( 0, count( $events ) );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}
}
