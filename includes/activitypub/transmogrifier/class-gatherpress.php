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

use DateTime;
use Event_Bridge_For_ActivityPub\Integrations\GatherPress as IntegrationsGatherPress;

use function Activitypub\sanitize_url;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use GatherPress\Core\Event as GatherPress_Event;

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
	 * @param int $post_id The post ID.
	 */
	private function add_tags_to_post( $post_id ) {
		$tags_array = $this->activitypub_event->get_tag();

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
			wp_set_object_terms( $post_id, $tag_names, IntegrationsGatherPress::get_event_category_taxonomy(), true );
		}

		return true;
	}

	/**
	 * Add venue.
	 *
	 * @param int $post_id The post ID.
	 */
	private function add_venue( $post_id ) {
		$location = $this->activitypub_event->get_location();

		if ( ! $location ) {
			return;
		}

		if ( ! isset( $location['name'] ) ) {
			return;
		}

		// Fallback for Gancio instances.
		if ( 'online' === $location['name'] ) {
			$online_event_link = $this->get_online_event_link_from_attachments();
			if ( ! $online_event_link ) {
				return;
			}
			update_post_meta( $post_id, 'gatherpress_online_event_link', sanitize_url( $online_event_link ) );
			wp_set_object_terms( $post_id, 'online-event', '_gatherpress_venue', false );
			return;
		}

		$venue_instance = \GatherPress\Core\Venue::get_instance();
		$venue_name     = sanitize_title( $location['name'] );
		$venue_slug     = $venue_instance->get_venue_term_slug( $venue_name );
		$venue_post     = $venue_instance->get_venue_post_from_term_slug( $venue_slug );

		if ( ! $venue_post ) {
			$venue_id = wp_insert_post(
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

		$address_string = isset( $location['address'] ) ? $this->address_to_string( $location['address'] ) : '';

		$venue_information['fullAddress']  = $address_string;
		$venue_information['phone_number'] = '';
		$venue_information['website']      = '';
		$venue_information['permalink']    = '';

		$venue_json = wp_json_encode( $venue_information );

		update_post_meta( $venue_id, 'gatherpress_venue_information', $venue_json );

		wp_set_object_terms( $post_id, $venue_slug, '_gatherpress_venue', false );
	}

	/**
	 * Save the ActivityPub event object as GatherPress Event.
	 *
	 * @return false|int
	 */
	protected function save_event() {
		// Limit this as a safety measure.
		add_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		$post_id = self::get_post_id_from_activitypub_id( $this->activitypub_event->get_id() );

		$args = array(
			'post_title'   => sanitize_text_field( $this->activitypub_event->get_name() ),
			'post_type'    => 'gatherpress_event',
			'post_content' => wp_kses_post( $this->activitypub_event->get_content() ?? '' ) . '<!-- wp:gatherpress/venue /-->',
			'post_excerpt' => wp_kses_post( $this->activitypub_event->get_summary() ?? '' ),
			'post_status'  => 'publish',
			'guid'         => sanitize_url( $this->activitypub_event->get_id() ),
		);

		if ( $post_id ) {
			// Update existing GatherPress event post.
			$args['ID'] = $post_id;
			wp_update_post( $args );
		} else {
			// Insert new GatherPress event post.
			$post_id = wp_insert_post( $args );
		}

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return false;
		}

		// Insert the dates.
		$event      = new GatherPress_Event( $post_id );
		$start_time = $this->activitypub_event->get_start_time();
		$end_time   = $this->activitypub_event->get_end_time();
		if ( ! $end_time ) {
			$end_time = new DateTime( $start_time );
			$end_time->modify( '+1 hour' );
			$end_time = $end_time->format( 'Y-m-d H:i:s' );
		}
		$params = array(
			'datetime_start' => $start_time,
			'datetime_end'   => $end_time,
			'timezone'       => $this->activitypub_event->get_timezone(),
		);
		// Sanitization of the params is done in the save_datetimes function just in time.
		$event->save_datetimes( $params );

		// Insert featured image.
		$image = $this->get_featured_image();
		self::set_featured_image_with_alt( $post_id, $image['url'], $image['alt'] );

		// Add hashtags.
		$this->add_tags_to_post( $post_id );

		$this->add_venue( $post_id );

		// Limit this as a safety measure.
		remove_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		return $post_id;
	}
}
