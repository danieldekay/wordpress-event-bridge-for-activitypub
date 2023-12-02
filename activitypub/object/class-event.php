<?php
/**
 * ActivityPub Transformer for VS Event.
 *
 * This is a file doc comments.
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
class Event extends \Activitypub\Activity\Base_Object {
	/**
	 * Event is an implementation of one of the
	 * Activity Streams
	 *
	 * @var string
	 */
	protected $type = 'Event';

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
	 * @var string
	 */
	protected $timezone;

	/**
	 * @context https://joinmobilizon/repliesModerationOption
	 * @var enum
	 */
	protected $replies_moderation_option;

	/**
	 * @var bool
	 */
	protected $anonymous_participation_enabled;

	/**
	 * @var enum
	 */
	protected $category;

	/**
	 * @var
	 */
	protected $in_language;

	/**
	 * @var bool
	 */
	protected $is_online;

	/**
	 * @var enum
	 */
	protected $ical_status;

	/**
	 * @var string
	 */
	protected $external_participation_url;

	/**
	 * @var enum
	 */
	protected $join_mode;

	/**
	 * @var bool
	 */
	protected $draft;

	/**
	 * @var int
	 */
	protected $participant_count;

	/**
	 * @var int
	 */
	protected $maximum_attendee_capacity;

	/**
	 * @param array $array The array version of an object of this class.
	 */
	private function rename_ical_status_key( $array ) {
		$array[ 'ical:status' ] = $array[ 'icalStatus' ];
		unset( $array[ 'icalStatus' ] );
	}

	/**
	 * @param array $array The array version of an object of this class.
	 */
	public function rename_array_keys( $array ) {
		if ( isset( $array[ 'icalStatus' ] ) ) {
			$array = rename_ical_status_key( $array );
		}
		return $array;
	}

	 /**
     * Get the context information for a property.
     *
     * @param string $property
     *
     * @return array|null
     */
    private function get_property_context( string $property ) {
        $reflection_class = new ReflectionClass( $this );

        if ( $reflection_class->hasProperty( $property ) ) {
            $reflection_property = $reflection_class->getProperty( $property );
            $doc_omment = $reflection_property->getDocComment();

            // Extract context information from the doc comment.
            preg_match( '/@context\s+([^\s]+)/', $doc_omment, $matches );

            if ( !empty( $matches[1] ) ) {
                return $matches[1];
            } else {
				return 'https://www.w3.org/ns/activitystreams';
			}
        }

        return null;
    }

	public function filter_context( $context ) {
		if ( isset( $this->replies_moderation_option ) ) {
			$replies_moderation_option_context = $this->get_property_context( 'replies_moderation_option' );
		}
		return $context;
	}


	/**
	 * When using this class we need to add some filters.
	 */
	public function __construct() {
		$class = get_class( $this );
		$class = 'event';
		add_filter( "activitypub_activity_{$class}_object_array", [ $this, 'rename_array_keys' ] );
		add_filter( 'activitypub_json_context', [ $this, 'filter_context' ] );
	}
}
