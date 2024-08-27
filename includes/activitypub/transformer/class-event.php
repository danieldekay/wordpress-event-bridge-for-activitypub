<?php
/**
 * Replace the default ActivityPub Transformer
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

namespace Activitypub_Event_Extensions\Activitypub\Transformer;

use Activitypub\Activity\Extended_Object\Event as Event_Object;
use Activitypub\Model\Blog;
use Activitypub\Transformer\Post;

use function Activitypub\get_rest_url_by_path;

/**
 * Base transformer for WordPress event post types to ActivityPub events.
 */
class Event extends Post {
	/**
	 * Returns the User-URL of the Author of the Post.
	 *
	 * If `single_user` mode is enabled, the URL of the Blog-User is returned.
	 *
	 * @return string The User-URL.
	 */
	protected function get_attributed_to() {
		$blog = new Blog();
		return $blog->get_id();
	}

	/**
	 * Returns the ActivityStreams 2.0 Object-Type for an Event.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-event
	 *
	 * @return string The Event Object-Type.
	 */
	protected function get_object_type() {
		return 'Event';
	}

	/**
	 * Format a human readable HTML summary.
	 *
	 * @param string $summary_text The base string to be formatted.
	 *
	 * @return string
	 */
	protected function format_html_summary( $summary_text ): string {
		return $summary_text;
	}

	/**
	 * Generic function that converts an WP-Event object to an ActivityPub-Event object.
	 *
	 * @return Event_Object
	 */
	public function to_object() {
		$activitypub_object = new Event_Object();
		$activitypub_object = $this->transform_object_properties( $activitypub_object );

		$published = \strtotime( $this->wp_object->post_date_gmt );

		$activitypub_object->set_published( \gmdate( 'Y-m-d\TH:i:s\Z', $published ) );

		$updated = \strtotime( $this->wp_object->post_modified_gmt );

		if ( $updated > $published ) {
			$activitypub_object->set_updated( \gmdate( 'Y-m-d\TH:i:s\Z', $updated ) );
		}

		$activitypub_object->set_content_map(
			array(
				$this->get_locale() => $this->get_content(),
			)
		);
		$path = sprintf( 'actors/%d/followers', intval( $this->wp_object->post_author ) );

		$activitypub_object->set_to(
			array(
				'https://www.w3.org/ns/activitystreams#Public',
				get_rest_url_by_path( $path ),
			)
		);

		return $activitypub_object;
	}
}
