<?php
/**
 * ActivityPub Transmogrify for the The Events Calendar event plugin.
 *
 * Handles converting incoming external ActivityPub events to The Events Calendar Events.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier;

use Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source;
use Tribe__Date_Utils;

use function Activitypub\sanitize_url;
use function Activitypub\object_to_uri;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * ActivityPub Transmogrifier for the GatherPress event plugin.
 *
 * Handles converting incoming external ActivityPub events to GatherPress Events.
 *
 * @since 1.0.0
 */
class The_Events_Calendar extends Base {
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

		$post_ids = tribe_venues()->search( $location['name'] )->all();

		$post_id = false;

		if ( count( $post_ids ) ) {
			$post_id = reset( $post_ids );
			if ( $post_id instanceof \WP_Post ) {
				$post_id = $post_id->ID;
			}
		}

		if ( $post_id && get_post_meta( $post_id, '_event_bridge_for_activitypub_is_remote_cached', true ) ) {
			$result = tribe_venues()->where( 'id', $post_id )->set_args( $this->get_venue_args( $location ) )->save();
			if ( array_key_exists( $post_id, $result ) && $result[ $post_id ] ) {
				return $post_id;
			}
		} else {
			$post = tribe_venues()->set_args( $this->get_venue_args( $location ) )->create();
			if ( $post ) {
				$post_id = $post->ID;
				update_post_meta( $post_id, '_event_bridge_for_activitypub_is_remote_cached', true );
			}
		}

		return $post_id;
	}

	/**
	 * Add organizer.
	 *
	 * @return int|bool $post_id The organizers post ID.
	 */
	private function add_organizer() {
		// This might likely change, because of FEP-8a8e.
		$actor = $this->activitypub_event->get_attributed_to();
		if ( is_null( $actor ) ) {
			return false;
		}
		$actor_id = object_to_uri( $actor );

		$event_source = Event_Source::get_by_id( $actor_id );

		// As long as we do not support announces, we expect the attributedTo to be an existing event source.
		if ( ! $event_source ) {
			return false;
		}

		$tribe_organizer = tribe_organizers()
			->set_args(
				array(
					'organizer'     => $event_source->get_name(),
					'description'   => $event_source->get_summary(),
					'post_date_gmt' => $event_source->get_published(),
					'website'       => $event_source->get_id(),
					'excerpt'       => $event_source->get_summary(),
				),
				'publish',
				true // This enables avoid_duplicates which includes exact matches of title, content, excerpt, and website.
			)->create();

		if ( ! $tribe_organizer ) {
			return;
		}

		// Make a relationship between the event source WP_Post and the organizer WP_Post.
		wp_update_post(
			array(
				'ID'          => $tribe_organizer->ID,
				'post_parent' => $event_source->get__id(),
			)
		);

		// Add the thumbnail of the event source to the organizer.
		if ( get_post_thumbnail_id( $event_source ) ) {
			set_post_thumbnail( $tribe_organizer, get_post_thumbnail_id( $event_source ) );
		}

		return $tribe_organizer->ID;
	}

	/**
	 * Save the ActivityPub event object as GatherPress Event.
	 *
	 * @return false|int
	 */
	public function save_event() {
		// Limit this as a safety measure.
		add_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		$post_id = self::get_post_id_from_activitypub_id( $this->activitypub_event->get_id() );

		$duration = $this->get_duration();

		$venue_id = $this->add_venue();

		$organizer_id = $this->add_organizer();

		$args = array(
			'title'      => sanitize_text_field( $this->activitypub_event->get_name() ),
			'content'    => wp_kses_post( $this->activitypub_event->get_content() ?? '' ),
			'start_date' => gmdate( 'Y-m-d H:i:s', strtotime( $this->activitypub_event->get_start_time() ) ),
			'duration'   => $duration,
			'status'     => 'publish',
			'guid'       => sanitize_url( $this->activitypub_event->get_id() ),
		);

		if ( $venue_id ) {
			$args['venue']   = $venue_id;
			$args['VenueID'] = $venue_id;
		}

		if ( $organizer_id ) {
			$args['organizer']   = $organizer_id;
			$args['OrganizerID'] = $organizer_id;
		}

		$tribe_event = new The_Events_Calendar_Event_Repository();

		if ( $post_id ) {
			$args['post_title']   = $args['title'];
			$args['post_content'] = $args['content'];
			// Update existing The Events Calendar event post.
			$post_id = \Tribe__Events__API::updateEvent( $post_id, $args );
		} else {
			$post = $tribe_event->set_args( $args )->create();
			if ( $post instanceof \WP_Post ) {
				$post_id = $post->ID;
			}
		}

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return false;
		}

		// Limit this as a safety measure.
		remove_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		return $post_id;
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
