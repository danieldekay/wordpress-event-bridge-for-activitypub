<?php
/**
 * Collection of functions that sanitize an incoming event.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Event;
use Activitypub\Activity\Extended_Object\Place;
use WP_Error;

use function Activitypub\sanitize_url;

/**
 * Collection of functions that sanitize an incoming event.
 *
 * We do a lot of duck-typing. We just discard/ignore attributes/properties we do not know.
 * Replacing this with defining a schema and using rest_sanitize_value_from_schema is a future goal.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */
class Sanitizer {
	/**
	 * Convert input array to an Event.
	 *
	 * @param array $data The object array.
	 *
	 * @return Event|WP_Error An Object built from the input array or WP_Error when it's not an array.
	 */
	public static function init_and_sanitize_event_object_from_array( $data ) {
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'invalid_array', __( 'Invalid array', 'event-bridge-for-activitypub' ), array( 'status' => 404 ) );
		}

		$event = new Event();

		// Straightforward sanitization of all attributes we possible make use of.
		if ( isset( $data['content'] ) ) {
			$event->set_content( \wp_kses_post( $data['content'] ) );
		}

		if ( isset( $data['summary'] ) ) {
			$event->set_summary( \wp_kses_post( $data['summary'] ) );
		}

		if ( isset( $data['name'] ) ) {
			$event->set_name( \sanitize_text_field( $data['name'] ) );
		}

		if ( isset( $data['startTime'] ) ) {
			$event->set_start_time( \sanitize_text_field( $data['startTime'] ) );
		}

		if ( isset( $data['endTime'] ) ) {
			$event->set_end_time( \sanitize_text_field( $data['endTime'] ) );
		}

		if ( isset( $data['published'] ) ) {
			$event->set_published( \sanitize_text_field( $data['published'] ) );
		}

		if ( isset( $data['id'] ) ) {
			$event->set_id( sanitize_url( $data['id'] ) );
		}

		if ( isset( $data['url'] ) ) {
			$event->set_url( sanitize_url( $data['url'] ) );
		}

		if ( isset( $data['location'] ) ) {
			$event->set_location( self::sanitize_place_object_from_array( $data['location'] ) );
		}

		if ( isset( $data['attachment'] ) ) {
			$event->set_attachment( self::sanitize_attachment( $data['attachment'] ) );
		}

		if ( isset( $data['tag'] ) ) {
			$event->set_tag( self::sanitize_attachment( $data['tag'] ) );
		}

		return $event;
	}

	/**
	 * Sanitize attachments.
	 *
	 * @param array $data The object array.
	 *
	 * @return ?array
	 */
	private static function sanitize_attachment( $data ) {
		if ( ! is_array( $data ) ) {
			return null;
		}

		if ( ! self::array_is_list( $data ) ) {
			$data = array( $data );
		}

		$attachment = array();

		foreach ( $data as $item ) {
			$sanitized_item = array();

			// Straightforward sanitization of all attributes we possible make use of.
			if ( isset( $item['name'] ) ) {
				$sanitized_item['name'] = \sanitize_text_field( $item['name'] );
			}
			if ( isset( $item['url'] ) ) {
				$sanitized_item['url'] = \sanitize_url( $item['url'] );
			}
			if ( isset( $item['id'] ) ) {
				$sanitized_item['id'] = \sanitize_url( $item['id'] );
			}
			if ( isset( $item['type'] ) ) {
				$sanitized_item['type'] = \sanitize_text_field( $item['type'] );
			}
			if ( isset( $item['href'] ) ) {
				$sanitized_item['href'] = \sanitize_text_field( $item['href'] );
			}

			if ( isset( $sanitized_item['url'] ) || isset( $sanitized_item['href'] ) || isset( $sanitized_item['name'] ) ) {
				$attachment[] = $sanitized_item;
			}
		}

		return $attachment;
	}

	/**
	 * Fallback for PHP version prior to 8.1 for array_is_list.
	 *
	 * @param array $arr The array to check.
	 * @return bool
	 */
	private static function array_is_list( $arr ) {
		if ( ! function_exists( 'array_is_list' ) ) {
			if ( array() === $arr ) {
				return true;
			}
			return array_keys( $arr ) === range( 0, count( $arr ) - 1 );
		}
		return array_is_list( $arr );
	}

	/**
	 * Convert input array to an Location.
	 *
	 * @param array $data The object array.
	 *
	 * @return ?Place An Object built from the input array or null.
	 */
	private static function sanitize_place_object_from_array( $data ) {
		if ( ! is_array( $data ) ) {
			return null;
		}

		// If the array is a list, work with the first item.
		if ( array_key_exists( 0, $data ) ) {
			$data = $data[0];
		}

		$place = new Place();

		if ( isset( $data['name'] ) ) {
			$place->set_name( \sanitize_text_field( $data['name'] ) );
		}

		if ( isset( $data['id'] ) ) {
			$place->set_id( sanitize_url( $data['id'] ) );
		}

		if ( isset( $data['url'] ) ) {
			$place->set_url( sanitize_url( $data['url'] ) );
		}

		if ( isset( $data['address'] ) ) {
			if ( is_string( $data['address'] ) ) {
				$place->set_address( \sanitize_text_field( $data['address'] ) );
			}
			if ( is_array( $data['address'] ) && isset( $data['address']['type'] ) && 'PostalAddress' === $data['address']['type'] ) {
				$address = array();
				if ( isset( $data['address']['streetAddress'] ) ) {
					$address['streetAddress'] = \sanitize_text_field( $data['address']['streetAddress'] );
				}
				if ( isset( $data['address']['postalCode'] ) ) {
					$address['postalCode'] = \sanitize_text_field( $data['address']['postalCode'] );
				}
				if ( isset( $data['address']['addressLocality'] ) ) {
					$address['addressLocality'] = \sanitize_text_field( $data['address']['addressLocality'] );
				}
				if ( isset( $data['address']['streetAddress'] ) ) {
					$address['addressState'] = \sanitize_text_field( $data['address']['addressState'] );
				}
				if ( isset( $data['address']['streetAddress'] ) ) {
					$address['addressCountry'] = \sanitize_text_field( $data['address']['addressCountry'] );
				}
				if ( isset( $data['address']['url'] ) ) {
					$address['url'] = \sanitize_url( $data['address']['url'] );
				}
				$place->set_address( $address );
			}
		}

		return $place;
	}
}
