<?php
/**
 * Event sources collection file.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Collection;

use WP_Error;
use WP_Query;
use Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source;

use function Activitypub\is_tombstone;
use function Activitypub\get_remote_metadata_by_actor;

/**
 * ActivityPub Event Sources (=Followed Actors) Collection.
 */
class Event_Sources {
	/**
	 * The custom post type.
	 */
	const POST_TYPE = 'ebap_event_source';

	/**
	 * Init.
	 */
	public static function init() {
		self::register_post_type();
		\add_action( 'event_bridge_for_activitypub_follow', array( self::class, 'activitypub_follow_actor' ), 10, 2 );
		\add_action( 'event_bridge_for_activitypub_unfollow', array( self::class, 'activitypub_unfollow_actor' ), 10, 2 );
	}

	/**
	 * Register the post type used to store the external event sources (i.e., followed ActivityPub actors).
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'           => array(
					'name'          => _x( 'Event Sources', 'post_type plural name', 'event-bridge-for-activitypub' ),
					'singular_name' => _x( 'Event Source', 'post_type single name', 'event-bridge-for-activitypub' ),
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
			'activitypub_actor_id',
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
						throw new \Exception( 'Error message is no valid string' );
					}

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

		\register_post_meta(
			self::POST_TYPE,
			'event_source_active',
			array(
				'type'   => 'bool',
				'single' => true,
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
			'event_source_utilize_announces',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => function ( $value ) {
					if ( 'same_origin' === $value ) {
						return 'same_origin';
					}
					return '';
				},
			)
		);
	}

	/**
	 * Add new Event Source.
	 *
	 * @param string $actor The Actor URL/ID.
	 *
	 * @return Event_Source|WP_Error The Followed (WP_Post array) or an WP_Error.
	 */
	public static function add_event_source( $actor ) {
		$meta = get_remote_metadata_by_actor( $actor );

		if ( is_tombstone( $meta ) ) {
			return $meta;
		}

		if ( empty( $meta ) || ! is_array( $meta ) || is_wp_error( $meta ) ) {
			return new WP_Error( 'activitypub_invalid_actor', __( 'Invalid ActivityPub Actor', 'event-bridge-for-activitypub' ), array( 'status' => 400 ) );
		}

		$event_source = new Event_Source();
		$event_source->from_array( $meta );

		$post_id = $event_source->save();

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		self::queue_follow_actor( $actor );

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
	 * Get all Event-Sources.
	 *
	 * @return array The Term list of Event Sources.
	 */
	public static function get_event_sources() {
		$args = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'DESC',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => array(
				array(
					'key'     => 'activitypub_actor_id',
					'compare' => 'EXISTS',
				),
			),
		);

		$query = new WP_Query( $args );

		$event_sources = array_map(
			function ( $post ) {
				return Event_Source::init_from_cpt( $post );
			},
			$query->get_posts()
		);

		return $event_sources;
	}

	/**
	 * Get the Event Sources along with a total count for pagination purposes.
	 *
	 * @param int   $number  Maximum number of results to return.
	 * @param int   $page    Page number.
	 * @param array $args    The WP_Query arguments.
	 *
	 * @return array {
	 *      Data about the followers.
	 *
	 *      @type array $followers List of `Follower` objects.
	 *      @type int   $total     Total number of followers.
	 *  }
	 */
	public static function get_event_sources_with_count( $number = -1, $page = null, $args = array() ) {
		$defaults = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => $number,
			'paged'          => $page,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		);

		$args   = wp_parse_args( $args, $defaults );
		$query  = new WP_Query( $args );
		$total  = $query->found_posts;
		$actors = array_map(
			function ( $post ) {
				return Event_Source::init_from_cpt( $post );
			},
			$query->get_posts()
		);

