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

use Activitypub\Activity\Extended_Object\Place as Place_Object;
use Activitypub\Transformer\Post;

/**
 * Class for the ActivityPub transformer of the venues of The Events Calendar to `as:Place`.
 *
 * @method array|string get_address()
 *
 * @since 1.0.0
 */
abstract class Place extends Post {
	/**
	 * Set the type of the object.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'Place';
	}

	/**
	 * Set the type of the object.
	 *
	 * @return ?array
	 */
	public function get_replies() {
		return null;
	}

	/**
	 * Set the type of the object.
	 *
	 * @return ?string
	 */
	public function get_sensitive() {
		return null;
	}

	/**
	 * Null content to prevent registering and unregistering ActivityPub shortcodes in parent function.
	 *
	 * @return ?string
	 */
	public function get_content() {
		return null;
	}

	/**
	 * Completely remove attachments.
	 *
	 * @return ?array
	 */
	public function get_attachment() {
		return null;
	}

	/**
	 * Completely remove summary.
	 *
	 * @return ?string
	 */
	public function get_summary() {
		return null;
	}

	/**
	 * Completely remove tag.
	 *
	 * @return ?array
	 */
	public function get_tag() {
		return null;
	}


	/**
	 * Completely media type.
	 *
	 * @return ?string
	 */
	public function get_media_type() {
		return null;
	}

	/**
	 * Generic function that converts an WordPress location object to an ActivityPub-Place object.
	 *
	 * @param bool $full_object bool Return an object with all properties set, or a minimal one as used within an `as:Event`s location.
	 * @return Place_Object|\WP_Error
	 */
	public function to_object( $full_object = true ) {
		$activitypub_object = new Place_Object();
		$activitypub_object = $this->transform_object_properties( $activitypub_object );

		if ( \is_wp_error( $activitypub_object ) ) {
			return $activitypub_object;
		}

		if ( ! empty( $activitypub_object->get_content() ) ) {
			$activitypub_object->set_content_map(
				array(
					$this->get_locale() => $this->get_content(),
				)
			);
		}

		$updated = \strtotime( $this->item->post_modified_gmt );

		$activitypub_object->set_updated( \gmdate( 'Y-m-d\TH:i:s\Z', $updated ) );

		if ( $full_object ) {
			$published = \strtotime( $this->item->post_date_gmt );

			$activitypub_object->set_published( \gmdate( 'Y-m-d\TH:i:s\Z', $published ) );

			$activitypub_object->set_to(
				array(
					'https://www.w3.org/ns/activitystreams#Public',
					$this->get_actor_object()->get_followers(),
				)
			);
		}

		$address = $this->get_address();

		if ( $address ) {
			$activitypub_object->set_address( $address );
		}

		// @phpstan-ignore-next-line
		return $activitypub_object;
	}
}
