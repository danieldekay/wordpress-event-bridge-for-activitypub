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

use Activitypub\Activity\Extended_Object\Event as Event_Object;
use Activitypub\Activity\Extended_Object\Place;
use ActivityPub_Event_Bridge\Activitypub\Transformer\Event;
use GatherPress\Core\Event as GatherPress_Event;

/**
 * ActivityPub Transformer for VS Event
 *
 * @since 1.0.0
 */
final class GatherPress extends Event {

	/**
	 * The current GatherPress Event object.
	 *
	 * @var GatherPress_Event
	 */
	protected $gp_event;

	/**
	 * The current GatherPress Venue object.
	 *
	 * @var Event
	 */
	protected $gp_venue;

	/**
	 * Extend the constructor, to also set the GatherPress objects.
	 *
	 * This is a special class object form The Events Calendar which
	 * has a lot of useful functions, we make use of our getter functions.
	 *
	 * @param WP_Post $wp_object The WordPress object.
	 * @param string  $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object, $wp_taxonomy );
		$this->gp_event = new GatherPress_Event( $this->wp_object->ID );
		$this->gp_venue = $this->gp_event->get_venue_information();
	}

	/**
	 * Get the event location.
	 *
	 * @return Place|null The place objector null if not place set.
	 */
	public function get_location(): ?Place {
		$address = $this->gp_venue['full_address'];
		if ( $address ) {
			$place = new Place();
			$place->set_type( 'Place' );
			$place->set_name( $address );
			$place->set_address( $address );
			return $place;
		} else {
			return null;
		}
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_end_time(): ?string {
		return $this->gp_event->get_datetime_end( 'Y-m-d\TH:i:s\Z' );
	}

	/**
	 * Get the end time from the event object.
	 */
	public function get_start_time(): string {
		return $this->gp_event->get_datetime_start( 'Y-m-d\TH:i:s\Z' );
	}

	/**
	 * Get the event link from the events metadata.
	 */
	private function get_event_link() {

		$event_link = get_post_meta( $this->wp_object->ID, 'event-link', true );
		if ( $event_link ) {
			return array(
				'type'      => 'Link',
				'name'      => 'Website',
				'href'      => \esc_url( $event_link ),
				'mediaType' => 'text/html',
			);
		}
	}

	/**
	 * Overrides/extends the get_attachments function to also add the event Link.
	 */
	protected function get_attachment(): array {
		$attachments = parent::get_attachment();
		if ( count( $attachments ) ) {
			$attachments[0]['type'] = 'Document';
			$attachments[0]['name'] = 'Banner';
		}
		$event_link = $this->get_event_link();
		if ( $event_link ) {
			$attachments[] = $this->get_event_link();
		}
		return $attachments;
	}

	/**
	 * Prevents gatherpress blocks from being rendered for the content.
	 *
	 * @param mixed $block_content The blocks content.
	 * @param mixed $block         The block.
	 */
	public static function filter_gatherpress_blocks( $block_content, $block ) {
		// Check if the block name starts with 'gatherpress'.
		if ( strpos( $block['blockName'], 'gatherpress/' ) === 0 ) {
			return ''; // Skip rendering this block.
		}

		return $block_content; // Return the content for other blocks.
	}

	/**
	 * Apply the filter for preventing the rendering off gatherpress blocks just in time.
	 *
	 * @return Event_Object
	 */
	public function to_object(): Event_Object {
		add_filter( 'render_block', array( self::class, 'filter_gatherpress_blocks' ), 10, 2 );
		$activitypub_object = parent::to_object();
		remove_filter( 'render_block', array( self::class, 'filter_gatherpress_blocks' ) );
		return $activitypub_object;
	}

	/**
	 * Determine whether the event is online.
	 *
	 * @return bool
	 */
	public function get_is_online(): bool {
		return $this->gp_event->maybe_get_online_event_link() ? true : false;
	}
}
