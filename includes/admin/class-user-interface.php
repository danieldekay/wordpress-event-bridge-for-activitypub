<?php
/**
 * Class responsible for User Interface additions in the Admin UI.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Class responsible for Event Plugin related admin notices.
 *
 * Notices for guiding to proper configuration of ActivityPub with event plugins.
 *
 * @since 1.0.0
 */
class User_Interface {
	/**
	 * Init.
	 */
	public static function init() {
		\add_filter( 'page_row_actions', array( self::class, 'row_actions' ), 10, 2 );
		\add_filter( 'post_row_actions', array( self::class, 'row_actions' ), 10, 2 );
		\add_action(
			'admin_init',
			\add_filter(
				'map_meta_cap',
				array( self::class, 'disable_editing_for_external_events' ),
				10,
				4
			)
		);
	}

	/**
	 * Add an column that shows the origin of an external event.
	 *
	 * @param array $columns The current columns.
	 * @return array
	 */
	public static function add_origin_column( $columns ) {
		// Add a new column after the title column.
		$columns['activitypub_origin'] = __( 'ActivityPub origin', 'event-bridge-for-activitypub' );
		return $columns;
	}

	/**
	 * Add a "⁂ Preview" link to the row actions.
	 *
	 * @param array    $actions The existing actions.
	 * @param \WP_Post $post    The post object.
	 *
	 * @return array The modified actions.
	 */
	public static function row_actions( $actions, $post ) {
		// check if the post is enabled for ActivityPub.
		if ( ! self::post_is_external_event_post( $post ) ) {
			return $actions;
		}

		$actions['view_origin'] = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			\esc_url( $post->guid ),
			\esc_html__( 'Open original page', 'event-bridge-for-activitypub' )
		);

		return $actions;
	}

	/**
	 * Check if a post is both an event post and external (from ActivityPub federation).
	 *
	 * @param WP_Post $post The post.
	 * @return bool
	 */
	private static function post_is_external_event_post( $post ) {
		if ( 'gatherpress_event' !== $post->post_type ) {
			return false;
		}
		return str_starts_with( $post->guid, 'https://ga.lan' ) ? true : false;
	}

	/**
	 * Modify the user capabilities so that nobody can edit external events.
	 *
	 * @param array $caps     Concerned user's capabilities.
	 * @param array $cap      Required primitive capabilities for the requested capability.
	 * @param array $user_id  The WordPress user ID.
	 * @param array $args     Additional args.
	 *
	 * @return array
	 */
	public static function disable_editing_for_external_events( $caps, $cap, $user_id, $args ) {
		if ( 'edit_post' === $cap && isset( $args[0] ) ) {
			$post_id = $args[0];
			$post    = get_post( $post_id );
			if ( $post && self::post_is_external_event_post( $post ) ) {
				// Deny editing by returning 'do_not_allow'.
				return array( 'do_not_allow' );
			}
		}
		return $caps;
	}
}
