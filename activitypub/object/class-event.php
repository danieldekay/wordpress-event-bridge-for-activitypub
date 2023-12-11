<?php
/**
 * ActivityPub Object of type Event.
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

use function Activitypub\snake_to_camel_case;
require_once __DIR__ . '/class-place.php';

/**
 * Event is an implementation of one of the Activity Streams Event object type.
 *
 * This class contains extra keys as used by Mobilizon to ensure compatibility.
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-event
 */
class Event extends \Activitypub\Activity\Base_Object {
	// todo maybe rename to mobilizon event?
	const REPLIES_MODERATION_OPTION_TYPES = [ 'allow_all', 'closed' ];
	const JOIN_MODE_TYPES = [ 'free', 'restricted', 'external' ]; // amd 'invite', but not used by mobilizon atm
	const ICAL_EVENT_STATUS_TYPES = ["TENTATIVE", "CONFIRMED", "CANCELLED"];

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
	 * @context https://joinpeertube.org/ns#commentsEnabled
	 * @see https://docs.joinpeertube.org/api/activitypub#video
	 * @see https://docs.joinmobilizon.org/contribute/activity_pub/
	 * @var bool|null
	 */
	protected $comments_enabled;

	/**
	 * @context https://joinmobilizon.org/ns#timezone
	 * @var string
	 */
	protected $timezone;

	/**
	 * @context https://joinmobilizon.org/ns#repliesModerationOption
	 * @see https://docs.joinmobilizon.org/contribute/activity_pub/#repliesmoderation
	 * @var string
	 */
	protected $replies_moderation_option;

	/**
	 * @context https://joinmobilizon.org/ns#anonymousParticipationEnabled
	 * @see https://docs.joinmobilizon.org/contribute/activity_pub/#anonymousparticipationenabled
	 * @var bool
	 */
	protected $anonymous_participation_enabled;

	/**
	 * @context https://schema.org/category
	 * @var enum
	 */
	protected $category;

	/**
	 * @context https://schema.org/inLanguage
	 * @var
	 */
	protected $in_language;

	/**
	 * @context https://joinmobilizon.org/ns#isOnline
	 * @var bool
	 */
	protected $is_online;

	/**
	 * @context http://www.w3.org/2002/12/cal/ical#status
	 * @var enum
	 */
	protected $status;

	/**
	 * @context https://joinmobilizon.org/ns#externalParticipationUrl
	 * @var string
	 */
	protected $external_participation_url;

	/**
	 * @context https://joinmobilizon.org/ns#joinMode
	 * @see https://docs.joinmobilizon.org/contribute/activity_pub/#joinmode
	 * @var
	 */
	protected $join_mode;

	/**
	 * @context https://joinmobilizon.org/ns#participantCount
	 * @var int
	 */
	protected $participant_count;

	/**
	 * @context https://schema.org/maximumAttendeeCapacity
	 * @see https://docs.joinmobilizon.org/contribute/activity_pub/#maximumattendeecapacity
	 * @var int
	 */
	protected $maximum_attendee_capacity;


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

			if ( ! empty( $matches[1] ) ) {
				return $matches[1];
			} else {
				return 'https://www.w3.org/ns/activitystreams';
			}
		}

		return null;
	}

	private static function compact_context( $key_context, $namespace, $abbreviation ) {
		$abbreviation_added = false;
		foreach ( $key_context as $key => $value ) {
			// Check if the key starts with the namespace
			if ( strpos( $value, $namespace ) === 0 ) {
				// Replace the key
				$key_context[ $key ] = $abbreviation . ':' . substr( $value, strlen( $namespace ) );

				// Add abbreviation element for the namespace only once
				if ( ! $abbreviation_added ) {
					$key_context = [ $abbreviation => $namespace ] + $key_context;
					$abbreviation_added = true;
				}
			}
		}
		return $key_context;
	}

	public static function get_context() {
		$class = self::class;
		$transient = "activitypub_json_context_object_{$class}";
		$context = get_transient( $transient );
		// if ( $context ) {
		//  return $context;
		// }
		$reflection_class = new ReflectionClass( self::class );
		$context = array(
			'https://www.w3.org/ns/activitystreams',
			'https://w3id.org/security/v1',
		);

		$key_context = [];

		foreach ( $reflection_class->getProperties() as $property ) {
			$doc_omment = $property->getDocComment();

			// Extract context information from the doc comment.
			preg_match( '/@context\s+([^\s]+)/', $doc_omment, $matches );

			if ( ! empty( $matches[1] ) ) {
				$key_context[ snake_to_camel_case( $property->name ) ] = $matches[1];
			}
		}

		$namespace_abbreviations = array(
			'https://joinpeertube.org/ns#'        => 'pt',
			'https://joinmobilizon.org/ns#'       => 'mz',
			'https://schema.org/'                 => 'sc',
			'http://www.w3.org/2002/12/cal/ical#' => 'ical',
		);

		foreach ( $namespace_abbreviations as $namespace => $abbreviation ) {
			$key_context = self::compact_context( $key_context, $namespace, $abbreviation );
		}

		$context[] = $key_context;

		set_transient( $transient, $context );
		return $context;
	}


	/**
	 * When using this class we need to add some filters.
	 */
	public function __construct() {
		// $class = strtolower( get_class( $this ) );
		// add_filter( "activitypub_activity_{$class}_object_array", [ $this, 'filter_object_array' ] );
		// add_filter( 'activitypub_json_context', [ $this, 'filter_json_context' ] );
	}
}
