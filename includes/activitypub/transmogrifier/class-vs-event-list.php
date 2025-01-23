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

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Event_Bridge_For_ActivityPub\Integrations\VS_Event_List as IntegrationsVS_Event_List;

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
	 * Extract location and address as string.
	 *
	 * @param ?array $location The ActivitySTreams location as an associative array.
	 * @return string The location and address formatted as a single string.
	 */
	private static function get_location_as_string( $location ): string {
		$location_string = '';

		// Return empty string when location is not an associative array.
		if ( is_null( $location ) || ! is_array( $location ) ) {
			return $location_string;
		}

		if ( ! isset( $location['type'] ) || 'Place' !== $location['type'] ) {
			return $location_string;
		}

		// Add name of the location.
		if ( isset( $location['name'] ) ) {
			$location_string .= $location['name'];
		}

		// Add delimiter between name and address if both are set.
		if ( isset( $location['name'] ) && isset( $location['address'] ) ) {
			$location_string .= ' – ';
		}

		// Add address.
		if ( isset( $location['address'] ) ) {
			$location_string .= self::address_to_string( $location['address'] );
		}
		return $location_string;
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
			wp_set_object_terms( $post_id, $tag_names, IntegrationsVS_Event_List::get_event_category_taxonomy(), true );
		}

		return true;
	}

	/**
	 * Save the ActivityPub event object as VS Event List event.
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
			'post_title'   => $activitypub_event->get_name(),
			'post_type'    => \Event_Bridge_For_ActivityPub\Integrations\VS_Event_List::get_post_type(),
			'post_content' => $activitypub_event->get_content() ?? '',
			'post_excerpt' => $activitypub_event->get_summary() ?? '',
			'post_status'  => 'publish',
			'guid'         => $activitypub_event->get_id(),
			'meta_input'   => array(
				'event-start-date'  => \strtotime( $activitypub_event->get_start_time() ),
				'event-link'        => $activitypub_event->get_url() ?? $activitypub_event->get_id(),
				'event-link-label'  => \sanitize_text_field( __( 'Original Website', 'event-bridge-for-activitypub' ) ),
				'event-link-target' => 'yes', // Open in new window.
				'event-link-title'  => 'no', // Whether to redirect event title to original source.
				'event-link-image'  => 'no', // Whether to redirect events featured image to original source.
			),
		);

		// Add end time.
		$end_time = $activitypub_event->get_end_time();
		if ( $end_time ) {
			$args['meta_input']['event-date'] = \strtotime( $end_time );
		}

		// Maybe add location.
		$location = self::get_location_as_string( $activitypub_event->get_location() );
		if ( $location ) {
			$args['meta_input']['event-location'] = $location;
		}

		if ( $post_id ) {
			// Update existing  event post.
			$args['ID'] = $post_id;
			$post_id    = \wp_update_post( $args );
		} else {
			// Insert new event post.
			$post_id = \wp_insert_post( $args );
		}

		if ( ! $post_id || \is_wp_error( $post_id ) ) {
			return false;
		}

		// Insert featured image.
		$image = self::get_featured_image( $activitypub_event );
		self::set_featured_image_with_alt( $post_id, $image['url'], $image['alt'] );

		// Add hashtags.
		self::add_tags_to_post( $activitypub_event, $post_id );

		// Limit this as a safety measure.
		\remove_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		return $post_id;
	}
}
