<?php
/**
 * ActivityPub Event Sources (=Followed Actors) Collection.
 *
 * The Event Sources are nothing else than follows in the ActivityPub world.
 * However, this plugins currently only listens to Event object being created,
 * updated or deleted by a follow.
 *
 * For the ActivityPub `Follow` the Blog-Actor from the ActivityPub plugin is used.
 *
 * This class is responsible for defining a custom post type in WordPress along
 * with post-meta fields and methods to easily manage event sources. This includes
 * handling side effects, like when an event source is added a follow request is sent
 * or adding them to the `follow` collection o the blog-actor profile.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Collection;

use Activitypub\Model\Blog;
use Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source;
use WP_Error;
use WP_Query;

use function Activitypub\is_tombstone;
use function Activitypub\get_remote_metadata_by_actor;

/**
 * ActivityPub Event Sources (=Followed Actors) Collection.
 *
 * The Event Sources are nothing else than follows in the ActivityPub world.
 * However, this plugins currently only listens to Event object being created,
 * updated or deleted by a follow.
 *
 * For the ActivityPub `Follow` the Blog-Actor from the ActivityPub plugin is used.
 *
 * This class is responsible for defining a custom post type in WordPress along
 * with post-meta fields and methods to easily manage event sources. This includes
 * handling side effects, like when an event source is added a follow request is sent
 * or adding them to the `follow` collection o the blog-actor profile.
 */
class Event_Sources {
	/**
	 * The custom post type.
	 */
	const POST_TYPE = 'ap_event_source';

	/**
	 * Init.
	 */
	public static function init() {
		self::register_post_type();
		\add_action( 'event_bridge_for_activitypub_follow', array( self::class, 'activitypub_follow_actor' ), 10, 1 );
		\add_action( 'event_bridge_for_activitypub_unfollow', array( self::class, 'activitypub_unfollow_actor' ), 10, 1 );
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
			'activitypub_inbox',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_url',
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'_event_bridge_for_activitypub_utilize_announces',
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

		\register_post_meta(
			self::POST_TYPE,
			'_event_bridge_for_activitypub_accept_of_follow',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_url',
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'_event_bridge_for_activitypub_event_count',
			array(
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'default'           => '0',
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

		self::delete_event_source_transients();

		return $event_source;
	}

	/**
	 * Compose the ActivityPub ID of a follow request.
	 *
	 * @param string $follower_id The ActivityPub ID of the actor that followers the other one.
	 * @param string $followed_id The ActivityPub ID of the followed actor.
	 * @return string The `Follow` ID.
	 */
	public static function compose_follow_id( $follower_id, $followed_id ) {
		return $follower_id . '#follow-' . \preg_replace( '~^https?://~', '', $followed_id );
	}

	/**
	 * Delete all transients related to the event sources.
	 *
	 * @return void
	 */
	public static function delete_event_source_transients(): void {
		delete_transient( 'event_bridge_for_activitypub_event_sources' );
		delete_transient( 'event_bridge_for_activitypub_event_sources_hosts' );
		delete_transient( 'event_bridge_for_activitypub_event_sources_ids' );
	}

	/**
	 * Check whether an attachment is set as a featured image of any post.
	 *
	 * @param string|int $attachment_id The numeric post ID of the attachment.
	 * @return bool
	 */
	public static function is_attachment_featured_image( $attachment_id ) {
		if ( ! is_numeric( $attachment_id ) ) {
			return false;
		}

		// Query posts with the given attachment ID as their featured image.
		$args = array(
			'post_type'   => 'any',
			'meta_query'  => array(
				array(
					'key'     => '_thumbnail_id',
					'value'   => $attachment_id,
					'compare' => '=',
				),
			),
			'fields'      => 'ids', // Only retrieve post IDs for performance.
			'numberposts' => 1,     // We only need one match to confirm.
		);

		$posts = \get_posts( $args );

		return ! empty( $posts );
	}

	/**
	 * Delete all posts of an event source.
	 *
	 * @param string $event_source_id The ActivityPub ID of the event source.
	 * @return void
	 */
	public static function delete_events_by_event_source( $event_source_id ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
				'_event_bridge_for_activitypub_event_source',
				esc_sql( $event_source_id )
			)
		);

		// If no matching posts are found, return early.
		if ( empty( $results ) || ! $results ) {
			return;
		}

		// Loop through the posts and delete them permanently.
		foreach ( $results as $post ) {
			// Check if the post has a thumbnail.
			$thumbnail_id = get_post_thumbnail_id( $post->post_id );

			if ( $thumbnail_id ) {
				// Remove the thumbnail from the post.
				\delete_post_thumbnail( $post->post_id );

				// Delete the attachment (and its files) from the media library.
				if ( self::is_attachment_featured_image( $thumbnail_id ) ) {
					\wp_delete_attachment( $thumbnail_id, true );
				}
			}

			\wp_delete_post( $post->post_id, true );
		}

		// Clean up the query.
		\wp_reset_postdata();
	}

