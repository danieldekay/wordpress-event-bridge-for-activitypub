<?php
/**
 * Class responsible for initializing Event Bridge for ActivityPub.
 *
 * The setup class provides function for checking if this plugin should be activated.
 * It detects supported event plugins and provides all setup hooks and filters.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 */

namespace Event_Bridge_For_ActivityPub;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Event_Bridge_For_ActivityPub\Admin\Event_Plugin_Admin_Notices;
use Event_Bridge_For_ActivityPub\Admin\General_Admin_Notices;
use Event_Bridge_For_ActivityPub\Admin\Health_Check;
use Event_Bridge_For_ActivityPub\Admin\Settings_Page;
use Event_Bridge_For_ActivityPub\Integrations\Event_Plugin;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Class Setup.
 *
 * This class is responsible for initializing Event Bridge for ActivityPub.
 *
 * @since 1.0.0
 */
class Setup {
	/**
	 * Keep the information whether the ActivityPub plugin is active.
	 *
	 * @var boolean
	 */
	protected $activitypub_plugin_is_active = false;

	/**
	 * Keep the current version of the current ActivityPub plugin.
	 *
	 * @var string
	 */
	protected $activitypub_plugin_version = '';

	/**
	 * Holds an array of the currently activated supported event plugins.
	 *
	 * @var Event_Plugin[]
	 */
	protected $active_event_plugins = array();

	/**
	 * Constructor for the Setup class.
	 *
	 * Initializes and sets up various components of the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		$this->activitypub_plugin_is_active = defined( 'ACTIVITYPUB_PLUGIN_VERSION' ) ||
			is_plugin_active( 'activitypub/activitypub.php' );
		// BeforeFirstRelease: decide whether we want to do anything at all when ActivityPub plugin is note active.
		// if ( ! $this->activitypub_plugin_is_active ) {
		// deactivate_plugins( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE );
		// return;
		// }.
		$this->active_event_plugins       = self::detect_active_event_plugins();
		$this->activitypub_plugin_version = self::get_activitypub_plugin_version();
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
	 * LooksUp the current version of the ActivityPub.
	 *
	 * @return string The semantic Version.
	 */
	private static function get_activitypub_plugin_version(): string {
		if ( defined( 'ACTIVITYPUB_PLUGIN_VERSION' ) ) {
			return constant( 'ACTIVITYPUB_PLUGIN_VERSION' );
		}
		$version = get_file_data( WP_PLUGIN_DIR . '/activitypub/activitypub.php', array( 'Version' ) )[0];
		return $version ?? '0.0.0';
	}

	/**
	 * Getter function for the active event plugins.
	 *
	 * @return Event_Plugin[]
	 */
	public function get_active_event_plugins() {
		return $this->active_event_plugins;
	}

	/**
	 * Holds all the classes for the supported event plugins.
	 *
	 * @var array
	 */
	private const EVENT_PLUGIN_CLASSES = array(
		'\Event_Bridge_For_ActivityPub\Integrations\Events_Manager',
		'\Event_Bridge_For_ActivityPub\Integrations\GatherPress',
		'\Event_Bridge_For_ActivityPub\Integrations\The_Events_Calendar',
		'\Event_Bridge_For_ActivityPub\Integrations\VS_Event_List',
		'\Event_Bridge_For_ActivityPub\Integrations\WP_Event_Manager',
		'\Event_Bridge_For_ActivityPub\Integrations\Eventin',
		'\Event_Bridge_For_ActivityPub\Integrations\Modern_Events_Calendar_Lite',
		'\Event_Bridge_For_ActivityPub\Integrations\EventPrime',
		'\Event_Bridge_For_ActivityPub\Integrations\Event_Organiser',
	);

