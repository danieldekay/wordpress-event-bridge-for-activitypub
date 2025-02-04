<?php
/**
 * Class file for the ActivityPub transformer of the venues of The Events Calendar to `as:Place`.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Class for the ActivityPub transformer of the venues of The Events Calendar to `as:Place`.
 *
 * @since 1.0.0
 */
final class EventPrime extends Base_Term_Place {
	/**
	 * Get URL
	 *
	 * @return string|null
	 */
	public function get_url() {
		$ep  = new \Eventprime_Basic_Functions();
		$url = $ep->ep_get_custom_page_url( 'venues_page', $this->item->term_id, 'venue', 'term' );

		if ( \is_wp_error( $url ) ) {
			return null;
		}
		return $url;
	}

	/**
	 * Get the best "ID" we currently have.
	 *
	 * @return string|null
	 */
	public function get_id() {
		return $this->get_url();
	}

	/**
	 * Get the event location.
	 *
	 * @return array|string|null The place/venue if one is set.
	 */
	public function get_address() {
		$address         = \get_term_meta( $this->item->term_id, 'em_address', true );
		$display_address = \get_term_meta( $this->item->term_id, 'em_display_address_on_frontend', true );

		if ( $address && $display_address ) {
			return $address;
		}

		return null;
	}
}
