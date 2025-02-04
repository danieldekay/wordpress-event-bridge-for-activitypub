<?php
/**
 * Join handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

use Activitypub\Activity\Activity;
use Activitypub\Collection\Actors;
use Activitypub\Activity\Actor;
use Activitypub\Http;
use Activitypub\Transformer\Factory;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Event as Event_Transformer;

use function Activitypub\get_remote_metadata_by_actor;
use function Activitypub\object_to_uri;

/**
 * Handle Join requests.
 */
class Join {
	/**
	 * Initialize the class, registering WordPress hooks.
	 */
	public static function init() {
		\add_action(
			'activitypub_register_handlers',
			array( self::class, 'register_join_handler' )
		);

		\add_action(
			'event_bridge_for_activitypub_ignore_join',
			array( self::class, 'send_ignore_response' ),
			10,
			3
		);
	}

	/**
	 * Register the join handler to the ActivityPub plugin.
	 */
	public static function register_join_handler() {
		\add_action(
			'activitypub_inbox_join',
			array( self::class, 'handle_join' )
		);
	}

	/**
	 * Handle ActivityPub "Join" requests.
	 *
	 * @param array $activity The activity object.
	 */
	public static function handle_join( $activity ) {
		$actor = get_remote_metadata_by_actor( object_to_uri( $activity['actor'] ) );

		// If we cannot fetch the actor, we cannot continue.
		if ( \is_wp_error( $actor ) ) {
			return;
		}

		// This should be already validated, but just to be sure.
		if ( ! array_key_exists( 'object', $activity ) ) {
			return;
		}

		// Get the WordPress Post ID, via the ActivityPub ID.
		$post_id = self::get_post_id_by_activitypub_id( \sanitize_url( object_to_uri( $activity['object'] ) ) );

		if ( ! $post_id ) {
			// No post is found for this URL/ID.
			return;
		}

		// Check whether the target object/post is an event post.

		$transformer = Factory::get_transformer( get_post( $post_id ) );

		if ( ! $transformer instanceof Event_Transformer ) {
			return;
		}

		// Pass over to Event plugin specific handler if implemented here. Until then just send an ignore.
		do_action(
			'event_bridge_for_activitypub_ignore_join',
			$transformer->get_actor_object()->get_id(), // Gets the WordPress user that "owns" the object by ActivityPub means.
			$activity['id'],
			Actor::init_from_array( $actor )
		);
	}

	/**
	 * Send "Ignore" response.
	 *
	 * @param string      $actor   The actors ActivityPub ID which sends the response.
	 * @param string      $ignored The ID of the Activity that gets ignored.
	 * @param Actor|mixed $to      The target actor.
	 */
	public static function send_ignore_response( $actor, $ignored, $to ) {
		// Get actor object that owns the object that was targeted by the ignored activity.
		$actor = Actors::get_by_resource( $actor );

		if ( \is_wp_error( $to ) || \is_wp_error( $actor ) ) {
			return;
		}

		// Get inbox.
		$inbox = $to->get_inbox();

		if ( ! $inbox ) {
			return;
		}

		// Send "Ignore" activity.
		$activity = new Activity();
		$activity->set_type( 'Ignore' );
		$activity->set_object( \esc_url_raw( $ignored ) );
		$activity->set_actor( $actor->get_id() );
		$activity->set_to( $to->get_id() );
		$activity->set_id( $actor->get_id() . '#ignore-' . \preg_replace( '~^https?://~', '', $ignored ) );
		$activity->set_sensitive( null );

		// @phpstan-ignore-next-line
		Http::post( $inbox, $activity->to_json(), $actor->get__id() );
	}

	/**
	 * Get the WordPress Post ID by the ActivityPub ID.
	 *
	 * @param string $activitypub_id The ActivityPub objects ID.
	 * @return int The WordPress post ID.
	 */
	private static function get_post_id_by_activitypub_id( $activitypub_id ) {
		// Parse the URL and extract its components.
		$parsed_url = wp_parse_url( $activitypub_id );
		$home_url   = \trailingslashit( \home_url() );

		// Ensure the base URL matches the home URL.
		if ( \trailingslashit( "{$parsed_url['scheme']}://{$parsed_url['host']}" ) !== $home_url ) {
			return 0;
		}

		// Check for a valid query string and parse it.
		if ( isset( $parsed_url['query'] ) ) {
			parse_str( $parsed_url['query'], $query_vars );

			// Ensure the only parameter is 'p'.
			if ( count( $query_vars ) === 1 && isset( $query_vars['p'] ) ) {
				return intval( $query_vars['p'] ); // Return the post ID.
			}
		}

		// Fallback: legacy ActivityPub plugin (before version 3.0.0) used pretty permalinks as `id`.
		return \url_to_postid( $activitypub_id );
	}
}
