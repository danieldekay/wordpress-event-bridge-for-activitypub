<?php
/**
 * Event Sources.
 *
 * @package Activitypub_Event_Bridge
 */

namespace ActivityPub_Event_Bridge\Admin;

use Activitypub\Collection\Actors;
use Activitypub\Activity\Extended_Object\Event;

use function Activitypub\get_remote_metadata_by_actor;

/**
 * Manage following other Event Sources (ActivityPub actors) and importing their Events.
 */
class Event_Sources {
	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'init', array( $this, 'register_post_meta' ) );

		\add_action( 'activitypub_inbox', array( $this, 'handle_activitypub_inbox' ), 15, 3 );
	}

	/**
	 * Handle the ActivityPub Inbox.
	 *
	 * @param array  $data     The raw post data JSON object as an associative array.
	 * @param int    $user_id  The target user id.
	 * @param string $type     The activity type.
	 */
	public static function handle_activitypub_inbox( $data, $user_id, $type ) {
		if ( Actors::APPLICATION_USER_ID !== $user_id ) {
			return;
		}

		if ( ! in_array( $type, array( 'Create', 'Delete', 'Announce', 'Undo Announce' ), true ) ) {
			return;
		}

		$event = Event::init_from_array( $data );
		return $event;
	}

	/**
	 * Get metadata.
	 *
	 * @param string $url The URL.
	 */
	public static function get_metadata( $url ) {
		if ( ! is_string( $url ) ) {
			return array();
		}

		if ( false !== strpos( $url, '@' ) ) {
			if ( false === strpos( $url, '/' ) && preg_match( '#^https?://#', $url, $m ) ) {
				$url = substr( $url, strlen( $m[0] ) );
			}
		}
		return get_remote_metadata_by_actor( $url );
	}

	/**
	 * Respond to the Ajax request to fetch feeds
	 */
	public function ajax_fetch_events() {
		if ( ! isset( $_POST['activitypub_event_bridge'] ) ) {
			wp_send_json_error( 'missing-parameters' );
		}

		check_ajax_referer( 'fetch-events-' . sanitize_user( wp_unslash( $_POST['actor'] ) ) );

		$actor = Actors::get_by_resource( sanitize_user( wp_unslash( $_POST['actor'] ) ) );
		if ( ! $actor ) {
			wp_send_json_error( 'unknown-actor' );
		}

		$actor->retrieve();

		wp_send_json_success();
	}
}
