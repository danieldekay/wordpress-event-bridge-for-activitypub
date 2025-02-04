<?php
/**
 * Tests for Modern Events Calendar Lite
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\Tests\ActivityPub\Transformer\Event;

/**
 *  Tests for the ActivityPub transformation of Modern Events Calendar Lite
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Modern_Events_Calendar_Lite
 */
class Test_Modern_Events_Calendar_Lite extends \WP_UnitTestCase {
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
		$aec = \Event_Bridge_For_ActivityPub\Setup::get_instance();
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
		$active_event_plugins = \Event_Bridge_For_ActivityPub\Setup::get_instance()->get_active_event_plugins();
		$this->assertEquals( 1, count( $active_event_plugins ) );

		// Enable ActivityPub support for the event plugin.
		$this->assertContains( 'mec-events', \get_option( 'activitypub_support_post_types' ) );

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

		$wp_object = \get_post( $post_id );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Modern_Events_Calendar_Lite::class, $transformer );
	}

	/**
	 * Test that the transformation of minimal event.
	 */
	public function test_modern_events_calendar_lite_minimal_event() {
		$start_timestamp = \strtotime( '+10 days 15:00:00' );
		$end_timestamp   = \strtotime( '+10 days 16:00:00' );

		// Insert a new Event.
		$event = array(
			'title'              => 'MEC Test Event',
			'status'             => 'publish',
			'content'            => 'This is the content of the MEC!',
			'start_time_hour'    => \gmdate( 'h', $start_timestamp ),
			'start_time_minutes' => \gmdate( 'i', $start_timestamp ),
			'start_time_ampm'    => \gmdate( 'A', $start_timestamp ),
			'start'              => \gmdate( 'Y-m-d', $start_timestamp ),
			'end'                => \gmdate( 'Y-m-d', $end_timestamp ),
			'end_time_hour'      => \gmdate( 'h', $end_timestamp ),
			'end_time_minutes'   => \gmdate( 'i', $end_timestamp ),
			'end_time_ampm'      => \gmdate( 'A', $end_timestamp ),
			'repeat_status'      => 0,
			'repeat_type'        => 'daily',
			'interval'           => 1,
		);

		$post_id = $this->mec_main->save_event( $event );

		$wp_object = \get_post( $post_id );

		// Call the transformer to make the ActivityStreams representation of the event.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( $wp_object )->to_object()->to_array();

		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'MEC Test Event', $event_array['name'] );
		$this->assertEquals( 'This is the content of the MEC!', \wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( \gmdate( 'Y-m-d', \strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( \gmdate( 'Y-m-d', \strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertTrue( $event_array['commentsEnabled'] );
		$this->assertEquals( 'allow_all', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertEquals( get_permalink( $wp_object ), $event_array['externalParticipationUrl'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
	}

	/**
	 * Test that the transformation of minimal event.
	 */
	public function test_modern_events_calendar_lite_event_with_location() {
		$start_timestamp = \strtotime( '+10 days 15:00:00' );
		$end_timestamp   = \strtotime( '+10 days 16:00:00' );

		// Add new location.
		$location = array(
			'name'      => 'MEC Location',
			'latitude'  => '52.356370',
			'longitude' => '4.955760',
			'address'   => 'Stichting NLnet, Science Park 400, 1098 XH Amsterdam',
			'url'       => 'https://nlnet.nl/',
		);

		$location_id = $this->mec_main->save_location( $location );

		// Insert a new Event.
		$event = array(
			'title'              => 'MEC Test Event',
			'status'             => 'publish',
			'content'            => 'This is the content of the MEC!',
			'start_time_hour'    => \gmdate( 'h', $start_timestamp ),
			'start_time_minutes' => \gmdate( 'i', $start_timestamp ),
			'start_time_ampm'    => \gmdate( 'A', $start_timestamp ),
			'start'              => \gmdate( 'Y-m-d', $start_timestamp ),
			'end'                => \gmdate( 'Y-m-d', $end_timestamp ),
			'end_time_hour'      => \gmdate( 'h', $end_timestamp ),
			'end_time_minutes'   => \gmdate( 'i', $end_timestamp ),
			'end_time_ampm'      => \gmdate( 'A', $end_timestamp ),
			'repeat_status'      => 0,
			'repeat_type'        => 'daily',
			'interval'           => 1,
			'location_id'        => $location_id,
		);

		$post_id = $this->mec_main->save_event( $event );

		$wp_object = \get_post( $post_id );

		// Call the transformer to make the ActivityStreams representation of the event.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( $wp_object )->to_object()->to_array();

		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'MEC Test Event', $event_array['name'] );
		$this->assertEquals( 'This is the content of the MEC!', \wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( \gmdate( 'Y-m-d', \strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( \gmdate( 'Y-m-d', \strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertTrue( $event_array['commentsEnabled'] );
		$this->assertEquals( 'allow_all', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertEquals( \get_permalink( $wp_object ), $event_array['externalParticipationUrl'] );
		$this->assertArrayHasKey( 'location', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
		$this->assertEquals( $location['address'], $event_array['location']['address'] );
		$this->assertEquals( $location['name'], $event_array['location']['name'] );
		$this->assertEquals( $location['latitude'], $event_array['location']['latitude'] );
		$this->assertEquals( $location['longitude'], $event_array['location']['longitude'] );
	}
}
