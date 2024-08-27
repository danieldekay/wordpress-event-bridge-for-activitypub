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

use Activitypub_Event_Extensions\Admin\Event_Plugin_Admin_Notices;
use Activitypub_Event_Extensions\Admin\General_Admin_Notices;
use Activitypub_Event_Extensions\Admin\Settings;
use Activitypub_Event_Extensions\Admin\Settings_Page;
use Activitypub_Event_Extensions\Plugins\Gatherpress;

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
class Setup {
	const SUPPORTED_EVENT_PLUGINS = array(
		'Events_Manager',
		'GatherPress',
		'The_Events_Calendar',
		'VS_Event_List',
	);

	/**
	 * Keep the information whether the ActivityPub plugin is active.
	 *
	 * @var boolean
	 */
	protected $activitypub_plugin_is_active = false;

	/**
	 * Holds an array of the currently activated supported event plugins.
	 *
	 * @var array
	 */
	protected $active_event_plugins = array();

	/**
	 * Getter function for the active event plugins.
	 */
	public function get_active_event_plugins() {
		return $this->active_event_plugins;
	}

	/**
	 * Constructor for the Setup class.
	 *
	 * Initializes and sets up various components of the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		$this->activitypub_plugin_is_active = is_plugin_active( 'activitypub/activitypub.php' );
		$this->active_event_plugins         = self::detect_supported_event_plugins();
		$this->setup_hooks();
	}

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 * @var ?self|null The instance of the class.
	 */
	private static $instance = null;

	/**
	 * Get the instance of the Singleton class.
	 *
	 * If an instance does not exist, it creates one; otherwise, it returns the existing instance.
	 *
	 * @since 1.0.0
	 *
	 * @return self The instance of the class.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Function that checks for supported activated event plugins.
	 *
	 * @return array List of supported event plugins as keys from the SUPPORTED_EVENT_PLUGINS const.
	 */
	public static function detect_supported_event_plugins(): array {
		$active_event_plugins = array();
		foreach ( self::SUPPORTED_EVENT_PLUGINS as $event_plugin ) {
			$event_plugin_class = 'Activitypub_Event_Extensions\Plugins\\' . $event_plugin;
			if ( \is_plugin_active( $event_plugin_class::get_plugin_file() ) ) {
				$active_event_plugins[ $event_plugin ] = $event_plugin_class;
			}
		}
		return $active_event_plugins;
	}

	/**
	 * Set up hooks for various purposes.
	 *
	 * This method adds hooks for different purposes as needed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function setup_hooks(): void {
		register_activation_hook( ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_FILE, array( $this, 'activate' ) );

		add_action( 'admin_init', array( $this, 'do_admin_notices' ) );
		add_action( 'admin_init', array( Settings::class, 'register_settings' ) );

		// If we don't have any active event plugins, or the ActivityPub plugin is not enabled, abort here.
		if ( empty( $this->active_event_plugins ) || ! $this->activitypub_plugin_is_active ) {
			return;
		}

		add_action( 'admin_menu', array( Settings_Page::class, 'admin_menu' ) );

		add_filter(
			'plugin_action_links_' . ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_BASENAME,
			array( Settings_Page::class, 'settings_link' )
		);
		add_filter( 'activitypub_transformer', array( $this, 'register_activitypub_event_transformer' ), 10, 3 );
	}

	/**
	 * Fires the initialization of admin notices.
	 */
	public function do_admin_notices(): void {
		foreach ( $this->active_event_plugins as $event_plugin ) {
			new Event_Plugin_Admin_Notices( $event_plugin );
		}
		// Check if any general admin notices are needed and add actions to insert the needed admin notices.

		if ( ! $this->activitypub_plugin_is_active ) {
			// The ActivityPub plugin is not active.
			add_action( 'admin_notices', array( 'Activitypub_Event_Extensions\Admin\General_Admin_Notices', 'activitypub_plugin_not_enabled' ), 10, 1 );
		}
		if ( empty( $this->active_event_plugins ) ) {
			// No supported Event Plugin is active.
			add_action( 'admin_notices', array( 'Activitypub_Event_Extensions\Admin\General_Admin_Notices', 'no_supported_event_plugin_active' ), 10, 1 );
		}
	}

	/**
	 * Add the custom transformers for the events of several WordPress event plugins.
	 *
	 * @param Activitypub\Transformer\Base $transformer  The transformer to use.
	 * @param mixed                        $wp_object    The WordPress object to transform.
	 * @param string                       $object_class The class of the object to transform.
	 *
	 * @return \Activitypub\Transformer\Base|null
	 */
	public function register_activitypub_event_transformer( $transformer, $wp_object, $object_class ): \Activitypub\Transformer\Base|null {
		// If the current WordPress object is not a post (e.g., a WP_Comment), don't change the transformer.
		if ( 'WP_Post' !== $object_class ) {
			return $transformer;
		}

		// Get the transformer for a specific event plugins event-post type.
		foreach ( $this->active_event_plugins as $event_plugin ) {
			if ( $wp_object->post_type === $event_plugin::get_post_type() ) {
				$transformer_class = 'Activitypub_Event_Extensions\Activitypub\Transformer\\' . $event_plugin['transformer_class'];
				return new $transformer_class( $wp_object );
			}
		}

		// Return the default transformer.
		return $transformer;
	}

	/**
	 * Activates the ActivityPub Event Extensions plugin.
	 *
	 * This method handles the activation of the ActivityPub Event Extensions plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function activate() {
		// Don't allow plugin activation, when the ActivityPub plugin is not activated yet.
		if ( ! $this->activitypub_plugin_is_active ) {
			deactivate_plugins( plugin_basename( ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_FILE ) );
			$notice = General_Admin_Notices::get_admin_notice_activitypub_plugin_not_enabled();
			wp_die(
				wp_kses( $notice, General_Admin_Notices::ALLOWED_HTML ),
				'Plugin dependency check',
				array( 'back_link' => true ),
			);
		}

		if ( empty( $this->active_event_plugins ) ) {
			deactivate_plugins( plugin_basename( ACTIVITYPUB_EVENT_EXTENSIONS_PLUGIN_FILE ) );
			$notice = General_Admin_Notices::get_admin_notice_no_supported_event_plugin_active();
			wp_die(
				wp_kses( $notice, General_Admin_Notices::ALLOWED_HTML ),
				'Plugin dependency check',
				array( 'back_link' => true ),
			);
		}

		// If someone installs this plugin, we simply enable ActivityPub support for all currently active event post types.
		$activitypub_supported_post_types = get_option( 'activitypub_support_post_types', array() );
		foreach ( $this->active_event_plugins as $event_plugin ) {
			if ( ! in_array( $event_plugin::get_post_type, $activitypub_supported_post_types, true ) ) {
				$activitypub_supported_post_types[] = $event_plugin['post_type'];
			}
		}
		update_option( 'activitypub_support_post_types', $activitypub_supported_post_types );
	}
}
