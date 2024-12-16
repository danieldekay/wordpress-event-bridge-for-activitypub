<?php
/**
 * Tests for WP Event Solution.
 *
 * @package Event_Bridge_For_ActivityPub
 */

/**
 * Test cases for WP Event Solution.
 */
class Test_Eventin extends WP_UnitTestCase {
	/**
	 * Basic Mock-up event.
	 */
	private function get_mockup_event(): array {
		return array(
			'post_status'    => 'publish',
			'post_title'     => 'Eventin Test Event Title',
			'post_content'   => 'Eventin Test Event Description',
			'etn_start_date' => \gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ),
			'etn_end_date'   => \gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ),
			'etn_start_time' => \gmdate( 'H:i', strtotime( '+10 days 15:00:00' ) ),
			'etn_end_time'   => \gmdate( 'H:i', strtotime( '+10 days 16:00:00' ) ),
			'event_timezone' => 'Europe/Vienna',
		);
	}

	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( '\Wpeventin' ) ) {
			self::markTestSkipped( 'Eventin plugin is not active.' );
		}

		// Make sure that ActivityPub support is enabled for The Events Calendar.
		$aec = \Event_Bridge_For_ActivityPub\Setup::get_instance();
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
		$active_event_plugins = \Event_Bridge_For_ActivityPub\Setup::get_instance()->get_active_event_plugins();
		$this->assertEquals( 1, count( $active_event_plugins ) );

		// Enable ActivityPub support for the event plugin.
		$this->assertContains( 'etn', get_option( 'activitypub_support_post_types' ) );

		// Create a Eventin Event without content.
		$event = new \Etn\Core\Event\Event_Model();
		$event->create( $this->get_mockup_event() );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( get_post( $event->id ) );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Eventin::class, $transformer );
	}

	/**
	 * Test that the right transformer gets applied.
	 */
	public function test_eventin_test_minimal_event() {
		// Create a Eventin Event without content.
		$event = new \Etn\Core\Event\Event_Model();
		$event->create( $this->get_mockup_event() );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $event->id ) )->to_object()->to_array();

		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'Eventin Test Event Title', $event_array['name'] );
		$this->assertEquals( 'Eventin Test Event Description', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '+10 days 15:00:00' ) ), $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '+10 days 16:00:00' ) ), $event_array['endTime'] );
		$this->assertEquals( 'Europe/Vienna', $event_array['timezone'] );
		$this->assertEquals( comments_open( $event->id ), $event_array['commentsEnabled'] );
		$this->assertEquals( comments_open( $event->id ) ? 'allow_all' : 'closed', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
		$this->assertEquals( false, $event_array['isOnline'] );
	}

	/**
	 * Test that the right transformer gets applied.
	 */
	public function test_eventin_test_online_event_with_custom_link() {
		// Create a Eventin Event without content.
		$event = new \Etn\Core\Event\Event_Model();
		$args  = array_merge(
			$this->get_mockup_event(),
			array(
				'event_type' => 'online',
				'location'   => array(
					'integration' => 'custom_url',
					'custom_url'  => 'https://jit.si/eventmeeting',
				),
			)
		);
		$event->create( $args );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $event->id ) )->to_object()->to_array();

		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'Eventin Test Event Title', $event_array['name'] );
		$this->assertEquals( 'Eventin Test Event Description', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '+10 days 15:00:00' ) ), $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '+10 days 16:00:00' ) ), $event_array['endTime'] );
		$this->assertEquals( 'Europe/Vienna', $event_array['timezone'] );
		$this->assertEquals( comments_open( $event->id ), $event_array['commentsEnabled'] );
		$this->assertEquals( comments_open( $event->id ) ? 'allow_all' : 'closed', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
		$this->assertEquals( true, $event_array['isOnline'] );
		$this->assertContains(
			array(
				'type'      => 'Link',
				'mediaType' => 'text/html',
				'name'      => 'https://jit.si/eventmeeting',
				'href'      => 'https://jit.si/eventmeeting',
			),
			$event_array['attachment']
		);
	}


	/**
	 * Test that the right transformer gets applied.
	 */
	public function test_eventin_test_online_event_with_physical_location() {
		// Create a Eventin Event without content.
		$event = new \Etn\Core\Event\Event_Model();
		$args  = array_merge(
			$this->get_mockup_event(),
			array(
				'event_type' => 'offline',
				'location'   => array(
					'address' => 'The NlNet center',
				),
			)
		);
		$event->create( $args );

		// Call the transformer Factory.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $event->id ) )->to_object()->to_array();

		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'Eventin Test Event Title', $event_array['name'] );
		$this->assertEquals( 'Eventin Test Event Description', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '+10 days 15:00:00' ) ), $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '+10 days 16:00:00' ) ), $event_array['endTime'] );
		$this->assertEquals( 'Europe/Vienna', $event_array['timezone'] );
		$this->assertEquals( comments_open( $event->id ), $event_array['commentsEnabled'] );
		$this->assertEquals( comments_open( $event->id ) ? 'allow_all' : 'closed', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertArrayHasKey( 'location', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
		$this->assertEquals( false, $event_array['isOnline'] );
		$this->assertEquals( 'The NlNet center', $event_array['location']['address'] );
		$this->assertEquals( 'The NlNet center', $event_array['location']['name'] );
	}
}
