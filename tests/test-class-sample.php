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
		$this->assertContains( 'the-events-calendar', $aec->get_active_event_plugins() );

		$aec->activate_activitypub_support_for_active_event_plugins();
		$this->assertContains( 'tribe_events',  get_option( 'activitypub_support_post_types' ));
	}
}
