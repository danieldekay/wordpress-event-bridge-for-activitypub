<?php
/**
 * ActivityPub Transformer for the plugin Event Organiser.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Event as Base_Event_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place\Event_Organiser as Event_Organiser_Location_Transformer;
use WP_Post;

/**
 * ActivityPub Transformer for Event Organiser.
 *
 * @since 1.0.0
 */
final class Event_Organiser extends Base_Event_Transformer {
	/**
	 * The events occurances.
	 *
	 * @var ?array
	 */
	protected $schedule;

	/**
	 * Extended constructor.
	 *
	 * The item is overridden with a the item with filters. This object
	 * also contains attributes specific to the Event organiser plugin like the
	 * occurrence id.
	 *
	 * @param WP_Post $item The WordPress object.
	 * @param string  $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $item, $wp_taxonomy ) {
		parent::__construct( $item, $wp_taxonomy );
		$this->schedule = \eo_get_event_schedule( $item->ID );
	}

	/**
	 * Get the end time from the event object.
	 */
	public function get_end_time(): string {
		return $this->schedule['end']->format( 'Y-m-d\TH:i:sP' );
	}

	/**
	 * Get the start time from the event object.
	 */
	public function get_start_time(): string {
		return $this->schedule['start']->format( 'Y-m-d\TH:i:sP' );
	}

	/**
	 * Get location from the event object.
	 */
	public function get_location(): ?Place {
		$venue = \get_the_terms( $this->item->ID, 'event-venue' );

		if ( empty( $venue ) || is_wp_error( $venue ) ) {
			return null;
		}

		$venue = array_pop( $venue );

		$location_transformer = new Event_Organiser_Location_Transformer( $venue );
		$location             = $location_transformer->to_object();

		return $location;
	}
}
