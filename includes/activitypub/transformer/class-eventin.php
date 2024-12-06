<?php
/**
 * ActivityPub Transformer for Events managed with Eventin.
 *
 * @link https://support.themewinter.com/docs/plugins/docs-category/eventin/
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use Event_Bridge_For_ActivityPub\Activitypub\Transformer\Event;
use DateTime;
use DateTimeZone;
use Etn\Core\Event\Event_Model;

use function Activitypub\esc_hashtag;

/**
 * ActivityPub Transformer for Events managed with Eventin.
 *
 * @since 1.0.0
 */
final class Eventin extends Event {

	/**
	 * Holds the EM_Event object.
	 *
	 * @var Event_Model
	 */
	protected $event_model;

	/**
	 * Extend the constructor, to also set the Event Model.
	 *
	 * This is a special class object form The Events Calendar which
	 * has a lot of useful functions, we make use of our getter functions.
	 *
	 * @param WP_Post $wp_object The WordPress object.
	 * @param string  $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object, $wp_taxonomy );
		$this->event_model = new Event_Model( $this->wp_object->ID );
	}

	/**
	 * Get the end time from the event object.
	 */
	public function get_start_time(): string {
		return \gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $this->event_model->get_start_datetime() ) );
	}

	/**
	 * Get the end time from the event object.
	 */
	public function get_end_time(): string {
		return \gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $this->event_model->get_end_datetime() ) );
	}

	/**
	 * Get the timezone of the event.
	 */
	public function get_timezone(): string {
		return $this->event_model->get_timezone();
	}

	/**
	 * Get whether the event is online.
	 *
	 * @return bool
	 */
	public function get_is_online(): bool {
		return 'online' === $this->event_model->__get( 'event_type' ) ? true : false;
	}

	/**
	 * Maybe add online link to attachments.
	 *
	 * @return array
	 */
	public function get_attachment(): array {
		$attachment = parent::get_attachment();

		$location = (array) $this->event_model->__get( 'location' );
		if ( array_key_exists( 'integration', $location ) && array_key_exists( $location['integration'], $location ) ) {
			$online_link  = array(
				'type'      => 'Link',
				'mediaType' => 'text/html',
				'name'      => $location[ $location['integration'] ],
				'href'      => $location[ $location['integration'] ],
			);
			$attachment[] = $online_link;
		}
		return $attachment;
	}

	/**
	 * Compose the events tags.
	 */
	public function get_tag() {
		// The parent tag function also fetches the mentions.
		$tags = parent::get_tag();

		$post_tags       = \wp_get_post_terms( $this->wp_object->ID, 'etn_tags' );
		$post_categories = \wp_get_post_terms( $this->wp_object->ID, 'etn_category' );

		if ( ! is_wp_error( $post_tags ) && $post_tags ) {
			foreach ( $post_tags as $term ) {
				$tag    = array(
					'type' => 'Hashtag',
					'href' => \esc_url( \get_tag_link( $term->term_id ) ),
					'name' => esc_hashtag( $term->name ),
				);
				$tags[] = $tag;
			}
		}

		if ( ! is_wp_error( $post_categories ) && $post_categories ) {
			foreach ( $post_categories as $term ) {
				$tag    = array(
					'type' => 'Hashtag',
					'href' => \esc_url( \get_tag_link( $term->term_id ) ),
					'name' => esc_hashtag( $term->name ),
				);
				$tags[] = $tag;
			}
		}

		if ( empty( $tags ) ) {
			return null;
		}

		return $tags;
	}

	/**
	 * Get the location.
	 *
	 * @return ?Place
	 */
	public function get_location(): ?Place {
		$location = (array) $this->event_model->__get( 'location' );

		if ( ! array_key_exists( 'address', $location ) ) {
			return null;
		}

		$place = new Place();

		$address = $location['address'];

		$place->set_name( $address );
		$place->set_address( $address );
		$place->set_sensitive( null );

		return $place;
	}
}
