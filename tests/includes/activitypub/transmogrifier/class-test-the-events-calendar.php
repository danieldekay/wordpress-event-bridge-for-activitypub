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
use WP_REST_Request;
use WP_REST_Server;

/**
 * Test class for the Transmogrifier (import of ActivityPub Event objects) of GatherPress.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\The_Events_Calendar
 */
class Test_The_Events_Calendar extends \WP_UnitTestCase {
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
	 * Create fake data before tests run.
	 */
	public static function wpSetUpBeforeClass() {
		// Follow actor.
		$event_source = Event_Source::init_from_array( self::FOLLOWED_ACTOR );
		$post_id      = $event_source->save();

		// Save the post ID for usage in tests.
		self::$event_source_post_id = $post_id;
	}

	/**
	 * Set up the test.
	 */
	public function set_up() {
		if ( ! class_exists( '\Tribe__Events__Main' ) ) {
			self::markTestSkipped( 'The Events Calendar plugin is not active.' );
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

		\update_option( 'event_bridge_for_activitypub_event_sources_active', true );
		\update_option(
			'event_bridge_for_activitypub_integration_used_for_event_sources_feature',
			\Event_Bridge_For_ActivityPub\Integrations\The_Events_Calendar::class
		);
		\update_option( 'activitypub_actor_mode', ACTIVITYPUB_BLOG_MODE );
	}

	/**
	 * Tear down the test.
	 */
	public function tear_down() {
		\delete_option( 'permalink_structure' );
		\add_filter( 'activitypub_defer_signature_verification', '__return_false' );
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
		$the_query = tribe_get_events();

		$this->assertEquals( true, $the_query->have_posts() );
		$this->assertEquals( 1, $the_query->post_count );

		// Initialize new GatherPress Event object.
		$event = tribe_get_event( $the_query->get_posts()[0] );

		$this->assertEquals( $json['object']['name'], $event->post_title );
		$this->assertEquals( $json['object']['startTime'], $event->start->format( 'Y-m-d\TH:i:s\Z' ) );
		$this->assertEquals( $json['object']['endTime'], $event->end->format( 'Y-m-d\TH:i:s\Z' ) );

		$venues = $event->venues;
		// Get first venue. We currently only support a single venue.
		if ( $venues instanceof \Tribe\Events\Collections\Lazy_Post_Collection ) {
			$venue = $venues->first();
		} elseif ( empty( $this->wp_object->venues ) || ! empty( $this->wp_object->venues[0] ) ) {
			return null;
		} else {
			$venue = $venues[0];
		}

		$this->assertEquals( $json['object']['location']['address'], $venue->address );
		$this->assertEquals( $json['object']['location']['name'], $venue->post_title );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}
}
