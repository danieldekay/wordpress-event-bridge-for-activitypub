<?php
/**
 * ActivityPub Transmogrify for the The Events Calendar event plugin.
 *
 * Handles converting incoming external ActivityPub events to The Events Calendar Events.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Activitypub\Transmogrifier;

use DateTime;

use function Activitypub\sanitize_url;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use GatherPress\Core\Event as GatherPress_Event;

/**
 * ActivityPub Transmogrifier for the GatherPress event plugin.
 *
 * Handles converting incoming external ActivityPub events to GatherPress Events.
 *
 * @since 1.0.0
 */
class The_Events_Calendar extends Base {
	/**
	 * Get a list of Post IDs of events that have ended.
	 *
	 * @param int $cache_retention_period Additional time buffer in seconds.
	 * @return ?array
	 */
	public static function get_past_events( $cache_retention_period = 0 ): ?array {
		unset( $cache_retention_period );

		$results = array();

		return $results;
	}

	/**
	 * Save the ActivityPub event object as GatherPress Event.
	 *
	 * @return void
	 */
	public function save_event(): void {
		// Limit this as a safety measure.
		add_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		$this->get_post_id_from_activitypub_id();

		// Limit this as a safety measure.
		remove_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );
	}
}
