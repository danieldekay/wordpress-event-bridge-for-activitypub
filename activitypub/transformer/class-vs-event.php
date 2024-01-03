<?php
/**
 * ActivityPub Transformer for the plugin Very Simple Event List.
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

use Activitypub\Activity\Event;
use Activitypub\Activity\Place;
use Activitypub\Transformer\Post;
use Activitypub\Model\Blog_user;

use function Activitypub\get_rest_url_by_path;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ActivityPub Transformer for VS Event
 *
 * @since 1.0.0
 */
class VS_Event extends Post {
	/**
	 * Get transformer name.
	 *
	 * Retrieve the transformers name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget name.
	 */
	public function get_transformer_name() {
		return 'activitypub-event-transformers/vs-event';
	}

	/**
	 * Get transformer title.
	 *
	 * Retrieve the transformers label.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget title.
	 */
	public function get_transformer_label() {
		return 'VS Event';
	}

	/**
	 * Get supported post types.
	 *
	 * Retrieve the list of supported WordPress post types this transformer widget can handle.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Widget categories.
	 */
	public static function get_supported_post_types() {
		return array( 'event' );
	}

	/**
	 * Returns the ActivityStreams 2.0 Object-Type for an Event.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-event
	 * @since 1.0.0
	 * @return string The Event Object-Type.
	 */
	protected function get_type() {
		return 'Event';
	}

	/**
	 * Get the event location.
	 *
	 * @param int $post_id The WordPress post ID.
	 * @return array The Place.
	 */
	public function get_location() {
		$address = get_post_meta( $this->object->ID, 'event-location', true );
		$place = new Place();
		$place->set_type( 'Place' );
		$place->set_name( $address );
		$place->set_address( $address );
		return $place;
	}

	/**
	 * Get the end time from the events metadata.
	 */
	protected function get_end_time() {
		$end_time = get_post_meta( $this->object->ID, 'event-date', true );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $end_time );
	}

	/**
	 * Get the end time from the events metadata.
	 */
	protected function get_start_time() {
		$start_time = get_post_meta( $this->object->ID, 'event-start-date', true );
		return \gmdate( 'Y-m-d\TH:i:s\Z', $start_time );
	}

	/**
	 * Get the event link from the events metadata.
	 */
	private function get_event_link() {
		$event_link = get_post_meta( $this->object->ID, 'event-link', true );
		if ( $event_link ) {
			return array(
				'type' => 'Link',
				'name' => 'Website',
				'href' => \esc_url( $event_link ),
				'mediaType' => 'text/html',
			);
		}
	}

	/**
	 * Overrides/extends the get_attachments function to also add the event Link.
	 */
	protected function get_attachment() {
		$attachments = parent::get_attachment();
		$attachments[0]['type'] = 'Document';
		$attachments[0]['name'] = 'Banner';
		$event_link = $this->get_event_link();
		if ( $event_link ) {
			$attachments[] = $this->get_event_link();
		}
		return $attachments;
	}

	/**
	 * This function tries to map VS-Event categories to Mobilizon event categories.
	 *
	 * @return string $category
	 */
	protected function get_category() {
		$post_categories = wp_get_post_terms( $this->object->ID, 'event_cat' );

		if ( empty( $post_categories ) ) {
			return 'MEETING';
		}

		// Prepare an array to store all category information for comparison.
		$category_info = array();

		// Extract relevant category information (name, slug, description) from the categories array.
		foreach ( $post_categories as $category ) {
			$category_info[] = strtolower( $category->name );
			$category_info[] = strtolower( $category->slug );
			$category_info[] = strtolower( $category->description );
		}

		// Convert mobilizon categories to lowercase for case-insensitive comparison.
		$mobilizon_categories = array_map( 'strtolower', Event::MOBILIZON_EVENT_CATEGORIES );

		// Initialize variables to track the best match.
		$best_mobilizon_category_match = '';
		$best_match_length = 0;

		// Check for the best match.
		foreach ( $mobilizon_categories as $mobilizon_category ) {
			foreach ( $category_info as $category ) {
				if ( stripos( $category, $mobilizon_category ) !== false ) {
					// Check if the current match is longer than the previous best match.
					$current_match_legnth = strlen( $mobilizon_category );
					if ( $current_match_legnth > $best_match_length ) {
						$best_mobilizon_category_match = $mobilizon_category;
						$best_match_length = $current_match_legnth;
					}
				}
			}
		}

		return ( '' != $best_mobilizon_category_match ) ? strtoupper( $best_mobilizon_category_match ) : 'MEETING';
	}

	/**
	 * Returns the User-URL of the Author of the Post.
		*
		* If `single_user` mode is enabled, the URL of the Blog-User is returned.
	 *
	 * @return string The User-URL.
	 */
	protected function get_attributed_to() {
		$user = new Blog_User();
		return $user->get_url();
	}

	/**
	 * Create a custom summary.
	 *
	 * It contains also the most important meta-information. The summary is often used when the
	 * ActivityPub object type 'Event' is not supported, e.g. in Mastodon.
	 *
	 * @return string $summary The custom event summary.
	 */
	public function get_summary() {
		if ( $this->object->excerpt ) {
			$excerpt = $this->object->post_excerpt;
		} else if ( get_post_meta( $this->object->ID, 'event-summary', true ) ) {
			$excerpt = get_post_meta( $this->object->ID, 'event-summary', true );
		} else {
			$excerpt = $this->get_content();
		}

		$address = get_post_meta( $this->object->ID, 'event-location', true );
		$start_time = get_post_meta( $this->object->ID, 'event-start-date', true );
		$datetime_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$start_time_string = wp_date( $datetime_format, $start_time );
		$summary = "📍 {$address}\n📅 {$start_time_string}\n\n{$excerpt}";
		return $summary;
	}

	/**
	 * Transform the WordPress Object into an ActivityPub Object.
	 *
	 * @return Activitypub\Activity\Event
	 */
	public function to_object() {
		$object = new Event();
		$object = $this->transform_object_properties( $object );

		// Set hardcoded values/one-liners that don't have a get(ter) function defined.
		return $object
			->set_comments_enabled( true )
			->set_external_participation_url( $this->get_url() )
			->set_status( 'CONFIRMED' )
			->set_name( get_the_title( $this->object->ID ) )
			->set_timezone( $object->get_locale )
			->set_is_online( false )
			->set_in_language( $this->get_locale() )
			->set_actor( get_rest_url_by_path( 'application' ) )
			->set_to( array( 'https://www.w3.org/ns/activitystreams#Public' ) );
	}
}
