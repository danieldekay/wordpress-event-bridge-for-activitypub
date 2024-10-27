<?php
/**
 * Test class for My Calendar.
 *
 * @package ActivityPub_Event_Bridge
 */

/**
 * Test class for My Calendar.
 */
class Test_My_Calendar extends WP_UnitTestCase {
	/**
	 * Mockup Event.
	 *
	 * @var array
	 */
	private $mockup_event;

	/**
	 * Mockup Location.
	 *
	 * @var array
	 */
	private $mockup_location;

	/**
	 * Mockup Category.
	 *
	 * @var array
	 */
	private $mockup_category;

	/**
	 * Setup mockup data.
	 */
	private function setUpMockupEvents() {
		$this->mockup_event = array(
			// Begin strings.
			'event_begin'        => date( 'Y-m-d', strtotime( '+10 days' ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			'event_end'          => date( 'Y-m-d', strtotime( '+10 days', ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			'event_title'        => 'Demo: Florence Price: Symphony No. 3 in c minor',
			'event_desc'         => "<p>Florence Price's <a href='https://en.wikipedia.org/wiki/Symphony_No._3_(Price)'>Symphony No. 3</a> was commissioned by the Works Progress Administration's <a href='https://en.wikipedia.org/wiki/Federal_Music_Project'>Federal Music Project</a> during the height of the Great Depression. It was first performed at the Detroit Institute of Arts on November 6, 1940, by the Detroit Civic Orchestra under the conductor Valter Poole.</p><p>The composition is Price's third symphony, following her Symphony in E minor—the first symphony by a black woman to be performed by a major American orchestra—and her lost Symphony No. 2.</p>",
			'event_short'        => "Florence Price's Symphony No.3 was first performed on November 6th, 1940. It was Ms. Price's third symphony, following her lost Symphony No. 2",
			'event_time'         => '15:00:00',
			'event_endtime'      => '16:00:00',
			'event_link'         => 'https://www.youtube.com/watch?v=1jgJ1OkjnaI&list=OLAK5uy_lKldgbFTYBDa7WN6jf2ubB595wncDU7yc&index=2',
			'event_recur'        => 'S1',
			'event_image'        => plugins_url( '/.wordpress-org/banner-772x250.jpg', ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_FILE ),
			'event_access'       => '',
			'event_tickets'      => '',
			'event_registration' => '',
			'event_repeats'      => '',
			// Begin integers.
			'event_author'       => wp_get_current_user()->ID,
			'event_category'     => 1,
			'event_link_expires' => 0,
			'event_zoom'         => 16,
			'event_approved'     => 1,
			'event_host'         => wp_get_current_user()->ID,
			'event_flagged'      => 0,
			'event_fifth_week'   => 0,
			'event_holiday'      => 0,
			'event_group_id'     => 1,
			'event_span'         => 0,
			'event_hide_end'     => 0,
			// Array: removed before DB insertion.
			'event_categories'   => array( 1 ),
		);

		$access = array( 1, 2, 3, 4, 6, 8, 9 );

		$this->mockup_location = array(
			'location_label'     => 'Demo: Minnesota Orchestra',
			'location_street'    => '1111 Nicollet Mall',
			'location_street2'   => '',
			'location_city'      => 'Minneapolis',
			'location_state'     => 'MN',
			'location_postcode'  => '55403',
			'location_region'    => '',
			'location_country'   => 'United States',
			'location_url'       => 'https://www.minnesotaorchestra.org',
			'location_latitude'  => '44.9722',
			'location_longitude' => '-93.2749',
			'location_zoom'      => 16,
			'location_phone'     => '612-371-5600',
			'location_phone2'    => '',
			'location_access'    => serialize( $access ),
		);

		$this->mockup_category = array(
			'category_name'  => 'General',
			'category_color' => '#243f82',
			'category_icon'  => 'event.svg',
		);
	}



	/**
	 * Override the setup function, so that tests don't run if the Events Calendar is not active.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'mc_get_event' ) ) {
			self::markTestSkipped( 'My Calendar plugin is not active.' );
		}

		self::setUpMockupEvents();

		// Make sure that ActivityPub support is enabled for The Events Calendar.
		$aec = \ActivityPub_Event_Bridge\Setup::get_instance();
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
		$active_event_plugins = \ActivityPub_Event_Bridge\Setup::get_instance()->get_active_event_plugins();
		$this->assertEquals( 1, count( $active_event_plugins ) );

		// Enable ActivityPub support for the event plugin.
		$this->assertContains( 'mc-events', get_option( 'activitypub_support_post_types' ) );

		// mc_create_category( $this->mockup_category );
		// $location = mc_insert_location( $this->mockup_location );
		// $location = apply_filters( 'mc_save_location', $location, $this->mockup_location, $this->mockup_location );
		// $event    = array( true, false, $this->mockup_event, false, array() );
		// $event    = my_calendar_save( 'add', $event );
		// mc_update_event( 'event_location', (int) $location, $event['event_id'] );

		// Insert a category.
		mc_create_category(
			array(
				'category_name'  => 'General',
				'category_color' => '#243f82',
				'category_icon'  => 'event.svg',
			)
		);
		// Insert a location.
		$access  = array( 1, 2, 3, 4, 6, 8, 9 );
		$add     = array(
			'location_label'     => 'Demo: Minnesota Orchestra',
			'location_street'    => '1111 Nicollet Mall',
			'location_street2'   => '',
			'location_city'      => 'Minneapolis',
			'location_state'     => 'MN',
			'location_postcode'  => '55403',
			'location_region'    => '',
			'location_country'   => 'United States',
			'location_url'       => 'https://www.minnesotaorchestra.org',
			'location_latitude'  => '44.9722',
			'location_longitude' => '-93.2749',
			'location_zoom'      => 16,
			'location_phone'     => '612-371-5600',
			'location_phone2'    => '',
			'location_access'    => serialize( $access ),
		);
		$results = mc_insert_location( $add );
		/**
		 * Executed an action when the demo location is saved at installation.
		 *
		 * @hook mc_save_location
		 *
		 * @param {int|false} $results Result of database insertion. Row ID or false.
		 * @param {array} $add Array of location parameters to add.
		 * @param {array} $add Demo location array.
		 */
		$results = apply_filters( 'mc_save_location', $results, $add, $add );
		// Insert an event.
		$submit = array(
			// Begin strings.
			'event_begin'        => date( 'Y-m-d', strtotime( '+1 day' ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			'event_end'          => date( 'Y-m-d', strtotime( '+1 day' ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			'event_title'        => 'Demo: Florence Price: Symphony No. 3 in c minor',
			'event_desc'         => "<p>Florence Price's <a href='https://en.wikipedia.org/wiki/Symphony_No._3_(Price)'>Symphony No. 3</a> was commissioned by the Works Progress Administration's <a href='https://en.wikipedia.org/wiki/Federal_Music_Project'>Federal Music Project</a> during the height of the Great Depression. It was first performed at the Detroit Institute of Arts on November 6, 1940, by the Detroit Civic Orchestra under the conductor Valter Poole.</p><p>The composition is Price's third symphony, following her Symphony in E minor—the first symphony by a black woman to be performed by a major American orchestra—and her lost Symphony No. 2.</p>",
			'event_short'        => "Florence Price's Symphony No.3 was first performed on November 6th, 1940. It was Ms. Price's third symphony, following her lost Symphony No. 2",
			'event_time'         => '19:30:00',
			'event_endtime'      => '21:00:00',
			'event_link'         => 'https://www.youtube.com/watch?v=1jgJ1OkjnaI&list=OLAK5uy_lKldgbFTYBDa7WN6jf2ubB595wncDU7yc&index=2',
			'event_recur'        => 'S1',
			'event_image'        => plugins_url( '/images/demo/event.jpg', __FILE__ ),
			'event_access'       => '',
			'event_tickets'      => '',
			'event_registration' => '',
			'event_repeats'      => '',
			// Begin integers.
			'event_author'       => wp_get_current_user()->ID,
			'event_category'     => 1,
			'event_link_expires' => 0,
			'event_zoom'         => 16,
			'event_approved'     => 1,
			'event_host'         => wp_get_current_user()->ID,
			'event_flagged'      => 0,
			'event_fifth_week'   => 0,
			'event_holiday'      => 0,
			'event_group_id'     => 1,
			'event_span'         => 0,
			'event_hide_end'     => 0,
			// Array: removed before DB insertion.
			'event_categories'   => array( 1 ),
		);

		$event    = array( true, false, $submit, false, array() );
		$response = my_calendar_save( 'add', $event );
		$event_id = $response['event_id'];
		$r        = mc_update_event( 'event_location', (int) $results, $event_id );

		$e       = mc_get_first_event( $event_id );
		$post_id = $e->event_post;
		$image   = media_sideload_image( plugins_url( '/images/demo/event.jpg', __FILE__ ), $post_id, null, 'id' );

		if ( ! is_wp_error( $image ) ) {
			set_post_thumbnail( $post_id, $image );
		}

		$wp_object = get_post( $event['event_post'] );

		// Call the transformer Factory.
		$transformer = \Activitypub\Transformer\Factory::get_transformer( $wp_object );

		// Check that we got the right transformer.
		$this->assertInstanceOf( \ActivityPub_Event_Bridge\Activitypub\Transformer\My_Calendar::class, $transformer );
	}
}
