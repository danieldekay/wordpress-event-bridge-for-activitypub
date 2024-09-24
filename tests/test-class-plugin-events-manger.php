<?php
/**
 * Class SampleTest
 *
 * @package Activitypub_Event_Extensions
 */

/**
 * Sample test case.
 */
class Test_Events_Manager extends WP_UnitTestCase {
	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( 'EM_Events' ) ) {
			self::markTestSkipped( 'VS Event List plugin is not active.' );
		}

		// Make sure that ActivityPub support is enabled for Events Manager.
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
		$this->assertContains( EM_POST_TYPE_EVENT, get_option( 'activitypub_support_post_types' ) );

		// Insert a new Event.
		$wp_post_id = wp_insert_post(
			array(
				'post_title'  => 'Events Manager Test event',
				'post_status' => 'published',
				'post_type'   => EM_POST_TYPE_EVENT,
				'meta_input'  => array(
					'event_start_time' => strtotime( '+10 days 15:00:00' ),
				),
			)
		);

		$wp_object = get_post( $wp_post_id );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \Activitypub_Event_Extensions\Activitypub\Transformer\Events_Manager::class, $transformer );
	}
}
