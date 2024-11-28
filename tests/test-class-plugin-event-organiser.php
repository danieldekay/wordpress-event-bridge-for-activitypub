<?php
/**
 * Test class for the integration of the Event Organiser.
 *
 * @package ActivityPub_Event_Bridge
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

		if ( ! class_exists( '\EO_Query_Result' ) ) {
			self::markTestSkipped( 'Event Organiser plugin is not active.' );
		}

		// Make sure that ActivityPub support is enabled.
		$aec = \ActivityPub_Event_Bridge\Setup::get_instance();
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
		$active_event_plugins = \ActivityPub_Event_Bridge\Setup::get_instance()->get_active_event_plugins();
		$this->assertEquals( 1, count( $active_event_plugins ) );

		// Enable ActivityPub support for the event plugin.
		$this->assertContains( 'event', get_option( 'activitypub_support_post_types' ) );

		$event_data = array(
			'start'     => new DateTime( '+10 days 15:00:00', eo_get_blog_timezone() ),
			'end'       => new DateTime( '+10 days 16:00:00', eo_get_blog_timezone() ),
			'all_day'   => 0,
			'schedule'  => 'once',
		);

		$post_data = array(
			'post_title'   => 'Unit Test Event',
			'post_content' => 'Unit Test description.',
		);

		$post_id = eo_insert_event( $post_data, $event_data );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( get_post( $post_id ) );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \ActivityPub_Event_Bridge\Activitypub\Transformer\Event_Organiser::class, $transformer );
	}

	/**
	 * Test transformation to ActivityPUb for basic event.
	 */
	public function test_transform_of_basic_event() {
		// Mock Event.
		$event_data = array(
			'start'     => new DateTime( '+10 days 15:00:00', eo_get_blog_timezone() ),
			'end'       => new DateTime( '+10 days 16:00:00', eo_get_blog_timezone() ),
			'all_day'   => 0,
			'schedule'  => 'once',
		);

		$post_data = array(
			'post_title'   => 'Unit Test Event',
			'post_content' => 'Unit Test description.',
		);

		$post_id = eo_insert_event( $post_data, $event_data );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $post_id )  )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'Unit Test Event', $event_array['name'] );
		$this->assertEquals( 'Unit Test description.', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
	}
}
