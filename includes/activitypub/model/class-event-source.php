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

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

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
 *
 * @method ?string get_published()
 * @method string get_id()
 * @method ?string get_name()
 * @method ?string get_updated()
 * @method int    get__id()
 * @method ?string get_status()
 * @method ?string get_summary()
 * @method Event_Source set_published(string $published)
 * @method Event_Source set_id(string $id)
 * @method Event_Source set_name(string $name)
 * @method Event_Source set_updated(string $updated)
 * @method Event_Source set__id(int $id)
 * @method Event_Source set_status(string $status)
 * @method Event_Source set_summary(string $summary)
 * @method ?string get_inbox()
 * @method string|array get_icon()
 * @method array get_endpoints()
 */
class Event_Source extends Actor {
	const ACTIVITYPUB_USER_HANDLE_REGEXP = '(?:([A-Za-z0-9_.-]+)@((?:[A-Za-z0-9_-]+\.)+[A-Za-z]+))';

	/**
	 * The WordPress Post ID which stores the event source.
	 *
	 * @var int
	 */
	protected $_id; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * The WordPress post status of the post which stores the event source.
	 *
	 * @var string
	 */
	protected $status; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Get the Icon URL (Avatar).
	 *
	 * @return string The URL to the Avatar.
	 */
	public function get_icon_url(): string {
		$icon = $this->get_icon();

		if ( is_string( $icon ) ) {
			return $icon;
		}

		if ( isset( $icon['url'] ) && is_string( $icon['url'] ) ) {
			return $icon['url'];
		}

		return '';
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
	 * Get the outbox.
	 *
	 * @return ?string The outbox URL.
	 */
	public function get_outbox() {
		if ( $this->outbox ) {
			return $this->outbox;
		}

		$actor_json = \get_post_meta( $this->get__id(), 'activitypub_actor_json', true );

		if ( ! $actor_json ) {
			return null;
		}

		$actor = \json_decode( $actor_json, true );

		if ( ! isset( $actor['outbox'] ) ) {
			\do_action( 'event_bridge_for_activitypub_write_log', array( "[ACTIVITYPUB] Did not find outbox URL for actor {$actor}" ) );
			return null;
		}

		return $actor['outbox'];
	}

	/**
	 * Get the Event Source Post ID by the ActivityPub ID.
	 *
	 * @param string $activitypub_actor_id The ActivityPub actor ID.
	 * @return int|false The Event Sources Post ID, if a WordPress Post representing it is found, false otherwise.
	 */
	public static function get_post_id_by_activitypub_id( $activitypub_actor_id ) {
		$event_sources = Event_Sources::get_event_sources();

		return array_search( $activitypub_actor_id, $event_sources, true );
	}

	/**
	 * Get the Event Source by the ActivityPub ID or WordPress Post ID.
	 *
	 * @param int|string $event_source_id The ActivityPub actor ID as string or the Post ID as int of the Event Source.
	 * @return ?Event_Source The Event Sources if it exists, false otherwise.
	 */
	public static function get_by_id( $event_source_id ): ?Event_Source {
		$post_id = is_integer( $event_source_id ) ? $event_source_id : self::get_post_id_by_activitypub_id( $event_source_id );

		if ( ! $post_id ) {
			return null;
		}

		// Get Custom Post.
		$event_source_post = \get_post( $post_id );

		if ( ! $event_source_post ) {
			return null;
		}

		// Init From Custom Post.
		$event_source = self::init_from_cpt( $event_source_post );

		if ( ! $event_source ) {
			return null;
		}

		return $event_source;
	}

	/**
	 * Convert a Custom-Post-Type input to an \Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source.
	 *
	 * @param \WP_Post $post The post object.
	 * @return ?Event_Source
	 */
	public static function init_from_cpt( $post ): ?Event_Source {
		if ( Event_Sources::POST_TYPE !== $post->post_type ) {
			return null;
		}
		$actor_json = get_post_meta( $post->ID, 'activitypub_actor_json', true );
		$object     = static::init_from_json( $actor_json );
		$object->set__id( $post->ID );
		$object->set_id( $post->guid );
		$object->set_name( $post->post_title );
		$object->set_summary( $post->post_excerpt );
		$object->set_published( gmdate( 'Y-m-d H:i:s', strtotime( $post->post_date ) ) );
		$object->set_updated( gmdate( 'Y-m-d H:i:s', strtotime( $post->post_modified ) ) );
		$object->set_status( $post->post_status );
		$thumbnail_id = get_post_thumbnail_id( $post );
		if ( $thumbnail_id ) {
			$object->set_icon(
				array(
					'type' => 'Image',
					'url'  => wp_get_attachment_image_url( $thumbnail_id, 'thumbnail', true ),
				)
			);
		}

		if ( ! $object instanceof Event_Source ) { // To make phpstan happy.
			return null;
		}

		return $object;
	}

	/**
	 * Validate the current Event Source ActivityPub actor object.
	 *
	 * @return boolean True if the verification was successful.
	 */
	public function is_valid(): bool {
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
	public function get_shared_inbox(): mixed {
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
		Event_Sources::delete_event_source_transients();

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
			'post_status'  => 'pending',
			'meta_input'   => $this->get_post_meta_input(),
		);

		if ( ! empty( $post_id ) ) {
			// If this is an update, prevent the "added" date from being overwritten by the current date.
			$post                  = get_post( $post_id );
			$args['post_date']     = $post->post_date;
			$args['post_date_gmt'] = $post->post_date_gmt;
		}

		$post_id   = \wp_insert_post( $args );
		$this->_id = $post_id;

		// Abort if inserting or updating the post didn't work.

		// @phpstan-ignore-next-line
		if ( is_wp_error( $post_id ) || 0 === $post_id ) {
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

	/**
	 * Delete an Event Source and it's profile image.
	 */
	public function delete() {
		$post_id = $this->get__id();

		if ( ! $post_id ) {
			return false;
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( $thumbnail_id ) {
			wp_delete_attachment( $thumbnail_id, true );
		}

		$result = wp_delete_post( $post_id, false ) ?? false;

		if ( $result ) {
			Event_Sources::delete_event_source_transients();
		}

		return $result;
	}
}
