<?php
/**
 * Class SampleTest
 *
 * @package ActivityPub_Event_Bridge
 */

/**
 * Sample test case.
 */
class Test_EventPrime extends WP_UnitTestCase {
	/**
	 * Mockup venues of certain complexity.
	 *
	 * @var array
	 */
	private $mockup_venue = array();

	/**
	 * Mockup events for tests.
	 *
	 * @var array
	 */
	protected $mockup_events = array();

	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( '\Eventprime_Basic_Functions' ) ) {
			self::markTestSkipped( 'The EventPrime Calendar management plugin is not active.' );
		}

		// Make sure that ActivityPub support is enabled for The Events Calendar.
		$aeb = \ActivityPub_Event_Bridge\Setup::get_instance();
		$aeb->activate_activitypub_support_for_active_event_plugins();

		// Delete all posts afterwards.
		_delete_all_posts();

		$this->setup_mockup_data();
	}

	/**
	 * Setup mockup events.
	 */
	private function setup_mockup_data() {
		$this->mockup_events = array(
			'minimal_event' => array(
				'name'              => 'EventPrime Event title',
				'description'       => 'EventPrime event description',
				'status'            => 'Publish',
				'em_event_type'     => '',
				'em_venue'          => '',
				'em_organizer'      => '',
				'em_performer'      => '',
				'em_start_date'     => strtotime( '+10 days 15:00:00' ),
				'em_end_date'       => strtotime( '+10 days 16:00:00' ),
				'em_enable_booking' => 'bookings_off',
				'em_ticket_price'   => 0,
			),
		);

		$this->mockup_venue = array(
			'name'    => 'Test Venue',
			'address' => 'Fediverse-street 1337, 1234 Fediverse-town',
		);
	}

	/**
	 * Test that the right transformer gets applied.
	 */
	public function test_the_events_calendar_transformer_class() {
		// We only test for one event plugin being active at the same time,
		// even though we support multiple onces in theory.
		// But testing all combinations is beyond scope.
		$active_event_plugins = \ActivityPub_Event_Bridge\Setup::get_instance()->get_active_event_plugins();
		$this->assertEquals( 1, count( $active_event_plugins ) );

		// Enable ActivityPub support for the event plugin.
		$this->assertContains( 'em_event', get_option( 'activitypub_support_post_types' ) );

		// Create an EventPrime Event without content.
		$ep_functions = new Eventprime_Basic_Functions();

		$post_id = $ep_functions->insert_event_post_data( $this->mockup_events['minimal_event'] );

		$wp_object = get_post( $post_id );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \ActivityPub_Event_Bridge\Activitypub\Transformer\EventPrime::class, $transformer );
	}

	/**
	 * Test transformation of minimal event.
	 */
	public function test_transformation_of_minimal_event() {
		// Create an EventPrime Event without content.
		$ep_functions = new Eventprime_Basic_Functions();

		$post_id = $ep_functions->insert_event_post_data( $this->mockup_events['minimal_event'] );

		$wp_object = get_post( $post_id );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( $wp_object )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'EventPrime Event title', $event_array['name'] );
		$this->assertEquals( 'EventPrime event description', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertTrue( $event_array['commentsEnabled'] );
		$this->assertEquals( 'allow_all', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
	}

	/**
	 * Test transformation of minimal event.
	 */
	public function test_transformation_of_minimal_event_with_venue() {
		// Create an EventPrime Event without content.
		$ep_functions  = new Eventprime_Basic_Functions();

		$venue_term_id = wp_insert_term( $this->mockup_venue['name'], 'em_venue' )['term_id'];
		add_term_meta( $venue_term_id, 'em_address', $this->mockup_venue['address'], true );
		add_term_meta( $venue_term_id, 'em_display_address_on_frontend', true, true );

		$event_data             = $this->mockup_events['minimal_event'];
		$event_data['em_venue'] = $venue_term_id;

		$post_id = $ep_functions->insert_event_post_data( $event_data );

		$wp_object = get_post( $post_id );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( $wp_object )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'EventPrime Event title', $event_array['name'] );
		$this->assertEquals( 'EventPrime event description', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertTrue( $event_array['commentsEnabled'] );
		$this->assertEquals( 'allow_all', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertEquals( $this->mockup_venue['name'], $event_array['location']['name'] );
		$this->assertEquals( $this->mockup_venue['address'], $event_array['location']['address'] );

		$this->assertEquals( 'MEETING', $event_array['category'] );
	}

	/**
	 * Test transformation of minimal event with venue which has a hidden address.
	 */
	public function test_transformation_of_minimal_event_with_venue_with_hidden_address() {
		// Create an EventPrime Event without content.
		$ep_functions  = new Eventprime_Basic_Functions();

		$venue_term_id = wp_insert_term( $this->mockup_venue['name'], 'em_venue' )['term_id'];
		add_term_meta( $venue_term_id, 'em_address', $this->mockup_venue['address'], true );
		add_term_meta( $venue_term_id, 'em_display_address_on_frontend', false, true );

		$event_data             = $this->mockup_events['minimal_event'];
		$event_data['em_venue'] = $venue_term_id;

		$post_id = $ep_functions->insert_event_post_data( $event_data );

		$wp_object = get_post( $post_id );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( $wp_object )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertArrayNotHasKey( 'address', $event_array['location'] );
	}
}
