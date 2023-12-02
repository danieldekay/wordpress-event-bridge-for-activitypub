<?php
/**
 * Event is an implementation of one of the
 * Activity Streams Event object type
 *
 * @package activity-event-transformers
 */

/**
 * Event is an implementation of one of the
 * Activity Streams Event object type
 *
 * The Object is the primary base type for the Activity Streams
 * vocabulary.
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-event
 */
class Place extends \Activitypub\Activity\Base_Object {
	/**
	 * Place is an implementation of one of the
	 * Activity Streams
	 *
	 * @var string
	 */
	protected $type = 'Place';

	/**
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-accuracy
	 * @var float
	 */
	protected $accuracy;

	/**
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-altitude
	 * @var float
	 */
	protected $altitude;

	/**
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-latitude
	 * @var float
	 */
	protected $latitude;

	/**
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-longitude
	 * @var float
	 */
	protected $longitude;

	/**
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-radius
	 * @var float
	 */
	protected $radius;

	/**
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-units
	 * @var string
	 */
	protected $units;

	/**
	 * Extension invented by PeerTube whether comments/replies are <enabled>
	 * Mobilizon also implemented this as a fallback to their own
	 * repliesModerationOption.
	 *
	 * @see https://docs.joinpeertube.org/api/activitypub#video
	 * @see https://docs.joinmobilizon.org/contribute/activity_pub/
	 *
	 * @var bool
	 */
	protected $comments_enabled;

    /**
     * @var Postal_Address|string
     */
    protected $address;
}
