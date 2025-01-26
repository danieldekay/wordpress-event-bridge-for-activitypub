<?php
/**
 * ActivityPub Transmogrify for the GatherPress event plugin.
 *
 * Handles converting incoming external ActivityPub events to GatherPress Events.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use DateTime;
use Event_Bridge_For_ActivityPub\Integrations\GatherPress as IntegrationsGatherPress;
use GatherPress\Core\Event as GatherPress_Event;

use function Activitypub\sanitize_url;

/**
 * ActivityPub Transmogrifier for the GatherPress event plugin.
 *
 * Handles converting incoming external ActivityPub events to GatherPress Events.
 *
 * @since 1.0.0
 */
class GatherPress extends Base {
	/**
	 * Add tags to post.
	 *
	 * @param Event $event The ActivityPub event object.
	 * @param int   $post_id The post ID.
	 *
	 * @return bool
	 */
	private static function add_tags_to_post( $event, $post_id ) {
		$tags_array = $event->get_tag();

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
			\wp_set_object_terms( $post_id, $tag_names, IntegrationsGatherPress::get_event_category_taxonomy(), true );
		}

		return true;
	}

	/**
	 * Add venue.
	 *
	 * @param Event $activitypub_event The ActivityPub event object.
	 * @param int   $post_id          The post ID.
	 */
	private static function add_venue( $activitypub_event, $post_id ) {
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
			$online_event_link = self::get_online_event_link_from_attachments( $activitypub_event );
			if ( ! $online_event_link ) {
				return;
			}
			\update_post_meta( $post_id, 'gatherpress_online_event_link', sanitize_url( $online_event_link ) );
			\wp_set_object_terms( $post_id, 'online-event', '_gatherpress_venue', false );
			return;
		}

		$venue_instance = \GatherPress\Core\Venue::get_instance();
		$venue_name     = \sanitize_title( $location['name'] );
		$venue_slug     = $venue_instance->get_venue_term_slug( $venue_name );
		$venue_post     = $venue_instance->get_venue_post_from_term_slug( $venue_slug );

		if ( ! $venue_post ) {
			$venue_id = \wp_insert_post(
				array(
					'post_title'  => sanitize_text_field( $location['name'] ),
					'post_type'   => 'gatherpress_venue',
					'post_status' => 'publish',
				)
			);
		} else {
			$venue_id = $venue_post->ID;
		}

		$venue_information = array();

		$address_string = isset( $location['address'] ) ? self::address_to_string( $location['address'] ) : '';

		$venue_information['fullAddress']  = $address_string;
		$venue_information['phone_number'] = '';
		$venue_information['website']      = '';
		$venue_information['permalink']    = '';

		$venue_json = \wp_json_encode( $venue_information );

		\update_post_meta( $venue_id, 'gatherpress_venue_information', $venue_json );

		\wp_set_object_terms( $post_id, $venue_slug, '_gatherpress_venue', false );
	}

	/**
	 * Save the ActivityPub event object as GatherPress Event.
	 *
	 * @param Event $activitypub_event    The ActivityPub event object.
	 * @param int   $event_source_post_id The Post ID of the Event Source that owns the outbox.
	 *
	 * @return false|int
	 */
	protected static function save_event( $activitypub_event, $event_source_post_id ) {
		// Limit this as a safety measure.
		\add_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		$post_id = self::get_post_id_from_activitypub_id( $activitypub_event->get_id() );

		$args = array(
			'post_title'   => sanitize_text_field( $activitypub_event->get_name() ),
			'post_type'    => 'gatherpress_event',
			'post_content' => wp_kses_post( $activitypub_event->get_content() ?? '' ) . '<!-- wp:gatherpress/venue /-->',
			'post_excerpt' => wp_kses_post( $activitypub_event->get_summary() ?? '' ),
			'post_status'  => 'publish',
			'guid'         => sanitize_url( $activitypub_event->get_id() ),
		);

		if ( $activitypub_event->get_published() ) {
			$post_date             = self::format_time_string_to_wordpress_gmt( $activitypub_event->get_published() );
			$args['post_date']     = $post_date;
			$args['post_date_gmt'] = $post_date;
		}

		if ( $post_id ) {
			// Update existing GatherPress event post.
			$args['ID'] = $post_id;
			\wp_update_post( $args );
		} else {
			// Insert new GatherPress event post.
			$post_id = \wp_insert_post( $args );
		}

		if ( ! $post_id || \is_wp_error( $post_id ) ) {
			return false;
		}

		// Insert the dates.
		$gatherpress_event = new GatherPress_Event( $post_id );
		$start_time        = $activitypub_event->get_start_time();
		$end_time          = $activitypub_event->get_end_time();
		if ( ! $end_time ) {
			$end_time = new DateTime( $start_time );
			$end_time->modify( '+1 hour' );
			$end_time = $end_time->format( 'Y-m-d H:i:s' );
		}
		$params = array(
			'datetime_start' => $start_time,
			'datetime_end'   => $end_time,
			'timezone'       => $activitypub_event->get_timezone(),
		);
		// Sanitization of the params is done in the save_datetimes function just in time.
		$gatherpress_event->save_datetimes( $params );

		// Insert featured image.
		$image = self::get_featured_image( $activitypub_event );
		self::set_featured_image_with_alt( $post_id, $image['url'], $image['alt'] );

		// Add hashtags.
		self::add_tags_to_post( $activitypub_event, $post_id );

		// Add venue.
		self::add_venue( $activitypub_event, $post_id );

		// Limit this as a safety measure.
		\remove_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		return $post_id;
	}
}
