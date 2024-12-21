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

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier;

use DateTime;

use function Activitypub\sanitize_url;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

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
	 * Add venue.
	 *
	 * @param int $post_id The post ID.
	 */
	private function add_venue( $post_id ) {
		$location = $this->activitypub_event->get_location();

		if ( ! $location ) {
			return;
		}

		if ( ! isset( $location['name'] ) ) {
			return;
		}

		// Fallback for Gancio instances.
		if ( 'online' === $location['name'] ) {
			return;
		}
	}

	/**
	 * Save the ActivityPub event object as GatherPress Event.
	 *
	 * @return false|int
	 */
	public function save_event() {
		// Limit this as a safety measure.
		add_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		$post_id = $this->get_post_id_from_activitypub_id();

		$duration = $this->get_duration();

		$args = array(
			'title'      => sanitize_text_field( $this->activitypub_event->get_name() ),
			'content'    => wp_kses_post( $this->activitypub_event->get_content() ),
			'start_date' => gmdate( 'Y-m-d H:i:s', strtotime( $this->activitypub_event->get_start_time() ) ),
			'duration'   => $duration,
			'status'     => 'publish',
			'guid'       => sanitize_url( $this->activitypub_event->get_id() ),
		);

		$tribe_event = new The_Events_Calendar_Event_Repository();

		if ( $post_id ) {
			$args['post_title']   = $args['title'];
			$args['post_content'] = $args['content'];
			// Update existing GatherPress event post.
			$post = \Tribe__Events__API::updateEvent( $post_id, $args );
		} else {
			$post = $tribe_event->set_args( $args )->create();
		}

		if ( ! $post ) {
			return false;
		}

		$this->add_venue( $post->ID );

		// Limit this as a safety measure.
		remove_filter( 'wp_revisions_to_keep', array( self::class, 'revisions_to_keep' ) );

		return $post->ID;
	}

	/**
	 * Get the events duration in seconds.
	 *
	 * @return int
	 */
	private function get_duration() {
		$end_time = $this->activitypub_event->get_end_time();
		if ( ! $end_time ) {
			return 2 * HOUR_IN_SECONDS;
		}
		return abs( strtotime( $end_time ) - strtotime( $this->activitypub_event->get_start_time() ) );
	}
}
