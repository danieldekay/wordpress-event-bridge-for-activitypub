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

use Event_Bridge_For_ActivityPub\ActivityPub\Collection\Event_Sources as Event_Sources_Collection;
use Event_Bridge_For_ActivityPub\ActivityPub\Handler\Join as Join_Handler;
use Event_Bridge_For_ActivityPub\ActivityPub\Scheduler\Event as Event_Scheduler;
use Event_Bridge_For_ActivityPub\Admin\Event_Plugin_Admin_Notices;
use Event_Bridge_For_ActivityPub\Admin\General_Admin_Notices;
use Event_Bridge_For_ActivityPub\Admin\Health_Check;
use Event_Bridge_For_ActivityPub\Admin\Settings_Page;
use Event_Bridge_For_ActivityPub\Integrations\Event_Plugin_Integration;
use Event_Bridge_For_ActivityPub\Integrations\Feature_Event_Sources;
use Event_Bridge_For_ActivityPub\Reminder;

use function Activitypub\is_user_type_disabled;

// @phpstan-ignore-next-line
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Class Setup.

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
	 * @var Event_Plugin_Integration[]
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
		// Detect the presence/active-status and version of the ActivityPub plugin.
		$this->activitypub_plugin_is_active = defined( 'ACTIVITYPUB_PLUGIN_VERSION' ) || \is_plugin_active( 'activitypub/activitypub.php' );
		$this->activitypub_plugin_version   = self::get_activitypub_plugin_version();

		// Register main action that load the Event Bridge For ActivityPub.
		\add_action( 'plugins_loaded', array( $this, 'setup_hooks' ) );
	}

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 * @var ?self The instance of the class.
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
	 * Getter function for whether the ActivityPub plugin is active.
	 *
	 * @return bool True when the ActivityPub plugin is active.
	 */
	public function is_activitypub_plugin_active(): bool {
		return $this->activitypub_plugin_is_active;
	}

	/**
	 * Get the current version of the ActivityPub plugin.
	 *
	 * @return string The semantic Version.
	 */
	private static function get_activitypub_plugin_version(): string {
		if ( defined( 'ACTIVITYPUB_PLUGIN_VERSION' ) ) {
			return constant( 'ACTIVITYPUB_PLUGIN_VERSION' );
		}
		return '0.0.0';
	}

	/**
	 * Getter function for the active event plugins.
	 *
	 * @return Event_Plugin_Integration[]
	 */
	public function get_active_event_plugins(): array {
		return $this->active_event_plugins;
	}

	/**
	 * Getter function for the active event plugins event post types.
	 *
	 * @return array List of event post types of the active event plugins.
	 */
	public function get_active_event_plugins_post_types(): array {
		$post_types = array();
		foreach ( $this->active_event_plugins as $event_plugin ) {
			$post_types[] = $event_plugin->get_post_type();
		}

		return $post_types;
	}

	/**
	 * Function to check whether a post type is an event post type of an active event plugin.
	 *
	 * @param string $post_type The post type.
	 *
	 * @return bool True if it is an event post type.
	 */
	public function is_event_post_type_of_active_event_plugin( $post_type ): bool {
		foreach ( $this->active_event_plugins as $event_plugin ) {
			if ( $post_type === $event_plugin->get_post_type() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Holds all the full class names for the supported event plugins.
	 *
	 * @var string[]
	 */
	private const EVENT_PLUGIN_INTEGRATIONS = array(
		\Event_Bridge_For_ActivityPub\Integrations\Events_Manager::class,
		\Event_Bridge_For_ActivityPub\Integrations\GatherPress::class,
		\Event_Bridge_For_ActivityPub\Integrations\The_Events_Calendar::class,
		\Event_Bridge_For_ActivityPub\Integrations\VS_Event_List::class,
		\Event_Bridge_For_ActivityPub\Integrations\WP_Event_Manager::class,
		\Event_Bridge_For_ActivityPub\Integrations\Eventin::class,
		\Event_Bridge_For_ActivityPub\Integrations\Modern_Events_Calendar_Lite::class,
		\Event_Bridge_For_ActivityPub\Integrations\Event_Organiser::class,
		\Event_Bridge_For_ActivityPub\Integrations\EventPrime::class,
		\Event_Bridge_For_ActivityPub\Integrations\EventOn::class,
	);

	/**
	 * Force the re-scan for active event plugins without using the cached transient.
	 *
	 * @return void
	 */
	public function redetect_active_event_plugins(): void {
		if ( ! $this->activitypub_plugin_is_active ) {
			return;
		}
		\delete_transient( 'event_bridge_for_activitypub_active_event_plugins' );

		$this->detect_active_event_plugins();
	}

	/**
	 * Function that checks for supported activated event plugins.
	 *
	 * @return array List of supported event plugins as keys from the SUPPORTED_EVENT_PLUGINS const.
	 */
	public function detect_active_event_plugins(): array {
		// Detection will fail in case the ActivityPub plugin is not active.
		if ( ! $this->activitypub_plugin_is_active ) {
			return array();
		}

		$active_event_plugins = \get_transient( 'event_bridge_for_activitypub_active_event_plugins' );

		if ( $active_event_plugins ) {
			$this->active_event_plugins = $active_event_plugins;
			return $active_event_plugins;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			// @phpstan-ignore-next-line
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = array_merge( \get_plugins(), \get_mu_plugins() );

		$active_event_plugins = array();
		foreach ( self::EVENT_PLUGIN_INTEGRATIONS as $event_plugin_integration ) {
			// Get the filename of the main plugin file of the event plugin (relative to the plugin dir).
			$event_plugin_file = $event_plugin_integration::get_relative_plugin_file();

			// Check if plugin is present on disk and is activated.
			if ( array_key_exists( $event_plugin_file, $all_plugins ) && \is_plugin_active( $event_plugin_file ) ) {
				$active_event_plugins[ $event_plugin_file ] = new $event_plugin_integration();
			}
		}
		\set_transient( 'event_bridge_for_activitypub_active_event_plugins', $active_event_plugins );
		$this->active_event_plugins = $active_event_plugins;
		return $active_event_plugins;
	}

	/**
	 * Function that checks which event plugins support the event sources feature.
	 *
	 * @return array List of supported event plugins as keys from the SUPPORTED_EVENT_PLUGINS const.
	 */
	public static function detect_event_plugins_supporting_event_sources(): array {
		$plugins_supporting_event_sources = array();

		foreach ( self::EVENT_PLUGIN_INTEGRATIONS as $event_plugin_integration ) {
			if ( is_a( $event_plugin_integration, Feature_Event_Sources::class, true ) ) {
				$plugins_supporting_event_sources[] = new $event_plugin_integration();
			}
		}
		return $plugins_supporting_event_sources;
	}

	/**
	 * Main setup function of the plugin "Event Bridge For ActivityPub".
	 *
	 * This method adds hooks for different purposes as needed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		// Detect active supported event plugins.
		$this->detect_active_event_plugins();

		// Register hook that runs when this plugin gets activated.
		\register_activation_hook( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE, array( $this, 'activate' ) );

		// Register listeners whenever any plugin gets activated or deactivated to maybe update the transient of active event plugins.
		\add_action( 'activated_plugin', array( $this, 'redetect_active_event_plugins' ) );
		\add_action( 'deactivated_plugin', array( $this, 'redetect_active_event_plugins' ) );

		// Add hook that takes care of all notices in the Admin UI.
		\add_action( 'admin_init', array( $this, 'do_admin_notices' ) );

		// Add hook that registers all settings of this plugin to WordPress.
		\add_action( 'admin_init', array( Settings::class, 'register_settings' ) );

		// Add hook that loads CSS and JavaScript files for the Admin UI.
		\add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_styles' ) );

		// Register the settings page of this plugin as a Tab in the ActivityPub plugins settings.
		Settings_Page::init();

		// Add settings link in the Plugin overview Page.
		\add_filter(
			'plugin_action_links_' . EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_BASENAME,
			array( Settings_Page::class, 'settings_link' )
		);

		// If we don't have any active event plugins, or the ActivityPub plugin is not enabled, abort here.
		if ( empty( $this->active_event_plugins ) || ! $this->activitypub_plugin_is_active ) {
			self::shut_down();
			return;
		}

		// Register health checks and status reports to the WordPress status report site.
		\add_action( 'init', array( Health_Check::class, 'init' ) );

		// Check if the minimum required version of the ActivityPub plugin is installed, if not abort.

		if ( ! version_compare( $this->activitypub_plugin_version, EVENT_BRIDGE_FOR_ACTIVITYPUB_ACTIVITYPUB_PLUGIN_MIN_VERSION, '>=' ) ) {
			return;
		}

		// Register our own deplayed event scheduler because of upstream race-condition bug.
		// See https://github.com/Automattic/wordpress-activitypub/issues/1269 for more information.
		if ( version_compare( $this->activitypub_plugin_version, '7.0.0', '<' ) ) {
			Event_Scheduler::init();
		}

		// Register the event reminders.
		\add_action( 'init', array( Reminder::class, 'init' ) );

		// Initialize the handling of "Join" activities.
		Join_Handler::init();

		// If the Event-Sources feature is enabled and all requirements are met, initialize it.
		if ( ! is_user_type_disabled( 'blog' ) && \get_option( 'event_bridge_for_activitypub_event_sources_active' ) ) {
			Event_Sources::init();
		}

		// Initialize writing of debug logs.
		Debug::init();

		$this->register_plugin_specific_hooks();

		// Most importantly: register the ActivityPub transformers for events to the ActivityPub plugin.
		\add_filter( 'activitypub_transformer', array( $this, 'register_activitypub_transformer' ), 10, 3 );

		// Apply custom ActivityPub previews for events.
		\add_action( 'init', array( Preview::class, 'init' ) );

		$this->maybe_register_term_activitypub_ids();

		// HotFix: Make sure status is 200 when ActivityPub query was successful.
		\add_action( 'activitypub_json_pre', array( self::class, 'ensure_200_status_header' ) );
	}

	/**
	 * Temporary hack to register custom actions and hooks, only needed by exceptional event plugins.
	 *
	 * @return void
	 */
	private function register_plugin_specific_hooks(): void {
		if ( array_key_exists( \Event_Bridge_For_ActivityPub\Integrations\EventPrime::get_relative_plugin_file(), $this->active_event_plugins ) ) {
			\Event_Bridge_For_ActivityPub\Integrations\EventPrime::init();
		}
	}

	/**
	 * Shut down the plugin.
	 *
	 * @return void
	 */
	public static function shut_down(): void {
		// Delete all transients.
		Event_Sources_Collection::delete_event_source_transients();
		\delete_transient( 'event_bridge_for_activitypub_active_event_plugins' );

		// Unschedule all crons.
		\wp_unschedule_hook( 'event_bridge_for_activitypub_event_sources_clear_cache' );
	}

	/**
	 * Add the CSS for the admin pages.
	 *
	 * @param string $hook_suffix The suffix of the hook.
	 *
	 * @return void
	 */
	public static function enqueue_styles( $hook_suffix ): void {
		if ( 'settings_page_activitypub' !== $hook_suffix ) {
			return;
		}

		// Check if we're on your custom tab.
		$current_tab = isset( $_GET['tab'] ) ? \sanitize_key( $_GET['tab'] ) : 'welcome'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'event-bridge-for-activitypub' === $current_tab ) {
			\wp_enqueue_style(
				'event-bridge-for-activitypub-admin-styles',
				plugins_url(
					'assets/css/event-bridge-for-activitypub-admin.css',
					EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE
				),
				array(),
				EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_VERSION
			);
			\wp_enqueue_script(
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
			\add_action( 'admin_notices', array( General_Admin_Notices::class, 'activitypub_plugin_not_enabled' ), 10, 0 );
			return;
		}
		if ( ! version_compare( $this->activitypub_plugin_version, EVENT_BRIDGE_FOR_ACTIVITYPUB_ACTIVITYPUB_PLUGIN_MIN_VERSION, '>=' ) ) {
			// The ActivityPub plugin is too old.
			\add_action( 'admin_notices', array( General_Admin_Notices::class, 'activitypub_plugin_version_too_old' ), 10, 0 );
			return;
		}
		if ( empty( $this->active_event_plugins ) ) {
			// No supported Event Plugin is active.
			\add_action( 'admin_notices', array( General_Admin_Notices::class, 'no_supported_event_plugin_active' ), 10, 0 );
		}
	}

	/**
	 * Add the custom transformers for the events and locations of several WordPress event plugins.
	 *
	 * @param \Activitypub\Transformer\Base $transformer  The transformer to use.
	 * @param mixed                         $data         The data to transform.
	 * @param string                        $object_class The class of the object to transform.
	 *
	 * @return \Activitypub\Transformer\Base|null|\WP_Error
	 */
	public function register_activitypub_transformer( $transformer, $data, $object_class ) {
		// If the current WordPress object is not a post (e.g., a WP_Comment), don't change the transformer.
		if ( 'WP_Post' === $object_class ) {
			// Get the transformer for a specific event plugins event or location post type.
			foreach ( $this->active_event_plugins as $event_plugin ) {
				// Check if we have an event.
				if ( $data->post_type === $event_plugin->get_post_type() ) {
					if ( ! self::is_post_disabled( $data ) ) {
						return $event_plugin::get_activitypub_event_transformer( $data );
					} else {
						return new \WP_Error( 'invalid_object', __( 'Invalid object', 'event-bridge-for-activitypub' ) );
					}
				}

				// Check if we have a location.
				if ( $data->post_type === $event_plugin->get_place_post_type() ) {
					if ( ! self::is_post_disabled( $data ) ) {
						return $event_plugin::get_activitypub_place_transformer( $data );
					} else {
						return new \WP_Error( 'invalid_object', __( 'Invalid object', 'event-bridge-for-activitypub' ) );
					}
				}
			}
		} elseif ( 'WP_Term' === $object_class ) {
			foreach ( $this->active_event_plugins as $event_plugin ) {
				if ( $data->taxonomy === $event_plugin->get_place_taxonomy() ) {
					return $event_plugin::get_activitypub_place_transformer( $data );
				}
			}
		}

		// Return the default transformer.
		return $transformer;
	}

	/**
	 * Check if a post of a post type that is managed by this plugin is disabled for ActivityPub.
	 *
	 * This function checks the visibility of the post and whether it is private or has a password.
	 *
	 * @param mixed $post The post object or ID.
	 *
	 * @return boolean True if the post is disabled, false otherwise.
	 */
	public static function is_post_disabled( $post ): bool {
		$post     = \get_post( $post );
		$disabled = false;

		if ( ! $post ) {
			return true;
		}

		$visibility = \get_post_meta( $post->ID, 'activitypub_content_visibility', true );

		if (
			ACTIVITYPUB_CONTENT_VISIBILITY_LOCAL === $visibility ||
			ACTIVITYPUB_CONTENT_VISIBILITY_PRIVATE === $visibility ||
			'private' === $post->post_status ||
			! empty( $post->post_password )
		) {
			$disabled = true;
		}

		return $disabled;
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
		\update_option( 'activitypub_support_post_types', $activitypub_supported_post_types );
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
		$this->redetect_active_event_plugins();
		// Don't allow plugin activation, when the ActivityPub plugin is not activated yet.
		if ( ! $this->activitypub_plugin_is_active ) {
			\deactivate_plugins( plugin_basename( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE ) );
			$notice = General_Admin_Notices::get_admin_notice_activitypub_plugin_not_enabled();
			\wp_die(
				// @phpstan-ignore-next-line
				wp_kses( $notice, General_Admin_Notices::ALLOWED_HTML ),
				'Plugin dependency check',
				array( 'back_link' => true ),
			);
		}

		if ( empty( $this->active_event_plugins ) ) {
			\deactivate_plugins( plugin_basename( EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE ) );
			$notice = General_Admin_Notices::get_admin_notice_no_supported_event_plugin_active();
			\wp_die(
				// @phpstan-ignore-next-line
				wp_kses( $notice, General_Admin_Notices::ALLOWED_HTML ),
				'Plugin dependency check',
				array( 'back_link' => true ),
			);
		}

		self::activate_activitypub_support_for_active_event_plugins();
	}

	/**
	 * Maybe (depending on active event plugins) make it possible to querly event terms by `?term_id=<term_id>`.
	 *
	 * @return void
	 */
	private function maybe_register_term_activitypub_ids(): void {
		$register_term_query_var = false;

		foreach ( $this->active_event_plugins as $event_plugin ) {
			if ( $event_plugin::get_place_taxonomy() ) {
				$register_term_query_var = true;
				break;
			}
		}

		if ( $register_term_query_var ) {
			\add_filter( 'query_vars', array( self::class, 'add_term_query_var' ) );
			\add_filter( 'activitypub_queried_object', array( $this, 'maybe_detect_event_plugins_location_term' ) );
		}
	}

	/**
	 * Add the 'activitypub' query for term variable so WordPress won't mangle it.
	 *
	 * @param array $vars The query variables.
	 *
	 * @return array The query variables.
	 */
	public static function add_term_query_var( $vars ) {
		$vars[] = 'term_id';

		return $vars;
	}

	/**
	 * Filters the queried object.
	 *
	 * @param \WP_Term|\WP_Post_Type|\WP_Post|\WP_User|\WP_Comment|null $queried_object The queried object.
	 */
	public function maybe_detect_event_plugins_location_term( $queried_object ) {
		if ( $queried_object ) {
			return $queried_object;
		}

		$term_id = \get_query_var( 'term_id' );

		if ( $term_id ) {
			$queried_object = \get_term( $term_id );
		}

		if ( $queried_object instanceof \WP_Term && $this->is_place_taxonomy_of_active_event_plugin( $queried_object->taxonomy ) ) {
			return $queried_object;
		}

		return null;
	}

	/**
	 * Check whether a taxonomy is an active event plugins location taxonomy.
	 *
	 * @param string $taxonomy The taxonomy.
	 * @return boolean
	 */
	private function is_place_taxonomy_of_active_event_plugin( $taxonomy ): bool {
		foreach ( $this->active_event_plugins as $event_plugin ) {
			if ( $event_plugin::get_place_taxonomy() === $taxonomy ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the event plugin integration class name used for the event sources feature.
	 *
	 * @return ?string The class name of the event plugin integration class.
	 */
	public static function get_event_plugin_integration_used_for_event_sources_feature(): ?string {
		// Get plugin option.
		$event_plugin_integration = get_option(
			'event_bridge_for_activitypub_integration_used_for_event_sources_feature',
			self::get_default_integration_class_name_used_for_event_sources_feature()
		);

		// Exit if event sources are not active or no plugin is specified.
		if ( empty( $event_plugin_integration ) ) {
			return null;
		}

		// Validate if setting is actual existing class.
		if ( ! class_exists( $event_plugin_integration ) ) {
			return null;
		}

		return $event_plugin_integration;
	}

	/**
	 * Get the transmogrifier class.
	 *
	 * Retrieves the appropriate transmogrifier class based on the active event plugins and settings.
	 *
	 * @return ?string The transmogrifier class name or null if not available.
	 */
	public static function get_transmogrifier(): ?string {
		$event_plugin_integration = self::get_event_plugin_integration_used_for_event_sources_feature();

		if ( ! $event_plugin_integration ) {
			return null;
		}

		// Validate if get_transformer method exists in event plugin integration.
		if ( ! method_exists( $event_plugin_integration, 'get_transmogrifier' ) ) {
			return null;
		}

		$transmogrifier = $event_plugin_integration::get_transmogrifier();

		return $transmogrifier;
	}

	/**
	 * Get the full class name of the first event plugin integration that is active and supports the event source feature.
	 *
	 * @return string The full class name of the event plugin integration.
	 */
	public static function get_default_integration_class_name_used_for_event_sources_feature(): string {
		$setup = self::get_instance();

		$event_plugin_integrations = $setup->get_active_event_plugins();
		foreach ( $event_plugin_integrations as $event_plugin_integration ) {
			if ( $event_plugin_integration instanceof Feature_Event_Sources ) {
				return get_class( $event_plugin_integration );
			}
		}
		return '';
	}

	/**
	 * Ensure 200 status header.
	 *
	 * Can be removed after https://github.com/Automattic/wordpress-activitypub/pull/1299/ is merged and released.
	 *
	 * @return void
	 */
	public static function ensure_200_status_header(): void {
		\set_query_var( 'is_404', false );

		// Check if header already sent.
		if ( ! \headers_sent() ) {
			// Send 200 status header.
			\status_header( 200 );

			// @phpstan-ignore-next-line
			if ( ACTIVITYPUB_SEND_VARY_HEADER ) {
				// Send Vary header for Accept header.
				\header( 'Vary: Accept' );
			}
		}
	}
}
