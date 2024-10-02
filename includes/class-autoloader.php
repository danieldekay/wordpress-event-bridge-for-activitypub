<?php
/**
 * Class responsible for autoloading ActivityPub Event Bridge class files.
 *
 * The Autoloader class is responsible for automatically loading class files as needed
 * to ensure a clean and organized codebase. It maps class names to their corresponding
 * file locations within the GatherPress plugin.
 *
 * @package ActivityPub_Event_Bridge
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace ActivityPub_Event_Bridge;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Class Autoloader.
 *
 * This class is responsible for automatic loading of classes and namespaces.
 *
 * @since 1.0.0
 */
class Autoloader {
	/**
	 * Register method for autoloader.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register(
			function ( $full_class ) {
				$base_dir = ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_DIR . '/includes/';
				$base     = 'ActivityPub_Event_Bridge\\';

				if ( strncmp( $full_class, $base, strlen( $base ) ) === 0 ) {
					$maybe_uppercase = str_replace( $base, '', $full_class );
					$class           = strtolower( $maybe_uppercase );
					// All classes should be capitalized. If this is instead looking for a lowercase method, we ignore that.
					if ( $maybe_uppercase === $class ) {
						return;
					}

					if ( false !== strpos( $class, '\\' ) ) {
						$parts    = explode( '\\', $class );
						$class    = array_pop( $parts );
						$sub_dir  = strtr( implode( '/', $parts ), '_', '-' );
						$base_dir = $base_dir . $sub_dir . '/';
					}

					$filename = 'class-' . strtr( $class, '_', '-' );
					$file     = $base_dir . $filename . '.php';

					if ( file_exists( $file ) && is_readable( $file ) ) {
						require_once $file;
					} else {
						\wp_die( sprintf( esc_html( 'Required class not found or not readable: %s' ), esc_html( $full_class ) ) );
					}
				}
			}
		);
	}
}