		return compact( 'actors', 'total' );
	}

	/**
	 * Remove a Follower.
	 *
	 * @param string $event_source   The Actor URL.
	 *
	 * @return mixed True on success, false on failure.
	 */
	public static function remove( $event_source ) {
		$post_id = Event_Source::get_wp_post_from_activitypub_actor_id( $event_source );
		return wp_delete_post( $post_id, true );
	}

	/**
	 * Queue a hook to run async.
	 *
	 * @param string $hook The hook name.
	 * @param array  $args The arguments to pass to the hook.
	 * @param string $unqueue_hook Optional a hook to unschedule before queuing.
	 * @return void|bool Whether the hook was queued.
	 */
	public static function queue( $hook, $args, $unqueue_hook = null ) {
		if ( $unqueue_hook ) {
			$hook_timestamp = wp_next_scheduled( $unqueue_hook, $args );
			if ( $hook_timestamp ) {
				wp_unschedule_event( $hook_timestamp, $unqueue_hook, $args );
			}
		}

		if ( wp_next_scheduled( $hook, $args ) ) {
			return;
		}

		return \wp_schedule_single_event( \time(), $hook, $args );
	}

	/**
	 * Prepare to follow an ActivityPub actor via a scheduled event.
	 *
	 * @param      string $actor  The ActivityPub actor.
	 *
	 * @return     bool|WP_Error  Whether the event was queued.
	 */
	public static function queue_follow_actor( $actor ) {
		$queued = self::queue(
			'event_bridge_for_activitypub_follow',
			$actor,
			'event_bridge_for_activitypub_unfollow'
		);

		return $queued;
	}

	/**
	 * Follow an ActivityPub actor via the Application user.
	 *
	 * @param string $actor_id The ID/URL of the Actor.
	 */
	public static function activitypub_follow_actor( $actor_id ) {
		$post_id = Event_Source::get_wp_post_from_activitypub_actor_id( $actor_id );

		if ( ! $post_id ) {
			return;
		}

		$actor = Event_Source::init_from_cpt( get_post( $post_id ) );

		if ( ! $actor instanceof Event_Source ) {
			return $actor;
		}

		$inbox = $actor->get_shared_inbox();
		$to    = $actor->get_id();

		$application = new \Activitypub\Model\Application();

		$activity = new \Activitypub\Activity\Activity();
		$activity->set_type( 'Follow' );
		$activity->set_to( null );
		$activity->set_cc( null );
		$activity->set_actor( $application->get_id() );
		$activity->set_object( $to );
		$activity->set_id( $application->get_id() . '#follow-' . \preg_replace( '~^https?://~', '', $to ) );
		$activity = $activity->to_json();
		\Activitypub\safe_remote_post( $inbox, $activity, \Activitypub\Collection\Actors::APPLICATION_USER_ID );
	}

	/**
	 * Prepare to unfollow an actor via a scheduled event.
	 *
	 * @param      string $actor  The ActivityPub actor ID.
	 *
	 * @return     bool|WP_Error              Whether the event was queued.
	 */
	public static function queue_unfollow_actor( $actor ) {
		$queued = self::queue(
			'event_bridge_for_activitypub_unfollow',
			$actor,
			'event_bridge_for_activitypub_follow'
		);

		return $queued;
	}

	/**
	 * Unfollow an ActivityPub actor.
	 *
	 * @param      string $actor_id  The ActivityPub actor ID.
	 */
	public static function activitypub_unfollow_actor( $actor_id ) {
		$post_id = Event_Source::get_wp_post_from_activitypub_actor_id( $actor_id );

		if ( ! $post_id ) {
			return;
		}

		$actor = Event_Source::init_from_cpt( get_post( $post_id ) );

		if ( ! $actor instanceof Event_Source ) {
			return $actor;
		}

		$inbox = $actor->get_shared_inbox();
		$to    = $actor->get_id();

		$application = new \Activitypub\Model\Application();

		if ( is_wp_error( $inbox ) ) {
			return $inbox;
		}

		$activity = new \Activitypub\Activity\Activity();
		$activity->set_type( 'Undo' );
		$activity->set_to( null );
		$activity->set_cc( null );
		$activity->set_actor( $application->get_id() );
		$activity->set_object(
			array(
				'type'   => 'Follow',
				'actor'  => $actor,
				'object' => $to,
				'id'     => $to,
			)
		);
		$activity->set_id( $actor . '#unfollow-' . \preg_replace( '~^https?://~', '', $to ) );
		$activity = $activity->to_json();
		\Activitypub\safe_remote_post( $inbox, $activity, \Activitypub\Collection\Actors::APPLICATION_USER_ID );
	}
}
