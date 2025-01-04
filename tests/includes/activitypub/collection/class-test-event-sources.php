<?php
/**
 * Test file for the Event Sources collection.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests\ActivityPub\Collection;

use WP_REST_Server;

/**
 * Test class for the Event Sources collection.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\ActivityPub\Collections\Event_Sources
 */
class Test_Event_Sources extends \WP_UnitTestCase {
	const FOLLOWED_ACTOR_1 = array(
		'id'      => 'https://remote.example/@organizer',
		'type'    => 'Person',
		'inbox'   => 'https://remote.example/@organizer/inbox',
		'outbox'  => 'https://remote.example/@organizer/outbox',
		'name'    => 'The Organizer',
		'summary' => 'Just a random organizer of events in the Fediverse',
	);

	const FOLLOWED_ACTOR_2 = array(
		'id'      => 'https://remote.example/@organizer2',
		'type'    => 'Person',
		'inbox'   => 'https://remote.example/@organizer2/inbox',
		'outbox'  => 'https://remote.example/@organizer2/outbox',
		'name'    => 'The Second Organizer',
		'summary' => 'Just a second random organizer of events in the Fediverse',
	);

	/**
	 * REST Server.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Set up the test.
	 */
	public function set_up() {
		if ( ! defined( 'GATHERPRESS_CORE_FILE' ) ) {
			self::markTestSkipped( 'GatherPress plugin is not active.' );
		}

		\add_option( 'permalink_structure', '/%postname%/' );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		\Activitypub\Rest\Server::add_hooks();

		// Mock the plugin activation.
		\GatherPress\Core\Setup::get_instance()->activate_gatherpress_plugin( false );

		// Make sure that ActivityPub support is enabled for GatherPress.
		$aec = \Event_Bridge_For_ActivityPub\Setup::get_instance();
		$aec->activate_activitypub_support_for_active_event_plugins();

		// Add event sources (ActivityPub followers).
		_delete_all_posts();
		\Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source::init_from_array( self::FOLLOWED_ACTOR_1 )->save();
		\Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source::init_from_array( self::FOLLOWED_ACTOR_2 )->save();

		\update_option( 'event_bridge_for_activitypub_event_sources_active', true );
		\update_option(
			'event_bridge_for_activitypub_integration_used_for_event_sources_feature',
			\Event_Bridge_For_ActivityPub\Integrations\GatherPress::class
		);
		\update_option( 'activitypub_actor_mode', ACTIVITYPUB_BLOG_MODE );
	}

	/**
	 * Testing the fetching of event sources from the database.
	 *
	 * @covers \Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources::get_event_sources_with_count
	 * @covers \Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources::get_event_sources
	 */
	public function test_get_event_sources_with_count() {
		\delete_transient( 'event_bridge_for_activitypub_event_sources' );
		$event_sources = \Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources::get_event_sources();

		$this->assertCount( 2, $event_sources );

		$this->assertArrayHasKey( self::FOLLOWED_ACTOR_1['id'], $event_sources );
		$this->assertArrayHasKey( self::FOLLOWED_ACTOR_2['id'], $event_sources );
	}
}
