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

	final class Freemius {
		/**
		 * @var string
		 */
		public $version = '1.0.5';

		private $_slug;
		private $_plugin_basename;
		private $_plugin_dir_path;
		private $_plugin_dir_name;
		private $_plugin_main_file_path;
		private $_plugin_data;

		/**
		 * @since 1.0.5
		 * @var bool If false, runs API calls through sandbox.
		 */
		private $_is_live;

		/**
		 * @since 1.0.5
		 * @var bool Hints the SDK if running a premium plugin or free.
		 */
		private $_is_premium;

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
		 * @var FS_Logger
		 * @since 1.0.0
		 */
		private static $_static_logger;

		/**
		 * @var FS_Option_Manager
		 * @since 1.0.2
		 */
		private static $_accounts;

		private function __construct( $slug )
		{
			$this->_slug = $slug;

			$this->_logger = FS_Logger::get_logger(WP_FS__SLUG . '_' . $slug, WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK);

			$bt = debug_backtrace();
			$i = 1;
			while ($i < count($bt) - 1 && false !== strpos($bt[ $i ]['file'], '/freemius/')) {
				$i++;
			}

			$this->_plugin_main_file_path = $bt[ $i ]['file'];
			$this->_plugin_dir_path = plugin_dir_path($this->_plugin_main_file_path);
			$this->_plugin_basename = plugin_basename($this->_plugin_main_file_path);

			$base_name_split = explode('/', $this->_plugin_basename);
			$this->_plugin_dir_name = $base_name_split[0];

			if ($this->_logger->is_on()) {
				$this->_logger->info('plugin_main_file_path = ' . $this->_plugin_main_file_path);
				$this->_logger->info('plugin_dir_path = ' . $this->_plugin_dir_path);
				$this->_logger->info('plugin_basename = ' . $this->_plugin_basename);
				$this->_logger->info('plugin_dir_name = ' . $this->_plugin_dir_name);
			}

			$this->_load_account();

			// Hook to plugin activation
			register_activation_hook($this->_plugin_main_file_path, array(&$this, '_activate_plugin_event_hook'));

			// Hook to plugin uninstall.
			register_uninstall_hook($this->_plugin_main_file_path, array('Freemius', '_uninstall_plugin_hook'));
		}

		/**
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
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 *
		 * @param $plugin_file
		 *
		 * @return bool|Freemius
		 */
		static function load_instance_by_file($plugin_file) {
			$sites = self::get_all_sites();

			return isset( $sites[ $plugin_file ] ) ? self::instance( $sites[ $plugin_file ]->slug ) : false;
		}

		private static $_statics_loaded = false;
		private static function _load_required_static() {
			if (self::$_statics_loaded)
				return;

			self::$_static_logger = FS_Logger::get_logger( WP_FS__SLUG, WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );

			self::$_static_logger->entrance();

			self::$_accounts = FS_Option_Manager::get_manager( WP_FS__ACCOUNTS_OPTION_NAME, true );

			// Configure which Freemius powered plugins should be auto updated.
//			add_filter( 'auto_update_plugin', '_include_plugins_in_auto_update', 10, 2 );

			self::$_statics_loaded = true;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 *
		 * @param FS_User $user
		 * @param FS_Site $site
		 * @param bool|array $plans
		 */
		private function _set_account(FS_User $user, FS_Site $site, $plans = false) {
			$site->slug    = $this->_slug;
			$site->user_id = $user->id;
			$site->version = $this->get_plugin_version();

			$this->_site = $site;
			$this->_user = $user;
			$this->_plans = $plans;

			$this->_store_account();
		}

		/***
		 * Load account information (user + site).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 */
		private function _load_account() {
			$this->_logger->entrance();

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

			if ( isset( $sites[ $this->_plugin_basename ] ) && is_object( $sites[ $this->_plugin_basename ] ) ) {
				// Load site.
				$this->_site       = clone $sites[ $this->_plugin_basename ];
				$this->_site->plan = $this->_decrypt_entity( $this->_site->plan );

				// Load relevant user.
				$this->_user = clone $users[ $this->_site->user_id ];

				// Load plans.
				$this->_plans = $plans[ $this->_slug ];

				if ( ! is_array( $this->_plans ) || empty( $this->_plans ) ) {
					$this->_sync_plans(true);
				}
				else {
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

				if ( version_compare( $this->_site->version, $this->get_plugin_version(), '<' ) ) {
					$this->_update_plugin_version_event();
				}

			} else {
				self::$_static_logger->info( 'Trying to load account from external source with ' . 'fs_load_account_' . $this->_slug );

				$account = apply_filters( 'fs_load_account_' . $this->_slug, false );

				if ( false === $account ) {
					self::$_static_logger->info( 'Plugin is not registered on that site.' );
				} else {
					if ( is_object( $account['site'] ) ) {
						self::$_static_logger->info( 'Account loaded: user_id = ' . $this->_user->id . '; site_id = ' . $this->_site->id . ';' );

						$this->_set_account( $account['user'], $account['site'] );
					}
				}
			}
		}

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
		function init( $id, $public_key, $is_live = true, $is_premium = true) {
			$this->_logger->entrance();

			$this->_plugin             = new FS_Plugin();
			$this->_plugin->id         = $id;
			$this->_plugin->public_key = $public_key;
			$this->_plugin->slug       = $this->_slug;

			$this->_is_live    = $is_live;
			$this->_is_premium = $is_premium;

			if ( $this->is_registered() ) {
				$this->_background_sync();
			}

			if ( is_admin() ) {
				if ( ! $this->is_registered() ) {
					$this->_init_admin_activation();
				} else {
					$this->set_has_menu();
					$this->_init_admin();

					// @todo Fix automatic plugin updater. Currently, extract the plugin into {{tag_id}}.tmp folder which breaks stuff.
					if ($this->is_paying__fs__())
						new FS_Plugin_Updater($this);
				}
			}
		}

		/**
		 * Set Freemius into sandbox mode for debugging.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @param $secret_key
		 */
		function init_sandbox($secret_key)
		{
			$this->_plugin->secret_key = $secret_key;
		}

		/**
		 * Check if running in sandbox mode.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @return bool
		 */
		function is_sandbox()
		{
			return (!$this->_is_live) || isset($this->_plugin->secret_key);
		}

		/**
		 * Check if running test vs. live plugin.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.5
		 *
		 * @return bool
		 */
		function is_live()
		{
			return $this->_is_live;
		}

		/**
		 * Check if running premium plugin code.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.5
		 *
		 * @return bool
		 */
		function is_premium()
		{
			return $this->_is_premium;
		}

		/**
		 * Background sync every 24 hours.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 */
		private function _background_sync()
		{
			if ( ! is_numeric( $this->_site->updated ) || $this->_site->updated >= time() ) {
				// If updated not set or happens to be in the future, set as if was 24 hours earlier.
				$this->_site->updated = time() - WP_FS__TIME_24_HOURS_IN_SEC;
			}

			if ($this->_site->updated <= time() - WP_FS__TIME_24_HOURS_IN_SEC)
			{
				// Initiate background plan sync.
				$this->_sync_license(true);

				$this->_check_updates(true);
			}
		}

		private function _init_admin_activation()
		{
			if ( get_option( "fs_{$this->_slug}_activated", false ) ) {
				delete_option( "fs_{$this->_slug}_activated" );
				add_action( 'admin_init', array( &$this, '_redirect_on_activation_hook' ) );
				return;
			}

			if (empty($_GET['page']) || $this->_slug != $_GET['page']) {
				$activation_url = $this->_get_admin_page_url();

				self::add_admin_message(
					sprintf(__('You are just one step away - %1sActivate ' . $this->get_plugin_name() . ' Now%2s', WP_FS__SLUG), '<a href="' . $activation_url . '"><b>', '</b></a>'),
					'',
					'update-nag'
				);
			}

			add_action( 'admin_menu', array( &$this, '_add_dashboard_menu_for_activation' ), WP_FS__LOWEST_PRIORITY );
		}

		private function _init_admin() {
			register_deactivation_hook( $this->_plugin_main_file_path, array( &$this, '_deactivate_plugin_hook' ) );

			add_action( 'admin_init', array( &$this, '_add_upgrade_action_link' ) );
			add_action( 'admin_menu', array( &$this, '_add_dashboard_menu' ), WP_FS__LOWEST_PRIORITY );
			add_action( 'init', array( &$this, '_add_default_submenu_items' ), WP_FS__LOWEST_PRIORITY );
			add_action( 'init', array( &$this, '_redirect_on_clicked_menu_link' ), WP_FS__LOWEST_PRIORITY );
		}

		/* Events
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * Delete site install from Database.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 */
		function _delete_site()
		{
			$sites = self::get_all_sites();
			if ( isset( $sites[ $this->_plugin_basename ] ) ) {
				unset( $sites[ $this->_plugin_basename ] );
			}

			self::$_accounts->set_option( 'sites', $sites, true );
		}

		/**
		 * Plugin activated hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 * @uses FS_Api
		 */
		function _activate_plugin_event_hook() {
			$this->_logger->entrance('slug = ' . $this->_slug);

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			if ($this->is_registered()) {
				// Send re-activation event.
				$this->get_api_site_scope()->call( '/', 'put', array( 'is_active' => true ) );
			}else{
				// Auto forward to account activation.
				add_option( "fs_{$this->_slug}_activated", true );

				// @todo Implement "bounce rate" by calculating number of plugin activations without registration.
			}
		}

		/**
		 * Delete account.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.3
		 */
		function delete_account_event() {
			$this->_logger->entrance( 'slug = ' . $this->_slug );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$this->do_action( 'before_account_delete' );

			$this->_delete_site();

			// Send delete event.
			$this->get_api_site_scope()->call( '/', 'delete' );

			$this->do_action( 'after_account_delete' );
		}

		/**
		 * Plugin deactivation hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 */
		function _deactivate_plugin_hook() {
			$this->_logger->entrance( 'slug = ' . $this->_slug );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			// Send deactivation event.
			$this->get_api_site_scope()->call( '/', 'put', array( 'is_active' => false ) );
		}

		/**
		 * Plugin version update hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 */
		private function _update_plugin_version_event() {
			$this->_logger->entrance( 'slug = ' . $this->_slug );

			$this->_site->version = $this->get_plugin_version();

			// Send upgrade event.
			$site = $this->get_api_site_scope()->call( '/', 'put', array( 'version' => $this->get_plugin_version() ) );

			if (!isset($site->error))
				$this->_store_site(true);
		}

		/**
		 * Plugin uninstall hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 */
		function _uninstall_plugin_event() {
			$this->_logger->entrance( 'slug = ' . $this->_slug );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			// Send uninstall event.
			$this->get_api_site_scope()->call( '/', 'put', array( 'is_active' => false, 'is_uninstalled' => true ) );
		}

		public static function _uninstall_plugin_hook() {
			self::_load_required_static();

			self::$_static_logger->entrance();

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$plugin_file = substr(current_filter(), strlen('uninstall_'));

			self::$_static_logger->info('plugin = ' . $plugin_file);

			$fs = self::load_instance_by_file($plugin_file);

			if (is_object($fs))
				$fs->_uninstall_plugin_event();
		}

		/* Plugin Information
		------------------------------------------------------------------------------------------------------------------*/
		function get_plugin_data() {
			if ( ! isset( $this->_plugin_data ) ) {
				if ( ! function_exists( 'get_plugins' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}

				$this->_plugin_data = get_plugin_data( $this->_plugin_main_file_path );
			}

			return $this->_plugin_data;
		}

		function get_slug()
		{
			return $this->_slug;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 *
		 * @return numeric Plugin ID.
		 */
		function get_id()
		{
			return $this->_plugin->id;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 *
		 * @return string Plugin public key.
		 */
		function get_public_key()
		{
			return $this->_plugin->public_key;
		}

		/**
		 * Will be available only on sandbox mode.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @return mixed Plugin secret key.
		 */
		function get_secret_key()
		{
			return $this->_plugin->_secret_key;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.0
		 *
		 * @return string
		 */
		function get_plugin_name()
		{
			$this->_logger->entrance();

			$plugin_data = $this->get_plugin_data();

			$this->_logger->departure( 'Name = ' . $plugin_data['Name'] );

			return $plugin_data['Name'];
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.0
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
		 * @since 1.0.4
		 *
		 * @return string
		 */
		function get_plugin_basename() {
			return $this->_plugin_basename;
		}

		/* Account
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * @return FS_User[]
		 */
		static function get_all_users()
		{
			$users = self::$_accounts->get_option( 'users', array() );

			if ( ! is_array( $users ) ) {
				$users = array();
			}

			return $users;
		}

		/**
		 * @return FS_Site[]
		 */
		private static function get_all_sites()
		{
			$sites = self::$_accounts->get_option( 'sites', array() );

			if ( ! is_array( $sites ) ) {
				$sites = array();
			}

			return $sites;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.6
		 *
		 * @return FS_Plugin_License[]
		 */
		private static function get_all_licenses()
		{
			$licenses = self::$_accounts->get_option( 'licenses', array() );

			if ( ! is_array( $licenses ) ) {
				$licenses = array();
			}

			return $licenses;
		}

		/**
		 * @return FS_Plugin_Plan[]
		 */
		private static function get_all_plans()
		{
			$plans = self::$_accounts->get_option( 'plans', array() );

			if ( ! is_array( $plans ) ) {
				$plans = array();
			}

			return $plans;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @return FS_Plugin_Tag[]
		 */
		private static function get_all_updates()
		{
			$updates = self::$_accounts->get_option( 'updates', array() );

			if ( ! is_array( $updates ) ) {
				$updates = array();
			}

			return $updates;
		}

		/**
		 * Check if user is registered.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1

		 * @return bool
		 */
		function is_registered() {
			return is_object( $this->_user );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @return FS_Plugin
		 */
		function get_plugin() {
			return $this->_plugin;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.3
		 *
		 * @return FS_User
		 */
		function get_user() {
			return $this->_user;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.3
		 *
		 * @return FS_Site
		 */
		function get_site() {
			return $this->_site;
		}

		function get_plan_id() {
			return $this->_site->plan->id;
		}

		function get_plan_title() {
			return $this->_site->plan->title;
		}

		function update_account($user_id, $user_email, $site_id)
		{
			$this->_user->id = $user_id;
			$this->_user->email = $user_email;
			$this->_site->user_id = $user_id;
			$this->_site->id = $site_id;
			$this->_store_account();
		}

		/* Licensing
		------------------------------------------------------------------------------------------------------------------*/


		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.3
		 *
		 * @return bool
		 */
		function is_trial() {
			$this->_logger->entrance();

			if (!$this->is_registered())
				return false;

			return ((isset($this->_site->is_trial) && $this->_site->is_trial) || 'trial' === $this->_site->plan->name);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 *
		 * @return bool
		 */
		function is_paying__fs__() {
			$this->_logger->entrance();

			if (!$this->is_registered())
				return false;

			return (!$this->is_trial() && 'free' !== $this->_site->plan->name);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @return bool
		 */
		function is_free_plan() {
			if (!$this->is_registered())
				return true;

			return ('free' === $this->_site->plan->name || is_null($this->_site->license_id));
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @return bool
		 */
		function is_not_paying() {
			$this->_logger->entrance();
			return ($this->is_trial() || $this->is_free_plan());
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.5
		 *
		 * @return bool
		 */
		function _has_premium_license() {
			$this->_logger->entrance();

			$premium_license = $this->_get_premium_license();

			return (false !== $premium_license);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.5
		 *
		 * @return FS_Plugin_License
		 */
		function _get_premium_license() {
			$this->_logger->entrance();

			if (is_array($this->_licenses)) {
				foreach ( $this->_licenses as $license ) {
					if ( $license->quota > ( ( $license->is_free_localhost ? 0 : $license->activated_local ) + $license->activated ) ) {
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
		 * @return FS_Plugin_Plan[]|stdClass
		 */
		function _sync_plans()
		{
			$plans = $this->_get_plugin_plans();
			if (!isset($plans->error))
			{
				$this->_plans = $plans;
				$this->_store_plans();
			}

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
		function _get_plan_by_id($id) {
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
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param number $id
		 *
		 * @return FS_Plugin_License
		 */
		function _get_license_by_id($id) {
			$this->_logger->entrance();

			if ( ! is_array( $this->_licenses ) || 0 === count( $this->_licenses ) ) {
				$this->_sync_plans();
			}

			foreach ( $this->_licenses as $license ) {
				if ( $id == $license->id ) {
					return $license;
				}
			}

			return false;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 * @param string $plan Plan name
		 * @param bool   $exact If true, looks for exact plan. If false, also check "higher" plans.
		 *
		 * @return bool
		 */
		function is_plan( $plan, $exact = false ) {
			$this->_logger->entrance();

			if (!$this->is_registered())
				return false;

			$plan = strtolower($plan);

			if ($this->_site->plan->name === $plan)
				// Exact plan.
				return true;
			else if ($exact)
				// Required exact, but plans are different.
				return false;

			$current_plan_order = -1;
			$required_plan_order = -1;
			for ($i = 0, $len = count($this->_plans); $i < $len; $i++)
			{
				if ($plan === $this->_plans[$i]->name)
					$required_plan_order = $i;
				else if ($this->_site->plan->name === $this->_plans[$i]->name)
					$current_plan_order = $i;
			}

			return ($current_plan_order > $required_plan_order);
		}

		function is_feature_supported($feature_id)
		{
			throw new Exception('not implemented');
		}

		function is_ssl() {

			return
				// Checks if CloudFlare's HTTPS (Flexible SSL support)
				( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) ||
				// Check if HTTPS request.
				( isset( $_SERVER['HTTPS'] ) && 'on' == $_SERVER['HTTPS'] ) ||
				( isset( $_SERVER['SERVER_PORT'] ) && 443 == $_SERVER['SERVER_PORT'] );

		}

		function is_ssl_and_plan( $plan, $exact = false ) {
			return ( $this->is_ssl() && $this->is_plan( $plan, $exact ) );
		}

		/**
		 * Alias to pricing_url().
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.2
		 *
		 * @uses pricing_url
		 *
		 * @param string $period Billing cycle
		 *
		 * @return string
		 */
		function get_upgrade_url( $period = WP_FS__PERIOD_ANNUALLY ) {
			return $this->pricing_url($period);
		}

		/**
		 * Construct plugin's settings page URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @param string $page
		 * @param array $params
		 *
		 * @return string
		 */
		function _get_admin_page_url($page = '', $params = array()) {
			return add_query_arg( array_merge( $params, array(
				'page' => trim( "{$this->_slug}-{$page}", '-' )
			) ), admin_url( 'admin.php', 'admin' ) );
		}

		/**
		 * Plugin's pricing URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @param string $period Billing cycle
		 *
		 * @return string
		 */
		function pricing_url( $period = WP_FS__PERIOD_ANNUALLY ) {
			$this->_logger->entrance();

			return $this->_get_admin_page_url( 'pricing', array('billing_cycle' => $period) );
		}

		/**
		 * Plugin's account URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param bool|string $action
		 *
		 * @return string
		 */
		function get_account_url($action = false) {
			return is_string($action) ?
				wp_nonce_url( $this->_get_admin_page_url( 'account', array('fs_action' => $action) ), $action ) :
				$this->_get_admin_page_url('account');
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
		function contact_url($topic = false, $message = false) {
			$params = array();
			if (is_string($topic))
				$params['topic'] = $topic;
			if (is_string($message))
				$params['message'] = $message;
			return $this->_get_admin_page_url('contact', $params);
		}

		function get_plugin_folder_name() {
			$this->_logger->entrance();

			$plugin_folder = $this->_plugin_basename;

			while ( '.' !== dirname( $plugin_folder ) ) {
				$plugin_folder = dirname( $plugin_folder );
			}

			$this->_logger->departure('Folder Name = ' . $plugin_folder);

			return $plugin_folder;
		}

		/* Logger
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * @param string $id
		 * @param bool $prefix_slug
		 *
		 * @return FS_Logger
		 */
		function get_logger( $id = '', $prefix_slug = true ) {
			return FS_Logger::get_logger( ( $prefix_slug ? $this->_slug : '' ) . ( ( ! $prefix_slug || empty( $id ) ) ? '' : '_' ) . $id );
		}

		/**
		 * @param $id
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
		private function _encrypt($str)
		{
			return base64_encode($str);
		}
		private function _decrypt($str)
		{
			return base64_decode($str);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param \FS_Entity $entity
		 *
		 * @return \FS_Entity Return an encrypted clone entity.
		 */
		private function _encrypt_entity(FS_Entity $entity) {
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
		 * @param \FS_Entity $entity
		 *
		 * @return \FS_Entity Return an decrypted clone entity.
		 */
		private function _decrypt_entity(FS_Entity $entity) {
			$clone = clone $entity;
			$props = get_object_vars( $entity );

			foreach ( $props as $key => $val ) {
				$clone->{$key} = $this->_decrypt( $val );
			}

			return $clone;
		}

		/* Management Dashboard Menu
		------------------------------------------------------------------------------------------------------------------*/
		private $_has_menu = false;
		private $_menu_items = array();

		function _redirect_on_clicked_menu_link() {
			$this->_logger->entrance();

			$page = strtolower( isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '' );

			$this->_logger->log( 'page = ' . $page );

			foreach ( $this->_menu_items as $priority => $items) {
				foreach ( $items as $item ) {
					if (isset($item['url'])) {
						if ( $page === $item['menu_slug'] ) {
							$this->_logger->log( 'Redirecting to ' . $item['url'] );

							fs_redirect( $item['url'] );
						}
					}
				}
			}
		}

		private function _find_plugin_main_menu()
		{
			global $menu;

			$position = -1;
			$found_menu = false;

			$menu_slug = plugin_basename( $this->_slug );
			$hook_name = get_plugin_page_hookname( $menu_slug, '' );
			foreach ($menu as $pos => $m)
			{
				if ($menu_slug === $m[2])
				{
					$position = $pos;
					$found_menu = $m;
					remove_all_actions($hook_name);
					break;
				}
			}

			return array('menu' => $found_menu, 'position' => $position, 'hook_name' => $hook_name);
		}

		function _add_dashboard_menu_for_activation()
		{
			$menu = $this->_find_plugin_main_menu();

			remove_all_actions($menu['hook_name']);

			// Override menu action.
			$hook = add_menu_page(
				$menu['menu'][3],
				$menu['menu'][0],
				'manage_options',
				$this->_slug,
				array(&$this, '_activation_page_render'),
				$menu['menu'][6],
				$menu['position']
			);

			add_action("load-$hook", array(&$this, '_activate_account'));
		}

		/**
		 * Tries to activate account based on POST params.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.2
		 */
		function _activate_account() {
			if ( $this->is_registered() ) {
				// Already activated.
				return;
			}

			$this->_clean_admin_content_section();

			if ( fs_request_is_action( 'activate' ) && fs_request_is_post() ) {
				check_admin_referer( 'activate_' . $this->_plugin->public_key );

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

		function _add_dashboard_menu() {
			$this->_logger->entrance();

			// Add user account page.
			$this->add_submenu_item(
				__( 'Account', $this->_slug ),
				array( &$this, '_account_page_render' ),
				$this->get_plugin_name() . ' &ndash; ' . __( 'Account', $this->_slug ),
				'manage_options',
				'account',
				array( &$this, '_account_page_load' )
			);

			// Add contact page.
			$this->add_submenu_item(
				__( 'Contact Us', $this->_slug ),
				array( &$this, '_contact_page_render' ),
				$this->_plugin_data['Name'] . ' &ndash; ' . __( 'Contact Us', $this->_slug ),
				'manage_options',
				'contact',
				array( &$this, '_clean_admin_content_section' )
			);

			// Add upgrade/pricing page.
			$this->add_submenu_item(
				( $this->is_paying__fs__() ? __( 'Pricing', $this->_slug ) : __( 'Upgrade', $this->_slug ) . '&nbsp;&nbsp;&#x27a4;' ),
				array( &$this, '_pricing_page_render' ),
				$this->_plugin_data['Name'] . ' &ndash; ' . __( 'Pricing', $this->_slug ),
				'manage_options',
				'pricing',
				array( &$this, '_clean_admin_content_section' ),
				WP_FS__LOWEST_PRIORITY
			);


			ksort( $this->_menu_items );

			foreach ( $this->_menu_items as $priority => $items ) {
				foreach ( $items as $item ) {
					if (!isset($item['url'])) {
						$hook = add_submenu_page(
							$this->_slug,
							$item['page_title'],
							$item['menu_title'],
							$item['capability'],
							$item['menu_slug'],
							$item['render_function']
						);

						if ( false !== $item['before_render_function'] ) {
							add_action( "load-$hook", $item['before_render_function'] );
						}
					}else {
						add_submenu_page(
							$this->_slug,
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
			if (!$this->_has_menu)
				return;

			$this->add_submenu_link_item( __( 'Support Forum', $this->_slug ), 'https://wordpress.org/support/plugin/' . $this->_slug, 'wp-support-forum', 'read', 50 );
		}

		function set_has_menu() {
			$this->_logger->entrance();

			$this->_has_menu = true;
		}

		private function _get_menu_slug( $slug = '' ) {
			return $this->_slug . ( empty( $slug ) ? '' : ( '-' . $slug ) );
		}

		/**
		 * @param string       $menu_title
		 * @param callable       $render_function
		 * @param bool|string   $page_title
		 * @param string $capability
		 * @param bool|string   $menu_slug
		 * @param bool|callable   $before_render_function
		 * @param int    $priority
		 */
		function add_submenu_item( $menu_title, $render_function, $page_title = false, $capability = 'manage_options', $menu_slug = false, $before_render_function = false, $priority = 10  ) {
			$this->_logger->entrance('Title = ' . $menu_title );

			if (!isset($this->_menu_items[$priority]))
				$this->_menu_items[$priority] = array();

			$this->_menu_items[$priority][] = array(
				'page_title'             => is_string( $page_title ) ? $page_title : $menu_title,
				'menu_title'             => $menu_title,
				'capability'             => $capability,
				'menu_slug'              => $this->_get_menu_slug( is_string( $menu_slug ) ? $menu_slug : strtolower( $menu_title ) ),
				'render_function'        => $render_function,
				'before_render_function' => $before_render_function,
			);

			$this->_has_menu = true;
		}

		function add_submenu_link_item( $menu_title, $url, $menu_slug = false, $capability = 'read', $priority = 10 ) {
			$this->_logger->entrance('Title = ' . $menu_title . '; Url = ' . $url);

			if (!isset($this->_menu_items[$priority]))
				$this->_menu_items[$priority] = array();

			$this->_menu_items[$priority][] = array(
				'menu_title'             => $menu_title,
				'capability'             => $capability,
				'menu_slug'              => $this->_get_menu_slug( is_string( $menu_slug ) ? $menu_slug : strtolower( $menu_title ) ),
				'url'                    => $url,
				'page_title'             => $menu_title,
				'render_function'        => 'fs_dummy',
				'before_render_function' => '',
			);

			$this->_has_menu = true;
		}

		/* Actions / Hooks / Filters
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * Do action, specific for the current context plugin.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 *
		 * @param $tag
		 *
		 * @uses do_action()
		 */
		function do_action( $tag ) {
			$this->_logger->entrance( $tag );

			call_user_func_array( 'do_action', array_merge(
					array( 'fs_' . $tag . '_' . $this->_slug ),
					array_slice( func_get_args(), 1 ) )
			);
//			do_action( $tag . '_' . $this->_slug );
		}

		/**
		 * Add action, specific for the current context plugin.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 *
		 * @param $tag
		 *
		 * @uses add_action()
		 */
		function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
			$this->_logger->entrance( $tag );

			add_action( $tag . '_' . $this->_slug, $function_to_add, $priority, $accepted_args );
		}

		/* Activation
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * Render activation/sign-up page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 */
		function _activation_page_render(){
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
		 * @since 1.0.1
		 *
		 * @param bool $store Flush to Database if true.
		 */
		private function _store_site($store = true) {
			$this->_logger->entrance();

			$this->_site->updated = time();
			$encrypted_site       = clone $this->_site;
			$encrypted_site->plan = $this->_encrypt_entity( $this->_site->plan );

			$sites                            = self::get_all_sites();
			$sites[ $this->_plugin_basename ] = $encrypted_site;
			self::$_accounts->set_option( 'sites', $sites, $store );
		}

		/**
		 * Update plugin's plans information.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.2
		 *
		 * @param bool $store Flush to Database if true.
		 */
		private function _store_plans($store = true) {
			$this->_logger->entrance();

			$plans = self::get_all_plans();

			// Copy plans.
			$encrypted_plans = array();
			for ( $i = 0, $len = count( $this->_plans ); $i < $len; $i ++ ) {
				$this->_plans[ $i ]->updated = time();
				$encrypted_plans[] = $this->_encrypt_entity( $this->_plans[ $i ] );
			}

			$plans[ $this->_slug ] = $encrypted_plans;
			self::$_accounts->set_option( 'plans', $plans, $store );
		}

		/**
		 * Update user's plugin licenses.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.5
		 *
		 * @param bool $store
		 */
		private function _store_licenses($store = true) {
			$this->_logger->entrance();

			$licenses = self::get_all_licenses();

			if ( ! isset( $licenses[ $this->_slug ] ) ) {
				$licenses[ $this->_slug ] = array();
			}

			$licenses[ $this->_slug ][ $this->_user->id ] = $this->_licenses;

			self::$_accounts->set_option( 'licenses', $licenses, $store );
		}

		/**
		 * Update user information.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 *
		 * @param bool $store Flush to Database if true.
		 */
		private function _store_user($store = true) {
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
		 */
		private function _store_update($update, $store = true) {
			if ( $update instanceof FS_Plugin_Tag ) {
				$update->updated = time();
			}

			$updates               = self::get_all_updates();
			$updates[ $this->_plugin->id ] = $update;
			self::$_accounts->set_option( 'updates', $updates, $store );
		}

		/**
		 * Store account params in the Database.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.1
		 */
		private function _store_account()
		{
			$this->_store_site(false);
			$this->_store_user(false);
			$this->_store_plans(false);
			$this->_store_licenses(false);

			self::$_accounts->store();
		}

		/**
		 * Sync user's information.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.3
		 * @uses FS_Api
		 */
		private function _handle_account_user_sync() {
			$api = $this->get_api_user_scope();

			// Get user's information.
			$user = $api->call( '/' );

			if ( isset( $user->id ) ) {
				$this->_user->first = $user->first;
				$this->_user->last  = $user->last;
				$this->_user->email = $user->email;

				if ( ( ! isset( $this->_user->is_verified ) || false === $this->_user->is_verified ) && $user->is_verified ) {
					$this->_user->is_verified = $user->is_verified;

					$this->do_action( 'account_email_verified', $user->email );

					self::add_admin_message(
						__('Your email has been successfully verified - you are AWESOME!', WP_FS__SLUG),
						__('Right on!', WP_FS__SLUG)
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
		 * @return stdClass|\FS_Site
		 */
		private function _get_site() {
			$this->_logger->entrance();
			$api = $this->get_api_site_scope();

			$site = $api->call( '/' );

			if (!isset( $site->error )) {
				$site = new FS_Site( $site );
				$site->slug = $this->_slug;
				$site->version = $this->get_plugin_version();
			}

			return $site;
		}

		/**
		 * @param bool $store
		 *
		 * @return \FS_Plugin_Plan|\stdClass|false
		 */
		private function _enrich_site_plan($store = true) {
			// Try to load plan from local cache.
			$plan = $this->_get_plan_by_id( $this->_site->plan->id );

			if ( false === $plan ) {
				$plan = $this->_get_site_plan();
			}

			if ( $plan instanceof FS_Plugin_Plan ) {
				$this->_update_plan( $plan, $store );
			}

			return $plan;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 * @uses   FS_Api
		 *
		 * @return FS_Plugin_Plan|stdClass
		 */
		private function _get_site_plan()
		{
			$this->_logger->entrance();
			$api = $this->get_api_site_scope();

			$plan = $api->call( "/plans/{$this->_site->plan->id}.json" );

			return !isset($plan->error) ? new FS_Plugin_Plan($plan) : $plan;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 * @uses   FS_Api
		 *
		 * @return FS_Plugin_Plan[]|stdClass
		 */
		private function _get_plugin_plans() {
			$this->_logger->entrance();
			$api = $this->get_api_site_scope();

			$result = $api->call( '/plans.json' );

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
		 * @return FS_Plugin_License[]|stdClass
		 */
		private function _get_licenses() {
			$this->_logger->entrance();
			$api = $this->get_api_user_scope();

			$result = $api->call( "/plugins/{$this->_plugin->id}/licenses.json" );

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
		 * @param FS_Plugin_Plan  $plan
		 * @param bool            $store
		 */
		private function _update_plan($plan, $store = false) {
			$this->_logger->entrance();

			$this->_site->plan = $plan;
			$this->_store_site( $store );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param FS_Plugin_License[] $licenses
		 */
		private function _update_licenses($licenses) {
			$this->_logger->entrance();

			if ( is_array( $licenses ) ) {
				for ( $i = 0, $len = count( $licenses ); $i < $len; $i ++ ) {
					$licenses[ $i ]->updated = time();
				}
			}

			$this->_licenses = $licenses;
			$this->_store_licenses();
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @return object|false Plugin latest tag info.
		 */
		private function _fetch_latest_version() {
			$tag            = $this->get_api_site_scope()->call( '/updates/latest.json' );
			$latest_version = ( is_object( $tag ) && isset( $tag->version ) ) ? $tag->version : 'couldn\'t get';
			$this->_logger->departure( 'Latest version ' . $latest_version );

			return ( is_object( $tag ) && isset( $tag->version ) ) ? $tag : false;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @return object|false New plugin tag info if exist.
		 */
		private function _fetch_newer_version() {
			$latest_tag = $this->_fetch_latest_version();

			if ( ! is_object( $latest_tag ) ) {
				return false;
			}

			// Check if version is actually newer.
			$has_new_version = version_compare( $this->get_plugin_version(), $latest_tag->version, '<' );

			$this->_logger->departure( $has_new_version ? 'Found newer plugin version ' . $latest_tag->version : 'No new version' );

			return $has_new_version ? $latest_tag : false;
		}

		/**
		 *
		 * @return bool|FS_Plugin_Tag
		 */
		function get_update() {
			$this->_check_updates(true);
			$updates = $this->get_all_updates();

			return isset( $updates[ $this->_plugin->id ] ) && is_object( $updates[ $this->_plugin->id ] ) ? $updates[ $this->_plugin->id ] : false;
		}

		/**
		 * Sync site's plan.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 * @uses   FS_Api
		 *
		 * @param bool $background Hints the method if it's a background sync. If false, it means that was initiated by the admin.
		 */
		private function _sync_license($background = false) {
			$this->_logger->entrance();

			// Load site details.
			$site = $this->_get_site();

			$plan_change = 'none';

			if ( isset( $site->error ) ) {
				$api = $this->get_api_site_scope();

				// Try to ping API to see if not blocked.
				if ( ! $api->test() ) {
					// Failed to ping API - blocked!
					self::add_admin_message(
						sprintf( __( 'Your server is blocking the access to Freemius\' API, which is crucial for %1s license synchronization. Please contact your host to whitelist %2s', WP_FS__SLUG ), $this->get_plugin_name(), '<a href="' . $api->get_url() . '" target="_blank">' . $api->get_url() . '</a>' ) . '<br> Error received from the server: ' . var_export( $site->error, true ),
						__( 'Oops...', WP_FS__SLUG ),
						'error',
						$background
					);
				} else {
					// Authentication params are broken.
					self::add_admin_message(
						__( 'It seems like one of the authentication parameters is wrong. Update your Public Key, Secret Key & User ID, and try again.', WP_FS__SLUG ),
						__( 'Oops...', WP_FS__SLUG ),
						'error',
						$background
					);
				}

				// Plan update failure, set update time to 24hours + 10min so it won't annoy the admin too much.
				$this->_site->updated = time() - WP_FS__TIME_24_HOURS_IN_SEC + WP_FS__TIME_10_MIN_IN_SEC;
			} else {
				// Sync licenses.
				$licenses = $this->_get_licenses();
				if ( ! isset( $licenses->error ) ) {
					$this->_update_licenses( $licenses );
				}

				// Check if plan / license changed.
				if ( $this->_site->plan->id !== $site->plan->id ||
				     $this->_site->license_id !== $site->license_id
				) {
					$is_free = $this->is_free_plan();

					// Make sure license exist and not expired.
					$new_license = is_null($site->license_id) ? false : $this->_get_license_by_id( $site->license_id );

					if ( $is_free && ((!is_object($new_license) || $new_license->is_expired()))) {
						// The license is expired, so ignore upgrade method.
					} else {
						// License changed.
						$this->_site = $site;
						$this->_enrich_site_plan( true );

						$plan_change = $is_free ? 'upgraded' : 'downgraded';
					}
				} else {
					if ( is_numeric( $this->_site->license_id ) ) {
						$license = $this->_get_license_by_id( $this->_site->license_id );
						if ( $license->is_expired() ) {
							$this->_site->license_id = null;
							$this->_site->plan->id   = $this->_plans[0]->id;
							$this->_enrich_site_plan( true );
							$plan_change = 'downgraded';
						}
					}
				}
			}

			if ( ! $background && is_admin() ) {
				switch ( $plan_change ) {
					case 'none':
						self::add_admin_message(
							sprintf(
								__( 'It looks like your plan did NOT change. If you did upgrade, it\'s probably an issue on our side (sorry). Please %1sContact Us HERE%2s.', WP_FS__SLUG ),
								'<a href="' . $this->contact_url( 'bug', sprintf( __( 'I have upgraded my account but when I try to Sync the License, the plan remains %s.', WP_FS__SLUG ), strtoupper( $this->_site->plan->name ) ) ) . '">',
								'</a>'
							),
							__( 'Hmm...', WP_FS__SLUG ),
							'error'
						);
						break;
					case 'upgraded':
						self::add_admin_message(
							sprintf(
								__( 'Your plan was successfully upgraded, %1sdownload our latest %2s version now%3s.', WP_FS__SLUG ), '<a href="' . $this->get_account_url( 'download_latest' ) . '">', $this->_site->plan->title, '</a>' ),
							__( 'Ye-ha!', WP_FS__SLUG )
						);
						break;
					case 'downgraded':
						self::add_admin_message(
							__( 'Your plan has been successfully synced.', WP_FS__SLUG ),
							__( 'Ye-ha!', WP_FS__SLUG )
						);
						break;
				}
			}

			$this->do_action( 'after_account_plan_sync', $this->_site->plan->name );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.5
		 *
		 */
		private function _activate_license() {
			$this->_logger->entrance();

			$premium_license = $this->_get_premium_license();

			$api     = $this->get_api_site_scope();
			$license = $api->call( "/licenses/{$premium_license->id}.json", 'put' );

			if ( isset( $license->error ) ) {
				self::add_admin_message(
					__( 'It looks like the license could not be activated.', WP_FS__SLUG ) . '<br> Error received from the server: ' . var_export( $license->error, true ),
					__( 'Hmm...', WP_FS__SLUG ),
					'error'
				);

				return;
			}

			// Updated site plan.
			$this->_site->plan->id = $license->plan_id;
			$this->_site->license_id = $license->id;
			$this->_enrich_site_plan( false );

			// Update license cache.
			for ( $i = 0, $len = count( $this->_licenses ); $i < $len; $i ++ ) {
				if ( $license->id == $this->_licenses[ $i ]->id ) {
					$this->_licenses[ $i ] = new FS_Plugin_License( $license );
					break;
				}
			}

			$this->_store_account();

			self::add_admin_message(
				sprintf( __( 'Your license was successfully activated, %1sdownload our latest %2s version now%3s.', WP_FS__SLUG ), '<a href="' . $this->get_account_url( 'download_latest' ) . '">', $this->_site->plan->title, '</a>' ),
				__( 'Ye-ha!', WP_FS__SLUG )
			);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.5
		 *
		 */
		private function _deactivate_license(){
			$this->_logger->entrance();

			$api     = $this->get_api_site_scope();
			$license = $api->call( "/licenses/{$this->_site->license_id}.json", 'delete' );

			if ( isset( $license->error ) ) {
				self::add_admin_message(
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
			$this->_enrich_site_plan(false);

			$this->_store_account();

			self::add_admin_message(
				sprintf( __( 'Your license was successfully deactivated, you are back to the %1s plan.', WP_FS__SLUG ), $this->_site->plan->title ),
				__( 'O.K', WP_FS__SLUG )
			);
		}

		/**
		 * Site plan downgrade.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @users FS_Api
		 */
		private function _downgrade_site() {
			$this->_logger->entrance();

			$api  = $this->get_api_site_scope();
			$site = $api->call( 'downgrade.json', 'put');

			$plan_downgraded = false;
			if ( ! isset( $site->error ) ) {
				$prev_plan_id = $this->_site->plan->id;

				// Update new site plan id.
				$this->_site->plan->id = $site->plan_id;

				$plan = $this->_enrich_site_plan();

				$plan_downgraded = ($plan instanceof FS_Plugin_Plan && $prev_plan_id != $plan->id);
			} else {
				// handle different error cases.

			}

			if ($plan_downgraded)
			{
				self::add_admin_message(
					sprintf(__( 'Your plan was successfully downgraded to %1s.', WP_FS__SLUG ), $plan->title)
				);
			}
			else
			{
				self::add_admin_message(
					__( 'Seems like we are having some temporary issue with your plan downgrade. Please try again in few minutes.', WP_FS__SLUG ),
					__( 'Oops...'),
					'error'
				);
			}
		}

		/**
		 * Download latest plugin version, based on plan.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @users FS_Api
		 */
		private function _download_latest(){
			$latest = $this->get_api_site_scope()->call( '/updates/latest.zip' );

			if (!is_object($latest)) {
				header( "Content-Type: application/zip" );
				header( "Content-Disposition: attachment; filename={$this->_slug}-premium.zip" );
				header( "Content-Length: " . strlen( $latest ) );
				echo $latest;

				exit();
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @users FS_Api
		 *
		 * @param bool $background Hints the method if it's a background updates check. If false, it means that was initiated by the admin.
		 */
		private function _check_updates($background = false) {
			// Check if there's a newer version for download.
			$new_version = $this->_fetch_newer_version();

			$update = null;
			if ( is_object( $new_version ) ) {
				$update          = new FS_Plugin_Tag();
				$update->id      = $new_version->id;
				$update->url     = $new_version->url;
				$update->version = $new_version->version;

				if ( ! $background ) {
					self::add_admin_message(
						sprintf(
							__( 'Version %1s was released. Please download our %2slatest %3s version here%4s.', WP_FS__SLUG ), $update->version, '<a href="' . $this->get_account_url( 'download_latest' ) . '">', $this->_site->plan->title, '</a>' ),
						__( 'New!', WP_FS__SLUG )
					);
				}
			}
			else if (false === $new_version && ! $background) {
				self::add_admin_message(
					__( 'Seems like you got the latest release.', WP_FS__SLUG ),
					__( 'You are all good!', WP_FS__SLUG )
				);
			}

			$this->_store_update( $update, true );
		}

		/**
		 * Handle user email update.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.3
		 * @users FS_Api
		 */
		private function _update_email() {
			$this->_logger->entrance();
			$new_email = fs_request_get( 'fs_email_' . $this->_slug, '' );

			$api  = $this->get_api_user_scope();
			$user = $api->call( "?plugin_id={$this->_plugin->id}&fields=id,email,is_verified", 'put', array(
				'email' => $new_email,
				'after_email_confirm_url' => $this->_get_admin_page_url(
					'account',
					array('fs_action' => 'sync_user')
				),
			) );

			if ( ! isset( $user->error ) ) {
				$this->_user->email       = $user->email;
				$this->_user->is_verified = $user->is_verified;
				$this->_store_user();
			} else {
				// handle different error cases.

			}
		}

		/**
		 * Verify user email.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.3
		 * @users FS_Api
		 */
		private function _verify_email() {
			$this->_handle_account_user_sync();

			if ($this->_user->is_verified())
				return;

			$api    = $this->get_api_site_scope();
			$result = $api->call( "/users/{$this->_user->id}/verify.json", 'put', array(
				'after_email_confirm_url' => $this->_get_admin_page_url(
					'account',
					array('fs_action' => 'sync_user')
				)
			));

			if ( ! isset( $result->error ) ) {
				self::add_admin_message(sprintf(__('Verification mail was just sent to %s. If you can\'t find it after 5 min, please check your spam box.', WP_FS__SLUG), sprintf('<a href="mailto:%1s">%2s</a>', esc_url( $this->_user->email ), $this->_user->email)));
			} else {
				// handle different error cases.

			}
		}

		/**
		 * Handle account page updates / edits / actions.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.2
		 *
		 */
		private function _handle_account_edits() {

			if ( fs_request_is_action( 'delete_account' ) ) {
				check_admin_referer( 'delete_account' );
				$this->delete_account_event();
				if ( fs_redirect( $this->_get_admin_page_url() ) ) {
					exit();
				}
			}

			if ( fs_request_is_action( 'downgrade_account' ) ) {
				check_admin_referer( 'downgrade_account' );
				$this->_downgrade_site();
				return;
			}

			if ( fs_request_is_action( 'verify_email' ) ) {
				check_admin_referer( 'verify_email' );
				$this->_verify_email();
				return;
			}

			if ( fs_request_is_action( 'sync_user' ) ) {
				$this->_handle_account_user_sync();
				return;
			}

			if ( fs_request_is_action( 'sync_license' ) ) {
//				check_admin_referer( 'sync_license' );
				$this->_sync_license();
				return;
			}

			if ( fs_request_is_action( 'activate_license' ) ) {
				check_admin_referer( 'activate_license' );
				$this->_activate_license();
				return;
			}

			if ( fs_request_is_action( 'deactivate_license' ) ) {
				check_admin_referer( 'deactivate_license' );
				$this->_deactivate_license();
				return;
			}

			if ( fs_request_is_action( 'download_latest' ) ) {
				check_admin_referer( 'download_latest' );
				$this->_download_latest();
				return;
			}

			if ( fs_request_is_action( 'check_updates' ) ) {
				check_admin_referer( 'check_updates' );
				$this->_check_updates();
				return;
			}

			if ( fs_request_is_action( 'update_email' ) ) {
				check_admin_referer( 'update_email' );

				$this->_update_email();

				self::add_admin_message(__('Your email was successfully updated. You should receive an email with confirmation instructions in few moments.', WP_FS__SLUG));

				return;
			}

			$properties = array( 'site_secret_key', 'site_id', 'site_public_key' );
			foreach ( $properties as $p ) {
				if ( fs_request_is_action( 'update_' . $p ) ) {
					check_admin_referer( 'update_' . $p );

					$this->_logger->log( 'update_' . $p );

					$site_property                      = substr( $p, strlen( 'site_' ) );
					$site_property_value                = fs_request_get( 'fs_' . $p . '_' . $this->_slug, '' );
					$this->get_site()->{$site_property} = $site_property_value;

					// Store account after modification.
					$this->_store_site();

					$this->do_action( 'account_property_edit', 'site', $site_property, $site_property_value );
//					do_action('fs_account_property_edit_' . $this->_slug, 'site', $site_property, $site_property_value);

					self::add_admin_message(sprintf(__('You have successfully updated your %s .', WP_FS__SLUG), '<b>' . str_replace('_', ' ', $p) . '</b>'));

					break;
				}
			}
		}

		function _account_page_load() {
			$this->_logger->entrance();

			$this->_logger->info( var_export( $_REQUEST, true ) );

			fs_enqueue_local_style( 'fs_account', 'account.css' );

			$this->_handle_account_edits();

			$this->do_action( 'account_page_load_before_departure' );
		}

		/**
		 * Render account page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.0
		 */
		function _account_page_render() {
			$this->_logger->entrance();

			$vars = array( 'slug' => $this->_slug );
			fs_require_once_template( 'account.php', $vars );
		}

		/* Pricing & Upgrade
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * Render pricing page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.0
		 */
		function _pricing_page_render() {
			$this->_logger->entrance();

			$vars = array( 'slug' => $this->_slug );

			if ('true' === fs_request_get('checkout', false))
				fs_require_once_template( 'checkout.php', $vars );
			else
				fs_require_once_template( 'pricing.php', $vars );
		}

		/* Contact Us
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * Render contact-us page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.3
		 */
		function _contact_page_render() {
			$this->_logger->entrance();

			$vars = array( 'slug' => $this->_slug );
			fs_require_once_template( 'contact.php', $vars );
		}

		/**
		 * Hide all admin notices to prevent distractions.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.3
		 *
		 * @uses remove_all_actions()
		 */
		function _hide_admin_notices()
		{
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'network_admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'user_admin_notices' );
		}

		function _clean_admin_content_section_hook()
		{
			$this->_hide_admin_notices();

			// Hide footer.
			echo '<style>#wpfooter { display: none !important; }</style>';
		}

		/**
		 * Attach to admin_head hook to hide all admin notices.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.3
		 */
		function _clean_admin_content_section()
		{
			add_action( 'admin_head', array(&$this, '_clean_admin_content_section_hook') );
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
		 * @since 1.0.2
		 *
		 * @return FS_Api
		 */
		function get_api_user_scope() {
			if ( ! isset( $this->_user_api ) ) {
				$this->_user_api = FS_Api::instance( $this, 'user', $this->_user->id, $this->_user->public_key, $this->_user->secret_key );
			}

			return $this->_user_api;
		}

		private $_site_api;
		/**
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.2
		 *
		 * @return FS_Api
		 */
		function get_api_site_scope() {
			if ( ! isset( $this->_site_api ) ) {
				$this->_site_api = FS_Api::instance( $this, 'install', $this->_site->id, $this->_site->public_key, $this->_site->secret_key );
			}

			return $this->_site_api;
		}

		/* Action Links
		------------------------------------------------------------------------------------------------------------------*/
		private $_action_links_hooked = false;
		private $_action_links = array();

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.0
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
		 * @since 1.0.0
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
		 * @since 1.0.0
		 *
		 * @param $label
		 * @param $url
		 * @param bool $external
		 * @param int $priority
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
		 * Adds Upgrade link to the main Plugins page plugin link actions collection.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.0
		 */
		function _add_upgrade_action_link() {
			$this->_logger->entrance();

			if ( ! $this->is_paying__fs__() ) {
				$this->add_plugin_action_link( __( 'Upgrade', $this->_slug ), $this->get_upgrade_url(), true, 20, 'upgrade' );
			}
		}

		/**
		 * Forward page to activation page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.3
		 */
		function _redirect_on_activation_hook(){
			wp_redirect( $this->_get_admin_page_url() );
			exit();
		}

		/**
		 * Modify plugin's page action links collection.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.0
		 *
		 * @param array $links
		 * @param $file
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


		/* Plugin Auto-Updates (@since 1.0.4)
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * @var string[]
		 */
		private static $_auto_updated_plugins;
		/**
		 * @todo TEST IF IT WORKS!!!
		 *
		 * Include plugins for automatic updates based on stored settings.
		 *
		 * @see http://wordpress.stackexchange.com/questions/131394/how-do-i-exclude-plugins-from-getting-automatically-updated/131404#131404
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
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


		/* Messaging (@since 1.0.4)
		------------------------------------------------------------------------------------------------------------------*/
		/**
		 * @var array
		 */
		private static $_admin_messages = array();

		/**
		 * Handle admin_notices by printing the admin messages stacked in the queue.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 */
		static function _admin_notices_hook() {
			$key = 'admin_notices';

			if ( ! isset( self::$_admin_messages[ $key ] ) || ! is_array( self::$_admin_messages[ $key ] ) ) {
				return;
			}

			foreach ( self::$_admin_messages[ $key ] as $msg ) {
				fs_require_once_template( 'admin-notice.php', $msg );
			}
		}

		/**
		 * Handle all_admin_notices by printing the admin messages stacked in the queue.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 */
		static function _all_admin_notices_hook() {
			$key = 'all_admin_notices';

			if ( ! isset( self::$_admin_messages[ $key ] ) || ! is_array( self::$_admin_messages[ $key ] ) ) {
				return;
			}

			foreach ( self::$_admin_messages[ $key ] as $msg ) {
				fs_require_once_template( 'all-admin-notice.php', $msg );
			}
		}

		/**
		 * Add admin message to admin messages queue, and hook to admin_notices / all_admin_notices if not yet hooked.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @param string $message
		 * @param string $title
		 * @param string $type
		 * @param bool   $all_admin
		 *
		 * @uses add_action()
		 */
		static function add_admin_message($message, $title = '', $type = 'success', $all_admin = true) {
			$key = ( $all_admin ? 'all_admin_notices' : 'admin_notices' );

			if ( ! isset( self::$_admin_messages[ $key ] ) ) {
				self::$_admin_messages[ $key ] = array();

				add_action( $key, array( 'Freemius', "_{$key}_hook" ) );
			}

			self::$_admin_messages[ $key ][] = array( 'message' => $message, 'title' => $title, 'type' => $type );
		}

		/**
		 * Add admin message to all admin messages queue, and hook to all_admin_notices if not yet hooked.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @param string $message
		 * @param string $title
		 * @param string $type
		 *
		 * @uses add_action()
		 */
		static function add_all_admin_message($message, $title = '', $type = 'success')
		{
			self::add_admin_message($message, $title, $type, true);
		}
	}
