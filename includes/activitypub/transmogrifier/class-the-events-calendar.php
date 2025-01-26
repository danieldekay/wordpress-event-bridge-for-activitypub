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
use Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\Helper\The_Events_Calendar_Event_Repository;
use Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\Helper\The_Events_Calendar_Venue_Repository;

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
	 * Save the ActivityPub event object as GatherPress Event.
	 *
	 * @param Event $activitypub_event    The ActivityPub event as associative array.
	 * @param int   $event_source_post_id The Post ID of the Event Source that owns the outbox.
	 *
	 * @return false|int
	 */
	protected static function save_event( $activitypub_event, $event_source_post_id ) {
		// Limit the number of saved post revisions as a safety measure.
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

		if ( $activitypub_event->get_published() ) {
			$post_date             = self::format_time_string_to_wordpress_gmt( $activitypub_event->get_published() );
			$args['post_date']     = $post_date;
			$args['post_date_gmt'] = $post_date;
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
		if ( isset( $image['url'] ) ) {
			self::set_featured_image_with_alt( $post_id, $image['url'], $image['alt'] );
		}

		// Add tags.
		self::add_tags_to_post( $activitypub_event, $post_id );

		// Remove revision limit.
		remove_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		return $post_id;
	}

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

		if ( isset( $location['id'] ) ) {
			$args['guid'] = $location['id'];
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

		// Make sure we have a valid location in the right format.
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

		$tribe_venue = new The_Events_Calendar_Venue_Repository();

		// If the venue already exists try to find it's post id.
		$post_id = null;

		// Search if we already got this venue/place in our database.
		if ( isset( $location['id'] ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$post_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM $wpdb->posts WHERE guid=%s AND post_type=%s",
					$location['id'],
					\Tribe__Events__Venue::POSTTYPE
				)
			);
			if ( $post_id ) {
				$post_id = \absint( $post_id );
			}
		}

		if ( ! $post_id ) {
			// Try to find a match by searching.
			$results = $tribe_venue->search( $location['name'] )->all();

			foreach ( $results as $potential_matching_post_id ) {
				if ( $potential_matching_post_id instanceof \WP_Post ) {
					$potential_matching_post_id = $potential_matching_post_id->ID;
				}
				// Only accept a match for the venue/location if it was received by the same actor.
				if ( \get_post_meta( $potential_matching_post_id, '_event_bridge_for_activitypub_event_source', true ) === $event_source_post_id ) {
					$post_id = $potential_matching_post_id;
					break;
				}
			}
		}

		if ( $post_id ) {
			// Update if we found a match.
			$result = $tribe_venue->where( 'id', $post_id )->set_args( self::get_venue_args( $location ) )->save();
			if ( array_key_exists( $post_id, $result ) && $result[ $post_id ] ) {
				return $post_id;
			}
		} else {
			// Create a new venue.
			$post = $tribe_venue->set_args( self::get_venue_args( $location ) )->create();
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

		$actor_id     = object_to_uri( $actor );
		$event_source = Event_Source::get_by_id( $actor_id );

		// As long as we do not support announces, we expect the attributedTo to be an existing event source.
		if ( ! $event_source ) {
			return false;
		}

		// Prepare arguments for inserting/updating the organizer post.
		$args = array(
			'organizer'   => $event_source->get_name(),
			'description' => $event_source->get_summary(),
			'website'     => $event_source->get_url(),
			'excerpt'     => $event_source->get_summary(),
			'post_parent' => $event_source->get__id(), // Maybe just use post meta too here.
		);

		if ( $event_source->get_published() ) {
			$post_date             = self::format_time_string_to_wordpress_gmt( $event_source->get_published() );
			$args['post_date']     = $post_date;
			$args['post_date_gmt'] = $post_date;
		}

		// Get organizer if it is already present.
		$children = \get_children(
			array(
				'post_parent' => $event_source->get__id(),
				'post_type'   => \Tribe__Events__Organizer::POSTTYPE,
			),
		);

		if ( count( $children ) ) {
			// Update organizer post.
			$child                    = array_pop( $children );
			$tribe_organizer_post_ids = \tribe_organizers()->where( 'id', $child->ID )->set_args( $args )->save();

			// Fallback to delete duplicates.
			foreach ( $children as $to_delete ) {
				\wp_delete_post( $to_delete->ID, true );
			}

			// If updating failed return.
			if ( 1 !== count( $tribe_organizer_post_ids ) || ! reset( $tribe_organizer_post_ids ) ) {
				return;
			}

			$tribe_organizer_post_id = array_key_first( $tribe_organizer_post_ids );
		} else {
			// Create new organizer post.
			$tribe_organizer_post = \tribe_organizers()->set_args( $args )->create();

			if ( ! $tribe_organizer_post ) {
				return;
			}

			$tribe_organizer_post_id = $tribe_organizer_post->ID;

			// Make a relationship between the event source WP_Post and the organizer WP_Post.
			\update_post_meta( $tribe_organizer_post_id, '_event_bridge_for_activitypub_event_source', true );
		}

		// Add the thumbnail of the event source to the organizer.
		if ( \get_post_thumbnail_id( $event_source ) ) {
			\set_post_thumbnail( $tribe_organizer_post_id, \get_post_thumbnail_id( $event_source ) );
		}

		return $tribe_organizer_post_id;
	}

	/**
	 * Add tags to post.
	 *
	 * @param Event $activitypub_event The ActivityPub event object.
	 * @param int   $post_id           The post ID.
	 */
	private static function add_tags_to_post( $activitypub_event, $post_id ) {
		$tags_array = $activitypub_event->get_tag();

		// Ensure the input is valid.
		if ( empty( $tags_array ) || ! is_array( $tags_array ) || ! $post_id ) {
			return false;
		}

		// Extract and process tag names.
		$tag_names = array();
		foreach ( $tags_array as $tag ) {
			if ( isset( $tag['name'] ) && 'Hashtag' === $tag['type'] ) {
				$tag_names[] = ltrim( $tag['name'], '#' ); // Remove the '#' from the name.
			}
		}

		// Add the tags as terms to the post.
		if ( ! empty( $tag_names ) ) {
			\wp_set_object_terms( $post_id, $tag_names, 'post_tag', true );
		}

		return true;
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
