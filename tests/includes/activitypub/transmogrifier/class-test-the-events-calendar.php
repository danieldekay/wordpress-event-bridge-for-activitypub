<?php
/**
 * Test file for the Transmogrifier (import of ActivityPub Event objects) of "The Events Calendar".
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests\ActivityPub\Transmogrifier;

use WP_REST_Request;
use WP_REST_Server;

require_once EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . '/tests/includes/activitypub/transmogrifier/class-helper.php';

/**
 * Test class for the Transmogrifier (import of ActivityPub Event objects) of "The Events Calendar".
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
	 * REST Server.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

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

		// Add event source (ActivityPub follower).
		_delete_all_posts();
		\Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source::init_from_array( self::FOLLOWED_ACTOR )->save();

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
	}

	/**
	 * Get the first venue of an Event of The Events Calendar.
	 *
	 * @param \WP_Post $event The Event Post.
	 * @return ?\WP_Post
	 */
	private static function get_first_tribe_venue_of_tribe_event( $event ) {
		// Get first venue. We currently only support a single venue.
		if ( $event->venues instanceof \Tribe\Events\Collections\Lazy_Post_Collection ) {
			return $event->venues->first();
		} elseif ( empty( $event->venues ) || ! empty( $event->venues[0] ) ) {
			return null;
		} else {
			return $event->venues[0];
		}
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
				'name'      => 'Fediverse Party for The Events Calendar',
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
		$events = tribe_get_events();

		$this->assertEquals( 1, count( $events ) );

		// Initialize new Tribe Event object.
		$event = tribe_get_event( $events[0] );

		$this->assertEquals( $json['object']['name'], $event->post_title );
		$this->assertEquals( $json['object']['startTime'], $event->dates->start->format( 'Y-m-d\TH:i:s\Z' ) );
		$this->assertEquals( $json['object']['endTime'], $event->dates->end->format( 'Y-m-d\TH:i:s\Z' ) );
		$this->assertEquals( $json['object']['id'], $event->guid );

		$venue = self::get_first_tribe_venue_of_tribe_event( $event );

		$this->assertEquals( $json['object']['location']['address'], $venue->address );
		$this->assertEquals( $json['object']['location']['name'], $venue->post_title );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}

	/**
	 * Test receiving event from followed actor.
	 */
	public function test_incoming_event_with_postal_address() {
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
				'name'      => 'Fediverse Party for The Events Calendar',
				'to'        => 'https://www.w3.org/ns/activitystreams#Public',
				'published' => \gmdate( 'Y-m-d\TH:i:s\Z', time() ),
				'location'  => array(
					'type'    => 'Place',
					'name'    => 'Fediverse Concert Hall',
					'address' => array(
						'type'            => 'PostalAddress',
						'streetAddress'   => 'FediStreet 13',
						'postalCode'      => '1337',
						'addressLocality' => 'Feditown',
						'addressState'    => 'Fediverse State',
						'addressCountry'  => 'Fediverse World',
						'url'             => 'https://fedidevs.org/',
					),
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
		$events = tribe_get_events();

		$this->assertEquals( 1, count( $events ) );

		// Initialize new Tribe Event object.
		$event = tribe_get_event( $events[0] );

		$this->assertEquals( $json['object']['name'], $event->post_title );
		$this->assertEquals( $json['object']['startTime'], $event->dates->start->format( 'Y-m-d\TH:i:s\Z' ) );
		$this->assertEquals( $json['object']['endTime'], $event->dates->end->format( 'Y-m-d\TH:i:s\Z' ) );

		$venue = self::get_first_tribe_venue_of_tribe_event( $event );

		$this->assertEquals( $json['object']['location']['name'], $venue->post_title );
		$this->assertEquals( $json['object']['location']['address']['streetAddress'], $venue->address );
		$this->assertEquals( $json['object']['location']['address']['postalCode'], $venue->zip );
		$this->assertEquals( $json['object']['location']['address']['addressLocality'], $venue->city );
		$this->assertEquals( $json['object']['location']['address']['addressState'], $venue->state );
		$this->assertEquals( $json['object']['location']['address']['addressCountry'], $venue->country );
		$this->assertEquals( $json['object']['location']['address']['url'], $venue->website );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}

	/**
	 * Test handling updates and deletes.
	 */
	public function test_incoming_event_updates() {
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
				'name'      => 'Fediverse Party for The Events Calendar',
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
		$events = tribe_get_events();

		$this->assertEquals( 1, count( $events ) );

		// Now we receive an update of that event.
		$json['type']                          = 'Update';
		$json['object']['name']                = 'Updated name';
		$json['object']['location']['address'] = 'Updated address';

		$request->set_body( \wp_json_encode( $json ) );
		$response = \rest_do_request( $request );

		// We do not except duplicated.
		$events = tribe_get_events();
		$this->assertEquals( 1, count( $events ) );

		// Check the updated representation of the event within The Events Calendar.
		$event = tribe_get_event( $events[0] );
		$venue = self::get_first_tribe_venue_of_tribe_event( $event );

		$this->assertEquals( 'Updated name', $event->post_title );
		$this->assertEquals( 'Updated address', $venue->address );

		// Test delete.
		$json['type']   = 'Delete';
		$json['object'] = $json['object']['id'];
		$request->set_body( \wp_json_encode( $json ) );
		$response = \rest_do_request( $request );
		$this->assertEquals( 202, $response->get_status() );

		// We do expect the event to be removed.
		$events = tribe_get_events();
		$this->assertEmpty( $events );

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
		$events = tribe_get_events();

		$this->assertEquals( 1, count( $events ) );

		// Initialize new Tribe Event object.
		$event = tribe_get_event( $events[0] );

		$this->assertEquals( $activity['object']['name'], $event->post_title );
		$this->assertEquals( strtotime( $activity['object']['startTime'] ), $event->dates->start->getTimestamp() );
		$this->assertEquals( strtotime( $activity['object']['endTime'] ), $event->dates->end->getTimestamp() );
		$this->assertEquals( strtotime( $activity['object']['published'] ), strtotime( $event->post_date_gmt ) );
		$this->assertEquals( $activity['object']['id'], $event->guid );

		$venue = self::get_first_tribe_venue_of_tribe_event( $event );

		$this->assertEquals( $activity['object']['location']['address'], $venue->address );
		$this->assertEquals( $activity['object']['location']['name'], $venue->post_title );

		// Check the thumbnails alt text.
		$thumbnail_id       = \get_post_thumbnail_id( $event->ID );
		$thumbnail_alt_text = \get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
		// We have to mockup the attachment sideload to be able to text this.

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}

	/**
	 * Test receiving an update from Gancio style event.
	 */
	public function test_gancio_event_receive_updated_location() {
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
		\rest_do_request( $request );

		// Check that event, venue and organizer post has been created.
		$events     = tribe_get_events();
		$venues     = tribe_get_venues();
		$organizers = tribe_get_organizers();
		$this->assertCount( 1, $events );
		$this->assertCount( 1, $venues );
		$this->assertCount( 1, $organizers );

		// Send update.
		$activity['type']                       = 'Update';
		$activity['object']['location']['name'] = 'New Location Name';
		$request->set_body( \wp_json_encode( $activity ) );
		\rest_do_request( $request );

		// No duplicates.
		$events     = tribe_get_events();
		$venues     = tribe_get_venues();
		$organizers = tribe_get_organizers();
		$this->assertCount( 1, $events );
		$this->assertCount( 1, $venues );
		$this->assertCount( 1, $organizers );

		// Check that the location is updated.
		$this->assertEquals( 'New Location Name', $venues[0]->post_title );

		\remove_filter( 'activitypub_defer_signature_verification', '__return_true' );
	}
}
