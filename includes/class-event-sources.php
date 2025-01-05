<?php
/**
 * Class for handling and saving the ActivityPub event sources (i.e. follows).
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Model\Blog;
use DateTime;
use DateTimeZone;
use Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources as Event_Sources_Collection;
use Event_Bridge_For_ActivityPub\ActivityPub\Handler;
use Event_Bridge_For_ActivityPub\Admin\User_Interface;
use Event_Bridge_For_ActivityPub\Integrations\Event_Plugin_Integration;
use Event_Bridge_For_ActivityPub\Integrations\Feature_Event_Sources;
use Exception;
use WP_Error;

use function Activitypub\get_remote_metadata_by_actor;
use function Activitypub\is_activitypub_request;

/**
 * Class for handling and saving the ActivityPub event sources (i.e. follows).
 *
 * @package Event_Bridge_For_ActivityPub
 */
class Event_Sources {
	/**
	 * Init.
	 */
	public static function init() {
		// Register the Event Sources Collection which takes care of managing the event sources.
		\add_action( 'init', array( Event_Sources_Collection::class, 'init' ) );

		// Allow wp_safe_redirect to all followed event sources hosts.
		\add_filter( 'allowed_redirect_hosts', array( self::class, 'add_event_sources_hosts_to_allowed_redirect_hosts' ) );

		// Register handlers for incoming activities to the ActivityPub plugin, e.g. incoming `Event` objects.
		\add_action( 'activitypub_register_handlers', array( Handler::class, 'register_handlers' ) );

		// Add validation filter, so that only plausible activities reach the handlers above.
		\add_filter(
			'activitypub_validate_object',
			array( self::class, 'validate_event_object' ),
			12,
			3
		);
		\add_filter(
			'activitypub_validate_object',
			array( self::class, 'validate_activity' ),
			13,
			3
		);

		// Apply modifications to the UI, e.g. disable editing of remote event posts.
		\add_action( 'init', array( User_Interface::class, 'init' ) );

		// Register post meta to the event plugins post types needed for easier handling of this feature.
		\add_action( 'init', array( self::class, 'register_post_meta' ) );

		// Register filters that prevent cached remote events from being federated again.
		\add_filter( 'activitypub_is_post_disabled', array( self::class, 'is_cached_external_post' ), 10, 2 );
		\add_filter( 'template_include', array( self::class, 'redirect_activitypub_requests_for_cached_external_events' ), 100 );

		// Register daily schedule to cleanup cached remote events that have ended.
		if ( ! \wp_next_scheduled( 'event_bridge_for_activitypub_event_sources_clear_cache' ) ) {
			\wp_schedule_event( time(), 'daily', 'event_bridge_for_activitypub_event_sources_clear_cache' );
		}
		\add_action( 'event_bridge_for_activitypub_event_sources_clear_cache', array( self::class, 'clear_cache' ) );

		// Add the actors followed by the event sources feature to the `follow` collection of the used ActivityPub actor.
		\add_filter( 'activitypub_rest_following', array( self::class, 'add_event_sources_to_follow_collection' ), 10, 2 );

		// Add action for backfilling the events.
		Outbox_Parser::init();
	}


