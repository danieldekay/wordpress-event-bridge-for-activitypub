<?php
/**
 * Replace the default ActivityPub Transformer
 *
 * @package Activitypub_Event_Extensions
 * @license AGPL-3.0-or-later
 */

namespace Activitypub_Event_Extensions\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Event as Event_Object;
use Activitypub\Activity\Extended_Object\Place;
use Activitypub\Transformer\Post;
use DateTime;

/**
 * Base transformer for WordPress event post types to ActivityPub events.
 *
 * Everything that transforming several WordPress post types that represent events
 * have in common, as well as sane defaults for events should be defined here.
 */
abstract class Event extends Post {

	/**
	 * The WordPress event taxonomy.
	 *
	 * @var string
	 */
	protected $wp_taxonomy;

	/**
	 * Returns the User-URL of the Author of the Post.
	 *
	 * If `single_user` mode is enabled, the URL of the Blog-User is returned.
	 *
	 * @return string The User-URL.
	 */
	protected function get_actor(): ?string {
		return $this->get_attributed_to();
	}

	/**
	 * Returns the ActivityStreams 2.0 Object-Type for an Event.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-event
	 *
	 * @return string The Event Object-Type.
	 */
	protected function get_type(): string {
		return 'Event';
	}

	/**
	 * Get a sane default for whether comments are enabled.
	 */
	protected function get_comments_enabled(): ?bool {
		return comments_open( $this->wp_object );
	}

	/**
	 * Returns the title of the event.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-name
	 *
	 * @return string The name.
	 */
	protected function get_name(): string {
		return $this->wp_object->post_title;
	}

	/**
	 * Extend the construction of the Post Transformer to also set the according taxonomy of the event post type.
	 *
	 * @param WP_Post $wp_object The WordPress post object (event).
	 * @param string  $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object );
		$this->wp_taxonomy = $wp_taxonomy;
	}

	/**
	 * Extract the join mode.
	 *
	 * Currently we don't handle joins, we always mark events as external.
	 *
	 * @return string
	 */
	public function get_join_mode(): ?string {
		return 'external';
	}

	/**
	 * Extract the external participation url.
	 *
	 * Currently we don't handle joins, we always mark events as external.
	 * We just link back to the events HTML representation on our WordPress site.
	 *
	 * @return ?string The external participation URL.
	 */
	public function get_external_participation_url(): ?string {
		return 'external' === $this->get_join_mode() ? $this->get_url() : null;
	}

	/**
	 * Set the event category, via the mapping setting.
	 */
	public function get_category(): ?string {
		$current_category_mapping = \get_option( 'activitypub_event_extensions_event_category_mappings', array() );
		$terms                    = \get_the_terms( $this->wp_object, $this->wp_taxonomy );

		// Check if the event has a category set and if that category has a specific mapping return that one.
		if ( ! is_wp_error( $terms ) && $terms && array_key_exists( $terms[0]->slug, $current_category_mapping ) ) {
			return sanitize_text_field( $current_category_mapping[ $terms[0]->slug ] );
		} else {
			// Return the default event category.
			return sanitize_text_field( \get_option( 'activitypub_event_extensions_default_event_category', 'MEETING' ) );
		}
	}

	/**
	 * Retrieves the excerpt text (may be HTML). Used for constructing the summary.
	 *
	 * @return ?string
	 */
	protected function extract_excerpt(): ?string {
		if ( $this->wp_object->excerpt ) {
			return $this->wp_object->post_excerpt;
		} else {
			return null;
		}
	}

	/**
	 * Get the start time.
	 *
	 * This is mandatory and must be implemented in the final event transformer class.
	 */
	abstract protected function get_start_time(): string;

	/**
	 * Get the end time.
	 *
	 * This is not mandatory and therefore just return null by default.
	 */
	protected function get_end_time(): ?string {
		return null;
	}

	/**
	 * Get a default for the location.
	 *
	 * This should be overridden in the actual event transformer.
	 */
	protected function get_location(): ?Place {
		return null;
	}

	/**
	 * Compose a human readable formatted start time.
	 */
	protected function format_start_time(): string {
		return $this->format_time( $this->get_start_time() );
	}

	/**
	 * Compose a human readable formatted end time.
	 */
	protected function format_end_time(): string {
		return $this->format_time( $this->get_end_time() );
	}

	static private function format_time( $time ) {
		if ( is_null( $time ) ) {
			return '';
		}
		$start_datetime  = new DateTime( $time );
		$start_timestamp = $start_datetime->getTimestamp();
		$datetime_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		return wp_date( $datetime_format, $start_timestamp );
	}

