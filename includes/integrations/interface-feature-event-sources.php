<?php
/**
 * Interface for defining Methods needed for the Event Sources feature.
 *
 * The Event Sources feature is about following other ActivityPub actors and
 * importing their events. That means treating them as cache and listing them.
 * Events should be deleted some time after they have ended.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Integrations;

use Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\Base as Transmogrifier;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Interface for an event plugin integration that supports the Event Sources feature.
 *
 * @since 1.0.0
 */
interface Feature_Event_Sources {
	/**
	 * Returns the plugin file relative to the plugins dir.
	 *
	 * @return Transmogrifier
	 */
	public static function get_transmogrifier(): Transmogrifier;

	/**
	 * Retrieves a list of post IDs for cached remote events that have ended.
	 *
	 * Filters the events to include only those that ended before the specified timestamp.
	 *
	 * @param int $ends_before_time Unix timestamp. Only events ending before this time will be included.
	 *
	 * @return int[] List of post IDs for events that match the criteria.
	 */
	public static function get_cached_remote_events( $ends_before_time ): array;
}
