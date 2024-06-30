<?php
/**
 * Replace the default ActivityPub Transformer
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

namespace Activitypub_Event_Extensions\Activitypub\Transformer;

use Activitypub\Model\Blog;
use Activitypub\Transformer\Post;

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
}
