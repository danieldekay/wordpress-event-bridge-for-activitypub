<?php
/**
 * Base class with common functions for transforming an ActivityPub Event object to a WordPress object.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Event;
use Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources;
use Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\Helper\Sanitizer;

use WP_Error;

/**
 * Base class with common functions for transforming an ActivityPub Event object to a WordPress object.
 *
 * @since 1.0.0
 */
abstract class Base {
	/**
	 * Internal function to actually save the event.
	 *
	 * @param Event $activitypub_event    The ActivityPub event as associative array.
	 * @param int   $event_source_post_id The Post ID of the Event Source that owns the outbox.
	 *
	 * @return false|int Post-ID on success, false on failure.
	 */
	abstract protected static function save_event( $activitypub_event, $event_source_post_id );

	/**
	 * Save the ActivityPub event object within WordPress.
	 *
	 * @param array $activitypub_event    The ActivityPub event as associative array.
	 * @param int   $event_source_post_id The Post ID of the Event Source that owns the outbox.
	 */
	public static function save( $activitypub_event, $event_source_post_id ) {
		$activitypub_event = Sanitizer::init_and_sanitize_event_object_from_array( $activitypub_event );

		if ( is_wp_error( $activitypub_event ) ) {
			return;
		}

		// Pass the saving to the actual Transmogrifier implementation.
		$post_id = static::save_event( $activitypub_event, $event_source_post_id );

		// Post processing: Logging and marking the imported event's origin.
		$event_activitypub_id        = $activitypub_event->get_id();
		$event_source_activitypub_id = \get_the_guid( $event_source_post_id );

		if ( $post_id ) {
			\do_action(
				'event_bridge_for_activitypub_write_log',
				array( "[ACTIVITYPUB] Processed incoming event {$event_activitypub_id} from {$event_source_activitypub_id}" )
			);
			\update_post_meta( $post_id, '_event_bridge_for_activitypub_event_source', absint( $event_source_post_id ) );
			\update_post_meta( $post_id, 'activitypub_content_visibility', constant( 'ACTIVITYPUB_CONTENT_VISIBILITY_LOCAL' ) ?? '' );
		} else {
			\do_action(
				'event_bridge_for_activitypub_write_log',
				array( "[ACTIVITYPUB] Failed processing incoming event {$event_activitypub_id} from {$event_source_activitypub_id}" )
			);
		}
	}

	/**
	 * Delete a local event in WordPress that is a cached remote one.
	 *
	 * @param int $activitypub_event_id The ActivityPub events ID.
	 */
	public static function delete( $activitypub_event_id ) {
		$post_id = static::get_post_id_from_activitypub_id( $activitypub_event_id );

		if ( ! $post_id ) {
			\do_action(
				'event_bridge_for_activitypub_write_log',
				array( "[ACTIVITYPUB] Received delete for event that is not cached locally {$activitypub_event_id}" )
			);
			return new WP_Error(
				'event_bridge_for_activitypub_remote_event_not_found',
				\__( 'Remote event not found in cache', 'event-bridge-for-activitypub' ),
				array( 'status' => 404 )
			);
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( $thumbnail_id && ! Event_Sources::is_attachment_featured_image( $thumbnail_id ) ) {
			wp_delete_attachment( $thumbnail_id, true );
		}

		$result = wp_delete_post( $post_id, true );

		if ( $result ) {
			\do_action( 'event_bridge_for_activitypub_write_log', array( "[ACTIVITYPUB] Deleted cached event {$activitypub_event_id}" ) );
		} else {
			\do_action( 'event_bridge_for_activitypub_write_log', array( "[ACTIVITYPUB] Failed deleting cached event {$activitypub_event_id}" ) );
		}
	}

	/**
	 * Format an ActivityStreams xds:datetime to WordPress GMT format.
	 *
	 * @param string $time_string The ActivityStreams xds:datetime (may include offset).
	 * @return string The GMT string in format 'Y-m-d H:i:s'.
	 */
	protected static function format_time_string_to_wordpress_gmt( $time_string ) {
		$datetime = new \DateTime( $time_string );
		$datetime->setTimezone( new \DateTimeZone( 'GMT' ) );
		return $datetime->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Get WordPress post by ActivityPub object ID.
	 *
	 * @param int $activitypub_id The ActivityPub object ID.
	 * @return int The WordPress Post ID.
	 */
	protected static function get_post_id_from_activitypub_id( $activitypub_id ) {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE guid=%s",
				esc_sql( $activitypub_id ),
			)
		);
	}

	/**
	 * Get the image URL and alt-text of an ActivityPub object.
	 *
	 * @param array $data The ActivityPub object as ann associative array.
	 * @return array Array containing the images URL and alt-text.
	 */
	private static function extract_image_alt_and_url( $data ) {
		$image = array(
			'url' => null,
			'alt' => null,
		);

		// Check whether it is already simple.
		if ( ! $data || is_string( $data ) ) {
			$image['url'] = $data;
			return $image;
		}

		if ( ! isset( $data['type'] ) ) {
			return $image;
		}

		if ( ! in_array( $data['type'], array( 'Document', 'Image' ), true ) ) {
			return $image;
		}

		if ( isset( $data['url'] ) ) {
			$image['url'] = $data['url'];
		} elseif ( isset( $data['id'] ) ) {
			$image['id'] = $data['id'];
		}

		if ( isset( $data['name'] ) ) {
			$image['alt'] = $data['name'];
		}

		return $image;
	}

