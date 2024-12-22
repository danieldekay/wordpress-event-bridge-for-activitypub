<?php
/**
 * Test file for the Event Sources feature.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests;

use GatherPress\Core\Event_Query;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Test class for the Event Sources Feature.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\Event_Sources
 */
class Test_Event_Sources extends \WP_UnitTestCase {
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
	}

	/**
	 * Test receiving event from followed actor.
	 */
	public function test_incoming_valid_event_returns_202() {
		\add_filter( 'activitypub_defer_signature_verification', '__return_true' );

		$json = array(
			'id'     => 'https://remote.example/@organizer/events/new-year-party#create',
			'type'   => 'Create',
			'actor'  => 'https://remote.example/@organizer',
			'object' => array(
				'id'        => 'https://remote.example/@organizer/events/new-year-party',
				'type'      => 'Event',
				'startTime' => \gmdate( 'Y-m-d\TH:i:s\Z', time() + WEEK_IN_SECONDS ),
				'name'      => 'Fediverse Party [valid]',
				'to'        => 'https://www.w3.org/ns/activitystreams#Public',
				'published' => '2020-01-01T00:00:00Z',
			),
		);

		$request = new WP_REST_Request( 'POST', '/activitypub/1.0/users/0/inbox' );
		$request->set_header( 'Content-Type', 'application/activity+json' );
		$request->set_body( \wp_json_encode( $json ) );

		// Dispatch the request.
		$response = \rest_do_request( $request );
		$this->assertEquals( 202, $response->get_status() );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}

	/**
	 * Test receiving event from followed actor with missing start time.
	 */
	public function test_incoming_create_with_missing_start_time() {
		\add_filter( 'activitypub_defer_signature_verification', '__return_true' );

		$json = array(
			'id'     => 'https://remote.example/@organizer/events/new-year-party#create',
			'type'   => 'Create',
			'actor'  => 'https://remote.example/@organizer',
			'object' => array(
				'id'        => 'https://remote.example/@organizer/events/new-year-party',
				'type'      => 'Event',
				'name'      => 'Fediverse Party [missing start time]',
				'to'        => 'https://www.w3.org/ns/activitystreams#Public',
				'published' => '2020-01-01T00:00:00Z',
			),
		);

		$request = new WP_REST_Request( 'POST', '/activitypub/1.0/users/0/inbox' );
		$request->set_header( 'Content-Type', 'application/activity+json' );
		$request->set_body( \wp_json_encode( $json ) );

		// Dispatch the request.
		$response = \rest_do_request( $request );
		$this->assertEquals( 400, $response->get_status() );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}

	/**
	 * Test receiving event from followed actor with wrongly formatted start time.
	 */
	public function test_incoming_event_with_faulty_start_time() {
		\add_filter( 'activitypub_defer_signature_verification', '__return_true' );

		$json = array(
			'id'     => 'https://remote.example/@organizer/events/new-year-party#create',
			'type'   => 'Create',
			'actor'  => 'https://remote.example/@organizer',
			'object' => array(
				'id'        => 'https://remote.example/@organizer/events/new-year-party',
				'type'      => 'Event',
				'name'      => 'Fediverse Party [faulty start time]',
				'startTime' => \gmdate( 'Y-m-d H:i:s', time() + WEEK_IN_SECONDS ),
				'to'        => 'https://www.w3.org/ns/activitystreams#Public',
				'published' => '2020-01-01T00:00:00Z',
			),
		);

		$request = new WP_REST_Request( 'POST', '/activitypub/1.0/users/0/inbox' );
		$request->set_header( 'Content-Type', 'application/activity+json' );
		$request->set_body( \wp_json_encode( $json ) );

		// Dispatch the request.
		$response = \rest_do_request( $request );
		$this->assertEquals( 400, $response->get_status() );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}

	/**
	 * We do understand, but do not care about incoming events that happened in the past.
	 */
	public function test_incoming_event_which_took_place_in_the_past() {
		\add_filter( 'activitypub_defer_signature_verification', '__return_true' );

		$json = array(
			'id'     => 'https://remote.example/@organizer/events/new-year-party#create',
			'type'   => 'Create',
			'actor'  => 'https://remote.example/@organizer',
			'object' => array(
				'id'        => 'https://remote.example/@organizer/events/new-year-party',
				'type'      => 'Event',
				'name'      => 'Fediverse Event [took place in past]',
				'startTime' => \gmdate( 'Y-m-d\TH:i:s\Z', time() - WEEK_IN_SECONDS ),
				'to'        => 'https://www.w3.org/ns/activitystreams#Public',
				'published' => '2020-01-01T00:00:00Z',
			),
		);

		$request = new WP_REST_Request( 'POST', '/activitypub/1.0/users/0/inbox' );
		$request->set_header( 'Content-Type', 'application/activity+json' );
		$request->set_body( \wp_json_encode( $json ) );

		// Dispatch the request.
		$response = \rest_do_request( $request );

		// This should be 403 but it is not possible without lots of hacks at the moment.
		$this->assertEquals( 202, $response->get_status() );

		// Verify that event did not get cached and added.
		$event_query = Event_Query::get_instance();
		$the_query   = $event_query->get_upcoming_events();
		$this->assertEquals( false, $the_query->have_posts() );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}

	/**
	 * Test receiving event from actor we do not follow.
	 */
	public function test_incoming_create_from_non_followed_actor() {
		\add_filter( 'activitypub_defer_signature_verification', '__return_true' );

		$json = array(
			'id'     => 'https://remote.example/@another_organizer/events/new-year-party#create',
			'type'   => 'Create',
			'actor'  => 'https://remote.example/@another_organizer',
			'object' => array(
				'id'        => 'https://remote.example/@another_organizer/events/new-year-party',
				'type'      => 'Event',
				'startTime' => '2050-12-31T18:00:00Z',
				'name'      => 'Fediverse Party [from non-follower actor]',
				'to'        => 'https://www.w3.org/ns/activitystreams#Public',
				'published' => '2020-01-01T00:00:00Z',
			),
		);

		$request = new WP_REST_Request( 'POST', '/activitypub/1.0/users/0/inbox' );
		$request->set_header( 'Content-Type', 'application/activity+json' );
		$request->set_body( \wp_json_encode( $json ) );

		// Dispatch the request.
		$response = \rest_do_request( $request );
		$this->assertEquals( 202, $response->get_status() );

		// Verify that event did not get cached and added.
		$event_query = Event_Query::get_instance();
		$the_query   = $event_query->get_upcoming_events();
		$this->assertEquals( false, $the_query->have_posts() );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}
}
