<?php
/**
 * Class responsible for registering handlers for incoming activities to the ActivityPub plugin.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\ActivityPub;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Event_Bridge_For_ActivityPub\ActivityPub\Handler\Accept;
use Event_Bridge_For_ActivityPub\ActivityPub\Handler\Announce;
use Event_Bridge_For_ActivityPub\ActivityPub\Handler\Update;
use Event_Bridge_For_ActivityPub\ActivityPub\Handler\Create;
use Event_Bridge_For_ActivityPub\ActivityPub\Handler\Delete;

/**
 *  Class responsible for registering handlers for incoming activities to the ActivityPub plugin.
 */
class Handler {
	/**
	 * Register all ActivityPub handlers.
	 */
	public static function register_handlers() {
		Accept::init();
		Announce::init();
		Update::init();
		Create::init();
		Delete::init();
	}
}
