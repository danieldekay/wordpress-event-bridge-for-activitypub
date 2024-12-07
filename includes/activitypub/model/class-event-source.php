<?php
/**
 * Event-Source (=ActivityPub Actor that is followed) model.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Model;

use Activitypub\Activity\Actor;
use Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources;
use WP_Error;

/**
 * Event-Source (=ActivityPub Actor that is followed) model.
 */
class Event_Source extends Actor {
	/**
	 * Get the Icon URL (Avatar).
	 *
	 * @return string The URL to the Avatar.
	 */
	public function get_icon_url() {
		$icon = $this->get_icon();

		if ( ! $icon ) {
			return '';
		}

		if ( is_array( $icon ) ) {
			return $icon['url'];
		}

		return $icon;
	}

	/**
	 * Convert a Custom-Post-Type input to an \Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source.
	 *
	 * @param \WP_Post $post The post object.
	 * @return \Event_Bridge_For_ActivityPub\ActivityPub\Event_Source|WP_Error
	 */
	public static function init_from_cpt( $post ) {
		if ( Event_Sources::POST_TYPE !== $post->post_type ) {
			return false;
		}
		$actor_json = get_post_meta( $post->ID, 'activitypub_actor_json', true );
		$object     = self::init_from_json( $actor_json );
		$object->set__id( $post->ID );
		$object->set_id( $post->guid );
		$object->set_name( $post->post_title );
		$object->set_summary( $post->post_excerpt );
		$object->set_published( gmdate( 'Y-m-d H:i:s', strtotime( $post->post_date ) ) );
		$object->set_updated( gmdate( 'Y-m-d H:i:s', strtotime( $post->post_modified ) ) );

		return $object;
	}
}
