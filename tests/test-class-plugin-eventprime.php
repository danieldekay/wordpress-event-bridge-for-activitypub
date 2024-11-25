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
	 * Mockup events of certain complexity.
	 */
	public const MOCKUP_VENUS = array(
		'minimal_venue' => array(
			'venue'  => 'Minimal Venue',
			'status' => 'publish',
		),
		'complex_venue' => array(
			'venue'         => 'Complex Venue',
			'status'        => 'publish',
			'show_map'      => false,
			'show_map_link' => false,
			'address'       => 'Venue address',
			'city'          => 'Venue city',
			'country'       => 'Venue country',
			'province'      => 'Venue province',
			'state'         => 'Venue state',
			'stateprovince' => 'Venue stateprovince',
			'zip'           => 'Venue zip',
			'phone'         => 'Venue phone',
			'website'       => 'http://venue.com',
		),
	);

	public const MOCKUP_EVENTS = array(
		'minimal_event' => array(
			'title'      => 'My Event',
			'content'    => 'Come to my event!',
			'start_date' => '+10 days 15:00:00',
			'duration'   => HOUR_IN_SECONDS,
			'status'     => 'publish',
		),
		'complex_event' => array(
			'title'      => 'My Event',
			'content'    => 'Come to my event!',
			'start_date' => '+10 days 15:00:00',
			'duration'   => HOUR_IN_SECONDS,
			'status'     => 'publish',
		),
	);

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
		$this->assertContains( 'tribe_events', get_option( 'activitypub_support_post_types' ) );

		$event_data                      = array();
		$event_data['name']              = 'EventPrime Event title';
		$event_data['description']       = 'EventPrime event description';
		$event_data['status']            = 'Publish';
		$event_data['em_event_type']     = '';
		$event_data['em_venue']          = '';
		$event_data['em_organizer']      = '';
		$event_data['em_performer']      = '';
		$event_data['em_start_date']     = strtotime( '+10 days 15:00:00' );
		$event_data['em_end_date']       = strtotime( '+10 days 16:00:00' );
		$event_data['em_enable_booking'] = 'bookings_off';
		$event_data['em_ticket_price']   = 0;

		// Create an EventPrime Event without content.
		$ep_functions = new Eventprime_Basic_Functions();

		$post_id = $ep_functions->insert_event_post_data( $event_data );

		$wp_object = get_post( $post_id );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \ActivityPub_Event_Bridge\Activitypub\Transformer\EventPrime::class, $transformer );
	}
}
