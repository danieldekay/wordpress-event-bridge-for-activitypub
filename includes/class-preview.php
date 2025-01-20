<?php
/**
 * Class file initializing the custom ActivityPub preview.
 *
 * @package Event_Bridge_For_ActivityPub
 * @license AGPL-3.0-or-later
 * @since 1.0.0
 */

namespace Event_Bridge_For_ActivityPub;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Class for initializing the custom ActivityPub preview(s).
 */
class Preview {
	/**
	 * Init functions to hook into the ActivityPub plugin.
	 */
	public static function init() {
		\add_filter( 'activitypub_preview_template', array( self::class, 'maybe_apply_event_preview_template' ), 10, 0 );
	}

	/**
	 * Maybe apply a custom preview template if the post type is an event post type of a supported event plugin.
	 *
	 * @return string The full path for the ActivityPub preview template to use.
	 */
	public static function maybe_apply_event_preview_template() {
		$event_post_types = Setup::get_instance()->get_active_event_plugins_post_types();

		if ( in_array( \get_post_type(), $event_post_types, true ) ) {
			return EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . '/templates/event-preview.php';
		}

		return ACTIVITYPUB_PLUGIN_DIR . '/templates/post-preview.php';
	}
}
