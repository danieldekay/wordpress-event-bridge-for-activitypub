<?php
/**
 * Class file for Debug Class.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub;

/**
 * Debug Class.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */
class Debug {
	/**
	 * Initialize the class, registering WordPress hooks.
	 */
	public static function init() {
		if ( defined( 'WP_DEBUG_LOG' ) && constant( 'WP_DEBUG_LOG' ) ) {
			\add_action( 'event_bridge_for_activitypub_write_log', array( self::class, 'write_log' ), 10, 1 );
		}
	}

	/**
	 * Write a log entry.
	 *
	 * @param mixed $log The log entry.
	 */
	public static function write_log( $log ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions
		\error_log( \print_r( $log, true ) );
	}
}