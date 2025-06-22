<?php
/**
 * Test file for the Generic Event Transformer.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests\ActivityPub\Transformer\Event;

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Generic_Event;

/**
 * Test class for the Generic Event Transformer.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Generic_Event
 */
class Test_Generic_Event extends \WP_UnitTestCase {

	/**
	 * Test event transformer with basic field mappings.
	 */
	public function test_transform_event_with_basic_mappings() {
		// Set up field mappings
		$field_mappings = array(
			'start_time' => array(
				'source_type' => 'meta',
				'field_name' => 'event_start_date',
			),
			'end_time' => array(
				'source_type' => 'meta',
				'field_name' => 'event_end_date',
			),
			'location' => array(
				'source_type' => 'meta',
				'field_name' => 'event_location',
			),
		);
		update_option( 'event_bridge_for_activitypub_generic_field_mappings', $field_mappings );

		// Create a test event post
		$post_id = wp_insert_post( array(
			'post_title' => 'Generic Test Event',
			'post_content' => 'Test event content',
			'post_status' => 'publish',
			'post_type' => 'event',
			'meta_input' => array(
				'event_start_date' => strtotime( '+10 days 15:00:00' ),
				'event_end_date' => strtotime( '+10 days 17:00:00' ),
				'event_location' => 'Test Venue, Test City',
			),
		) );

		$post = get_post( $post_id );
		$transformer = new Generic_Event( $post, 'category' );

		// Test start time
		$start_time = $transformer->get_start_time();
		$this->assertIsString( $start_time );
		$this->assertStringContainsString( 'T', $start_time ); // ISO 8601 format

		// Test end time
		$end_time = $transformer->get_end_time();
		$this->assertIsString( $end_time );
		$this->assertStringContainsString( 'T', $end_time ); // ISO 8601 format

		// Test location
		$location = $transformer->get_location();
		$this->assertNotNull( $location );
		$this->assertEquals( 'Place', $location->get_type() );
		$this->assertEquals( 'Test Venue, Test City', $location->get_name() );

		// Clean up
		wp_delete_post( $post_id, true );
		delete_option( 'event_bridge_for_activitypub_generic_field_mappings' );
	}

	/**
	 * Test event transformer with missing mappings.
	 */
	public function test_transform_event_with_missing_mappings() {
		// Clear field mappings
		delete_option( 'event_bridge_for_activitypub_generic_field_mappings' );

		// Create a test event post
		$post_id = wp_insert_post( array(
			'post_title' => 'Generic Test Event Without Mappings',
			'post_content' => 'Test event content',
			'post_status' => 'publish',
			'post_type' => 'event',
		) );

		$post = get_post( $post_id );
		$transformer = new Generic_Event( $post, 'category' );

		// Test start time (should fallback to post date)
		$start_time = $transformer->get_start_time();
		$this->assertIsString( $start_time );
		$this->assertStringContainsString( 'T', $start_time ); // ISO 8601 format

		// Test end time (should be null without mapping)
		$end_time = $transformer->get_end_time();
		$this->assertNull( $end_time );

		// Test location (should be null without mapping)
		$location = $transformer->get_location();
		$this->assertNull( $location );

		// Clean up
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test event transformer with string date values.
	 */
	public function test_transform_event_with_string_dates() {
		// Set up field mappings
		$field_mappings = array(
			'start_time' => array(
				'source_type' => 'meta',
				'field_name' => 'event_start_date_string',
			),
			'end_time' => array(
				'source_type' => 'meta',
				'field_name' => 'event_end_date_string',
			),
		);
		update_option( 'event_bridge_for_activitypub_generic_field_mappings', $field_mappings );

		// Create a test event post with string dates
		$post_id = wp_insert_post( array(
			'post_title' => 'Generic Test Event with String Dates',
			'post_content' => 'Test event content',
			'post_status' => 'publish',
			'post_type' => 'event',
			'meta_input' => array(
				'event_start_date_string' => '2024-12-25 15:00:00',
				'event_end_date_string' => '2024-12-25 17:00:00',
			),
		) );

		$post = get_post( $post_id );
		$transformer = new Generic_Event( $post, 'category' );

		// Test start time parsing
		$start_time = $transformer->get_start_time();
		$this->assertIsString( $start_time );
		$this->assertStringContainsString( '2024-12-25T15:00:00Z', $start_time );

		// Test end time parsing
		$end_time = $transformer->get_end_time();
		$this->assertIsString( $end_time );
		$this->assertStringContainsString( '2024-12-25T17:00:00Z', $end_time );

		// Clean up
		wp_delete_post( $post_id, true );
		delete_option( 'event_bridge_for_activitypub_generic_field_mappings' );
	}

	/**
	 * Test event transformer with post field source type.
	 */
	public function test_transform_event_with_post_field_source() {
		// Set up field mappings using post fields
		$field_mappings = array(
			'summary' => array(
				'source_type' => 'post_field',
				'field_name' => 'post_excerpt',
			),
		);
		update_option( 'event_bridge_for_activitypub_generic_field_mappings', $field_mappings );

		// Create a test event post
		$post_id = wp_insert_post( array(
			'post_title' => 'Generic Test Event with Post Field',
			'post_content' => 'Test event content',
			'post_excerpt' => 'Custom event summary',
			'post_status' => 'publish',
			'post_type' => 'event',
		) );

		$post = get_post( $post_id );
		$transformer = new Generic_Event( $post, 'category' );

		// Test summary
		$summary = $transformer->get_summary();
		$this->assertStringContainsString( 'Custom event summary', $summary );

		// Clean up
		wp_delete_post( $post_id, true );
		delete_option( 'event_bridge_for_activitypub_generic_field_mappings' );
	}
}