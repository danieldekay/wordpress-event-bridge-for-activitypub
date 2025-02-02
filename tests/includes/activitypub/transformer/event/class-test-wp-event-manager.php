<?php
/**
 * Test class for the transformation of the events of the WordPress event plugin WP Event Manager.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\Tests\ActivityPub\Transformer\Event;

/**
 * Test class for the transformation of the events of the WordPress event plugin WP Event Manager.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\WP_Event_Manager
 */
class Test_WP_Event_Manager extends \WP_UnitTestCase {
	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'wp_event_manager_notify_new_user' ) ) {
			self::markTestSkipped( 'WP Event Manager plugin is not active.' );
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
	public function test_transformer_class() {
		// We only test for one event plugin being active at the same time,
		// even though we support multiple onces in theory.
		// But testing all combinations is beyond scope.
		$active_event_plugins = \Event_Bridge_For_ActivityPub\Setup::get_instance()->get_active_event_plugins();
		$this->assertEquals( 1, count( $active_event_plugins ) );

		// Enable ActivityPub support for the event plugin.
		$this->assertContains( 'event_listing', get_option( 'activitypub_support_post_types' ) );

		// Insert a new Event.
		$wp_post_id = wp_insert_post(
			array(
				'post_title'  => 'WP Event Manager TestEvent',
				'post_status' => 'publish',
				'post_type'   => 'event_listing',
				'meta_input'  => array(
					'event-start-date' => strtotime( '+10 days 15:00:00' ),
				),
			)
		);

		$wp_object = \get_post( $wp_post_id );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\WP_Event_Manager::class, $transformer );
	}

	/**
	 * Test the transformation to ActivityStreams of minimal event.
	 */
	public function test_transform_of_minimal_event() {
		// Insert a new Event.
		$wp_post_id = \wp_insert_post(
			array(
				'post_title'   => 'WP Event Manager TestEvent',
				'post_status'  => 'publish',
				'post_type'    => 'event_listing',
				'post_content' => 'Come to my WP Event Manager event!',
				'meta_input'   => array(
					'_event_start_date' => strtotime( '+10 days 15:00:00' ),
				),
			)
		);

		// Transform the event to ActivityStreams.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $wp_post_id ) )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'WP Event Manager TestEvent', $event_array['name'] );
		$this->assertEquals( 'Come to my WP Event Manager event!', \wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( \gmdate( 'Y-m-d', \strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertArrayNotHasKey( 'endTime', $event_array );
		$this->assertEquals( \comments_open( $wp_post_id ), $event_array['commentsEnabled'] );
		$this->assertEquals( \comments_open( $wp_post_id ) ? 'allow_all' : 'closed', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertEquals( \esc_url( \get_permalink( $wp_post_id ) ), $event_array['externalParticipationUrl'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
	}

	/**
	 * Test the transformation to ActivityStreams of minimal event.
	 */
	public function test_transform_of_full_online_event() {
		// Insert a new Event.
		$wp_post_id = \wp_insert_post(
			array(
				'post_title'   => 'WP Event Manager TestEvent',
				'post_status'  => 'publish',
				'post_type'    => 'event_listing',
				'post_content' => 'Come to my WP Event Manager event!',
				'meta_input'   => array(
					'_event_start_date' => \gmdate( 'Y-m-d H:i:s', strtotime( '+10 days 15:00:00' ) ),
					'_event_end_date'   => \gmdate( 'Y-m-d H:i:s', strtotime( '+10 days 16:00:00' ) ),
					'_event_video_url'  => 'https://event-federation.eu/meeting-room',
					'_event_online'     => 'yes',
				),
			)
		);

		// Transform the event to ActivityStreams.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $wp_post_id ) )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'WP Event Manager TestEvent', $event_array['name'] );
		$this->assertEquals( 'Come to my WP Event Manager event!', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertEquals( comments_open( $wp_post_id ), $event_array['commentsEnabled'] );
		$this->assertEquals( comments_open( $wp_post_id ) ? 'allow_all' : 'closed', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertEquals( true, $event_array['isOnline'] );
		$this->assertEquals( esc_url( get_permalink( $wp_post_id ) ), $event_array['externalParticipationUrl'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
		$this->assertContains(
			array(
				'type'      => 'Link',
				'name'      => __( 'Video URL', 'event-bridge-for-activitypub' ),
				'href'      => 'https://event-federation.eu/meeting-room',
				'mediaType' => 'text/html',
			),
			$event_array['attachment']
		);
	}

	/**
	 * Test the transformation to ActivityStreams of minimal event.
	 */
	public function test_transform_of_event_with_location() {
		// Insert a new Event.
		$wp_post_id = \wp_insert_post(
			array(
				'post_title'   => 'WP Event Manager TestEvent',
				'post_status'  => 'publish',
				'post_type'    => 'event_listing',
				'post_content' => 'Come to my WP Event Manager event!',
				'meta_input'   => array(
					'_event_start_date' => \gmdate( 'Y-m-d H:i:s', \strtotime( '+10 days 15:00:00' ) ),
					'_event_end_date'   => \gmdate( 'Y-m-d H:i:s', \strtotime( '+10 days 16:00:00' ) ),
					'_event_location'   => 'Some text location',
					'_event_online'     => 'no',
				),
			)
		);

		// Transform the event to ActivityStreams.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $wp_post_id ) )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'WP Event Manager TestEvent', $event_array['name'] );
		$this->assertEquals( 'Come to my WP Event Manager event!', \wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( \gmdate( 'Y-m-d', \strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( \gmdate( 'Y-m-d', \strtotime( '+10 days 15:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertEquals( \comments_open( $wp_post_id ), $event_array['commentsEnabled'] );
		$this->assertEquals( \comments_open( $wp_post_id ) ? 'allow_all' : 'closed', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertEquals( false, $event_array['isOnline'] );
		$this->assertEquals( \esc_url( \get_permalink( $wp_post_id ) ), $event_array['externalParticipationUrl'] );
		$this->assertArrayHasKey( 'location', $event_array );
		$this->assertEquals( 'Some text location', $event_array['location']['address'] );
	}
}
