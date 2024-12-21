<?php
/**
 * Test file for Activitypub Shortcodes.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPup\Tests\ActivityPub\Transformer;

use Event_Bridge_For_ActivityPup\Tests\ActivityPub\Transformer\Test_The_Events_Calendar;

/**
 * Test class for Shortcodes.
 */
class Test_Event extends \WP_UnitTestCase {
	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( '\Tribe__Events__Main' ) ) {
			self::markTestSkipped( 'The Events Calendar plugin is needed to test Event Shortcodes' );
		}

		// Make sure that ActivityPub support is enabled for The Events Calendar.
		$aec = \Event_Bridge_For_ActivityPub\Setup::get_instance();
		$aec->activate_activitypub_support_for_active_event_plugins();

		// Delete all posts afterwards.
		_delete_all_posts();
	}

	/**
	 * Test the shortcode for rendering the events start time.
	 */
	public function test_start_time() {
		// Create a The Events Calendar Event without content.
		$wp_object = tribe_events()
			->set_args( Test_The_Events_Calendar::MOCKUP_EVENTS['minimal_event'] )
			->create();

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		if ( ! $transformer instanceof \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event ) {
			return;
		}

		$transformer->register_shortcodes();

		$summary = '[ap_start_time]';
		$summary = \do_shortcode( $summary );
		$this->assertEquals( '🗓️ Start: December 1, 2024 3:00 pm', $summary );

		$summary = '[ap_start_time icon="false"]';
		$summary = \do_shortcode( $summary );
		$this->assertEquals( 'Start: December 1, 2024 3:00 pm', $summary );

		$summary = '[ap_start_time icon="false" label="false"]';
		$summary = \do_shortcode( $summary );
		$this->assertEquals( 'December 1, 2024 3:00 pm', $summary );

		$transformer->unregister_shortcodes();
	}

	/**
	 * Test the shortcode for rendering the events end time.
	 */
	public function test_end_time() {
		// Create a The Events Calendar Event without content.
		$wp_object = tribe_events()
			->set_args( Test_The_Events_Calendar::MOCKUP_EVENTS['minimal_event'] )
			->create();

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		if ( ! $transformer instanceof \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event ) {
			return;
		}

		$transformer->register_shortcodes();

		$summary = '[ap_end_time]';
		$summary = \do_shortcode( $summary );
		$this->assertEquals( '⏳ End: December 1, 2024 4:00 pm', $summary );

		$summary = '[ap_end_time icon="false"]';
		$summary = \do_shortcode( $summary );
		$this->assertEquals( 'End: December 1, 2024 4:00 pm', $summary );

		$summary = '[ap_end_time icon="false" label="false"]';
		$summary = \do_shortcode( $summary );
		$this->assertEquals( 'December 1, 2024 4:00 pm', $summary );

		$transformer->unregister_shortcodes();
	}

	/**
	 * Test the shortcode for rendering the events location when no location is set.
	 */
	public function test_location_when_no_location_is_set() {
		// Create a The Events Calendar Event without content.
		$wp_object = tribe_events()
			->set_args( Test_The_Events_Calendar::MOCKUP_EVENTS['minimal_event'] )
			->create();

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		if ( ! $transformer instanceof \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event ) {
			return;
		}

		$transformer->register_shortcodes();

		$summary = '[ap_location]';
		$summary = do_shortcode( $summary );
		$this->assertEquals( '', $summary );

		$transformer->unregister_shortcodes();
	}

	/**
	 * Test the shortcode for rendering the events location when location is set.
	 */
	public function test_location_when_location_is_set() {
		// Create Venue.
		$venue = tribe_venues()->set_args( Test_The_Events_Calendar::MOCKUP_VENUS['minimal_venue'] )->create();
		// Create a The Events Calendar Event.
		$wp_object = tribe_events()
			->set_args( Test_The_Events_Calendar::MOCKUP_EVENTS['complex_event'] )
			->set( 'venue', $venue->ID )
			->create();

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		if ( ! $transformer instanceof \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event ) {
			return;
		}

		$transformer->register_shortcodes();

		$summary = '[ap_location]';
		$summary = do_shortcode( $summary );
		$this->assertEquals( '📍 Location: Minimal Venue', $summary );

		$summary = '[ap_location icon="false"]';
		$summary = do_shortcode( $summary );
		$this->assertEquals( 'Location: Minimal Venue', $summary );

		$summary = '[ap_location icon="false" label="false"]';
		$summary = do_shortcode( $summary );
		$this->assertEquals( 'Minimal Venue', $summary );

		$transformer->unregister_shortcodes();
	}

	/**
	 * Test the shortcode for rendering the events location when location with detailed address is set.
	 */
	public function test_location_when_detailed_location_is_set() {
		// Create Venue.
		$venue = tribe_venues()->set_args( Test_The_Events_Calendar::MOCKUP_VENUS['complex_venue'] )->create();
		// Create a The Events Calendar Event.
		$wp_object = tribe_events()
			->set_args( Test_The_Events_Calendar::MOCKUP_EVENTS['complex_event'] )
			->set( 'venue', $venue->ID )
			->create();

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		if ( ! $transformer instanceof \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event ) {
			return;
		}

		$transformer->register_shortcodes();

		$summary = '[ap_location]';
		$summary = do_shortcode( $summary );
		$this->assertEquals( '📍 Location: Complex Venue, Venue address, Venue zip, Venue city, Venue country', $summary );

		$summary = '[ap_location country="false"]';
		$summary = do_shortcode( $summary );
		$this->assertEquals( '📍 Location: Complex Venue, Venue address, Venue zip, Venue city', $summary );

		$transformer->unregister_shortcodes();
	}
}
