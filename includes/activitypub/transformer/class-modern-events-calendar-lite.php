<?php
/**
 * ActivityPub Tribe Transformer
 *
 * @package ActivityPub_Event_Bridge
 * @license AGPL-3.0-or-later
 */

namespace ActivityPub_Event_Bridge\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use ActivityPub_Event_Bridge\Activitypub\Transformer\Event;

use MEC;
use MEC\Events\Event as MEC_Event;
use MEC_main;

/**
 * ActivityPub Tribe Transformer
 *
 * @since 1.0.0
 */
final class Modern_Events_Calendar_Lite extends Event {
	/**
	 * The MEC Event object.
	 *
	 * @var MEC_Event|null
	 */
	protected $mec_event;

	/**
	 * The MEC main instance.
	 *
	 * @var MEC_main|null
	 */
	protected $mec_main;


	/**
	 * Extend the constructor, to also set the tribe object.
	 *
	 * This is a special class object form The Events Calendar which
	 * has a lot of useful functions, we make use of our getter functions.
	 *
	 * @param WP_Post $wp_object The WordPress object.
	 * @param string  $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object, $wp_taxonomy );
		$this->mec_main  = MEC::getInstance( 'app.libraries.main' );
		$this->mec_event = new MEC_Event( $wp_object );
	}

	/**
	 * Get the end time from the event object.
	 */
	public function get_start_time(): string {
		return \gmdate( 'Y-m-d\TH:i:s\Z', $this->mec_event->get_datetime()['start']['timestamp'] );
	}

	/**
	 * Get the end time from the event object.
	 */
	public function get_end_time(): ?string {
		return \gmdate( 'Y-m-d\TH:i:s\Z', $this->mec_event->get_datetime()['end']['timestamp'] );
	}

	/**
	 * Get the location.
	 */
	public function get_location(): ?Place {
		$location_id = $this->mec_main->get_master_location_id( $this->mec_event->ID );

		if ( ! $location_id ) {
			return null;
		}

		$data = $this->mec_main->get_location_data( $location_id );

		$location = new Place();
		$location->set_sensitive( null );

		if ( ! empty( $data['address'] ) ) {
			$location->set_address( $data['address'] );
		}
		if ( ! empty( $data['name'] ) ) {
			$location->set_name( $data['name'] );
		}
		if ( ! empty( $data['longitude'] ) ) {
			$location->set_longitude( $data['longitude'] );
		}
		if ( ! empty( $data['latitude'] ) ) {
			$location->set_latitude( $data['latitude'] );
		}

		return $location;
	}

	/**
	 * Get the location.
	 */
	public function get_timezone(): string {
		$timezone = get_post_meta( $this->wp_object->ID, 'mec_timezone', true );

		if ( 'global' === $timezone ) {
			return parent::get_timezone();
		}

		return $timezone;
	}
}
