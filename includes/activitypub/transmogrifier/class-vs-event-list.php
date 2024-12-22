<?php
/**
 * ActivityPub Transmogrifier for the VS Event List event plugin.
 *
 * Handles converting incoming external ActivityPub events to events of VS Event List.
 *
 * @link https://wordpress.org/plugins/very-simple-event-list/
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier;

use function Activitypub\sanitize_url;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * ActivityPub Transmogrifier for the VS Event List event plugin.
 *
 * Handles converting incoming external ActivityPub events to events of VS Event List.
 *
 * @link https://wordpress.org/plugins/very-simple-event-list/
 * @since 1.0.0
 */
class VS_Event_List extends Base {
	/**
	 * Get a list of Post IDs of events that have ended.
	 *
	 * @param int $cache_retention_period Additional time buffer in seconds.
	 * @return ?array
	 */
	public static function get_past_events( $cache_retention_period = 0 ): ?array {
		unset( $cache_retention_period );

		$results = array();

		return $results;
	}

	/**
	 * Map an ActivityStreams Place to the Events Calendar venue.
	 *
	 * @param array $location An ActivityPub location as an associative array.
	 * @link https://www.w3.org/TR/activitystreams-vocabulary/#dfn-place
	 * @return array
	 */
	private function get_venue_args( $location ) {
		$args = array(
			'venue'  => $location['name'],
			'status' => 'publish',
		);

		if ( is_array( $location['address'] ) && isset( $location['address']['type'] ) && 'PostalAddress' === $location['address']['type'] ) {
			$mapping = array(
				'streetAddress'   => 'address',
				'postalCode'      => 'zip',
				'addressLocality' => 'city',
				'addressState'    => 'state',
				'addressCountry'  => 'country',
				'url'             => 'website',
			);

			foreach ( $mapping as $postal_address_key => $venue_key ) {
				if ( isset( $location['address'][ $postal_address_key ] ) ) {
					$args[ $venue_key ] = $location['address'][ $postal_address_key ];
				}
			}
		} elseif ( is_string( $location['address'] ) ) {
			// Use the address field for a solely text address.
			$args['address'] = $location['address'];
		}

		return $args;
	}

	/**
	 * Add venue.
	 *
	 * @return int|bool $post_id The venues post ID.
	 */
	private function add_venue() {
		$location = $this->activitypub_event->get_location();

		if ( ! $location ) {
			return;
		}

		if ( ! isset( $location['name'] ) ) {
			return;
		}

		// Fallback for Gancio instances.
		if ( 'online' === $location['name'] ) {
			return;
		}

		$post_ids = tribe_events()->search( $location['name'] )->all();

		$post_id = false;

		if ( count( $post_ids ) ) {
			$post_id = reset( $post_ids );
		}

		if ( $post_id && get_post_meta( $post_id, '_event_bridge_for_activitypub_is_remote_cached' ) ) {
			tribe_venues()->where( 'id', $post_id )->set_args( $this->get_venue_args( $location ) )->save()[0];
		} else {
			$post = tribe_venues()->set_args( $this->get_venue_args( $location ) )->create();
			if ( $post ) {
				$post_id = $post->ID;
			}
		}

		return $post_id;
	}

	/**
	 * Save the ActivityPub event object as GatherPress Event.
	 *
	 * @return false|int
	 */
	public function save_event() {
		// Limit this as a safety measure.
		add_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		$post_id = $this->get_post_id_from_activitypub_id();

		$duration = $this->get_duration();

		$venue_id = $this->add_venue();

		$args = array(
			'title'      => sanitize_text_field( $this->activitypub_event->get_name() ),
			'content'    => wp_kses_post( $this->activitypub_event->get_content() ),
			'start_date' => gmdate( 'Y-m-d H:i:s', strtotime( $this->activitypub_event->get_start_time() ) ),
			'duration'   => $duration,
			'status'     => 'publish',
			'guid'       => sanitize_url( $this->activitypub_event->get_id() ),
		);

		if ( $venue_id ) {
			$args['venue']   = $venue_id;
			$args['VenueID'] = $venue_id;
		}

		$tribe_event = new The_Events_Calendar_Event_Repository();

		if ( $post_id ) {
			$args['post_title']   = $args['title'];
			$args['post_content'] = $args['content'];
			// Update existing GatherPress event post.
			$post = \Tribe__Events__API::updateEvent( $post_id, $args );
		} else {
			$post = $tribe_event->set_args( $args )->create();
		}

		if ( ! $post ) {
			return false;
		}

		// Limit this as a safety measure.
		remove_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		return $post->ID;
	}

	/**
	 * Get the events duration in seconds.
	 *
	 * @return int
	 */
	private function get_duration() {
		$end_time = $this->activitypub_event->get_end_time();
		if ( ! $end_time ) {
			return 2 * HOUR_IN_SECONDS;
		}
		return abs( strtotime( $end_time ) - strtotime( $this->activitypub_event->get_start_time() ) );
	}
}
