<?php
/**
 * Class file for parsing an ActivityPub outbox for Events.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub;

use Activitypub\Http;
use Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source;

/**
 * Class for parsing an ActivityPub outbox for Events.
 */
class Outbox_Parser {
	/**
	 * Maximum number of events to backfill per actor.
	 */
	const MAX_EVENTS_TO_IMPORT = 20;

	/**
	 * Init actions.
	 */
	public static function init() {
		// Add action for backfilling the events.
		\add_action( 'event_bridge_for_activitypub_backfill_events', array( self::class, 'backfill_events' ), 10, 1 );
		\add_action( 'event_bridge_for_activitypub_import_events_from_outbox', array( self::class, 'import_events_from_outbox' ), 10, 2 );
	}

	/**
	 * Check if an Activity is of type Update or Create.
	 *
	 * @param array $activity The Activity as associative array.
	 * @return bool
	 */
	public static function is_create_or_update_activity( $activity ) {
		if ( ! isset( $activity['type'] ) ) {
			return false;
		}
		if ( in_array( $activity['type'], array( 'Update', 'Create' ), true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Parses items from an Collection, OrderedCollection, CollectionPage or OrderedCollectionPage.
	 *
	 * @param array $items The items as an associative array.
	 * @param int   $max_items  The maximum number of items to parse.
	 * @return array Parsed events from the collection.
	 */
	private static function parse_items_for_events( $items, $max_items ) {
		$parsed_events = array();

		foreach ( $items as $activity ) {
			// Abort if we have exceeded the maximal events to return.
			if ( $max_items > 0 && count( $parsed_events ) >= $max_items ) {
				break;
			}

			// Check if it is a create or update Activity.
			if ( ! self::is_create_or_update_activity( $activity ) ) {
				continue;
			}

			// If no object is set we cannot process anything.
			if ( ! isset( $activity['object'] ) ) {
				continue;
			}

			// Check if the Event object meets the minimum requirements and is valid.
			$is_valid = Event_Sources::is_valid_activitypub_event_object( $activity['object'] );
			if ( ! $is_valid || \is_wp_error( $is_valid ) ) {
				continue;
			}

			// Check if the event is in the future or ongoing.
			if ( Event_Sources::is_ongoing_or_future_event( $activity['object'] ) ) {
				$parsed_events[] = $activity['object'];
			}
		}

		return $parsed_events;
	}

	/**
	 * Import events from the items of an outbox.
	 *
	 * @param array  $items The items/orderedItems as an associative array.
	 * @param string $actor The actor that owns the items.
	 * @param int    $limit The limit of how many events to save locally.
	 * @return int The number of saved events (at least attempted).
	 */
	public static function import_events_from_items( $items, $actor, $limit = -1 ) {
		$events = self::parse_items_for_events( $items, $limit );

		$transmogrifier = Setup::get_transmogrifier();

		if ( ! $transmogrifier ) {
			return;
		}

		$count = 0;

		foreach ( $events as $event ) {
			$transmogrifier->save( $event, $actor );
			++$count;
			if ( $limit > 0 && $count >= $limit ) {
				break;
			}
		}

		return count( $events );
	}

	/**
	 * Schedule the import of events from an outbox OrderedCollection or OrderedCollectionPage.
	 *
	 * @param string $url The url of the current page or outbox.
	 * @param string $actor The ActivityPub ID/URL of the actor that owns the outbox.
	 * @param int    $delay The delay of the current time in seconds.
	 * @return void
	 */
	public static function queue_importing_from_outbox( $url, $actor, $delay = 10 ) {
		$hook = 'event_bridge_for_activitypub_import_events_from_outbox';
		$args = array( $url, $actor );

		if ( \wp_next_scheduled( $hook, $args ) ) {
			return;
		}

		return \wp_schedule_single_event( \time() + $delay, $hook, $args );
	}

	/**
	 * Initialize the backfilling of events via the outbox of an ActivityPub actor.
	 *
	 * @param string $actor The ActivityPub ID of the actor which owns the outbox.
	 * @return bool|WP_Error
	 */
	public static function backfill_events( $actor ) {
		// Initiate parsing of outbox collection.
		$outbox_url = Event_Source::get_by_id( $actor )->get_outbox();

		if ( ! $outbox_url ) {
			return;
		}
		return self::queue_importing_from_outbox( $outbox_url, $actor );
	}

	/**
	 * Import events from an outbox: OrderedCollection or OrderedCollectionPage.
	 *
	 * @param string $url The url of the current page or outbox.
	 * @param string $actor The ActivityPub ID/URL of the actor that owns the outbox.
	 * @return void
	 */
	public static function import_events_from_outbox( $url, $actor ) {
		$response = Http::get( $url );

		if ( \is_wp_error( $response ) ) {
			return;
		}

		$outbox = \wp_remote_retrieve_body( $response );
		$outbox = \json_decode( $outbox, true );

		// Validate the outbox type and structure.
		if ( ! is_array( $outbox ) || ! isset( $outbox['type'] ) ) {
			return;
		}

		$current_count = (int) \get_option( "event_bridge_for_activitypub_backfill_count_{$actor}", 0 );

		if ( $current_count >= self::MAX_EVENTS_TO_IMPORT ) {
			// Stop importing as the limit is reached.
			return;
		}

		// Process orderedItems if they exist (non-paginated outbox).
		if ( isset( $outbox['orderedItems'] ) && is_array( $outbox['orderedItems'] ) ) {
			$current_count += self::import_events_from_items( $outbox['orderedItems'], $actor, self::MAX_EVENTS_TO_IMPORT - $current_count );
		}

		// Update the count.
		\update_option( "activitypub_import_event_count_{$actor}", $current_count );

		// If the count is already exceeded abort here.
		if ( $current_count >= self::MAX_EVENTS_TO_IMPORT ) {
			return;
		}

		// Determine the pagination URL based on the outbox type.
		$pagination_url = null;

		if ( 'OrderedCollection' === $outbox['type'] && ! empty( $outbox['first'] ) && is_string( $outbox['first'] ) ) {
			$pagination_url = $outbox['first'];
		} elseif ( 'OrderedCollectionPage' === $outbox['type'] && ! empty( $outbox['next'] ) && is_string( $outbox['next'] ) ) {
			$pagination_url = $outbox['next'];
		}

		// Trigger the action if a pagination URL is found.
		if ( $pagination_url ) {
			self::queue_importing_from_outbox( $pagination_url, $actor );
		}
	}
}
