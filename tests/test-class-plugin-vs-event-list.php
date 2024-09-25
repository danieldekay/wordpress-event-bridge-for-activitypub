<?php
/**
 * Class SampleTest
 *
 * @package Activitypub_Event_Extensions
 */

/**
 * Sample test case.
 */
class Test_VS_Event_List extends WP_UnitTestCase {
	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'vsel_custom_post_type' ) ) {
			self::markTestSkipped( 'VS Event List plugin is not active.' );
		}

		// Make sure that ActivityPub support is enabled for The Events Calendar.
		$aec = \Activitypub_Event_Extensions\Setup::get_instance();
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
		$active_event_plugins = \Activitypub_Event_Extensions\Setup::get_instance()->get_active_event_plugins();
		$this->assertEquals( 1, count( $active_event_plugins ) );

		// Enable ActivityPub support for the event plugin.
		$this->assertContains( 'event', get_option( 'activitypub_support_post_types' ) );

		// Insert a new Event.
		$wp_post_id = wp_insert_post(
			array(
				'post_title'  => 'VSEL Test Event',
				'post_status' => 'published',
				'post_type'   => 'event',
				'meta_input'  => array(
					'event-start-date' => strtotime( '+10 days 15:00:00' ),
				),
			)
		);

		$wp_object = get_post( $wp_post_id );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \Activitypub_Event_Extensions\Activitypub\Transformer\VS_Event_List::class, $transformer );
	}

	/**
	 * Test the transformation to ActivityStreams of minimal event.
	 */
	public function test_transform_of_minimal_event() {
		// Insert a new Event.
		$wp_post_id = wp_insert_post(
			array(
				'post_title'  => 'VSEL Test Event',
				'post_status' => 'published',
				'post_type'   => 'event',
				'meta_input'  => array(
					'event-start-date' => strtotime( '+10 days 15:00:00' ),
				),
			)
		);

		// Transform the event to ActivityStreams.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $wp_post_id ) )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'VSEL Test Event', $event_array['name'] );
		$this->assertEquals( '', $event_array['content'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertArrayNotHasKey( 'endTime', $event_array );
		$this->assertEquals( comments_open( $wp_post_id ), $event_array['commentsEnabled'] );
		$this->assertEquals( comments_open( $wp_post_id ) ? 'allow_all' : 'closed', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertEquals( esc_url( get_permalink( $wp_post_id ) ), $event_array['externalParticipationUrl'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
	}

	/**
	 * Test the transformation to ActivityStreams of minimal event.
	 */
	public function test_transform_of_full_event() {
		// Insert a new Event.
		$wp_post_id = wp_insert_post(
			array(
				'post_title'  => 'VSEL Test Event',
				'post_status' => 'published',
				'post_type'   => 'event',
				'meta_input'  => array(
					'event-start-date' => strtotime( '+10 days 15:00:00' ),
					'event-date'       => strtotime( '+10 days 16:00:00' ),
					'event-link'       => 'https://event-federation.eu/vsel-test-event',
					'event-link-label' => 'Website',
				),
			)
		);

		// Transform the event to ActivityStreams.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $wp_post_id ) )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'VSEL Test Event', $event_array['name'] );
		$this->assertEquals( '', $event_array['content'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertEquals( comments_open( $wp_post_id ), $event_array['commentsEnabled'] );
		$this->assertEquals( comments_open( $wp_post_id ) ? 'allow_all' : 'closed', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertEquals( esc_url( get_permalink( $wp_post_id ) ), $event_array['externalParticipationUrl'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
		$this->assertContains(
			array(
				'type'      => 'Link',
				'name'      => 'Website',
				'href'      => 'https://event-federation.eu/vsel-test-event',
				'mediaType' => 'text/html',
			),
			$event_array['attachment']
		);
	}

	/**
	 * Test the transformation to ActivityStreams of event with hidden end time.
	 */
	public function test_transform_of_event_with_hidden_end_time() {
		// Insert a new Event.
		$wp_post_id = wp_insert_post(
			array(
				'post_title'  => 'VSEL Test Event',
				'post_status' => 'published',
				'post_type'   => 'event',
				'meta_input'  => array(
					'event-start-date'    => strtotime( '+10 days 15:00:00' ),
					'event-date'          => strtotime( '+10 days 16:00:00' ),
					'event-hide-end-time' => 'yes',
				),
			)
		);

		// Transform the event to ActivityStreams.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $wp_post_id ) )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertArrayNotHasKey( 'endTime', $event_array );
	}

	/**
	 * Test transformation of event with mapped category.
	 */
	public function test_transform_event_with_mapped_categories() {
		// Create category.
		$category_id_music   = wp_insert_term( 'Music', 'event_cat', array( 'slug' => 'music' ) );
		$category_id_theatre = wp_insert_term( 'Theatre', 'event_cat', array( 'slug' => 'theatre' ) );

		// Set default mapping for event categories.
		update_option( 'activitypub_event_extensions_default_event_category', 'MUSIC' );

		// Set an override for the category with the slug theatre.
		update_option( 'activitypub_event_extensions_event_category_mappings', array( 'theatre' => 'THEATRE' ) );

		// Create a VS Event List event with the music category.
		$wp_post_id = wp_insert_post(
			array(
				'post_title'  => 'VSEL Test Event',
				'post_status' => 'published',
				'post_type'   => 'event',
				'meta_input'  => array(
					'event-start-date'    => strtotime( '+10 days 15:00:00' ),
					'event-date'          => strtotime( '+10 days 16:00:00' ),
					'event-hide-end-time' => 'yes',
				),
			)
		);
		wp_set_post_terms( $wp_post_id, $category_id_music['term_id'], 'event_cat' );

		// Call the transformer.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $wp_post_id ) )->to_object()->to_array();

		// See if the default category mapping is applied.
		$this->assertEquals( 'MUSIC', $event_array['category'] );

		// Change the event category to theatre.
		wp_set_post_terms( $wp_post_id, $category_id_theatre['term_id'], 'event_cat', false );

		// Call the transformer.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( get_post( $wp_post_id ) )->to_object()->to_array();

		// See if the default category mapping is applied.
		$this->assertEquals( 'THEATRE', $event_array['category'] );
	}
}
