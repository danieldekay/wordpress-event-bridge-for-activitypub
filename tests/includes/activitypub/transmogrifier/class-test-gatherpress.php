<?php
/**
 * Test file for the Transmogrifier (import of ActivityPub Event objects) of GatherPress.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests\ActivityPub\Transmogrifier;

use Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source;
use GatherPress\Core\Event;
use GatherPress\Core\Event_Query;
use WP_REST_Request;
use WP_REST_Server;

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
}
