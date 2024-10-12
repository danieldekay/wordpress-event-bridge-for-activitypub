<?php
/**
 * ActivityPub Transformer for Events managed with Eventin.
 *
 * @link https://support.themewinter.com/docs/plugins/docs-category/eventin/
 *
 * @package ActivityPub_Event_Bridge
 * @license AGPL-3.0-or-later
 */

namespace ActivityPub_Event_Bridge\Activitypub\Transformer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Place;
use ActivityPub_Event_Bridge\Activitypub\Transformer\Event;
use Etn\Core\Event\Event_Model;

/**
 * ActivityPub Transformer for Events managed with Eventin.
 *
 * @since 1.0.0
 */
final class Eventin extends Event {

	/**
	 * Holds the EM_Event object.
	 *
	 * @var Event_Model
	 */
	protected $event_model;

	/**
	 * Extend the constructor, to also set the Event Model.
	 *
	 * This is a special class object form The Events Calendar which
	 * has a lot of useful functions, we make use of our getter functions.
	 *
	 * @param WP_Post $wp_object The WordPress object.
	 * @param string  $wp_taxonomy The taxonomy slug of the event post type.
	 */
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object, $wp_taxonomy );
		$this->event_model = new Event_Model( $this->wp_object->ID );
	}

	/**
	 * Get the end time from the event object.
	 */
	protected function get_start_time(): string {
		return \gmdate( 'Y-m-d\TH:i:s\Z', \time() );
	}

	/**
	 * Get status of the tribe event
	 *
	 * @return string status of the event
	 */
	public function get_status(): ?string {
		return 'CONFIRMED';
	}
}
