<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.3
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	// "final class" only supported since PHP 5.
	class Freemius extends Freemius_Abstract {
		/**
		 * @var string
		 */
		public $version = '1.0.9';

		/**
		 * @since 1.0.1
		 *
		 * @var string
		 */
		private $_slug;
		/**
		 * @since 1.0.6
		 *
		 * @var string
		 */
		private $_menu_slug;
		private $_plugin_basename;
		private $_free_plugin_basename;
		private $_plugin_dir_path;
		private $_plugin_dir_name;
		private $_plugin_main_file_path;
		private $_plugin_data;
		/**
		 * @since 1.0.9
		 *
		 * @var string
		 */
		private $_plugin_name;

		/**
		 * @since 1.0.9
		 * @var bool If false, don't turn Freemius on.
		 */
		private $_is_on;

		/**
		 * @since 1.0.9
		 * @var bool If false, issues with connectivity to Freemius API.
		 */
		private $_has_api_connection;

		/**
		 * @since 1.0.9
		 * @var bool Hints the SDK if plugin can support anonymous mode (if skip connect is visible).
		 */
		private $_enable_anonymous;


		/**
		 * @since 1.0.8
		 * @var bool Hints the SDK if the plugin has any paid plans.
		 */
		private $_has_paid_plans;

		/**
		 * @since 1.0.7
		 * @var bool Hints the SDK if the plugin is WordPress.org compliant.
		 */
		private $_is_org_compliant;

		/**
		 * @since 1.0.7
		 * @var bool Hints the SDK if the plugin is has add-ons.
		 */
		private $_has_addons;

		/**
		 * @var FS_Key_Value_Storage
		 */
		private $_storage;

		/**
		 * @var Freemius[]
		 */
		private static $_instances = array();

		/**
		 * @var FS_Logger
		 * @since 1.0.0
		 */
		private $_logger;
		/**
		 * @var FS_Plugin
		 * @since 1.0.4
		 */
		private $_plugin = false;
		/**
		 * @var FS_Plugin
		 * @since 1.0.4
		 */
		private $_parent = false;
		/**
		 * @var FS_User
		 * @since 1.0.1
		 */
		private $_user = false;
		/**
		 * @var FS_Site
		 * @since 1.0.1
		 */
		private $_site = false;
		/**
		 * @var FS_Plugin_License
		 * @since 1.0.1
		 */
		private $_license;
		/**
		 * @var FS_Plugin_Plan[]
		 * @since 1.0.2
		 */
		private $_plans = false;
		/**
		 * @var FS_Plugin_License[]
		 * @since 1.0.5
		 */
		private $_licenses = false;

		/**
		 * @var FS_Admin_Notice_Manager
		 */
		private $_admin_notices;

		/**
		 * @var FS_Logger
		 * @since 1.0.0
		 */
		private static $_static_logger;

		/**
		 * @var FS_Option_Manager
		 * @since 1.0.2
		 */
		private static $_accounts;

		/* Ctor
------------------------------------------------------------------------------------------------------------------*/

		private function __construct( $slug ) {
			$this->_slug = $slug;

			$this->_logger = FS_Logger::get_logger( WP_FS__SLUG . '_' . $slug, WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );

			$this->_storage = FS_Key_Value_Storage::instance( 'plugin_data', $this->_slug );

			$this->_plugin_main_file_path = $this->_find_caller_plugin_file();
			$this->_plugin_dir_path       = plugin_dir_path( $this->_plugin_main_file_path );
			$this->_plugin_basename       = plugin_basename( $this->_plugin_main_file_path );
			$this->_free_plugin_basename  = str_replace( '-premium/', '/', $this->_plugin_basename );

			$base_name_split        = explode( '/', $this->_plugin_basename );
			$this->_plugin_dir_name = $base_name_split[0];

			if ( $this->_logger->is_on() ) {
				$this->_logger->info( 'plugin_main_file_path = ' . $this->_plugin_main_file_path );
				$this->_logger->info( 'plugin_dir_path = ' . $this->_plugin_dir_path );
				$this->_logger->info( 'plugin_basename = ' . $this->_plugin_basename );
				$this->_logger->info( 'free_plugin_basename = ' . $this->_free_plugin_basename );
				$this->_logger->info( 'plugin_dir_name = ' . $this->_plugin_dir_name );
			}

			// Remember link between file to slug.
			$this->store_file_slug_map();

			// Store plugin's initial install timestamp.
			if ( ! isset( $this->_storage->install_timestamp ) ) {
				$this->_storage->install_timestamp = WP_FS__SCRIPT_START_TIME;
			}

			$this->_plugin = FS_Plugin_Manager::instance( $this->_slug )->get();

			$this->_admin_notices = FS_Admin_Notice_Manager::instance(
				$slug,
				is_object( $this->_plugin ) ? $this->_plugin->title : ''
			);

			if ( 'true' === fs_request_get( 'fs_clear_api_cache' ) ) {
				FS_Api::clear_cache();
			}

			$this->_register_hooks();

			$this->_load_account();

			$this->_version_updates_handler();
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		private function _version_updates_handler() {
			if ( ! isset( $this->_storage->sdk_version ) || $this->_storage->sdk_version != $this->version ) {
				// Freemius version upgrade mode.
				$this->_storage->sdk_last_version = $this->_storage->sdk_version;
				$this->_storage->sdk_version      = $this->version;

				if ( empty( $this->_storage->sdk_last_version ) ||
				     version_compare( $this->_storage->sdk_last_version, $this->version, '<' )
				) {
					$this->_storage->sdk_upgrade_mode   = true;
					$this->_storage->sdk_downgrade_mode = false;
				} else {
					$this->_storage->sdk_downgrade_mode = true;
					$this->_storage->sdk_upgrade_mode   = false;

				}

				$this->do_action( 'sdk_version_update' );
			}

			$plugin_version = $this->get_plugin_version();
			if ( ! isset( $this->_storage->plugin_version ) || $this->_storage->plugin_version != $plugin_version ) {
				// Plugin version upgrade mode.
				$this->_storage->plugin_last_version = $this->_storage->plugin_version;
				$this->_storage->plugin_version      = $plugin_version;

				if ( empty( $this->_storage->plugin_last_version ) ||
				     version_compare( $this->_storage->plugin_last_version, $plugin_version, '<' )
				) {
					$this->_storage->plugin_upgrade_mode   = true;
					$this->_storage->plugin_downgrade_mode = false;
				} else {
					$this->_storage->plugin_downgrade_mode = true;
					$this->_storage->plugin_upgrade_mode   = false;
				}

				$this->do_action( 'plugin_version_update' );
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		private function _register_hooks() {
			if ( is_admin() ) {
				// Hook to plugin activation
				register_activation_hook( $this->_plugin_main_file_path, array(
					&$this,
					'_activate_plugin_event_hook'
				) );

				// Hook to plugin uninstall.
				register_uninstall_hook( $this->_plugin_main_file_path, array( 'Freemius', '_uninstall_plugin_hook' ) );

				if ( ! $this->is_ajax() ) {
					if ( ! $this->is_addon() ) {
						add_action( 'init', array( &$this, '_add_default_submenu_items' ), WP_FS__LOWEST_PRIORITY );
						add_action( 'admin_menu', array( &$this, '_prepare_admin_menu' ), WP_FS__LOWEST_PRIORITY );
					}
				}
			}

			register_deactivation_hook( $this->_plugin_main_file_path, array( &$this, '_deactivate_plugin_hook' ) );

			add_action( 'init', array( &$this, '_redirect_on_clicked_menu_link' ), WP_FS__LOWEST_PRIORITY );

			$this->add_action( 'after_plans_sync', array( &$this, '_check_for_trial_plans' ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		private function _register_account_hooks() {
			if ( is_admin() ) {
				if ( ! $this->is_ajax() ) {
					if ( $this->has_trial_plan() ) {
						$last_time_trial_promotion_shown = $this->_storage->get( 'trial_promotion_shown', false );
						if ( ! $this->_site->is_trial_utilized() &&
						     (
							     // Show promotion if never shown it yet and 24 hours after initial activation.
							     ( false === $last_time_trial_promotion_shown && $this->_storage->activation_timestamp < ( time() - WP_FS__TIME_24_HOURS_IN_SEC ) ) ||
							     // Show promotion in every 30 days.
							     ( is_numeric( $last_time_trial_promotion_shown ) && 30 * WP_FS__TIME_24_HOURS_IN_SEC < time() - $last_time_trial_promotion_shown ) )
						) {
							$this->add_action( 'after_init_plugin_registered', array( &$this, '_add_trial_notice' ) );
						}
					}
				}

//				$this->add_action( 'plugin_version_update', array( &$this, 'update_plugin_version_event' ));
			}
		}

		/**
		 * Leverage backtrace to find caller plugin file path.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return string
		 */
		private function _find_caller_plugin_file() {
			$bt              = debug_backtrace();
			$abs_path_lenght = strlen( ABSPATH );
			$i               = 1;
			while (
				$i < count( $bt ) - 1 &&
				// substr is used to prevent cases where a freemius folder appears
				// in the path. For example, if WordPress is installed on:
				//  /var/www/html/some/path/freemius/path/wordpress/wp-content/...
				( false !== strpos( substr( fs_normalize_path( $bt[ $i ]['file'] ), $abs_path_lenght ), '/freemius/' ) ||
				  fs_normalize_path( dirname( dirname( $bt[ $i ]['file'] ) ) ) !== fs_normalize_path( WP_PLUGIN_DIR ) )
			) {
				$i ++;
			}

			return $bt[ $i ]['file'];
		}

		#region Instance ------------------------------------------------------------------

		/**
		 * Main singleton instance.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 *
		 * @param $slug
		 *
		 * @return Freemius
		 */
		static function instance( $slug ) {
			$slug = strtolower( $slug );

			if ( ! isset( self::$_instances[ $slug ] ) ) {
				if ( 0 === count( self::$_instances ) ) {
					self::_load_required_static();
				}

				self::$_instances[ $slug ] = new Freemius( $slug );
			}

			return self::$_instances[ $slug ];
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param string|number $slug_or_id
		 *
		 * @return bool
		 */
		private static function has_instance( $slug_or_id ) {
			return ! is_numeric( $slug_or_id ) ?
				isset( self::$_instances[ strtolower( $slug_or_id ) ] ) :
				( false !== self::get_instance_by_id( $slug_or_id ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param $id
		 *
		 * @return false|Freemius
		 */
		static function get_instance_by_id( $id ) {
			foreach ( self::$_instances as $slug => $instance ) {
				if ( $id == $instance->get_id() ) {
					return $instance;
				}
			}

			return false;
		}

		/**
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @param $plugin_file
		 *
		 * @return false|Freemius
		 */
		static function get_instance_by_file( $plugin_file ) {
			$slug = self::find_slug_by_basename($plugin_file);

			return (false !== $slug) ?
				self::instance( $slug ) :
				false;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return false|Freemius
		 */
		function get_parent_instance() {
			return self::get_instance_by_id( $this->_plugin->parent_plugin_id );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param $slug_or_id
		 *
		 * @return bool|Freemius
		 */
		function get_addon_instance( $slug_or_id ) {
			return ! is_numeric( $slug_or_id ) ?
				self::instance( strtolower( $slug_or_id ) ) :
				self::get_instance_by_id( $slug_or_id );
		}

		#endregion ------------------------------------------------------------------

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return bool
		 */
		function is_parent_plugin_installed() {
			return self::has_instance( $this->_plugin->parent_plugin_id );
		}

		/**
		 * Check if add-on parent plugin in activation mode.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return bool
		 */
		function is_parent_in_activation() {
			$parent_fs = $this->get_parent_instance();
			if ( ! is_object( $parent_fs ) ) {
				return false;
			}

			return ( $parent_fs->is_activation_mode() );
		}

		/**
		 * Is plugin in activation mode.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return bool
		 */
		function is_activation_mode() {
			return (
				! $this->is_registered() &&
				( ! $this->enable_anonymous() ||
				  ( ! $this->is_anonymous() && ! $this->is_pending_activation() ) )
			);
		}

		/**
		 * Is user on plugin's admin activation page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.8
		 *
		 * @return bool
		 */
		function is_activation_page() {
			return isset( $_GET['page'] ) && ( strtolower( $this->_menu_slug ) === strtolower( $_GET['page'] ) );
		}

		private static $_statics_loaded = false;

		/**
		 * Load static resources.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 */
		private static function _load_required_static() {
			if ( self::$_statics_loaded ) {
				return;
			}

			self::$_static_logger = FS_Logger::get_logger( WP_FS__SLUG, WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );

			self::$_static_logger->entrance();

			self::$_accounts = FS_Option_Manager::get_manager( WP_FS__ACCOUNTS_OPTION_NAME, true );

			// Configure which Freemius powered plugins should be auto updated.
//			add_filter( 'auto_update_plugin', '_include_plugins_in_auto_update', 10, 2 );

			if ( WP_FS__DEV_MODE ) {
				add_action( 'admin_menu', array( 'Freemius', 'add_debug_page' ) );
			}

			self::$_statics_loaded = true;
		}

		#region Debugging ------------------------------------------------------------------

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.8
		 */
		static function add_debug_page() {
			self::$_static_logger->entrance();

			$hook = add_object_page(
				__( 'Freemius Debug', WP_FS__SLUG ),
				__( 'Freemius Debug', WP_FS__SLUG ),
				'manage_options',
				WP_FS__SLUG,
				array( 'Freemius', '_debug_page_render' )
			);

			add_action( "load-$hook", array( 'Freemius', '_debug_page_actions' ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.8
		 */
		static function _debug_page_actions() {
			if ( fs_request_is_action( 'delete_all_accounts' ) ) {
				check_admin_referer( 'delete_all_accounts' );

				self::$_accounts->clear( true );

				return;
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.8
		 */
		static function _debug_page_render() {
			self::$_static_logger->entrance();

			$sites          = self::get_all_sites();
			$users          = self::get_all_users();
			$addons         = self::get_all_addons();
			$account_addons = self::get_all_account_addons();

//			$plans    = self::get_all_plans();
//			$licenses = self::get_all_licenses();

			$vars = array(
				'sites'          => $sites,
				'users'          => $users,
				'addons'         => $addons,
				'account_addons' => $account_addons,
			);
			fs_require_once_template( 'debug.php', $vars );
		}

		#endregion ------------------------------------------------------------------

		#region Connectivity Issues ------------------------------------------------------------------

		/**
		 * Check if Freemius should be turned on for the current plugin install + version combination. The API query will be only invoked once per plugin version (cached locally).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		private function is_on() {
			self::$_static_logger->entrance();

			if ( isset( $this->_is_on ) ) {
				return $this->_is_on;
			}

			// If already installed then sure it's on :)
			if ( $this->is_registered() ) {
				$this->_is_on = true;

				return $this->_is_on;
			}

			$version = $this->get_plugin_version();

			if ( isset( $this->_storage->is_on ) ) {
				if ( $version == $this->_storage->is_on['version'] ) {
					$this->_is_on = $this->_storage->is_on['is_active'];

					return $this->_is_on;
				}
			}

			// Defaults to new install.
			$is_update = false;
			$is_update = $this->apply_filters( 'is_plugin_update', $is_update );

			/**
			 * Check anonymously if the SDK should be currently activated.
			 * The logic is based on whether the developer turned Freemius off,
			 * or set a limit to the number of activations. It's not related to
			 * any private information of the current WordPress instance.
			 *
			 * Note:
			 * Only the plugin's public key is being shared with the endpoint.
			 * NO private nor sensitive information is being shared.
			 */
			$result = $this->get_api_plugin_scope()->get(
				'is_active.json?is_update=' . json_encode( $is_update )
			);

			$is_active = ! isset( $result->error ) &&
			             isset( $result->is_active ) &&
			             is_bool( $result->is_active ) ?
				$result->is_active :
				false;

			$this->_storage->is_on = array(
				'is_active' => $is_active,
				'timestamp' => WP_FS__SCRIPT_START_TIME,
				'version'   => $version,
			);

			$this->_is_on = $is_active;

			return $this->_is_on;
		}

		/**
		 * Check if there's any connectivity issue to Freemius API.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		private function has_api_connectivity() {
			if ( isset( $this->_has_api_connection ) ) {
				return $this->_has_api_connection;
			}

			$version = $this->get_plugin_version();

			if (WP_FS__SIMULATE_NO_API_CONNECTIVITY &&
			    isset( $this->_storage->connectivity_test ) &&
			    true === $this->_storage->connectivity_test['is_connected']
			) {
				unset( $this->_storage->connectivity_test );
			}

			if ( isset( $this->_storage->connectivity_test ) ) {
				if ( $version == $this->_storage->connectivity_test['version'] &&
				     $_SERVER['HTTP_HOST'] == $this->_storage->connectivity_test['host'] &&
				     $_SERVER['SERVER_ADDR'] == $this->_storage->connectivity_test['server_ip']
				) {
					$this->_has_api_connection = $this->_storage->connectivity_test['is_connected'];

					return $this->_has_api_connection;
				}
			}

			$is_connected = WP_FS__SIMULATE_NO_API_CONNECTIVITY ?
				false :
				$this->get_api_plugin_scope()->test();

			if ( ! $is_connected ) {
				$this->_add_connectivity_issue_message();
			}

			$this->_storage->connectivity_test = array(
				'is_connected' => $is_connected,
				'host' => $_SERVER['HTTP_HOST'],
				'server_ip' => $_SERVER['SERVER_ADDR'],
				'version' => $version,
			);

			$this->_has_api_connection = $is_connected;

			return $this->_has_api_connection;
		}

		/**
		 * Generate API connectivity issue message.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		function _add_connectivity_issue_message() {
			if ( ! function_exists( 'wp_nonce_url' ) ) {
				require_once( ABSPATH . 'wp-includes/functions.php' );
			}
			if ( ! function_exists( 'wp_get_current_user' ) ) {
				require_once( ABSPATH . 'wp-includes/pluggable.php' );
			}

			$current_user = wp_get_current_user();
//			$admin_email = get_option( 'admin_email' );
			$admin_email = $current_user->user_email;

			$ping = $this->get_api_plugin_scope()->ping();

			if ( is_object( $ping ) &&
			     isset( $ping->error ) &&
			     'cloudflare_ddos_protection' === $ping->error->code
			) {
				$message = __( 'From unknown reason, CloudFlare, the firewall we use, blocks the connection.', WP_FS__SLUG );
			} else {
				$message = __( 'From unknown reason, the API connectivity test fails.', WP_FS__SLUG );
			}

			$this->_admin_notices->add_sticky(
				sprintf(
					__( '%s requires an access to our API.', WP_FS__SLUG ) . ' ' .
					$message . ' ' .
					__( 'We are sure it\'s an issue on our side and more than happy to resolve it for you ASAP if you give us a chance.', WP_FS__SLUG ) .
					' %s',
					'<b>' . $this->get_plugin_name() . '</b>',
					sprintf(
						'<ol id="fs_firewall_issue_options"><li>%s</li><li>%s</li><li>%s</li></ol>',
						sprintf(
							'<a class="fs-resolve" href="#"><b>%s</b></a>%s',
							__( 'Yes - I\'m giving you a chance to fix it', WP_FS__SLUG ),
							' - ' . sprintf(
								__( 'We will do our best to whitelist your server and resolve this issue ASAP. You will get a follow-up email to %s once we have an update.', WP_FS__SLUG ),
								'<a href="mailto:' . $admin_email . '">' . $admin_email . '</a>'
							)
						),
						sprintf(
							'<a href="%s" target="_blank"><b>%s</b></a>%s',
							sprintf( 'https://wordpress.org/plugins/%s/download/', $this->_slug ),
							__( 'Let\'s try your previous version', WP_FS__SLUG ),
							' - ' . __( 'Uninstall this version and install the previous one.', WP_FS__SLUG )
						),
						sprintf(
							'<a href="%s"><b>%s</b></a>%s',
							wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $this->_plugin_basename . '&amp;plugin_status=' . 'all' . '&amp;paged=' . '1' . '&amp;s=' . '', 'deactivate-plugin_' . $this->_plugin_basename ),
							__( 'That\'s exhausting, please deactivate', WP_FS__SLUG ),
							' - ' . __( 'We feel your frustration and sincerely apologize for the inconvenience. Hope to see you again in the future.', WP_FS__SLUG )
						)
					)
				),
				'failed_connect_api',
				'Oops...',
				'error'
			);

//				add_action( "wp_ajax_{$this->_slug}_deactivate_plugin", array( &$this, 'send_affiliate_application' ) );
		}

		/**
		 * Get collection of all active plugins.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return array[string]array
		 */
		private function get_active_plugins() {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}

			$active_plugin            = array();
			$all_plugins              = get_plugins();
			$active_plugins_basenames = get_option( 'active_plugins' );

			foreach ( $active_plugins_basenames as $plugin_basename ) {
				$active_plugin[ $plugin_basename ] = $all_plugins[ $plugin_basename ];
			}

			return $active_plugin;
		}

		/**
		 * Handle user request to resolve connectivity issue.
		 * This method will send an email to Freemius API technical staff for resolution.
		 * The email will contain server's info and installed plugins (might be caching issue).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		function _email_about_firewall_issue()
		{
			$this->_admin_notices->remove_sticky('failed_connect_api');

			$active_plugin = $this->get_active_plugins();
			$active_plugin_string = '';
			foreach ($active_plugin as $plugin)
			{
				$active_plugin_string .= sprintf(
					'<a href="%s">%s</a> [v%s]<br>',
					$plugin['PluginURI'],
					$plugin['Name'],
					$plugin['Version']
				);
			}

			if ( ! function_exists( 'wp_get_current_user' ) ) {
				require_once( ABSPATH . 'wp-includes/pluggable.php' );
			}

			$curl_version = curl_version();
			$current_user = wp_get_current_user();
//			$admin_email = get_option( 'admin_email' );
			$admin_email = $current_user->user_email;

			$ping = $this->get_api_plugin_scope()->ping();

			// Send email with technical details to resolve CloudFlare's firewall unnecessary protection.
			wp_mail(
				'api@freemius.com',
				'API Connectivity Issue [' . $this->get_plugin_name() . ']',
				sprintf('<table>
	<thead>
		<tr><th colspan="2" style="text-align: left; background: #333; color: #fff; padding: 5px;">SDK</th></tr>
	</thead>
	<tbody>
		<tr><td><b>FS Version:</b></td><td>%s</td></tr>
		<tr><td><b>cURL Version:</b></td><td>%s</td></tr>
	</tbody>
	<thead>
		<tr><th colspan="2" style="text-align: left; background: #333; color: #fff; padding: 5px;">Plugin</th></tr>
	</thead>
	<tbody>
		<tr><td><b>Name:</b></td><td>%s</td></tr>
		<tr><td><b>Version:</b></td><td>%s</td></tr>
	</tbody>
	<thead>
		<tr><th colspan="2" style="text-align: left; background: #333; color: #fff; padding: 5px;">Site</th></tr>
	</thead>
	<tbody>
		<tr><td><b>Address:</b></td><td>%s</td></tr>
		<tr><td><b>HTTP_HOST:</b></td><td>%s</td></tr>
		<tr><td><b>SERVER_ADDR:</b></td><td>%s</td></tr>
	</tbody>
	<thead>
		<tr><th colspan="2" style="text-align: left; background: #333; color: #fff; padding: 5px;">User</th></tr>
	</thead>
	<tbody>
		<tr><td><b>Email:</b></td><td><a href="mailto:%s">%s</a></td></tr>
		<tr><td><b>First:</b></td><td>%s</td></tr>
		<tr><td><b>Last:</b></td><td>%s</td></tr>
	</tbody>
	<thead>
		<tr><th colspan="2" style="text-align: left; background: #333; color: #fff; padding: 5px;">Plugins</th></tr>
	</thead>
	<tbody>
		<tr><td style="vertical-align: top"><b>Active Plugins:</b></td><td>%s</td></tr>
	</tbody>
	<thead>
		<tr><th colspan="2" style="text-align: left; background: #333; color: #fff; padding: 5px;">API Error</th></tr>
	</thead>
	<tbody>
		<tr><td colspan="2">%s</td></tr>
	</tbody>
</table>',
					$this->version,
					$curl_version['version'],
					$this->get_plugin_name(),
					$this->get_plugin_version(),
					site_url(),
					!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
					!empty($_SERVER['SERVER_ADDR']) ? '<a href="http://www.projecthoneypot.org/ip_' . $_SERVER['SERVER_ADDR'] . '">' . $_SERVER['SERVER_ADDR'] . '</a>' : '',
					$admin_email,
					$admin_email,
					$current_user->user_firstname,
					$current_user->user_lastname,
					$active_plugin_string,
					(is_string($ping) ? $ping : json_encode($ping))
				),
				"Content-type: text/html\r\n" .
		        "Reply-To: $admin_email <$admin_email>"
			);

			$this->_admin_notices->add_sticky(
				sprintf(
					__('Thank for giving us the chance to fix it! A message was just sent to our technical staff. We will get back to you as soon as we have an update to %s. Appreciate your patience.', WP_FS__SLUG),
					'<a href="mailto:' . $admin_email . '">' . $admin_email . '</a>'
				),
				'server_details_sent'
			);

			// Action was taken, tell that API connectivity troubleshooting should be off now.

			echo "1";
			exit;
		}

		static function _add_firewall_issues_javascript()
		{
			$params = array();
			fs_require_once_template( 'firewall-issues-js.php', $params );
		}

		#endregion Connectivity Issues ------------------------------------------------------------------

		#region Initialization ------------------------------------------------------------------

		/**
		 * Init plugin's Freemius instance.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @param number $id
		 * @param string $public_key
		 * @param bool   $is_live
		 * @param bool   $is_premium
		 */
		function init( $id, $public_key, $is_live = true, $is_premium = true ) {
			$this->_logger->entrance();

			$this->dynamic_init( array(
				'id'         => $id,
				'public_key' => $public_key,
				'is_live'    => $is_live,
				'is_premium' => $is_premium,
			) );
		}

		private function _get_option( &$options, $key, $default = false ) {
			return ! empty( $options[ $key ] ) ? $options[ $key ] : $default;
		}

		private function _get_bool_option( &$options, $key, $default = false ) {
			return isset( $options[ $key ] ) && is_bool( $options[ $key ] ) ? $options[ $key ] : $default;
		}

		private function _get_numeric_option( &$options, $key, $default = false ) {
			return isset( $options[ $key ] ) && is_numeric( $options[ $key ] ) ? $options[ $key ] : $default;
		}

		/**
		 * Dynamic initiator, originally created to support initiation
		 * with parent_id for add-ons.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param array $plugin_info
		 *
		 * @throws Freemius_Exception
		 */
		function dynamic_init( array $plugin_info ) {
			$this->_logger->entrance();

			$id          = $this->_get_numeric_option( $plugin_info, 'id', false );
			$public_key  = $this->_get_option( $plugin_info, 'public_key', false );
			$secret_key  = $this->_get_option( $plugin_info, 'secret_key', null );
			$parent_id   = $this->_get_numeric_option( $plugin_info, 'parent_id', null );
			$parent_name = $this->_get_option( $plugin_info, 'parent_name', null );

			if ( isset( $plugin_info['parent'] ) ) {
				$parent_id = $this->_get_numeric_option( $plugin_info['parent'], 'id', null );
//				$parent_slug       = $this->_get_option( $plugin_info['parent'], 'slug', null );
//				$parent_public_key = $this->_get_option( $plugin_info['parent'], 'public_key', null );
				$parent_name = $this->_get_option( $plugin_info['parent'], 'name', null );
			}

			if ( false === $id ) {
				throw new Freemius_Exception( 'Plugin id parameter is not set.' );
			}
			if ( false === $public_key ) {
				throw new Freemius_Exception( 'Plugin public_key parameter is not set.' );
			}

			$plugin = ( $this->_plugin instanceof FS_Plugin ) ?
				$this->_plugin :
				new FS_Plugin();

			$plugin->update( array(
				'id'               => $id,
				'public_key'       => $public_key,
				'slug'             => $this->_slug,
				'parent_plugin_id' => $parent_id,
				'version'          => $this->get_plugin_version(),
				'title'            => $this->get_plugin_name(),
				'file'             => $this->_free_plugin_basename,
				'is_premium'       => $this->_get_bool_option( $plugin_info, 'is_premium', true ),
				'is_live'          => $this->_get_bool_option( $plugin_info, 'is_live', true ),
//				'secret_key' => $secret_key,
			) );

			if ( $plugin->is_updated() ) {
				// Update plugin details.
				$this->_plugin = FS_Plugin_Manager::instance( $this->_slug )->store( $plugin );
			}
			$this->_plugin->secret_key = $secret_key;

			$this->_menu_slug        = plugin_basename( isset( $plugin_info['menu_slug'] ) ? $plugin_info['menu_slug'] : $this->_slug );
			$this->_has_addons       = $this->_get_bool_option( $plugin_info, 'has_addons', false );
			$this->_has_paid_plans   = $this->_get_bool_option( $plugin_info, 'has_paid_plans', true );
			$this->_is_org_compliant = $this->_get_bool_option( $plugin_info, 'is_org_compliant', true );
			$this->_enable_anonymous = $this->_get_bool_option( $plugin_info, 'enable_anonymous', true );

			if (!$this->is_registered()) {
				if ( ! $this->has_api_connectivity() ) {
					if ( is_admin() && $this->_admin_notices->has_sticky( 'failed_connect_api' ) ) {
						add_action( 'admin_footer', array( 'Freemius', '_add_firewall_issues_javascript' ) );

						add_action( "wp_ajax_{$this->_slug}_resolve_firewall_issues", array(
							&$this,
							'_email_about_firewall_issue'
						) );
					}

					// Turn Freemius off.
					$this->_is_on = false;

					return;
				}

				// Check if Freemius is on for the current plugin.
				// This MUST be executed after all the plugin variables has been loaded.
				if ( ! $this->is_on() ) {
					return;
				}
			}

			if ( false === $this->_background_sync() ) {
				// If background sync wasn't executed,
				// and if the plugin declared it has add-ons but
				// no add-ons found in the local data, then try to sync add-ons.
				if ( $this->_has_addons &&
				     ! $this->is_addon() &&
				     ( false === $this->get_addons() )
				) {
					$this->_sync_addons();
				}
			}


			if ( is_admin() ) {
				if ( $this->is_addon() ) {
					if ( ! $this->is_parent_plugin_installed() ) {
						$this->_admin_notices->add(
							( is_string( $parent_name ) ?
								sprintf( __( '%s cannot run without %s.', WP_FS__SLUG ), $this->get_plugin_name(), $parent_name ) :
								sprintf( __( '%s cannot run without the plugin.', WP_FS__SLUG ), $this->get_plugin_name() )
							),
							__( 'Oops...', WP_FS__SLUG ),
							'error'
						);

						return;
					} else {
						$parent_fs = self::get_instance_by_id( $parent_id );

						// Get parent plugin reference.
						$this->_parent = $parent_fs->get_plugin();

						if ( $parent_fs->is_registered() && ! $this->is_registered() ) {
							// If parent plugin activated, automatically install add-on for the user.
							$this->_activate_addon_account( $parent_fs );
						}
					}
				} else {
					add_action( 'admin_init', array( &$this, '_admin_init_action' ) );

					if ( $this->_has_addons() &&
					     'plugin-information' === fs_request_get( 'tab', false ) &&
					     $this->get_id() == fs_request_get( 'parent_plugin_id', false )
					) {
						// Remove default plugin information action.
						remove_all_actions( 'install_plugins_pre_plugin-information' );

						require_once WP_FS__DIR_INCLUDES . '/fs-plugin-functions.php';

						// Override action with custom plugins function for add-ons.
						add_action( 'install_plugins_pre_plugin-information', 'fs_install_plugin_information' );

						// Override request for plugin information for Add-ons.
						add_filter( 'plugins_api', array( &$this, '_get_addon_info_filter' ), 10, 3 );
					} else {
						if ( $this->is_paying() || $this->_has_addons() ) {
							new FS_Plugin_Updater( $this );
						}
					}
				}

//				if ( $this->is_registered() ||
//				     $this->is_anonymous() ||
//				     $this->is_pending_activation()
//				) {
//					$this->_init_admin();
//				}
			}

			$this->do_action( 'initiated' );

			if ( ! $this->is_addon() ) {
				if ( $this->is_registered() ) {
					// Fix for upgrade from versions < 1.0.9.
					if ( ! isset( $this->_storage->activation_timestamp ) ) {
						$this->_storage->activation_timestamp = WP_FS__SCRIPT_START_TIME;
					}
					if ( $this->_storage->prev_is_premium !== $this->_plugin->is_premium ) {
						if ( isset( $this->_storage->prev_is_premium ) ) {
							add_action( is_admin() ? 'admin_init' : 'init', array(
								&$this,
								'_plugin_code_type_changed'
							) );
						} else {
							// Set for code type for the first time.
							$this->_storage->prev_is_premium = $this->_plugin->is_premium;
						}
					}

					$this->do_action( 'after_init_plugin_registered' );
				} else if ( $this->is_anonymous() ) {
					$this->do_action( 'after_init_plugin_anonymous' );
				} else if ( $this->is_pending_activation() ) {
					$this->do_action( 'after_init_plugin_pending_activations' );
				}
			} else {
				if ( $this->is_registered() ) {
					$this->do_action( 'after_init_addon_registered' );
				} else if ( $this->is_anonymous() ) {
					$this->do_action( 'after_init_addon_anonymous' );
				} else if ( $this->is_pending_activation() ) {
					$this->do_action( 'after_init_addon_pending_activations' );
				}
			}
		}

		/**
		 * Handles plugin's code type change (free <--> premium).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		function _plugin_code_type_changed() {
			// Send code type changes event.
			$this->get_api_site_scope()->call( '/', 'put', array( 'is_premium' => $this->_plugin->is_premium ) );

			if ( true === $this->_plugin->is_premium ) {
				// Activated premium code.
				$this->do_action( 'after_premium_version_activation' );

				// Remove all sticky messages related to download of the premium version.
				$this->_admin_notices->remove_sticky( array(
					'trial_started',
					'plan_upgraded',
					'plan_changed',
				) );

				$this->_admin_notices->add_sticky(
					__( 'Premium plugin version was successfully activated.', WP_FS__SLUG ),
					'premium_activated',
					__( 'W00t!', WP_FS__SLUG )
				);
			} else {
				// Activated free code (after had the premium before).
				$this->do_action( 'after_free_version_reactivation' );

				if ( $this->is_paying() && !$this->is_premium() ) {
					$this->_admin_notices->add_sticky(
						sprintf(
							__( 'You have a %s license.', WP_FS__SLUG ),
							$this->_site->plan->title
						) . ' ' . $this->_get_latest_download_link( sprintf(
							__( 'Download %s version now', WP_FS__SLUG ),
							$this->_site->plan->title
						) ),
						'plan_upgraded',
						__( 'Ye-ha!', WP_FS__SLUG )
					);
				}
			}

			// Update is_premium of latest version.
			$this->_storage->prev_is_premium = $this->_plugin->is_premium;
		}

		#endregion ------------------------------------------------------------------

		#region Add-ons -------------------------------------------------------------------------

		/**
		 * Generate add-on plugin information.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param array         $data
		 * @param string        $action
		 * @param object|null   $args
		 *
		 * @return array|null
		 */
		function _get_addon_info_filter( $data, $action = '', $args = null ) {
			$this->_logger->entrance();

			$parent_plugin_id = fs_request_get( 'parent_plugin_id', false );

			if ( $this->get_id() != $parent_plugin_id ||
			     ( 'plugin_information' !== $action ) ||
			     ! isset( $args->slug )
			) {
				return $data;
			}

			// Find add-on by slug.
			$addons         = $this->get_addons();
			$selected_addon = false;
			foreach ( $addons as $addon ) {
				if ( $addon->slug == $args->slug ) {
					$selected_addon = $addon;
					break;
				}
			}

			if ( false === $selected_addon ) {
				return $data;
			}

			if ( ! isset( $selected_addon->info ) ) {
				// Setup some default info.
				$selected_addon->info                  = new stdClass();
				$selected_addon->info->selling_point_0 = 'Selling Point 1';
				$selected_addon->info->selling_point_1 = 'Selling Point 2';
				$selected_addon->info->selling_point_2 = 'Selling Point 3';
				$selected_addon->info->description     = '<p>Tell your users all about your add-on</p>';
			}

			fs_enqueue_local_style( 'fs_addons', '/admin/add-ons.css' );

			$data = $args;

			// Fetch as much as possible info from local files.
			$plugin_local_data = $this->get_plugin_data();
			$data->name        = $selected_addon->title;
			$data->author      = $plugin_local_data['Author'];
			$view_vars         = array( 'plugin' => $selected_addon );
			$data->sections    = array(
				'description' => fs_get_template( '/plugin-info/description.php', $view_vars ),
			);

			if ( ! empty( $selected_addon->info->banner_url ) ) {
				$data->banners = array(
					'low' => $selected_addon->info->banner_url,
				);
			}

			if ( ! empty( $selected_addon->info->screenshots ) ) {
				$view_vars                     = array( 'screenshots' => $selected_addon->info->screenshots );
				$data->sections['screenshots'] = fs_get_template( '/plugin-info/screenshots.php', $view_vars );
			}

			// Load add-on pricing.
			$has_pricing  = false;
			$has_features = false;
			$plans        = false;
			$plans_result = $this->get_api_site_or_plugin_scope()->get( "/addons/{$selected_addon->id}/plans.json" );
			if ( ! isset( $plans_result->error ) ) {
				$plans = $plans_result->plans;
				if ( is_array( $plans ) ) {
					foreach ( $plans as &$plan ) {
						$pricing_result = $this->get_api_site_or_plugin_scope()->get( "/addons/{$selected_addon->id}/plans/{$plan->id}/pricing.json" );
						if ( ! isset( $pricing_result->error ) ) {
							// Update plan's pricing.
							$plan->pricing = $pricing_result->pricing;

							$has_pricing = true;
						}

						$features_result = $this->get_api_site_or_plugin_scope()->get( "/addons/{$selected_addon->id}/plans/{$plan->id}/features.json" );
						if ( ! isset( $features_result->error ) &&
						     is_array( $features_result->features ) &&
						     0 < count( $features_result->features )
						) {
							// Update plan's pricing.
							$plan->features = $features_result->features;

							$has_features = true;
						}
					}
				}
			}

			// Get latest add-on version.
			$latest = $this->_fetch_latest_version( $selected_addon->id );

			if ( is_object( $latest ) ) {
				$data->version      = $latest->version;
				$data->last_updated = ! is_null( $latest->updated ) ? $latest->updated : $latest->created;
				$data->requires     = $latest->requires_platform_version;
				$data->tested       = $latest->tested_up_to_version;
			} else {
				// Add dummy version.
				$data->version = '1.0.0';

				// Add message to developer to deploy the plugin through Freemius.
			}

			$data->checkout_link = $this->checkout_url();
			$data->download_link = 'https://dummy.com';

			if ( $has_pricing ) {
				// Add plans to data.
				$data->plans = $plans;

				if ( $has_features ) {
					$view_vars                  = array( 'plans' => $plans );
					$data->sections['features'] = fs_get_template( '/plugin-info/features.php', $view_vars );
				}
			}

			return $data;
		}

		/**
		 * Check if add-on installed and activated on site.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param string|number $slug_or_id
		 *
		 * @return bool
		 */
		function is_addon_activated( $slug_or_id ) {
			return self::has_instance( $slug_or_id );
		}

		/**
		 * Determines if add-on installed.
		 *
		 * NOTE: This is a heuristic and only works if the folder/file named as the slug.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param string $slug
		 *
		 * @return bool
		 */
		function is_addon_installed( $slug ) {
			return file_exists( fs_normalize_path( WP_PLUGIN_DIR . '/' . $this->get_addon_basename( $slug ) ) );
		}

		/**
		 * Get add-on basename.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param string $slug
		 *
		 * @return string
		 */
		function get_addon_basename( $slug ) {
			if ( $this->is_addon_activated( $slug ) ) {
				self::instance( $slug )->get_plugin_basename();
			}

			return $slug . '/' . $slug . '.php';
		}

		/**
		 * Get installed add-ons instances.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return Freemius[]
		 */
		function get_installed_addons() {
			$installed_addons = array();
			foreach ( self::$_instances as $slug => $instance ) {
				if ( $instance->is_addon() && is_object($instance->_parent) ) {
					if ( $this->_plugin->id == $instance->_parent->id ) {
						$installed_addons[] = $instance;
					}
				}
			}

			return $installed_addons;
		}

		/**
		 * Tell Freemius that the current plugin is an add-on.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param number $parent_plugin_id The parent plugin ID
		 */
		function init_addon( $parent_plugin_id ) {
			$this->_plugin->parent_plugin_id = $parent_plugin_id;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return bool
		 */
		function is_addon() {
			return isset( $this->_plugin->parent_plugin_id ) && is_numeric( $this->_plugin->parent_plugin_id );
		}

		#endregion ------------------------------------------------------------------

		/**
		 * Set Freemius into sandbox mode for debugging.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param string $secret_key
		 */
		function init_sandbox( $secret_key ) {
			$this->_plugin->secret_key = $secret_key;

			// Update plugin details.
			FS_Plugin_Manager::instance( $this->_slug )->update( $this->_plugin, true );
		}

		/**
		 * Check if running payments in sandbox mode.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @return bool
		 */
		function is_payments_sandbox() {
			return ( ! $this->is_live() ) || isset( $this->_plugin->secret_key );
		}

		/**
		 * Check if running test vs. live plugin.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @return bool
		 */
		function is_live() {
			return $this->_plugin->is_live;
		}

		/**
		 * Check if the user skipped connecting the account with Freemius.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return bool
		 */
		function is_anonymous() {
			return $this->_storage->get( 'is_anonymous', false );
		}

		/**
		 * Check if user connected his account and install pending email activation.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return bool
		 */
		function is_pending_activation() {
			return $this->_storage->get( 'is_pending_activation', false );
		}

		/**
		 * Check if plugin must be WordPress.org compliant.
		 *
		 * @since 1.0.7
		 *
		 * @return bool
		 */
		function is_org_repo_compliant() {
			return $this->_is_org_compliant;
		}

		/**
		 * Background sync every 24 hours.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @return bool If function actually executed the sync in this iteration.
		 */
		private function _background_sync() {
			$this->_logger->entrance();

			// Don't sync license on AJAX calls.
			if ( $this->is_ajax() ) {
				return false;
			}

			// Asked to sync explicitly, no need for background sync.
			if ( fs_request_is_action( $this->_slug . '_sync_license' ) ) {
				return false;
			}

			$sync_timestamp = $this->_storage->get( 'sync_timestamp' );

			if ( ! is_numeric( $sync_timestamp ) || $sync_timestamp >= time() ) {
				// If updated not set or happens to be in the future, set as if was 24 hours earlier.
				$sync_timestamp                 = time() - WP_FS__TIME_24_HOURS_IN_SEC;
				$this->_storage->sync_timestamp = $sync_timestamp;
			}

			if ( /*( defined( 'WP_FS__DEV_MODE' ) && WP_FS__DEV_MODE && ) ||*/
			( $sync_timestamp <= time() - WP_FS__TIME_24_HOURS_IN_SEC )
			) {

				if ( $this->is_registered() ) {
					// Initiate background plan sync.
					$this->_sync_license( true );

					// Check for plugin updates.
					$this->_check_updates( true );
				}

				if ( ! $this->is_addon() ) {
					if ( $this->is_registered() || $this->_has_addons ) {
						// Try to fetch add-ons if registered or if plugin
						// declared that it has add-ons.
						$this->_sync_addons();
					}
				}

				// Update last sync timestamp.
				$this->_storage->sync_timestamp = time();

				return true;
			}

			return false;
		}

		/**
		 * Show a notice that activation is currently pending.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @param bool|string $email
		 */
		function _add_pending_activation_notice( $email = false ) {
			if ( ! is_string( $email ) ) {
				$current_user = wp_get_current_user();
				$email        = $current_user->user_email;
			}

			$this->_admin_notices->add_sticky(
				sprintf( __( 'You should receive an activation email for %s to your mailbox at %s. Please make sure you click the activation button in that email to complete the install.', WP_FS__SLUG ), '<b>' . $this->get_plugin_name() . '</b>', '<b>' . $email . '</b>' ),
				'activation_pending',
				'Thanks!'
			);
		}

		/**
		 *
		 * NOTE: admin_menu action executed before admin_init.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 */
		function _admin_init_action() {
			// Automatically redirect to connect/activation page after plugin activation.
			if ( get_option( "fs_{$this->_slug}_activated", false ) ) {
				delete_option( "fs_{$this->_slug}_activated" );
				$this->_redirect_on_activation_hook();

				return;
			}

			if ( fs_request_is_action( $this->_slug . '_skip_activation' ) ) {
				check_admin_referer( $this->_slug . '_skip_activation' );
				$this->_storage->is_anonymous = true;
				if ( fs_redirect( $this->_get_admin_page_url() ) ) {
					exit();
				}
			}

			if ( ! $this->is_addon() && ! $this->is_registered() && ! $this->is_anonymous() ) {
				if ( ! $this->is_pending_activation() ) {
					if ( ! $this->is_activation_page() ) {
						$activation_url = $this->_get_admin_page_url();

						$this->_admin_notices->add(
							sprintf( __( 'You are just one step away - %1sActivate "' . $this->get_plugin_name() . '" Now%2s', WP_FS__SLUG ), '<a href="' . $activation_url . '"><b>', '</b></a>' ),
							'',
							'update-nag'
						);
					}
				}
			}

			$this->_add_upgrade_action_link();
		}

		/**
		 * Return current page's URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return string
		 */
		function current_page_url() {
			$url = 'http';

			if ( isset( $_SERVER["HTTPS"] ) ) {
				if ( $_SERVER["HTTPS"] == "on" ) {
					$url .= "s";
				}
			}
			$url .= "://";
			if ( $_SERVER["SERVER_PORT"] != "80" ) {
				$url .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
			} else {
				$url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
			}

			return esc_url( $url );
		}

		/**
		 * Check if the current page is the plugin's main admin settings page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return bool
		 */
		function _is_plugin_page() {
			return fs_is_plugin_page( $this->_menu_slug );
		}

		/* Events
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * Delete site install from Database.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @param bool $store
		 */
		function _delete_site( $store = true ) {
			$sites = self::get_all_sites();

			if ( isset( $sites[ $this->_slug ] ) ) {
				unset( $sites[ $this->_slug ] );
			}

			self::$_accounts->set_option( 'sites', $sites, $store );
		}

		/**
		 * Delete plugin's plans information.
		 *
		 * @param bool $store Flush to Database if true.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		private function _delete_plans( $store = true ) {
			$this->_logger->entrance();

			$plans = self::get_all_plans();

			unset( $plans[ $this->_slug ] );

			self::$_accounts->set_option( 'plans', $plans, $store );
		}

		/**
		 * Delete all plugin licenses.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param bool        $store
		 * @param string|bool $plugin_slug
		 */
		private function _delete_licenses( $store = true, $plugin_slug = false ) {
			$this->_logger->entrance();

			$all_licenses = self::get_all_licenses();

			if ( ! is_string( $plugin_slug ) ) {
				$plugin_slug = $this->_slug;
			}

			unset( $all_licenses[ $plugin_slug ] );

			self::$_accounts->set_option( 'licenses', $all_licenses, $store );
		}

		/**
		 * Plugin activated hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 * @uses   FS_Api
		 */
		function _activate_plugin_event_hook() {
			$this->_logger->entrance( 'slug = ' . $this->_slug );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			// Clear API cache on activation.
			FS_Api::clear_cache();

			if ( $this->is_registered() ) {
				// Send re-activation event.
				$this->get_api_site_scope()->call( '/', 'put', array(
					'is_active' => true,
					'is_premium' => $this->is_premium(),
					// Send version on activation.
					'version' => $this->get_plugin_version(),
				) );

				/**
				 * @todo Work on automatic deactivation of the Free plugin version. It doesn't work since the slug of the free & premium versions is identical. Therefore, only one instance of Freemius is created and the activation hook of the premium version is not being added.
				 */
				if ( $this->_plugin_basename !== $this->_free_plugin_basename ) {
					// Deactivate Free plugin version on premium plugin activation.
					deactivate_plugins( $this->_free_plugin_basename );

					$this->_admin_notices->add(
						sprintf( __( 'The upgrade of %s was successfully completed.' ), sprintf( '<b>%s</b>', $this->_plugin->title ) ),
						__( 'W00t!' )
					);
				}
			} else {
				// @todo Implement "bounce rate" by calculating number of plugin activations without registration.
			}

			if ($this->has_api_connectivity()) {
				// Store hint that the plugin was just activated to enable auto-redirection to settings.
				add_option( "fs_{$this->_slug}_activated", true );
			}
		}

		/**
		 * Delete account.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @param bool $check_user Enforce checking if user have plugins activation privileges.
		 */
		function delete_account_event($check_user = true) {
			$this->_logger->entrance( 'slug = ' . $this->_slug );

			if ( $check_user && ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$this->do_action( 'before_account_delete' );

			// Clear all admin notices.
			$this->_admin_notices->clear_all_sticky();

			$this->_delete_site( false );

			$this->_delete_plans( false );

			$this->_delete_licenses( false );

			// Delete add-ons related to plugin's account.
			$this->_delete_account_addons( false );

			// @todo Delete plans and licenses of add-ons.

			self::$_accounts->store();

			// Clear all storage data.
			$this->_storage->clear_all(true, array(
				'connectivity_test',
				'is_on',
			));

			// Send delete event.
			$this->get_api_site_scope()->call( '/', 'delete' );

			$this->do_action( 'after_account_delete' );
		}

		/**
		 * Plugin deactivation hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 */
		function _deactivate_plugin_hook() {
			$this->_logger->entrance( 'slug = ' . $this->_slug );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$this->_admin_notices->clear_all_sticky();

			if (!$this->has_api_connectivity()) {
				// Reset connectivity test cache.
				unset( $this->_storage->connectivity_test );
			}

			if ( $this->is_registered() ) {
				// Send deactivation event.
				$this->get_api_site_scope()->call( '/', 'put', array(
					'is_active' => false,
					'is_premium' => $this->is_premium(),
					// Send version on deactivation.
					'version'   => $this->get_plugin_version(),
				) );
			}

			// Clear API cache on deactivation.
			FS_Api::clear_cache();
		}

		/**
		 * Plugin version update hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 */
		private function update_plugin_version_event() {
			$this->_logger->entrance( 'slug = ' . $this->_slug );

			$this->_site->version = $this->get_plugin_version();

			// Send upgrade event.
			$site = $this->get_api_site_scope()->call( '/', 'put', array(
				'version'    => $this->get_plugin_version(),
				'is_premium' => $this->is_premium(),
			) );

			if ( ! isset( $site->error ) ) {
				$this->_store_site( true );
			}
		}

		/**
		 * Update install details.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		private function send_install_update($params) {
			$this->_logger->entrance( );

			// Send data update event.
			$this->get_api_site_scope()->call( '/', 'put', $params );
		}

		/**
		 * Plugin uninstall hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @param bool $check_user Enforce checking if user have plugins activation privileges.
		 */
		function _uninstall_plugin_event($check_user = true) {
			$this->_logger->entrance( 'slug = ' . $this->_slug );

			if ($check_user && ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			// Send uninstall event.
			$this->get_api_site_scope()->call( '/', 'put', array(
				'is_active'      => false,
				'is_premium' => $this->is_premium(),
				'is_uninstalled' => true,
				// Send version on uninstall.
				'version'        => $this->get_plugin_version(),
			) );

			// @todo Decide if we want to delete plugin information from db.
		}

		/**
		 * Uninstall plugin hook. Called only when connected his account with Freemius for active sites tracking.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 */
		public static function _uninstall_plugin_hook() {
			self::_load_required_static();

			self::$_static_logger->entrance();

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$plugin_file = substr( current_filter(), strlen( 'uninstall_' ) );

			self::$_static_logger->info( 'plugin = ' . $plugin_file );

			define('WP_FS__UNINSTALL_MODE', true);

			$fs = self::get_instance_by_file( $plugin_file );

			if ( is_object( $fs ) ) {
				$fs->_uninstall_plugin_event();
			}
		}

		#region Plugin Information ------------------------------------------------------------------

		/**
		 * Return plugin data.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @return array
		 */
		function get_plugin_data() {
			if ( ! isset( $this->_plugin_data ) ) {
				if ( ! function_exists( 'get_plugins' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}

				$this->_plugin_data = get_plugin_data( $this->_plugin_main_file_path );
			}

			return $this->_plugin_data;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @return string Plugin slug.
		 */
		function get_slug() {
			return $this->_slug;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @return number Plugin ID.
		 */
		function get_id() {
			return $this->_plugin->id;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @return string Plugin public key.
		 */
		function get_public_key() {
			return $this->_plugin->public_key;
		}

		/**
		 * Will be available only on sandbox mode.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @return mixed Plugin secret key.
		 */
		function get_secret_key() {
			return $this->_plugin->secret_key;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return string
		 */
		function get_plugin_name() {
			$this->_logger->entrance();

			if ( ! isset( $this->_plugin_name ) ) {
				$plugin_data = $this->get_plugin_data();

				// Get name.
				$this->_plugin_name = $plugin_data['Name'];

				// Check if plugin name contains [Premium] suffix and remove it.
				$suffix     = '[premium]';
				$suffix_len = strlen( $suffix );

				if ( strlen( $plugin_data['Name'] ) > $suffix_len &&
				     $suffix === substr( strtolower( $plugin_data['Name'] ), - $suffix_len )
				) {
					$this->_plugin_name = substr( $plugin_data['Name'], 0, - $suffix_len );
				}

				$this->_logger->departure( 'Name = ' . $this->_plugin_name );
			}

			return $this->_plugin_name;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 *
		 * @return string
		 */
		function get_plugin_version() {
			$this->_logger->entrance();

			$plugin_data = $this->get_plugin_data();

			$this->_logger->departure( 'Version = ' . $plugin_data['Version'] );

			return $plugin_data['Version'];
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @return string
		 */
		function get_plugin_basename() {
			return $this->_plugin_basename;
		}

		function get_plugin_folder_name() {
			$this->_logger->entrance();

			$plugin_folder = $this->_plugin_basename;

			while ( '.' !== dirname( $plugin_folder ) ) {
				$plugin_folder = dirname( $plugin_folder );
			}

			$this->_logger->departure( 'Folder Name = ' . $plugin_folder );

			return $plugin_folder;
		}

		#endregion ------------------------------------------------------------------

		/* Account
		------------------------------------------------------------------------------------------------------------------*/

		/**
		 * Find plugin's slug by plugin's basename.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param string $plugin_base_name
		 *
		 * @return false|string
		 */
		private static function find_slug_by_basename($plugin_base_name)
		{
			$file_slug_map = self::$_accounts->get_option( 'file_slug_map', array() );

			if (!array($file_slug_map) || !isset($file_slug_map[$plugin_base_name]))
				return false;

			return $file_slug_map[$plugin_base_name];
		}

		/**
		 * Store the map between the plugin's basename to the slug.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		private function store_file_slug_map() {
			$file_slug_map = self::$_accounts->get_option( 'file_slug_map', array() );

			if ( ! array( $file_slug_map ) ) {
				$file_slug_map = array();
			}

			if ( ! isset( $file_slug_map[ $this->_plugin_basename ] ) ||
			     $file_slug_map[ $this->_plugin_basename ] !== $this->_slug
			) {
				$file_slug_map[ $this->_plugin_basename ] = $this->_slug;
				self::$_accounts->set_option( 'file_slug_map', $file_slug_map, true );
			}
		}

		/**
		 * @return FS_User[]
		 */
		static function get_all_users() {
			$users = self::$_accounts->get_option( 'users', array() );

			if ( ! is_array( $users ) ) {
				$users = array();
			}

			return $users;
		}

		/**
		 * @return FS_Site[]
		 */
		private static function get_all_sites() {
			$sites = self::$_accounts->get_option( 'sites', array() );

			if ( ! is_array( $sites ) ) {
				$sites = array();
			}

			return $sites;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return FS_Plugin_License[]
		 */
		private static function get_all_licenses() {
			$licenses = self::$_accounts->get_option( 'licenses', array() );

			if ( ! is_array( $licenses ) ) {
				$licenses = array();
			}

			return $licenses;
		}

		/**
		 * @return FS_Plugin_Plan[]
		 */
		private static function get_all_plans() {
			$plans = self::$_accounts->get_option( 'plans', array() );

			if ( ! is_array( $plans ) ) {
				$plans = array();
			}

			return $plans;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @return FS_Plugin_Tag[]
		 */
		private static function get_all_updates() {
			$updates = self::$_accounts->get_option( 'updates', array() );

			if ( ! is_array( $updates ) ) {
				$updates = array();
			}

			return $updates;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return FS_Plugin[]|false
		 */
		private static function get_all_addons() {
			$addons = self::$_accounts->get_option( 'addons', array() );

			if ( ! is_array( $addons ) ) {
				$addons = array();
			}

			return $addons;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return FS_Plugin[]|false
		 */
		private static function get_all_account_addons() {
			$addons = self::$_accounts->get_option( 'account_addons', array() );

			if ( ! is_array( $addons ) ) {
				$addons = array();
			}

			return $addons;
		}

		/**
		 * Check if user is registered.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 * @return bool
		 */
		function is_registered() {
			return is_object( $this->_user );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @return FS_Plugin
		 */
		function get_plugin() {
			return $this->_plugin;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @return FS_User
		 */
		function get_user() {
			return $this->_user;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @return FS_Site
		 */
		function get_site() {
			return $this->_site;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return FS_Plugin[]|false
		 */
		function get_addons() {
			$this->_logger->entrance();

			$addons = self::get_all_addons();

			if ( ! is_array( $addons ) ||
			     ! isset( $addons[ $this->_plugin->id ] ) ||
			     ! is_array( $addons[ $this->_plugin->id ] ) ||
			     0 === count( $addons[ $this->_plugin->id ] )
			) {
				return false;
			}

			return $addons[ $this->_plugin->id ];
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return FS_Plugin[]|false
		 */
		function get_account_addons() {
			$this->_logger->entrance();

			$addons = self::get_all_account_addons();

			if ( ! is_array( $addons ) ||
			     ! isset( $addons[ $this->_plugin->id ] ) ||
			     ! is_array( $addons[ $this->_plugin->id ] ) ||
			     0 === count( $addons[ $this->_plugin->id ] )
			) {
				return false;
			}

			return $addons[ $this->_plugin->id ];
		}

		/**
		 * Get add-on by ID (from local data).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param number $id
		 *
		 * @return FS_Plugin|false
		 */
		function get_addon( $id ) {
			$this->_logger->entrance();

			$addons = $this->get_addons();

			if ( is_array( $addons ) ) {
				foreach ( $addons as $addon ) {
					if ( $id == $addon->id ) {
						return $addon;
					}
				}
			}

			return false;
		}

		/**
		 * Get add-on by slug (from local data).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param string $slug
		 *
		 * @return FS_Plugin|false
		 */
		function get_addon_by_slug( $slug ) {
			$this->_logger->entrance();

			$addons = $this->get_addons();

			if ( is_array( $addons ) ) {
				foreach ( $addons as $addon ) {
					if ( $slug == $addon->slug ) {
						return $addon;
					}
				}
			}

			return false;
		}

		#region Plans & Licensing ------------------------------------------------------------------

		/**
		 * Check if running premium plugin code.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @return bool
		 */
		function is_premium() {
			return $this->_plugin->is_premium;
		}

		/**
		 * Get site's plan ID.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 * @return number
		 */
		function get_plan_id() {
			return $this->_site->plan->id;
		}

		/**
		 * Get site's plan title.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 * @return string
		 */
		function get_plan_title() {
			return $this->_site->plan->title;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return FS_Plugin_Plan
		 */
		function get_plan() {
			return is_object( $this->_site->plan ) ? $this->_site->plan : false;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @return bool
		 */
		function is_trial() {
			$this->_logger->entrance();

			if ( ! $this->is_registered() ) {
				return false;
			}

			// Paid plan beats trial.
			return $this->is_free_plan() && $this->_site->is_trial();
		}

		/**
		 * Check if trial already utilized.
		 *
		 * @since 1.0.9
		 *
		 * @return bool
		 */
		function is_trial_utilized() {
			$this->_logger->entrance();

			if ( ! $this->is_registered() ) {
				return false;
			}

			return $this->_site->is_trial_utilized();
		}

		/**
		 * Get trial plan information (if in trial).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool|FS_Plugin_Plan
		 */
		function get_trial_plan() {
			$this->_logger->entrance();

			if ( ! $this->is_trial() ) {
				return false;
			}

			return $this->_storage->trial_plan;
		}

		/**
		 * Check if the user has an activated and valid paid license on current plugin's install.
		 *
		 * @since 1.0.9
		 *
		 * @return bool
		 */
		function is_paying() {
			$this->_logger->entrance();

			if ( ! $this->is_registered() ) {
				return false;
			}

			return (
				! $this->is_trial() &&
				'free' !== $this->_site->plan->name &&
				$this->has_features_enabled_license()
			);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @return bool
		 */
		function is_free_plan() {
			if ( ! $this->is_registered() ) {
				return true;
			}

			return (
				'free' === $this->_site->plan->name ||
				! $this->has_features_enabled_license()
			);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @return bool
		 */
		function _has_premium_license() {
			$this->_logger->entrance();

			$premium_license = $this->_get_available_premium_license();

			return ( false !== $premium_license );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @return FS_Plugin_License
		 */
		function _get_available_premium_license() {
			$this->_logger->entrance();

			if ( is_array( $this->_licenses ) ) {
				foreach ( $this->_licenses as $license ) {
					if ( ! $license->is_utilized() && $license->is_features_enabled() ) {
						return $license;
					}
				}
			}

			return false;
		}

		/**
		 * Sync local plugin plans with remote server.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @return FS_Plugin_Plan[]|object
		 */
		function _sync_plans() {
			$plans = $this->_fetch_plugin_plans();
			if ( ! isset( $plans->error ) ) {
				$this->_plans = $plans;
				$this->_store_plans();
			}

			$this->do_action( 'after_plans_sync', $plans );

			return $this->_plans;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param number $id
		 *
		 * @return FS_Plugin_Plan
		 */
		function _get_plan_by_id( $id ) {
			$this->_logger->entrance();

			if ( ! is_array( $this->_plans ) || 0 === count( $this->_plans ) ) {
				$this->_sync_plans();
			}

			foreach ( $this->_plans as $plan ) {
				if ( $id == $plan->id ) {
					return $plan;
				}
			}

			return false;
		}

		/**
		 * Sync local plugin plans with remote server.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return FS_Plugin_License[]|object
		 */
		function _sync_licenses() {
			$licenses = $this->_fetch_licenses();
			if ( ! isset( $licenses->error ) ) {
				$this->_licenses = $licenses;
				$this->_store_licenses();
			}

			// Update current license.
			if ( is_object( $this->_license ) ) {
				$this->_license = $this->_get_license_by_id( $this->_license->id );
			}

			return $this->_licenses;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param number $id
		 *
		 * @return FS_Plugin_License
		 */
		function _get_license_by_id( $id ) {
			$this->_logger->entrance();

			if ( ! is_numeric( $id ) ) {
				return false;
			}

			if ( ! is_array( $this->_licenses ) || 0 === count( $this->_licenses ) ) {
				$this->_sync_licenses();
			}

			foreach ( $this->_licenses as $license ) {
				if ( $id == $license->id ) {
					return $license;
				}
			}

			return false;
		}

		/**
		 * Sync site's license with user licenses.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param FS_Plugin_License|null $new_license
		 */
		function _update_site_license( $new_license ) {
			$this->_logger->entrance();

			$this->_license = $new_license;

			if ( ! is_object( $new_license ) ) {
				$this->_site->license_id = null;
				$this->_sync_site_subscription( null );

				return;
			}

			$this->_site->license_id = $this->_license->id;

			if ( ! is_array( $this->_licenses ) ) {
				$this->_licenses = array();
			}

			$is_license_found = false;
			for ( $i = 0, $len = count( $this->_licenses ); $i < $len; $i ++ ) {
				if ( $new_license->id == $this->_licenses[ $i ]->id ) {
					$this->_licenses[ $i ] = $new_license;

					$is_license_found = true;
					break;
				}
			}

			// If new license just append.
			if ( ! $is_license_found ) {
				$this->_licenses[] = $new_license;
			}

			$this->_sync_site_subscription( $new_license );
		}

		/**
		 * Sync site's subscription.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param FS_Plugin_License|null $license
		 *
		 * @return bool|\FS_Subscription
		 */
		private function _sync_site_subscription( $license ) {
			if ( ! is_object( $license ) ) {
				unset( $this->_storage->subscription );

				return false;
			}

			// Load subscription details if not lifetime.
			$subscription = $license->is_lifetime() ?
				false :
				$this->_fetch_site_license_subscription();

			if ( is_object( $subscription ) && ! isset( $subscription->error ) ) {
				$this->_storage->subscription = $subscription;
			} else {
				unset( $this->_storage->subscription );
			}

			return $subscription;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return bool|\FS_Plugin_License
		 */
		function _get_license() {
			return $this->_license;
		}

		/**
		 * @return bool|\FS_Subscription
		 */
		function _get_subscription() {
			return isset( $this->_storage->subscription ) ?
				$this->_storage->subscription :
				false;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 * @param string $plan  Plan name
		 * @param bool   $exact If true, looks for exact plan. If false, also check "higher" plans.
		 *
		 * @return bool
		 */
		function is_plan( $plan, $exact = false ) {
			$this->_logger->entrance();

			if ( ! $this->is_registered() ) {
				return false;
			}

			$plan = strtolower( $plan );

			if ( $this->_site->plan->name === $plan ) // Exact plan.
			{
				return true;
			} else if ( $exact ) // Required exact, but plans are different.
			{
				return false;
			}

			$current_plan_order  = - 1;
			$required_plan_order = - 1;
			for ( $i = 0, $len = count( $this->_plans ); $i < $len; $i ++ ) {
				if ( $plan === $this->_plans[ $i ]->name ) {
					$required_plan_order = $i;
				} else if ( $this->_site->plan->name === $this->_plans[ $i ]->name ) {
					$current_plan_order = $i;
				}
			}

			return ( $current_plan_order > $required_plan_order );
		}

		/**
		 * Check if plan based on trial. If not in trial mode, should return false.
		 *
		 * @since  1.0.9
		 *
		 * @param string $plan  Plan name
		 * @param bool   $exact If true, looks for exact plan. If false, also check "higher" plans.
		 *
		 * @return bool
		 */
		function is_trial_plan( $plan, $exact = false ) {
			$this->_logger->entrance();

			if ( ! $this->is_registered() ) {
				return false;
			}

			if ( ! $this->is_trial() ) {
				return false;
			}

			if ( ! isset( $this->_storage->trial_plan ) ) {
				// Store trial plan information.
				$this->_enrich_site_trial_plan( true );
			}

			if ( $this->_storage->trial_plan->name === $plan ) // Exact plan.
			{
				return true;
			} else if ( $exact ) // Required exact, but plans are different.
			{
				return false;
			}

			$current_plan_order  = - 1;
			$required_plan_order = - 1;
			for ( $i = 0, $len = count( $this->_plans ); $i < $len; $i ++ ) {
				if ( $plan === $this->_plans[ $i ]->name ) {
					$required_plan_order = $i;
				} else if ( $this->_storage->trial_plan->name === $this->_plans[ $i ]->name ) {
					$current_plan_order = $i;
				}
			}

			return ( $current_plan_order > $required_plan_order );
		}

		/**
		 * Check if plugin has any paid plans.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return bool
		 */
		function has_paid_plan() {
			return $this->_has_paid_plans || FS_Plan_Manager::instance()->has_paid_plan( $this->_plans );
		}

		/**
		 * Check if plugin has any plan with a trail.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function has_trial_plan() {
			if ( ! $this->is_registered() ) {
				return false;
			}

			return $this->_storage->get( 'has_trial_plan', false );
		}

		/**
		 * Check if plugin has any free plan, or is it premium only.
		 *
		 * Note: If no plans configured, assume plugin is free.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return bool
		 */
		function has_free_plan() {
			return FS_Plan_Manager::instance()->has_free_plan( $this->_plans );
		}

		#region URL Generators

		/**
		 * Alias to pricing_url().
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 * @uses   pricing_url
		 *
		 * @param string $period Billing cycle
		 *
		 * @return string
		 */
		function get_upgrade_url( $period = WP_FS__PERIOD_ANNUALLY ) {
			return $this->pricing_url( $period );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @uses   get_upgrade_url
		 *
		 * @return string
		 */
		function get_trial_url() {
			return $this->get_upgrade_url( 'trial' );
		}

		/**
		 * Plugin's pricing URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param string $period Billing cycle
		 *
		 * @return string
		 */
		function pricing_url( $period = WP_FS__PERIOD_ANNUALLY ) {
			$this->_logger->entrance();

			return $this->_get_admin_page_url( 'pricing', array( 'billing_cycle' => $period ) );
		}

		/**
		 * Checkout page URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param string      $period Billing cycle
		 * @param bool|string $plan_name
		 * @param bool|number $plan_id
		 * @param bool|int    $licenses
		 *
		 * @return string
		 */
		function checkout_url(
			$period = WP_FS__PERIOD_ANNUALLY,
			$plan_name = false,
			$plan_id = false,
			$licenses = false
		) {
			$this->_logger->entrance();

			$params = array(
				'checkout'      => 'true',
				'billing_cycle' => $period,
			);

			if ( false !== $plan_name ) {
				$params['plan_name'] = $plan_name;
			}
			if ( false !== $plan_id ) {
				$params['plan_id'] = $plan_id;
			}
			if ( false !== $licenses ) {
				$params['licenses'] = $licenses;
			}

			return $this->_get_admin_page_url( 'pricing', $params );
		}

		#endregion

		#endregion ------------------------------------------------------------------

		/**
		 * Check if plugin has any add-ons.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @return bool
		 */
		function _has_addons() {
			$this->_logger->entrance();

			return ( $this->_has_addons || false !== $this->get_addons() );
		}

		/**
		 * Check if plugin can work in anonymous mode.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function enable_anonymous() {
			return $this->_enable_anonymous;
		}

		/**
		 * Check if feature supported with current site's plan.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @todo   IMPLEMENT
		 *
		 * @param number $feature_id
		 *
		 * @throws Exception
		 */
		function is_feature_supported( $feature_id ) {
			throw new Exception( 'not implemented' );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @return bool Is running in SSL/HTTPS
		 */
		function is_ssl() {
			return WP_FS__IS_HTTPS;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool Is running in AJAX call.
		 *
		 * @link   http://wordpress.stackexchange.com/questions/70676/how-to-check-if-i-am-in-admin-ajax
		 */
		function is_ajax() {
			return ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		}

		/**
		 * Check if running in HTTPS and if site's plan matching the specified plan.
		 *
		 * @param string $plan
		 * @param bool   $exact
		 *
		 * @return bool
		 */
		function is_ssl_and_plan( $plan, $exact = false ) {
			return ( $this->is_ssl() && $this->is_plan( $plan, $exact ) );
		}

		/**
		 * Construct plugin's settings page URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param string $page
		 * @param array  $params
		 *
		 * @return string
		 */
		function _get_admin_page_url( $page = '', $params = array() ) {
			return add_query_arg( array_merge( $params, array(
				'page' => trim( "{$this->_menu_slug}-{$page}", '-' )
			) ), admin_url( 'admin.php', 'admin' ) );
		}

		/**
		 * Plugin's account URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param bool|string $action
		 * @param array       $params
		 *
		 * @param bool        $add_action_nonce
		 *
		 * @return string
		 */
		function get_account_url( $action = false, $params = array(), $add_action_nonce = true ) {
			if ( is_string( $action ) ) {
				$params['fs_action'] = $action;
			}

			if ( ! function_exists( 'wp_create_nonce' ) ) {
				require_once( ABSPATH . 'wp-includes/pluggable.php' );
			}

			return ( $add_action_nonce && is_string( $action ) ) ?
				wp_nonce_url( $this->_get_admin_page_url( 'account', $params ), $action ) :
				$this->_get_admin_page_url( 'account', $params );
		}

		/**
		 * Plugin's account URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param bool|string $topic
		 * @param bool|string $message
		 *
		 * @return string
		 */
		function contact_url( $topic = false, $message = false ) {
			$params = array();
			if ( is_string( $topic ) ) {
				$params['topic'] = $topic;
			}
			if ( is_string( $message ) ) {
				$params['message'] = $message;
			}
			
			if ( $this->is_addon() ) {
				$params['addon_id'] = $this->get_id();
				return $this->get_parent_instance()->_get_admin_page_url( 'contact', $params );
			} else {
				return $this->_get_admin_page_url( 'contact', $params );
			}
		}

		/* Logger
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * @param string $id
		 * @param bool   $prefix_slug
		 *
		 * @return FS_Logger
		 */
		function get_logger( $id = '', $prefix_slug = true ) {
			return FS_Logger::get_logger( ( $prefix_slug ? $this->_slug : '' ) . ( ( ! $prefix_slug || empty( $id ) ) ? '' : '_' ) . $id );
		}

		/**
		 * @param      $id
		 * @param bool $load_options
		 * @param bool $prefix_slug
		 *
		 * @return FS_Option_Manager
		 */
		function get_options_manager( $id, $load_options = false, $prefix_slug = true ) {
			return FS_Option_Manager::get_manager( ( $prefix_slug ? $this->_slug : '' ) . ( ( ! $prefix_slug || empty( $id ) ) ? '' : '_' ) . $id, $load_options );
		}

		/* Security
		------------------------------------------------------------------------------------------------------------------*/
		private function _encrypt( $str ) {
			if ( is_null( $str ) ) {
				return null;
			}

			return base64_encode( $str );
		}

		private function _decrypt( $str ) {
			if ( is_null( $str ) ) {
				return null;
			}

			return base64_decode( $str );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param FS_Entity $entity
		 *
		 * @return FS_Entity Return an encrypted clone entity.
		 */
		private function _encrypt_entity( FS_Entity $entity ) {
			$clone = clone $entity;
			$props = get_object_vars( $entity );

			foreach ( $props as $key => $val ) {
				$clone->{$key} = $this->_encrypt( $val );
			}

			return $clone;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param FS_Entity $entity
		 *
		 * @return FS_Entity Return an decrypted clone entity.
		 */
		private function _decrypt_entity( FS_Entity $entity ) {
			$clone = clone $entity;
			$props = get_object_vars( $entity );

			foreach ( $props as $key => $val ) {
				$clone->{$key} = $this->_decrypt( $val );
			}

			return $clone;
		}

		/**
		 * Tries to activate account based on POST params.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 */
		function _activate_account() {
			if ( $this->is_registered() ) {
				// Already activated.
				return;
			}

			$this->_clean_admin_content_section();

			if ( fs_request_is_action( 'activate' ) && fs_request_is_post() ) {
//				check_admin_referer( 'activate_' . $this->_plugin->public_key );

				// Verify matching plugin details.
				if ( $this->_plugin->id != fs_request_get( 'plugin_id' ) || $this->_slug != fs_request_get( 'plugin_slug' ) ) {
					return;
				}

				$user              = new FS_User();
				$user->id          = fs_request_get( 'user_id' );
				$user->public_key  = fs_request_get( 'user_public_key' );
				$user->secret_key  = fs_request_get( 'user_secret_key' );
				$user->email       = fs_request_get( 'user_email' );
				$user->first       = fs_request_get( 'user_first' );
				$user->last        = fs_request_get( 'user_last' );
				$user->is_verified = fs_request_get_bool( 'user_is_verified' );

				$site              = new FS_Site();
				$site->id          = fs_request_get( 'install_id' );
				$site->public_key  = fs_request_get( 'install_public_key' );
				$site->secret_key  = fs_request_get( 'install_secret_key' );
				$site->plan->id    = fs_request_get( 'plan_id' );
				$site->plan->title = fs_request_get( 'plan_title' );
				$site->plan->name  = fs_request_get( 'plan_name' );

				$plans      = array();
				$plans_data = json_decode( urldecode( fs_request_get( 'plans' ) ) );
				foreach ( $plans_data as $p ) {
					$plans[] = new FS_Plugin_Plan( $p );
				}

				$this->_set_account( $user, $site, $plans );

				// Reload the page with the keys.
				if ( fs_redirect( $this->_get_admin_page_url() ) ) {
					exit();
				}
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @param string $email
		 *
		 * @return FS_User|bool
		 */
		static function _get_user_by_email( $email ) {
			self::$_static_logger->entrance();

			$email = trim( strtolower( $email ) );
			$users = self::get_all_users();
			if ( is_array( $users ) ) {
				foreach ( $users as $u ) {
					if ( $email === trim( strtolower( $u->email ) ) ) {
						return $u;
					}
				}
			}

			return false;
		}

		#region Account (Loading, Updates & Activation) ------------------------------------------------------------------

		/***
		 * Load account information (user + site).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 */
		private function _load_account() {
			$this->_logger->entrance();

			$this->do_action( 'before_account_load' );

			$sites    = self::get_all_sites();
			$users    = self::get_all_users();
			$plans    = self::get_all_plans();
			$licenses = self::get_all_licenses();

			if ( $this->_logger->is_on() && is_admin() ) {
				$this->_logger->log( 'sites = ' . var_export( $sites, true ) );
				$this->_logger->log( 'users = ' . var_export( $users, true ) );
				$this->_logger->log( 'plans = ' . var_export( $plans, true ) );
				$this->_logger->log( 'licenses = ' . var_export( $licenses, true ) );
			}

			$site = isset($sites[ $this->_slug ]) ? $sites[ $this->_slug ] : false;

			if ( is_object( $site ) &&
			     is_numeric( $site->id ) &&
			     is_numeric( $site->user_id ) &&
			     is_object( $site->plan )
			) {
				// Load site.
				$this->_site       = clone $site;
				$this->_site->plan = $this->_decrypt_entity( $this->_site->plan );

				// Load relevant user.
				$this->_user = clone $users[ $this->_site->user_id ];

				// Load plans.
				$this->_plans = $plans[ $this->_slug ];
				if ( ! is_array( $this->_plans ) || empty( $this->_plans ) ) {
					$this->_sync_plans( true );
				} else {
					for ( $i = 0, $len = count( $this->_plans ); $i < $len; $i ++ ) {
						if ( $this->_plans[ $i ] instanceof FS_Plugin_Plan ) {
							$this->_plans[ $i ] = $this->_decrypt_entity( $this->_plans[ $i ] );
						} else {
							unset( $this->_plans[ $i ] );
						}
					}
				}

				// Load licenses.
				$this->_licenses = array();
				if ( is_array( $licenses ) &&
				     isset( $licenses[ $this->_slug ] ) &&
				     isset( $licenses[ $this->_slug ][ $this->_user->id ] )
				) {
					$this->_licenses = $licenses[ $this->_slug ][ $this->_user->id ];
				}

				$this->_license = $this->_get_license_by_id( $this->_site->license_id );

				if ( $this->_site->version != $this->get_plugin_version() ) {
					// If stored install version is different than current installed plugin version,
					// then update plugin version event.
					$this->update_plugin_version_event();
				}
			}

			$this->_register_account_hooks();
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @param FS_User    $user
		 * @param FS_Site    $site
		 * @param bool|array $plans
		 */
		private function _set_account( FS_User $user, FS_Site $site, $plans = false ) {
			$site->slug    = $this->_slug;
			$site->user_id = $user->id;

			$this->_site = $site;
			$this->_user = $user;
			if ( false !== $plans ) {
				$this->_plans = $plans;
			}

			$params = array();

			if ( ! empty( $this->_site->version ) &&
			     $this->_site->version != $this->get_plugin_version()
			) {
				$this->_site->version = $this->get_plugin_version();
				$params['version'] = $this->_site->version;
			}

			if ( $this->_site->is_premium != $this->is_premium() ) {
				$this->_site->is_premium = $this->is_premium();
				$params['is_premium'] = $this->_site->is_premium;
			}

			if (0 < count($params)) {
				// Send updated values to FS.
				$this->send_install_update( $params );
			}

			$this->_store_account();

		}

		/**
		 * Set user and site identities.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param FS_User $user
		 * @param FS_Site $site
		 *
		 * @return bool False if account already set.
		 */
		function setup_account( FS_User $user, FS_Site $site ) {
			$this->_user = $user;
			$this->_site = $site;
			$this->_enrich_site_plan( false );

			$this->_set_account( $user, $site );
			$this->_sync_plans();

			if ( $this->is_trial() ) {
				// Store trial plan information.
				$this->_enrich_site_trial_plan( true );
			}

			$this->do_action( 'after_account_connection', $user, $site );

			if ( is_numeric( $site->license_id ) ) {
				$this->_license = $this->_get_license_by_id( $site->license_id );
			}

			if ( $this->is_pending_activation() ) {
				// Remove pending activation sticky notice (if still exist).
				$this->_admin_notices->remove_sticky( 'activation_pending' );

				// Remove plugin from pending activation mode.
				unset( $this->_storage->is_pending_activation );

				if ( ! $this->is_paying() ) {
					$this->_admin_notices->add_sticky(
						sprintf( __( '%s activation was successfully completed.', WP_FS__SLUG ), '<b>' . $this->get_plugin_name() . '</b>' ),
						'activation_complete'
					);
				}
			}

			if ( $this->is_paying() && !$this->is_premium() ) {
				$this->_admin_notices->add_sticky(
					sprintf(
						__( 'Your account was successfully activated with the %s plan.', WP_FS__SLUG ),
						$this->_site->plan->title
					) . ' ' . $this->_get_latest_download_link( sprintf(
						__( 'Download our latest %s version now', WP_FS__SLUG ),
						$this->_site->plan->title
					) ),
					'plan_upgraded',
					__( 'Ye-ha!', WP_FS__SLUG )
				);
			}

			return true;
		}

		/**
		 * Install plugin with new user information after approval.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 */
		function _install_with_new_user() {
			if ( $this->is_registered() ) {
				return;
			}

			if ( fs_request_is_action( $this->_slug . '_activate_new' ) ) {
//				check_admin_referer( $this->_slug . '_activate_new' );

				if ( fs_request_has( 'user_secret_key' ) ) {
					$user             = new FS_User();
					$user->id         = fs_request_get( 'user_id' );
					$user->public_key = fs_request_get( 'user_public_key' );
					$user->secret_key = fs_request_get( 'user_secret_key' );

					$this->_user = $user;
					$user_result = $this->get_api_user_scope()->get();
					$user        = new FS_User( $user_result );
					$this->_user = $user;

					$site             = new FS_Site();
					$site->id         = fs_request_get( 'install_id' );
					$site->public_key = fs_request_get( 'install_public_key' );
					$site->secret_key = fs_request_get( 'install_secret_key' );

					$this->_site = $site;
					$site_result = $this->get_api_site_scope()->get();
					$site        = new FS_Site( $site_result );
					$this->_site = $site;

					$this->setup_account( $this->_user, $this->_site );

					$plugin_id = fs_request_get( 'plugin_id', false );

					// Store activation time ONLY for plugins (not add-ons).
					if ( ! is_numeric( $plugin_id ) || ( $plugin_id == $this->_plugin->id ) ) {
						$this->_storage->activation_timestamp = WP_FS__SCRIPT_START_TIME;
					}

					if ( is_numeric( $plugin_id ) ) {
						if ( $plugin_id != $this->_plugin->id ) {
							// Add-on was purchased - sync license after install.
							if ( fs_redirect( fs_nonce_url( $this->_get_admin_page_url(
								'account',
								array(
									'fs_action' => $this->_slug . '_sync_license',
									'plugin_id' => $plugin_id
								)
							), $this->_slug . '_sync_license' ) ) ) {
								exit();
							}

						}
					}
				} else if ( fs_request_has( 'pending_activation' ) ) {
					// Install must be activated via email since
					// user with the same email already exist.
					$this->_storage->is_pending_activation = true;
					$this->_add_pending_activation_notice( fs_request_get( 'user_email' ) );
				}

				if ( fs_redirect( $this->_get_admin_page_url() ) ) {
					exit();
				}
			}
		}

		/**
		 * Install plugin with current logged WP user info.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 */
		function _install_with_current_user() {
			if ( $this->is_registered() ) {
				return;
			}

			if ( fs_request_is_action( $this->_slug . '_activate_existing' ) && fs_request_is_post() ) {
//				check_admin_referer( 'activate_existing_' . $this->_plugin->public_key );
				// Get current logged WP user.
				$current_user = wp_get_current_user();

				// Find the relevant FS user by the email.
				$user = self::_get_user_by_email( $current_user->user_email );

				// We have to set the user before getting user scope API handler.
				$this->_user = $user;

				// Install the plugin.
				$install = $this->get_api_user_scope()->call( "/plugins/{$this->get_id()}/installs.json", 'post', array(
					'url'              => get_site_url(),
					'title'            => get_bloginfo( 'name' ),
					'version'          => $this->get_plugin_version(),
					'language'         => get_bloginfo( 'language' ),
					'charset'          => get_bloginfo( 'charset' ),
					'platform_version' => get_bloginfo( 'version' ),
				) );

				if ( isset( $install->error ) ) {
					$this->_admin_notices->add(
						sprintf( __( 'Couldn\'t activate %s. Please contact us with the following message: %s', WP_FS__SLUG ), $this->get_plugin_name(), '<b>' . $install->error->message . '</b>' ),
						'Oops...',
						'error'
					);

					return;
				}

				$site        = new FS_Site( $install );
				$this->_site = $site;
				$this->_enrich_site_plan( false );

				$this->_set_account( $user, $site );
				$this->_sync_plans();

				// Reload the page with the keys.
				if ( fs_redirect( $this->_get_admin_page_url() ) ) {
					exit();
				}
			}
		}

		/**
		 * Tries to activate add-on account based on parent plugin info.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param Freemius $parent_fs
		 */
		private function _activate_addon_account( Freemius $parent_fs ) {
			if ( $this->is_registered() ) {
				// Already activated.
				return;
			}

			// Activate add-on with parent plugin credentials.
			$addon_install = $parent_fs->get_api_site_scope()->call( "/addons/{$this->_plugin->id}/installs.json", 'post', array(
				'title'            => get_bloginfo( 'name' ),
				'version'          => $this->get_plugin_version(),
				'language'         => get_bloginfo( 'language' ),
				'charset'          => get_bloginfo( 'charset' ),
				'platform_version' => get_bloginfo( 'version' ),
			) );

			if ( isset( $addon_install->error ) ) {
				$this->_admin_notices->add(
					sprintf( __( 'Couldn\'t activate %s. Please contact us with the following message: %s', WP_FS__SLUG ), $this->get_plugin_name(), '<b>' . $addon_install->error->message . '</b>' ),
					'Oops...',
					'error'
				);

				return;
			}

			// First of all, set site info - otherwise we won't
			// be able to invoke API calls.
			$this->_site = new FS_Site( $addon_install );

			// Sync add-on plans.
			$this->_sync_plans();

			// Get site's current plan.
			$this->_site->plan = $this->_get_plan_by_id( $this->_site->plan->id );

			// Get user information based on parent's plugin.
			$user = $parent_fs->get_user();

			$this->_set_account( $user, $this->_site );

			// Sync licenses.
			$this->_sync_licenses();

			// Try to activate premium license.
			$this->_activate_license( true );
		}

		#endregion ------------------------------------------------------------------

		#region Admin Menu Items ------------------------------------------------------------------

		private $_has_menu = false;
		private $_menu_items = array();

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return string
		 */
		function get_menu_slug() {
			return $this->_menu_slug;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		function _prepare_admin_menu() {
			if ( ! $this->has_api_connectivity() && !$this->enable_anonymous() ) {
				$this->remove_menu_item();
			} else {
				$this->add_submenu_items();
				$this->add_menu_action();
			}
		}

		/**
		 * Admin dashboard menu items modifications.
		 *
		 * NOTE: admin_menu action executed before admin_init.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 */
		private function add_menu_action() {
			if ( $this->is_activation_mode() ) {
				$this->override_plugin_menu_with_activation();
			} else {
				// If not registered try to install user.
				if ( ! $this->is_registered() &&
				     fs_request_is_action( $this->_slug . '_activate_new' )
				) {
					$this->_install_with_new_user();
				}
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @return string
		 */
		function _redirect_on_clicked_menu_link() {
			$this->_logger->entrance();

			$page = strtolower( isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '' );

			$this->_logger->log( 'page = ' . $page );

			foreach ( $this->_menu_items as $priority => $items ) {
				foreach ( $items as $item ) {
					if ( isset( $item['url'] ) ) {
						if ( $page === $item['menu_slug'] ) {
							$this->_logger->log( 'Redirecting to ' . $item['url'] );

							fs_redirect( $item['url'] );
						}
					}
				}
			}
		}

		/**
		 * Find plugin's admin dashboard main menu item.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 * @return string[]
		 */
		private function find_plugin_main_menu() {
			global $menu;

			$position   = - 1;
			$found_menu = false;

			$menu_slug = plugin_basename( $this->_menu_slug );
			$hook_name = get_plugin_page_hookname( $menu_slug, '' );
			foreach ( $menu as $pos => $m ) {
				if ( $menu_slug === $m[2] ) {
					$position   = $pos;
					$found_menu = $m;
					break;
				}
			}

			return array(
				'menu'      => $found_menu,
				'position'  => $position,
				'hook_name' => $hook_name
			);
		}

		/**
		 * Remove all sub-menu items.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return bool If submenu with plugin's menu slug was found.
		 */
		private function remove_all_submenu_items() {
			global $submenu;

			if ( ! isset( $submenu[ $this->_menu_slug ] ) ) {
				return false;
			}

			$submenu[ $this->_menu_slug ] = array();

			return true;
		}

		/**
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return array[string]mixed
		 */
		private function remove_menu_item(){
			$this->_logger->entrance();

			// Find main menu item.
			$menu = $this->find_plugin_main_menu();

			// Remove it with its actions.
			remove_all_actions( $menu['hook_name'] );

			// Remove all submenu items.
			$this->remove_all_submenu_items();

			return $menu;
		}

		/**
		 * Remove plugin's all admin menu items & pages, and replace with activation page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 */
		private function override_plugin_menu_with_activation() {
			$this->_logger->entrance();

			$menu = $this->remove_menu_item();

			if ( $this->is_activation_page() ) {
				// Clean admin page from distracting content.
				$this->_clean_admin_content_section();
			}

			// Override menu action.
			$hook = add_menu_page(
				$menu['menu'][3],
				$menu['menu'][0],
				'manage_options',
				$this->_menu_slug,
				array( &$this, '_connect_page_render' ),
				$menu['menu'][6],
				$menu['position']
			);

			if ( fs_request_is_action( $this->_slug . '_activate_existing' ) ) {
				add_action( "load-$hook", array( &$this, '_install_with_current_user' ) );
			} else if ( fs_request_is_action( $this->_slug . '_activate_new' ) ) {
				add_action( "load-$hook", array( &$this, '_install_with_new_user' ) );
			}
		}

		private function add_submenu_items() {
			$this->_logger->entrance();

			$this->do_action( 'before_admin_menu_init' );

			if ( ! $this->is_addon() ) {
				if ( $this->is_registered() || $this->is_anonymous() ) {
					if ( $this->is_registered() ) {
						// Add user account page.
						$this->add_submenu_item(
							__( 'Account', $this->_slug ),
							array( &$this, '_account_page_render' ),
							$this->get_plugin_name() . ' &ndash; ' . __( 'Account', $this->_slug ),
							'manage_options',
							'account',
							array( &$this, '_account_page_load' )
						);
					}

					// Add contact page.
					$this->add_submenu_item(
						__( 'Contact Us', $this->_slug ),
						array( &$this, '_contact_page_render' ),
						$this->get_plugin_name() . ' &ndash; ' . __( 'Contact Us', $this->_slug ),
						'manage_options',
						'contact',
						array( &$this, '_clean_admin_content_section' )
					);

					if ( $this->_has_addons() ) {
						$this->add_submenu_item(
							__( 'Add Ons', $this->_slug ),
							array( &$this, '_addons_page_render' ),
							$this->get_plugin_name() . ' &ndash; ' . __( 'Add Ons', $this->_slug ),
							'manage_options',
							'addons',
							array( &$this, '_addons_page_load' ),
							WP_FS__LOWEST_PRIORITY - 1
						);
					}

					// Add upgrade/pricing page.
					$this->add_submenu_item(
						( $this->is_paying() ? __( 'Pricing', $this->_slug ) : __( 'Upgrade', $this->_slug ) . '&nbsp;&nbsp;&#x27a4;' ),
						array( &$this, '_pricing_page_render' ),
						$this->get_plugin_name() . ' &ndash; ' . __( 'Pricing', $this->_slug ),
						'manage_options',
						'pricing',
						array( &$this, '_clean_admin_content_section' ),
						WP_FS__LOWEST_PRIORITY,
						// If user don't have paid plans, add pricing page
						// to support add-ons checkout but don't add the submenu item.
						( $this->has_paid_plan() || ( isset( $_GET['page'] ) && $this->_get_menu_slug( 'pricing' ) == $_GET['page'] ) )
					);
				}
			}

			ksort( $this->_menu_items );

			foreach ( $this->_menu_items as $priority => $items ) {
				foreach ( $items as $item ) {
					if ( ! isset( $item['url'] ) ) {
						$hook = add_submenu_page(
							$item['show_submenu'] ? ( $this->is_addon() ? $this->get_parent_instance()->_menu_slug : $this->_menu_slug ) : null,
							$item['page_title'],
							$item['menu_title'],
							$item['capability'],
							$item['menu_slug'],
							$item['render_function']
						);

						if ( false !== $item['before_render_function'] ) {
							add_action( "load-$hook", $item['before_render_function'] );
						}
					} else {
						add_submenu_page(
							$this->is_addon() ? $this->get_parent_instance()->_menu_slug : $this->_menu_slug,
							$item['page_title'],
							$item['menu_title'],
							$item['capability'],
							$item['menu_slug'],
							array( $this, '' )
						);
					}
				}
			}
		}

		function _add_default_submenu_items() {
			if ( $this->is_registered() ) {
				$this->add_submenu_link_item( __( 'Support Forum', $this->_slug ), 'https://wordpress.org/support/plugin/' . $this->_slug, 'wp-support-forum', 'read', 50 );
			}
		}

		private function _get_menu_slug( $slug = '' ) {
			return $this->_menu_slug . ( empty( $slug ) ? '' : ( '-' . $slug ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @param string        $menu_title
		 * @param callable      $render_function
		 * @param bool|string   $page_title
		 * @param string        $capability
		 * @param bool|string   $menu_slug
		 * @param bool|callable $before_render_function
		 * @param int           $priority
		 * @param bool          $show_submenu
		 */
		function add_submenu_item(
			$menu_title,
			$render_function,
			$page_title = false,
			$capability = 'manage_options',
			$menu_slug = false,
			$before_render_function = false,
			$priority = 10,
			$show_submenu = true
		) {
			$this->_logger->entrance( 'Title = ' . $menu_title );

			if ( $this->is_addon() ) {
				$parent_fs = $this->get_parent_instance();

				if ( is_object( $parent_fs ) ) {
					$parent_fs->add_submenu_item(
						$menu_title,
						$render_function,
						$page_title,
						$capability,
						$menu_slug,
						$before_render_function,
						$priority,
						$show_submenu
					);

					return;
				}
			}

			if ( ! isset( $this->_menu_items[ $priority ] ) ) {
				$this->_menu_items[ $priority ] = array();
			}

			$this->_menu_items[ $priority ][] = array(
				'page_title'             => is_string( $page_title ) ? $page_title : $menu_title,
				'menu_title'             => $menu_title,
				'capability'             => $capability,
				'menu_slug'              => $this->_get_menu_slug( is_string( $menu_slug ) ? $menu_slug : strtolower( $menu_title ) ),
				'render_function'        => $render_function,
				'before_render_function' => $before_render_function,
				'show_submenu'           => $show_submenu,
			);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @param string $menu_title
		 * @param string $url
		 * @param bool   $menu_slug
		 * @param string $capability
		 * @param int    $priority
		 *
		 */
		function add_submenu_link_item(
			$menu_title,
			$url,
			$menu_slug = false,
			$capability = 'read',
			$priority = 10
		) {
			$this->_logger->entrance( 'Title = ' . $menu_title . '; Url = ' . $url );

			if ( $this->is_addon() ) {
				$parent_fs = $this->get_parent_instance();

				if ( is_object( $parent_fs ) ) {
					$parent_fs->add_submenu_link_item(
						$menu_title,
						$url,
						$menu_slug,
						$capability,
						$priority
					);

					return;
				}
			}

			if ( ! isset( $this->_menu_items[ $priority ] ) ) {
				$this->_menu_items[ $priority ] = array();
			}

			$this->_menu_items[ $priority ][] = array(
				'menu_title'             => $menu_title,
				'capability'             => $capability,
				'menu_slug'              => $this->_get_menu_slug( is_string( $menu_slug ) ? $menu_slug : strtolower( $menu_title ) ),
				'url'                    => $url,
				'page_title'             => $menu_title,
				'render_function'        => 'fs_dummy',
				'before_render_function' => '',
			);
		}

		#endregion ------------------------------------------------------------------

		/* Actions / Hooks / Filters
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * Do action, specific for the current context plugin.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @param string $tag     The name of the action to be executed.
		 * @param mixed  $arg,... Optional. Additional arguments which are passed on to the
		 *                        functions hooked to the action. Default empty.
		 *
		 * @uses   do_action()
		 */
		function do_action( $tag, $arg = '' ) {
			$this->_logger->entrance( $tag );

			$args = func_get_args();

			call_user_func_array( 'do_action', array_merge(
					array( 'fs_' . $tag . '_' . $this->_slug ),
					array_slice( $args, 1 ) )
			);
		}

		/**
		 * Add action, specific for the current context plugin.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @param string   $tag
		 * @param callable $function_to_add
		 * @param int      $priority
		 * @param int      $accepted_args
		 *
		 * @uses   add_action()
		 */
		function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
			$this->_logger->entrance( $tag );

			add_action( 'fs_' . $tag . '_' . $this->_slug, $function_to_add, $priority, $accepted_args );
		}

		/**
		 * Apply filter, specific for the current context plugin.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param string $tag   The name of the filter hook.
		 * @param mixed  $value The value on which the filters hooked to `$tag` are applied on.
		 *
		 * @return mixed The filtered value after all hooked functions are applied to it.
		 *
		 * @uses   apply_filters()
		 */
		function apply_filters( $tag, $value ) {
			$this->_logger->entrance( $tag );

			$args = func_get_args();

			return call_user_func_array( 'apply_filters', array_merge(
					array( 'fs_' . $tag . '_' . $this->_slug ),
					array_slice( $args, 1 ) )
			);
		}

		/**
		 * Add filter, specific for the current context plugin.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param string   $tag
		 * @param callable $function_to_add
		 * @param int      $priority
		 * @param int      $accepted_args
		 *
		 * @uses   add_filter()
		 */
		function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
			$this->_logger->entrance( $tag );

			add_filter( 'fs_' . $tag . '_' . $this->_slug, $function_to_add, $priority, $accepted_args );
		}

		/* Activation
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * Render activation/sign-up page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 */
		function _activation_page_render() {
			$this->_logger->entrance();

			$vars = array( 'slug' => $this->_slug );
			fs_require_once_template( 'activation.php', $vars );
		}

		/* Account Page
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * Update site information.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @param bool $store Flush to Database if true.
		 */
		private function _store_site( $store = true ) {
			$this->_logger->entrance();

			$this->_site->updated = time();
			$encrypted_site       = clone $this->_site;
			$encrypted_site->plan = $this->_encrypt_entity( $this->_site->plan );

			$sites                = self::get_all_sites();
			$sites[ $this->_slug ] = $encrypted_site;
			self::$_accounts->set_option( 'sites', $sites, $store );
		}

		/**
		 * Update plugin's plans information.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 * @param bool $store Flush to Database if true.
		 */
		private function _store_plans( $store = true ) {
			$this->_logger->entrance();

			$plans = self::get_all_plans();

			// Copy plans.
			$encrypted_plans = array();
			for ( $i = 0, $len = count( $this->_plans ); $i < $len; $i ++ ) {
				$this->_plans[ $i ]->updated = time();
				$encrypted_plans[]           = $this->_encrypt_entity( $this->_plans[ $i ] );
			}

			$plans[ $this->_slug ] = $encrypted_plans;
			self::$_accounts->set_option( 'plans', $plans, $store );
		}

		/**
		 * Update user's plugin licenses.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param bool                $store
		 * @param string|bool         $plugin_slug
		 * @param FS_Plugin_License[] $licenses
		 */
		private function _store_licenses( $store = true, $plugin_slug = false, $licenses = array() ) {
			$this->_logger->entrance();

			$all_licenses = self::get_all_licenses();

			if ( ! is_string( $plugin_slug ) ) {
				$plugin_slug = $this->_slug;
				$licenses    = $this->_licenses;
			}

			if ( ! isset( $all_licenses[ $plugin_slug ] ) ) {
				$all_licenses[ $plugin_slug ] = array();
			}

			$all_licenses[ $plugin_slug ][ $this->_user->id ] = $licenses;

			self::$_accounts->set_option( 'licenses', $all_licenses, $store );
		}

		/**
		 * Update user information.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @param bool $store Flush to Database if true.
		 */
		private function _store_user( $store = true ) {
			$this->_logger->entrance();

			$this->_user->updated      = time();
			$users                     = self::get_all_users();
			$users[ $this->_user->id ] = $this->_user;
			self::$_accounts->set_option( 'users', $users, $store );
		}

		/**
		 * Update new updates information.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param FS_Plugin_Tag|null $update
		 * @param bool               $store Flush to Database if true.
		 * @param bool|number        $plugin_id
		 */
		private function _store_update( $update, $store = true, $plugin_id = false ) {
			$this->_logger->entrance();

			if ( $update instanceof FS_Plugin_Tag ) {
				$update->updated = time();
			}

			if ( ! is_numeric( $plugin_id ) ) {
				$plugin_id = $this->_plugin->id;
			}

			$updates               = self::get_all_updates();
			$updates[ $plugin_id ] = $update;
			self::$_accounts->set_option( 'updates', $updates, $store );
		}

		/**
		 * Update new updates information.
		 *
		 * @author   Vova Feldman (@svovaf)
		 * @since    1.0.6
		 *
		 * @param FS_Plugin[] $plugin_addons
		 * @param bool        $store Flush to Database if true.
		 */
		private function _store_addons( $plugin_addons, $store = true ) {
			$this->_logger->entrance();

			$addons                       = self::get_all_addons();
			$addons[ $this->_plugin->id ] = $plugin_addons;
			self::$_accounts->set_option( 'addons', $addons, $store );
		}

		/**
		 * Delete plugin's associated add-ons.
		 *
		 * @author   Vova Feldman (@svovaf)
		 * @since    1.0.8
		 *
		 * @param bool $store
		 *
		 * @return bool
		 */
		private function _delete_account_addons( $store = true ) {
			$all_addons = self::get_all_account_addons();

			if ( ! isset( $all_addons[ $this->_plugin->id ] ) ) {
				return false;
			}

			unset( $all_addons[ $this->_plugin->id ] );

			self::$_accounts->set_option( 'account_addons', $all_addons, $store );

			return true;
		}

		/**
		 * Update account add-ons list.
		 *
		 * @author   Vova Feldman (@svovaf)
		 * @since    1.0.6
		 *
		 * @param FS_Plugin[] $addons
		 * @param bool        $store Flush to Database if true.
		 */
		private function _store_account_addons( $addons, $store = true ) {
			$this->_logger->entrance();

			$all_addons                       = self::get_all_account_addons();
			$all_addons[ $this->_plugin->id ] = $addons;
			self::$_accounts->set_option( 'account_addons', $all_addons, $store );
		}

		/**
		 * Store account params in the Database.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 */
		private function _store_account() {
			$this->_logger->entrance();

			$this->_store_site( false );
			$this->_store_user( false );
			$this->_store_plans( false );
			$this->_store_licenses( false );

			self::$_accounts->store();
		}

		/**
		 * Sync user's information.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 * @uses   FS_Api
		 */
		private function _handle_account_user_sync() {
			$this->_logger->entrance();

			$api = $this->get_api_user_scope();

			// Get user's information.
			$user = $api->get( '/', true );

			if ( isset( $user->id ) ) {
				$this->_user->first = $user->first;
				$this->_user->last  = $user->last;
				$this->_user->email = $user->email;

				if ( ( ! isset( $this->_user->is_verified ) || false === $this->_user->is_verified ) && $user->is_verified ) {
					$this->_user->is_verified = $user->is_verified;

					$this->do_action( 'account_email_verified', $user->email );

					$this->_admin_notices->add(
						__( 'Your email has been successfully verified - you are AWESOME!', WP_FS__SLUG ),
						__( 'Right on!', WP_FS__SLUG )
					);
				}

				// Flush user details to DB.
				$this->_store_user();

				$this->do_action( 'after_account_user_sync', $user );
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 * @uses   FS_Api
		 *
		 * @param bool $flush
		 *
		 * @return object|\FS_Site
		 */
		private function _fetch_site( $flush = false ) {
			$this->_logger->entrance();
			$api = $this->get_api_site_scope();

			$site = $api->get( '/', $flush );

			if ( ! isset( $site->error ) ) {
				$site          = new FS_Site( $site );
				$site->slug    = $this->_slug;
				$site->version = $this->get_plugin_version();
			}

			return $site;
		}

		/**
		 * @param bool $store
		 *
		 * @return FS_Plugin_Plan|object|false
		 */
		private function _enrich_site_plan( $store = true ) {
			// Try to load plan from local cache.
			$plan = $this->_get_plan_by_id( $this->_site->plan->id );

			if ( false === $plan ) {
				$plan = $this->_fetch_site_plan();
			}

			if ( $plan instanceof FS_Plugin_Plan ) {
				$this->_update_plan( $plan, $store );
			}

			return $plan;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 * @uses   FS_Api
		 *
		 * @param bool $store
		 *
		 * @return FS_Plugin_Plan|object|false
		 */
		private function _enrich_site_trial_plan( $store = true ) {
			// Try to load plan from local cache.
			$trial_plan = $this->_get_plan_by_id( $this->_site->trial_plan_id );

			if ( false === $trial_plan ) {
				$trial_plan = $this->_fetch_site_plan( $this->_site->trial_plan_id );
			}

			if ( $trial_plan instanceof FS_Plugin_Plan ) {
				$this->_storage->store( 'trial_plan', $trial_plan, $store );
			}

			return $trial_plan;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 * @uses   FS_Api
		 *
		 * @param number|bool $license_id
		 *
		 * @return FS_Subscription|object|bool
		 */
		private function _fetch_site_license_subscription( $license_id = false ) {
			$this->_logger->entrance();
			$api = $this->get_api_site_scope();

			if ( ! is_numeric( $license_id ) ) {
				$license_id = $this->_license->id;
			}

			$result = $api->get( "/licenses/{$license_id}/subscriptions.json", true );

			return ! isset( $result->error ) ?
				( ( is_array( $result->subscriptions ) && 0 < count( $result->subscriptions ) ) ?
					new FS_Subscription( $result->subscriptions[0] ) :
					false
				) :
				$result;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 * @uses   FS_Api
		 *
		 * @param number|bool $plan_id
		 *
		 * @return FS_Plugin_Plan|object
		 */
		private function _fetch_site_plan( $plan_id = false ) {
			$this->_logger->entrance();
			$api = $this->get_api_site_scope();

			if ( ! is_numeric( $plan_id ) ) {
				$plan_id = $this->_site->plan->id;
			}

			$plan = $api->get( "/plans/{$plan_id}.json", true );

			return ! isset( $plan->error ) ? new FS_Plugin_Plan( $plan ) : $plan;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 * @uses   FS_Api
		 *
		 * @return FS_Plugin_Plan[]|object
		 */
		private function _fetch_plugin_plans() {
			$this->_logger->entrance();
			$api = $this->get_api_site_scope();

			$result = $api->get( '/plans.json', true );

			if ( ! isset( $result->error ) ) {
				for ( $i = 0, $len = count( $result->plans ); $i < $len; $i ++ ) {
					$result->plans[ $i ] = new FS_Plugin_Plan( $result->plans[ $i ] );
				}

				$result = $result->plans;
			}

			return $result;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 * @uses   FS_Api
		 *
		 * @param number|bool $plugin_id
		 *
		 * @return FS_Plugin_License[]|object
		 */
		private function _fetch_licenses( $plugin_id = false ) {
			$this->_logger->entrance();

			$api = $this->get_api_user_scope();

			if ( ! is_numeric( $plugin_id ) ) {
				$plugin_id = $this->_plugin->id;
			}

			$result = $api->get( "/plugins/{$plugin_id}/licenses.json", true );

			if ( ! isset( $result->error ) ) {
				for ( $i = 0, $len = count( $result->licenses ); $i < $len; $i ++ ) {
					$result->licenses[ $i ] = new FS_Plugin_License( $result->licenses[ $i ] );
				}

				$result = $result->licenses;
			}

			return $result;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param FS_Plugin_Plan $plan
		 * @param bool           $store
		 */
		private function _update_plan( $plan, $store = false ) {
			$this->_logger->entrance();

			$this->_site->plan = $plan;
			$this->_store_site( $store );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param FS_Plugin_License[] $licenses
		 * @param string|bool         $plugin_slug
		 */
		private function _update_licenses( $licenses, $plugin_slug = false ) {
			$this->_logger->entrance();

			if ( is_array( $licenses ) ) {
				for ( $i = 0, $len = count( $licenses ); $i < $len; $i ++ ) {
					$licenses[ $i ]->updated = time();
				}
			}

			if ( ! is_string( $plugin_slug ) ) {
				$this->_licenses = $licenses;
			}

			$this->_store_licenses( true, $plugin_slug, $licenses );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param bool|number $plugin_id
		 *
		 * @return object|false New plugin tag info if exist.
		 */
		private function _fetch_newer_version( $plugin_id = false ) {
			$latest_tag = $this->_fetch_latest_version( $plugin_id );

			if ( ! is_object( $latest_tag ) ) {
				return false;
			}

			// Check if version is actually newer.
			$has_new_version =
				// If it's an non-installed add-on then always return latest.
				( $this->_is_addon_id( $plugin_id ) && ! $this->is_addon_activated( $plugin_id ) ) ||
				// Compare versions.
				version_compare( $this->get_plugin_version(), $latest_tag->version, '<' );

			$this->_logger->departure( $has_new_version ? 'Found newer plugin version ' . $latest_tag->version : 'No new version' );

			return $has_new_version ? $latest_tag : false;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param bool|number $plugin_id
		 *
		 * @return bool|FS_Plugin_Tag
		 */
		function get_update( $plugin_id = false ) {
			$this->_logger->entrance();

			if ( ! is_numeric( $plugin_id ) ) {
				$plugin_id = $this->_plugin->id;
			}

			$this->_check_updates( true, $plugin_id );
			$updates = $this->get_all_updates();

			return isset( $updates[ $plugin_id ] ) && is_object( $updates[ $plugin_id ] ) ? $updates[ $plugin_id ] : false;
		}

		/**
		 * Check if site assigned with active license.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 */
		function has_active_license() {
			return (
				is_object( $this->_license ) &&
				is_numeric( $this->_license->id ) &&
				! $this->_license->is_expired()
			);
		}

		/**
		 * Check if site assigned with license with enabled features.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return bool
		 */
		function has_features_enabled_license() {
			return (
				is_object( $this->_license ) &&
				is_numeric( $this->_license->id ) &&
				$this->_license->is_features_enabled()
			);
		}

		/**
		 * Sync site's plan.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @uses   FS_Api
		 *
		 * @param bool $background Hints the method if it's a background sync. If false, it means that was initiated by the admin.
		 */
		private function _sync_license( $background = false ) {
			$this->_logger->entrance();

			$plugin_id = fs_request_get( 'plugin_id', $this->get_id() );

			$is_addon_sync = ( ! $this->_plugin->is_addon() && $plugin_id != $this->get_id() );

			if ( $is_addon_sync ) {
				$this->_sync_addon_license( $plugin_id, $background );
			} else {
				$this->_sync_plugin_license( $background );
			}

			$this->do_action( 'after_account_plan_sync', $this->_site->plan->name );
		}

		/**
		 * Sync plugin's add-on license.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 * @uses   FS_Api
		 *
		 * @param number $addon_id
		 * @param bool   $background
		 */
		private function _sync_addon_license( $addon_id, $background ) {
			$this->_logger->entrance();

			if ( $this->is_addon_activated( $addon_id ) ) {
				// If already installed, use add-on sync.
				$fs_addon = self::get_instance_by_id( $addon_id );
				$fs_addon->_sync_license( $background );

				return;
			}

			// Validate add-on exists.
			$addon = $this->get_addon( $addon_id );

			if ( ! is_object( $addon ) ) {
				return;
			}

			// Add add-on into account add-ons.
			$account_addons = $this->get_account_addons();
			if ( ! is_array( $account_addons ) ) {
				$account_addons = array();
			}
			$account_addons[] = $addon->id;
			$account_addons   = array_unique( $account_addons );
			$this->_store_account_addons( $account_addons );

			// Load add-on licenses.
			$licenses = $this->_fetch_licenses( $addon->id );

			// Sync add-on licenses.
			if ( ! isset( $licenses->error ) ) {
				$this->_update_licenses( $licenses, $addon->slug );

				if ( ! $this->is_addon_installed( $addon->slug ) && FS_License_Manager::has_premium_license( $licenses ) ) {
					$plans_result = $this->get_api_site_or_plugin_scope()->get( "/addons/{$addon_id}/plans.json" );

					if ( ! isset( $plans_result->error ) ) {
						$plans = $plans_result->plans;

						$this->_admin_notices->add_sticky(
							FS_Plan_Manager::instance()->has_free_plan( $plans ) ?
								sprintf(
									__( 'Your %s Add-on plan was successfully upgraded.', WP_FS__SLUG ),
									$addon->title
								) . ' ' . $this->_get_latest_download_link(
									__( 'Download the latest version now', WP_FS__SLUG ),
									$addon_id
								)
								:
								sprintf(
									__( '%s Add-on was successfully purchased.', WP_FS__SLUG ),
									$addon->title
								) . ' ' . $this->_get_latest_download_link(
									__( 'Download the latest version now', WP_FS__SLUG ),
									$addon_id
								),
							'addon_plan_upgraded',
							__( 'Ye-ha!', WP_FS__SLUG )
						);
					}
				}
			}
		}

		/**
		 * Sync site's plugin plan.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 * @uses   FS_Api
		 *
		 * @param bool $background Hints the method if it's a background sync. If false, it means that was initiated by the admin.
		 */
		private function _sync_plugin_license( $background = false ) {
			$this->_logger->entrance();

			// Load site details.
			$site = $this->_fetch_site( true );

			$plan_change = 'none';

			if ( isset( $site->error ) ) {
				$api = $this->get_api_site_scope();

				// Try to ping API to see if not blocked.
				if ( ! $api->test() ) {
					// Failed to ping API - blocked!
					$this->_admin_notices->add(
						sprintf( __( 'Your server is blocking the access to Freemius\' API, which is crucial for %1s license synchronization. Please contact your host to whitelist %2s', WP_FS__SLUG ), $this->get_plugin_name(), '<a href="' . $api->get_url() . '" target="_blank">' . $api->get_url() . '</a>' ) . '<br> Error received from the server: ' . var_export( $site->error, true ),
						__( 'Oops...', WP_FS__SLUG ),
						'error',
						$background
					);
				} else {
					// Authentication params are broken.
					$this->_admin_notices->add(
						__( 'It seems like one of the authentication parameters is wrong. Update your Public Key, Secret Key & User ID, and try again.', WP_FS__SLUG ),
						__( 'Oops...', WP_FS__SLUG ),
						'error'
					);
				}

				// Plan update failure, set update time to 24hours + 10min so it won't annoy the admin too much.
				$this->_site->updated = time() - WP_FS__TIME_24_HOURS_IN_SEC + WP_FS__TIME_10_MIN_IN_SEC;
			} else {
				// Sync licenses.
				$this->_sync_licenses();

				// Check if plan / license changed.
				if ( ! FS_Entity::equals( $site->plan, $this->_site->plan ) ||
				     // Check if trial started.
				     $site->trial_plan_id != $this->_site->trial_plan_id ||
				     $site->trial_ends != $this->_site->trial_ends ||
				     // Check if license changed.
				     $site->license_id != $this->_site->license_id
				) {
					if ( $site->is_trial() && ! $this->_site->is_trial() ) {
						// New trial started.
						$this->_site = $site;
						$plan_change = 'trial_started';

						// Store trial plan information.
						$this->_enrich_site_trial_plan( true );

					} else if ( $this->_site->is_trial() && ! $site->is_trial() && ! is_numeric( $site->license_id ) ) {
						// Was in trial, but now trial expired and no license ID.
						// New trial started.
						$this->_site = $site;
						$plan_change = 'trial_expired';

						// Clear trial plan information.
						$this->_storage->trial_plan = null;

					} else {
						$is_free = $this->is_free_plan();

						// Make sure license exist and not expired.
						$new_license = is_null( $site->license_id ) ? null : $this->_get_license_by_id( $site->license_id );

						if ( $is_free && ( ( ! is_object( $new_license ) || $new_license->is_expired() ) ) ) {
							// The license is expired, so ignore upgrade method.
						} else {
							// License changed.
							$this->_site = $site;
							$this->_update_site_license( $new_license );
							$this->_store_licenses();
							$this->_enrich_site_plan( true );

							$plan_change = $is_free ?
								'upgraded' :
								( is_object( $new_license ) ?
									'changed' :
									'downgraded' );
						}
					}

					// Store updated site info.
					$this->_store_site();
				} else {
					if ( is_object( $this->_license ) && $this->_license->is_expired() ) {
						if ( ! $this->has_features_enabled_license() ) {
							$this->_deactivate_license();
							$plan_change = 'downgraded';
						} else {
							$plan_change = 'expired';
						}
					}

					if ( is_numeric( $site->license_id ) && is_object( $this->_license ) ) {
						$this->_sync_site_subscription( $this->_license );
					}
				}
			}

			switch ( $plan_change ) {
				case 'none':
					if ( ! $background && is_admin() ) {
						$this->_admin_notices->add(
							sprintf(
								__( 'It looks like your plan did\'t change. If you did upgrade, it\'s probably an issue on our side - sorry. %1sPlease contact us here%2s', WP_FS__SLUG ),
								'<a href="' . $this->contact_url( 'bug', sprintf( __( 'I have upgraded my account but when I try to Sync the License, the plan remains %s.', WP_FS__SLUG ), strtoupper( $this->_site->plan->name ) ) ) . '">',
								'</a>'
							),
							__( 'Hmm...', WP_FS__SLUG ),
							'error'
						);
					}
					break;
				case 'upgraded':
					$this->_admin_notices->add_sticky(
						sprintf(
							__( 'Your plan was successfully upgraded.', WP_FS__SLUG ),
							'<i>' . $this->get_plugin_name() . '</i>'
						) . ( $this->is_premium() ? '' : ' ' . $this->_get_latest_download_link( sprintf(
								__( 'Download the latest %s version now', WP_FS__SLUG ),
								$this->_site->plan->title
							) )
						),
						'plan_upgraded',
						__( 'Ye-ha!', WP_FS__SLUG )
					);

					$this->_admin_notices->remove_sticky( array(
						'trial_started',
						'trial_promotion',
						'trial_expired',
						'activation_complete',
					) );
					break;
				case 'changed':
					$this->_admin_notices->add_sticky(
						sprintf(
							__( 'Your plan was successfully changed to %s.', WP_FS__SLUG ),
							$this->_site->plan->title
						),
						'plan_changed'
					);

					$this->_admin_notices->remove_sticky( array(
						'trial_started',
						'trial_promotion',
						'trial_expired',
						'activation_complete',
					) );
					break;
				case 'downgraded':
					$this->_admin_notices->add_sticky(
						sprintf( __( 'Your license has expired. You can still continue using the free plugin forever.', WP_FS__SLUG ) ),
						'license_expired',
						__( 'Hmm...', WP_FS__SLUG )
					);
					$this->_admin_notices->remove_sticky( 'plan_upgraded' );
					break;
				case 'expired':
					$this->_admin_notices->add_sticky(
						sprintf( __( 'Your license has expired. You can still continue using all the %s features, but you\'ll need to renew your license to continue getting updates and support.', WP_FS__SLUG ), $this->_site->plan->title ),
						'license_expired',
						__( 'Hmm...', WP_FS__SLUG )
					);
					$this->_admin_notices->remove_sticky( 'plan_upgraded' );
					break;
				case 'trial_started':
					$this->_admin_notices->add_sticky(
						sprintf(
							__( 'Your trial has been successfully started.', WP_FS__SLUG ),
							'<i>' . $this->get_plugin_name() . '</i>'
						) . ( $this->is_premium() ? '' : ' ' . $this->_get_latest_download_link( sprintf(
								__( 'Download the latest %s version now', WP_FS__SLUG ),
								$this->_storage->trial_plan->title
							) ) ),
						'trial_started',
						__( 'Ye-ha!', WP_FS__SLUG )
					);

					$this->_admin_notices->remove_sticky( array(
						'trial_promotion',
					) );
					break;
				case 'trial_expired':
					$this->_admin_notices->add_sticky(
						__( 'Your trial has expired. You can still continue using all our free features.', WP_FS__SLUG ),
						'trial_expired',
						__( 'Hm...', WP_FS__SLUG )
					);
					$this->_admin_notices->remove_sticky( array(
						'trial_started',
						'trial_promotion',
						'plan_upgraded',
					) );
					break;
			}

			if ( 'none' !== $plan_change ) {
				$this->do_action( 'after_license_change', $plan_change, $this->_site->plan );
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param bool $background
		 */
		protected function _activate_license( $background = false ) {
			$this->_logger->entrance();

			$premium_license = $this->_get_available_premium_license();

			if ( ! is_object( $premium_license ) ) {
				return;
			}

			$api     = $this->get_api_site_scope();
			$license = $api->call( "/licenses/{$premium_license->id}.json", 'put' );

			if ( isset( $license->error ) ) {
				if ( ! $background ) {
					$this->_admin_notices->add(
						__( 'It looks like the license could not be activated.', WP_FS__SLUG ) . '<br> Error received from the server: ' . var_export( $license->error, true ),
						__( 'Hmm...', WP_FS__SLUG ),
						'error'
					);
				}

				return;
			}

			$premium_license = new FS_Plugin_License( $license );

			// Updated site plan.
			$this->_site->plan->id = $premium_license->plan_id;
			$this->_update_site_license( $premium_license );
			$this->_enrich_site_plan( false );

			$this->_store_account();

			if ( ! $background ) {
				$this->_admin_notices->add_sticky(
					__( 'Your license was successfully activated.', WP_FS__SLUG ) .
					( $this->is_premium() ? '' : ' ' . $this->_get_latest_download_link( sprintf(
						__( 'Download the latest %s version now', WP_FS__SLUG ),
						$this->_site->plan->title
					) ) ),
					'license_activated',
					__( 'Ye-ha!', WP_FS__SLUG )
				);
			}

			$this->_admin_notices->remove_sticky(array(
				'trial_promotion',
				'license_expired',
			));
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param bool $show_notice
		 */
		protected function _deactivate_license( $show_notice = true ) {
			$this->_logger->entrance();

			if ( ! is_object( $this->_license ) ) {
				$this->_admin_notices->add(
					sprintf( __( 'It looks like your site currently don\'t have an active license.', WP_FS__SLUG ), $this->_site->plan->title ),
					__( 'Hmm...', WP_FS__SLUG )
				);

				return;
			}

			$api     = $this->get_api_site_scope();
			$license = $api->call( "/licenses/{$this->_site->license_id}.json", 'delete' );

			if ( isset( $license->error ) ) {
				$this->_admin_notices->add(
					__( 'It looks like the license deactivation failed.', WP_FS__SLUG ) . '<br> Error received from the server: ' . var_export( $license->error, true ),
					__( 'Hmm...', WP_FS__SLUG ),
					'error'
				);

				return;
			}

			// Update license cache.
			for ( $i = 0, $len = count( $this->_licenses ); $i < $len; $i ++ ) {
				if ( $license->id == $this->_licenses[ $i ]->id ) {
					$this->_licenses[ $i ] = new FS_Plugin_License( $license );
				}
			}

			// Updated site plan to default.
			$this->_sync_plans();
			$this->_site->plan->id = $this->_plans[0]->id;
			// Unlink license from site.
			$this->_update_site_license( null );
			$this->_enrich_site_plan( false );

			$this->_store_account();

			if ( $show_notice ) {
				$this->_admin_notices->add(
					sprintf( __( 'Your license was successfully deactivated, you are back to the %1s plan.', WP_FS__SLUG ), $this->_site->plan->title ),
					__( 'O.K', WP_FS__SLUG )
				);
			}

			$this->_admin_notices->remove_sticky( array(
				'plan_upgraded',
				'license_activated',
			) );
		}

		/**
		 * Site plan downgrade.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @uses   FS_Api
		 */
		private function _downgrade_site() {
			$this->_logger->entrance();

			$api  = $this->get_api_site_scope();
			$site = $api->call( 'downgrade.json', 'put' );

			$plan_downgraded = false;
			$plan            = false;
			if ( ! isset( $site->error ) ) {
				$prev_plan_id = $this->_site->plan->id;

				// Update new site plan id.
				$this->_site->plan->id = $site->plan_id;

				$plan         = $this->_enrich_site_plan();
				$subscription = $this->_sync_site_subscription( $this->_license );

				// Plan downgraded if plan was changed or subscription was cancelled.
				$plan_downgraded = ( $plan instanceof FS_Plugin_Plan && $prev_plan_id != $plan->id ) ||
				                   ( is_object( $subscription ) && ! isset( $subscription->error ) && ! $subscription->is_active() );
			} else {
				// handle different error cases.

			}

			if ( $plan_downgraded ) {
				// Remove previous sticky message about upgrade (if exist).
				$this->_admin_notices->remove_sticky( 'plan_upgraded' );

				$this->_admin_notices->add(
					sprintf( __( 'Your plan was successfully downgraded. Your %s plan license will expire in %s.', WP_FS__SLUG ),
						$plan->title,
						human_time_diff( time(), strtotime( $this->_license->expiration ) )
					)
				);

				// Store site updates.
				$this->_store_site();
			} else {
				$this->_admin_notices->add(
					__( 'Seems like we are having some temporary issue with your plan downgrade. Please try again in few minutes.', WP_FS__SLUG ),
					__( 'Oops...' ),
					'error'
				);
			}
		}

		/**
		 * Cancel site trial.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @uses   FS_Api
		 */
		private function _cancel_trial() {
			$this->_logger->entrance();

			if ( ! $this->is_trial() ) {
				$this->_admin_notices->add(
					__( 'It looks like you are not in trial mode anymore so there\'s nothing to cancel :)', WP_FS__SLUG ),
					__( 'Oops...' ),
					'error'
				);

				return;
			}

			$api  = $this->get_api_site_scope();
			$site = $api->call( 'trials.json', 'delete' );

			$trial_cancelled = false;

			if ( ! isset( $site->error ) ) {
				$prev_trial_ends = $this->_site->trial_ends;

				// Update new site plan id.
				$this->_site->trial_ends = $site->trial_ends;

				$trial_cancelled = ( $prev_trial_ends != $site->trial_ends );
			} else {
				// handle different error cases.

			}

			if ( $trial_cancelled ) {
				// Remove previous sticky message about upgrade (if exist).
				$this->_admin_notices->remove_sticky( 'plan_upgraded' );

				$this->_admin_notices->add(
					sprintf( __( 'Your %s Plan trial was successfully cancelled.', WP_FS__SLUG ), $this->_storage->trial_plan->title )
				);

				$this->_admin_notices->remove_sticky( array(
					'trial_started',
					'trial_promotion',
					'plan_upgraded',
				) );

				// Store site updates.
				$this->_store_site();

				// Clear trial plan information.
				unset( $this->_storage->trial_plan );
			} else {
				$this->_admin_notices->add(
					__( 'Seems like we are having some temporary issue with your trial cancellation. Please try again in few minutes.', WP_FS__SLUG ),
					__( 'Oops...' ),
					'error'
				);
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param bool|number $plugin_id
		 *
		 * @return bool
		 */
		private function _is_addon_id( $plugin_id ) {
			return is_numeric( $plugin_id ) && ( $this->get_id() != $plugin_id );
		}

		/**
		 * Check if user eligible to download premium version updates.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return bool
		 */
		private function _can_download_premium() {
			return $this->has_active_license() ||
			       ( $this->is_trial() && ! $this->get_trial_plan()->is_free() );
		}

		/**
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param bool|number $addon_id
		 * @param string      $type "json" or "zip"
		 *
		 * @return string
		 */
		private function _get_latest_version_endpoint( $addon_id = false, $type = 'json' ) {

			$is_addon = $this->_is_addon_id( $addon_id );

			$is_premium = null;
			if ( ! $is_addon ) {
				$is_premium = $this->_can_download_premium();
			} else if ( $this->is_addon_activated( $addon_id ) ) {
				$is_premium = self::get_instance_by_id( $addon_id )->_can_download_premium();
			}

			return // If add-on, then append add-on ID.
				( $is_addon ? "/addons/$addon_id" : '' ) .
				'/updates/latest.' . $type .
				// If add-on and not yet activated, try to fetch based on server licensing.
				( is_bool( $is_premium ) ? '?is_premium=' . json_encode( $is_premium ) : '' );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param bool|number $addon_id
		 *
		 * @return object|false Plugin latest tag info.
		 */
		function _fetch_latest_version( $addon_id = false ) {
			$tag            = $this->get_api_site_or_plugin_scope()->get( $this->_get_latest_version_endpoint( $addon_id, 'json' ), true );
			$latest_version = ( is_object( $tag ) && isset( $tag->version ) ) ? $tag->version : 'couldn\'t get';
			$this->_logger->departure( 'Latest version ' . $latest_version );

			return ( is_object( $tag ) && isset( $tag->version ) ) ? $tag : false;
		}

		#region Download Plugin ------------------------------------------------------------------

		/**
		 * Download latest plugin version, based on plan.
		 * The download will be fetched via the API first.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param bool|number $plugin_id
		 *
		 * @uses   FS_Api
		 *
		 * @deprecated
		 */
		private function _download_latest( $plugin_id = false ) {
			$this->_logger->entrance();

			$is_addon = $this->_is_addon_id( $plugin_id );

			$is_premium = $this->_can_download_premium();

			$latest = $this->get_api_site_scope()->call(
				$this->_get_latest_version_endpoint( $plugin_id, 'zip' )
			);

			$slug = $this->_slug;
			if ( $is_addon ) {
				$addon = $this->get_addon( $plugin_id );
				$slug  = is_object( $addon ) ? $addon->slug : 'addon';
			}

			if ( ! is_object( $latest ) ) {
				header( "Content-Type: application/zip" );
				header( "Content-Disposition: attachment; filename={$slug}" . ( !$is_addon && $is_premium ? '-premium' : '' ) . ".zip" );
				header( "Content-Length: " . strlen( $latest ) );
				echo $latest;

				exit();
			}
		}

		/**
		 * Download latest plugin version, based on plan.
		 *
		 * Not like _download_latest(), this will redirect the page
		 * to secure download url to prevent dual download (from FS to WP server,
		 * and then from WP server to the client / browser).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param bool|number $plugin_id
		 *
		 * @uses   FS_Api
		 * @uses   wp_redirect()
		 */
		private function _download_latest_directly( $plugin_id = false ) {
			$this->_logger->entrance();

			wp_redirect( $this->_get_latest_download_api_url( $plugin_id ) );
		}

		/**
		 * Get latest plugin FS API download URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param bool|number $plugin_id
		 *
		 * @return string
		 */
		private function _get_latest_download_api_url( $plugin_id = false ) {
			$this->_logger->entrance();

			return $this->get_api_site_scope()->get_signed_url(
				$this->_get_latest_version_endpoint( $plugin_id, 'zip' )
			);
		}

		/**
		 * Get latest plugin download link.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param string      $label
		 * @param bool|number $plugin_id
		 *
		 * @return string
		 */
		private function _get_latest_download_link( $label, $plugin_id = false ) {
			return sprintf(
				'<a target="_blank" href="%s">%s</a>',
				$this->_get_latest_download_local_url( $plugin_id ),
				$label
			);
		}

		/**
		 * Get latest plugin download local URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param bool|number $plugin_id
		 *
		 * @return string
		 */
		function _get_latest_download_local_url($plugin_id = false) {
			// Add timestamp to protect from caching.
			$params = array( 'ts' => WP_FS__SCRIPT_START_TIME );

			if ( ! empty( $plugin_id ) ) {
				$params['plugin_id'] = $plugin_id;
			}

			return $this->get_account_url( 'download_latest', $params );
		}

		#endregion Download Plugin ------------------------------------------------------------------

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @uses   FS_Api
		 *
		 * @param bool        $background Hints the method if it's a background updates check. If false, it means that was initiated by the admin.
		 * @param bool|number $plugin_id
		 */
		private function _check_updates( $background = false, $plugin_id = false ) {
			$this->_logger->entrance();

			// Check if there's a newer version for download.
			$new_version = $this->_fetch_newer_version( $plugin_id );

			$update = null;
			if ( is_object( $new_version ) ) {
				$update = new FS_Plugin_Tag( $new_version );

				if ( ! $background ) {
					$this->_admin_notices->add(
						sprintf(
							__( 'Version %1s was released. Please download our %2slatest %3s version here%4s.', WP_FS__SLUG ), $update->version, '<a href="' . $this->get_account_url( 'download_latest' ) . '">', $this->_site->plan->title, '</a>' ),
						__( 'New!', WP_FS__SLUG )
					);
				}
			} else if ( false === $new_version && ! $background ) {
				$this->_admin_notices->add(
					__( 'Seems like you got the latest release.', WP_FS__SLUG ),
					__( 'You are all good!', WP_FS__SLUG )
				);
			}

			$this->_store_update( $update, true, $plugin_id );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @uses   FS_Api
		 *
		 */
		private function _sync_addons() {
			$this->_logger->entrance();

			$result = $this->get_api_site_or_plugin_scope()->get( '/addons.json?enriched=true', true );

			if ( isset( $result->error ) ) {
				return;
			}

			$addons = array();
			for ( $i = 0, $len = count( $result->plugins ); $i < $len; $i ++ ) {
				$addons[ $i ] = new FS_Plugin( $result->plugins[ $i ] );
			}

			$this->_store_addons( $addons, true );
		}

		/**
		 * Handle user email update.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 * @uses   FS_Api
		 *
		 * @return object
		 */
		private function _update_email() {
			$this->_logger->entrance();
			$new_email = fs_request_get( 'fs_email_' . $this->_slug, '' );

			$api  = $this->get_api_user_scope();
			$user = $api->call( "?plugin_id={$this->_plugin->id}&fields=id,email,is_verified", 'put', array(
				'email'                   => $new_email,
				'after_email_confirm_url' => $this->_get_admin_page_url(
					'account',
					array( 'fs_action' => 'sync_user' )
				),
			) );

			if ( ! isset( $user->error ) ) {
				$this->_user->email       = $user->email;
				$this->_user->is_verified = $user->is_verified;
				$this->_store_user();
			} else {
				// handle different error cases.

			}

			return $user;
		}

		/**
		 * Handle user name update.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 * @uses   FS_Api
		 *
		 * @return object
		 */
		private function _update_user_name() {
			$this->_logger->entrance();
			$name = fs_request_get( 'fs_user_name_' . $this->_slug, '' );

			$api  = $this->get_api_user_scope();
			$user = $api->call( "?plugin_id={$this->_plugin->id}&fields=id,first,last", 'put', array(
				'name' => $name,
			) );

			if ( ! isset( $user->error ) ) {
				$this->_user->first = $user->first;
				$this->_user->last  = $user->last;
				$this->_store_user();
			} else {
				// handle different error cases.

			}

			return $user;
		}

		/**
		 * Verify user email.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 * @uses   FS_Api
		 */
		private function _verify_email() {
			$this->_handle_account_user_sync();

			if ( $this->_user->is_verified() ) {
				return;
			}

			$api    = $this->get_api_site_scope();
			$result = $api->call( "/users/{$this->_user->id}/verify.json", 'put', array(
				'after_email_confirm_url' => $this->_get_admin_page_url(
					'account',
					array( 'fs_action' => 'sync_user' )
				)
			) );

			if ( ! isset( $result->error ) ) {
				$this->_admin_notices->add( sprintf( __( 'Verification mail was just sent to %s. If you can\'t find it after 5 min, please check your spam box.', WP_FS__SLUG ), sprintf( '<a href="mailto:%1s">%2s</a>', esc_url( $this->_user->email ), $this->_user->email ) ) );
			} else {
				// handle different error cases.

			}
		}

		/**
		 * Handle account page updates / edits / actions.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 */
		private function _handle_account_edits() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$plugin_id = fs_request_get( 'plugin_id', $this->get_id() );
			$action = fs_get_action();

			switch ($action)
			{
				case 'delete_account':
					check_admin_referer( $action );

					if ( $plugin_id == $this->get_id() ) {
						$this->delete_account_event();

						if ( fs_redirect( $this->_get_admin_page_url() ) ) {
							exit();
						}
					} else {
						if ( $this->is_addon_activated( $plugin_id ) ) {
							$fs_addon = self::get_instance_by_id( $plugin_id );
							$fs_addon->delete_account_event();

							if ( fs_redirect( $this->_get_admin_page_url( 'account' ) ) ) {
								exit();
							}
						}
					}

					return;

				case 'downgrade_account':
					check_admin_referer( $action );
					$this->_downgrade_site();

					return;

				case 'activate_license':
					check_admin_referer( $action );

					if ( $plugin_id == $this->get_id() ) {
						$this->_activate_license();
					} else {
						if ( $this->is_addon_activated( $plugin_id ) ) {
							$fs_addon = self::get_instance_by_id( $plugin_id );
							$fs_addon->_activate_license();
						}
					}

					return;

				case 'deactivate_license':
					check_admin_referer( $action );

					if ( $plugin_id == $this->get_id() ) {
						$this->_deactivate_license();
					} else {
						if ( $this->is_addon_activated( $plugin_id ) ) {
							$fs_addon = self::get_instance_by_id( $plugin_id );
							$fs_addon->_deactivate_license();
						}
					}

					return;

				case 'check_updates':
					check_admin_referer( $action );
					$this->_check_updates();

					return;

				case 'update_email':
					check_admin_referer( 'update_email' );

					$result = $this->_update_email();

					if ( isset( $result->error ) ) {
						switch ( $result->error->code ) {
							case 'user_exist':
								$this->_admin_notices->add(
									__( 'Sorry, we could not complete the email update. Another user with the same email is already registered.', WP_FS__SLUG ),
									__( 'Oops...', WP_FS__SLUG ),
									'error'
								);
								break;
						}
					} else {
						$this->_admin_notices->add( __( 'Your email was successfully updated. You should receive an email with confirmation instructions in few moments.', WP_FS__SLUG ) );
					}

					return;

				case 'update_user_name':
					check_admin_referer( 'update_user_name' );

					$result = $this->_update_user_name();

					if ( isset( $result->error ) ) {
						$this->_admin_notices->add(
							__( 'Please provide your full name.', WP_FS__SLUG ),
							__( 'Oops...', WP_FS__SLUG ),
							'error'
						);
					} else {
						$this->_admin_notices->add( __( 'Your name was successfully updated.', WP_FS__SLUG ) );
					}

					return;

				#region Actions that might be called from external links (e.g. email)

				case 'cancel_trial':
					$this->_cancel_trial();

					return;

				case 'verify_email':
					$this->_verify_email();

					return;

				case 'sync_user':
					$this->_handle_account_user_sync();

					return;

				case $this->_slug . '_sync_license':
					$this->_sync_license();

					return;

				case 'download_latest':
					$this->_download_latest_directly( $plugin_id );

					return;

				#endregion
			}


			if ( WP_FS__IS_POST_REQUEST ) {
				$properties = array( 'site_secret_key', 'site_id', 'site_public_key' );
				foreach ( $properties as $p ) {
					if ( 'update_' . $p  === $action ) {
						check_admin_referer( $action );

						$this->_logger->log( $action );

						$site_property                      = substr( $p, strlen( 'site_' ) );
						$site_property_value                = fs_request_get( 'fs_' . $p . '_' . $this->_slug, '' );
						$this->get_site()->{$site_property} = $site_property_value;

						// Store account after modification.
						$this->_store_site();

						$this->do_action( 'account_property_edit', 'site', $site_property, $site_property_value );

						$this->_admin_notices->add( sprintf(
							__( 'You have successfully updated your %s .', WP_FS__SLUG ),
							'<b>' . str_replace( '_', ' ', $p ) . '</b>' ) );

						return;
					}
				}
			}
		}

		/**
		 * Account page resources load.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 */
		function _account_page_load() {
			$this->_logger->entrance();

			$this->_logger->info( var_export( $_REQUEST, true ) );

			fs_enqueue_local_style( 'fs_account', '/admin/account.css' );

			if ( $this->_has_addons() ) {
				wp_enqueue_script( 'plugin-install' );
				add_thickbox();

				function fs_addons_body_class( $classes ) {
					$classes .= ' plugins-php';

					return $classes;
				}

				add_filter( 'admin_body_class', 'fs_addons_body_class' );
			}

			$this->_handle_account_edits();

			$this->do_action( 'account_page_load_before_departure' );
		}

		/**
		 * Render account page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 */
		function _account_page_render() {
			$this->_logger->entrance();

			$vars = array( 'slug' => $this->_slug );
			fs_require_once_template( 'account.php', $vars );
		}

		/**
		 * Render account connect page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 */
		function _connect_page_render() {
			$this->_logger->entrance();

			$vars = array( 'slug' => $this->_slug );
			if ( $this->is_pending_activation() ) {
				fs_require_once_template( 'pending-activation.php', $vars );
			} else {
				fs_require_once_template( 'connect.php', $vars );
			}
		}

		/**
		 * Load required resources before add-ons page render.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 */
		function _addons_page_load() {
			$this->_logger->entrance();

			fs_enqueue_local_style( 'fs_addons', '/admin/add-ons.css' );

			wp_enqueue_script( 'plugin-install' );
			add_thickbox();

			function fs_addons_body_class( $classes ) {
				$classes .= ' plugins-php';

				return $classes;
			}

			add_filter( 'admin_body_class', 'fs_addons_body_class' );

			if ( ! $this->is_registered() && $this->is_org_repo_compliant() ) {
				$this->_admin_notices->add(
					sprintf( __( 'Just letting you know that the add-ons information of %s is being pulled from external server.', WP_FS__SLUG ), '<b>' . $this->get_plugin_name() . '</b>' ),
					__( 'Heads up ', WP_FS__SLUG ),
					'update-nag'
				);
			}
		}

		/**
		 * Render add-ons page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 */
		function _addons_page_render() {
			$this->_logger->entrance();

			$vars = array( 'slug' => $this->_slug );
			fs_require_once_template( 'add-ons.php', $vars );
		}

		/* Pricing & Upgrade
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * Render pricing page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 */
		function _pricing_page_render() {
			$this->_logger->entrance();

			$vars = array( 'slug' => $this->_slug );

			if ( 'true' === fs_request_get( 'checkout', false ) ) {
				fs_require_once_template( 'checkout.php', $vars );
			} else {
				fs_require_once_template( 'pricing.php', $vars );
			}
		}

		#region Contact Us ------------------------------------------------------------------

		/**
		 * Render contact-us page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 */
		function _contact_page_render() {
			$this->_logger->entrance();

			$vars = array( 'slug' => $this->_slug );
			fs_require_once_template( 'contact.php', $vars );
		}

		#endregion ------------------------------------------------------------------

		/**
		 * Hide all admin notices to prevent distractions.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @uses   remove_all_actions()
		 */
		function _hide_admin_notices() {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'network_admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'user_admin_notices' );
		}

		function _clean_admin_content_section_hook() {
			$this->_hide_admin_notices();

			// Hide footer.
			echo '<style>#wpfooter { display: none !important; }</style>';
		}

		/**
		 * Attach to admin_head hook to hide all admin notices.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 */
		function _clean_admin_content_section() {
			add_action( 'admin_head', array( &$this, '_clean_admin_content_section_hook' ) );
		}

		/* CSS & JavaScript
		------------------------------------------------------------------------------------------------------------------*/
		/*		function _enqueue_script($handle, $src) {
					$url = plugins_url( substr( WP_FS__DIR_JS, strlen( $this->_plugin_dir_path ) ) . '/assets/js/' . $src );

					$this->_logger->entrance( 'script = ' . $url );

					wp_enqueue_script( $handle, $url );
				}*/

		/* SDK
		------------------------------------------------------------------------------------------------------------------*/
		private $_user_api;

		/**
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 * @return FS_Api
		 */
		function get_api_user_scope() {
			if ( ! isset( $this->_user_api ) ) {
				$this->_user_api = FS_Api::instance(
					$this->_slug,
					'user',
					$this->_user->id,
					$this->_user->public_key,
					! $this->is_live(),
					$this->_user->secret_key
				);
			}

			return $this->_user_api;
		}

		private $_site_api;

		/**
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 * @return FS_Api
		 */
		function get_api_site_scope() {
			if ( ! isset( $this->_site_api ) ) {
				$this->_site_api = FS_Api::instance(
					$this->_slug,
					'install',
					$this->_site->id,
					$this->_site->public_key,
					! $this->is_live(),
					$this->_site->secret_key
				);
			}

			return $this->_site_api;
		}

		private $_plugin_api;

		/**
		 * Get plugin public API scope.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return FS_Api
		 */
		function get_api_plugin_scope() {
			if ( ! isset( $this->_plugin_api ) ) {
				$this->_plugin_api = FS_Api::instance(
					$this->_slug,
					'plugin',
					$this->_plugin->id,
					$this->_plugin->public_key,
					! $this->is_live()
				);
			}

			return $this->_plugin_api;
		}

		/**
		 * Get site API scope object (fallback to public plugin scope when not registered).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return FS_Api
		 */
		function get_api_site_or_plugin_scope() {
			return $this->is_registered() ?
				$this->get_api_site_scope() :
				$this->get_api_plugin_scope();
		}

		/**
		 * Show trial promotional notice (if any trial exist).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param $plans
		 */
		function _check_for_trial_plans( $plans ) {
			$this->_storage->has_trial_plan = FS_Plan_Manager::instance()->has_trial_plan( $plans );
		}

		/**
		 * Show trial promotional notice (if any trial exist).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		function _add_trial_notice() {
			// Check if trial already utilized.
			if ( $this->_site->is_trial_utilized() ) {
				return;
			}

			// Check if already paying.
			if ( $this->is_paying() ) {
				return;
			}

			// Check if trial message is already shown.
			if ( $this->_admin_notices->has_sticky( 'trial_promotion' ) ) {
				return;
			}

			$trial_plans       = FS_Plan_Manager::instance()->get_trial_plans( $this->_plans );
			$trial_plans_count = count( $trial_plans );

			// Check if any of the plans contains trial.
			if ( 0 === $trial_plans_count ) {
				return;
			}

			/**
			 * @var FS_Plugin_Plan $paid_plan
			 */
			$paid_plan            = $trial_plans[0];
			$require_subscription = $paid_plan->is_require_subscription;
			$upgrade_url          = $this->get_trial_url();
			$cc_string            = $require_subscription ?
				sprintf( __( 'No commitment for %s days - cancel anytime!', WP_FS__SLUG ), $paid_plan->trial_period ) :
				__( 'No credit card required!', WP_FS__SLUG );


			$total_paid_plans = count( $this->_plans ) - ( FS_Plan_Manager::instance()->has_free_plan( $this->_plans ) ? 1 : 0 );

			if ( $total_paid_plans === $trial_plans_count ) {
				// All paid plans have trials.
				$message = sprintf(
					__( 'Hey! How do you like %s so far? Test all our awesome premium features with a %d-day free trial.', WP_FS__SLUG ) . ' ' . $cc_string,
					sprintf( '<b>%s</b>', $this->get_plugin_name() ),
					$paid_plan->trial_period
				);
			} else {
				$plans_string = '';
				for ( $i = 0; $i < $trial_plans_count; $i ++ ) {
					$plans_string .= sprintf( '<a href="%s">%s</a>', $upgrade_url, $trial_plans[ $i ]->title );

					if ( $i < $trial_plans_count - 2 ) {
						$plans_string .= ', ';
					} else if ( $i == $trial_plans_count - 2 ) {
						$plans_string .= ' and ';
					}
				}

				// Not all paid plans have trials.
				$message = sprintf(
					__( 'Hey! How do you like the plugin so far? Test all our %s features with a %d-day free trial.' . $cc_string, WP_FS__SLUG ),
					$plans_string,
					$paid_plan->trial_period
				);
			}

			// Add start trial button.
			$message .= ' ' . sprintf(
					'<a style="margin-left: 10px;" href="%s"><button class="button button-primary">%s &nbsp;&#10140;</button></a>',
					$upgrade_url,
					__( 'Start free trial', WP_FS__SLUG )
				);

			$this->_admin_notices->add_sticky(
				$this->apply_filters( 'trial_promotion_message', $message ),
				'trial_promotion',
				'',
				'promotion'
			);

			$this->_storage->trial_promotion_shown = WP_FS__SCRIPT_START_TIME;
		}

		/* Action Links
		------------------------------------------------------------------------------------------------------------------*/
		private $_action_links_hooked = false;
		private $_action_links = array();

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 *
		 * @return bool
		 */
		private function is_plugin_action_links_hooked() {
			$this->_logger->entrance( json_encode( $this->_action_links_hooked ) );

			return $this->_action_links_hooked;
		}

		/**
		 * Hook to plugin action links filter.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 */
		private function hook_plugin_action_links() {
			$this->_logger->entrance();

			$this->_action_links_hooked = true;

			$this->_logger->log( 'Adding action links hooks.' );

			// Add action link to settings page.
			add_filter( 'plugin_action_links_' . $this->_plugin_basename, array(
				&$this,
				'_modify_plugin_action_links_hook'
			), 10, 2 );
			add_filter( 'network_admin_plugin_action_links_' . $this->_plugin_basename, array(
				&$this,
				'_modify_plugin_action_links_hook'
			), 10, 2 );
		}

		/**
		 * Add plugin action link.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 *
		 * @param      $label
		 * @param      $url
		 * @param bool $external
		 * @param int  $priority
		 * @param bool $key
		 */
		function add_plugin_action_link( $label, $url, $external = false, $priority = 10, $key = false ) {
			$this->_logger->entrance();

			if ( ! isset( $this->_action_links[ $priority ] ) ) {
				$this->_action_links[ $priority ] = array();
			}

			if ( false === $key ) {
				$key = preg_replace( "/[^A-Za-z0-9 ]/", '', strtolower( $label ) );
			}

			$this->_action_links[ $priority ][] = array(
				'label'    => $label,
				'href'     => $url,
				'key'      => $key,
				'external' => $external
			);

			if ( ! $this->is_plugin_action_links_hooked() ) {
				$this->hook_plugin_action_links();
			}
		}

		/**
		 * Adds Upgrade and Add-Ons links to the main Plugins page link actions collection.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 */
		function _add_upgrade_action_link() {
			$this->_logger->entrance();

			if ( $this->is_registered() ) {
				if ( ! $this->is_paying() && $this->has_paid_plan() ) {
					$this->add_plugin_action_link(
						__( 'Upgrade', $this->_slug ),
						$this->get_upgrade_url(),
						false,
						20,
						'upgrade'
					);
				}

				if ( $this->_has_addons() ) {
					$this->add_plugin_action_link(
						__( 'Add-Ons', $this->_slug ),
						$this->_get_admin_page_url( 'addons' ),
						false,
						10,
						'addons'
					);
				}
			}
		}

		/**
		 * Forward page to activation page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 */
		function _redirect_on_activation_hook() {
			$url       = false;
			$plugin_fs = false;

			if ( ! $this->is_addon() ) {
				$plugin_fs = $this;
				$url = $plugin_fs->_get_admin_page_url();
			} else {
				if ( $this->is_parent_plugin_installed() ) {
					$plugin_fs = self::get_parent_instance();
				}

				if ( is_object( $plugin_fs ) ) {
					if ( ! $plugin_fs->is_registered() ) {
						// Forward to parent plugin activation when parent not registered.
						$url = $plugin_fs->_get_admin_page_url();
					} else {
						// Forward to account page.
						$url = $plugin_fs->_get_admin_page_url( 'account' );
					}
				}
			}

			if ( is_string( $url ) ) {
				wp_redirect( $url );
				exit();
			}
		}

		/**
		 * Modify plugin's page action links collection.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 *
		 * @param array $links
		 * @param       $file
		 *
		 * @return array
		 */
		function _modify_plugin_action_links_hook( $links, $file ) {
			$this->_logger->entrance();

			ksort( $this->_action_links );

			foreach ( $this->_action_links as $new_links ) {
				foreach ( $new_links as $link ) {
					$links[ $link['key'] ] = '<a href="' . $link['href'] . '"' . ( $link['external'] ? ' target="_blank"' : '' ) . '>' . $link['label'] . '</a>';
				}
			}

			return $links;
		}

		/**
		 * Adds admin message.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param string $message
		 * @param string $title
		 * @param string $type
		 */
		function add_admin_message( $message, $title = '', $type = 'success' ) {
			$this->_admin_notices->add( $message, $title, $type );
		}

		/* Plugin Auto-Updates (@since 1.0.4)
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * @var string[]
		 */
		private static $_auto_updated_plugins;

		/**
		 * @todo   TEST IF IT WORKS!!!
		 *
		 * Include plugins for automatic updates based on stored settings.
		 *
		 * @see    http://wordpress.stackexchange.com/questions/131394/how-do-i-exclude-plugins-from-getting-automatically-updated/131404#131404
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param bool   $update Whether to update (not used for plugins)
		 * @param object $item   The plugin's info
		 *
		 * @return bool
		 */
		static function _include_plugins_in_auto_update( $update, $item ) {
			// Before version 3.8.2 the $item was the file name of the plugin,
			// while in 3.8.2 statistics were added (https://core.trac.wordpress.org/changeset/27905).
			$by_slug = ( (int) str_replace( '.', '', get_bloginfo( 'version' ) ) >= 382 );

			if ( ! isset( self::$_auto_updated_plugins ) ) {
				$plugins = self::$_accounts->get_option( 'plugins', array() );

				$identifiers = array();
				foreach ( $plugins as $p ) {
					/**
					 * @var FS_Plugin $p
					 */
					if ( isset( $p->auto_update ) && $p->auto_update ) {
						$identifiers[] = ( $by_slug ? $p->slug : plugin_basename( $p->file ) );
					}
				}

				self::$_auto_updated_plugins = $identifiers;
			}

			if ( in_array( $by_slug ? $item->slug : $item, self::$_auto_updated_plugins ) ) {
				return true;
			}

			// Pass update decision to next filters
			return $update;
		}

		#region Versioning ------------------------------------------------------------------

		/**
		 * Check if Freemius in SDK upgrade mode.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function is_sdk_upgrade_mode() {
			return isset( $this->_storage->sdk_upgrade_mode ) ?
				$this->_storage->sdk_upgrade_mode :
				false;
		}

		/**
		 * Turn SDK upgrade mode off.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function set_sdk_upgrade_complete() {
			$this->_storage->sdk_upgrade_mode = false;
		}

		/**
		 * Check if plugin upgrade mode.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function is_plugin_upgrade_mode() {
			return isset( $this->_storage->plugin_upgrade_mode ) ?
				$this->_storage->plugin_upgrade_mode :
				false;
		}

		/**
		 * Turn plugin upgrade mode off.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function set_plugin_upgrade_complete() {
			$this->_storage->plugin_upgrade_mode = false;
		}

		#endregion ------------------------------------------------------------------

		#region Marketing ------------------------------------------------------------------

		/**
		 * Check if current user purchased any other plugins before.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function has_purchased_before() {
			// TODO: Implement has_purchased_before() method.
		}

		/**
		 * Check if current user classified as an agency.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function is_agency() {
			// TODO: Implement is_agency() method.
		}

		/**
		 * Check if current user classified as a developer.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function is_developer() {
			// TODO: Implement is_developer() method.
		}

		/**
		 * Check if current user classified as a business.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function is_business() {
			// TODO: Implement is_business() method.
		}

		#endregion ------------------------------------------------------------------
	}