<?php
/**
 * Class SampleTest
 *
 * @package Event_Bridge_For_ActivityPub
 */

/**
 * Sample test case.
 */
class Test_GatherPress extends WP_UnitTestCase {
	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! defined( 'GATHERPRESS_CORE_FILE' ) ) {
			self::markTestSkipped( 'GatherPress plugin is not active.' );
		}

		// Mock the plugin activation.
		GatherPress\Core\Setup::get_instance()->activate_gatherpress_plugin( false );

		// Make sure that ActivityPub support is enabled for The Events Calendar.
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
		$this->assertContains( 'gatherpress_event', get_option( 'activitypub_support_post_types' ) );

		// Mock GatherPress Event.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Unit Test Event',
				'post_type'    => 'gatherpress_event',
				'post_content' => 'Unit Test description.',
				'post_status'  => 'publish',
			)
		);
		$event   = new \GatherPress\Core\Event( $post_id );
		$params  = array(
			'datetime_start' => '+10 days 15:00:00',
			'datetime_end'   => '+10 days 16:00:00',
			'timezone'       => \wp_timezone_string(),
		);

		$event->save_datetimes( $params );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $event->event );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \Event_Bridge_For_ActivityPub\Activitypub\Transformer\GatherPress::class, $transformer );
	}

	/**
	 * Test transformation to ActivityPUb for basic event.
	 */
	public function test_transform_of_basic_event() {
		// Mock GatherPress Event.
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Unit Test Event',
				'post_type'    => 'gatherpress_event',
				'post_content' => 'Unit Test description.',
				'post_status'  => 'publish',
			)
		);
		$event   = new \GatherPress\Core\Event( $post_id );
		$params  = array(
			'datetime_start' => '+10 days 15:00:00',
			'datetime_end'   => '+10 days 16:00:00',
			'timezone'       => \wp_timezone_string(),
		);
		$event->save_datetimes( $params );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( $event->event )->to_object()->to_array();

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
