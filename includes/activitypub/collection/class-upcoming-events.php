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

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Base_Object;

/**
 * ActivityPub upcomingEvents.
 */
class Upcoming_Events {

	/**
	 * Init.
	 */
	public static function init(): void {
		\add_filter( 'activitypub_activity_blog_object_array', array( self::class, 'add_upcoming_events_to_actor' ), 10, 2 );
		\add_filter( 'activitypub_activity_user_object_array', array( self::class, 'add_upcoming_events_to_actor' ), 10, 3 );
	}

	/**
	 * Filter the array of the ActivityPub object by class.
	 *
	 * @param array       $object_array  The array of the ActivityPub object.
	 * @param int         $id            The ID of the ActivityPub object.
	 * @param Base_Object $object        The ActivityPub object.
	 *
	 * @return array The filtered array of the ActivityPub object.
	 */
	public static function add_upcoming_events_to_actor( $object_array, $id ): array {
		if ( isset( $object_array['upcomingEvents'] ) ) {
			return $object_array;
		}

		$object_array['upcomingEvents'] = 'someid';

		return $object_array;
	}
}
