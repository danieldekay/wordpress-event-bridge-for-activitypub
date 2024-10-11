<?php
/**
 * Test for Reminder class.
 *
 * @package ActivityPub_Event_Bridge
 */

/**
 * Test class for testing the scheduling of reminder Activities.
 */
class Test_Reminder extends WP_UnitTestCase {
	/**
	 * Mockup events of certain complexity.
	 */
	public const MOCKUP_VENUE = array(
		'venue'  => 'Minimal Venue',
		'status' => 'publish',
	);

	public const MOCKUP_EVENT = array(
		'title'      => 'My Event',
		'content'    => 'Come to my event!',
		'start_date' => '+10 days 15:00:00',
		'duration'   => HOUR_IN_SECONDS,
		'status'     => 'publish',
	);

	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( '\Tribe__Events__Main' ) ) {
			self::markTestSkipped( 'The Events Calendar is not active.' );
		}

		// For tests allow every user to create new events.
		update_option( 'dbem_events_anonymous_submissions', true );

		// Make sure that ActivityPub support is enabled for Events Manager.
		$aec = \ActivityPub_Event_Bridge\Setup::get_instance();
		$aec->activate_activitypub_support_for_active_event_plugins();

		// Delete all posts afterwards.
		_delete_all_posts();
	}

	public function test_event_reminder_not_being_scheduled_by_default() {
		// Create a The Events Calendar Event.
		$wp_object = tribe_events()
			->set_args( self::MOCKUP_EVENT )
			->create();

		$scheduled_event = \wp_get_scheduled_event( 'activitypub_event_bridge_send_event_reminder', array( $wp_object->ID ) );

		$this->assertEquals( false, $scheduled_event );
	}

	public function test_event_reminder_scheduled_with_site_wide_option() {
		\update_option( 'activitypub_event_bridge_reminder_time_gap', DAY_IN_SECONDS );
		// Create a The Events Calendar Event.
		$wp_object = tribe_events()
			->set_args( self::MOCKUP_EVENT )
			->create();

		$scheduled_event = \wp_get_scheduled_event( 'activitypub_event_bridge_send_event_reminder', array( $wp_object->ID ) );

		$this->assertNotEquals( false, $scheduled_event );
		$this->assertEquals( strtotime( self::MOCKUP_EVENT['start_date'] ) - DAY_IN_SECONDS, $scheduled_event->timestamp );
		$this->assertEquals( false, $scheduled_event->schedule );
		$this->assertEquals( 'activitypub_event_bridge_send_event_reminder', $scheduled_event->hook );
	}

	public function test_event_reminder_scheduled_with_per_event_override() {
		\update_option( 'activitypub_event_bridge_reminder_time_gap', DAY_IN_SECONDS );

		// Create a The Events Calendar Event.
		$wp_object = tribe_events()
			->set_args(
				array_merge(
					self::MOCKUP_EVENT,
					array( 'activitypub_event_bridge_reminder_time_gap' => DAY_IN_SECONDS * 3 ),
				)
			)
			->create();

		$scheduled_event = \wp_get_scheduled_event( 'activitypub_event_bridge_send_event_reminder', array( $wp_object->ID ) );

		$this->assertNotEquals( false, $scheduled_event );
		$this->assertEquals( strtotime( self::MOCKUP_EVENT['start_date'] ) - DAY_IN_SECONDS * 3, $scheduled_event->timestamp );
		$this->assertEquals( false, $scheduled_event->schedule );
		$this->assertEquals( 'activitypub_event_bridge_send_event_reminder', $scheduled_event->hook );

		// Now update the option once more to see if the schedule got updated too.
		$post_id = array_key_first(
			tribe_events( $wp_object->ID )
				->set_args(
					array( 'activitypub_event_bridge_reminder_time_gap' => HOUR_IN_SECONDS ),
				)
				->save()
		);

		$scheduled_event = \wp_get_scheduled_event( 'activitypub_event_bridge_send_event_reminder', array( $post_id ) );
		$this->assertNotEquals( false, $scheduled_event );
		$this->assertEquals( strtotime( self::MOCKUP_EVENT['start_date'] ) - HOUR_IN_SECONDS, $scheduled_event->timestamp );
	}

	public function test_event_reminder_deleted_event() {
		\update_option( 'activitypub_event_bridge_reminder_time_gap', DAY_IN_SECONDS );

		// Create a The Events Calendar Event.
		$wp_object = tribe_events()
			->set_args(
				array_merge(
					self::MOCKUP_EVENT,
					array( 'activitypub_event_bridge_reminder_time_gap' => DAY_IN_SECONDS * 3 ),
				)
			)
			->create();

		$scheduled_event = \wp_get_scheduled_event( 'activitypub_event_bridge_send_event_reminder', array( $wp_object->ID ) );

		$this->assertNotEquals( false, $scheduled_event );
		$this->assertEquals( strtotime( self::MOCKUP_EVENT['start_date'] ) - DAY_IN_SECONDS * 3, $scheduled_event->timestamp );
		$this->assertEquals( false, $scheduled_event->schedule );
		$this->assertEquals( 'activitypub_event_bridge_send_event_reminder', $scheduled_event->hook );

		// Now delete the event.
		tribe_events( $wp_object->ID )->delete();

		$scheduled_event = \wp_get_scheduled_event( 'activitypub_event_bridge_send_event_reminder', array( $wp_object->ID ) );
		$this->assertEquals( false, $scheduled_event );
	}

	public function test_event_reminder_event_moved_to_trash() {
		\update_option( 'activitypub_event_bridge_reminder_time_gap', DAY_IN_SECONDS );

		// Create a The Events Calendar Event.
		$wp_object = tribe_events()
			->set_args(
				array_merge(
					self::MOCKUP_EVENT,
					array( 'activitypub_event_bridge_reminder_time_gap' => DAY_IN_SECONDS * 3 ),
				)
			)
			->create();

		$scheduled_event = \wp_get_scheduled_event( 'activitypub_event_bridge_send_event_reminder', array( $wp_object->ID ) );

		$this->assertNotEquals( false, $scheduled_event );
		$this->assertEquals( strtotime( self::MOCKUP_EVENT['start_date'] ) - DAY_IN_SECONDS * 3, $scheduled_event->timestamp );
		$this->assertEquals( false, $scheduled_event->schedule );
		$this->assertEquals( 'activitypub_event_bridge_send_event_reminder', $scheduled_event->hook );

		// Now move the event to the trash.
		$post_id = array_key_first(
			tribe_events( $wp_object->ID )
				->set_args(
					array( 'post_status' => 'trash' ),
				)
				->save()
		);

		$scheduled_event = \wp_get_scheduled_event( 'activitypub_event_bridge_send_event_reminder', array( $wp_object->ID ) );
		$this->assertEquals( false, $scheduled_event );
	}
}
