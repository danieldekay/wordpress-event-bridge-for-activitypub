<?php
/**
 * ActivityPub Transformer for the plugin Very Simple Event List.
 *
 * @package ActivityPub_Event_Bridge
 * @license AGPL-3.0-or-later
 */

namespace ActivityPub_Event_Bridge\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use ActivityPub_Event_Bridge\Activitypub\Transformer\Event as Event_Transformer;
use DateTime;

/**
 * ActivityPub Transformer for events from the WordPress plugin 'Events Manager'
 *
 * @see https://wordpress.org/plugins/events-manager/
 *
 * @since 1.0.0
 */
final class WP_Event_Manager extends Event_Transformer {
	/**
	 * Returns whether the even is online
	 *
	 * @return bool
	 */
	protected function get_is_online() {
		$is_online_text = get_post_meta( $this->wp_object->ID, '_event_online', true );
		$is_online      = false;
		// Radio buttons.
		if ( 'yes' === $is_online_text ) {
			$is_online = true;
		}
		// Checkbox.
		if ( '1' === $is_online_text ) {
			$is_online = true;
		}
		return $is_online;
	}

	/**
	 * Get the event location.
	 *
	 * @return array The Place.
	 */
	public function get_location(): ?Place {
		$location_name = get_post_meta( $this->wp_object->ID, '_event_online', true );

		if ( $location_name ) {
			$location = new Place();
			$location->set_name( $location_name );
			$location->set_sensitive( null );
			$location->set_address( $location_name );

			return $location;
		}
		return null;
	}

	/**
	 * Get the end time from the events metadata.
	 */
	public function get_end_time(): ?string {
		$end_date     = get_post_meta( $this->wp_object->ID, '_event_end_date', true );
		$end_datetime = new DateTime( $end_date );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $end_datetime->getTimestamp() );
	}

	/**
	 * Get the end time from the events metadata.
	 */
	public function get_start_time(): string {
		$start_date     = get_post_meta( $this->wp_object->ID, '_event_start_date', true );
		$start_datetime = new DateTime( $start_date );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $start_datetime->getTimestamp() );
	}

	/**
	 * Get the event link as an ActivityPub Link object, but as an associative array.
	 *
	 * @return array
	 */
	private function get_event_link_attachment(): array {
		$event_link_url = get_post_meta( $this->wp_object->ID, '_event_video_url', true );

		if ( str_starts_with( $event_link_url, 'http' ) ) {
			return array(
				'type'      => 'Link',
				'name'      => 'Video URL',
				'href'      => \esc_url( $event_link_url ),
				'mediaType' => 'text/html',
			);
		} else {
			return null;
		}
	}

	/**
	 * Overrides/extends the get_attachments function to also add the event Link.
	 */
	protected function get_attachment() {
		// Get attachments via parent function.
		$attachments = parent::get_attachment();

		// The first attachment is the featured image, make sure it is compatible with Mobilizon.
		if ( count( $attachments ) ) {
			$attachments[0]['type'] = 'Document';
			$attachments[0]['name'] = 'Banner';
		}

		if ( $this->get_event_link_attachment() ) {
			$attachments[] = $this->get_event_link_attachment();
		}
		return $attachments;
	}

	/**
	 * Get the events title/name.
	 *
	 * @return string
	 */
	protected function get_name(): string {
		return $this->wp_object->post_title;
	}
}
