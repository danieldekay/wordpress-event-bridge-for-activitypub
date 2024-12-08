<?php
/**
 * Class for handling and saving the ActivityPub event sources (i.e. follows).
 *
 * @package Event_Bridge_For_ActivityPub
 */

namespace Event_Bridge_For_ActivityPub;

use Activitypub\Activity\Extended_Object\Event;
use Activitypub\Collection\Actors;
use Activitypub\Http;
use Exception;

use function Activitypub\get_remote_metadata_by_actor;
use function register_post_type;

/**
 * Class for handling and saving the ActivityPub event sources (i.e. follows).
 *
 * @package Event_Bridge_For_ActivityPub
 */
class Event_Sources {
	/**
	 * The custom post type.
	 */
	const POST_TYPE = 'event_bridge_follow';

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'init', array( $this, 'register_post_meta' ) );

		\add_action( 'activitypub_inbox', array( $this, 'handle_activitypub_inbox' ), 15, 3 );
	}

	/**
	 * Register the post type used to store the external event sources (i.e., followed ActivityPub actors).
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'           => array(
					'name'          => _x( 'Event Sources', 'post_type plural name', 'activitypub' ),
					'singular_name' => _x( 'Event Source', 'post_type single name', 'activitypub' ),
				),
				'public'           => false,
				'hierarchical'     => false,
				'rewrite'          => false,
				'query_var'        => false,
				'delete_with_user' => false,
				'can_export'       => true,
				'supports'         => array(),
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'activitypub_inbox',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_url',
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'activitypub_errors',
			array(
				'type'              => 'string',
				'single'            => false,
				'sanitize_callback' => function ( $value ) {
					if ( ! is_string( $value ) ) {
						throw new Exception( 'Error message is no valid string' );
					}

					return esc_sql( $value );
				},
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'activitypub_user_id',
			array(
				'type'              => 'string',
				'single'            => false,
				'sanitize_callback' => function ( $value ) {
					return esc_sql( $value );
				},
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'activitypub_actor_json',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => function ( $value ) {
					return sanitize_text_field( $value );
				},
			)
		);
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
	 * Get metadata of ActivityPub Actor by ID/URL.
	 *
	 * @param string $url The URL or ID of the ActivityPub actor.
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
		if ( ! isset( $_POST['Event_Bridge_For_ActivityPub'] ) ) {
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

	/**
	 * Get the Application actor via FEP-2677.
	 *
	 * @param string $domain  The domain without scheme.
	 * @return bool|string    The URL/ID of the application actor, false if not found.
	 */
	public static function get_application_actor( $domain ) {
		$result = wp_remote_get( 'https://' . $domain . '/.well-known/nodeinfo' );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $result );

		$nodeinfo = json_decode( $body, true );

		// Check if 'links' exists and is an array.
		if ( isset( $nodeinfo['links'] ) && is_array( $nodeinfo['links'] ) ) {
			foreach ( $nodeinfo['links'] as $link ) {
				// Check if this link matches the application actor rel.
				if ( isset( $link['rel'] ) && 'https://www.w3.org/ns/activitystreams#Application' === $link['rel'] ) {
					if ( is_string( $link['href'] ) ) {
						return $link['href'];
					}
					break;
				}
			}
		}

		// Return false if no application actor is found.
		return false;
	}
}
