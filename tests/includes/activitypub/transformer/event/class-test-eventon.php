<?php
/**
 * Test class for the transformation of the events of the WordPress event plugin EventOn (Lite).
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub\Tests\ActivityPub\Transformer\Event;

/**
 * Test class for the transformation of the events of the WordPress event plugin EventOn (Lite).
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\EventOn
 */
class Test_EventOn extends \WP_UnitTestCase {
	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( 'EventON' ) ) {
			self::markTestSkipped( 'EventON plugin is not active.' );
		}

		// Make sure that ActivityPub support is enabled.
		$aec = \Event_Bridge_For_ActivityPub\Setup::get_instance();
		$aec->activate_activitypub_support_for_active_event_plugins();

		// Delete all posts afterwards.
		\_delete_all_posts();
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
		$this->assertContains( 'ajde_events', get_option( 'activitypub_support_post_types' ) );

		$post_id = \wp_insert_post(
			array(
				'post_title' => 'EventOn Test Event',
				'post_type'  => 'ajde_events',
			)
		);

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( \get_post( $post_id ) );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\EventOn::class, $transformer );
	}
}
