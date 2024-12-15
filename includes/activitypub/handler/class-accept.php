<?php
/**
 * Accept handler file.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Handler;

use Activitypub\Collection\Actors;

/**
 * Handle Accept requests.
 */
class Accept {
	/**
	 * Initialize the class, registering WordPress hooks.
	 */
	public static function init() {
		\add_action(
			'activitypub_inbox_accept',
			array( self::class, 'handle_accept' )
		);
	}

	/**
	 * Handle "Follow" requests.
	 *
	 * @param array $activity The activity object.
	 */
	public static function handle_accept( $activity ) {
		if ( ! isset( $activity['object'] ) ) {
			return;
		}

		$object = Actors::get_by_resource( $activity['object'] );

		if ( ! $object || is_wp_error( $object ) ) {
			// If we can not find a actor, we handle the `Accept` activity.
			return;
		}

		// We only expect `Accept` activities being answers to follow requests by the application actor.
		if ( Actors::BLOG_USER_ID !== $object->get__id() ) {
			return;
		}
	}
}