	/**
	 * Register post meta.
	 */
	public static function register_post_meta() {
		$setup = Setup::get_instance();

		foreach ( $setup->get_active_event_plugins() as $event_plugin_integration ) {
			if ( ! $event_plugin_integration instanceof Feature_Event_Sources && $event_plugin_integration instanceof Event_Plugin_Integration ) {
				continue;
			}

			\register_post_meta(
				$event_plugin_integration::get_post_type(),
				'_event_bridge_for_activitypub_event_source',
				array(
					'type'              => 'integer',
					'single'            => true,
					'sanitize_callback' => 'absint',
				)
			);

			$location_post_type = $event_plugin_integration::get_location_post_type();
			if ( $location_post_type ) {
				\register_post_meta(
					$location_post_type,
					'_event_bridge_for_activitypub_event_source',
					array(
						'type'              => 'string',
						'single'            => false,
						'sanitize_callback' => function ( $value ) {
							return sanitize_url( $value );
						},
					)
				);
			}
		}
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

	/**
	 * Filter that cached external posts are not scheduled via the ActivityPub plugin.
	 *
	 * Posts that are actually just external events are treated as cache. They are displayed in
	 * the frontend HTML view and redirected via ActivityPub request, but we do not own them.
	 *
	 * @param bool    $disabled If it is disabled already by others (the upstream ActivityPub plugin).
	 * @param WP_Post $post The WordPress post object.
	 * @return bool True if the post can be federated via ActivityPub.
	 */
	public static function is_cached_external_post( $disabled, $post = null ): bool {
		if ( $disabled || ! $post ) {
			return $disabled;
		}
		return ! self::is_cached_external_event_post( $post );
	}

	/**
	 * Determine whether a WP post is a cached external event.
	 *
	 * @param WP_Post $post The WordPress post object.
	 * @return bool
	 */
	public static function is_cached_external_event_post( $post ): bool {
		if ( get_post_meta( $post->ID, '_event_bridge_for_activitypub_event_source', true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Add the ActivityPub template for EventPrime.
	 *
	 * @param  string $template The path to the template object.
	 * @return string The new path to the JSON template.
	 */
	public static function redirect_activitypub_requests_for_cached_external_events( $template ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $template;
		}

		if ( ! is_activitypub_request() ) {
			return $template;
		}

		if ( ! \is_singular() ) {
			return $template;
		}

		global $post;

		if ( self::is_cached_external_event_post( $post ) ) {
			\wp_safe_redirect( $post->guid, 301 );
			exit;
		}

		return $template;
	}

	/**
	 * Delete old cached events that took place in the past.
	 */
	public static function clear_cache() {
		// Get the event plugin integration that is used.
		$event_plugin_integration = Setup::get_event_plugin_integration_used_for_event_sources_feature();

		if ( ! $event_plugin_integration ) {
			return;
		}

		$cache_retention_period = get_option( 'event_bridge_for_activitypub_event_source_cache_retention', WEEK_IN_SECONDS );

		$ended_before_time = gmdate( 'Y-m-d H:i:s', time() - $cache_retention_period );

		$past_event_ids = $event_plugin_integration::get_cached_remote_events( $ended_before_time );

		foreach ( $past_event_ids as $post_id ) {
			if ( has_post_thumbnail( $post_id ) ) {
				$attachment_id = get_post_thumbnail_id( $post_id );
				wp_delete_attachment( $attachment_id, true );
			}
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Add the Blog Authors to the following list of the Blog Actor
	 * if Blog not in single mode.
	 *
	 * @param array                   $follow_list The array of following urls.
	 * @param \Activitypub\Model\User $user        The user object.
	 *
	 * @return array The array of following urls.
	 */
	public static function add_event_sources_to_follow_collection( $follow_list, $user ) {
		if ( ! $user instanceof Blog ) {
			return $follow_list;
		}

		$event_sources_activitypub_ids = array_values( Event_Sources_Collection::get_event_sources() );

		if ( ! is_array( $event_sources_activitypub_ids ) ) {
			return $follow_list;
		}

		return array_merge( $follow_list, $event_sources_activitypub_ids );
	}

	/**
	 * Get an array will all unique hosts of all Event-Sources.
	 *
	 * @return array A list with all unique hosts of all Event Sources' ActivityPub IDs.
	 */
	public static function get_event_sources_hosts() {
		$hosts = get_transient( 'event_bridge_for_activitypub_event_sources_hosts' );

		if ( $hosts ) {
			return $hosts;
		}

		$event_sources = Event_Sources_Collection::get_event_sources();

		$hosts = array();
		foreach ( $event_sources as $actor ) {
			$url = wp_parse_url( $actor );
			if ( isset( $url['host'] ) ) {
				$hosts[] = $url['host'];
			}
		}

		$hosts = array_unique( $hosts );

		set_transient( 'event_bridge_for_activitypub_event_sources_hosts', $hosts );

		return $hosts;
	}

	/**
	 * Add Event Sources hosts to allowed hosts used by safe redirect.
	 *
	 * @param array $hosts The hosts before the filter.
	 * @return array
	 */
	public static function add_event_sources_hosts_to_allowed_redirect_hosts( $hosts ) {
		$event_sources_hosts = self::get_event_sources_hosts();
		return array_merge( $hosts, $event_sources_hosts );
	}

	/**
	 * Validate the event object.
	 *
	 * @param bool             $valid   The validation state.
	 * @param string           $param   The object parameter.
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return bool|WP_Error The validation state: true if valid, false if not.
	 */
	public static function validate_activity( $valid, $param, $request ) {
		if ( $valid ) {
			return $valid;
		}
		$json_params = $request->get_json_params();

		if ( isset( $json_params['object']['type'] ) && in_array( $json_params['object']['type'], array( 'Accept', 'Undo' ), true ) ) {
			return true;
		}

		return $valid;
	}

	/**
	 * Validate the event object.
	 *
	 * @param bool             $valid   The validation state.
	 * @param string           $param   The object parameter.
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return bool|WP_Error The validation state: true if valid, false if not.
	 */
	public static function validate_event_object( $valid, $param, $request ) {
		$json_params = $request->get_json_params();

		// Check if we should continue with the validation.
		if ( isset( $json_params['object']['type'] ) && 'Event' === $json_params['object']['type'] ) {
			$valid = true;
		} else {
			return $valid;
		}

		if ( empty( $json_params['type'] ) ) {
			return false;
		}

		if ( empty( $json_params['actor'] ) ) {
			return false;
		}

		if ( ! in_array( $json_params['type'], array( 'Create', 'Update', 'Delete', 'Announce' ), true ) || is_wp_error( $request ) ) {
			return $valid;
		}

		return self::is_valid_activitypub_event_object( $json_params['object'] );
	}

	/**
	 * Check if the object is a valid ActivityPub event.
	 *
	 * @param array $event_object The (event) object as an associative array.
	 * @return bool|WP_Error True if the object is an valid ActivityPub Event, false or WP_Error if not.
	 */
	public static function is_valid_activitypub_event_object( $event_object ) {
		if ( ! is_array( $event_object ) ) {
			return false;
		}

		$required = array(
			'id',
			'startTime',
			'name',
		);

		if ( array_intersect( $required, array_keys( $event_object ) ) !== $required ) {
			return new WP_Error(
				'event_bridge_for_activitypub_invalid_event_object',
				__( 'The Event object is missing a required attribute.', 'event-bridge-for-activitypub' )
			);
		}

		if ( ! self::is_valid_activitypub_time_string( $event_object['startTime'] ) ) {
			return new WP_Error(
				'event_bridge_for_activitypub_event_object_is_not_in_the_future',
				__( 'Ignoring event that has already started.', 'event-bridge-for-activitypub' )
			);
		}

		return true;
	}

	/**
	 * Validate a time string if it is according to the ActivityPub specification.
	 *
	 * @param string $time_string The time string.
	 * @return bool
	 */
	public static function is_valid_activitypub_time_string( $time_string ) {
		// Try to create a DateTime object from the input string.
		try {
			$date = new DateTime( $time_string );
		} catch ( Exception $e ) {
			// If parsing fails, it's not valid.
			return false;
		}

		// Ensure the timezone is correctly formatted (e.g., 'Z' or a valid offset).
		$timezone           = $date->getTimezone();
		$formatted_timezone = $timezone->getName();

		// Return true only if the time string includes 'Z' or a valid timezone offset.
		$valid = 'Z' === $formatted_timezone || preg_match( '/^[+-]\d{2}:\d{2}$/ ', $formatted_timezone );
		return $valid;
	}

	/**
	 * Check if a given DateTime is already passed.
	 *
	 * @param string|DateTime $time The ActivityPub like time string or DateTime object.
	 * @return bool
	 */
	public static function is_time_passed( $time ) {
		if ( ! $time instanceof DateTime ) {
			// Create a DateTime object from the ActivityPub time string.
			$time = new DateTime( $time, new DateTimeZone( 'UTC' ) );
		}

		// Get the current time in UTC.
		$current_time = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

		// Compare the event time with the current time.
		return $time < $current_time;
	}

	/**
	 * Determine whether an Event is an ongoing or future event.
	 *
	 * @param array $event_object The ActivityPub Event as an associative array.
	 * @return bool
	 */
	public static function is_ongoing_or_future_event( $event_object ) {
		if ( isset( $event_object['endTime'] ) ) {
			$time = $event_object['endTime'];
		} else {
			$time = new DateTime( $event_object['startTime'], new DateTimeZone( 'UTC' ) ) + 3 * HOUR_IN_SECONDS;
		}
		return ! self::is_time_passed( $time );
	}

	/**
	 * Check that an ActivityPub actor is an event source (i.e. it is followed by the ActivityPub blog actor).
	 *
	 * @param string $actor_id The actor ID.
	 * @return bool True if the ActivityPub actor ID is followed, false otherwise.
	 */
	public static function actor_is_event_source( $actor_id ) {
		$event_sources = Event_Sources_Collection::get_event_sources();
		if ( in_array( $actor_id, $event_sources, true ) ) {
			return true;
		}
		return false;
	}
}
