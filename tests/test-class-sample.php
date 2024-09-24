<?php
/**
 * Class SampleTest
 *
 * @package Activitypub_Event_Extensions
 */

/**
 * Sample test case.
 */
class Test_Sample extends WP_UnitTestCase {

	/**
	 * A single example test.
	 */
	public function test_sample() {
		// Replace this with some actual testing code.
		$this->assertTrue( true );
	}

	/**
	 * Tesd tes
	 */
	public function test_the_events_calendar() {
		// First check manually that The Events Calendar is loaded.
		$class = class_exists( '\Tribe__Events__Main' );
		$this->assertTrue( $class );

		// Get instance of our plugin.
		$aec = \Activitypub_Event_Extensions\Setup::get_instance();

		// We only test for one event plugin being active at the same time,
		// even though we support multiple onces in theory.
		// But testing all combinations is beyond scope.
		$active_event_plugins = $aec->get_active_event_plugins();
		$this->assertEquals( 1, count( $active_event_plugins ) );

		// Enable ActivityPub support for the event plugin.
		$aec->activate_activitypub_support_for_active_event_plugins();
		$this->assertContains( 'tribe_events',  get_option( 'activitypub_support_post_types' ) );

		$wp_object = tribe_events()
			->set_args(
				array(
					'title'      => 'My Event',
					'content'    => 'Come to my event. Let\'s connect!',
					'start_date' => '+10 days 15:00:00',
					'duration'   => HOUR_IN_SECONDS,
					'status'     => 'publish',
				)
			)
			->create();

		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		$event_array = $transformer->to_object()->to_array();

		$this->assertArrayHasKey( 'type', $event_array );
		$this->assertEquals( 'Event', $event_array['type'] );

		$this->assertEquals( 'My Event', $event_array['name'] );

		$this->assertEquals( '', $event_array['content'] );

		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );

		$this->assertEquals( ) . 'T16:00:00Z', $event_array['commentsEnabled'] );

		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
	}
}