	/**
	 * Function that checks for supported activated event plugins.
	 *
	 * @return array List of supported event plugins as keys from the SUPPORTED_EVENT_PLUGINS const.
	 */
	public static function detect_active_event_plugins(): array {
		$active_event_plugins = array();

		foreach ( self::EVENT_PLUGIN_CLASSES as $event_plugin_class ) {
			if ( ! class_exists( $event_plugin_class ) || ! method_exists( $event_plugin_class, 'get_plugin_file' ) ) {
				continue;
			}
			$event_plugin_file = call_user_func( array( $event_plugin_class, 'get_plugin_file' ) );
			if ( \is_plugin_active( $event_plugin_file ) ) {
				$active_event_plugins[] = new $event_plugin_class();
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
		register_activation_hook( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE, array( $this, 'activate' ) );

		add_action( 'admin_init', array( $this, 'do_admin_notices' ) );
		add_action( 'admin_init', array( Settings::class, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_styles' ) );
		add_action( 'admin_menu', array( Settings_Page::class, 'admin_menu' ) );
		add_filter(
			'plugin_action_links_' . EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_BASENAME,
			array( Settings_Page::class, 'settings_link' )
		);

		// If we don't have any active event plugins, or the ActivityPub plugin is not enabled, abort here.
		if ( empty( $this->active_event_plugins ) || ! $this->activitypub_plugin_is_active ) {
			return;
		}

		add_action( 'init', array( Health_Check::class, 'init' ) );
		add_action( 'init', array( Event_Sources::class, 'register_taxonomy' ) );

		// Check if the minimum required version of the ActivityPub plugin is installed.
		if ( ! version_compare( $this->activitypub_plugin_version, EVENT_BRIDGE_FOR_ACTIVITYPUB_ACTIVITYPUB_PLUGIN_MIN_VERSION ) ) {
			return;
		}

		add_filter( 'activitypub_transformer', array( $this, 'register_activitypub_event_transformer' ), 10, 3 );
	}

	/**
	 * Add the CSS for the admin pages.
	 *
	 * @param string $hook_suffix The suffix of the hook.
	 *
	 * @return void
	 */
	public static function enqueue_styles( $hook_suffix ): void {
		if ( false !== strpos( $hook_suffix, 'event-bridge-for-activitypub' ) ) {
			wp_enqueue_style(
				'event-bridge-for-activitypub-admin-styles',
				plugins_url(
					'assets/css/event-bridge-for-activitypub-admin.css',
					EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE
				),
				array(),
				EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_VERSION
			);
			wp_enqueue_script(
				'event-bridge-for-activitypub-admin-script',
				plugins_url(
					'assets/js/event-bridge-for-activitypub-admin.js',
					EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE
				),
				array( 'jquery' ),
				EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_VERSION,
				false
			);
		}
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
			add_action( 'admin_notices', array( 'Event_Bridge_For_ActivityPub\Admin\General_Admin_Notices', 'activitypub_plugin_not_enabled' ), 10, 1 );
		}
		if ( ! version_compare( $this->activitypub_plugin_version, EVENT_BRIDGE_FOR_ACTIVITYPUB_ACTIVITYPUB_PLUGIN_MIN_VERSION ) ) {
			// The ActivityPub plugin is too old.
			add_action( 'admin_notices', array( 'Event_Bridge_For_ActivityPub\Admin\General_Admin_Notices', 'activitypub_plugin_version_too_old' ), 10, 1 );
		}
		if ( empty( $this->active_event_plugins ) ) {
			// No supported Event Plugin is active.
			add_action( 'admin_notices', array( 'Event_Bridge_For_ActivityPub\Admin\General_Admin_Notices', 'no_supported_event_plugin_active' ), 10, 1 );
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
	public function register_activitypub_event_transformer( $transformer, $wp_object, $object_class ): ?\Activitypub\Transformer\Base {
		// If the current WordPress object is not a post (e.g., a WP_Comment), don't change the transformer.
		if ( 'WP_Post' !== $object_class ) {
			return $transformer;
		}

		// Get the transformer for a specific event plugins event-post type.
		foreach ( $this->active_event_plugins as $event_plugin ) {
			if ( $wp_object->post_type === $event_plugin->get_post_type() ) {
				$transformer_class = $event_plugin::get_activitypub_event_transformer_class();
				if ( class_exists( $transformer_class ) ) {
					return new $transformer_class( $wp_object, $event_plugin::get_event_category_taxonomy() );
				}
			}
		}

		// Return the default transformer.
		return $transformer;
	}

	/**
	 * Activates ActivityPub support for all active event plugins event post-types.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function activate_activitypub_support_for_active_event_plugins(): void {
		// If someone installs this plugin, we simply enable ActivityPub support for all currently active event post types.
		$activitypub_supported_post_types = get_option( 'activitypub_support_post_types', array() );
		foreach ( $this->active_event_plugins as $event_plugin ) {
			if ( ! in_array( $event_plugin->get_post_type(), $activitypub_supported_post_types, true ) ) {
				$activitypub_supported_post_types[] = $event_plugin->get_post_type();
				add_post_type_support( $event_plugin->get_post_type(), 'activitypub' );
			}
		}
		update_option( 'activitypub_support_post_types', $activitypub_supported_post_types );
	}

	/**
	 * Activates the Event Bridge for ActivityPub plugin.
	 *
	 * This method handles the activation of the Event Bridge for ActivityPub plugin.
	 *
	 * @since 1.0.0
	 * @see register_activation_hook()
	 * @return void
	 */
	public function activate(): void {
		// Don't allow plugin activation, when the ActivityPub plugin is not activated yet.
		if ( ! $this->activitypub_plugin_is_active ) {
			deactivate_plugins( plugin_basename( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE ) );
			$notice = General_Admin_Notices::get_admin_notice_activitypub_plugin_not_enabled();
			wp_die(
				wp_kses( $notice, General_Admin_Notices::ALLOWED_HTML ),
				'Plugin dependency check',
				array( 'back_link' => true ),
			);
		}

		if ( empty( $this->active_event_plugins ) ) {
			deactivate_plugins( plugin_basename( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE ) );
			$notice = General_Admin_Notices::get_admin_notice_no_supported_event_plugin_active();
			wp_die(
				wp_kses( $notice, General_Admin_Notices::ALLOWED_HTML ),
				'Plugin dependency check',
				array( 'back_link' => true ),
			);
		}

		self::activate_activitypub_support_for_active_event_plugins();
	}
}
