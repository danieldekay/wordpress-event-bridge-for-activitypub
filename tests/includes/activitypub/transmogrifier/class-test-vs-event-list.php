<?php
/**
 * Test file for the Transmogrifier (import of ActivityPub Event objects) in VS Event List.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests\ActivityPub\Transmogrifier;

use Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\VS_Event_List as TransformerVS_Event_List;
use Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\VS_Event_List;
use Event_Bridge_For_ActivityPub\Integrations\VS_Event_List as IntegrationsVS_Event_List;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Test class for the Transmogrifier (import of ActivityPub Event objects) in VS Event List.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\VS_Event_List
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
				'name'      => 'Fediverse Party Test Event',
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

		$events = get_posts( array( 'post_type' => IntegrationsVS_Event_List::get_post_type() ) );
		$this->assertCount( 1, $events );
		$event = $events[0];

		$this->assertEquals( $json['object']['name'], $event->post_title );
		$this->assertEquals( $json['object']['startTime'], \gmdate( 'Y-m-d\TH:i:s\Z', get_post_meta( $event->ID, 'event-start-date', true ) ) );
		$this->assertEquals( $json['object']['endTime'], \gmdate( 'Y-m-d\TH:i:s\Z', get_post_meta( $event->ID, 'event-date', true ) ) );
		$this->assertStringStartsWith( $json['object']['location']['name'], get_post_meta( $event->ID, 'event-location', true ) );
		$this->assertStringContainsString( $json['object']['location']['address'], get_post_meta( $event->ID, 'event-location', true ) );

		// Now we receive an update of that event.
		$json['type']                          = 'Update';
		$json['object']['name']                = 'Updated name';
		$json['object']['location']['address'] = 'Updated address';

		$request->set_body( \wp_json_encode( $json ) );
		$response = \rest_do_request( $request );

		// We do not except duplicated.
		$events = get_posts( array( 'post_type' => IntegrationsVS_Event_List::get_post_type() ) );
		$this->assertCount( 1, $events );
		$event = $events[0];
		$this->assertStringContainsString( 'Updated address', get_post_meta( $event->ID, 'event-location', true ) );

		// We should see the updates.
		$this->assertEquals( 'Updated name', $event->post_title );

		// Test delete.
		$json['type']   = 'Delete';
		$json['object'] = $json['object']['id'];
		$request->set_body( \wp_json_encode( $json ) );
		$response = \rest_do_request( $request );
		$this->assertEquals( 202, $response->get_status() );

		// We do expect the event to be removed.
		$events = get_posts( array( 'post_type' => IntegrationsVS_Event_List::get_post_type() ) );
		$this->assertCount( 0, $events );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}
}
