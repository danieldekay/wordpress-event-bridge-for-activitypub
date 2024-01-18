<?php
/**
 * ActivityPub Transformer for the plugin Very Simple Event List.
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

use EM_Event;

use Activitypub\Activity\Extended_Object\Event;
use Activitypub\Activity\Extended_Object\Place;
use Activitypub\Transformer\Post;

use function Activitypub\esc_hashtag;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ActivityPub Transformer for events from the WordPress plugin 'Events Manager'
 *
 * @see https://wordpress.org/plugins/events-manager/
 *
 * @since 1.0.0
 */
class Events_Manager extends Post {
	/**
	 * Holds the EM_Event object.
	 *
	 * @var EM_Event
	 */
	protected $em_event;

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
		return 'activitypub-event-transformers/events-manager';
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
		return 'Events Manager';
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
		return array();
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

	protected function get_is_online() {
		return 'url' === $this->em_event->event_location_type;
	}

	/**
	 * Get the event location.
	 *
	 * @param int $post_id The WordPress post ID.
	 * @return array The Place.
	 */
	public function get_location() {
		if ( 'url' === $this->em_event->event_location_type ) {
			return null;
		}

		$location = new Place();
		$em_location = $this->em_event->get_location();

		$location->set_name( $em_location->location_name );

		$address = array(
			'type' => 'PostalAddress',
			'addressCountry' => $em_location->location_country,
			'addressLocality' => $em_location->location_town,
			'streetAddress' => $em_location->location_address,
			'name' => $em_location->location_name,
		);
		if ( $em_location->location_state ) {
			$address['addressRegion'] = $em_location->location_state;
		}
		if ( $em_location->location_postcode ) {
			$address['postalCode'] = $em_location->location_postcode;
		}

		$location->set_address( $address );
		return $location;
	}


	/**
	 * Get the end time from the events metadata.
	 */
	protected function get_end_time() {
		return null;
	}

	/**
	 * Get the end time from the events metadata.
	 */
	protected function get_start_time() {
		$date_string = $this->em_event->event_start_date;
		$time_string = $this->em_event->event_start_time;
		$timezone_string = $this->em_event->event_timezone;

		// Create a DateTime object with the given date, time, and timezone
		$datetime = new DateTime( $date_string . ' ' . $time_string, new DateTimeZone( $timezone_string ) );

		// Set the timezone for proper formatting
		$datetime->setTimezone( new DateTimeZone( 'UTC' ) );

		// Format the DateTime object as 'Y-m-d\TH:i:s\Z'
		$formatted_date = $datetime->format( 'Y-m-d\TH:i:s\Z' );
		return $formatted_date;
	}

	protected function get_maximum_attendee_capacity() {
		return $this->em_event->event_spaces;
	}

	/**
	 * @todo decide whether to include pending bookings or not!
	 */
	protected function get_remaining_attendee_capacity() {
		$em_bookings = $this->em_event->get_bookings()->get_bookings();
		$remaining_attendee_capacity = $this->em_event->event_spaces - count( $em_bookings->bookings );
		return $remaining_attendee_capacity;
	}

	protected function get_participant_count() {
		$em_bookings = $this->em_event->get_bookings()->get_bookings();
		return count( $em_bookings->bookings );
	}

	protected function get_content() {
		return $this->wp_object->post_content;
	}

	protected function get_summary() {
		if ( $this->em_event->post_excerpt ) {
			$excerpt = $this->em_event->post_excerpt;
		} else {
			$excerpt = $this->get_content();
		}
		$address = $this->em_event->get_location()->location_name;
		$start_time = strtotime( $this->get_start_time() );
		$datetime_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$start_time_string = wp_date( $datetime_format, $start_time );
		$summary = "📍 {$address}\n📅 {$start_time_string}\n\n{$excerpt}";
		return $summary;
	}

	// protected function get_join_mode() {
	//  return 'free';
	// }

	private function get_event_link_attachment() {
		$event_link_url = $this->em_event->event_location->data['url'];
		$event_link_text = $this->em_event->event_location->data['text'];
		return array(
			'type' => 'Link',
			'name' => 'Website',
			// 'name' => $event_link_text,
			'href' => \esc_url( $event_link_url ),
			'mediaType' => 'text/html',
		);
	}

	/**
	 * Overrides/extends the get_attachments function to also add the event Link.
	 */
	protected function get_attachment() {
		// Get attachments via parent function
		$attachments = parent::get_attachment();

		// The first attachment is the featured image, make sure it is compatible with Mobilizon.
		if ( count( $attachments ) ) {
			$attachments[0]['type'] = 'Document';
			$attachments[0]['name'] = 'Banner';
		}

		if ( 'url' === $this->em_event->event_location_type ) {
			$attachments[] = $this->get_event_link_attachment();
		}
		return $attachments;

		return $attachments;
	}

	/**
	 * This function tries to map VS-Event categories to Mobilizon event categories.
	 *
	 * @return string $category
	 */
	protected function get_category() {
		$categories = $this->em_event->get_categories()->terms;

		if ( empty( $categories ) ) {
			return 'MEETING';
		}

		// Prepare an array to store all category information for comparison.
		$category_info = array();

		// Extract relevant category information (name, slug, description) from the categories array.
		foreach ( $categories as $category ) {
			$category_info[] = strtolower( $category->name );
			$category_info[] = strtolower( $category->slug );
			$category_info[] = strtolower( $category->description );
		}

		// Convert mobilizon categories to lowercase for case-insensitive comparison.
		$mobilizon_categories = array_map( 'strtolower', Event::DEFAULT_EVENT_CATEGORIES );

		// Initialize variables to track the best match.
		$best_mobilizon_category_match = '';
		$best_match_length = 0;

		// Check for the best match.
		foreach ( $mobilizon_categories as $mobilizon_category ) {
			foreach ( $category_info as $category ) {
				foreach ( explode( '_', $mobilizon_category ) as $mobilizon_category_slice ) {
					if ( stripos( $category, $mobilizon_category_slice ) !== false ) {
						// Check if the current match is longer than the previous best match.
						$current_match_legnth = strlen( $mobilizon_category_slice );
						if ( $current_match_legnth > $best_match_length ) {
							$best_mobilizon_category_match = $mobilizon_category;
							$best_match_length = $current_match_legnth;
						}
					}
				}
			}
		}

		return ( '' != $best_mobilizon_category_match ) ? strtoupper( $best_mobilizon_category_match ) : 'MEETING';
	}

	protected function get_tag() {
		// The parent tag function also fetches the mentions.
		$tags = parent::get_tag();

		$post_tags = \wp_get_post_terms( $this->wp_object->ID, 'event-tags' );

		if ( $post_tags ) {
			foreach ( $post_tags as $post_tag ) {
				$tag = array(
					'type' => 'Hashtag',
					'href' => \esc_url( \get_tag_link( $post_tag->term_id ) ),
					'name' => esc_hashtag( $post_tag->name ),
				);
				$tags[] = $tag;
			}
		}
		return $tags;
	}

	protected function get_name() {
		return $this->em_event->event_name;
	}

	/**
	 * Transform the WordPress Object into an ActivityPub Object.
	 *
	 * @return Activitypub\Activity\Event
	 */
	public function to_object() {
		$this->em_event = new EM_Event( $this->wp_object->ID, 'post_id' );
		$activitypub_object = new Event();

		$activitypub_object = $this->transform_object_properties( $activitypub_object );

		$activitypub_object->set_external_participation_url( $this->get_url() );

		return $activitypub_object;
	}
}
