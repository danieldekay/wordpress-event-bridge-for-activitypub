<?php
/**
 * Class file containing tests for the ActivityPub transformer of the WordPress plugin The Events Calendar.
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPup\Tests\ActivityPub\Transformer\Event;

/**
 * Class containing tests for the ActivityPub transformer of the WordPress plugin The Events Calendar.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\The_Events_Calendar
 */
class Test_The_Events_Calendar extends \WP_UnitTestCase {
	/**
	 * Mockup events of certain complexity.
	 */
	public const MOCKUP_VENUS = array(
		'minimal_venue' => array(
			'venue'  => 'Minimal Venue',
			'status' => 'publish',
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
			'content'    => 'Come to my event!',
			'start_date' => '+10 days 15:00:00',
			'duration'   => HOUR_IN_SECONDS,
			'status'     => 'publish',
		),
		'complex_event' => array(
			'title'      => 'My Event',
			'content'    => 'Come to my event!',
			'start_date' => '+10 days 15:00:00',
			'duration'   => HOUR_IN_SECONDS,
			'status'     => 'publish',
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
		$aec = \Event_Bridge_For_ActivityPub\Setup::get_instance();
		$aec->activate_activitypub_support_for_active_event_plugins();

		// Delete all posts afterwards.
		_delete_all_posts();
	}

	/**
	 * Test that the right transformer gets applied.
	 */
	public function test_the_events_calendar_transformer_class() {
		// We only test for one event plugin being active at the same time,
		// even though we support multiple onces in theory.
		// But testing all combinations is beyond scope.
		$active_event_plugins = \Event_Bridge_For_ActivityPub\Setup::get_instance()->get_active_event_plugins();
		$this->assertArrayHasKey( 'the-events-calendar/the-events-calendar.php', $active_event_plugins );

		// Enable ActivityPub support for the event plugin.
		$this->assertContains( 'tribe_events', get_option( 'activitypub_support_post_types' ) );

		// Create a The Events Calendar Event without content.
		$wp_object = tribe_events()
			->set_args( self::MOCKUP_EVENTS['minimal_event'] )
			->create();

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\The_Events_Calendar::class, $transformer );
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
		$this->assertEquals( 'Come to my event!', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( strtotime( '+10 days 15:00:00' ), strtotime( $event_array['startTime'] ) );
		$this->assertEquals( strtotime( '+10 days 16:00:00' ), strtotime( $event_array['endTime'] ) );
		$this->assertTrue( $event_array['commentsEnabled'] );
		$this->assertEquals( 'allow_all', $event_array['repliesModerationOption'] );
		$this->assertEquals( 'external', $event_array['joinMode'] );
		$this->assertArrayNotHasKey( 'location', $event_array );
		$this->assertEquals( 'MEETING', $event_array['category'] );
	}

	/**
	 * Test transformation of event with mapped category.
	 */
	public function test_transform_event_with_mapped_categories() {
		// Create category.
		$category_id_music   = wp_insert_term( 'Music', \Tribe__Events__Main::TAXONOMY, array( 'slug' => 'music' ) );
		$category_id_theatre = wp_insert_term( 'Theatre', \Tribe__Events__Main::TAXONOMY, array( 'slug' => 'theatre' ) );

		// Set default mapping for event categories.
		\update_option( 'event_bridge_for_activitypub_default_event_category', 'MUSIC' );

		// Set an override for the category with the slug theatre.
		\update_option( 'event_bridge_for_activitypub_event_category_mappings', array( 'theatre' => 'THEATRE' ) );

		// Create a The Events Calendar event with the music category.
		$wp_object = tribe_events()
			->set_args( self::MOCKUP_EVENTS['minimal_event'] )
			->create();
		// Set the post term music to the event.
		wp_set_post_terms( $wp_object->ID, $category_id_music['term_id'], \Tribe__Events__Main::TAXONOMY );
		// Call the transformer.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( $wp_object )->to_object()->to_array();
		// See if the default category mapping is applied.
		$this->assertEquals( 'MUSIC', $event_array['category'] );

		// Set the post term theatre to the event.
		wp_set_post_terms( $wp_object->ID, $category_id_theatre['term_id'], \Tribe__Events__Main::TAXONOMY, false );
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
		$this->assertEquals( 'Come to my event!', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( strtotime( '+10 days 15:00:00' ), strtotime( $event_array['startTime'] ) );
		$this->assertEquals( strtotime( '+10 days 16:00:00' ), strtotime( $event_array['endTime'] ) );
		$this->assertArrayHasKey( 'location', $event_array );
		$this->assertEquals( 'Place', $event_array['location']['type'] );
		$this->assertEquals( self::MOCKUP_VENUS['minimal_venue']['venue'], $event_array['location']['name'] );
	}

	/**
	 * Test transformation of  minimal event with fully filled venue.
	 */
	public function test_transform_of_minimal_event_with_address_venue() {
		// Create Venue.
		$venue = tribe_venues()->set_args( self::MOCKUP_VENUS['complex_venue'] )->create();
		// Create a The Events Calendar Event.
		$wp_object = tribe_events()
			->set_args( self::MOCKUP_EVENTS['minimal_event'] )
			->set( 'venue', $venue->ID )
			->create();

		// Call the transformer.

		$event_array = \Activitypub\Transformer\Factory::get_transformer( $wp_object )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'My Event', $event_array['name'] );
		$this->assertEquals( 'Come to my event!', wp_strip_all_tags( $event_array['content'] ) );
		$this->assertEquals( strtotime( '+10 days 15:00:00' ), strtotime( $event_array['startTime'] ) );
		$this->assertEquals( strtotime( '+10 days 16:00:00' ), strtotime( $event_array['endTime'] ) );
		$this->assertArrayHasKey( 'location', $event_array );
		$this->assertEquals( 'Place', $event_array['location']['type'] );
		$this->assertEquals( 'PostalAddress', $event_array['location']['address']['type'] );
		$this->assertEquals( self::MOCKUP_VENUS['complex_venue']['venue'], $event_array['location']['name'] );
		$this->assertEquals( self::MOCKUP_VENUS['complex_venue']['province'], $event_array['location']['address']['addressRegion'] );
		$this->assertEquals( self::MOCKUP_VENUS['complex_venue']['address'], $event_array['location']['address']['streetAddress'] );
		$this->assertEquals( self::MOCKUP_VENUS['complex_venue']['city'], $event_array['location']['address']['addressLocality'] );
		$this->assertEquals( self::MOCKUP_VENUS['complex_venue']['country'], $event_array['location']['address']['addressCountry'] );
		$this->assertEquals( self::MOCKUP_VENUS['complex_venue']['zip'], $event_array['location']['address']['postalCode'] );
	}

	/**
	 * Test transformation of  minimal event with fully filled venue.
	 */
	public function test_transform_of__event_with_custom_timezone(): void {
		$args = self::MOCKUP_EVENTS['minimal_event'];

		$timezone_string   = 'Europe/Vienna';
		$start_time_string = '+10 days 15:00:00';
		$end_time_string   = '+10 days 16:00:00';

		$timezone   = new \DateTimeZone( $timezone_string );
		$start_time = new \DateTime( $start_time_string, $timezone );
		$end_time   = new \DateTime( $end_time_string, $timezone );

		// Event with timezone information.
		$args = array(
			'title'      => 'My Event',
			'content'    => 'Come to my event!',
			'start_date' => $start_time,
			'duration'   => HOUR_IN_SECONDS,
			'status'     => 'publish',
			'timezone'   => $timezone_string,
		);

		// Create a The Events Calendar Event.
		$wp_object = tribe_events()->set_args( $args )->create();

		// Call the transformer.
		$event_array = \Activitypub\Transformer\Factory::get_transformer( $wp_object )->to_object()->to_array();

		// Check that the event ActivityStreams representation contains everything as expected.
		$this->assertEquals( 'Event', $event_array['type'] );
		$this->assertEquals( 'My Event', $event_array['name'] );
		$this->assertEquals( $timezone_string, $event_array['timezone'] );
		$this->assertEquals( $start_time->format( 'Y-m-d\TH:i:sP' ), $event_array['startTime'] );
		$this->assertEquals( $end_time->format( 'Y-m-d\TH:i:sP' ), $event_array['endTime'] );
	}
}
