<?php
/**
 * File responsible for defining and registering custom shortcodes.
 *
 * @package ActivityPub_Event_Bridge
 * @license AGPL-3.0-or-later
 */

namespace ActivityPub_Event_Bridge;

use Activitypub\Shortcodes;
use Activitypub\Transformer\Factory as Transformer_Factory;
use ActivityPub_Event_Bridge\Activitypub\Transformer\Event as Event_Transformer;
use DateTime;

/**
 * Class responsible for defining and registering custom shortcodes.
 */
class Event_Shortcodes extends Shortcodes {
	/**
	 * Register the shortcodes.
	 */
	public static function register() {
		foreach ( get_class_methods( self::class ) as $shortcode ) {
			if ( 'init' !== $shortcode ) {
				add_shortcode( 'ap_' . $shortcode, array( self::class, $shortcode ) );
			}
		}
	}

	/**
	 * Unregister the shortcodes.
	 */
	public static function unregister() {
		foreach ( get_class_methods( self::class ) as $shortcode ) {
			if ( 'init' !== $shortcode ) {
				remove_shortcode( 'ap_' . $shortcode );
			}
		}
	}

	/**
	 * Get the transformer of the current event post.
	 *
	 * @return ?Event_Transformer The Event Transformer.
	 */
	protected static function get_transformer(): ?Event_Transformer {
		$post = self::get_item();

		if ( ! $post ) {
			return null;
		}

		$transformer = Transformer_Factory::get_transformer( $post );

		if ( ! is_subclass_of( $transformer, Event_Transformer::class ) ) {
			return null;
		}

		return $transformer;
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
		return wp_date( $datetime_format, $start_timestamp );
	}

	/**
	 * Generates output for the 'apeb_start_time' shortcode.
	 *
	 * @param ?array $atts  The shortcodes attributes.
	 *
	 * @return string The formatted start date and time of the event.
	 */
	public static function start_time( $atts ) {
		$transformer = self::get_transformer();

		if ( ! $transformer ) {
			return '';
		}

		$args = shortcode_atts(
			array(
				'icon'  => 'true',
				'title' => 'true',
			),
			$atts,
			'ap_start_time'
		);

		$args['icon']  = filter_var( $args['icon'], FILTER_VALIDATE_BOOLEAN );
		$args['title'] = filter_var( $args['title'], FILTER_VALIDATE_BOOLEAN );

		$start_timestamp = $transformer->get_start_time();

		if ( ! $start_timestamp ) {
			return '';
		}

		$start_time = array();

		if ( $args['icon'] ) {
			$start_time[] = '🗓️';
		}

		if ( $args['title'] ) {
			$start_time[] = __( 'Start', 'activitypub-event-bridge' ) . ':';
		}

		$start_time[] = self::format_time( $start_timestamp );

		$start_time = implode( ' ', $start_time );

		return $start_time;
	}

	/**
	 * Generates output for the 'apeb_end_time' shortcode.
	 *
	 * @param ?array $atts  The shortcodes attributes.
	 *
	 * @return string The formatted end date and time of the event.
	 */
	public static function end_time( $atts ) {
		$transformer = self::get_transformer();

		if ( ! $transformer ) {
			return '';
		}

		$args = shortcode_atts(
			array(
				'icon'  => 'true',
				'title' => 'true',
			),
			$atts,
			'ap_end_time'
		);

		$args['icon']  = filter_var( $args['icon'], FILTER_VALIDATE_BOOLEAN );
		$args['title'] = filter_var( $args['title'], FILTER_VALIDATE_BOOLEAN );

		$end_timestamp = $transformer->get_end_time();

		if ( ! $end_timestamp ) {
			return '';
		}

		$end_time = array();

		if ( $args['icon'] ) {
			$end_time[] = '⏳';
		}

		if ( $args['title'] ) {
			$end_time[] = __( 'End', 'activitypub-event-bridge' ) . ':';
		}

		$end_time[] = self::format_time( $end_timestamp );

		$end_time = implode( ' ', $end_time );

		return $end_time;
	}

	/**
	 * Generates output for the 'apeb_location shortcode.
	 *
	 * @param ?array $atts  The shortcodes attributes.
	 *
	 * @return string The formatted location/address of the event.
	 */
	public static function location( $atts ) {
		$transformer = self::get_transformer();

		if ( ! $transformer ) {
			return '';
		}

		$args = shortcode_atts(
			array(
				'icon'    => 'true',
				'title'   => 'true',
				'country' => 'true',
				'zip'     => 'true',
				'city'    => 'true',
				'street'  => 'true',
				'name'    => 'true',
			),
			$atts,
			'ap_location'
		);

		foreach ( $args as $arg => $value ) {
			$args[ $arg ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		}

		$location = $transformer->get_location();

		if ( ! $location ) {
			return '';
		}

		$output = array();

		if ( $args['icon'] ) {
			$output[] = '📍';
		}

		if ( $args['title'] ) {
			$output[] = __( 'Location', 'activitypub-event-bridge' ) . ':';
		}

		$address = $location->get_address();

		if ( $address ) {
			if ( is_string( $address ) ) {
				$output[] = $address;
			}
			if ( is_array( $address ) ) {
				if ( $args['name'] && array_key_exists( 'name', $address ) ) {
					$output[] = $address['name'] . ',';
				}
				if ( $args['street'] && array_key_exists( 'streetAddress', $address ) ) {
					$output[] = $address['streetAddress'] . ',';
				}
				if ( $args['zip'] && array_key_exists( 'postalCode', $address ) ) {
					$output[] = $address['postalCode'];
				}
				if ( $args['city'] && array_key_exists( 'addressLocality', $address ) ) {
					$output[] = $address['addressLocality'] . ',';
				}
				if ( $args['country'] && array_key_exists( 'addressCountry', $address ) ) {
					$output[] = $address['addressCountry'];
				}
			}
		}

		$output = implode( ' ', $output );

		return $output;
	}
}
