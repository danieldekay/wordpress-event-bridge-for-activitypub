<?php
/**
 * Replace the default ActivityPub Transformer
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

namespace Activitypub_Event_Extensions\Activitypub\Transformer;

use Activitypub\Transformer\Post;

/**
 * Base transformer for WordPress event post types to ActivityPub events.
 */
class Event extends Post {
}
