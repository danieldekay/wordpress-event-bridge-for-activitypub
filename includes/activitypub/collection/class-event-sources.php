<?php
/**
 * Event sources collection file.
 *
 * @package ActivityPub_Event_Bridge
 * @license AGPL-3.0-or-later
 */

namespace ActivityPub_Event_Bridge\ActivityPub\Collection;

use WP_Error;
use WP_Query;
use ActivityPub_Event_Bridge\ActivityPub\Event_Source;

use function Activitypub\is_tombstone;
use function Activitypub\get_remote_metadata_by_actor;

/**
 * ActivityPub Event Sources (=Followed Actors) Collection.
 */
class Event_Sources {
	/**
	 * The custom post type.
	 */
	const POST_TYPE = 'activitypub_event_bridge_follow';

	/**
	 * Register the post type used to store the external event sources (i.e., followed ActivityPub actors).
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'           => array(
					'name'          => _x( 'Event Sources', 'post_type plural name', 'activitypub' ),
					'singular_name' => _x( 'Event Source', 'post_type single name', 'activitypub' ),
				),
				'public'           => false,
				'hierarchical'     => false,
				'rewrite'          => false,
				'query_var'        => false,
				'delete_with_user' => false,
				'can_export'       => true,
				'supports'         => array(),
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'activitypub_inbox',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_url',
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'activitypub_errors',
			array(
				'type'              => 'string',
				'single'            => false,
				'sanitize_callback' => function ( $value ) {
					if ( ! is_string( $value ) ) {
						throw new Exception( 'Error message is no valid string' );
					}

					return esc_sql( $value );
				},
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'activitypub_user_id',
			array(
				'type'              => 'string',
				'single'            => false,
				'sanitize_callback' => function ( $value ) {
					return esc_sql( $value );
				},
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'activitypub_actor_json',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => function ( $value ) {
					return sanitize_text_field( $value );
				},
			)
		);
	}

	/**
	 * Add new Event Source.
	 *
	 * @param string $actor The Actor ID.
	 *
	 * @return Event_Source|WP_Error The Followed (WP_Post array) or an WP_Error.
	 */
	public static function add_event_source( $actor ) {
		$meta = get_remote_metadata_by_actor( $actor );

		if ( is_tombstone( $meta ) ) {
			return $meta;
		}

		if ( empty( $meta ) || ! is_array( $meta ) || is_wp_error( $meta ) ) {
			return new WP_Error( 'activitypub_invalid_follower', __( 'Invalid Follower', 'activitypub' ), array( 'status' => 400 ) );
		}

		$event_source = new Event_Source();
		$event_source->from_array( $meta );

		$post_id = $event_source->save();

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		return $event_source;
	}

	/**
	 * Remove an Event Source (=Followed ActivityPub actor).
	 *
	 * @param string $actor  The Actor URL.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function remove_event_source( $actor ) {
		$actor = true;
		return $actor;
	}

	/**
	 * Get all Followers.
	 *
	 * @return array The Term list of Followers.
	 */
	public static function get_all_followers() {
		$args = array(
			'nopaging'   => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'activitypub_inbox',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'activitypub_actor_json',
					'compare' => 'EXISTS',
				),
			),
		);
		return self::get_followers( null, null, null, $args );
	}
}