	/**
	 * Format a human readable address.
	 */
	protected function format_address(): string {
		$location = $this->get_location();
		if ( is_null( $location ) ) {
			return '';
		}
		$address = $location->get_address();
		if ( ! $address ) {
			return $location->get_name();
		}
		if ( is_string( $address ) ) {
			return $address;
		}
		if ( ! is_array( $address ) ) {
			return '';
		}
		return isset( $address['locality'] ) ? $address['locality'] : '';
	}

	/**
	 * Format the category using the translation.
	 */
	protected function format_category(): string {
		require_once ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_DIR . '/includes/event-categories.php';
		$category = $this->get_category();
		if ( array_key_exists( $category, ACTIVITYPUB_EVENT_EXTENSIONS_EVENT_CATEGORIES ) ) {
			return ACTIVITYPUB_EVENT_EXTENSIONS_EVENT_CATEGORIES[ $category ];
		} else {
			return ACTIVITYPUB_EVENT_EXTENSIONS_EVENT_CATEGORIES['MEETING'];
		}
	}

	/**
	 * Create a custom summary.
	 *
	 * It contains also the most important meta-information. The summary is often used when the
	 * ActivityPub object type 'Event' is not supported, e.g. in Mastodon.
	 *
	 * @return string $summary The custom event summary.
	 */
	public function get_summary(): ?string {
		// this will result in race conditions and is imho a bad idea
		// - either use the (userdefined) template of the activitypub plugin as it is
		// - or implement our own templating (based on the activitypub plugin templates / by reusing their code heavily)
		add_filter( 'activitypub_object_content_template', array( self::class, 'remove_ap_permalink_from_template' ), 2 );
		$excerpt = $this->extract_excerpt();
		// BeforeFirstRelease: decide whether this should be a admin setting.
		$fallback_to_content = true;
		if ( is_null( $excerpt ) && $fallback_to_content ) {
			$excerpt = $this->get_content();
		}
		remove_filter( 'activitypub_object_content_template', array( self::class, 'remove_ap_permalink_from_template' ) );

		$category   = $this->format_category();
		$start_time = $this->format_start_time();
		$end_time   = $this->format_end_time();
		$address    = $this->format_address();

		$formatted_items = array();
		if ( ! empty( $category ) ) {
			$formatted_items[] = "🏷️ $category";
		}

		if ( ! empty( $start_time ) ) {
			$formatted_items[] = "🗓️ {$start_time}";
		}

		if ( ! empty( $end_time ) ) {
			$formatted_items[] = "⏳ {$end_time}";
		}

		if ( ! empty( $address ) ) {
			$formatted_items[] = "📍 {$address}";
		}
		// Compose the summary based on the number of meta items.
		if ( count( $formatted_items ) > 1 ) {
			$summary = '<ul><li>' . implode( '</li><li>', $formatted_items ) . '</li></ul>';
		} elseif ( 1 === count( $formatted_items ) ) {
			$summary = $formatted_items[0]; // Just the one item without <ul><li>.
		} else {
			$summary = ''; // No items, so no output.
		}

		$summary .= $excerpt;
		return $summary;
	}

	/**
	 * Remove the permalink shortcode from a WordPress template.
	 *
	 * This used for the summary template, because the summary usually gets,
	 * used when converting a object, where the URL is usually appended anyway.
	 *
	 * @param string $template The template string.
	 */
	public static function remove_ap_permalink_from_template( $template ) {
		$template = str_replace( '[ap_permalink]', '', $template );
		$template = str_replace( '[ap_permalink type="html"]', '', $template );

		return $template;
	}

	/**
	 * Generic function that converts an WP-Event object to an ActivityPub-Event object.
	 *
	 * @return Event_Object
	 */
	public function to_object(): Event_Object {
		$activitypub_object = new Event_Object();
		$activitypub_object = $this->transform_object_properties( $activitypub_object );

		$published = \strtotime( $this->wp_object->post_date_gmt );

		$activitypub_object->set_published( \gmdate( 'Y-m-d\TH:i:s\Z', $published ) );

		$updated = \strtotime( $this->wp_object->post_modified_gmt );

		if ( $updated > $published ) {
			$activitypub_object->set_updated( \gmdate( 'Y-m-d\TH:i:s\Z', $updated ) );
		}

		$activitypub_object->set_content_map(
			array(
				$this->get_locale() => $this->get_content(),
			)
		);

		$activitypub_object->set_to(
			array(
				'https://www.w3.org/ns/activitystreams#Public',
				$this->get_actor_object()->get_followers(), // this fails on my machine
			)
		);

		return $activitypub_object;
	}
}
