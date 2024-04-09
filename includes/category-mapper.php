<?php

/**
 * Mapping of WordPress Terms(Tags) to known Event Categories
 *
 * @package activity-event-transformers
 * @license AGPL-3.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Activitypub\Activity\Extended_Object\Event;

/**
 * ActivityPub Tribe Transformer
 *
 * @since 1.0.0
 */
class Category_Mapper {

	/**
	 * Static function to do the Mapping
	 **/
	public static function map( $post_categories ) {

		if ( empty( $post_categories ) ) {
			return 'MEETING';
		}

		// Prepare an array to store all category information for comparison.
		$category_info = array();

		// Extract relevant category information (name, slug, description) from the categories array.
		foreach ( $post_categories as $category ) {
			$category_info[] = strtolower( $category->name );
			$category_info[] = strtolower( $category->slug );
			$category_info[] = strtolower( $category->description );
		}

		// Convert mobilizon categories to lowercase for case-insensitive comparison.
		$mobilizon_categories = array_map( 'strtolower', Event::DEFAULT_EVENT_CATEGORIES );

		// Initialize variables to track the best match.
		$best_mobilizon_category_match = '';
		$best_match_length             = 0;

		// Check for the best match.
		foreach ( $mobilizon_categories as $mobilizon_category ) {
			foreach ( $category_info as $category ) {
				foreach ( explode( '_', $mobilizon_category ) as $mobilizon_category_slice ) {
					if ( stripos( $category, $mobilizon_category_slice ) !== false ) {
						// Check if the current match is longer than the previous best match.
						$current_match_legnth = strlen( $mobilizon_category_slice );
						if ( $current_match_legnth > $best_match_length ) {
							$best_mobilizon_category_match = $mobilizon_category;
							$best_match_length             = $current_match_legnth;
						}
					}
				}
			}
		}

		return ( '' != $best_mobilizon_category_match ) ? strtoupper( $best_mobilizon_category_match ) : 'MEETING';
	}
}
