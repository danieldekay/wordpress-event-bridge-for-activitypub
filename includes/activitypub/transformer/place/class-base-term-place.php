<?php
/**
 * Class file a base `Place` transformer where the place/location/venue is stored in a WordPress term.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Place;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place as Place_Object;
use Activitypub\Transformer\Base;

/**
 * Class for a base `Place` transformer where the place/location/venue is stored in a WordPress term.
 *
 * @method array|string get_address()
 *
 * @since 1.0.0
 */
abstract class Base_Term_Place extends Base {
	/**
	 * Set the type of the object.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'Place';
	}

	/**
	 * Get the WordPress term ID of the current Transformers item.
	 *
	 * @return int
	 */
	public function get__id() {
		return $this->item->term_id;
	}

	/**
	 * Get the ActivityPub ID of the term.
	 *
	 * @return string
	 */
	public function get_url() {
		return \get_term_link( $this->item );
	}

	/**
	 * Returns the ID of the Post.
	 *
	 * @return string The Posts ID.
	 */
	public function get_id() {
		return \add_query_arg( $this->item->taxonomy, $this->item->slug, \trailingslashit( \home_url() ) );
	}

	/**
	 * Don't set sensitive per default.
	 *
	 * @return null
	 */
	public function get_sensitive() {
		return null;
	}

	/**
	 * Don't set sensitive per default.
	 *
	 * @return null
	 */
	public function get_content() {
		return null;
	}

	/**
	 * Returns the name for the ActivityPub Item which is the title of the term.
	 *
	 * @return string|null The title or null if the object type is `note`.
	 */
	protected function get_name() {
		if ( isset( $this->item->name ) && $this->item instanceof \WP_Term ) {
			return \wp_strip_all_tags(
				\html_entity_decode(
					$this->item->name
				)
			);
		}

		return null;
	}

	/**
	 * Generic function that converts an WordPress location object to an ActivityPub-Place object.
	 *
	 * @return Place_Object|\WP_Error
	 */
	public function to_object() {
		$activitypub_object = new Place_Object();

		$activitypub_object->set_type( $this->get_type() );
		$activitypub_object->set_id( $this->get_id() );
		$activitypub_object->set_name( $this->get_name() );
		$activitypub_object->set_content( $this->get_content() );

		$address = $this->get_address();

		if ( $address ) {
			$activitypub_object->set_address( $address );
		}

		return $activitypub_object;
	}

	/**
	 * Don't set a media type on Place per default.
	 *
	 * @return null
	 * @phpstan-ignore-next-line
	 */
	public function get_media_type() {
		return null;
	}

	/**
	 * Don't support replies for Place per default.
	 *
	 * @return null
	 * @phpstan-ignore-next-line
	 */
	public function get_replies() {
		return null;
	}

	/**
	 * Don't support tags for Place per default.
	 *
	 * @return null
	 * @phpstan-ignore-next-line
	 */
	protected function get_tag() {
		return null;
	}

	/**
	 * Don't set attrbuted to per default.
	 *
	 * @return null The attributed to.
	 * @phpstan-ignore-next-line
	 */
	protected function get_attributed_to() {
		return null;
	}
}
