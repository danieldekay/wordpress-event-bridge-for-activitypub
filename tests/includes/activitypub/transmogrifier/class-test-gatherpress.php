<?php
/**
 * Test file for the Transmogrifier (import of ActivityPub Event objects) of GatherPress.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests\ActivityPub\Transmogrifier;

use GatherPress\Core\Event;
use GatherPress\Core\Event_Query;
use WP_REST_Request;
use WP_REST_Server;

require_once EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . '/tests/includes/activitypub/transmogrifier/class-helper.php';

/**
 * Test class for the Transmogrifier (import of ActivityPub Event objects) of GatherPress.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\GatherPress
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
	 * Post ID.
	 *
	 * @var int
	 */
	protected static $event_source_post_id;

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
	 * Purge gatherpress custom events table.
	 */
	public static function delete_all_gatherpress_events() {
		global $wpdb;
	}

	/**
	 * Tear down the test.
	 */
	public function tear_down() {
		\delete_option( 'permalink_structure' );
		_delete_all_posts();
	}

	/**
	 * Test receiving event from followed actor.
	 */
	public function test_incoming_event() {
		\add_filter( 'activitypub_defer_signature_verification', '__return_true' );

		$json = array(
			'id'     => 'https://remote.example/@organizer/events/new-year-party#create',
			'type'   => 'Create',
			'actor'  => 'https://remote.example/@organizer',
			'object' => array(
				'id'        => 'https://remote.example/@organizer/events/new-year-party',
				'type'      => 'Event',
				'startTime' => \gmdate( 'Y-m-d\TH:i:s\Z', time() + WEEK_IN_SECONDS ),
				'endTime'   => \gmdate( 'Y-m-d\TH:i:s\Z', time() + WEEK_IN_SECONDS + HOUR_IN_SECONDS ),
				'name'      => 'Fediverse Party',
				'to'        => 'https://www.w3.org/ns/activitystreams#Public',
				'published' => \gmdate( 'Y-m-d\TH:i:s\Z', time() ),
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

		// Check if post has been created.
		$event_query = Event_Query::get_instance();
		$the_query   = $event_query->get_upcoming_events();

		$this->assertEquals( true, $the_query->have_posts() );
		$this->assertEquals( 1, $the_query->post_count );

		// Initialize new GatherPress Event object.
		$event = new Event( $the_query->get_posts()[0] );

		$this->assertEquals( $json['object']['name'], $event->event->post_title );
		$this->assertEquals( $json['object']['startTime'], $event->get_datetime_start( 'Y-m-d\TH:i:s\Z' ) );
		$this->assertEquals( $json['object']['endTime'], $event->get_datetime_end( 'Y-m-d\TH:i:s\Z' ) );
		$this->assertEquals( $json['object']['location']['address'], $event->get_venue_information()['full_address'] );
		$this->assertEquals( $json['object']['location']['name'], $event->get_venue_information()['name'] );
		$this->assertEquals( false, $event->get_venue_information()['is_online_event'] );
	}

	/**
	 * Test receiving event from followed actor.
	 */
	public function test_incoming_event_update_and_delete() {
		\add_filter( 'activitypub_defer_signature_verification', '__return_true' );

		$json = array(
			'id'     => 'https://remote.example/@organizer/events/new-year-party#create',
			'type'   => 'Create',
			'actor'  => 'https://remote.example/@organizer',
			'object' => array(
				'id'        => 'https://remote.example/@organizer/events/new-year-party',
				'type'      => 'Event',
				'startTime' => \gmdate( 'Y-m-d\TH:i:s\Z', time() + WEEK_IN_SECONDS ),
				'endTime'   => \gmdate( 'Y-m-d\TH:i:s\Z', time() + WEEK_IN_SECONDS + HOUR_IN_SECONDS ),
				'name'      => 'Fediverse Party',
				'to'        => 'https://www.w3.org/ns/activitystreams#Public',
				'published' => \gmdate( 'Y-m-d\TH:i:s\Z', time() ),
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

		// Check if post has been created.
		$event_query = Event_Query::get_instance();
		$the_query   = $event_query->get_upcoming_events();

		$this->assertEquals( true, $the_query->have_posts() );
		$this->assertEquals( 1, $the_query->post_count );

		// Initialize new GatherPress Event object.
		$event = new Event( $the_query->get_posts()[0] );

		$this->assertEquals( $json['object']['name'], $event->event->post_title );
		$this->assertEquals( $json['object']['startTime'], $event->get_datetime_start( 'Y-m-d\TH:i:s\Z' ) );
		$this->assertEquals( $json['object']['endTime'], $event->get_datetime_end( 'Y-m-d\TH:i:s\Z' ) );
		$this->assertEquals( $json['object']['location']['address'], $event->get_venue_information()['full_address'] );
		$this->assertEquals( $json['object']['location']['name'], $event->get_venue_information()['name'] );
		$this->assertEquals( false, $event->get_venue_information()['is_online_event'] );

		// Now we receive an update of that event.
		$json['type']                          = 'Update';
		$json['object']['name']                = 'Updated name';
		$json['object']['location']['address'] = 'Updated address';

		$request->set_body( \wp_json_encode( $json ) );
		$response = \rest_do_request( $request );

		// We do not except duplicated.
		$the_query = $event_query->get_upcoming_events();
		$this->assertEquals( 1, $the_query->post_count );

		// Check the updated representation of the event within The Events Calendar.
		$event = new Event( $the_query->get_posts()[0] );

		$this->assertEquals( 'Updated name', $event->event->post_title );
		$this->assertEquals( 'Updated address', $event->get_venue_information()['full_address'] );

		// Test delete.
		$json['type']   = 'Delete';
		$json['object'] = $json['object']['id'];
		$request->set_body( \wp_json_encode( $json ) );
		$response = \rest_do_request( $request );
		$this->assertEquals( 202, $response->get_status() );

		// We do expect the event to be removed.
		$the_query = $event_query->get_upcoming_events();
		$this->assertFalse( $the_query->have_posts() );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}

	/**
	 * Test incoming Gancio style event.
	 */
	public function test_incoming_gancio_event() {
		\add_filter( 'activitypub_defer_signature_verification', '__return_true' );

		$activity = array(
			'id'     => 'https://remote.example/@organizer/events/new-year-party#create',
			'type'   => 'Create',
			'actor'  => 'https://remote.example/@organizer',
			'object' => Helper::get_gancio_event(),
		);

		$request = new WP_REST_Request( 'POST', '/activitypub/1.0/users/0/inbox' );
		$request->set_header( 'Content-Type', 'application/activity+json' );
		$request->set_body( \wp_json_encode( $activity ) );

		// Dispatch the request.
		$response = \rest_do_request( $request );
		$this->assertEquals( 202, $response->get_status() );

		// Check if post has been created.
		$event_query = Event_Query::get_instance();
		$the_query   = $event_query->get_upcoming_events();

		$this->assertEquals( true, $the_query->have_posts() );
		$this->assertEquals( 1, $the_query->post_count );

		// Initialize new GatherPress Event object.
		$event = new Event( $the_query->get_posts()[0] );

		$this->assertEquals( $activity['object']['name'], $event->event->post_title );
		$this->assertEquals( strtotime( $activity['object']['startTime'] ), strtotime( $event->get_datetime()['datetime_start_gmt'] ) );
		$this->assertEquals( strtotime( $activity['object']['endTime'] ), strtotime( $event->get_datetime()['datetime_end_gmt'] ) );
		$this->assertEquals( $activity['object']['location']['address'], $event->get_venue_information()['full_address'] );
		$this->assertEquals( $activity['object']['location']['name'], $event->get_venue_information()['name'] );
		$this->assertEquals( strtotime( $activity['object']['published'] ), strtotime( $event->event->post_date_gmt ) );
		$this->assertEquals( $activity['object']['id'], $event->event->guid );
		$this->assertEquals( false, $event->get_venue_information()['is_online_event'] );
	}
}
