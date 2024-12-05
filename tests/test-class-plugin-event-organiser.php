<?php
/**
 * Test class for the integration of the Event Organiser.
 *
 * @package Event_Bridge_For_ActivityPub
 */

/**
 * Sample test case.
 */
class Test_Event_Organiser extends WP_UnitTestCase {
	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'eo_get_events' ) ) {
			self::markTestSkipped( 'Event Organiser plugin is not active.' );
		}

		// Make sure that ActivityPub support is enabled.
		$aec = \Event_Bridge_For_ActivityPub\Setup::get_instance();
		$aec->activate_activitypub_support_for_active_event_plugins();

		// Run the install script just in time which makes sure the custom tables exist and more.
		eventorganiser_install();

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
		$this->assertContains( 'event', get_option( 'activitypub_support_post_types' ) );

		$event_data = array(
			'start'    => new DateTime( '+10 days 15:00:00', eo_get_blog_timezone() ),
			'end'      => new DateTime( '+10 days 16:00:00', eo_get_blog_timezone() ),
			'all_day'  => 0,
			'schedule' => 'once',
		);

		$post_data = array(
			'post_title'   => 'Unit Test Event',
			'post_content' => 'Unit Test description.',
			'post_status'  => 'publish',
		);

		$post_id = eo_insert_event( $post_data, $event_data );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( get_post( $post_id ) );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \Event_Bridge_For_ActivityPub\Activitypub\Transformer\Event_Organiser::class, $transformer );
	}

	/**
	 * Test transformation to ActivityPub for basic event.
	 */
	public function test_transform_of_basic_event() {
		// Mock Event.
		$event_data = array(
			'start'    => new DateTime( '+10 days 15:00:00', eo_get_blog_timezone() ),
			'end'      => new DateTime( '+10 days 16:00:00', eo_get_blog_timezone() ),
			'all_day'  => 0,
			'schedule' => 'once',
		);

		$post_data = array(
			'post_title'   => 'Unit Test Event',
			'post_content' => 'Unit Test description.',
			'post_status'  => 'publish',
		);

		$post_id = eo_insert_event( $post_data, $event_data );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $post_id ) )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'Unit Test Event', $event_array['name'] );
		$this->assertEquals( 'Unit Test description.', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
	}

	/**
	 * Test transformation to ActivityPub for event with location.
	 */
	public function test_transform_of_event_with_location() {
		// Create venue.
		$venue_args = array(
			'description' => 'This is a test venue for the Fediverse.',
			'address'     => 'Fediverse-Street 1337',
			'city'        => 'Fediverse-Town',
			'state'       => 'Fediverse-Sate',
			'postcode'    => '1337',
			'country'     => 'Fediverse-Country',
			'latitude'    => 7.076668,
			'longitude'   => 15.421371,
		);
		$venue_name = 'Fediverse Venue';
		$venue      = eo_insert_venue( $venue_name, $venue_args );

		// Mock Event.
		$event_data = array(
			'start'    => new DateTime( '+10 days 15:00:00', eo_get_blog_timezone() ),
			'end'      => new DateTime( '+10 days 16:00:00', eo_get_blog_timezone() ),
			'all_day'  => 0,
			'schedule' => 'once',
		);
		$post_data  = array(
			'post_title'   => 'Unit Test Event',
			'post_content' => 'Unit Test description.',
			'post_status'  => 'publish',
		);
		$post_id    = eo_insert_event( $post_data, $event_data );
		wp_set_object_terms( $post_id, $venue['term_id'], 'event-venue' );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $post_id ) )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'Unit Test Event', $event_array['name'] );
		$this->assertEquals( 'Unit Test description.', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertArrayHasKey( 'location', $event_array );
		$this->assertEquals( $venue_args['description'], wp_strip_all_tags( $event_array['location']['content'] ) );
		$this->assertEquals( $venue_args['address'], $event_array['location']['address']['streetAddress'] );
		$this->assertEquals( $venue_args['city'], $event_array['location']['address']['addressLocality'] );
		$this->assertEquals( $venue_args['state'], $event_array['location']['address']['addressRegion'] );
		$this->assertEquals( $venue_args['country'], $event_array['location']['address']['addressCountry'] );
		$this->assertEquals( $venue_args['postcode'], $event_array['location']['address']['postalCode'] );
		$this->assertEquals( $venue_name, $event_array['location']['name'] );
	}
}
