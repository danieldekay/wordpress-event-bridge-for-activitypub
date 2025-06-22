<?php
/**
 * Test file for the Generic Event Plugin integration.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests\Integrations;

use Event_Bridge_For_ActivityPub\Integrations\Generic_Event_Plugin;

/**
 * Test class for the Generic Event Plugin integration.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\Integrations\Generic_Event_Plugin
 */
class Test_Generic_Event_Plugin extends \WP_UnitTestCase {

	/**
	 * Test that the Generic Event Plugin integration exists.
	 */
	public function test_generic_event_plugin_class_exists() {
		$this->assertTrue( class_exists( 'Event_Bridge_For_ActivityPub\Integrations\Generic_Event_Plugin' ) );
	}

	/**
	 * Test the default post type.
	 */
	public function test_get_post_type_default() {
		$post_type = Generic_Event_Plugin::get_post_type();
		$this->assertEquals( 'event', $post_type );
	}

	/**
	 * Test configurable post type.
	 */
	public function test_get_post_type_configured() {
		update_option( 'event_bridge_for_activitypub_generic_post_type', 'custom_event' );
		$post_type = Generic_Event_Plugin::get_post_type();
		$this->assertEquals( 'custom_event', $post_type );
		
		// Clean up
		delete_option( 'event_bridge_for_activitypub_generic_post_type' );
	}

	/**
	 * Test the default event category taxonomy.
	 */
	public function test_get_event_category_taxonomy_default() {
		$taxonomy = Generic_Event_Plugin::get_event_category_taxonomy();
		$this->assertEquals( 'category', $taxonomy );
	}

	/**
	 * Test configurable event category taxonomy.
	 */
	public function test_get_event_category_taxonomy_configured() {
		update_option( 'event_bridge_for_activitypub_generic_category_taxonomy', 'event_category' );
		$taxonomy = Generic_Event_Plugin::get_event_category_taxonomy();
		$this->assertEquals( 'event_category', $taxonomy );
		
		// Clean up
		delete_option( 'event_bridge_for_activitypub_generic_category_taxonomy' );
	}

	/**
	 * Test plugin name.
	 */
	public function test_get_plugin_name() {
		$name = Generic_Event_Plugin::get_plugin_name();
		$this->assertEquals( 'Generic Event Plugin', $name );
	}

	/**
	 * Test is_enabled method.
	 */
	public function test_is_enabled_default() {
		$enabled = Generic_Event_Plugin::is_enabled();
		$this->assertFalse( $enabled );
	}

	/**
	 * Test is_enabled when configured.
	 */
	public function test_is_enabled_configured() {
		update_option( 'event_bridge_for_activitypub_generic_enabled', true );
		$enabled = Generic_Event_Plugin::is_enabled();
		$this->assertTrue( $enabled );
		
		// Clean up
		delete_option( 'event_bridge_for_activitypub_generic_enabled' );
	}

	/**
	 * Test get_relative_plugin_file method.
	 */
	public function test_get_relative_plugin_file() {
		$plugin_file = Generic_Event_Plugin::get_relative_plugin_file();
		$this->assertEquals( 'generic-event-plugin/generic-event-plugin.php', $plugin_file );
	}

	/**
	 * Test get_settings_pages method.
	 */
	public function test_get_settings_pages() {
		$pages = Generic_Event_Plugin::get_settings_pages();
		$this->assertIsArray( $pages );
		$this->assertContains( 'event-bridge-for-activitypub-generic', $pages );
	}

	/**
	 * Test transformer creation.
	 */
	public function test_get_activitypub_event_transformer() {
		// Create a test post
		$post_id = wp_insert_post( array(
			'post_title' => 'Test Event',
			'post_content' => 'Test event content',
			'post_status' => 'publish',
			'post_type' => 'event',
		) );

		$post = get_post( $post_id );
		$transformer = Generic_Event_Plugin::get_activitypub_event_transformer( $post );
		
		$this->assertInstanceOf( 'Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Generic_Event', $transformer );
		
		// Clean up
		wp_delete_post( $post_id, true );
	}
}