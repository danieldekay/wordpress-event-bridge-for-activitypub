<?php
/**
 * Tests or Modern Events Calendar Lite
 *
 * @package ActivityPub_Event_Bridge
 */

/**
 * Sample test case.
 */
class Test_Modern_Events_Calendar_Lite extends WP_UnitTestCase {
	/**
	 * The MEC main instance.
	 *
	 * @var \MEC_main|null
	 */
	protected $mec_main;

	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( '\MEC' ) ) {
			self::markTestSkipped( 'Modern Events Calendar Lite is not active.' );
		}

		// Make sure that ActivityPub support is enabled for The Events Calendar.
		$aec = \ActivityPub_Event_Bridge\Setup::get_instance();
		$aec->activate_activitypub_support_for_active_event_plugins();

		$this->mec_main = \MEC::getInstance( 'app.libraries.main' );

		// Delete all posts afterwards.
		_delete_all_posts();
	}

	/**
	 * Test that the right transformer gets applied.
	 */
	public function test_modern_events_calendar_lite_transformer_class() {
		// We only test for one event plugin being active at the same time,
		// even though we support multiple onces in theory.
		// But testing all combinations is beyond scope.
		$active_event_plugins = \ActivityPub_Event_Bridge\Setup::get_instance()->get_active_event_plugins();
		$this->assertEquals( 1, count( $active_event_plugins ) );

		// Enable ActivityPub support for the event plugin.
		$this->assertContains( 'mec-events', get_option( 'activitypub_support_post_types' ) );

		// Insert a new Event.
		$event = array(
			'title'              => 'MEC Test Event',
			'status'             => 'publish',
			'start_time_hour'    => '3',
			'start_time_minutes' => '00',
			'start_time_ampm'    => 'PM',
			'start'              => '2025-01-01',
			'end'                => '2025-01-01',
			'end_time_hour'      => '4',
			'end_time_minutes'   => '00',
			'end_time_ampm'      => 'PM',
			'repeat_status'      => 0,
			'repeat_type'        => 'daily',
			'interval'           => 1,
		);

		$post_id = $this->mec_main->save_event( $event );

		$wp_object = get_post( $post_id );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \ActivityPub_Event_Bridge\Activitypub\Transformer\Modern_Events_Calendar_Lite::class, $transformer );
	}
}
