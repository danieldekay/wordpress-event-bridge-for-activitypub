<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ActivityPub Tribe Transformer
 *
 * @since 1.0.0
 */
class Activitypub_Tribe_Transformer extends \Activitypub\Transformer_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve oEmbed widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'activitypub-tribe/tribe';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve Transformer title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget title.
	 */
	public function get_label() {
		return 'The Events Calendar';
	}

	/**
	 * Get supported post types.
	 *
	 * Retrieve the list of supported WordPress post types this transformer widget can handle.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Widget categories.
	 */
	public function get_supported_post_types() {
		return [ 'tribe_events' ];
	}

}
