<?php
/**
 * ActivityPub Transmogrify for the GatherPress event plugin.
 *
 * Handles converting incoming external ActivityPub events to GatherPress Events.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Activitypub\Transmogrify;

use Activitypub\Activity\Extended_Object\Event;
use Activitypub\Activity\Extended_Object\Place;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use GatherPress\Core\Event as GatherPress_Event;

/**
 * ActivityPub Transmogrify for the GatherPress event plugin.
 *
 * Handles converting incoming external ActivityPub events to GatherPress Events.
 *
 * @since 1.0.0
 */
class GatherPress {
	/**
	 * The current GatherPress Event object.
	 *
	 * @var Event
	 */
	protected $activitypub_event;

	/**
	 * Extend the constructor, to also set the GatherPress objects.
	 *
	 * This is a special class object form The Events Calendar which
	 * has a lot of useful functions, we make use of our getter functions.
	 *
	 * @param array $activitypub_event The ActivityPub Event as associative array.
	 */
	public function __construct( $activitypub_event ) {
		$activitypub_event = Event::init_from_array( $activitypub_event );

		if ( is_wp_error( $activitypub_event ) ) {
			return;
		}

		$this->activitypub_event = $activitypub_event;
	}

	/**
	 * Save the ActivityPub event object as GatherPress Event.
	 */
	public function save() {
		// Insert GatherPress Event here.
	}
}
