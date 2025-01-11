<?php
/**
 * Class file for parsing an ActivityPub outbox for Events.
 *
 * The main external entry function is `backfill_events`.
 * The function `import_events_from_outbox` is used for delaying the parsing via schedules.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Http;
use Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source;

use function Activitypub\object_to_uri;

/**
 * Class for parsing an ActivityPub outbox for Events.
 *
 * The main external entry function is `backfill_events`.
 * The function `import_events_from_outbox` is used for delaying the parsing via schedules.
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
	 * Initialize the backfilling of events via the outbox of an ActivityPub actor.
	 *
	 * @param int $event_source_post_id The Post ID of Event Source we want to backfill the events for.
	 * @return bool|WP_Error
	 */
	public static function backfill_events( $event_source_post_id ) {
		$event_source = Event_Source::get_by_id( $event_source_post_id );

		if ( ! $event_source ) {
			return;
		}

		$outbox_url = $event_source->get_outbox();

		if ( ! $outbox_url ) {
			return;
		}

		// Schedule the import of events via the outbox.
		return self::queue_importing_from_outbox( $outbox_url, $event_source->get__id(), 0 );
	}

	/**
	 * Import events from an outbox: OrderedCollection or OrderedCollectionPage.
	 *
	 * @param string $url                  The url of the current page or outbox.
	 * @param int    $event_source_post_id The Post ID of the Event Source that owns the outbox.
	 * @return void
	 */
	public static function import_events_from_outbox( $url, $event_source_post_id ) {
		$setup = Setup::get_instance();
		if ( ! $setup->is_activitypub_plugin_active() ) {
			return;
		}

		$outbox = self::fetch_outbox( $url );

		if ( ! $outbox ) {
			return;
		}

		$current_count = self::get_import_count( $event_source_post_id );

		if ( $current_count >= self::MAX_EVENTS_TO_IMPORT ) {
			return;
		}

		// Process orderedItems if they exist (non-paginated outbox).
		if ( isset( $outbox['orderedItems'] ) && is_array( $outbox['orderedItems'] ) ) {
			$current_count += self::import_events_from_items(
				$outbox['orderedItems'],
				$event_source_post_id,
				self::MAX_EVENTS_TO_IMPORT - $current_count
			);
		}

		self::update_import_count( $event_source_post_id, $current_count );

		// If the count is already exceeded abort here.
		if ( $current_count >= self::MAX_EVENTS_TO_IMPORT ) {
			return;
		}

		// Get next page and if it exists schedule the import of next page.
		$pagination_url = self::get_pagination_url( $outbox );

		if ( $pagination_url ) {
			self::queue_importing_from_outbox( $pagination_url, $event_source_post_id );
		}
	}

	/**
	 * Check if an Activity is of type Update or Create.
	 *
	 * @param array $activity The Activity as associative array.
	 * @return bool
	 */
	private static function is_create_or_update_activity( $activity ) {
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
	private static function parse_outbox_items_for_events( $items, $max_items ) {
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
	 * @param array $items                The items/orderedItems as an associative array.
	 * @param int   $event_source_post_id The Post ID of the Event Source that owns the outbox.
	 * @param int   $limit                The limit of how many events to save locally.
	 * @return int The number of saved events (at least attempted).
	 */
	private static function import_events_from_items( $items, $event_source_post_id, $limit = -1 ) {
		$events = self::parse_outbox_items_for_events( $items, $limit );

		$transmogrifier = Setup::get_transmogrifier();

		if ( ! $transmogrifier ) {
			return;
		}

		$imported_count = 0;

		foreach ( $events as $event ) {
			$transmogrifier->save( $event, $event_source_post_id );
			++$imported_count;
			if ( $limit > 0 && $imported_count >= $limit ) {
				break;
			}
		}

		return $imported_count;
	}

	/**
	 * Schedule the import of events from an outbox OrderedCollection or OrderedCollectionPage.
	 *
	 * @param string $url                  The url of the current page or outbox.
	 * @param int    $event_source_post_id The Post ID of the Event Source that owns the outbox.
	 * @param int    $delay                The delay of the current time in seconds.
	 * @return void
	 */
	private static function queue_importing_from_outbox( $url, $event_source_post_id, $delay = 10 ) {
		$hook = 'event_bridge_for_activitypub_import_events_from_outbox';
		$args = array( $url, $event_source_post_id );

		if ( \wp_next_scheduled( $hook, $args ) ) {
			return;
		}

		return \wp_schedule_single_event( \time() + $delay, $hook, $args );
	}

	/**
	 * Get the current import count for the actor.
	 *
	 * @param int $event_source_post_id The Post ID of the Event Source that owns the outbox.
	 * @return int The current count of imported events.
	 */
	private static function get_import_count( $event_source_post_id ) {
		return (int) \get_post_meta( $event_source_post_id, '_event_bridge_for_activitypub_event_count', true );
	}

	/**
	 * Update the import count for an event source..
	 *
	 * @param int $event_source_post_id The Post ID of the Event Source that owns the outbox.
	 * @param int $count                The new count of imported events.
	 * @return void
	 */
	private static function update_import_count( $event_source_post_id, $count ) {
		\update_post_meta( $event_source_post_id, '_event_bridge_for_activitypub_event_count', $count );
	}

	/**
	 * Fetch the outbox from the given URL.
	 *
	 * @param string $url The URL of the outbox.
	 * @return array|null The decoded outbox data, or null if fetching fails.
	 */
	private static function fetch_outbox( $url ) {
		$response = Http::get( $url );

		if ( \is_wp_error( $response ) ) {
			return null;
		}

		$outbox = \wp_remote_retrieve_body( $response );
		$outbox = \json_decode( $outbox, true );

		return ( is_array( $outbox ) && isset( $outbox['type'] ) && isset( $outbox['id'] ) ) ? $outbox : null;
	}

	/**
	 * Get the pagination URL from the outbox.
	 *
	 * @param array $outbox The outbox data.
	 * @return string|null The pagination URL, or null if not found.
	 */
	private static function get_pagination_url( $outbox ) {
		// If we are on a collection page simply use the next key.
		if ( 'OrderedCollectionPage' === $outbox['type'] && ! empty( $outbox['next'] ) && is_string( $outbox['next'] ) ) {
			return $outbox['next'];
		}

		// If we still have the ordered collection itself.
		if ( isset( $outbox['type'] ) && 'OrderedCollection' === $outbox['type'] && isset( $outbox['first'] ) ) {
			return object_to_uri( $outbox['first'] );
		}

		return null;
	}
}
