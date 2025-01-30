<?php
/**
 * Extending the Tribe Events API to allow setting of the guid.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub\Transmogrifier\Helper;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Extending the Tribe Events API to allow setting of the guid.
 *
 * @since 1.0.0
 */
class The_Events_Calendar_Event_Repository extends \Tribe__Events__Repositories__Event {
	/**
	 * Override diff: allow setting of guid.
	 *
	 * @var  array An array of keys that cannot be updated on this repository.
	 */
	protected static $blocked_keys = array(
		'ID',
		'post_type',
		'comment_count',
	);

	/**
	 * Whether the current key can be updated by this repository or not.
	 *
	 * @since 4.7.19
	 *
	 * @param string $key The key.
	 * @return bool
	 */
	protected function can_be_updated( $key ): bool {
		return ! in_array( $key, self::$blocked_keys, true );
	}
}
