<?php
/**
 * Class SampleTest
 *
 * @package Activitypub_Event_Extensions
 */

/**
 * Sample test case.
 */
class Test_The_Events_Calendar extends WP_UnitTestCase {
	/**
	 * Mockup events of certain complexity.
	 */
	public const MOCKUP_VENUS = array(
		'minimal_venue' => array(
			'venue'         => 'Minimal Venue',
			'status'        => 'publish',
		),
		'complex_venue' => array(
			'venue'         => 'Complex Venue',
			'status'        => 'publish',
			'show_map'      => false,
			'show_map_link' => false,
			'address'       => 'Venue address',
			'city'          => 'Venue city',
			'country'       => 'Venue country',
			'province'      => 'Venue province',
			'state'         => 'Venue state',
			'stateprovince' => 'Venue stateprovince',
			'zip'           => 'Venue zip',
			'phone'         => 'Venue phone',
			'website'       => 'http://venue.com',
		),
	);

	public const MOCKUP_EVENTS = array(
		'minimal_event' => array(
			'title'      => 'My Event',
			'content'    => 'Come to my event. Let\'s connect!',
			'start_date' => '+10 days 15:00:00',
			'duration'   => HOUR_IN_SECONDS,
			'status'     => 'publish',
		),
		'complex_event' => array(
			'title'      => 'My Event',
			'content'    => 'Come to my event. Let\'s connect!',
			'start_date' => '+10 days 15:00:00',
			'duration'   => HOUR_IN_SECONDS,
			'status'     => 'publish',
		),
	);

	public const MOCKUP_CATEGORIES = array(
		'concert' => array(
			'cat_name'             => 'concert',
			'category_description' => 'Mostly live concerts',
			'category_nicename'    => 'Concert',
			'taxonomy'             => 'tribe_events_cat',
		),
		'theatre' => array(
			'cat_name'             => 'theatre',
			'category_description' => 'Theatre shows',
			'category_nicename'    => 'Theatre',
			'taxonomy'             => 'tribe_events_cat',
		),
	);

	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( '\Tribe__Events__Main' ) ) {
			self::markTestSkipped( 'The Events Calendar plugin is not active.' );
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
		$this->assertContains( 'tribe_events', get_option( 'activitypub_support_post_types' ) );

		// Create a The Events Calendar Event without content.
		$wp_object = tribe_events()
			->set_args( self::MOCKUP_EVENTS['minimal_event'] )
			->create();

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \Activitypub_Event_Extensions\Activitypub\Transformer\The_Events_Calendar::class, $transformer );
	}

	/**
	 * Test transformation of minimal event without venue.
	 */
	public function test_transform_of_minimal_event_without_venue() {
		// Create a The Events Calendar Event.
		$wp_object = tribe_events()
			->set_args( self::MOCKUP_EVENTS['minimal_event'] )
			->create();

		// Call the transformer.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( $wp_object )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'My Event', $event_array['name'] );
		$this->assertEquals( '', $event_array['content'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertTrue( $event_array['commentsEnabled'] );
		$this->assertEquals( 'allow_all', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'free', $event_array['joinMode'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
	}

	/**
	 * Test transformation of event with mapped category.
	 */
	public function test_transform_event_with_mapped_categories() {
		// Create category.
		$category_id_music   = wp_insert_category( self::MOCKUP_CATEGORIES['concert'] );
		$category_id_theatre = wp_insert_category( self::MOCKUP_CATEGORIES['theatre'] );

		// Set default mapping for event categories.
		update_option( 'activitypub_event_extensions_default_event_category', 'MUSIC' );

		// Set an override for the category with the slug theatre.
		update_option( 'activitypub_event_extensions_event_category_mappings', array( 'theatre' => 'THEATRE' ) );

		// Create a The Events Calendar event with the music category.
		$wp_object = tribe_events()
			->set_args( self::MOCKUP_EVENTS['minimal_event'] )
			->set( 'category', array( $category_id_music ) )
			->create();
		// Call the transformer.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( $wp_object )->to_object()->to_array();
		// See if the default category mapping is applied.
		$this->assertEquals( 'MUSIC', $event_array['category'] );

		// Create a The Events Calendar event with the theatre category.
		$wp_object = tribe_events()
			->set_args( self::MOCKUP_EVENTS['minimal_event'] )
			->set( 'category', array( $category_id_theatre ) )
			->create();

		// Call the transformer.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( $wp_object )->to_object()->to_array();

		// See if the default category mapping is applied.
		$this->assertEquals( 'THEATRE', $event_array['category'] );
	}

	/**
	 * Test transformation of  minimal event with minimal venue.
	 */
	public function test_transform_of_minimal_event_with_venue() {
		// Create Venue.
		$venue = tribe_venues()->set_args( self::MOCKUP_VENUS['minimal_venue'] )->create();
		// Create a The Events Calendar Event.
		$wp_object = tribe_events()
			->set_args( self::MOCKUP_EVENTS['complex_event'] )
			->set( 'venue', $venue->ID )
			->create();

		// Call the transformer.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( $wp_object )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'My Event', $event_array['name'] );
		$this->assertEquals( '', $event_array['content'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 15:00:00' ) ) . 'T15:00:00Z', $event_array['startTime'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['commentsEnabled'] );
		$this->assertEquals( gmdate( 'Y-m-d', strtotime( '+10 days 16:00:00' ) ) . 'T16:00:00Z', $event_array['endTime'] );
		$this->assertArrayHasKey( 'location', $event_array );
		$this->assertEquals( 'Place', $event_array['location']['type'] );
		$this->assertEquals( self::MOCKUP_VENUS['minimal_venue']['venue'], $event_array['location']['name'] );
	}
}
