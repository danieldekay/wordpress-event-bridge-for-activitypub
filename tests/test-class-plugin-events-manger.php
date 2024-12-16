<?php
/**
 * Class SampleTest
 *
 * @package Event_Bridge_For_ActivityPub
 */

/**
 * Sample test case.
 */
class Test_Events_Manager extends WP_UnitTestCase {
	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( 'EM_Events' ) ) {
			self::markTestSkipped( 'VS Event List plugin is not active.' );
		}

		// For tests allow every user to create new events.
		update_option( 'dbem_events_anonymous_submissions', true );

		// Make sure that ActivityPub support is enabled for Events Manager.
		$aec = \Event_Bridge_For_ActivityPub\Setup::get_instance();
		$aec->activate_activitypub_support_for_active_event_plugins();

		// Delete all posts afterwards.
		_delete_all_posts();
	}

	/**
	 * Test that the right transformer gets applied.
	 */
	public function test_transformer_class() {
		// We only test for one event plugin being active at the same time,
		// even though we support multiple onces in theory.
		// But testing all combinations is beyond scope.
		$active_event_plugins = \Event_Bridge_For_ActivityPub\Setup::get_instance()->get_active_event_plugins();
		$this->assertEquals( 1, count( $active_event_plugins ) );

		// Enable ActivityPub support for the event plugin.
		$this->assertContains( EM_POST_TYPE_EVENT, get_option( 'activitypub_support_post_types' ) );

		// Insert a new Event.
		$wp_post_id = wp_insert_post(
			array(
				'post_title'  => 'Events Manager Test event',
				'post_status' => 'publish',
				'post_type'   => EM_POST_TYPE_EVENT,
				'meta_input'  => array(
					'event_start_time' => strtotime( '+10 days 15:00:00' ),
				),
			)
		);

		$wp_object = get_post( $wp_post_id );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Events_Manager::class, $transformer );
	}

	/**
	 * Test the transformation of a minimal event.
	 */
	public function test_transform_of_minimal_event() {
		// Create mockup event.
		$event                   = new EM_Event();
		$event->event_name       = 'Events Manager Test event';
		$event->post_content     = 'Event description';
		$event->event_start_date = gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) );
		$event->event_start_time = '15:00:00';
		$event->start            = strtotime( $event->event_start_date . ' ' . $event->event_start_time );
		$event->force_status     = 'publish';
		$event->event_rsvp       = false;
		$this->assertTrue( $event->save() );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $event->post_id ) )->to_object()->to_array();

		// Check that we got the right transformer.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'Events Manager Test event', $event_array['name'] );
		$this->assertEquals( 'Event description', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( comments_open( $event->post_id ), $event_array['commentsEnabled'] );
		$this->assertEquals( comments_open( $event->post_id ) ? 'allow_all' : 'closed', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
		$this->assertArrayNotHasKey( 'endTime', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
	}

	/**
	 * Test the transformation of a event with full location.
	 */
	public function test_transform_of__full_event_with_location() {
		// Create a mockup location.
		$location                    = new EM_Location();
		$location->location_name     = 'Test location';
		$location->location_address  = 'Test Address';
		$location->location_town     = 'Test Town';
		$location->location_state    = 'Test state';
		$location->location_postcode = '1337';
		$location->location_region   = 'Test region';
		$location->location_country  = 'AT'; // Must be a two char country code.
		$this->assertTrue( $location->save() );

		// Create mockup event.
		$event                   = new EM_Event();
		$event->event_name       = 'Events Manager Test event';
		$event->post_content     = 'Event description';
		$event->location_id      = $location->location_id;
		$event->event_start_date = gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) );
		$event->event_end_date   = gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) );
		$event->event_start_time = '15:00:00';
		$event->event_end_time   = '16:00:00';
		$event->start            = strtotime( $event->event_start_date . ' ' . $event->event_start_time );
		$event->end              = strtotime( $event->event_end_date . ' ' . $event->event_end_time );
		$event->force_status     = 'publish';
		$event->event_rsvp       = false;
		$this->assertTrue( $event->save() );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $event->post_id ) )->to_object()->to_array();

		// Check that we got the right transformer.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'Events Manager Test event', $event_array['name'] );
		$this->assertEquals( 'Event description', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertEquals( 'MEETING', $event_array['category'] );
		$this->assertArrayHasKey( 'location', $event_array );
		$this->assertEquals( 'Test location', $event_array['location']['name'] );
		$this->assertEquals( 'Test Address', $event_array['location']['address']['postalAddress'] );
		$this->assertEquals( 'Test Town', $event_array['location']['address']['addressLocality'] );
		$this->assertEquals( 'Test state', $event_array['location']['address']['addressRegion'] );
		$this->assertEquals( '1337', $event_array['location']['address']['postalCode'] );
		$this->assertEquals( 'AT', $event_array['location']['address']['addressCountry'] );
	}

	/**
	 * Test the transformation of a minimal event.
	 */
	public function test_transform_of_event_with_name_only_location() {
		// Create a mockup location.
		$location                = new EM_Location();
		$location->location_name = 'Name only location';
		$this->assertTrue( $location->save() );

		// Create mockup event.
		$event                   = new EM_Event();
		$event->event_name       = 'Events Manager Test event';
		$event->post_content     = 'Event description';
		$event->location_id      = $location->location_id;
		$event->event_start_date = gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) );
		$event->event_end_date   = gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) );
		$event->event_start_time = '15:00:00';
		$event->event_end_time   = '16:00:00';
		$event->start            = strtotime( $event->event_start_date . ' ' . $event->event_start_time );
		$event->end              = strtotime( $event->event_end_date . ' ' . $event->event_end_time );
		$event->force_status     = 'publish';
		$event->event_rsvp       = false;
		$this->assertTrue( $event->save() );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $event->post_id ) )->to_object()->to_array();

		// Check that we got the right transformer.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'Events Manager Test event', $event_array['name'] );
		$this->assertEquals( 'Event description', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertEquals( 'MEETING', $event_array['category'] );
		$this->assertArrayHasKey( 'location', $event_array );
		$this->assertEquals( 'Name only location', $event_array['location']['name'] );
	}
}