	/**
	 * Remove an Event Source (=Followed ActivityPub actor).
	 *
	 * @param string $actor  The Actor URL.
	 *
	 * @return WP_Post|false|null Post data on success, false or null on failure.
	 */
	public static function remove_event_source( $actor ) {
		self::delete_event_source_transients();

		$actor = Event_Source::get_by_id( $actor );

		if ( ! $actor ) {
			return;
		}

		$post_id = $actor->get__id();

		if ( ! $post_id ) {
			return;
		}

		self::delete_events_by_event_source( $actor->get_id() );

		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( $thumbnail_id ) {
			wp_delete_attachment( $thumbnail_id, true );
		}

		$result = wp_delete_post( $post_id, false );

		// If the deletion was successful delete all transients regarding event sources.
		if ( $result ) {
			self::queue_unfollow_actor( $actor );
		}

		return $result;
	}

	/**
	 * Get all Event-Sources.
	 *
	 * @return Event_Source[] A List of all Event Sources (follows).
	 */
	public static function get_event_sources() {
		return self::get_event_sources_with_count()['actors'];
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
		$event_sources = get_transient( 'event_bridge_for_activitypub_event_sources' );

		if ( $event_sources ) {
			return $event_sources;
		}

		$defaults = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => $number,
			'paged'          => $page,
			'orderby'        => 'ID',
			'order'          => 'DESC',
			'post_status'    => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit' ),
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

		$event_sources = compact( 'actors', 'total' );

		set_transient( 'event_bridge_for_activitypub_event_sources', $event_sources );

		return $event_sources;
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
			array( $actor ),
			'event_bridge_for_activitypub_unfollow'
		);

		return $queued;
	}

	/**
	 * Follow an ActivityPub actor via the Blog user.
	 *
	 * @param string $actor_id The ID/URL of the Actor.
	 */
	public static function activitypub_follow_actor( $actor_id ) {
		$actor = Event_Source::get_by_id( $actor_id );

		if ( ! $actor ) {
			return $actor;
		}

		$inbox = $actor->get_shared_inbox();
		$to    = $actor->get_id();

		$application = new Blog();

		$activity = new \Activitypub\Activity\Activity();
		$activity->set_type( 'Follow' );
		$activity->set_to( null );
		$activity->set_cc( null );
		$activity->set_actor( $application->get_id() );
		$activity->set_object( $to );
		$activity->set_id( self::compose_follow_id( $application->get_id(), $to ) );
		$activity = $activity->to_json();
		\Activitypub\safe_remote_post( $inbox, $activity, \Activitypub\Collection\Actors::BLOG_USER_ID );
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
			array( $actor ),
			'event_bridge_for_activitypub_follow'
		);

		return $queued;
	}

	/**
	 * Unfollow an ActivityPub actor.
	 *
	 * @param Event_Source $actor The ActivityPub actor model.
	 */
	public static function activitypub_unfollow_actor( $actor ) {
		if ( ! $actor instanceof Event_Source ) {
			return;
		}

		$inbox = $actor->get_shared_inbox();
		$to    = $actor->get_id();

		$application = new Blog();

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
		$activity->set_id( $application->get_id() . '#unfollow-' . \preg_replace( '~^https?://~', '', $to ) );
		$activity = $activity->to_json();
		\Activitypub\safe_remote_post( $inbox, $activity, \Activitypub\Collection\Actors::BLOG_USER_ID );
	}
}
