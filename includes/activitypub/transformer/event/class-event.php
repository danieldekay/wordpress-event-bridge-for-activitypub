<?php
/**
 * Replace the default ActivityPub Transformer
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Event as Event_Object;
use Activitypub\Activity\Extended_Object\Place;
use Activitypub\Shortcodes;
use Activitypub\Transformer\Post;
use DateTime;
use WP_Comment;
use WP_Post;

/**
 * Base transformer for WordPress event post types to ActivityPub events.
 *
 * Everything that transforming several WordPress post types that represent events
 * have in common, as well as sane defaults for events should be defined here.
 *
 * BeforeFirstRelease:
 * [ ] remove link at the end of the content.
 * [ ] add organizer.
 * [ ] do add Cancelled reason in the content.
 */
abstract class Event extends Post {
	/**
	 * The WordPress event taxonomy.
	 *
	 * @var ?string
	 */
	protected $wp_taxonomy;

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
		return \comments_open( $this->item );
	}

	/**
	 * Set a hardcoded template for the content.
	 *
	 * This actually disabled templates for the content.
	 * Maybe this independent templates for events will be added later.
	 */
	protected function get_post_content_template(): string {
		return '[ap_content]';
	}

	/**
	 * Extend the construction of the Post Transformer to also set the according taxonomy of the event post type.
	 *
	 * @param \WP_Post $item The WordPress post object (event).
	 * @param string   $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $item, $wp_taxonomy = 'category' ) {
		parent::__construct( $item );
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
	 *
	 * @return ?string
	 */
	public function get_category(): ?string {
		if ( is_null( $this->wp_taxonomy ) ) {
			return null;
		}
		$current_category_mapping = \get_option( 'event_bridge_for_activitypub_event_category_mappings', array() );
		$terms                    = \get_the_terms( $this->item, $this->wp_taxonomy );

		// Check if the event has a category set and if that category has a specific mapping return that one.
		if ( ! is_wp_error( $terms ) && $terms && array_key_exists( $terms[0]->slug, $current_category_mapping ) ) {
			return sanitize_text_field( $current_category_mapping[ $terms[0]->slug ] );
		} else {
			// Return the default event category.
			return sanitize_text_field( \get_option( 'event_bridge_for_activitypub_default_event_category', 'MEETING' ) );
		}
	}

	/**
	 * Retrieves the excerpt text (may be HTML). Used for constructing the summary.
	 *
	 * @return ?string
	 */
	protected function retrieve_excerpt(): ?string {
		if ( $this->item->post_excerpt ) {
			return $this->item->post_excerpt;
		} else {
			return null;
		}
	}

	/**
	 * Get the start time.
	 *
	 * This is mandatory and must be implemented in the final event transformer class.
	 */
	abstract public function get_start_time(): string;

	/**
	 * Get the end time.
	 *
	 * This is not mandatory and therefore just return null by default.
	 */
	public function get_end_time(): ?string {
		return null;
	}

	/**
	 * Get a default for the location.
	 *
	 * This should be overridden in the actual event transformer.
	 *
	 * @return array|Place|null
	 */
	public function get_location() {
		return null;
	}

	/**
	 * Default value for the event status.
	 */
	public function get_status(): ?string {
		return 'CONFIRMED';
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

	/**
	 * Compose a human readable formatted time.
	 *
	 * @param ?string $time The time which needs to be formatted.
	 */
	protected static function format_time( $time ) {
		if ( is_null( $time ) ) {
			return '';
		}
		$start_datetime  = new DateTime( $time );
		$start_timestamp = $start_datetime->getTimestamp();
		$datetime_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		return \wp_date( $datetime_format, $start_timestamp );
	}

	/**
	 * Generates output for the 'ap_start_time' shortcode.
	 *
	 * @param ?array $atts  The shortcode's attributes.
	 * @return string The formatted start date and time of the event.
	 */
	public function shortcode_start_time( $atts ) {
		$start_timestamp = $this->get_start_time();
		return $this->generate_time_output( $start_timestamp, $atts, '🗓️', __( 'Start', 'event-bridge-for-activitypub' ) );
	}

	/**
	 * Generates output for the 'ap_end_time' shortcode.
	 *
	 * @param ?array $atts  The shortcode's attributes.
	 * @return string The formatted end date and time of the event.
	 */
	public function shortcode_end_time( $atts ) {
		$end_timestamp = $this->get_end_time();
		return $this->generate_time_output( $end_timestamp, $atts, '⏳', __( 'End', 'event-bridge-for-activitypub' ) );
	}

	/**
	 * Generates the formatted time output for a shortcode.
	 *
	 * @param string|null $timestamp  The timestamp for the event time.
	 * @param array       $atts       The shortcode attributes.
	 * @param string      $icon       The icon to display.
	 * @param string      $label      The label to display (e.g., 'Start', 'End').
	 * @return string The formatted date and time, or an empty string if the timestamp is invalid.
	 */
	private function generate_time_output( $timestamp, $atts, $icon, $label ): string {
		if ( ! $timestamp ) {
			return '';
		}

		$args = shortcode_atts(
			array(
				'icon'  => 'true',
				'label' => 'true',
			),
			$atts
		);

		$args['icon']  = filter_var( $args['icon'], FILTER_VALIDATE_BOOLEAN );
		$args['label'] = filter_var( $args['label'], FILTER_VALIDATE_BOOLEAN );

		$output = array();

		if ( $args['icon'] ) {
			$output[] = $icon;
		}

		if ( $args['label'] ) {
			$output[] = $label . ':';
		}

		$output[] = self::format_time( $timestamp );

		return implode( ' ', $output );
	}

	/**
	 * Generates output for the 'ap_location' shortcode.
	 *
	 * @param ?array $atts The shortcode's attributes.
	 * @return string The formatted location/address of the event.
	 */
	public function shortcode_location( $atts ) {
		$args = shortcode_atts(
			array(
				'icon'    => 'true',
				'label'   => 'true',
				'country' => 'true',
				'zip'     => 'true',
				'city'    => 'true',
				'street'  => 'true',
			),
			$atts,
			'ap_location'
		);

		// Convert attributes to booleans.
		$args = array_map(
			function ( $value ) {
				return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			},
			$args
		);

		$location = $this->get_location();
		if ( ! $location ) {
			return '';
		}

		$output = array();

		if ( is_array( $location ) && isset( $location['type'] ) && 'VirtualLocation' === $location['type'] ) {
			if ( $args['icon'] ) {
				$output[] = '🔗';
			}
			if ( $args['label'] && isset( $location['name'] ) ) {
				$output[] = $location['name'] . ':';
			}
		} else {
			if ( $args['icon'] ) {
				$output[] = '📍';
			}

			if ( $args['label'] ) {
				$output[] = esc_html__( 'Location', 'event-bridge-for-activitypub' ) . ':';
			}
		}

		$output[] = $this->get_formatted_address( true, $args );

		// Join output array into a single string with spaces and return.
		return implode( ' ', array_filter( $output ) );
	}

	/**
	 * Formats the address based on provided arguments.
	 *
	 * @param mixed $address The address data, either as a string or an array.
	 * @param array $args    The arguments for which components to include.
	 * @return string The formatted address.
	 */
	protected static function format_address( $address, $args = array() ) {
		if ( is_string( $address ) ) {
			return esc_html( $address );
		}

		if ( empty( $args ) ) {
			$args = array(
				'icon'    => 'true',
				'title'   => 'true',
				'country' => 'true',
				'zip'     => 'true',
				'city'    => 'true',
				'street'  => 'true',
			);
		}

		if ( is_array( $address ) ) {
			$address_parts = array();

			$components = array(
				'street'  => 'streetAddress',
				'zip'     => 'postalCode',
				'city'    => 'addressLocality',
				'country' => 'addressCountry',
			);

			foreach ( $components as $arg_key => $address_key ) {
				if ( $args[ $arg_key ] && ! empty( $address[ $address_key ] ) ) {
					$address_parts[] = esc_html( $address[ $address_key ] );
				}
			}

			return implode( ', ', $address_parts );
		}

		return '';
	}

	/**
	 * Format the category using the translation.
	 */
	protected function format_categories(): string {
		if ( is_null( $this->wp_taxonomy ) ) {
			return '';
		}
		$categories = array();

		// Add the federated category string.
		require_once EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . '/includes/event-categories.php';
		$federated_category = $this->get_category();
		if ( array_key_exists( $federated_category, EVENT_BRIDGE_FOR_ACTIVITYPUB_EVENT_CATEGORIES ) ) {
			$categories[] = EVENT_BRIDGE_FOR_ACTIVITYPUB_EVENT_CATEGORIES[ $federated_category ];
		}

		// Add all category terms.
		$terms = \get_the_terms( $this->item, $this->wp_taxonomy );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[] = $term->name;
			}
		}

		if ( ! empty( $categories ) ) {
			return implode( ' · ', array_unique( $categories ) );
		}
		return '';
	}

	/**
	 * Register the shortcodes.
	 */
	public function register_shortcodes() {
		Shortcodes::register();
		foreach ( array( 'location', 'start_time', 'end_time' ) as $shortcode ) {
			\add_shortcode( 'ap_' . $shortcode, array( $this, 'shortcode_' . $shortcode ) );
		}
	}

	/**
	 * Register the shortcodes.
	 */
	public function unregister_shortcodes() {
		Shortcodes::unregister();
		foreach ( array( 'location', 'start_time', 'end_time' ) as $shortcode ) {
			\remove_shortcode( 'ap_' . $shortcode );
		}
	}

	/**
	 * Get the summary.
	 */
	public function get_summary(): ?string {
		if ( 'preset' === get_option( 'event_bridge_for_activitypub_summary_type', 'preset' ) ) {
			$summary = EVENT_BRIDGE_FOR_ACTIVITYPUB_SUMMARY_TEMPLATE;
		} else {
			$summary = $this->get_event_summary_template();
		}

		// It seems that shortcodes are only applied to published posts.
		if ( is_preview() ) {
			$this->item->post_status = 'publish';
		}

		// Register our shortcodes just in time.

		$this->register_shortcodes();

		// Fill in the shortcodes.
		\setup_postdata( $this->item );
		Shortcodes::register();
		$summary = \do_shortcode( $summary );
		\wp_reset_postdata();

		$summary = \wpautop( $summary );
		$summary = \preg_replace( '/[\n\r\t]/', '', $summary );
		$summary = \trim( $summary );

		$summary = \apply_filters( 'event_bridge_for_activitypub_the_summary', $summary, $this->item );

		// Unregister the shortcodes.
		$this->unregister_shortcodes();

		if ( 'plain' === get_option( 'event_bridge_for_activitypub_summary_format', 'html' ) ) {
			$summary = self::strip_html_preserve_linebreaks( $summary );
		}

		return $summary;
	}

	/**
	 * Strip all HTML but preverse some line breaks.
	 *
	 * @param mixed $content The HTML input.
	 * @return string
	 */
	private static function strip_html_preserve_linebreaks( $content ): string {
		// Replace <br> with newlines.
		$content = preg_replace( '/<br\s*\/?>/i', "\n", $content );

		// Replace closing </p> followed by <p> with double newlines (preserve paragraph breaks).
		$content = preg_replace( '/<\/p>\s*<p>/', "\n\n", $content );

		// Preserve list structure.
		$content = preg_replace( '/<\/ul>/i', "\n", $content );
		$content = preg_replace( '/<li>/i', '- ', $content );
		$content = preg_replace( '/<\/li>/i', "\n", $content );

		// Remove all remaining HTML tags.
		$content = wp_strip_all_tags( $content );

		// Normalize excessive newlines (more than 2 in a row to just 2).
		$content = preg_replace( "/\n{3,}/", "\n\n", $content );

		// Trim trailing newlines.
		return trim( $content );
	}

	/**
	 * Get the address as a string.
	 *
	 * @param bool  $include_location_name  Whether to include the locations name.
	 * @param array $args                   The arguments forwarded to format_address.
	 *
	 * @return string
	 */
	public function get_formatted_address( $include_location_name = false, $args = array() ) {
		$location = $this->get_location();

		if ( $location instanceof Place ) {
			$location_name    = $location->get_name();
			$foramted_address = self::format_address( $location->get_address(), $args );

			$loaction_parts = array();

			if ( $location_name ) {
				$location_parts[] = $location_name;
			}

			if ( $foramted_address ) {
				$location_parts[] = $foramted_address;
			}

			if ( ! empty( $location_parts ) ) {
				return implode( ', ', $location_parts );
			}
		} elseif ( is_array( $location ) && isset( $location['type'] ) && 'VirtualLocation' === $location['type'] ) {
			if ( isset( $location['url'] ) ) {
				return $location['url'];
			}
		}

		return '';
	}

	/**
	 * Gets the template to use to generate the summary of the ActivityStreams representation of an event post.
	 *
	 * @return string The Template.
	 */
	protected function get_event_summary_template() {
		$summary  = \get_option( 'event_bridge_for_activitypub_custom_summary', EVENT_BRIDGE_FOR_ACTIVITYPUB_SUMMARY_TEMPLATE );
		$template = $summary ?? EVENT_BRIDGE_FOR_ACTIVITYPUB_SUMMARY_TEMPLATE;

		return apply_filters( 'event_bridge_for_activitypub_summary_template', $template, $this->item );
	}

	/**
	 * By default set the timezone of the WordPress site.
	 *
	 * This is likely to be overwritten by the actual transformer.
	 *
	 * @return string  The timezone string of the site.
	 */
	public function get_timezone(): string {
		return \wp_timezone_string();
	}

	/**
	 * Remove the permalink shortcode from a WordPress template.
	 *
	 * This used for the summary template, because the summary usually gets,
	 * used when converting a object, where the URL is usually appended anyway.
	 *
	 * @param string             $template The template string.
	 * @param WP_Post|WP_Comment $item The item which was used to select the template.
	 */
	public static function remove_ap_permalink_from_template( $template, $item ) {

		// we could override the template here, to get out custom template from an option.

		if ( 'event' === $item->post_type ) {
			$template = str_replace( '[ap_permalink]', '', $template );
			$template = str_replace( '[ap_permalink type="html"]', '', $template );
		}

		return $template;
	}

	/**
	 * Generic function that converts an WP-Event object to an ActivityPub-Event object.
	 *
	 * @return Event_object|\WP_Error
	 */
	public function to_object() {
		$activitypub_object = new Event_Object();
		$activitypub_object = $this->transform_object_properties( $activitypub_object );

		// Manually set boolean  because of https://github.com/Automattic/wordpress-activitypub/issues/1565.
		// @phpstan-ignore-next-line
		$activitypub_object->set_comments_enabled( $this->get_comments_enabled() );
		// @phpstan-ignore-next-line
		$activitypub_object->set_is_online( $this->get_is_online() );

		if ( \is_wp_error( $activitypub_object ) ) {
			return $activitypub_object;
		}

		// maybe move the following logic (till end of the function) into getter functions.

		$published = \strtotime( $this->item->post_date_gmt );

		$activitypub_object->set_published( \gmdate( 'Y-m-d\TH:i:s\Z', $published ) );

		$updated = \strtotime( $this->item->post_modified_gmt );

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
				$this->get_actor_object()->get_followers(),
			)
		);

		// @phpstan-ignore-next-line
		return $activitypub_object;
	}
}
