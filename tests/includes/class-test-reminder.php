<?php
/**
 * Test for Reminder class.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Tests;

require_once ACTIVITYPUB_PLUGIN_DIR . '/tests/class-activitypub-testcase-cache-http.php';

use Activitypub\Tests\ActivityPub_TestCase_Cache_HTTP;
use Event_Bridge_for_ActivityPub\Reminder;

/**
 * Test class for testing the scheduling of reminder Activities.
 */
class Test_Reminder extends ActivityPub_TestCase_Cache_HTTP {
	/**
	 * Mockup events of certain complexity.
	 */
	public const MOCKUP_VENUE = array(
		'venue'  => 'Minimal Venue',
		'status' => 'publish',
	);

	public const MOCKUP_EVENT = array(
		'title'      => 'My Event',
		'content'    => 'Come to my event!',
		'start_date' => '+10 days 15:00:00',
		'duration'   => HOUR_IN_SECONDS,
		'status'     => 'publish',
	);

	/**
	 * Users.
	 *
	 * @var array[] $users
	 */
	public static $users = array(
		'username@example.org' => array(
			'id'                => 'https://example.org/users/username',
			'url'               => 'https://example.org/users/username',
			'inbox'             => 'https://example.org/users/username/inbox',
			'name'              => 'username',
			'preferredUsername' => 'username',
		),
		'jon@example.com'      => array(
			'id'                => 'https://example.com/author/jon',
			'url'               => 'https://example.com/author/jon',
			'inbox'             => 'https://example.com/author/jon/inbox',
			'name'              => 'jon',
			'preferredUsername' => 'jon',
		),
	);

	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( '\Tribe__Events__Main' ) ) {
			self::markTestSkipped( 'The Events Calendar is not active.' );
		}

		// For tests allow every user to create new events.
		\update_option( 'dbem_events_anonymous_submissions', true );

		// Make sure that ActivityPub support is enabled for Events Manager.
		$aec = \Event_Bridge_For_ActivityPub\Setup::get_instance();
		$aec->activate_activitypub_support_for_active_event_plugins();

		\Activitypub\Migration::add_default_settings();

		// Delete all posts afterwards.
		\_delete_all_posts();
	}

	/**
	 * Test that with the default reminder setting (time-gap is zero) no reminder event is scheduled.
	 */
	public function test_event_reminder_not_being_scheduled_by_default() {
		// Create a The Events Calendar Event.
		$wp_object = \tribe_events()
			->set_args( self::MOCKUP_EVENT )
			->create();

		$scheduled_event = \wp_get_scheduled_event( 'event_bridge_for_activitypub_send_event_reminder', array( $wp_object->ID ) );

		$this->assertEquals( false, $scheduled_event );
	}

	/**
	 * Test that with a side-wide option the reminder is scheduled.
	 */
	public function test_event_reminder_scheduled_with_site_wide_option() {
		\update_option( 'event_bridge_for_activitypub_reminder_time_gap', DAY_IN_SECONDS );
		// Create a The Events Calendar Event.
		$wp_object = \tribe_events()
			->set_args( self::MOCKUP_EVENT )
			->create();

		$scheduled_event = \wp_get_scheduled_event( 'event_bridge_for_activitypub_send_event_reminder', array( $wp_object->ID ) );

		$this->assertNotEquals( false, $scheduled_event );
		$this->assertEquals( strtotime( self::MOCKUP_EVENT['start_date'] ) - DAY_IN_SECONDS, $scheduled_event->timestamp );
		$this->assertEquals( false, $scheduled_event->schedule );
		$this->assertEquals( 'event_bridge_for_activitypub_send_event_reminder', $scheduled_event->hook );
	}

	/**
	 * Test that a specific event can override the side-wide reminder default.
	 */
	public function test_event_reminder_scheduled_with_per_event_override() {
		\update_option( 'event_bridge_for_activitypub_reminder_time_gap', DAY_IN_SECONDS );

		// Create a The Events Calendar Event.
		$wp_object = tribe_events()
			->set_args(
				array_merge(
					self::MOCKUP_EVENT,
					array( 'event_bridge_for_activitypub_reminder_time_gap' => DAY_IN_SECONDS * 3 ),
				)
			)
			->create();

		$scheduled_event = \wp_get_scheduled_event( 'event_bridge_for_activitypub_send_event_reminder', array( $wp_object->ID ) );

		$this->assertNotEquals( false, $scheduled_event );
		$this->assertEquals( strtotime( self::MOCKUP_EVENT['start_date'] ) - DAY_IN_SECONDS * 3, $scheduled_event->timestamp );
		$this->assertEquals( false, $scheduled_event->schedule );
		$this->assertEquals( 'event_bridge_for_activitypub_send_event_reminder', $scheduled_event->hook );

		// Now update the option once more to see if the schedule got updated too.
		$post_id = array_key_first(
			\tribe_events( $wp_object->ID )
				->set_args(
					array( 'event_bridge_for_activitypub_reminder_time_gap' => HOUR_IN_SECONDS ),
				)
				->save()
		);

		$scheduled_event = \wp_get_scheduled_event( 'event_bridge_for_activitypub_send_event_reminder', array( $post_id ) );
		$this->assertNotEquals( false, $scheduled_event );
		$this->assertEquals( strtotime( self::MOCKUP_EVENT['start_date'] ) - HOUR_IN_SECONDS, $scheduled_event->timestamp );
	}

	/**
	 * Test that the scheduled reminder is removed when the event is deleted.
	 */
	public function test_event_reminder_deleted_event() {
		\update_option( 'event_bridge_for_activitypub_reminder_time_gap', DAY_IN_SECONDS );

		// Create a The Events Calendar Event.
		$wp_object = \tribe_events()
			->set_args(
				array_merge(
					self::MOCKUP_EVENT,
					array( 'event_bridge_for_activitypub_reminder_time_gap' => DAY_IN_SECONDS * 3 ),
				)
			)
			->create();

		$scheduled_event = \wp_get_scheduled_event( 'event_bridge_for_activitypub_send_event_reminder', array( $wp_object->ID ) );

		$this->assertNotEquals( false, $scheduled_event );
		$this->assertEquals( strtotime( self::MOCKUP_EVENT['start_date'] ) - DAY_IN_SECONDS * 3, $scheduled_event->timestamp );
		$this->assertEquals( false, $scheduled_event->schedule );
		$this->assertEquals( 'event_bridge_for_activitypub_send_event_reminder', $scheduled_event->hook );

		// Now delete the event.
		\tribe_events( $wp_object->ID )->delete();

		$scheduled_event = \wp_get_scheduled_event( 'event_bridge_for_activitypub_send_event_reminder', array( $wp_object->ID ) );
		$this->assertEquals( false, $scheduled_event );
	}

	/**
	 * Test that the scheduled reminder is removed when the event is moved to trash.
	 */
	public function test_event_reminder_event_moved_to_trash() {
		\update_option( 'event_bridge_for_activitypub_reminder_time_gap', DAY_IN_SECONDS );

		// Create a The Events Calendar Event.
		$wp_object = \tribe_events()
			->set_args(
				array_merge(
					self::MOCKUP_EVENT,
					array( 'event_bridge_for_activitypub_reminder_time_gap' => DAY_IN_SECONDS * 3 ),
				)
			)
			->create();

		$scheduled_event = \wp_get_scheduled_event( 'event_bridge_for_activitypub_send_event_reminder', array( $wp_object->ID ) );

		$this->assertNotEquals( false, $scheduled_event );
		$this->assertEquals( strtotime( self::MOCKUP_EVENT['start_date'] ) - DAY_IN_SECONDS * 3, $scheduled_event->timestamp );
		$this->assertEquals( false, $scheduled_event->schedule );
		$this->assertEquals( 'event_bridge_for_activitypub_send_event_reminder', $scheduled_event->hook );

		// Now move the event to the trash.
		$post_id = array_key_first(
			tribe_events( $wp_object->ID )
				->set_args(
					array( 'post_status' => 'trash' ),
				)
				->save()
		);

		$scheduled_event = \wp_get_scheduled_event( 'event_bridge_for_activitypub_send_event_reminder', array( $wp_object->ID ) );
		$this->assertEquals( false, $scheduled_event );
	}

	/**
	 * Test the schedule action which sends the event reminder.
	 */
	public function test_send_event_reminder() {
		\add_filter( 'pre_get_remote_metadata_by_actor', array( get_called_class(), 'pre_get_remote_metadata_by_actor' ), 10, 2 );

		$followers = array( 'https://example.com/author/jon', 'https://example.org/users/username' );

		\add_filter(
			'activitypub_is_user_type_disabled',
			function ( $value, $type ) {
				if ( 'blog' === $type ) {
					return false;
				} else {
					return true;
				}
			},
			10,
			2
		);

		$this->assertTrue( \Activitypub\is_single_user() );

		foreach ( $followers as $follower ) {
			\Activitypub\Collection\Followers::add_follower( \Activitypub\Collection\Actors::BLOG_USER_ID, $follower );
		}

		// Create a The Events Calendar Event.
		$wp_object = \tribe_events()
			->set_args(
				array_merge(
					self::MOCKUP_EVENT,
					array( 'event_bridge_for_activitypub_reminder_time_gap' => DAY_IN_SECONDS * 3 ),
				)
			)
			->create();

		$pre_http_request = new \MockAction();
		\add_filter( 'pre_http_request', array( $pre_http_request, 'filter' ), 10, 3 );

		Reminder::send_event_reminder( $wp_object );

		$post              = $this->get_latest_outbox_item();
		$event_transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		$activity_object = \json_decode( $post->post_content, true );
		$this->assertArrayHasKey( 'id', $activity_object );
		$this->assertEquals( $event_transformer->get_id(), $activity_object['id'] );
		$this->assertEquals( 'Announce', \get_post_meta( $post->ID, '_activitypub_activity_type', true ) );

		\remove_filter( 'pre_http_request', array( $pre_http_request, 'filter' ), 10 );
		\remove_filter( 'pre_get_remote_metadata_by_actor', array( get_called_class(), 'pre_get_remote_metadata_by_actor' ) );
	}

	/**
	 * Filters remote metadata by actor.
	 *
	 * @param array|bool $pre The metadata for the given URL.
	 * @param string     $actor The URL of the actor.
	 * @return array|bool
	 */
	public static function pre_get_remote_metadata_by_actor( $pre, $actor ) {
		if ( isset( self::$users[ $actor ] ) ) {
			return self::$users[ $actor ];
		}
		foreach ( self::$users as $data ) {
			if ( $data['url'] === $actor ) {
				return $data;
			}
		}
		return $pre;
	}

	/**
	 * Filters the arguments used in an HTTP request.
	 *
	 * @param array  $args The arguments for the HTTP request.
	 * @param string $url  The request URL.
	 * @return array
	 */
	public static function http_request_args( $args, $url ) {
		if ( in_array( wp_parse_url( $url, PHP_URL_HOST ), array( 'example.com', 'example.org' ), true ) ) {
			$args['reject_unsafe_urls'] = false;
		}
		return $args;
	}

	/**
	 * Filters the return value of an HTTP request.
	 *
	 * @param bool   $preempt Whether to preempt an HTTP request's return value.
	 * @param array  $request {
	 *      Array of HTTP request arguments.
	 *
	 *      @type string $method Request method.
	 *      @type string $body   Request body.
	 * }
	 * @param string $url The request URL.
	 * @return array Array containing 'headers', 'body', 'response'.
	 */
	public static function pre_http_request( $preempt, $request, $url ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return array(
			'headers'  => array(
				'content-type' => 'text/json',
			),
			'body'     => '',
			'response' => array(
				'code' => 202,
			),
		);
	}

	/**
	 * Filters the return value of an HTTP request.
	 *
	 * @param array  $response Response array.
	 * @param array  $args     Request arguments.
	 * @param string $url      Request URL.
	 * @return array
	 */
	public static function http_response( $response, $args, $url ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $response;
	}

	/**
	 * Retrieve the latest Outbox item to compare against.
	 *
	 * @param string $title Title of the Outbox item.
	 * @return int|\WP_Post|null
	 */
	protected function get_latest_outbox_item( $title = '' ) {
		$outbox = \get_posts(
			array(
				'post_type'      => \Activitypub\Collection\Outbox::POST_TYPE,
				'posts_per_page' => 1,
				'post_status'    => 'pending',
				'post_title'     => $title,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		return $outbox ? $outbox[0] : null;
	}
}
