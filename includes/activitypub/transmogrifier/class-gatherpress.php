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

namespace Event_Bridge_For_ActivityPub\Activitypub\Transmogrifier;

use Activitypub\Activity\Extended_Object\Event;
use DateTime;
use Exception;
use WP_Error;

use function Activitypub\object_to_uri;
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
class GatherPress {
	/**
	 * The current GatherPress Event object.
	 *
	 * @var Event
	 */
	protected $activitypub_event;

	/**
	 * Extend the constructor, to also set the GatherPress objects.
	 *
	 * This is a special class object form The Events Calendar which
	 * has a lot of useful functions, we make use of our getter functions.
	 *
	 * @param array $activitypub_event The ActivityPub Event as associative array.
	 */
	public function __construct( $activitypub_event ) {
		$activitypub_event = Event::init_from_array( $activitypub_event );

		if ( is_wp_error( $activitypub_event ) ) {
			return;
		}

		$this->activitypub_event = $activitypub_event;
	}

	/**
	 * Validate a time string if it is according to the ActivityPub specification.
	 *
	 * @param string $time_string The time string.
	 * @return bool
	 */
	public static function is_valid_activitypub_time_string( $time_string ) {
		// Try to create a DateTime object from the input string.
		try {
			$date = new DateTime( $time_string );
		} catch ( Exception $e ) {
			// If parsing fails, it's not valid.
			return false;
		}

		// Ensure the timezone is correctly formatted (e.g., 'Z' or a valid offset).
		$timezone           = $date->getTimezone();
		$formatted_timezone = $timezone->getName();

		// Return true only if the time string includes 'Z' or a valid timezone offset.
		$valid = 'Z' === $formatted_timezone || preg_match( '/^[+-]\d{2}:\d{2}$/ ', $formatted_timezone );
		return $valid;
	}

	/**
	 * Get a list of Post IDs of events that have ended.
	 *
	 * @param int $cache_retention_period Additional time buffer in seconds.
	 * @return int[]
	 */
	public static function get_past_events( $cache_retention_period = 0 ) {
		global $wpdb;

		$time_limit = gmdate( 'Y-m-d H:i:s', time() - $cache_retention_period );

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->prefix}gatherpress_events WHERE datetime_end < %s",
				$time_limit
			)
		);

		return $results;
	}

	/**
	 * Get post.
	 */
	private function get_post_id_from_activitypub_id() {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE guid=%s",
				esc_sql( $this->activitypub_event->get_id() )
			)
		);
	}

	/**
	 * Get the image URL and alt-text of an ActivityPub object.
	 *
	 * @param array $data The ActivityPub object as ann associative array.
	 * @return ?array Array containing the images URL and alt-text.
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
	 * @return array
	 */
	private function get_featured_image() {
		$event = $this->activitypub_event;
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
	 * @param int    $post_id   The post ID where the image will be set as featured image.
	 * @param string $url The image URL to maybe sideload.
	 * @uses media_sideload_image
	 * @return string|int|WP_Error
	 */
	public static function maybe_sideload_image( $post_id, $url = '' ) {
		global $wpdb;

		// Include necessary WordPress file for media handling.
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		// Check to see if the URL has already been fetched, if so return the attachment ID.
		$attachment_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = '_source_url' AND `meta_value` = %s", sanitize_url( $url ) )
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
		return media_sideload_image( sanitize_url( $url ), $post_id, sanitize_url( $url ), 'id' );
	}


	/**
	 * Sideload an image_url set it as featured image and add the alt-text.
	 *
	 * @param int    $post_id   The post ID where the image will be set as featured image.
	 * @param string $image_url The image URL.
	 * @param string $alt_text  The alt-text of the image.
	 * @return int The attachment ID
	 */
	private static function set_featured_image_with_alt( $post_id, $image_url, $alt_text = '' ) {
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
			update_post_meta( $image_id, '_wp_attachment_image_alt', sanitize_text_field( $alt_text ) );
		}

		return $image_id; // Return the attachment ID for further use if needed.
	}

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
			wp_set_object_terms( $post_id, $tag_names, 'gatherpress_topic', true ); // 'true' appends to existing terms.
		}

		return true;
	}

	/**
	 * Returns the URL of the online event link.
	 *
	 * @return ?string
	 */
	protected function get_online_event_link_from_attachments() {
		$attachments = $this->activitypub_event->get_attachment();

		if ( ! is_array( $attachments ) || empty( $attachments ) ) {
			return;
		}

		foreach ( $attachments as $attachment ) {
			if ( array_key_exists( 'type', $attachment ) && 'Link' === $attachment['type'] && isset( $attachment['href'] ) ) {
				return $attachment['href'];
			}
		}
	}

	/**
	 * Convert a PostalAddress to a string.
	 *
	 * @link https://schema.org/PostalAddress
	 *
	 * @param array $postal_address The PostalAddress as an associative array.
	 * @return string
	 */
	protected static function postal_address_to_string( $postal_address ) {
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

		if ( ! is_array( $address ) || ! isset( $address['type'] ) ){
			return '';
		}

		if ( 'PostalAddress' === $address['type'] ) {
			return self::postal_address_to_string( $address );
		}
		return '';
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

		$address = $this->address_to_string();

		$venue_information['fullAddress']  = $address;
		$venue_information['phone_number'] = '';
		$venue_information['website']      = '';
		$venue_information['permalink']    = '';

		$venue_json = wp_json_encode( $venue_information );

		update_post_meta( $venue_id, 'gatherpress_venue_information', $venue_json );

		wp_set_object_terms( $post_id, $venue_slug, '_gatherpress_venue', false );
	}

	/**
	 * Save the ActivityPub event object as GatherPress Event.
	 */
	public function save() {
		// Limit this as a safety measure.
		add_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		$post_id = $this->get_post_id_from_activitypub_id();

		$args = array(
			'post_title'   => sanitize_text_field( $this->activitypub_event->get_name() ),
			'post_type'    => 'gatherpress_event',
			'post_content' => wp_kses_post( $this->activitypub_event->get_content() ) . '<!-- wp:gatherpress/venue /-->',
			'post_excerpt' => wp_kses_post( $this->activitypub_event->get_summary() ),
			'post_status'  => 'publish',
			'guid'         => sanitize_url( $this->activitypub_event->get_id() ),
			'meta_input'   => array(
				'event_bridge_for_activitypub_is_cached' => 'GatherPress',
				'activitypub_content_visibility'         => ACTIVITYPUB_CONTENT_VISIBILITY_LOCAL,
			),
		);

		if ( $post_id ) {
			$args['ID'] = $post_id;
		}

		// Insert new GatherPress Event post.
		$post_id = wp_update_post( $args );

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return;
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
	}

	/**
	 * Save the ActivityPub event object as GatherPress event.
	 */
	public function delete() {
		$post_id = $this->get_post_id_from_activitypub_id();

		if ( ! $post_id ) {
			return new WP_Error(
				'event_bridge_for_activitypub_remote_event_not_found',
				\__( 'Remote event not found in cache', 'event-bridge-for-activitypub' ),
				array( 'status' => 404 )
			);
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( $thumbnail_id ) {
			wp_delete_attachment( $thumbnail_id, true );
		}

		wp_delete_post( $post_id, true );
	}

	/**
	 * Return the number of revisions to keep.
	 *
	 * @return     int   The number of revisions to keep.
	 */
	public static function revisions_to_keep() {
		return 5;
	}
}
