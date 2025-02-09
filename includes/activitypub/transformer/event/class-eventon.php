<?php
/**
 * Class file for the ActivityPub Transformer for events the WordPress plugin EventON – Events Calendar.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Event as Event_Transformer;
use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place\EventOn as EventOn_Place_Transformer;

/**
 * Class for the ActivityPub Transformer for events the WordPress plugin EventON – Events Calendar.
 *
 * This transformer tries a different principle: The setters are chainable.
 *
 * @since 1.0.0
 */
final class EventOn extends Event_Transformer {
	/**
	 * The location meta for all locations.
	 *
	 * @var array
	 */
	protected $tax_meta;

	/**
	 * Extend the construction of the Post Transformer to also set the according taxonomy of the event post type.
	 *
	 * @param \WP_Post $item The WordPress post object (event).
	 * @param string   $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $item, $wp_taxonomy = 'category' ) {
		parent::__construct( $item );
		$this->wp_taxonomy = $wp_taxonomy;

		$this->tax_meta = \get_option( 'evo_tax_meta' );
	}


	/**
	 * Get content.
	 */
	public function get_content(): string {
		$subtitle = \get_post_meta( $this->item->ID, 'evcal_subtitle', true );

		$content = $subtitle ? $subtitle . '<br>' : '';
		$content = $content . parent::get_content();
		return $content;
	}

	/**
	 * Get the event location(s).
	 *
	 * @return array|null The Place.
	 */
	public function get_location() {
		$location = array();

		$terms = \get_the_terms( $this->item->ID, 'event_location' );

		// The terms may both contain virtual and physical Locations.
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$location_transformer = new EventOn_Place_Transformer( $term );
				$location[]           = $location_transformer->to_object()->to_array( false );
			}
		}

		// Virtual Locations can also be directly int the post meta, not in terms!
		$virtual_url  = \get_post_meta( $this->item->ID, '_vir_url', true );
		$virtual_type = \get_post_meta( $this->item->ID, '_vir_type', true );

		if ( $virtual_url ) {
			$virtual_location = array(
				'type' => 'VirtualLocation',
				'url'  => (string) $virtual_url,
			);
			if ( $virtual_type ) {
				$virtual_location['name'] = (string) $virtual_type;
			}
			$location[] = $virtual_location;
		}

		// If we only have one location, send object directy, not in array.
		if ( 1 === count( $location ) ) {
			$location = reset( $location );
		} elseif ( empty( $location ) ) {
			return null;
		}

		return $location;
	}

	/**
	 * Get the end time from the events metadata.
	 */
	public function get_end_time(): ?string {
		$end_time = \get_post_meta( $this->item->ID, '_unix_end_ev', true );
		$timezone = \get_post_meta( $this->item->ID, '_evo_tz', true );
		$timezone = $timezone ? new \DateTimeZone( $timezone ) : null;

		if ( is_null( $end_time ) || empty( $end_time ) ) {
			return null;
		}
		return \wp_date( 'Y-m-d\TH:i:sP', (int) $end_time, $timezone );
	}

	/**
	 * Get timezone
	 *
	 * @return string
	 */
	public function get_timezone(): string {
		$timezone = \get_post_meta( $this->item->ID, '_evo_tz', true );

		return $timezone ?? \wp_timezone_string();
	}

	/**
	 * Get the end time from the events metadata.
	 */
	public function get_start_time(): string {
		$start_time = \get_post_meta( $this->item->ID, '_unix_start_ev', true );
		$timezone   = \get_post_meta( $this->item->ID, '_evo_tz', true );
		$timezone   = $timezone ? new \DateTimeZone( $timezone ) : null;

		return \wp_date( 'Y-m-d\TH:i:sP', (int) $start_time, $timezone );
	}

	/**
	 * Get the event link from the events metadata.
	 *
	 * @return ?array Associated array of an ActivityStreams Link object with the events URL.
	 */
	private function get_event_link(): ?array {
		$event_link       = \get_post_meta( $this->item->ID, 'event-link', true );
		$event_link_label = \get_post_meta( $this->item->ID, 'event-link-label', true ) ?? 'Event Link';
		if ( $event_link ) {
			return array(
				'type'      => 'Link',
				'name'      => $event_link_label,
				'href'      => \esc_url( $event_link ),
				'mediaType' => 'text/html',
			);
		}
		return null;
	}

	/**
	 * Overrides/extends the get_attachments function to also add the event Link.
	 *
	 * @return array
	 */
	protected function get_attachment(): array {
		$attachments = parent::get_attachment();
		if ( count( $attachments ) ) {
			$attachments[0]['type'] = 'Document';
			$attachments[0]['name'] = 'Banner';
		}
		$event_link = $this->get_event_link();
		if ( $event_link ) {
			$attachments[] = $event_link;
		}
		return $attachments;
	}

	/**
	 * Retrieves the excerpt text (may be HTML). Used for constructing the summary.
	 *
	 * @return ?string
	 */
	protected function retrieve_excerpt(): ?string {
		if ( \get_post_meta( $this->item->ID, 'event-summary', true ) ) {
			return \get_post_meta( $this->item->ID, 'event-summary', true );
		} else {
			return parent::retrieve_excerpt();
		}
	}
}
