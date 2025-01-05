<?php
/**
 * Event Sources Table-Class file.
 *
 * This table display the event sources (=followed ActivityPub actors) that are used for
 * importing (caching and displaying) remote events to the WordPress site.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub\Table;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use WP_List_Table;
use Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources as Event_Sources_Collection;
use Event_Bridge_For_ActivityPub\ActivityPub\Model\Event_Source;

use function Activitypub\object_to_uri;

if ( ! \class_exists( '\WP_List_Table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Event Sources Table-Class.
 */
class Event_Sources extends WP_List_Table {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => \__( 'Event Source', 'event-bridge-for-activitypub' ),
				'plural'   => \__( 'Event Sources', 'event-bridge-for-activitypub' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'        => '<input type="checkbox" />',
			'icon'      => \__( 'Icon', 'event-bridge-for-activitypub' ),
			'name'      => \__( 'Name', 'event-bridge-for-activitypub' ),
			'accepted'  => \__( 'Follow', 'event-bridge-for-activitypub' ),
			'url'       => \__( 'URL', 'event-bridge-for-activitypub' ),
			'published' => \__( 'Followed', 'event-bridge-for-activitypub' ),
			'modified'  => \__( 'Last updated', 'event-bridge-for-activitypub' ),
		);
	}

	/**
	 * Returns sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'name'      => array( 'name', true ),
			'modified'  => array( 'modified', false ),
			'published' => array( 'published', false ),
		);
	}

	/**
	 * Prepare items.
	 */
	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden  = array();

		$this->process_action();
		$this->_column_headers = array( $columns, $hidden, $this->get_sortable_columns() );

		$page_num = $this->get_pagenum();
		$per_page = 20;

		$args = array();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['orderby'] ) ) {
			$args['orderby'] = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
		}

		if ( isset( $_GET['order'] ) ) {
			$args['order'] = sanitize_text_field( wp_unslash( $_GET['order'] ) );
		}

		if ( isset( $_GET['s'] ) && isset( $_REQUEST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
			if ( wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
				$args['s'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$event_sources = Event_Sources_Collection::get_event_sources_with_count( $per_page, $page_num, $args );
		$total_count   = $event_sources['total'];

		$this->items = array();
		$this->set_pagination_args(
			array(
				'total_items' => $total_count,
				'total_pages' => ceil( $total_count / $per_page ),
				'per_page'    => $per_page,
			)
		);

		foreach ( $event_sources['actors'] as $event_source_post_id => $event_source_activitypub_id ) {
			$event_source = Event_Source::get_by_id( $event_source_activitypub_id );

			if ( \is_wp_error( $event_source ) ) {
				continue;
			}

			$item = array(
				'icon'       => esc_attr( $event_source->get_icon_url() ),
				'name'       => esc_attr( $event_source->get_name() ),
				'url'        => esc_attr( $event_source_activitypub_id ),
				'accepted'   => esc_attr( get_post_meta( $event_source->get__id(), '_event_bridge_for_activitypub_accept_of_follow', true ) ),
				'identifier' => esc_attr( $event_source->get_id() ),
				'published'  => esc_attr( $event_source->get_published() ),
				'modified'   => esc_attr( $event_source->get_updated() ),
			);

			$this->items[] = $item;
		}
	}

	/**
	 * Returns bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'event-bridge-for-activitypub' ),
		);
	}

	/**
	 * Column default.
	 *
	 * @param array  $item        Item.
	 * @param string $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		if ( ! array_key_exists( $column_name, $item ) ) {
			return __( 'None', 'event-bridge-for-activitypub' );
		}
		return $item[ $column_name ];
	}

	/**
	 * Column avatar.
	 *
	 * @param array $item Item.
	 * @return string
	 */
	public function column_icon( $item ) {
		return sprintf(
			'<img src="%s" width="25px;" />',
			$item['icon']
		);
	}

	/**
	 * Column url.
	 *
	 * @param array $item Item.
	 * @return string
	 */
	public function column_url( $item ) {
		return sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( $item['url'] ),
			$item['url']
		);
	}

	/**
	 * Column cb.
	 *
	 * @param array $item Item.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="event_sources[]" value="%s" />', esc_attr( $item['identifier'] ) );
	}

	/**
	 * Column action.
	 *
	 * @param array $item Item.
	 * @return string
	 */
	public function column_accepted( $item ) {
		if ( $item['accepted'] ) {
			return esc_html__( 'Accepted', 'event-bridge-for-activitypub' );
		} else {
			return esc_html__( 'Pending', 'event-bridge-for-activitypub' );
		}
	}

	/**
	 * Process action.
	 */
	public function process_action() {
		if ( ! isset( $_REQUEST['event_sources'] ) || ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$event_sources = $_REQUEST['event_sources']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( 'delete' === $this->current_action() ) {
			if ( ! is_array( $event_sources ) ) {
				$event_sources = array( $event_sources );
			}
			foreach ( $event_sources as $event_source ) {
				Event_Sources_Collection::remove_event_source( $event_source );
			}
		}
	}
}
