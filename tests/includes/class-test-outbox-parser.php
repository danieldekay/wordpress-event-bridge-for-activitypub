<?php
/**
 * Test file for the Outbox Parser Library.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests;

use WP_REST_Server;

/**
 * Test class for the Outbox Parser Library.
 *
 * @coversDefaultClass \Event_Bridge_For_ActivityPub\Outbox_Parser
 */
class Test_Outbox_Parser extends \WP_UnitTestCase {
	const FOLLOWED_ACTOR = array(
		'id'      => 'https://remote.example/@organizer',
		'type'    => 'Person',
		'inbox'   => 'https://remote.example/@organizer/inbox',
		'outbox'  => 'https://remote.example/@organizer/outbox',
		'name'    => 'The Organizer',
		'summary' => 'Just a random organizer of events in the Fediverse',
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

		// Add event source (ActivityPub follower).
		_delete_all_posts();
		\Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source::init_from_array( self::FOLLOWED_ACTOR )->save();

		\update_option( 'event_bridge_for_activitypub_event_sources_active', true );
		\update_option(
			'event_bridge_for_activitypub_integration_used_for_event_sources_feature',
			\Event_Bridge_For_ActivityPub\Integrations\GatherPress::class
		);
		\update_option( 'activitypub_actor_mode', ACTIVITYPUB_BLOG_MODE );
	}

	/**
	 * Tear down the test.
	 */
	public function tear_down() {
		\delete_option( 'permalink_structure' );
	}

	/**
	 * Test the import of events from an items array of an outbox.
	 */
	public function test_import_events_from_items() {
		$items = array(
			array(
				'id'     => 'https://remote.example/@organizer/events/concert#create',
				'type'   => 'Create',
				'actor'  => self::FOLLOWED_ACTOR['id'],
				'object' => array(
					'id'        => 'https://remote.example/@organizer/events/concert',
					'type'      => 'Event',
					'startTime' => \gmdate( 'Y-m-d\TH:i:s\Z', time() + WEEK_IN_SECONDS ),
					'endTime'   => \gmdate( 'Y-m-d\TH:i:s\Z', time() + WEEK_IN_SECONDS + HOUR_IN_SECONDS ),
					'name'      => 'Concert',
					'to'        => 'https://www.w3.org/ns/activitystreams#Public',
					'published' => \gmdate( 'Y-m-d\TH:i:s\Z', time() - WEEK_IN_SECONDS ),
				),
			),
			array(
				'id'     => 'https://remote.example/@organizer/events/birthday-party#create',
				'type'   => 'Create',
				'actor'  => self::FOLLOWED_ACTOR['id'],
				'object' => array(
					'id'        => 'https://remote.example/@organizer/events/birthday-party',
					'type'      => 'Event',
					'startTime' => \gmdate( 'Y-m-d\TH:i:s\Z', time() + 2 * WEEK_IN_SECONDS ),
					'endTime'   => \gmdate( 'Y-m-d\TH:i:s\Z', time() + 2 * WEEK_IN_SECONDS + HOUR_IN_SECONDS ),
					'name'      => 'Birthday Party',
					'to'        => 'https://www.w3.org/ns/activitystreams#Public',
					'published' => \gmdate( 'Y-m-d\TH:i:s\Z', time() - WEEK_IN_SECONDS ),
				),
			),
			array(
				'id'     => 'https://remote.example/@organizer/events/status/1#create',
				'type'   => 'Create',
				'actor'  => self::FOLLOWED_ACTOR['id'],
				'object' => array(
					'id'        => 'https://remote.example/@organizer/status/1',
					'type'      => 'Note',
					'content'   => 'This is a note',
					'to'        => 'https://www.w3.org/ns/activitystreams#Public',
					'published' => \gmdate( 'Y-m-d\TH:i:s\Z', time() - WEEK_IN_SECONDS ),
				),
			),
			array(
				'id'     => 'https://remote.example/@organizer/likes/1',
				'type'   => 'Like',
				'actor'  => self::FOLLOWED_ACTOR['id'],
				'object' => 'https://remote2.example/@actor/status/1',
			),
			array(
				'id'     => 'https://remote.example/@organizer/shares/1',
				'type'   => 'Announce',
				'actor'  => self::FOLLOWED_ACTOR['id'],
				'object' => 'https://remote2.example/@actor/status/2',
			),
		);

		// The function we want to test is private, so we need a Reflection class.
		$reflection = new \ReflectionClass( \Event_Bridge_For_ActivityPub\Outbox_Parser::class );

		$method = $reflection->getMethod( 'import_events_from_items' );
		$method->setAccessible( true );

		$event_source_post_id = \Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source::get_post_id_by_activitypub_id( self::FOLLOWED_ACTOR['id'] );

		$count = $method->invoke( null, $items, $event_source_post_id );

		$this->assertEquals( 2, $count );

		// Check that we have two event posts.
		$event_query = \GatherPress\Core\Event_Query::get_instance();
		$the_query   = $event_query->get_upcoming_events();
		$this->assertEquals( 2, $the_query->post_count );
	}
}
