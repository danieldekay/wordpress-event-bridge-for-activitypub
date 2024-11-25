<?php
/**
 * ActivityPub Transformer for the plugin EventPrime.
 *
 * @package ActivityPub_Event_Bridge
 * @license AGPL-3.0-or-later
 */

namespace ActivityPub_Event_Bridge\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Event as Event_Object;
use Activitypub\Activity\Extended_Object\Place;
use ActivityPub_Event_Bridge\Activitypub\Transformer\Event;
use GatherPress\Core\Event as GatherPress_Event;

/**
 * ActivityPub Transformer for VS Event
 *
 * @since 1.0.0
 */
final class EventPrime extends Event {
	/**
	 * Get the end time from the event object.
	 */
	protected function get_end_time(): ?string {
		$timestamp = get_post_meta( $this->wp_object->ID, 'em_end_date', true );
		if ( $timestamp ) {
			return \gmdate( 'Y-m-d\TH:i:s\Z', $timestamp );
		} else {
			return null;
		}
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_start_time(): string {
		$timestamp = get_post_meta( $this->wp_object->ID, 'em_start_date', true );
		if ( $timestamp ) {
			return \gmdate( 'Y-m-d\TH:i:s\Z', $timestamp );
		} else {
			return '';
		}
	}
}
