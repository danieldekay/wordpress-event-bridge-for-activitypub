<?php
/**
 * Event-Source (=ActivityPub Actor that is followed) model.
 *
 * This class holds methods needed for relating an ActivityPub actor
 * that is followed with the custom post type structure how it is
 * stored within WordPress.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Model;

use Activitypub\Activity\Actor;
use Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources;
use WP_Error;
use WP_Post;

use function Activitypub\sanitize_url;

/**
 * Event-Source (=ActivityPub Actor that is followed) model.
 *
 * This class holds methods needed for relating an ActivityPub actor
 * that is followed with the custom post type structure how it is
 * stored within WordPress.
 */
class Event_Source extends Actor {
	const ACTIVITYPUB_USER_HANDLE_REGEXP = '(?:([A-Za-z0-9_.-]+)@((?:[A-Za-z0-9_-]+\.)+[A-Za-z]+))';

	/**
	 * The complete remote ActivityPub profile of the Event Source.
	 *
	 * @var int
	 */
	protected $_id; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Get the Icon URL (Avatar).
	 *
	 * @return string The URL to the Avatar.
	 */
	public function get_icon_url() {
		$icon = $this->get_icon();

		if ( ! $icon ) {
			return '';
		}

		if ( is_array( $icon ) ) {
			return $icon['url'];
		}

		return $icon;
	}

	/**
	 * Return the Post-IDs of all events cached by this event source.
	 */
	public static function get_cached_events(): array {
		return array();
	}

	/**
	 * Getter for URL attribute.
	 *
	 * @return string The URL.
	 */
	public function get_url() {
		if ( $this->url ) {
			return $this->url;
		}

		return $this->id;
	}

	/**
	 * Get the WordPress post which stores the Event Source by the ActivityPub actor id of the event source.
	 *
	 * @param string $actor_id The ActivityPub actor ID.
	 * @return ?WP_Post The WordPress post if the actor is found, null if not.
	 */
	private static function get_wp_post_by_activitypub_actor_id( $actor_id ) {
		global $wpdb;
		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE guid=%s AND post_type=%s",
				esc_sql( $actor_id ),
				Event_Sources::POST_TYPE
			)
		);
		return $post_id ? get_post( $post_id ) : null;
	}

	/**
	 * Get the WordPress post which stores the Event Source by the ActivityPub actor id of the event source.
	 *
	 * @param string $actor_id The ActivityPub actor ID.
	 * @return ?Event_Source The WordPress post ID if the actor is found, null if not.
	 */
	public static function get_by_id( $actor_id ) {
		$post = self::get_wp_post_by_activitypub_actor_id( $actor_id );

		if ( $post ) {
			$actor = self::init_from_cpt( $post );
		}
		return $actor ?? null;
	}

	/**
	 * Convert a Custom-Post-Type input to an \Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source.
	 *
	 * @param \WP_Post $post The post object.
	 * @return \Event_Bridge_For_ActivityPub\ActivityPub\Event_Source|WP_Error
	 */
	public static function init_from_cpt( $post ) {
		if ( Event_Sources::POST_TYPE !== $post->post_type ) {
			return false;
		}
		$actor_json = get_post_meta( $post->ID, 'activitypub_actor_json', true );
		$object     = self::init_from_json( $actor_json );
		$object->set__id( $post->ID );
		$object->set_id( $post->guid );
		$object->set_name( $post->post_title );
		$object->set_summary( $post->post_excerpt );
		$object->set_published( gmdate( 'Y-m-d H:i:s', strtotime( $post->post_date_gmt ) ) );
		$object->set_updated( gmdate( 'Y-m-d H:i:s', strtotime( $post->post_modified_gmt ) ) );
		$thumbnail_id = get_post_thumbnail_id( $post );
		if ( $thumbnail_id ) {
			$object->set_icon(
				array(
					'type' => 'Image',
					'url'  => wp_get_attachment_image_url( $thumbnail_id, 'thumbnail', true ),
				)
			);
		}

		return $object;
	}

	/**
	 * Validate the current Event Source ActivityPub actor object.
	 *
	 * @return boolean True if the verification was successful.
	 */
	public function is_valid() {
		// The minimum required attributes.
		$required_attributes = array(
			'id',
			'preferredUsername',
			'inbox',
			'publicKey',
			'publicKeyPem',
		);

		foreach ( $required_attributes as $attribute ) {
			if ( ! $this->get( $attribute ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Update the post meta.
	 */
	protected function get_post_meta_input() {
		$meta_input                           = array();
		$meta_input['activitypub_inbox']      = sanitize_url( $this->get_shared_inbox() );
		$meta_input['activitypub_actor_json'] = $this->to_json();

		return $meta_input;
	}

	/**
	 * Get the shared inbox, with a fallback to the inbox.
	 *
	 * @return string|null The URL to the shared inbox, the inbox or null.
	 */
	public function get_shared_inbox() {
		if ( ! empty( $this->get_endpoints()['sharedInbox'] ) ) {
			return $this->get_endpoints()['sharedInbox'];
		} elseif ( ! empty( $this->get_inbox() ) ) {
			return $this->get_inbox();
		}

		return null;
	}

	/**
	 * Save the current Event Source object to Database within custom post type.
	 *
	 * @return int|WP_Error The post ID or an WP_Error.
	 */
	public function save() {
		if ( ! $this->is_valid() ) {
			return new WP_Error( 'activitypub_invalid_follower', __( 'Invalid Follower', 'event-bridge-for-activitypub' ), array( 'status' => 400 ) );
		}

		if ( ! $this->get__id() ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$post_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM $wpdb->posts WHERE guid=%s",
					esc_sql( $this->get_id() )
				)
			);

			if ( $post_id ) {
				$post = get_post( $post_id );
				$this->set__id( $post->ID );
			}
		}

		$post_id = $this->get__id();

		$args = array(
			'ID'           => $post_id,
			'guid'         => esc_url_raw( $this->get_id() ),
			'post_title'   => wp_strip_all_tags( sanitize_text_field( $this->get_name() ) ),
			'post_author'  => 0,
			'post_type'    => Event_Sources::POST_TYPE,
			'post_name'    => esc_url_raw( $this->get_id() ),
			'post_excerpt' => sanitize_text_field( wp_kses( $this->get_summary(), 'user_description' ) ),
			'post_status'  => 'publish',
			'meta_input'   => $this->get_post_meta_input(),
		);

		if ( ! empty( $post_id ) ) {
			// If this is an update, prevent the "added" date from being overwritten by the current date.
			$post                  = get_post( $post_id );
			$args['post_date']     = $post->post_date;
			$args['post_date_gmt'] = $post->post_date_gmt;
		}

		$post_id   = wp_insert_post( $args );
		$this->_id = $post_id;

		// Abort if inserting or updating the post didn't work.
		if ( 0 === $post_id || is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Delete old icon.
		// Check if the post has a thumbnail.
		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( $thumbnail_id ) {
			// Remove the thumbnail from the post.
			delete_post_thumbnail( $post_id );

			// Delete the attachment (and its files) from the media library.
			wp_delete_attachment( $thumbnail_id, true );
		}

		// Set new icon.
		$icon = $this->get_icon();

		if ( isset( $icon['url'] ) ) {
			$image = media_sideload_image( sanitize_url( $icon['url'] ), $post_id, null, 'id' );
		}
		if ( isset( $image ) && ! is_wp_error( $image ) ) {
			set_post_thumbnail( $post_id, $image );
		}

		return $post_id;
	}
}
