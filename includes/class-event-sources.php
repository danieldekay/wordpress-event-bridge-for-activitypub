<?php
/**
 * Class for handling and saving the ActivityPub event sources (i.e. follows).
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub;

use Activitypub\Activity\Extended_Object\Event;
use Activitypub\Collection\Actors;
use Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources as Event_Sources_Collection;
use Event_Bridge_For_ActivityPub\Activitypub\Transformer\GatherPress as TransformerGatherPress;
use Event_Bridge_For_ActivityPub\Activitypub\Transmogrifier\GatherPress;
use Event_Bridge_For_ActivityPub\Integrations\GatherPress as IntegrationsGatherPress;

use function Activitypub\get_remote_metadata_by_actor;
use function Activitypub\is_activitypub_request;

/**
 * Class for handling and saving the ActivityPub event sources (i.e. follows).
 *
 * @package Event_Bridge_For_ActivityPub
 */
class Event_Sources {
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
		if ( ! str_starts_with( \get_site_url(), $post->guid ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Determine whether a WP post is a cached external event.
	 *
	 * @param WP_Post $post The WordPress post object.
	 * @return bool
	 */
	public static function is_cached_external_event_post( $post ): bool {
		if ( 'gatherpress_event' !== $post->post_type ) {
			return false;
		}

		if ( ! str_starts_with( \get_site_url(), $post->guid ) ) {
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
		$cache_retention_period = get_option( 'event_bridge_for_activitypub_event_source_cache_retention', WEEK_IN_SECONDS );

		$past_event_ids = GatherPress::get_past_events( $cache_retention_period );

		foreach ( $past_event_ids as $post_id ) {
			if ( has_post_thumbnail( $post_id ) ) {
				$attachment_id = get_post_thumbnail_id( $post_id );
				wp_delete_attachment( $attachment_id, true );
			}
			wp_delete_post( $post_id, true );
		}
	}
}
