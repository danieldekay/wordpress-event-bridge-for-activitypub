<?php
/**
 * Tests for WP Event Solution.
 *
 * @package ActivityPub_Event_Bridge
 */

/**
 * Test cases for WP Event Solution.
 */
class Test_Eventin extends WP_UnitTestCase {
	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( '\Wpeventin' ) ) {
			self::markTestSkipped( 'Eventin plugin is not active.' );
		}

		// Make sure that ActivityPub support is enabled for The Events Calendar.
		$aec = \ActivityPub_Event_Bridge\Setup::get_instance();
		$aec->activate_activitypub_support_for_active_event_plugins();

		// Delete all posts afterwards.
		_delete_all_posts();
	}

	/**
	 * Test that the right transformer gets applied.
	 */
	public function test_eventin_transformer_class() {
		// We only test for one event plugin being active at the same time,
		// even though we support multiple onces in theory.
		// But testing all combinations is beyond scope.
		$active_event_plugins = \ActivityPub_Event_Bridge\Setup::get_instance()->get_active_event_plugins();
		$this->assertEquals( 1, count( $active_event_plugins ) );

		// Enable ActivityPub support for the event plugin.
		$this->assertContains( 'etn', get_option( 'activitypub_support_post_types' ) );

		// Create a Eventin Event without content.
		$event_model = new \Etn\Core\Event\Event_Model();
		$event_model->create(
			array(
				'post_status'    => 'publish',
				'post_title'     => 'Eventin Test Event Title',
				'post_content'   => 'Eventin Test Event Description',
				'etn_start_date' => \gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ),
				'etn_end_date'   => \gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ),
				'etn_start_time' => \gmdate( 'H:i', strtotime( '+10 days 15:00:00' ) ),
				'etn_end_time'   => \gmdate( 'H:i', strtotime( '+10 days 15:00:00' ) ),
				'event_timezone' => 'Europe/Vienna',
			)
		);

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( get_post( $event_model->id ) );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \ActivityPub_Event_Bridge\Activitypub\Transformer\Eventin::class, $transformer );
	}

	/**
	 * Test that the right transformer gets applied.
	 */
	public function test_eventin_test_minimal_event() {
		// We only test for one event plugin being active at the same time,
		// even though we support multiple onces in theory.
		// But testing all combinations is beyond scope.
		$active_event_plugins = \ActivityPub_Event_Bridge\Setup::get_instance()->get_active_event_plugins();
		$this->assertEquals( 1, count( $active_event_plugins ) );

		// Enable ActivityPub support for the event plugin.
		$this->assertContains( 'etn', get_option( 'activitypub_support_post_types' ) );

		// Create a Eventin Event without content.
		$event = new \Etn\Core\Event\Event_Model();
		$event->create(
			array(
				'post_status'    => 'publish',
				'post_title'     => 'Eventin Test Event Title',
				'post_content'   => 'Eventin Test Event Description',
				'etn_start_date' => \gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ),
				'etn_end_date'   => \gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ),
				'etn_start_time' => '03:00 PM',
				'etn_end_time'   => '04:00 PM',
				'event_timezone' => 'Europe/Vienna',
			)
		);

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $event->id ) )->to_object()->to_array();

		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'Eventin Test Event Title', $event_array['name'] );
		$this->assertEquals( 'Eventin Test Event Description', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( comments_open( $event->id ), $event_array['commentsEnabled'] );
		$this->assertEquals( comments_open( $event->id ) ? 'allow_all' : 'closed', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
		$this->assertArrayNotHasKey( 'endTime', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
	}
}
