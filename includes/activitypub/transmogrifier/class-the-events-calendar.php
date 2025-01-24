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

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source;

use function Activitypub\object_to_uri;

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
	private static function get_venue_args( $location ) {
		$args = array(
			'venue'  => $location['name'],
			'status' => 'publish',
		);

		if ( $location instanceof Place ) {
			$location = $location->to_array();
		}

		if ( ! isset( $location['address'] ) ) {
			return $args;
		}

		if ( is_array( $location['address'] ) ) {
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
	 * @param Event $activitypub_event    The ActivityPub event object.
	 * @param int   $event_source_post_id The WordPress Post ID of the event source.
	 *
	 * @return int|bool $post_id The venues post ID.
	 */
	private static function add_venue( $activitypub_event, $event_source_post_id ) {
		$location = $activitypub_event->get_location();

		if ( ! $location ) {
			return;
		}

		if ( $location instanceof Place ) {
			$location = $location->to_array();
		}

		if ( ! is_array( $location ) ) {
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

		if ( $post_id && get_post_meta( $post_id, '_event_bridge_for_activitypub_event_source', true ) ) {
			$result = tribe_venues()->where( 'id', $post_id )->set_args( self::get_venue_args( $location ) )->save();
			if ( array_key_exists( $post_id, $result ) && $result[ $post_id ] ) {
				return $post_id;
			}
		} else {
			$post = tribe_venues()->set_args( self::get_venue_args( $location ) )->create();
			if ( $post ) {
				$post_id = $post->ID;
				update_post_meta( $post_id, '_event_bridge_for_activitypub_event_source', $event_source_post_id );
			}
		}

		return $post_id;
	}

	/**
	 * Add organizer.
	 *
	 * @param Event $activitypub_event    The ActivityPub event object.
	 *
	 * @return int|bool $post_id The organizers post ID.
	 */
	private static function add_organizer( $activitypub_event ) {
		// This might likely change, because of FEP-8a8e.
		$actor = $activitypub_event->get_attributed_to();
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
					'website'       => $event_source->get_url(),
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
	 * @param Event $activitypub_event    The ActivityPub event as associative array.
	 * @param int   $event_source_post_id The Post ID of the Event Source that owns the outbox.
	 *
	 * @return false|int
	 */
	protected static function save_event( $activitypub_event, $event_source_post_id ) {
		// Limit this as a safety measure.
		add_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		$post_id      = self::get_post_id_from_activitypub_id( $activitypub_event->get_id() );
		$duration     = self::get_duration( $activitypub_event );
		$venue_id     = self::add_venue( $activitypub_event, $event_source_post_id );
		$organizer_id = self::add_organizer( $activitypub_event );

		$args = array(
			'title'      => $activitypub_event->get_name(),
			'content'    => $activitypub_event->get_content() ?? '',
			'start_date' => gmdate( 'Y-m-d H:i:s', strtotime( $activitypub_event->get_start_time() ) ),
			'duration'   => $duration,
			'status'     => 'publish',
			'guid'       => $activitypub_event->get_id(),
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
			$post = $tribe_event->where( 'id', $post_id )->set_args( $args )->save();
		} else {
			$post = $tribe_event->set_args( $args )->create();
		}

		if ( $post instanceof \WP_Post ) {
			$post_id = $post->ID;
		}

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return false;
		}

		// Insert featured image.
		$image = self::get_featured_image( $activitypub_event );
		self::set_featured_image_with_alt( $post_id, $image['url'], $image['alt'] );

		// Limit this as a safety measure.
		remove_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		return $post_id;
	}

	/**
	 * Get the events duration in seconds.
	 *
	 * @param Event $activitypub_event    The ActivityPub event object.
	 *
	 * @return int
	 */
	private static function get_duration( $activitypub_event ) {
		$end_time = $activitypub_event->get_end_time();
		if ( ! $end_time ) {
			return 2 * HOUR_IN_SECONDS;
		}
		return abs( strtotime( $end_time ) - strtotime( $activitypub_event->get_start_time() ) );
	}
}
