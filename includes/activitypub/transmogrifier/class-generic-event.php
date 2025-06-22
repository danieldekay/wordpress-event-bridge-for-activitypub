<?php
/**
 * ActivityPub Transmogrifier for the Generic Event Plugin.
 *
 * Handles converting incoming external ActivityPub events to generic events.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Event;
use Activitypub\Activity\Extended_Object\Place;
use Event_Bridge_For_ActivityPub\Integrations\Generic_Event_Plugin;

/**
 * ActivityPub Transmogrifier for the Generic Event Plugin.
 *
 * Handles converting incoming external ActivityPub events to generic events.
 *
 * @since 1.0.0
 */
class Generic_Event extends Base {

	/**
	 * Internal function to actually save the event.
	 *
	 * @param Event $activitypub_event    The ActivityPub event object.
	 * @param int   $event_source_post_id The Post ID of the Event Source that owns the outbox.
	 *
	 * @return false|int Post-ID on success, false on failure.
	 */
	protected static function save_event( $activitypub_event, $event_source_post_id ) {
		$post_type = Generic_Event_Plugin::get_post_type();
		$field_mappings = \get_option( 'event_bridge_for_activitypub_generic_field_mappings', array() );

		// Prepare the post data
		$post_args = array(
			'post_title'   => $activitypub_event->get_name(),
			'post_content' => $activitypub_event->get_content(),
			'post_status'  => 'publish',
			'post_type'    => $post_type,
		);

		// Check if this event already exists
		$existing_post_id = static::get_post_id_from_activitypub_id( $activitypub_event->get_id() );
		if ( $existing_post_id ) {
			$post_args['ID'] = $existing_post_id;
		}

		// Insert or update the post
		$post_id = \wp_insert_post( $post_args );

		if ( \is_wp_error( $post_id ) || ! $post_id ) {
			return false;
		}

		// Store the ActivityPub ID for future reference
		\update_post_meta( $post_id, '_activitypub_event_id', $activitypub_event->get_id() );

		// Map ActivityPub properties to configured fields
		static::map_event_fields( $activitypub_event, $post_id, $field_mappings );

		// Handle location
		$location = $activitypub_event->get_location();
		if ( $location && isset( $field_mappings['location'] ) ) {
			$location_value = static::get_location_as_string( $location );
			static::save_field_value( $post_id, $field_mappings['location'], $location_value );
		}

		// Handle categories/tags
		static::add_tags_to_post( $activitypub_event, $post_id );

		return $post_id;
	}

	/**
	 * Map ActivityPub event properties to configured field mappings.
	 *
	 * @param Event $activitypub_event The ActivityPub event object.
	 * @param int   $post_id           The WordPress post ID.
	 * @param array $field_mappings    The configured field mappings.
	 */
	private static function map_event_fields( $activitypub_event, $post_id, $field_mappings ) {
		// Map start time
		if ( isset( $field_mappings['start_time'] ) ) {
			$start_time = $activitypub_event->get_start_time();
			if ( $start_time ) {
				$timestamp = \strtotime( $start_time );
				static::save_field_value( $post_id, $field_mappings['start_time'], $timestamp );
			}
		}

		// Map end time
		if ( isset( $field_mappings['end_time'] ) ) {
			$end_time = $activitypub_event->get_end_time();
			if ( $end_time ) {
				$timestamp = \strtotime( $end_time );
				static::save_field_value( $post_id, $field_mappings['end_time'], $timestamp );
			}
		}

		// Map summary
		if ( isset( $field_mappings['summary'] ) ) {
			$summary = $activitypub_event->get_summary();
			if ( $summary ) {
				static::save_field_value( $post_id, $field_mappings['summary'], $summary );
			}
		}

		// Map event link
		if ( isset( $field_mappings['event_link'] ) ) {
			$url = $activitypub_event->get_url();
			if ( $url ) {
				static::save_field_value( $post_id, $field_mappings['event_link'], $url );
			}
		}
	}

	/**
	 * Save a field value based on its mapping configuration.
	 *
	 * @param int   $post_id       The WordPress post ID.
	 * @param array $field_config  The field mapping configuration.
	 * @param mixed $value         The value to save.
	 */
	private static function save_field_value( $post_id, $field_config, $value ) {
		$source_type = $field_config['source_type'] ?? 'meta';
		$field_name = $field_config['field_name'] ?? '';

		if ( empty( $field_name ) ) {
			return;
		}

		switch ( $source_type ) {
			case 'meta':
				\update_post_meta( $post_id, $field_name, $value );
				break;

			case 'post_field':
				// For post fields, we need to update the post directly
				$post_data = array( 'ID' => $post_id );
				if ( 'post_excerpt' === $field_name ) {
					$post_data['post_excerpt'] = $value;
				} elseif ( 'post_content' === $field_name ) {
					$post_data['post_content'] = $value;
				}
				if ( count( $post_data ) > 1 ) {
					\wp_update_post( $post_data );
				}
				break;

			case 'custom_field':
				// For ACF fields
				if ( function_exists( 'update_field' ) ) {
					\update_field( $field_name, $value, $post_id );
				}
				break;

			case 'taxonomy':
				// For taxonomy terms, set the value as a term
				\wp_set_object_terms( $post_id, $value, $field_name );
				break;
		}
	}

	/**
	 * Extract location and address as string.
	 *
	 * @param mixed $location The ActivityStreams location.
	 * @return string The location and address formatted as a single string.
	 */
	private static function get_location_as_string( $location ): string {
		$location_string = '';

		if ( $location instanceof Place ) {
			$location = $location->to_array();
		}

		// Return empty string when location is not an associative array.
		if ( ! is_array( $location ) || 0 === count( $location ) ) {
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
			$location_string .= static::address_to_string( $location['address'] );
		}

		return $location_string;
	}

	/**
	 * Convert address to string.
	 *
	 * @param mixed $address The address data.
	 * @return string
	 */
	private static function address_to_string( $address ): string {
		if ( is_string( $address ) ) {
			return $address;
		}

		if ( is_array( $address ) ) {
			$address_parts = array();
			$address_fields = array( 'streetAddress', 'addressLocality', 'addressRegion', 'postalCode', 'addressCountry' );
			
			foreach ( $address_fields as $field ) {
				if ( isset( $address[ $field ] ) && ! empty( $address[ $field ] ) ) {
					$address_parts[] = $address[ $field ];
				}
			}
			
			return implode( ', ', $address_parts );
		}

		return '';
	}

	/**
	 * Add tags to post.
	 *
	 * @param Event $activitypub_event The ActivityPub event object.
	 * @param int   $post_id           The post ID.
	 */
	private static function add_tags_to_post( $activitypub_event, $post_id ) {
		$taxonomy = Generic_Event_Plugin::get_event_category_taxonomy();
		$tags_array = $activitypub_event->get_tag();

		// Ensure the input is valid.
		if ( empty( $tags_array ) || ! is_array( $tags_array ) || ! $post_id || ! $taxonomy ) {
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
			\wp_set_object_terms( $post_id, $tag_names, $taxonomy, true );
		}

		return true;
	}

	/**
	 * Get the post ID from an ActivityPub event ID.
	 *
	 * @param string $activitypub_event_id The ActivityPub event ID.
	 * @return int|false The post ID or false if not found.
	 */
	private static function get_post_id_from_activitypub_id( $activitypub_event_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_activitypub_event_id' AND meta_value = %s",
				$activitypub_event_id
			)
		);

		return $post_id ? (int) $post_id : false;
	}
}