<?php
/**
 * EventPrime – Events Calendar, Bookings and Tickets
 *
 * @link    https://wordpress.org/plugins/eventprime-event-calendar-management/
 * @package ActivityPub_Event_Bridge
 * @since   1.0.0
 */

namespace ActivityPub_Event_Bridge\Plugins;

use Activitypub\Signature;
use Eventprime_Basic_Functions;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * This class defines which information is necessary for the EventPrime event plugin.
 *
 * @since 1.0.0
 */
final class EventPrime extends Event_Plugin {
	/**
	 * Add filter for the template inclusion.
	 */
	public function __construct() {
		\add_filter( 'template_include', array( self::class, 'render_activitypub_template' ), 100 );
	}

	/**
	 * Returns the full plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file(): string {
		return 'eventprime-event-calendar-management/event-prime.php';
	}

	/**
	 * Returns the event post type of the plugin.
	 *
	 * @return string
	 */
	public static function get_post_type(): string {
		return 'em_event';
	}

	/**
	 * Returns the IDs of the admin pages of the plugin.
	 *
	 * @return array The settings page urls.
	 */
	public static function get_settings_pages(): array {
		return array( 'ep-settings' );
	}

	/**
	 * Returns the ActivityPub transformer class.
	 *
	 * @return string
	 */
	public static function get_activitypub_transformer_class_name(): string {
		return 'EventPrime';
	}

	/**
	 * Returns the taxonomy used for the plugin's event categories.
	 *
	 * @return string
	 */
	public static function get_event_category_taxonomy(): string {
		return 'em_event_type';
	}

	/**
	 * Determine whether the current request is an EventPrime ActivityPub request.
	 */
	private static function is_eventprime_activitypub_request() {
		global $wp_query;

		/*
		 * ActivityPub requests are currently only made for
		 * author archives, singular posts, and the homepage.
		 */
		if ( ! \is_author() && ! \is_singular() && ! \is_home() && ! defined( '\REST_REQUEST' ) ) {
			return false;
		}

		// Check if the current post type supports ActivityPub.
		if ( \is_singular() ) {
			$queried_object = \get_queried_object();

			if ( ! $queried_object instanceof \WP_Post ) {
				return false;
			}

			if ( '[em_event]' !== $queried_object->post_content && '[em_events]' !== $queried_object->post_content ) {
				return false;
			}
		}

		// Check if header already sent.
		if ( ! \headers_sent() && ACTIVITYPUB_SEND_VARY_HEADER ) {
			// Send Vary header for Accept header.
			\header( 'Vary: Accept' );
		}

		// One can trigger an ActivityPub request by adding ?activitypub to the URL.
		if ( isset( $wp_query->query_vars['activitypub'] ) ) {
			return true;
		}

		/*
		 * The other (more common) option to make an ActivityPub request
		 * is to send an Accept header.
		 */
		if ( isset( $_SERVER['HTTP_ACCEPT'] ) ) {
			$accept = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) );

			/*
			 * $accept can be a single value, or a comma separated list of values.
			 * We want to support both scenarios,
			 * and return true when the header includes at least one of the following:
			 * - application/activity+json
			 * - application/ld+json
			 * - application/json
			 */
			if ( preg_match( '/(application\/(ld\+json|activity\+json|json))/i', $accept ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extract the post id of the event for an EventPrime event query.
	 *
	 * @return bool|int The post ID if an event could be identified, false otherwise.
	 */
	private static function get_eventprime_post_id() {
		$event = get_query_var( 'event' );
		if ( ! $event ) {
			if ( ! empty( filter_input( INPUT_GET, 'event', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) ) {
				$event = rtrim( filter_input( INPUT_GET, 'event', FILTER_SANITIZE_FULL_SPECIAL_CHARS ), '/\\' );
			}
		}

		if ( $event ) {
			$ep_basic_functions = new Eventprime_Basic_Functions();
			return $ep_basic_functions->ep_get_id_by_slug( $event, 'em_event' );
		}

		return false;
	}

	/**
	 * Add the ActivityPub template for EventPrime.
	 *
	 * @param  string $template The path to the template object.
	 * @return string The new path to the JSON template.
	 */
	public static function render_activitypub_template( $template ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $template;
		}

		// Check if the request is a page with (solely) the eventprime shortcode in it.
		if ( ! self::is_eventprime_activitypub_request() ) {
			return $template;
		}

		if ( ! \is_singular() ) {
			return $template;
		}

		$post_id = self::get_eventprime_post_id();

		if ( $post_id ) {
			$preview = \get_query_var( 'preview' );
			if ( $preview ) {
				$activitypub_template = ACTIVITYPUB_PLUGIN_DIR . '/templates/post-preview.php';
			} else {
				$activitypub_template = ACTIVITYPUB_PLUGIN_DIR . '/templates/post-json.php';
			}
		}

		/*
		 * Check if the request is authorized.
		 *
		 * @see https://www.w3.org/wiki/SocialCG/ActivityPub/Primer/Authentication_Authorization#Authorized_fetch
		 * @see https://swicg.github.io/activitypub-http-signature/#authorized-fetch
		 */
		if ( $activitypub_template && ACTIVITYPUB_AUTHORIZED_FETCH ) {
			$verification = Signature::verify_http_signature( $_SERVER );
			if ( \is_wp_error( $verification ) ) {
				header( 'HTTP/1.1 401 Unauthorized' );

				// Fallback as template_loader can't return http headers.
				return $template;
			}
		}

		if ( $activitypub_template ) {
			global $post;

			$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			// Ensure WordPress functions use the new post data.
			setup_postdata( $post );
			// Return the default ActivityPub template.
			return $activitypub_template;
		}

		return $template;
	}
}
