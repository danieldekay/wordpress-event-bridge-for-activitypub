<?php
/**
 * Join handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Activitypub\Handler;

use Activitypub\Activity\Activity;
use Activitypub\Collection\Actors;
use Activitypub\Http;
use Activitypub\Transformer\Factory;
use Event_Bridge_For_ActivityPub\Activitypub\Transformer\Event as Event_Transformer;

use function Activitypub\is_same_domain;
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
			'activitypub_inbox_join',
			array( self::class, 'handle_join' )
		);

		\add_action(
			'event_bridge_for_activitypub_ignore_join',
			array( self::class, 'send_ignore_response' ),
			10,
			4
		);
	}

	/**
	 * Handle ActivityPub "Join" requests.
	 *
	 * @param array $activity The activity object.
	 */
	public static function handle_follow( $activity ) {
		$actor = Actors::get_by_resource( $activity['actor'] );

		if ( ! $actor || \is_wp_error( $actor ) ) {
			// If we can not find a user, we can not proceed the join process.
			return;
		}

		if ( ! array_key_exists( 'object', $activity ) ) {
			// If the object is not set, we can not proceed the join process.
			return;
		}

		$object_id = object_to_uri( $activity['object'] );

		if ( ! is_same_domain( $object_id ) ) {
			// If the "Join" object is not owned by this WordPress site, abort.
			return;
		}

		$post_id = \url_to_postid( $object_id );

		if ( ! $post_id ) {
			// No post is found for this URL/ID.
			return;
		}

		$transformer = Factory::get_transformer( get_post( $post_id ) );

		if ( ! $transformer instanceof Event_Transformer ) {
			// The target post is not an event post.
			return;
		}

		// Pass over to Event plugin specific handler if implemented here.
		// Until then just send an ignore.

		do_action(
			'event_bridge_for_activitypub_ignore_join',
			Actors::APPLICATION_USER_ID,
			$activity,
		);
	}

	/**
	 * Send "Ignore" response.
	 *
	 * @param string $actor           The actors ActivityPub ID which sends the response.
	 * @param array  $activity_object The Activity object that gets ignored.
	 * @param string $to              The target actor.
	 */
	public static function send_ignore_response( $actor, $activity_object, $to = null ) {
		if ( ! $to && array_key_exists( 'actor', $activity_object ) ) {
			$to = object_to_uri( $activity_object['actor'] );
		}

		if ( ! $to ) {
			return;
		}

		// Only send minimal data.
		$activity_object = array_intersect_key(
			$activity_object,
			array_flip(
				array(
					'id',
					'type',
					'actor',
					'object',
				)
			)
		);

		$to    = Actors::get_by_resource( $to );
		$actor = Actors::get_by_resource( $actor );

		// Get inbox.
		$inbox = $to->get_shared_inbox();

		// Send "Ignore" activity.
		$activity = new Activity();
		$activity->set_type( 'Ignore' );
		$activity->set_object( $activity_object );
		$activity->set_actor( $actor->get_id() );
		$activity->set_to( $to );
		$activity->set_id( $actor->get_id() . '#ignore-' . \preg_replace( '~^https?://~', '', $activity_object['id'] ) );

		$activity = $activity->to_json();

		Http::post( $inbox, $activity, $actor->get__id() );
	}
}
