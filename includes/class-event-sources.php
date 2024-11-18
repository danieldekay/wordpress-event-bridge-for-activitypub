<?php
/**
 * Class for handling and saving the ActivityPub event sources (i.e. follows).
 *
 * @package ActivityPub_Event_Bridge
 */

namespace ActivityPub_Event_Bridge;

use Activitypub\Http;
use Exception;

use function register_post_type;

/**
 * Class for handling and saving the ActivityPub event sources (i.e. follows).
 *
 * @package ActivityPub_Event_Bridge
 */
class Event_Sources {
	/**
	 * The custom post type.
	 */
	const POST_TYPE = 'activitypub_event_bridge_follow';

	/**
	 * Register the post type used to store the external event sources (i.e., followed ActivityPub actors).
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'           => array(
					'name'          => _x( 'Event Sources', 'post_type plural name', 'activitypub' ),
					'singular_name' => _x( 'Event Source', 'post_type single name', 'activitypub' ),
				),
				'public'           => false,
				'hierarchical'     => false,
				'rewrite'          => false,
				'query_var'        => false,
				'delete_with_user' => false,
				'can_export'       => true,
				'supports'         => array(),
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'activitypub_inbox',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_url',
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'activitypub_errors',
			array(
				'type'              => 'string',
				'single'            => false,
				'sanitize_callback' => function ( $value ) {
					if ( ! is_string( $value ) ) {
						throw new Exception( 'Error message is no valid string' );
					}

					return esc_sql( $value );
				},
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'activitypub_user_id',
			array(
				'type'              => 'string',
				'single'            => false,
				'sanitize_callback' => function ( $value ) {
					return esc_sql( $value );
				},
			)
		);

		\register_post_meta(
			self::POST_TYPE,
			'activitypub_actor_json',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => function ( $value ) {
					return sanitize_text_field( $value );
				},
			)
		);
	}
}
