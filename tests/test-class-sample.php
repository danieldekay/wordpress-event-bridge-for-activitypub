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
		// Replace this with some actual testing code.
		$class_exists = class_exists( 'Tribe__Events__Main' );
		$this->assertTrue( $class_exists );
	}
}
