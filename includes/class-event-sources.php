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
use Event_Bridge_For_ActivityPub\Integrations\Feature_Event_Sources;
use WP_Error;
use WP_Post;
use WP_REST_Request;

use function Activitypub\is_activitypub_request;
use function Activitypub\sanitize_url;

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
		\add_filter( 'activitypub_is_post_disabled', array( self::class, 'is_post_disabled_for_activitypub' ), 99, 2 );
		\add_filter( 'template_include', array( self::class, 'redirect_activitypub_requests_for_cached_external_events' ), 100 );

		// Register daily schedule to cleanup cached remote events that have ended.
		\add_action( 'event_bridge_for_activitypub_event_sources_clear_cache', array( self::class, 'clear_cache' ) );
		if ( ! \wp_next_scheduled( 'event_bridge_for_activitypub_event_sources_clear_cache' ) ) {
			\wp_schedule_event( time(), 'daily', 'event_bridge_for_activitypub_event_sources_clear_cache' );
		}

		// Add the actors followed by the event sources feature to the `follow` collection of the used ActivityPub actor.
		\add_filter( 'activitypub_rest_following', array( self::class, 'add_event_sources_to_follow_collection' ), 10, 2 );

		// Add action for backfilling the events.
		Outbox_Parser::init();
	}


	/**
	 * Register post meta.
	 *
	 * @return void
	 */
	public static function register_post_meta() {
		$setup = Setup::get_instance();

		foreach ( $setup->get_active_event_plugins() as $event_plugin_integration ) {
			if ( ! is_a( $event_plugin_integration, Feature_Event_Sources::class ) ) {
				continue;
			}

			$post_type = $event_plugin_integration::get_post_type();
			self::register_post_meta_event_bridge_for_activitypub_event_source( $post_type );

			$post_type = $event_plugin_integration::get_place_post_type();
			if ( $post_type ) {
				self::register_post_meta_event_bridge_for_activitypub_event_source( $post_type );
			}

			$post_type = $event_plugin_integration::get_organizer_post_type();
			if ( $post_type ) {
				self::register_post_meta_event_bridge_for_activitypub_event_source( $post_type );
			}
		}
	}

	/**
	 * Register post meta _event_bridge_for_activitypub_event_source for a given post type.
	 *
	 * @param string $post_type The post type to register the meta for.
	 * @return void
	 */
	private static function register_post_meta_event_bridge_for_activitypub_event_source( $post_type ) {
		\register_post_meta(
			$post_type,
			'_event_bridge_for_activitypub_event_source',
			array(
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => 'absint',
			)
		);
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
	 * @return bool False if the post is not disabled for federation via ActivityPub.
	 */
	public static function is_post_disabled_for_activitypub( $disabled, $post = null ): bool {
		if ( $disabled ) {
			return $disabled;
		}
		return self::is_cached_external_post( $post );
	}

	/**
	 * Determine whether a WP post is a cached external event.
	 *
	 * @param WP_Post|int $post The WordPress post object or post ID.
	 * @return bool
	 */
	public static function is_cached_external_post( $post ): bool {
		$post_id = $post instanceof WP_Post ? $post->ID : $post;

		if ( \get_post_meta( $post_id, '_event_bridge_for_activitypub_event_source', true ) ) {
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

		$post = \get_post( \get_queried_object_id() );

		if ( self::is_cached_external_post( $post ) ) {
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
	 * @param array $follow_list The array of following urls.
	 * @param mixed $user        The user object, a subtype of \Activitypub\Model\User.
	 *
	 * @return array The array of following urls.
	 */
	public static function add_event_sources_to_follow_collection( $follow_list, $user ): array {
		if ( ! $user instanceof Blog ) {
			return $follow_list;
		}

		$event_sources_activitypub_ids = array_values( Event_Sources_Collection::get_event_sources() );

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
	 * Mark incoming accept activities as valid.
	 *
	 * @param bool            $valid   The validation state.
	 * @param string          $param   The object parameter.
	 * @param WP_REST_Request $request The request object.
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
	 * @param bool            $valid   The validation state.
	 * @param string          $param   The object parameter.
	 * @param WP_REST_Request $request The request object.
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

		if ( ! in_array( $json_params['type'], array( 'Create', 'Update', 'Delete', 'Announce' ), true ) ) {
			return $valid;
		}

		if ( ! self::is_valid_activitypub_event_object( $json_params['object'] ) ) {
			return false;
		}

		if ( ! self::same_host( $json_params['actor'], $json_params['id'], $json_params['object']['id'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if all provided URLs belong to the same origin (host).
	 *
	 * @param string ...$urls List of URLs to compare.
	 * @return bool True if all URLs have the same host, false otherwise.
	 */
	public static function same_host( ...$urls ) {
		if ( empty( $urls ) ) {
			return false; // No URLs given, can't compare hosts.
		}

		$first = \wp_parse_url( array_shift( $urls ) );
		if ( ! isset( $first['host'] ) ) {
			return false;
		}

		$first_host = $first['host'];

		foreach ( $urls as $url ) {
			$result = \wp_parse_url( $url );
			if ( ! isset( $result['host'] ) ) {
				return false;
			}

			if ( $result['host'] !== $first_host ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Check if the object is a valid ActivityPub event.
	 *
	 * @param mixed $event_object The (event) object as an associative array.
	 * @return bool True if the object is an valid ActivityPub Event, false if not.
	 */
	public static function is_valid_activitypub_event_object( $event_object ): bool {
		if ( ! is_array( $event_object ) ) {
			return false;
		}

		$required = array(
			'id',
			'startTime',
			'name',
		);

		if ( array_intersect( $required, array_keys( $event_object ) ) !== $required ) {
			return false;
		}

		if ( ! self::is_valid_activitypub_time_string( $event_object['startTime'] ) ) {
			return false;
		}

		if ( ! self::is_valid_activitypub_id( $event_object['id'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate an ActivityPub ID.
	 *
	 * @link https://www.w3.org/TR/activitypub/#obj-id
	 *
	 * @param string $id The ID to validate.
	 * @return bool
	 */
	public static function is_valid_activitypub_id( $id ) {
		return sanitize_url( $id ) ? true : false;
	}

	/**
	 * Validate a time string if it is according to the ActivityPub specification.
	 *
	 * @link https://www.w3.org/TR/activitystreams-core/#dates
	 *
	 * @param string $time_string The xsd:datetime string.
	 * @return bool
	 */
	public static function is_valid_activitypub_time_string( string $time_string ): bool {
		// Regular expression based on AS2 rules.
		return 1 === preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?(\.\d+)?(Z|[+-]\d{2}:\d{2})$/', $time_string );
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
			$time = new DateTime( $event_object['startTime'], new DateTimeZone( 'UTC' ) );
			$time->modify( '+3 hours' );
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
