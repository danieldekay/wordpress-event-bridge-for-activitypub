<?php
/**
 * Class responsible for initializing ActivityPub Event Extensions.
 *
 * The setup class provides function for checking if this plugin should be activated.
 * It detects supported event plugins and provides all setup hooks and filters.
 *
 * @package Activitypub_Event_Extensions
 * @since 1.0.0
 */

namespace Activitypub_Event_Extensions;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Class Setup.
 *
 * This class is responsible for initializing ActivityPub Event Extensions.
 *
 * @since 1.0.0
 */
class Plugin_Manager {
    /**
     * Gets all active supported event plugins.
     */
    public function get_active_event_plugins() {
        $active_event_plugins = array();

        foreach ( get_declared_classes() as $class ) {
            if (strpos($class, __NAMESPACE__) === 0) {
                $reflection = new ReflectionClass($class);

                // Skip interfaces or abstract classes.
                if ($reflection->isAbstract() || $reflection->isInterface()) {
                    continue;
                }

                if ($reflection->hasMethod('get_plugin_file')) {
                    $instance = $reflection->newInstance();
                    $plugin_file = $instance->get_plugin_file();

                    if (is_plugin_active($plugin_file)) {
                        $active_event_plugins[$class] = $plugin_file;
                    }
                }
            }
        }

        // Now you can use $active_event_plugins for further processing
        return $active_event_plugins;
    }
}