	/**
	 * Returns the URL of the featured image.
	 *
	 * @param Event $event The ActivityPub event object.
	 *
	 * @return array
	 */
	protected static function get_featured_image( $event ) {
		$image = $event->get_image();
		if ( $image ) {
			return self::extract_image_alt_and_url( $image );
		}
		$attachment = $event->get_attachment();
		if ( is_array( $attachment ) && ! empty( $attachment ) ) {
			$supported_types = array( 'Image', 'Document' );
			$match           = null;

			foreach ( $attachment as $item ) {
				if ( in_array( $item['type'], $supported_types, true ) ) {
					$match = $item;
					break;
				}
			}
			$attachment = $match;
		}
		return self::extract_image_alt_and_url( $attachment );
	}

	/**
	 * Given an image URL return an attachment ID. Image will be side-loaded into the media library if it doesn't exist.
	 *
	 * Forked from https://gist.github.com/kingkool68/a66d2df7835a8869625282faa78b489a.
	 *
	 * @param int    $post_id The post ID where the image will be set as featured image.
	 * @param string $url     The image URL to maybe sideload.
	 * @uses media_sideload_image
	 * @return string|int|WP_Error
	 */
	protected static function maybe_sideload_image( $post_id, $url = '' ) {
		global $wpdb;

		// Include necessary WordPress file for media handling.
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		// Check to see if the URL has already been fetched, if so return the attachment ID.
		$attachment_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = '_source_url' AND `meta_value` = %s", $url )
		);
		if ( ! empty( $attachment_id ) ) {
			return $attachment_id;
		}

		$attachment_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT `ID` FROM {$wpdb->posts} WHERE guid=%s", $url )
		);
		if ( ! empty( $attachment_id ) ) {
			return $attachment_id;
		}

		// If the URL doesn't exist, sideload it to the media library.
		return media_sideload_image( $url, $post_id, $url, 'id' );
	}

	/**
	 * Sideload an image_url set it as featured image and add the alt-text.
	 *
	 * @param int    $post_id   The post ID where the image will be set as featured image.
	 * @param string $image_url The image URL.
	 * @param string $alt_text  The alt-text of the image.
	 * @return int The attachment ID
	 */
	protected static function set_featured_image_with_alt( $post_id, $image_url, $alt_text = '' ) {
		// Maybe sideload the image or get the Attachment ID of an existing one.
		$image_id = self::maybe_sideload_image( $post_id, $image_url );

		if ( is_wp_error( $image_id ) ) {
			// Handle the error.
			return $image_id;
		}

		// Set the image as the featured image for the post.
		set_post_thumbnail( $post_id, $image_id );

		// Update the alt text.
		if ( ! empty( $alt_text ) ) {
			update_post_meta( $image_id, '_wp_attachment_image_alt', $alt_text );
		}

		return $image_id; // Return the attachment ID for further use if needed.
	}

	/**
	 * Convert a PostalAddress to a string.
	 *
	 * @link https://schema.org/PostalAddress
	 *
	 * @param array $postal_address The PostalAddress as an associative array.
	 * @return string
	 */
	private static function postal_address_to_string( $postal_address ) {
		if ( ! is_array( $postal_address ) || 'PostalAddress' !== $postal_address['type'] ) {
			_doing_it_wrong(
				__METHOD__,
				'The parameter postal_address must be an associate array like schema.org/PostalAddress.',
				esc_html( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_VERSION )
			);
		}

		$address = array();

		$known_attributes = array(
			'streetAddress',
			'postalCode',
			'addressLocality',
			'addressState',
			'addressCountry',
		);

		foreach ( $known_attributes as $attribute ) {
			if ( isset( $postal_address[ $attribute ] ) && is_string( $postal_address[ $attribute ] ) ) {
				$address[] = $postal_address[ $attribute ];
			}
		}

		$address_string = implode( ' ,', $address );

		return $address_string;
	}

	/**
	 * Convert an address to a string.
	 *
	 * @param mixed $address The address as an object, string or associative array.
	 * @return string
	 */
	protected static function address_to_string( $address ) {
		if ( is_string( $address ) ) {
			return $address;
		}

		if ( is_object( $address ) ) {
			$address = (array) $address;
		}

		if ( ! is_array( $address ) || ! isset( $address['type'] ) ) {
			return '';
		}

		if ( 'PostalAddress' === $address['type'] ) {
			return self::postal_address_to_string( $address );
		}
		return '';
	}

	/**
	 * Return the number of revisions to keep.
	 *
	 * @return     int   The number of revisions to keep.
	 */
	public static function revisions_to_keep() {
		return 5;
	}

	/**
	 * Returns the URL of the online event link.
	 *
	 * @param Event $event The ActivityPub event object.
	 *
	 * @return ?string
	 */
	protected static function get_online_event_link_from_attachments( $event ) {
		$attachments = $event->get_attachment();

		if ( ! is_array( $attachments ) || empty( $attachments ) ) {
			return;
		}

		foreach ( $attachments as $attachment ) {
			if ( array_key_exists( 'type', $attachment ) && 'Link' === $attachment['type'] && isset( $attachment['href'] ) ) {
				return $attachment['href'];
			}
		}
	}
}
