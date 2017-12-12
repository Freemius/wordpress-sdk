<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.0.3
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	// "final class"
	class Freemius extends Freemius_Abstract {
		/**
		 * SDK Version
		 *
		 * @var string
		 */
		public $version = WP_FS__SDK_VERSION;

		#region Plugin Info

		/**
		 * @since 1.0.1
		 *
		 * @var string
		 */
		private $_slug;

		/**
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private $_plugin_basename;
		/**
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private $_free_plugin_basename;
		/**
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private $_plugin_dir_path;
		/**
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private $_plugin_dir_name;
		/**
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private $_plugin_main_file_path;
		/**
		 * @var string[]
		 */
		private $_plugin_data;
		/**
		 * @since 1.0.9
		 *
		 * @var string
		 */
		private $_plugin_name;
		/**
		 * @since 1.2.2
		 *
		 * @var string
		 */
		private $_module_type;

		#endregion Plugin Info

		/**
		 * @since 1.0.9
		 *
		 * @var bool If false, don't turn Freemius on.
		 */
		private $_is_on;

		/**
		 * @since 1.1.3
		 *
		 * @var bool If false, don't turn Freemius on.
		 */
		private $_is_anonymous;

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
		 * @since 1.1.7.5
		 * @var bool Hints the SDK if plugin should run in anonymous mode (only adds feedback form).
		 */
		private $_anonymous_mode;

		/**
		 * @since 1.1.9
		 * @var bool Hints the SDK if plugin have any free plans.
		 */
		private $_is_premium_only;

		/**
		 * @since 1.2.1.6
		 * @var bool Hints the SDK if plugin have premium code version at all.
		 */
		private $_has_premium_version;

		/**
		 * @since 1.2.1.6
		 * @var bool Hints the SDK if plugin should ignore pending mode by simulating a skip.
		 */
		private $_ignore_pending_mode;

		/**
		 * @since 1.0.8
		 * @var bool Hints the SDK if the plugin has any paid plans.
		 */
		private $_has_paid_plans;

		/**
		 * @since 1.2.1.5
		 * @var int Hints the SDK if the plugin offers a trial period. If negative, no trial, if zero - has a trial but
		 *      without a specified period, if positive - the number of trial days.
		 */
		private $_trial_days = - 1;

		/**
		 * @since 1.2.1.5
		 * @var bool Hints the SDK if the trial requires a payment method or not.
		 */
		private $_is_trial_require_payment = false;

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
		 * @since 1.1.6
		 * @var string[]bool.
		 */
		private $_permissions;

		/**
		 * @var FS_Key_Value_Storage
		 */
		private $_storage;

		/**
		 * @since 1.2.2.7
		 * @var FS_Cache_Manager
		 */
		private $_cache;

		/**
		 * @since 1.0.0
		 *
		 * @var FS_Logger
		 */
		private $_logger;
		/**
		 * @since 1.0.4
		 *
		 * @var FS_Plugin
		 */
		private $_plugin = false;
		/**
		 * @since 1.0.4
		 *
		 * @var FS_Plugin|false
		 */
		private $_parent_plugin = false;
		/**
		 * @since 1.1.1
		 *
		 * @var Freemius
		 */
		private $_parent = false;
		/**
		 * @since 1.0.1
		 *
		 * @var FS_User
		 */
		private $_user = false;
		/**
		 * @since 1.0.1
		 *
		 * @var FS_Site
		 */
		private $_site = false;
		/**
		 * @since 1.0.1
		 *
		 * @var FS_Plugin_License
		 */
		private $_license;
		/**
		 * @since 1.0.2
		 *
		 * @var FS_Plugin_Plan[]
		 */
		private $_plans = false;
		/**
		 * @var FS_Plugin_License[]
		 * @since 1.0.5
		 */
		private $_licenses = false;

		/**
		 * @since 1.0.1
		 *
		 * @var FS_Admin_Menu_Manager
		 */
		private $_menu;

		/**
		 * @var FS_Admin_Notice_Manager
		 */
		private $_admin_notices;

		/**
		 * @since 1.1.6
		 *
		 * @var FS_Admin_Notice_Manager
		 */
		private static $_global_admin_notices;

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

		/**
		 * @since 1.2.2
		 *
		 * @var number
		 */
		private $_module_id;

		/**
		 * @var Freemius[]
		 */
		private static $_instances = array();

        /**
         * @author Leo Fajardo (@leorw)
         *
         * @since 1.2.3
         *
         * @var FS_Affiliate
         */
        private $affiliate = null;

        /**
         * @author Leo Fajardo (@leorw)
         *
         * @since 1.2.3
         *
         * @var FS_AffiliateTerms
         */
        private $plugin_affiliate_terms = null;

        /**
         * @author Leo Fajardo (@leorw)
         *
         * @since 1.2.3
         *
         * @var FS_AffiliateTerms
         */
        private $custom_affiliate_terms = null;

		#region Uninstall Reasons IDs

		const REASON_NO_LONGER_NEEDED = 1;
		const REASON_FOUND_A_BETTER_PLUGIN = 2;
		const REASON_NEEDED_FOR_A_SHORT_PERIOD = 3;
		const REASON_BROKE_MY_SITE = 4;
		const REASON_SUDDENLY_STOPPED_WORKING = 5;
		const REASON_CANT_PAY_ANYMORE = 6;
		const REASON_OTHER = 7;
		const REASON_DIDNT_WORK = 8;
		const REASON_DONT_LIKE_TO_SHARE_MY_INFORMATION = 9;
		const REASON_COULDNT_MAKE_IT_WORK = 10;
		const REASON_GREAT_BUT_NEED_SPECIFIC_FEATURE = 11;
		const REASON_NOT_WORKING = 12;
		const REASON_NOT_WHAT_I_WAS_LOOKING_FOR = 13;
		const REASON_DIDNT_WORK_AS_EXPECTED = 14;
		const REASON_TEMPORARY_DEACTIVATION = 15;

		#endregion

		/* Ctor
------------------------------------------------------------------------------------------------------------------*/

		/**
		 * Main singleton instance.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 *
		 * @param number      $module_id
		 * @param string|bool $slug
		 * @param bool        $is_init Since 1.2.1 Is initiation sequence.
		 */
		private function __construct( $module_id, $slug = false, $is_init = false ) {
			if ( $is_init && is_numeric( $module_id ) && is_string( $slug ) ) {
				$this->store_id_slug_type_path_map( $module_id, $slug );
			}

			$this->_module_id   = $module_id;
			$this->_slug        = $this->get_slug();
			$this->_module_type = $this->get_module_type();

			$this->_storage = FS_Key_Value_Storage::instance( $this->_module_type . '_data', $this->_slug );
			$this->_cache   = FS_Cache_Manager::get_manager( WP_FS___OPTION_PREFIX . "cache_{$module_id}" );

			$this->_logger = FS_Logger::get_logger( WP_FS__SLUG . '_' . $this->get_unique_affix(), WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );

			$this->_plugin_main_file_path = $this->_find_caller_plugin_file( $is_init );
			$this->_plugin_dir_path       = plugin_dir_path( $this->_plugin_main_file_path );
			$this->_plugin_basename       = $this->get_plugin_basename();
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

			if ( ! is_object( $this->_plugin ) ) {
				$this->_plugin = FS_Plugin_Manager::instance( $this->_module_id )->get();
			}

			$this->_admin_notices = FS_Admin_Notice_Manager::instance(
				$this->_slug . ( $this->is_theme() ? ':theme' : '' ),
				/**
				 * Ensure that the admin notice will always have a title by using the stored plugin title if available and
				 * retrieving the title via the "get_plugin_name" method if there is no stored plugin title available.
				 *
				 * @author Leo Fajardo (@leorw)
				 * @since  1.2.2
				 */
				( is_object( $this->_plugin ) ? $this->_plugin->title : $this->get_plugin_name() ),
				$this->get_unique_affix()
			);

			if ( 'true' === fs_request_get( 'fs_clear_api_cache' ) ||
			     'true' === fs_request_is_action( 'restart_freemius' )
			) {
				FS_Api::clear_cache();
				$this->_cache->clear();
			}

			$this->_register_hooks();

			$this->_load_account();

			$this->_version_updates_handler();
		}

		/**
		 * Checks whether this module has a settings menu.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @return bool
		 */
		function has_settings_menu() {
			return $this->_menu->has_menu();
		}

		/**
		 * Check if the context module is free wp.org theme.
		 *
		 * This method is helpful because:
		 *      1. wp.org themes are limited to a single submenu item,
		 *         and sub-submenu items are most likely not allowed (never verified).
		 *      2. wp.org themes are not allowed to redirect the user
		 *         after the theme activation, therefore, the agreed UX
		 *         is showing the opt-in as a modal dialog box after
		 *         activation (approved by @otto42, @emiluzelac, @greenshady, @grapplerulrich).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return bool
		 */
		function is_free_wp_org_theme() {
			return (
				$this->is_theme() &&
				$this->is_org_repo_compliant() &&
				! $this->is_premium()
			);
		}

		/**
		 * Checks whether this a submenu item is visible.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.6
		 * @since  1.2.2.7 Even if the menu item was specified to be hidden, when it is the context page, then show the submenu item so the user will have the right context page.
		 *
		 * @param string $slug
		 *
		 * @return bool
		 */
		function is_submenu_item_visible( $slug ) {
			if ( $this->is_admin_page( $slug ) ) {
				/**
				 * It is the current context page, so show the submenu item
				 * so the user will have the right context page, even if it
				 * was set to hidden.
				 */
				return true;
			}

			if ( ! $this->has_settings_menu() ) {
				// No menu settings at all.
				return false;
			}

			if ( $this->is_free_wp_org_theme() ) {
				/**
				 * wp.org themes are limited to a single submenu item, and
				 * sub-submenu items are most likely not allowed (never verified).
				 */
				return false;
			}

			return $this->_menu->is_submenu_item_visible( $slug );
		}

		/**
		 * Check if a Freemius page should be accessible via the UI.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @param string $slug
		 *
		 * @return bool
		 */
		function is_page_visible( $slug ) {
			if ( $this->is_admin_page( $slug ) ) {
				return true;
			}

			return $this->_menu->is_submenu_item_visible( $slug, true, true );
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

				$this->do_action( 'sdk_version_update', $this->_storage->sdk_last_version, $this->version );
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

				if ( ! empty( $this->_storage->plugin_last_version ) ) {
					// Different version of the plugin was installed before, therefore it's an update.
					$this->_storage->is_plugin_new_install = false;
				}

				$this->do_action( 'plugin_version_update', $this->_storage->plugin_last_version, $plugin_version );
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.5
		 *
		 * @param string $sdk_prev_version
		 * @param string $sdk_version
		 */
		function _data_migration( $sdk_prev_version, $sdk_version ) {
			/**
			 * @since 1.1.7.3 Fixed unwanted connectivity test cleanup.
			 */
			if ( empty( $sdk_prev_version ) ) {
				return;
			}

            if ( version_compare( $sdk_prev_version, '1.2.3', '<' ) &&
                version_compare( $sdk_version, '1.2.3', '>=' )
            ) {
                /**
                 * Starting from version 1.2.3, paths are stored as relative paths and not absolute paths; so when
                 * upgrading to 1.2.3, make paths relative.
                 *
                 * @author Leo Fajardo (@leorw)
                 */
                $this->make_paths_relative();
            }

			if ( version_compare( $sdk_prev_version, '1.1.5', '<' ) &&
			     version_compare( $sdk_version, '1.1.5', '>=' )
			) {
				// On version 1.1.5 merged connectivity and is_on data.
				if ( isset( $this->_storage->connectivity_test ) ) {
					if ( ! isset( $this->_storage->is_on ) ) {
						unset( $this->_storage->connectivity_test );
					} else {
						$connectivity_data              = $this->_storage->connectivity_test;
						$connectivity_data['is_active'] = $this->_storage->is_on['is_active'];
						$connectivity_data['timestamp'] = $this->_storage->is_on['timestamp'];

						// Override.
						$this->_storage->connectivity_test = $connectivity_data;

						// Remove previous structure.
						unset( $this->_storage->is_on );
					}

				}
			}
		}

        /**
         * Makes paths relative.
         *
         * @author Leo Fajardo
         * @since 1.2.3
         */
        private function make_paths_relative() {
            $id_slug_type_path_map = self::$_accounts->get_option( 'id_slug_type_path_map', array() );

            if ( isset( $id_slug_type_path_map[ $this->_module_id ]['path'] ) ) {
                $id_slug_type_path_map[ $this->_module_id ]['path'] = $this->get_relative_path( $id_slug_type_path_map[ $this->_module_id ]['path'] );

                self::$_accounts->set_option( 'id_slug_type_path_map', $id_slug_type_path_map, true );
            }

            if ( isset( $this->_storage->plugin_main_file ) ) {
                $plugin_main_file = $this->_storage->plugin_main_file;

                if ( isset( $plugin_main_file->path ) ) {
                    $this->_storage->plugin_main_file->path = $this->get_relative_path( $this->_storage->plugin_main_file->path );
                } else if ( isset( $plugin_main_file->prev_path ) ) {
                    $this->_storage->plugin_main_file->prev_path = $this->get_relative_path( $this->_storage->plugin_main_file->prev_path );
                }
            }

            // Remove invalid path that is still associated with the current slug if there's any.
            $file_slug_map = self::$_accounts->get_option( 'file_slug_map', array() );
            foreach ( $file_slug_map as $plugin_basename => $slug ) {
                if ( $slug === $this->_slug &&
                    $plugin_basename !== $this->_plugin_basename &&
                    ! file_exists( $this->get_absolute_path( $plugin_basename ) )
                ) {
                    unset( $file_slug_map[ $plugin_basename ] );
                    self::$_accounts->set_option( 'file_slug_map', $file_slug_map, true );

                    break;
                }
            }
        }

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @param string $plugin_prev_version
		 * @param string $plugin_version
		 */
		function _after_version_update( $plugin_prev_version, $plugin_version ) {
			if ( $this->is_theme() ) {
				// Expire the cache of the previous tabs since the theme may
				// have setting updates.
				$this->_cache->expire( 'tabs' );
				$this->_cache->expire( 'tabs_stylesheets' );
			}
		}

		/**
		 * This action is connected to the 'plugins_loaded' hook and helps to determine
		 * if this is a new plugin installation or a plugin update.
		 *
		 * There are 3 different use-cases:
		 *    1) New plugin installation right with Freemius:
		 *       1.1 _activate_plugin_event_hook() will be executed first
		 *       1.2 Since $this->_storage->is_plugin_new_install is not set,
		 *           and $this->_storage->plugin_last_version is not set,
		 *           $this->_storage->is_plugin_new_install will be set to TRUE.
		 *       1.3 When _plugins_loaded() will be executed, $this->_storage->is_plugin_new_install will
		 *           be already set to TRUE.
		 *
		 *    2) Plugin update, didn't have Freemius before, and now have the SDK:
		 *       2.1 _activate_plugin_event_hook() will not be executed, because
		 *           the activation hook do NOT fires on updates since WP 3.1.
		 *       2.2 When _plugins_loaded() will be executed, $this->_storage->is_plugin_new_install will
		 *           be empty, therefore, it will be set to FALSE.
		 *
		 *    3) Plugin update, had Freemius in prev version as well:
		 *       3.1 _version_updates_handler() will be executed 1st, since FS was installed
		 *           before, $this->_storage->plugin_last_version will NOT be empty,
		 *           therefore, $this->_storage->is_plugin_new_install will be set to FALSE.
		 *       3.2 When _plugins_loaded() will be executed, $this->_storage->is_plugin_new_install is
		 *           already set, therefore, it will not be modified.
		 *
		 *    Use-case #3 is backward compatible, #3.1 will be executed since 1.0.9.
		 *
		 * NOTE:
		 *    The only fallback of this mechanism is if an admin updates a plugin based on use-case #2,
		 *    and then, the next immediate PageView is the plugin's main settings page, it will not
		 *    show the opt-in right away. The reason it will happen is because Freemius execution
		 *    will be turned off till the plugin is fully loaded at least once
		 *    (till $this->_storage->was_plugin_loaded is TRUE).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.9
		 *
		 */
		function _plugins_loaded() {
			// Update flag that plugin was loaded with Freemius at least once.
			$this->_storage->was_plugin_loaded = true;

			/**
			 * Bug fix - only set to false when it's a plugin, due to the
			 * execution sequence of the theme hooks and our methods, if
			 * this will be set for themes, Freemius will always assume
			 * it's a theme update.
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since  1.2.2.2
			 */
			if ( $this->is_plugin() &&
			     ! isset( $this->_storage->is_plugin_new_install )
			) {
				$this->_storage->is_plugin_new_install = false;
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		private function _register_hooks() {
			$this->_logger->entrance();

			if ( is_admin() ) {
				if ( $this->is_plugin() ) {
					$plugin_dir = dirname( $this->_plugin_dir_path ) . '/';

					/**
					 * @since 1.2.2
					 *
					 * Hook to both free and premium version activations to support
					 * auto deactivation on the other version activation.
					 */
					register_activation_hook(
						$plugin_dir . $this->_free_plugin_basename,
						array( &$this, '_activate_plugin_event_hook' )
					);

					register_activation_hook(
						$plugin_dir . $this->premium_plugin_basename(),
						array( &$this, '_activate_plugin_event_hook' )
					);
				} else {
					add_action( 'after_switch_theme', array( &$this, '_activate_theme_event_hook' ), 10, 2 );

					/**
					 * Include the required hooks to capture the theme settings' page tabs
					 * and cache them.
					 *
					 * @author Vova Feldman (@svovaf)
					 * @since 1.2.2.7
					 */
					if ( ! $this->_cache->has_valid( 'tabs' ) ) {
						add_action( 'admin_footer', array( &$this, '_tabs_capture' ) );
						// Add license activation AJAX callback.
						$this->add_ajax_action( 'store_tabs', array( &$this, '_store_tabs_ajax_action' ) );

						add_action( 'admin_enqueue_scripts', array( &$this, '_store_tabs_styles' ), 9999999 );
					}

					add_action(
						'admin_footer',
						array( &$this, '_add_freemius_tabs' ),
						/**
						 * The tabs JS code must be executed after the tabs capture logic (_tabs_capture()).
						 * That's why the priority is 11 while the tabs capture logic is added
						 * with priority 10.
						 *
						 * @author Vova Feldman (@svovaf)
						 */
						11
					);

					add_action( 'admin_footer', array( &$this, '_style_premium_theme' ) );
				}

				/**
				 * Part of the mechanism to identify new plugin install vs. plugin update.
				 *
				 * @author Vova Feldman (@svovaf)
				 * @since  1.1.9
				 */
				if ( empty( $this->_storage->was_plugin_loaded ) ) {
					if ( $this->is_plugin() &&
					     $this->is_activation_mode( false ) &&
					     0 == did_action( 'plugins_loaded' )
					) {
						add_action( 'plugins_loaded', array( &$this, '_plugins_loaded' ) );
					} else {
						// If was activated before, then it was already loaded before.
						$this->_plugins_loaded();
					}
				}

				if ( ! self::is_ajax() ) {
					if ( ! $this->is_addon() ) {
						add_action( 'init', array( &$this, '_add_default_submenu_items' ), WP_FS__LOWEST_PRIORITY );
					}
				}
			}

			if ( $this->is_plugin() ) {
				register_deactivation_hook( $this->_plugin_main_file_path, array( &$this, '_deactivate_plugin_hook' ) );
			}

			if ( $this->is_theme() && self::is_customizer() ) {
				// Register customizer upsell.
				add_action( 'customize_register', array( &$this, '_customizer_register' ) );
			}

			add_action( 'init', array( &$this, '_redirect_on_clicked_menu_link' ), WP_FS__LOWEST_PRIORITY );

			add_action( 'admin_init', array( &$this, '_add_tracking_links' ) );
			add_action( 'admin_init', array( &$this, '_add_license_activation' ) );
			$this->add_ajax_action( 'update_billing', array( &$this, '_update_billing_ajax_action' ) );
			$this->add_ajax_action( 'start_trial', array( &$this, '_start_trial_ajax_action' ) );

			$this->add_ajax_action( 'install_premium_version', array(
				&$this,
				'_install_premium_version_ajax_action'
			) );

            $this->add_ajax_action( 'submit_affiliate_application', array( &$this, '_submit_affiliate_application' ) );

			$this->add_action( 'after_plans_sync', array( &$this, '_check_for_trial_plans' ) );

			$this->add_action( 'sdk_version_update', array( &$this, '_data_migration' ), WP_FS__DEFAULT_PRIORITY, 2 );
			$this->add_action( 'plugin_version_update', array( &$this, '_after_version_update' ), WP_FS__DEFAULT_PRIORITY, 2 );
			$this->add_filter( 'after_code_type_change', array( &$this, '_after_code_type_change' ) );

			add_action( 'admin_init', array( &$this, '_add_trial_notice' ) );
			add_action( 'admin_init', array( &$this, '_add_affiliate_program_notice' ) );
			add_action( 'admin_init', array( &$this, '_enqueue_common_css' ) );

			/**
			 * Handle request to reset anonymous mode for `get_reconnect_url()`.
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since  1.2.1.5
			 */
			if ( fs_request_is_action( 'reset_anonymous_mode' ) &&
			     $this->get_unique_affix() === fs_request_get( 'fs_unique_affix' )
			) {
				add_action( 'admin_init', array( &$this, 'connect_again' ) );
			}
		}

		/**
		 * Keeping the uninstall hook registered for free or premium plugin version may result to a fatal error that
		 * could happen when a user tries to uninstall either version while one of them is still active. Uninstalling a
		 * plugin will trigger inclusion of the free or premium version and if one of them is active during the
		 * uninstallation, a fatal error may occur in case the plugin's class or functions are already defined.
		 *
		 * @author Leo Fajardo (leorw)
		 *
		 * @since  1.2.0
		 */
		private function unregister_uninstall_hook() {
			$uninstallable_plugins = (array) get_option( 'uninstall_plugins' );
			unset( $uninstallable_plugins[ $this->_free_plugin_basename ] );
			unset( $uninstallable_plugins[ $this->premium_plugin_basename() ] );

			update_option( 'uninstall_plugins', $uninstallable_plugins );
		}

		/**
		 * @since 1.2.0 Invalidate module's main file cache, otherwise, FS_Plugin_Updater will not fetch updates.
		 */
		private function clear_module_main_file_cache() {
			if ( ! isset( $this->_storage->plugin_main_file ) ||
			     empty( $this->_storage->plugin_main_file->path )
			) {
				return;
			}

			$plugin_main_file = clone $this->_storage->plugin_main_file;

			// Store cached path (2nd layer cache).
			$plugin_main_file->prev_path = $plugin_main_file->path;

			// Clear cached path.
			unset( $plugin_main_file->path );

			$this->_storage->plugin_main_file = $plugin_main_file;

			/**
			 * Clear global cached path.
			 *
			 * @author Leo Fajardo (@leorw)
			 * @since  1.2.2
			 */
			$id_slug_type_path_map = self::$_accounts->get_option( 'id_slug_type_path_map' );
			unset( $id_slug_type_path_map[ $this->_module_id ]['path'] );
			self::$_accounts->set_option( 'id_slug_type_path_map', $id_slug_type_path_map, true );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		private function _register_account_hooks() {
			if ( ! is_admin() ) {
				return;
			}

			/**
			 * Always show the deactivation feedback form since we added
			 * automatic free version deactivation upon premium code activation.
			 *
			 * @since 1.2.1.6
			 */
			$this->add_ajax_action(
				'submit_uninstall_reason',
				array( &$this, '_submit_uninstall_reason_action' )
			);

			if ( ( $this->is_plugin() && self::is_plugins_page() ) ||
			     ( $this->is_theme() && self::is_themes_page() )
			) {
				add_action( 'admin_footer', array( &$this, '_add_deactivation_feedback_dialog_box' ) );
			}
		}

		/**
		 * Leverage backtrace to find caller plugin file path.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param  bool $is_init Is initiation sequence.
		 *
		 * @return string
		 */
		private function _find_caller_plugin_file( $is_init = false ) {
			// Try to load the cached value of the file path.
			if ( isset( $this->_storage->plugin_main_file ) ) {
				$plugin_main_file = $this->_storage->plugin_main_file;
                if ( isset( $plugin_main_file->path ) ) {
                    $absolute_path = $this->get_absolute_path( $plugin_main_file->path );
                    if ( file_exists( $absolute_path ) ) {
                        return $absolute_path;
                    }
				}
			}

			/**
			 * @since 1.2.1
			 *
			 * `clear_module_main_file_cache()` is clearing the plugin's cached path on
			 * deactivation. Therefore, if any plugin/theme was initiating `Freemius`
			 * with that plugin's slug, it was overriding the empty plugin path with a wrong path.
			 *
			 * So, we've added a special mechanism with a 2nd layer of cache that uses `prev_path`
			 * when the class instantiator isn't the module.
			 */
			if ( ! $is_init ) {
				// Fetch prev path cache.
				if ( isset( $this->_storage->plugin_main_file ) &&
				     isset( $this->_storage->plugin_main_file->prev_path )
				) {
                    $absolute_path = $this->get_absolute_path( $this->_storage->plugin_main_file->prev_path );
					if ( file_exists( $absolute_path ) ) {
						return $absolute_path;
					}
				}

				wp_die(
					$this->get_text_inline( 'Freemius SDK couldn\'t find the plugin\'s main file. Please contact sdk@freemius.com with the current error.', 'failed-finding-main-path' ) .
					" Module: {$this->_slug}; SDK: " . WP_FS__SDK_VERSION . ";",
					$this->get_text_inline( 'Error', 'error' ),
					array( 'back_link' => true )
				);
			}

			/**
			 * @since 1.2.1
			 *
			 * Only the original instantiator that calls dynamic_init can modify the module's path.
			 */
			// Find caller module.
			$id_slug_type_path_map            = self::$_accounts->get_option( 'id_slug_type_path_map', array() );
			$this->_storage->plugin_main_file = (object) array(
				'path' => $id_slug_type_path_map[ $this->_module_id ]['path'],
			);

			return $this->get_absolute_path( $id_slug_type_path_map[ $this->_module_id ]['path'] );
		}

        /**
         * @author Leo Fajardo (@leorw)
         * @since 1.2.3
         *
         * @param string $path
         *
         * @return string
         */
        private function get_relative_path( $path ) {
            $module_root_dir = $this->get_module_root_dir_path();
            if ( 0 === strpos( $path, $module_root_dir ) ) {
                $path = substr( $path, strlen( $module_root_dir ) );
            }

            return $path;
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 1.2.3
         *
         * @param string      $path
         * @param string|bool $module_type
         *
         * @return string
         */
        private function get_absolute_path( $path, $module_type = false ) {
            $module_root_dir = $this->get_module_root_dir_path( $module_type );
            if ( 0 !== strpos( $path, $module_root_dir ) ) {
                $path = fs_normalize_path( $module_root_dir . $path );
            }

            return $path;
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 1.2.3
         *
         * @param string|bool $module_type
         *
         * @return string
         */
        private function get_module_root_dir_path( $module_type = false ) {
            $is_plugin = empty( $module_type ) ?
                $this->is_plugin() :
                ( WP_FS__MODULE_TYPE_PLUGIN === $module_type );

            return fs_normalize_path( trailingslashit( $is_plugin ?
                WP_PLUGIN_DIR :
                get_theme_root() ) );
        }

		/**
		 * @author Leo Fajardo (@leorw)
		 *
		 * @param number $module_id
		 * @param string $slug
		 *
		 * @since  1.2.2
		 */
		private function store_id_slug_type_path_map( $module_id, $slug ) {
			$id_slug_type_path_map = self::$_accounts->get_option( 'id_slug_type_path_map', array() );

			$store_option = false;

			if ( ! isset( $id_slug_type_path_map[ $module_id ] ) ) {
				$id_slug_type_path_map[ $module_id ] = array(
					'slug' => $slug
				);

				$store_option = true;
			}

			if ( ! isset( $id_slug_type_path_map[ $module_id ]['path'] ) ||
			     /**
			      * This verification is for cases when suddenly the same module
			      * is installed but with a different folder name.
			      *
			      * @author Vova Feldman (@svovaf)
			      * @since 1.2.3
			      */
                ! file_exists( $this->get_absolute_path(
                    $id_slug_type_path_map[ $module_id ]['path'],
                    $id_slug_type_path_map[ $module_id ]['type']
                ) )
			) {
				$caller_main_file_and_type = $this->get_caller_main_file_and_type();

				$id_slug_type_path_map[ $module_id ]['type'] = $caller_main_file_and_type->module_type;
				$id_slug_type_path_map[ $module_id ]['path'] = $caller_main_file_and_type->path;

				$store_option = true;
			}

			if ( $store_option ) {
				self::$_accounts->set_option( 'id_slug_type_path_map', $id_slug_type_path_map, true );
			}
		}

		/**
		 * Identifies the caller type: plugin or theme.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.3 Find the earliest module in the call stack that calls to the SDK. This fix is for cases when
		 *         add-ons are relying on loading the SDK from the parent module, and also allows themes including the
		 *         SDK an internal file instead of directly from functions.php.
		 * @since  1.2.1.7 Knows how to handle cases when an add-on includes the parent module logic.
		 */
		private function get_caller_main_file_and_type() {
			self::require_plugin_essentials();

			$all_plugins       = get_plugins();
			$all_plugins_paths = array();

			// Get active plugin's main files real full names (might be symlinks).
			foreach ( $all_plugins as $relative_path => &$data ) {
				if ( false === strpos( fs_normalize_path( $relative_path ), '/' ) ) {
					/**
					 * Ignore plugins that don't have a folder (e.g. Hello Dolly) since they
					 * can't really include the SDK.
					 *
					 * @author Vova Feldman
					 * @since  1.2.1.7
					 */
					continue;
				}

				$all_plugins_paths[] = fs_normalize_path( realpath( WP_PLUGIN_DIR . '/' . $relative_path ) );
			}

			$caller_file_candidate = false;
			$caller_map            = array();
			$module_type           = WP_FS__MODULE_TYPE_PLUGIN;
			$themes_dir            = fs_normalize_path( get_theme_root() );

			for ( $i = 1, $bt = debug_backtrace(), $len = count( $bt ); $i < $len; $i ++ ) {
				if ( empty( $bt[ $i ]['file'] ) ) {
					continue;
				}

				if ( $i > 1 && ! empty( $bt[ $i - 1 ]['file'] ) && $bt[ $i ]['file'] === $bt[ $i - 1 ]['file'] ) {
					// If file same as the prev file in the stack, skip it.
					continue;
				}

				if ( ! empty( $bt[ $i ]['function'] ) && in_array( $bt[ $i ]['function'], array(
						'do_action',
						'apply_filter',
						// The string split is stupid, but otherwise, theme check
						// throws info notices.
						'requir' . 'e_once',
						'requir' . 'e',
						'includ' . 'e_once',
						'includ' . 'e'
					) )
				) {
					// Ignore call stack hooks and files inclusion.
					continue;
				}

				$caller_file_path = fs_normalize_path( $bt[ $i ]['file'] );

				if ( 'functions.php' === basename( $caller_file_path ) ) {
					/**
					 * 1. Assumes that theme's starting execution file is functions.php.
					 * 2. This complex logic fixes symlink issues (e.g. with Vargant).
					 *
					 * @author Vova Feldman (@svovaf)
					 * @since  1.2.2.5
					 */

					if ( $caller_file_path == fs_normalize_path( realpath( trailingslashit( $themes_dir ) . basename( dirname( $caller_file_path ) ) . '/' . basename( $caller_file_path ) ) ) ) {
						$module_type = WP_FS__MODULE_TYPE_THEME;

                        /**
                         * Relative path of the theme, e.g.:
                         * `my-theme/functions.php`
                         *
                         * @author Leo Fajardo (@leorw)
                         */
                        $caller_file_candidate = basename( dirname( $caller_file_path ) ) .
                            '/' .
                            basename( $caller_file_path );

						continue;
					}
				}

				$caller_file_hash = md5( $caller_file_path );

				if ( ! isset( $caller_map[ $caller_file_hash ] ) ) {
					foreach ( $all_plugins_paths as $plugin_path ) {
						if ( false !== strpos( $caller_file_path, fs_normalize_path( dirname( $plugin_path ) . '/' ) ) ) {
							$caller_map[ $caller_file_hash ] = fs_normalize_path( $plugin_path );
							break;
						}
					}
				}

				if ( isset( $caller_map[ $caller_file_hash ] ) ) {
					$module_type           = WP_FS__MODULE_TYPE_PLUGIN;
					$caller_file_candidate = plugin_basename( $caller_map[ $caller_file_hash ] );
				}
			}

			return (object) array(
				'module_type' => $module_type,
				'path'        => $caller_file_candidate
			);
		}

		#----------------------------------------------------------------------------------
		#region Deactivation Feedback Form
		#----------------------------------------------------------------------------------

		/**
		 * Displays a confirmation and feedback dialog box when the user clicks on the "Deactivate" link on the plugins
		 * page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @author Leo Fajardo (@leorw)
		 * @since  1.1.2
		 */
		function _add_deactivation_feedback_dialog_box() {
			/* Check the type of user:
			 * 1. Long-term (long-term)
			 * 2. Non-registered and non-anonymous short-term (non-registered-and-non-anonymous-short-term).
			 * 3. Short-term (short-term)
			 */
			$is_long_term_user = true;

			// Check if the site is at least 2 days old.
			$time_installed = $this->_storage->install_timestamp;

			// Difference in seconds.
			$date_diff = time() - $time_installed;

			// Convert seconds to days.
			$date_diff_days = floor( $date_diff / ( 60 * 60 * 24 ) );

			if ( $date_diff_days < 2 ) {
				$is_long_term_user = false;
			}

			$is_long_term_user = $this->apply_filters( 'is_long_term_user', $is_long_term_user );

			if ( $is_long_term_user ) {
				$user_type = 'long-term';
			} else {
				if ( ! $this->is_registered() && ! $this->is_anonymous() ) {
					$user_type = 'non-registered-and-non-anonymous-short-term';
				} else {
					$user_type = 'short-term';
				}
			}

			$uninstall_reasons = $this->_get_uninstall_reasons( $user_type );

			// Load the HTML template for the deactivation feedback dialog box.
			$vars = array(
				'reasons' => $uninstall_reasons,
				'id'      => $this->_module_id
			);

			/**
			 * @todo Deactivation form core functions should be loaded only once! Otherwise, when there are multiple Freemius powered plugins the same code is loaded multiple times. The only thing that should be loaded differently is the various deactivation reasons object based on the state of the plugin.
			 */
			fs_require_template( 'forms/deactivation/form.php', $vars );
		}

		/**
		 * @author Leo Fajardo (leorw)
		 * @since  1.1.2
		 *
		 * @param string $user_type
		 *
		 * @return array The uninstall reasons for the specified user type.
		 */
		function _get_uninstall_reasons( $user_type = 'long-term' ) {
			$module_type = $this->_module_type;

			$internal_message_template_var = array(
				'id' => $this->_module_id
			);

			if ( $this->is_registered() && false !== $this->get_plan() && $this->get_plan()->has_technical_support() ) {
				$contact_support_template = fs_get_template( 'forms/deactivation/contact.php', $internal_message_template_var );
			} else {
				$contact_support_template = '';
			}

			$reason_found_better_plugin = array(
				'id'                => self::REASON_FOUND_A_BETTER_PLUGIN,
				'text'              => sprintf( $this->get_text_inline( 'I found a better %s', 'reason-found-a-better-plugin' ), $module_type ),
				'input_type'        => 'textfield',
				'input_placeholder' => sprintf( $this->get_text_inline( "What's the %s's name?", 'placeholder-plugin-name' ), $module_type ),
			);

			$reason_temporary_deactivation = array(
				'id'                => self::REASON_TEMPORARY_DEACTIVATION,
				'text'              => sprintf(
					$this->get_text_inline( "It's a temporary %s. I'm just debugging an issue.", 'reason-temporary-x' ),
					strtolower( $this->is_plugin() ?
						$this->get_text_inline( 'Deactivation', 'deactivation' ) :
						$this->get_text_inline( 'Theme Switch', 'theme-switch' )
					)
				),
				'input_type'        => '',
				'input_placeholder' => ''
			);

			$reason_other = array(
				'id'                => self::REASON_OTHER,
				'text'              => $this->get_text_inline( 'Other', 'reason-other' ),
				'input_type'        => 'textfield',
				'input_placeholder' => ''
			);

			$long_term_user_reasons = array(
				array(
					'id'                => self::REASON_NO_LONGER_NEEDED,
					'text'              => sprintf( $this->get_text_inline( 'I no longer need the %s', 'reason-no-longer-needed' ), $module_type ),
					'input_type'        => '',
					'input_placeholder' => ''
				),
				$reason_found_better_plugin,
				array(
					'id'                => self::REASON_NEEDED_FOR_A_SHORT_PERIOD,
					'text'              => sprintf( $this->get_text_inline( 'I only needed the %s for a short period', 'reason-needed-for-a-short-period' ), $module_type ),
					'input_type'        => '',
					'input_placeholder' => ''
				),
				array(
					'id'                => self::REASON_BROKE_MY_SITE,
					'text'              => sprintf( $this->get_text_inline( 'The %s broke my site', 'reason-broke-my-site' ), $module_type ),
					'input_type'        => '',
					'input_placeholder' => '',
					'internal_message'  => $contact_support_template
				),
				array(
					'id'                => self::REASON_SUDDENLY_STOPPED_WORKING,
					'text'              => sprintf( $this->get_text_inline( 'The %s suddenly stopped working', 'reason-suddenly-stopped-working' ), $module_type ),
					'input_type'        => '',
					'input_placeholder' => '',
					'internal_message'  => $contact_support_template
				)
			);

			if ( $this->is_paying() ) {
				$long_term_user_reasons[] = array(
					'id'                => self::REASON_CANT_PAY_ANYMORE,
					'text'              => $this->get_text_inline( "I can't pay for it anymore", 'reason-cant-pay-anymore' ),
					'input_type'        => 'textfield',
					'input_placeholder' => $this->get_text_inline( 'What price would you feel comfortable paying?', 'placeholder-comfortable-price' )
				);
			}

			$reason_dont_share_info = array(
				'id'                => self::REASON_DONT_LIKE_TO_SHARE_MY_INFORMATION,
				'text'              => $this->get_text_inline( "I don't like to share my information with you", 'reason-dont-like-to-share-my-information' ),
				'input_type'        => '',
				'input_placeholder' => ''
			);

			/**
			 * If the current user has selected the "don't share data" reason in the deactivation feedback modal, inform the
			 * user by showing additional message that he doesn't have to share data and can just choose to skip the opt-in
			 * (the Skip button is included in the message to show). This message will only be shown if anonymous mode is
			 * enabled and the user's account is currently not in pending activation state (similar to the way the Skip
			 * button in the opt-in form is shown/hidden).
			 */
			if ( $this->is_enable_anonymous() && ! $this->is_pending_activation() ) {
				$reason_dont_share_info['internal_message'] = fs_get_template( 'forms/deactivation/retry-skip.php', $internal_message_template_var );
			}

			$uninstall_reasons = array(
				'long-term'                                   => $long_term_user_reasons,
				'non-registered-and-non-anonymous-short-term' => array(
					array(
						'id'                => self::REASON_DIDNT_WORK,
						'text'              => sprintf( $this->get_text_inline( "The %s didn't work", 'reason-didnt-work' ), $module_type ),
						'input_type'        => '',
						'input_placeholder' => ''
					),
					$reason_dont_share_info,
					$reason_found_better_plugin
				),
				'short-term'                                  => array(
					array(
						'id'                => self::REASON_COULDNT_MAKE_IT_WORK,
						'text'              => $this->get_text_inline( "I couldn't understand how to make it work", 'reason-couldnt-make-it-work' ),
						'input_type'        => '',
						'input_placeholder' => '',
						'internal_message'  => $contact_support_template
					),
					$reason_found_better_plugin,
					array(
						'id'                => self::REASON_GREAT_BUT_NEED_SPECIFIC_FEATURE,
						'text'              => sprintf( $this->get_text_inline( "The %s is great, but I need specific feature that you don't support", 'reason-great-but-need-specific-feature' ), $module_type ),
						'input_type'        => 'textarea',
						'input_placeholder' => $this->get_text_inline( 'What feature?', 'placeholder-feature' )
					),
					array(
						'id'                => self::REASON_NOT_WORKING,
						'text'              => sprintf( $this->get_text_inline( 'The %s is not working', 'reason-not-working' ), $module_type ),
						'input_type'        => 'textarea',
						'input_placeholder' => $this->get_text_inline( "Kindly share what didn't work so we can fix it for future users...", 'placeholder-share-what-didnt-work' )
					),
					array(
						'id'                => self::REASON_NOT_WHAT_I_WAS_LOOKING_FOR,
						'text'              => $this->get_text_inline( "It's not what I was looking for", 'reason-not-what-i-was-looking-for' ),
						'input_type'        => 'textarea',
						'input_placeholder' => $this->get_text_inline( "What you've been looking for?", 'placeholder-what-youve-been-looking-for' )
					),
					array(
						'id'                => self::REASON_DIDNT_WORK_AS_EXPECTED,
						'text'              => sprintf( $this->get_text_inline( "The %s didn't work as expected", 'reason-didnt-work-as-expected' ), $module_type ),
						'input_type'        => 'textarea',
						'input_placeholder' => $this->get_text_inline( 'What did you expect?', 'placeholder-what-did-you-expect' )
					)
				)
			);

			// Randomize the reasons for the current user type.
			shuffle( $uninstall_reasons[ $user_type ] );

			// Keep the following reasons as the last items in the list.
			$uninstall_reasons[ $user_type ][] = $reason_temporary_deactivation;
			$uninstall_reasons[ $user_type ][] = $reason_other;

			$uninstall_reasons = $this->apply_filters( 'uninstall_reasons', $uninstall_reasons );

			return $uninstall_reasons[ $user_type ];
		}

		/**
		 * Called after the user has submitted his reason for deactivating the plugin.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.1.2
		 */
		function _submit_uninstall_reason_action() {
			$this->_logger->entrance();

			$this->check_ajax_referer( 'submit_uninstall_reason' );

			$reason_id = fs_request_get( 'reason_id' );

			// Check if the given reason ID is an unsigned integer.
			if ( ! ctype_digit( $reason_id ) ) {
				exit;
			}

			$reason_info = trim( fs_request_get( 'reason_info', '' ) );
			if ( ! empty( $reason_info ) ) {
				$reason_info = substr( $reason_info, 0, 128 );
			}

			$reason = (object) array(
				'id'           => $reason_id,
				'info'         => $reason_info,
				'is_anonymous' => fs_request_get_bool( 'is_anonymous' )
			);

			$this->_storage->store( 'uninstall_reason', $reason );

			/**
			 * If the module type is "theme", trigger the uninstall event here (on theme deactivation) since themes do
			 * not support uninstall hook.
			 *
			 * @author Leo Fajardo (@leorw)
			 * @since  1.2.2
			 */
			if ( $this->is_theme() ) {
				$this->_uninstall_plugin_event( false );
				$this->remove_sdk_reference();
			}

			// Print '1' for successful operation.
			echo 1;
			exit;
		}

		#endregion

		#----------------------------------------------------------------------------------
		#region Instance
		#----------------------------------------------------------------------------------

		/**
		 * Main singleton instance.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 *
		 * @param  number      $module_id
		 * @param  string|bool $slug
		 * @param  bool        $is_init Is initiation sequence.
		 *
		 * @return Freemius|false
		 */
		static function instance( $module_id, $slug = false, $is_init = false ) {
			if ( empty( $module_id ) ) {
				return false;
			}

			if ( ! is_numeric( $module_id ) ) {
				if ( ! $is_init && true === $slug ) {
					$is_init = true;
				}

				$slug = $module_id;

				$module = FS_Plugin_Manager::instance( $slug )->get();

				if ( is_object( $module ) ) {
					$module_id = $module->id;
				}
			}

			$key = 'm_' . $module_id;

			if ( ! isset( self::$_instances[ $key ] ) ) {
				if ( 0 === count( self::$_instances ) ) {
					self::_load_required_static();
				}

				self::$_instances[ $key ] = new Freemius( $module_id, $slug, $is_init );
			}

			return self::$_instances[ $key ];
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param number $addon_id
		 *
		 * @return bool
		 */
		private static function has_instance( $addon_id ) {
			return isset( self::$_instances[ 'm_' . $addon_id ] );
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @param  string|number $id_or_slug
		 *
		 * @return number|false
		 */
		private static function get_module_id( $id_or_slug ) {
			if ( is_numeric( $id_or_slug ) ) {
				return $id_or_slug;
			}

			foreach ( self::$_instances as $instance ) {
				if ( $instance->is_plugin() && ( $id_or_slug === $instance->get_slug() ) ) {
					return $instance->get_id();
				}
			}

			return false;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param number $id
		 *
		 * @return false|Freemius
		 */
		static function get_instance_by_id( $id ) {
			return isset ( self::$_instances[ 'm_' . $id ] ) ?
				self::$_instances[ 'm_' . $id ] :
				false;
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
			$slug = self::find_slug_by_basename( $plugin_file );

			return ( false !== $slug ) ?
				self::instance( self::get_module_id( $slug ) ) :
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
		 * @param  string|number $id_or_slug
		 *
		 * @return false|Freemius
		 */
		function get_addon_instance( $id_or_slug ) {
			$addon_id = self::get_module_id( $id_or_slug );

			return self::instance( $addon_id );
		}

		#endregion ------------------------------------------------------------------

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return bool
		 */
		function is_parent_plugin_installed() {
			$is_active = self::has_instance( $this->_plugin->parent_plugin_id );

			if ( $is_active ) {
				return true;
			}

			/**
			 * Parent module might be a theme. If that's the case, the add-on's FS
			 * instance will be loaded prior to the theme's FS instance, therefore,
			 * we need to check if it's active with a "look ahead".
			 *
			 * @author Vova Feldman
			 * @since  1.2.2.3
			 */
			global $fs_active_plugins;
			if ( is_object( $fs_active_plugins ) && is_array( $fs_active_plugins->plugins ) ) {
				$active_theme = wp_get_theme();

				foreach ( $fs_active_plugins->plugins as $sdk => $module ) {
					if ( WP_FS__MODULE_TYPE_THEME === $module->type ) {
						if ( $module->plugin_path == $active_theme->get_stylesheet() ) {
							// Parent module is a theme and it's currently active.
							return true;
						}
					}
				}
			}

			return false;
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
		 * @param bool $and_on
		 *
		 * @return bool
		 */
		function is_activation_mode( $and_on = true ) {
			return (
				( $this->is_on() || ! $and_on ) &&
				( ! $this->is_registered() || ( $this->is_only_premium() && ! $this->has_features_enabled_license() ) ) &&
				( ! $this->is_enable_anonymous() ||
				  ( ! $this->is_anonymous() && ! $this->is_pending_activation() ) )
			);
		}

		/**
		 * Check if current page is the opt-in/pending-activation page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 *
		 * @return bool
		 */
		function is_activation_page() {
			if ( $this->_menu->is_main_settings_page() ) {
				return true;
			}

			if ( ! $this->is_activation_mode() ) {
				return false;
			}

			// Check if current page is matching the activation page.
			return $this->is_matching_url( $this->get_activation_url() );
		}

		/**
		 * Check if URL path's are matching and that all querystring
		 * arguments of the $sub_url exist in the $url with the same values.
		 *
		 * WARNING:
		 *  1. This method doesn't check if the sub/domain are matching.
		 *  2. Ignore case sensitivity.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 *
		 * @param string $sub_url
		 * @param string $url     If argument is not set, check if the sub_url matching the current's page URL.
		 *
		 * @return bool
		 */
		private function is_matching_url( $sub_url, $url = '' ) {
			if ( empty( $url ) ) {
				$url = $_SERVER['REQUEST_URI'];
			}

			$url     = strtolower( $url );
			$sub_url = strtolower( $sub_url );

			if ( parse_url( $sub_url, PHP_URL_PATH ) !== parse_url( $url, PHP_URL_PATH ) ) {
				// Different path - DO NOT OVERRIDE PAGE.
				return false;
			}

			$url_params = array();
			parse_str( parse_url( $url, PHP_URL_QUERY ), $url_params );

			$sub_url_params = array();
			parse_str( parse_url( $sub_url, PHP_URL_QUERY ), $sub_url_params );

			foreach ( $sub_url_params as $key => $val ) {
				if ( ! isset( $url_params[ $key ] ) || $val != $url_params[ $key ] ) {
					// Not matching query string - DO NOT OVERRIDE PAGE.
					return false;
				}
			}

			return true;
		}

		/**
		 * Get collection of all active plugins.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return array[string]array
		 */
		private static function get_active_plugins() {
			self::require_plugin_essentials();

			$active_plugin            = array();
			$all_plugins              = get_plugins();
			$active_plugins_basenames = get_option( 'active_plugins' );

			foreach ( $active_plugins_basenames as $plugin_basename ) {
				$active_plugin[ $plugin_basename ] = $all_plugins[ $plugin_basename ];
			}

			return $active_plugin;
		}

		/**
		 * Get collection of all plugins.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.8
		 *
		 * @return array Key is the plugin file path and the value is an array of the plugin data.
		 */
		private static function get_all_plugins() {
			self::require_plugin_essentials();

			$all_plugins              = get_plugins();
			$active_plugins_basenames = get_option( 'active_plugins' );

			foreach ( $all_plugins as $basename => &$data ) {
				// By default set to inactive (next foreach update the active plugins).
				$data['is_active'] = false;
				// Enrich with plugin slug.
				$data['slug'] = self::get_plugin_slug( $basename );
			}

			// Flag active plugins.
			foreach ( $active_plugins_basenames as $basename ) {
				if ( isset( $all_plugins[ $basename ] ) ) {
					$all_plugins[ $basename ]['is_active'] = true;
				}
			}

			return $all_plugins;
		}


		/**
		 * Cached result of get_site_transient( 'update_plugins' )
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.8
		 *
		 * @var object
		 */
		private static $_plugins_info;

		/**
		 * Helper function to get specified plugin's slug.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.8
		 *
		 * @param $basename
		 *
		 * @return string
		 */
		private static function get_plugin_slug( $basename ) {
			if ( ! isset( self::$_plugins_info ) ) {
				self::$_plugins_info = get_site_transient( 'update_plugins' );
			}

			$slug = '';

			if ( is_object( self::$_plugins_info ) ) {
				if ( isset( self::$_plugins_info->no_update ) &&
				     isset( self::$_plugins_info->no_update[ $basename ] ) &&
				     ! empty( self::$_plugins_info->no_update[ $basename ]->slug )
				) {
					$slug = self::$_plugins_info->no_update[ $basename ]->slug;
				} else if ( isset( self::$_plugins_info->response ) &&
				            isset( self::$_plugins_info->response[ $basename ] ) &&
				            ! empty( self::$_plugins_info->response[ $basename ]->slug )
				) {
					$slug = self::$_plugins_info->response[ $basename ]->slug;
				}
			}

			if ( empty( $slug ) ) {
				// Try to find slug from FS data.
				$slug = self::find_slug_by_basename( $basename );
			}

			if ( empty( $slug ) ) {
				// Fallback to plugin's folder name.
				$slug = dirname( $basename );
			}

			return $slug;
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

			self::$_global_admin_notices = FS_Admin_Notice_Manager::instance( 'global' );

			add_action( 'admin_menu', array( 'Freemius', '_add_debug_section' ) );

			add_action( "wp_ajax_fs_toggle_debug_mode", array( 'Freemius', '_toggle_debug_mode' ) );

			self::add_ajax_action_static( 'get_debug_log', array( 'Freemius', '_get_debug_log' ) );

			self::add_ajax_action_static( 'get_db_option', array( 'Freemius', '_get_db_option' ) );

			self::add_ajax_action_static( 'set_db_option', array( 'Freemius', '_set_db_option' ) );

			if ( 0 == did_action( 'plugins_loaded' ) ) {
				add_action( 'plugins_loaded', array( 'Freemius', '_load_textdomain' ), 1 );
			}

			self::$_statics_loaded = true;
		}

		#----------------------------------------------------------------------------------
		#region Localization
		#----------------------------------------------------------------------------------

		/**
		 * Load framework's text domain.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1
		 */
		static function _load_textdomain() {
			if ( ! is_admin() ) {
				return;
			}

			global $fs_active_plugins;

			// Works both for plugins and themes.
			load_plugin_textdomain(
				'freemius',
				false,
				$fs_active_plugins->newest->sdk_path . '/languages/'
			);
		}

		#endregion

		#----------------------------------------------------------------------------------
		#region Debugging
		#----------------------------------------------------------------------------------

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.8
		 */
		static function _add_debug_section() {
			if ( ! current_user_can( 'activate_plugins' )
			     && ! current_user_can( 'switch_themes' )
			) {
				return;
			}

			self::$_static_logger->entrance();

			$title = sprintf( '%s [v.%s]', fs_text_inline( 'Freemius Debug' ), WP_FS__SDK_VERSION );

			if ( WP_FS__DEV_MODE ) {
				// Add top-level debug menu item.
				$hook = FS_Admin_Menu_Manager::add_page(
					$title,
					$title,
					'manage_options',
					'freemius',
					array( 'Freemius', '_debug_page_render' )
				);
			} else {
				// Add hidden debug page.
				$hook = FS_Admin_Menu_Manager::add_subpage(
					null,
					$title,
					$title,
					'manage_options',
					'freemius',
					array( 'Freemius', '_debug_page_render' )
				);
			}

			if ( ! empty( $hook ) ) {
				add_action( "load-$hook", array( 'Freemius', '_debug_page_actions' ) );
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 */
		static function _toggle_debug_mode() {
			$is_on = fs_request_get( 'is_on', false, 'post' );

			if ( fs_request_is_post() && in_array( $is_on, array( 0, 1 ) ) ) {
				update_option( 'fs_debug_mode', $is_on );

				// Turn on/off storage logging.
				FS_Logger::_set_storage_logging( ( 1 == $is_on ) );
			}

			exit;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.6
		 */
		static function _get_debug_log() {
			$logs = FS_Logger::load_db_logs(
				fs_request_get( 'filters', false, 'post' ),
				! empty( $_POST['limit'] ) && is_numeric( $_POST['limit'] ) ? $_POST['limit'] : 200,
				! empty( $_POST['offset'] ) && is_numeric( $_POST['offset'] ) ? $_POST['offset'] : 0
			);

			self::shoot_ajax_success( $logs );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 */
		static function _get_db_option() {
			$option_name = fs_request_get( 'option_name' );

			$value = get_option( $option_name );

			$result = array(
				'name' => $option_name,
			);

			if ( false !== $value ) {
				if ( ! is_string( $value ) ) {
					$value = json_encode( $value );
				}

				$result['value'] = $value;
			}

			self::shoot_ajax_success( $result );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 */
		static function _set_db_option() {
			$option_name  = fs_request_get( 'option_name' );
			$option_value = fs_request_get( 'option_value' );

			if ( ! empty( $option_value ) ) {
				update_option( $option_name, $option_value );
			}

			self::shoot_ajax_success();
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.8
		 */
		static function _debug_page_actions() {
			self::_clean_admin_content_section();

			if ( fs_request_is_action( 'restart_freemius' ) ) {
				check_admin_referer( 'restart_freemius' );

				// Clear accounts data.
				self::$_accounts->clear( true );

				// Clear SDK reference cache.
				delete_option( 'fs_active_plugins' );
			} else if ( fs_request_is_action( 'simulate_trial' ) ) {
				check_admin_referer( 'simulate_trial' );

				$fs = freemius( fs_request_get( 'module_id' ) );

				// Update SDK install to at least 24 hours before.
				$fs->_storage->install_timestamp = ( time() - WP_FS__TIME_24_HOURS_IN_SEC );
				// Unset the trial shown timestamp.
				unset( $fs->_storage->trial_promotion_shown );
			} else if ( fs_request_is_action( 'delete_install' ) ) {
				check_admin_referer( 'delete_install' );

				self::_delete_site_by_slug(
					fs_request_get( 'slug' ),
					fs_request_get( 'module_type' )
				);
			} else if ( fs_request_is_action( 'download_logs' ) ) {
				check_admin_referer( 'download_logs' );

				$download_url = FS_Logger::download_db_logs(
					fs_request_get( 'filters', false, 'post' )
				);

				if ( false === $download_url ) {
					wp_die( 'Oops... there was an error while generating the logs download file. Please try again and if it doesn\'t work contact support@freemius.com.' );
				}

				fs_redirect( $download_url );
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.8
		 */
		static function _debug_page_render() {
			self::$_static_logger->entrance();

			$vars = array(
				'plugin_sites'    => self::get_all_sites(),
				'theme_sites'     => self::get_all_sites( WP_FS__MODULE_TYPE_THEME ),
				'users'           => self::get_all_users(),
				'addons'          => self::get_all_addons(),
				'account_addons'  => self::get_all_account_addons(),
				'plugin_licenses' => self::get_all_licenses(),
				'theme_licenses'  => self::get_all_licenses( WP_FS__MODULE_TYPE_THEME )
			);

			fs_enqueue_local_style( 'fs_debug', '/admin/debug.css' );
			fs_require_once_template( 'debug.php', $vars );
		}

		#endregion

		#----------------------------------------------------------------------------------
		#region Connectivity Issues
		#----------------------------------------------------------------------------------

		/**
		 * Check if Freemius should be turned on for the current plugin install.
		 *
		 * Note:
		 *  $this->_is_on is updated in has_api_connectivity()
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function is_on() {
			self::$_static_logger->entrance();

			if ( isset( $this->_is_on ) ) {
				return $this->_is_on;
			}

			// If already installed or pending then sure it's on :)
			if ( $this->is_registered() || $this->is_pending_activation() ) {
				$this->_is_on = true;

				return true;
			}

			return false;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 *
		 * @param bool $flush_if_no_connectivity
		 *
		 * @return bool
		 */
		private function should_run_connectivity_test( $flush_if_no_connectivity = false ) {
			if ( ! isset( $this->_storage->connectivity_test ) ) {
				// Connectivity test was never executed, or cache was cleared.
				return true;
			}

			if ( WP_FS__PING_API_ON_IP_OR_HOST_CHANGES ) {
				if ( WP_FS__IS_HTTP_REQUEST ) {
					if ( $_SERVER['HTTP_HOST'] != $this->_storage->connectivity_test['host'] ) {
						// Domain changed.
						return true;
					}

					if ( WP_FS__REMOTE_ADDR != $this->_storage->connectivity_test['server_ip'] ) {
						// Server IP changed.
						return true;
					}
				}
			}

			if ( $this->_storage->connectivity_test['is_connected'] &&
			     $this->_storage->connectivity_test['is_active']
			) {
				// API connected and Freemius is active - no need to run connectivity check.
				return false;
			}

			if ( $flush_if_no_connectivity ) {
				/**
				 * If explicitly asked to flush when no connectivity - do it only
				 * if at least 10 sec passed from the last API connectivity test.
				 */
				return ( isset( $this->_storage->connectivity_test['timestamp'] ) &&
				         ( WP_FS__SCRIPT_START_TIME - $this->_storage->connectivity_test['timestamp'] ) > 10 );
			}

			/**
			 * @since 1.1.7 Don't check for connectivity on plugin downgrade.
			 */
			$version = $this->get_plugin_version();
			if ( version_compare( $version, $this->_storage->connectivity_test['version'], '>' ) ) {
				// If it's a plugin version upgrade and Freemius is off or no connectivity, run connectivity test.
				return true;
			}

			return false;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 *
		 * @return object|false
		 */
		private function ping() {
			if ( WP_FS__SIMULATE_NO_API_CONNECTIVITY ) {
				return false;
			}

			$version = $this->get_plugin_version();

			$is_update = $this->apply_filters( 'is_plugin_update', $this->is_plugin_update() );

			return $this->get_api_plugin_scope()->ping(
				$this->get_anonymous_id(),
				array(
					'is_update' => json_encode( $is_update ),
					'version'   => $version,
					'sdk'       => $this->version,
					'is_admin'  => json_encode( is_admin() ),
					'is_ajax'   => json_encode( self::is_ajax() ),
					'is_cron'   => json_encode( self::is_cron() ),
					'is_http'   => json_encode( WP_FS__IS_HTTP_REQUEST ),
				)
			);
		}

		/**
		 * Check if there's any connectivity issue to Freemius API.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param bool $flush_if_no_connectivity
		 *
		 * @return bool
		 */
		function has_api_connectivity( $flush_if_no_connectivity = false ) {
			$this->_logger->entrance();

			if ( isset( $this->_has_api_connection ) && ( $this->_has_api_connection || ! $flush_if_no_connectivity ) ) {
				return $this->_has_api_connection;
			}

			if ( WP_FS__SIMULATE_NO_API_CONNECTIVITY &&
			     isset( $this->_storage->connectivity_test ) &&
			     true === $this->_storage->connectivity_test['is_connected']
			) {
				unset( $this->_storage->connectivity_test );
			}

			if ( ! $this->should_run_connectivity_test( $flush_if_no_connectivity ) ) {
				$this->_has_api_connection = $this->_storage->connectivity_test['is_connected'];
				/**
				 * @since 1.1.6 During dev mode, if there's connectivity - turn Freemius on regardless the configuration.
				 *
				 * @since 1.2.1.5 If the user running the premium version then ignore the 'is_active' flag and turn Freemius on to enable license key activation.
				 */
				$this->_is_on = $this->_storage->connectivity_test['is_active'] ||
				                $this->is_premium() ||
				                ( WP_FS__DEV_MODE && $this->_has_api_connection && ! WP_FS__SIMULATE_FREEMIUS_OFF );

				return $this->_has_api_connection;
			}

			$pong         = $this->ping();
			$is_connected = $this->get_api_plugin_scope()->is_valid_ping( $pong );

			if ( ! $is_connected ) {
				// API failure.
				$this->_add_connectivity_issue_message( $pong );
			}

			$this->store_connectivity_info( $pong, $is_connected );

			return $this->_has_api_connection;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 *
		 * @param object $pong
		 * @param bool   $is_connected
		 */
		private function store_connectivity_info( $pong, $is_connected ) {
			$this->_logger->entrance();

			$version = $this->get_plugin_version();

			if ( ! $is_connected || WP_FS__SIMULATE_FREEMIUS_OFF ) {
				$is_active = false;
			} else {
				$is_active = ( isset( $pong->is_active ) && true == $pong->is_active );
			}

			$is_active = $this->apply_filters(
				'is_on',
				$is_active,
				$this->is_plugin_update(),
				$version
			);

			$this->_storage->connectivity_test = array(
				'is_connected' => $is_connected,
				'host'         => $_SERVER['HTTP_HOST'],
				'server_ip'    => WP_FS__REMOTE_ADDR,
				'is_active'    => $is_active,
				'timestamp'    => WP_FS__SCRIPT_START_TIME,
				// Last version with connectivity attempt.
				'version'      => $version,
			);

			$this->_has_api_connection = $is_connected;
			$this->_is_on              = $is_active || ( WP_FS__DEV_MODE && $is_connected && ! WP_FS__SIMULATE_FREEMIUS_OFF );
		}

		/**
		 * Force turning Freemius on.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.8.1
		 *
		 * @return bool TRUE if successfully turned on.
		 */
		private function turn_on() {
			$this->_logger->entrance();

			if ( $this->is_on() || ! isset( $this->_storage->connectivity_test['is_active'] ) ) {
				return false;
			}

			$updated_connectivity              = $this->_storage->connectivity_test;
			$updated_connectivity['is_active'] = true;
			$updated_connectivity['timestamp'] = WP_FS__SCRIPT_START_TIME;
			$this->_storage->connectivity_test = $updated_connectivity;

			$this->_is_on = true;

			return true;
		}

		/**
		 * Anonymous and unique site identifier (Hash).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.0
		 *
		 * @return string
		 */
        function get_anonymous_id() {
            $unique_id = self::$_accounts->get_option( 'unique_id' );

            if ( empty( $unique_id ) || ! is_string( $unique_id ) ) {
                $key = get_site_url();

                // If localhost, assign microtime instead of domain.
                if ( WP_FS__IS_LOCALHOST ||
                     false !== strpos( $key, 'localhost' ) ||
                     false === strpos( $key, '.' )
                ) {
                    $key = microtime();
                }

                /**
                 * Base the unique identifier on the WP secure authentication key. Which
                 * turns the key into a secret anonymous identifier.
                 *
                 * @author Vova Feldman (@svovaf)
                 * @since 1.2.3
                 */
                $unique_id = md5( $key . SECURE_AUTH_KEY );

                self::$_accounts->set_option( 'unique_id', $unique_id, true );
            }

            $this->_logger->departure( $unique_id );

            return $unique_id;
        }

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 *
		 * @return \WP_User
		 */
		static function _get_current_wp_user() {
			self::require_pluggable_essentials();

			return wp_get_current_user();
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 *
		 * @param string $email
		 *
		 * @return bool
		 */
		static function is_valid_email( $email ) {
			if ( false === filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				return false;
			}

			$parts = explode( '@', $email );

			if ( 2 !== count( $parts ) || empty( $parts[1] ) ) {
				return false;
			}

			$blacklist = array(
				'admin.',
				'webmaster.',
				'localhost.',
				'dev.',
				'development.',
				'test.',
				'stage.',
				'staging.',
			);

			// Make sure domain is not one of the blacklisted.
			foreach ( $blacklist as $invalid ) {
				if ( 0 === strpos( $parts[1], $invalid ) ) {
					return false;
				}
			}

			// Get the UTF encoded domain name.
			$domain = idn_to_ascii( $parts[1] ) . '.';

			return ( checkdnsrr( $domain, 'MX' ) || checkdnsrr( $domain, 'A' ) );
		}
		
		/**
		 * Generate API connectivity issue message.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param mixed $api_result
		 * @param bool  $is_first_failure
		 */
		function _add_connectivity_issue_message( $api_result, $is_first_failure = true ) {
			if ( ! $this->is_premium() && $this->_enable_anonymous ) {
				// Don't add message if it's the free version and can run anonymously.
				return;
			}

			if ( ! function_exists( 'wp_nonce_url' ) ) {
				require_once ABSPATH . 'wp-includes/functions.php';
			}

			$current_user = self::_get_current_wp_user();
//			$admin_email = get_option( 'admin_email' );
			$admin_email = $current_user->user_email;

			// Aliases.
            $deactivate_plugin_title  = $this->esc_html_inline( 'That\'s exhausting, please deactivate', 'deactivate-plugin-title' );
            $deactivate_plugin_desc   = $this->esc_html_inline( 'We feel your frustration and sincerely apologize for the inconvenience. Hope to see you again in the future.', 'deactivate-plugin-desc' );
            $install_previous_title   = $this->esc_html_inline( 'Let\'s try your previous version', 'install-previous-title' );
            $install_previous_desc    = $this->esc_html_inline( 'Uninstall this version and install the previous one.', 'install-previous-desc' );
            $fix_issue_title          = $this->esc_html_inline( 'Yes - I\'m giving you a chance to fix it', 'fix-issue-title' );
            $fix_issue_desc           = $this->esc_html_inline( 'We will do our best to whitelist your server and resolve this issue ASAP. You will get a follow-up email to %s once we have an update.', 'fix-issue-desc' );
            /* translators: %s: product title (e.g. "Awesome Plugin" requires an access to...) */
            $x_requires_access_to_api = $this->esc_html_inline( '%s requires an access to our API.', 'x-requires-access-to-api' );
            $sysadmin_title = $this->esc_html_inline( 'I\'m a system administrator', 'sysadmin-title' );
            $happy_to_resolve_issue_asap = $this->esc_html_inline( 'We are sure it\'s an issue on our side and more than happy to resolve it for you ASAP if you give us a chance.', 'happy-to-resolve-issue-asap' );

			$message = false;
			if ( is_object( $api_result ) &&
			     isset( $api_result->error ) &&
			     isset( $api_result->error->code )
			) {
				switch ( $api_result->error->code ) {
					case 'curl_missing':
						$missing_methods = '';
						if ( is_array( $api_result->missing_methods ) &&
						     ! empty( $api_result->missing_methods )
						) {
							foreach ( $api_result->missing_methods as $m ) {
								if ( 'curl_version' === $m ) {
									continue;
								}

								if ( ! empty( $missing_methods ) ) {
									$missing_methods .= ', ';
								}

								$missing_methods .= sprintf( '<code>%s</code>', $m );
							}

							if ( ! empty( $missing_methods ) ) {
								$missing_methods = sprintf(
									'<br><br><b>%s</b> %s',
									$this->esc_html_inline( 'Disabled method(s):', 'curl-disabled-methods' ),
									$missing_methods
								);
							}
						}

						$message = sprintf(
                            $x_requires_access_to_api . ' ' .
							$this->esc_html_inline( 'We use PHP cURL library for the API calls, which is a very common library and usually installed and activated out of the box. Unfortunately, cURL is not activated (or disabled) on your server.', 'curl-missing-message' ) . ' ' .
							$missing_methods .
							' %s',
							'<b>' . $this->get_plugin_name() . '</b>',
							sprintf(
								'<ol id="fs_firewall_issue_options"><li>%s</li><li>%s</li><li>%s</li></ol>',
								sprintf(
									'<a class="fs-resolve" data-type="curl" href="#"><b>%s</b></a>%s',
									$this->get_text_inline( 'I don\'t know what is cURL or how to install it, help me!', 'curl-missing-no-clue-title' ),
									' - ' . sprintf(
										$this->get_text_inline( 'We\'ll make sure to contact your hosting company and resolve the issue. You will get a follow-up email to %s once we have an update.', 'curl-missing-no-clue-desc' ),
										'<a href="mailto:' . $admin_email . '">' . $admin_email . '</a>'
									)
								),
								sprintf(
									'<b>%s</b> - %s',
                                    $sysadmin_title,
									esc_html( sprintf( $this->get_text_inline( 'Great, please install cURL and enable it in your php.ini file. In addition, search for the \'disable_functions\' directive in your php.ini file and remove any disabled methods starting with \'curl_\'. To make sure it was successfully activated, use \'phpinfo()\'. Once activated, deactivate the %s and reactivate it back again.', 'curl-missing-sysadmin-desc' ), $this->get_module_label( true ) ) )
								),
								sprintf(
									'<a href="%s"><b>%s</b></a> - %s',
									wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $this->_plugin_basename . '&amp;plugin_status=all&amp;paged=1&amp;s=', 'deactivate-plugin_' . $this->_plugin_basename ),
									$deactivate_plugin_title,
									$deactivate_plugin_desc
								)
							)
						);
						break;
					case 'cloudflare_ddos_protection':
						$message = sprintf(
                            $x_requires_access_to_api . ' ' .
							$this->esc_html_inline( 'From unknown reason, CloudFlare, the firewall we use, blocks the connection.', 'cloudflare-blocks-connection-message' ) . ' ' .
                            $happy_to_resolve_issue_asap .
							' %s',
							'<b>' . $this->get_plugin_name() . '</b>',
							sprintf(
								'<ol id="fs_firewall_issue_options"><li>%s</li><li>%s</li><li>%s</li></ol>',
								sprintf(
									'<a class="fs-resolve" data-type="cloudflare" href="#"><b>%s</b></a>%s',
									$fix_issue_title,
									' - ' . sprintf(
										$fix_issue_desc,
										'<a href="mailto:' . $admin_email . '">' . $admin_email . '</a>'
									)
								),
								sprintf(
									'<a href="%s" target="_blank"><b>%s</b></a> - %s',
									sprintf( 'https://wordpress.org/plugins/%s/download/', $this->_slug ),
									$install_previous_title,
									$install_previous_desc
								),
								sprintf(
									'<a href="%s"><b>%s</b></a> - %s',
									wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $this->_plugin_basename . '&amp;plugin_status=all&amp;paged=1&amp;s=' . '', 'deactivate-plugin_' . $this->_plugin_basename ),
									$deactivate_plugin_title,
									$deactivate_plugin_desc
								)
							)
						);
						break;
					case 'squid_cache_block':
						$message = sprintf(
                            $x_requires_access_to_api . ' ' .
							$this->esc_html_inline( 'It looks like your server is using Squid ACL (access control lists), which blocks the connection.', 'squid-blocks-connection-message' ) .
							' %s',
							'<b>' . $this->get_plugin_name() . '</b>',
							sprintf(
								'<ol id="fs_firewall_issue_options"><li>%s</li><li>%s</li><li>%s</li></ol>',
								sprintf(
									'<a class="fs-resolve" data-type="squid" href="#"><b>%s</b></a> - %s',
									$this->esc_html_inline( 'I don\'t know what is Squid or ACL, help me!', 'squid-no-clue-title' ),
									sprintf(
										$this->esc_html_inline( 'We\'ll make sure to contact your hosting company and resolve the issue. You will get a follow-up email to %s once we have an update.', 'squid-no-clue-desc' ),
										'<a href="mailto:' . $admin_email . '">' . $admin_email . '</a>'
									)
								),
								sprintf(
									'<b>%s</b> - %s',
                                    $sysadmin_title,
									sprintf(
										$this->esc_html_inline( 'Great, please whitelist the following domains: %s. Once you are done, deactivate the %s and activate it again.', 'squid-sysadmin-desc' ),
										// We use a filter since the plugin might require additional API connectivity.
										'<b>' . implode( ', ', $this->apply_filters( 'api_domains', array( 'api.freemius.com', 'wp.freemius.com' ) ) ) . '</b>',
										$this->_module_type
									)
								),
								sprintf(
									'<a href="%s"><b>%s</b></a> - %s',
									wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $this->_plugin_basename . '&amp;plugin_status=all&amp;paged=1&amp;s=', 'deactivate-plugin_' . $this->_plugin_basename ),
									$deactivate_plugin_title,
									$deactivate_plugin_desc
								)
							)
						);
						break;
//					default:
//						$message = $this->get_text_inline( 'connectivity-test-fails-message' );
//						break;
				}
			}

			$message_id = 'failed_connect_api';
			$type       = 'error';

            $connectivity_test_fails_message = $this->esc_html_inline( 'From unknown reason, the API connectivity test failed.', 'connectivity-test-fails-message' );

			if ( false === $message ) {
				if ( $is_first_failure ) {
					// First attempt failed.
					$message = sprintf(
                        $x_requires_access_to_api . ' ' .
                        $connectivity_test_fails_message . ' ' .
						$this->esc_html_inline( 'It\'s probably a temporary issue on our end. Just to be sure, with your permission, would it be o.k to run another connectivity test?', 'connectivity-test-maybe-temporary' ) . '<br><br>' .
						'%s',
						'<b>' . $this->get_plugin_name() . '</b>',
						sprintf(
							'<div id="fs_firewall_issue_options">%s %s</div>',
							sprintf(
								'<a  class="button button-primary fs-resolve" data-type="retry_ping" href="#">%s</a>',
								$this->get_text_inline( 'Yes - do your thing', 'yes-do-your-thing' )
							),
							sprintf(
								'<a href="%s" class="button">%s</a>',
								wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $this->_plugin_basename . '&amp;plugin_status=all&amp;paged=1&amp;s=', 'deactivate-plugin_' . $this->_plugin_basename ),
								$this->get_text_inline( 'No - just deactivate', 'no-deactivate' )
							)
						)
					);

					$message_id = 'failed_connect_api_first';
					$type       = 'promotion';
				} else {
					// Second connectivity attempt failed.
					$message = sprintf(
                        $x_requires_access_to_api . ' ' .
                        $connectivity_test_fails_message . ' ' .
                        $happy_to_resolve_issue_asap .
						' %s',
						'<b>' . $this->get_plugin_name() . '</b>',
						sprintf(
							'<ol id="fs_firewall_issue_options"><li>%s</li><li>%s</li><li>%s</li></ol>',
							sprintf(
								'<a class="fs-resolve" data-type="general" href="#"><b>%s</b></a>%s',
								$fix_issue_title,
								' - ' . sprintf(
									$fix_issue_desc,
									'<a href="mailto:' . $admin_email . '">' . $admin_email . '</a>'
								)
							),
							sprintf(
								'<a href="%s" target="_blank"><b>%s</b></a> - %s',
								sprintf( 'https://wordpress.org/plugins/%s/download/', $this->_slug ),
								$install_previous_title,
								$install_previous_desc
							),
							sprintf(
								'<a href="%s"><b>%s</b></a> - %s',
								wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $this->_plugin_basename . '&amp;plugin_status=all&amp;paged=1&amp;s=', 'deactivate-plugin_' . $this->_plugin_basename ),
								$deactivate_plugin_title,
								$deactivate_plugin_desc
							)
						)
					);
				}
			}

			$this->_admin_notices->add_sticky(
				$message,
				$message_id,
				$this->get_text_x_inline( 'Oops', 'exclamation', 'oops' ) . '...',
				$type
			);
		}

		/**
		 * Handle user request to resolve connectivity issue.
		 * This method will send an email to Freemius API technical staff for resolution.
		 * The email will contain server's info and installed plugins (might be caching issue).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		function _email_about_firewall_issue() {
			$this->_admin_notices->remove_sticky( 'failed_connect_api' );

			$pong = $this->ping();

			$is_connected = $this->get_api_plugin_scope()->is_valid_ping( $pong );

			if ( $is_connected ) {
				$this->store_connectivity_info( $pong, $is_connected );

				echo $this->get_after_plugin_activation_redirect_url();
				exit;
			}

			$current_user = self::_get_current_wp_user();
			$admin_email  = $current_user->user_email;

			$error_type = fs_request_get( 'error_type', 'general' );

			switch ( $error_type ) {
				case 'squid':
					$title = 'Squid ACL Blocking Issue';
					break;
				case 'cloudflare':
					$title = 'CloudFlare Blocking Issue';
					break;
				default:
					$title = 'API Connectivity Issue';
					break;
			}

			$custom_email_sections = array();

			// Add 'API Error' custom email section.
			$custom_email_sections['api_error'] = array(
				'title' => 'API Error',
				'rows'  => array(
					'ping' => array(
						'API Error',
						is_string( $pong ) ? htmlentities( $pong ) : json_encode( $pong )
					),
				)
			);

			// Send email with technical details to resolve API connectivity issues.
			$this->send_email(
				'api@freemius.com',                              // recipient
				$title . ' [' . $this->get_plugin_name() . ']',  // subject
				$custom_email_sections,
				array( "Reply-To: $admin_email <$admin_email>" ) // headers
			);

			$this->_admin_notices->add_sticky(
				sprintf(
					$this->get_text_inline( 'Thank for giving us the chance to fix it! A message was just sent to our technical staff. We will get back to you as soon as we have an update to %s. Appreciate your patience.', 'fix-request-sent-message' ),
					'<a href="mailto:' . $admin_email . '">' . $admin_email . '</a>'
				),
				'server_details_sent'
			);

			// Action was taken, tell that API connectivity troubleshooting should be off now.

			echo "1";
			exit;
		}

		/**
		 * Handle connectivity test retry approved by the user.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 */
		function _retry_connectivity_test() {
			$this->_admin_notices->remove_sticky( 'failed_connect_api_first' );

			$pong = $this->ping();

			$is_connected = $this->get_api_plugin_scope()->is_valid_ping( $pong );

			if ( $is_connected ) {
				$this->store_connectivity_info( $pong, $is_connected );

				echo $this->get_after_plugin_activation_redirect_url();
			} else {
				// Add connectivity issue message after 2nd failed attempt.
				$this->_add_connectivity_issue_message( $pong, false );

				echo "1";
			}

			exit;
		}

		static function _add_firewall_issues_javascript() {
			$params = array();
			fs_require_once_template( 'firewall-issues-js.php', $params );
		}

		#endregion

		#----------------------------------------------------------------------------------
		#region Email
		#----------------------------------------------------------------------------------

		/**
		 * Generates and sends an HTML email with customizable sections.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.1.2
		 *
		 * @param string $to_address
		 * @param string $subject
		 * @param array  $sections
		 * @param array  $headers
		 *
		 * @return bool Whether the email contents were sent successfully.
		 */
		private function send_email(
			$to_address,
			$subject,
			$sections = array(),
			$headers = array()
		) {
			$default_sections = $this->get_email_sections();

			// Insert new sections or replace the default email sections.
			if ( is_array( $sections ) && ! empty( $sections ) ) {
				foreach ( $sections as $section_id => $custom_section ) {
					if ( ! isset( $default_sections[ $section_id ] ) ) {
						// If the section does not exist, add it.
						$default_sections[ $section_id ] = $custom_section;
					} else {
						// If the section already exists, override it.
						$current_section = $default_sections[ $section_id ];

						// Replace the current section's title if a custom section title exists.
						if ( isset( $custom_section['title'] ) ) {
							$current_section['title'] = $custom_section['title'];
						}

						// Insert new rows under the current section or replace the default rows.
						if ( isset( $custom_section['rows'] ) && is_array( $custom_section['rows'] ) && ! empty( $custom_section['rows'] ) ) {
							foreach ( $custom_section['rows'] as $row_id => $row ) {
								$current_section['rows'][ $row_id ] = $row;
							}
						}

						$default_sections[ $section_id ] = $current_section;
					}
				}
			}

			$vars    = array( 'sections' => $default_sections );
			$message = fs_get_template( 'email.php', $vars );

			// Set the type of email to HTML.
			$headers[] = 'Content-type: text/html; charset=UTF-8';

			$header_string = implode( "\r\n", $headers );

			return wp_mail(
				$to_address,
				$subject,
				$message,
				$header_string
			);
		}

		/**
		 * Generates the data for the sections of the email content.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.1.2
		 *
		 * @return array
		 */
		private function get_email_sections() {
			// Retrieve the current user's information so that we can get the user's email, first name, and last name below.
			$current_user = self::_get_current_wp_user();

			// Retrieve the cURL version information so that we can get the version number below.
			$curl_version_information = curl_version();

			$active_plugin = self::get_active_plugins();

			// Generate the list of active plugins separated by new line.
			$active_plugin_string = '';
			foreach ( $active_plugin as $plugin ) {
				$active_plugin_string .= sprintf(
					'<a href="%s">%s</a> [v%s]<br>',
					$plugin['PluginURI'],
					$plugin['Name'],
					$plugin['Version']
				);
			}

			$server_ip = WP_FS__REMOTE_ADDR;

			// Add PHP info for deeper investigation.
			ob_start();
			phpinfo();
			$php_info = ob_get_clean();

			$api_domain = substr( FS_API__ADDRESS, strpos( FS_API__ADDRESS, ':' ) + 3 );

			// Generate the default email sections.
			$sections = array(
				'sdk'      => array(
					'title' => 'SDK',
					'rows'  => array(
						'fs_version'   => array( 'FS Version', $this->version ),
						'curl_version' => array( 'cURL Version', $curl_version_information['version'] )
					)
				),
				'plugin'   => array(
					'title' => ucfirst( $this->get_module_type() ),
					'rows'  => array(
						'name'    => array( 'Name', $this->get_plugin_name() ),
						'version' => array( 'Version', $this->get_plugin_version() )
					)
				),
				'api'      => array(
					'title' => 'API Subdomain',
					'rows'  => array(
						'dns' => array(
							'DNS_CNAME',
							function_exists( 'dns_get_record' ) ?
								var_export( dns_get_record( $api_domain, DNS_CNAME ), true ) :
								'dns_get_record() disabled/blocked'
						),
						'ip'  => array(
							'IP',
							function_exists( 'gethostbyname' ) ?
								gethostbyname( $api_domain ) :
								'gethostbyname() disabled/blocked'
						),
					),
				),
				'site'     => array(
					'title' => 'Site',
					'rows'  => array(
						'unique_id'   => array( 'Unique ID', $this->get_anonymous_id() ),
						'address'     => array( 'Address', site_url() ),
						'host'        => array(
							'HTTP_HOST',
							( ! empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '' )
						),
						'hosting'     => array(
							'Hosting Company' => fs_request_has( 'hosting_company' ) ?
								fs_request_get( 'hosting_company' ) :
								'Unknown',
						),
						'server_addr' => array(
							'SERVER_ADDR',
							'<a href="http://www.projecthoneypot.org/ip_' . $server_ip . '">' . $server_ip . '</a>'
						)
					)
				),
				'user'     => array(
					'title' => 'User',
					'rows'  => array(
						'email' => array( 'Email', $current_user->user_email ),
						'first' => array( 'First', $current_user->user_firstname ),
						'last'  => array( 'Last', $current_user->user_lastname )
					)
				),
				'plugins'  => array(
					'title' => 'Plugins',
					'rows'  => array(
						'active_plugins' => array( 'Active Plugins', $active_plugin_string )
					)
				),
				'php_info' => array(
					'title' => 'PHP Info',
					'rows'  => array(
						'info' => array( $php_info )
					),
				)
			);

			// Allow the sections to be modified by other code.
			$sections = $this->apply_filters( 'email_template_sections', $sections );

			return $sections;
		}

		#endregion

		#----------------------------------------------------------------------------------
		#region Initialization
		#----------------------------------------------------------------------------------

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

			$this->parse_settings( $plugin_info );

            if ( $this->has_affiliate_program() ) {
                $this->fetch_affiliate_and_terms();
            }

            if ( ! self::is_ajax() ) {
                if ( ! $this->is_addon() || $this->is_only_premium() ) {
                    add_action( 'admin_menu', array( &$this, '_prepare_admin_menu' ), WP_FS__LOWEST_PRIORITY );
                }
            }

            if ( $this->should_stop_execution() ) {
				return;
			}

			if ( ! $this->is_registered() ) {
				if ( $this->is_anonymous() ) {
					// If user skipped, no need to test connectivity.
					$this->_has_api_connection = true;
					$this->_is_on              = true;
				} else {
					if ( ! $this->has_api_connectivity() ) {
						if ( $this->_admin_notices->has_sticky( 'failed_connect_api_first' ) ||
						     $this->_admin_notices->has_sticky( 'failed_connect_api' )
						) {
							if ( ! $this->_enable_anonymous || $this->is_premium() ) {
								// If anonymous mode is disabled, add firewall admin-notice message.
								add_action( 'admin_footer', array( 'Freemius', '_add_firewall_issues_javascript' ) );

								$this->add_ajax_action( 'resolve_firewall_issues', array(
									&$this,
									'_email_about_firewall_issue'
								) );

								$this->add_ajax_action( 'retry_connectivity_test', array(
									&$this,
									'_retry_connectivity_test'
								) );
							}
						}

						return;
					} else {
						$this->_admin_notices->remove_sticky( array(
							'failed_connect_api_first',
							'failed_connect_api',
						) );

						if ( $this->_anonymous_mode ) {
							// Simulate anonymous mode.
							$this->_is_anonymous = true;
						}
					}
				}

				// Check if Freemius is on for the current plugin.
				// This MUST be executed after all the plugin variables has been loaded.
				if ( ! $this->is_on() ) {
					return;
				}
			}

			if ( $this->has_api_connectivity() ) {
				if ( self::is_cron() ) {
					$this->hook_callback_to_sync_cron();
				} else if ( $this->is_user_in_admin() ) {
					/**
					 * Schedule daily data sync cron if:
					 *
					 *  1. User opted-in (for tracking).
					 *  2. If skipped, but later upgraded (opted-in via upgrade).
					 *
					 * @author Vova Feldman (@svovaf)
					 * @since  1.1.7.3
					 *
					 */
					if ( $this->is_registered() ) {
						if ( ! $this->is_sync_cron_on() && $this->is_tracking_allowed() ) {
							$this->schedule_sync_cron();
						}
					}

					/**
					 * Check if requested for manual blocking background sync.
					 */
					if ( fs_request_has( 'background_sync' ) ) {
						$this->run_manual_sync();
					}
				}
			}

			if ( $this->is_registered() ) {
				$this->hook_callback_to_install_sync();
			}

			if ( $this->is_addon() ) {
				if ( $this->is_parent_plugin_installed() ) {
					// Link to parent FS.
					$this->_parent = self::get_instance_by_id( $this->_plugin->parent_plugin_id );

					// Get parent plugin reference.
					$this->_parent_plugin = $this->_parent->get_plugin();
				}
			}

			if ( $this->is_user_in_admin() ) {
				if ( self::is_plugins_page() && $this->is_plugin() ) {
					$this->hook_plugin_action_links();
				}

				if ( $this->is_addon() ) {
					if ( ! $this->is_parent_plugin_installed() ) {
						$parent_name = $this->get_option( $plugin_info, 'parent_name', null );

						if ( isset( $plugin_info['parent'] ) ) {
							$parent_name = $this->get_option( $plugin_info['parent'], 'name', null );
						}

						$this->_admin_notices->add(
							( ! empty( $parent_name ) ?
								sprintf( $this->get_text_x_inline( '%s cannot run without %s.', 'addonX cannot run without pluginY', 'addon-x-cannot-run-without-y' ), $this->get_plugin_name(), $parent_name ) :
								sprintf( $this->get_text_x_inline( '%s cannot run without the plugin.', 'addonX cannot run...', 'addon-x-cannot-run-without-parent' ), $this->get_plugin_name() )
							),
							$this->get_text_x_inline( 'Oops', 'exclamation', 'oops' ) . '...',
							'error'
						);

						return;
					} else {
						if ( $this->_parent->is_registered() && ! $this->is_registered() ) {
							// If parent plugin activated, automatically install add-on for the user.
							$this->_activate_addon_account( $this->_parent );
						} else if ( ! $this->_parent->is_registered() && $this->is_registered() ) {
							// If add-on activated and parent not, automatically install parent for the user.
							$this->activate_parent_account( $this->_parent );
						}

						// @todo This should be only executed on activation. It should be migrated to register_activation_hook() together with other activation related logic.
						if ( $this->is_premium() ) {
							// Remove add-on download admin-notice.
							$this->_parent->_admin_notices->remove_sticky( array(
								'addon_plan_upgraded_' . $this->_slug,
								'no_addon_license_' . $this->_slug,
							) );
						}

//						$this->deactivate_premium_only_addon_without_license();
					}
				} else {
					if ( $this->has_addons() &&
					     'plugin-information' === fs_request_get( 'tab', false ) &&
					     $this->get_id() == fs_request_get( 'parent_plugin_id', false )
					) {
						require_once WP_FS__DIR_INCLUDES . '/fs-plugin-info-dialog.php';

						new FS_Plugin_Info_Dialog( $this );
					}
				}

				add_action( 'admin_init', array( &$this, '_admin_init_action' ) );

//				if ( $this->is_registered() ||
//				     $this->is_anonymous() ||
//				     $this->is_pending_activation()
//				) {
//					$this->_init_admin();
//				}
			}

			/**
			 * Should be called outside `$this->is_user_in_admin()` scope
			 * because the updater has some logic that needs to be executed
			 * during AJAX calls.
			 *
			 * Currently we need to hook to the `http_request_host_is_external` filter.
			 * In the future, there might be additional logic added.
			 *
			 * @author Vova Feldman
			 * @since  1.2.1.6
			 */
			if ( $this->is_premium() && $this->has_release_on_freemius() ) {
				new FS_Plugin_Updater( $this );
			}

			$this->do_action( 'initiated' );

			if ( $this->_storage->prev_is_premium !== $this->_plugin->is_premium ) {
				if ( isset( $this->_storage->prev_is_premium ) ) {
					$this->apply_filters(
						'after_code_type_change',
						// New code type.
						$this->_plugin->is_premium
					);
				} else {
					// Set for code type for the first time.
					$this->_storage->prev_is_premium = $this->_plugin->is_premium;
				}
			}

			if ( ! $this->is_addon() ) {
				if ( $this->is_registered() ) {
					// Fix for upgrade from versions < 1.0.9.
					if ( ! isset( $this->_storage->activation_timestamp ) ) {
						$this->_storage->activation_timestamp = WP_FS__SCRIPT_START_TIME;
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
		 * @author Leo Fajardo (@leorw)
		 *
		 * @since  1.2.1.5
		 */
		function _stop_tracking_callback() {
			$this->_logger->entrance();

			$this->check_ajax_referer( 'stop_tracking' );

			$result = $this->stop_tracking();

			if ( true === $result ) {
				self::shoot_ajax_success();
			}

			$this->_logger->api_error( $result );

			self::shoot_ajax_failure(
				sprintf( $this->get_text_inline( 'Unexpected API error. Please contact the %s\'s author with the following error.', 'unexpected-api-error' ), $this->_module_type ) .
				( $this->is_api_error( $result ) && isset( $result->error ) ?
					$result->error->message :
					var_export( $result, true ) )
			);
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.1.5
		 */
		function _allow_tracking_callback() {
			$this->_logger->entrance();

			$this->check_ajax_referer( 'allow_tracking' );

			$result = $this->allow_tracking();

			if ( true === $result ) {
				self::shoot_ajax_success();
			}

			$this->_logger->api_error( $result );

			self::shoot_ajax_failure(
				sprintf( $this->get_text_inline( 'Unexpected API error. Please contact the %s\'s author with the following error.', 'unexpected-api-error' ), $this->_module_type ) .
				( $this->is_api_error( $result ) && isset( $result->error ) ?
					$result->error->message :
					var_export( $result, true ) )
			);
		}

		/**
		 * Opt-out from usage tracking.
		 *
		 * Note: This will not delete the account information but will stop all tracking.
		 *
		 * Returns:
		 *  1. FALSE  - If the user never opted-in.
		 *  2. TRUE   - If successfully opted-out.
		 *  3. object - API result on failure.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.1.5
		 *
		 * @return bool|object
		 */
		function stop_tracking() {
			$this->_logger->entrance();

			if ( ! $this->is_registered() ) {
				// User never opted-in.
				return false;
			}

			if ( $this->is_tracking_prohibited() ) {
				// Already disconnected.
				return true;
			}

			// Send update to FS.
			$result = $this->get_api_site_scope()->call( '/?fields=is_disconnected', 'put', array(
				'is_disconnected' => true
			) );

			if ( ! $this->is_api_result_entity( $result ) ||
			     ! isset( $result->is_disconnected ) ||
			     ! $result->is_disconnected
			) {
				$this->_logger->api_error( $result );

				return $result;
			}

			$this->_site->is_disconnected = $result->is_disconnected;
			$this->_store_site();

			$this->clear_sync_cron();

			// Successfully disconnected.
			return true;
		}

		/**
		 * Opt-in back into usage tracking.
		 *
		 * Note: This will only work if the user opted-in previously.
		 *
		 * Returns:
		 *  1. FALSE  - If the user never opted-in.
		 *  2. TRUE   - If successfully opted-in back to usage tracking.
		 *  3. object - API result on failure.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.1.5
		 *
		 * @return bool|object
		 */
		function allow_tracking() {
			$this->_logger->entrance();

			if ( ! $this->is_registered() ) {
				// User never opted-in.
				return false;
			}

			if ( $this->is_tracking_allowed() ) {
				// Tracking already allowed.
				return true;
			}

			$result = $this->get_api_site_scope()->call( '/?is_disconnected', 'put', array(
				'is_disconnected' => false
			) );

			if ( ! $this->is_api_result_entity( $result ) ||
			     ! isset( $result->is_disconnected ) ||
			     $result->is_disconnected
			) {
				$this->_logger->api_error( $result );

				return $result;
			}

			$this->_site->is_disconnected = $result->is_disconnected;
			$this->_store_site();

			$this->schedule_sync_cron();

			// Successfully reconnected.
			return true;
		}

		/**
		 * If user opted-in and later disabled usage-tracking,
		 * re-allow tracking for licensing and updates.
		 *
		 * @author Leo Fajardo (@leorw)
		 *
		 * @since  1.2.1.5
		 */
		private function reconnect_locally() {
			$this->_logger->entrance();

			if ( $this->is_tracking_prohibited() &&
			     $this->is_registered()
			) {
				$this->_site->is_disconnected = false;
				$this->_store_site();
			}
		}

		/**
		 * Parse plugin's settings (as defined by the plugin dev).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 *
		 * @param array $plugin_info
		 *
		 * @throws \Freemius_Exception
		 */
		private function parse_settings( &$plugin_info ) {
			$this->_logger->entrance();

			$id          = $this->get_numeric_option( $plugin_info, 'id', false );
			$public_key  = $this->get_option( $plugin_info, 'public_key', false );
			$secret_key  = $this->get_option( $plugin_info, 'secret_key', null );
			$parent_id   = $this->get_numeric_option( $plugin_info, 'parent_id', null );
			$parent_name = $this->get_option( $plugin_info, 'parent_name', null );

			/**
			 * @author Vova Feldman (@svovaf)
			 * @since  1.1.9 Try to pull secret key from external config.
			 */
			if ( is_null( $secret_key ) && defined( "WP_FS__{$this->_slug}_SECRET_KEY" ) ) {
				$secret_key = constant( "WP_FS__{$this->_slug}_SECRET_KEY" );
			}

			if ( isset( $plugin_info['parent'] ) ) {
				$parent_id = $this->get_numeric_option( $plugin_info['parent'], 'id', null );
//				$parent_slug       = $this->get_option( $plugin_info['parent'], 'slug', null );
//				$parent_public_key = $this->get_option( $plugin_info['parent'], 'public_key', null );
//				$parent_name = $this->get_option( $plugin_info['parent'], 'name', null );
			}

			if ( false === $id ) {
				throw new Freemius_Exception( array(
					'error' => array(
						'type'    => 'ParameterNotSet',
						'message' => 'Plugin id parameter is not set.',
						'code'    => 'plugin_id_not_set',
						'http'    => 500,
					)
				) );
			}
			if ( false === $public_key ) {
				throw new Freemius_Exception( array(
					'error' => array(
						'type'    => 'ParameterNotSet',
						'message' => 'Plugin public_key parameter is not set.',
						'code'    => 'plugin_public_key_not_set',
						'http'    => 500,
					)
				) );
			}

			$plugin = ( $this->_plugin instanceof FS_Plugin ) ?
				$this->_plugin :
				new FS_Plugin();

			$plugin->update( array(
				'id'                   => $id,
				'type'                 => $this->get_option( $plugin_info, 'type', $this->_module_type),
				'public_key'           => $public_key,
				'slug'                 => $this->_slug,
				'parent_plugin_id'     => $parent_id,
				'version'              => $this->get_plugin_version(),
				'title'                => $this->get_plugin_name(),
				'file'                 => $this->_plugin_basename,
				'is_premium'           => $this->get_bool_option( $plugin_info, 'is_premium', true ),
				'is_live'              => $this->get_bool_option( $plugin_info, 'is_live', true ),
				'affiliate_moderation' => $this->get_option( $plugin_info, 'has_affiliation' ),
			) );

			if ( $plugin->is_updated() ) {
				// Update plugin details.
				$this->_plugin = FS_Plugin_Manager::instance( $this->_module_id )->store( $plugin );
			}
			// Set the secret key after storing the plugin, we don't want to store the key in the storage.
			$this->_plugin->secret_key = $secret_key;

			if ( ! isset( $plugin_info['menu'] ) ) {
				$plugin_info['menu'] = array();

				if ( ! empty( $this->_storage->sdk_last_version ) &&
				     version_compare( $this->_storage->sdk_last_version, '1.1.2', '<=' )
				) {
					// Backward compatibility to 1.1.2
					$plugin_info['menu']['slug'] = isset( $plugin_info['menu_slug'] ) ?
						$plugin_info['menu_slug'] :
						$this->_slug;
				}
			}

			$this->_menu = FS_Admin_Menu_Manager::instance(
				$this->_module_id,
				$this->_module_type,
				$this->get_unique_affix()
			);

			$this->_menu->init( $plugin_info['menu'], $this->is_addon() );

			$this->_has_addons          = $this->get_bool_option( $plugin_info, 'has_addons', false );
			$this->_has_paid_plans      = $this->get_bool_option( $plugin_info, 'has_paid_plans', true );
			$this->_has_premium_version = $this->get_bool_option( $plugin_info, 'has_premium_version', $this->_has_paid_plans );
			$this->_ignore_pending_mode = $this->get_bool_option( $plugin_info, 'ignore_pending_mode', false );
			$this->_is_org_compliant    = $this->get_bool_option( $plugin_info, 'is_org_compliant', true );
			$this->_is_premium_only     = $this->get_bool_option( $plugin_info, 'is_premium_only', false );
			if ( $this->_is_premium_only ) {
				// If premium only plugin, disable anonymous mode.
				$this->_enable_anonymous = false;
				$this->_anonymous_mode   = false;
			} else {
				$this->_enable_anonymous = $this->get_bool_option( $plugin_info, 'enable_anonymous', true );
				$this->_anonymous_mode   = $this->get_bool_option( $plugin_info, 'anonymous_mode', false );
			}
			$this->_permissions = $this->get_option( $plugin_info, 'permissions', array() );

			if ( ! empty( $plugin_info['trial'] ) ) {
				$this->_trial_days = $this->get_numeric_option(
					$plugin_info['trial'],
					'days',
					// Default to 0 - trial without days specification.
					0
				);

				$this->_is_trial_require_payment = $this->get_bool_option( $plugin_info['trial'], 'is_require_payment', false );
			}
		}

		/**
		 * @param string[] $options
		 * @param string   $key
		 * @param mixed    $default
		 *
		 * @return bool
		 */
		private function get_option( &$options, $key, $default = false ) {
			return ! empty( $options[ $key ] ) ? $options[ $key ] : $default;
		}

		private function get_bool_option( &$options, $key, $default = false ) {
			return isset( $options[ $key ] ) && is_bool( $options[ $key ] ) ? $options[ $key ] : $default;
		}

		private function get_numeric_option( &$options, $key, $default = false ) {
			return isset( $options[ $key ] ) && is_numeric( $options[ $key ] ) ? $options[ $key ] : $default;
		}

		/**
		 * Gate keeper.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 *
		 * @return bool
		 */
		private function should_stop_execution() {
			if ( empty( $this->_storage->was_plugin_loaded ) ) {
				/**
				 * Don't execute Freemius until plugin was fully loaded at least once,
				 * to give the opportunity for the activation hook to run before pinging
				 * the API for connectivity test. This logic is relevant for the
				 * identification of new plugin install vs. plugin update.
				 *
				 * @author Vova Feldman (@svovaf)
				 * @since  1.1.9
				 */
				return true;
			}

			if ( $this->is_activation_mode() ) {
				if ( ! is_admin() ) {
					/**
					 * If in activation mode, don't execute Freemius outside of the
					 * admin dashboard.
					 *
					 * @author Vova Feldman (@svovaf)
					 * @since  1.1.7.3
					 */
					return true;
				}

				if ( ! WP_FS__IS_HTTP_REQUEST ) {
					/**
					 * If in activation and executed without HTTP context (e.g. CLI, Cronjob),
					 * then don't start Freemius.
					 *
					 * @author Vova Feldman (@svovaf)
					 * @since  1.1.6.3
					 *
					 * @link   https://wordpress.org/support/topic/errors-in-the-freemius-class-when-running-in-wordpress-in-cli
					 */
					return true;
				}

				if ( self::is_cron() ) {
					/**
					 * If in activation mode, don't execute Freemius during wp crons
					 * (wp crons have HTTP context - called as HTTP request).
					 *
					 * @author Vova Feldman (@svovaf)
					 * @since  1.1.7.3
					 */
					return true;
				}

				if ( self::is_ajax() &&
				     ! $this->_admin_notices->has_sticky( 'failed_connect_api_first' ) &&
				     ! $this->_admin_notices->has_sticky( 'failed_connect_api' )
				) {
					/**
					 * During activation, if running in AJAX mode, unless there's a sticky
					 * connectivity issue notice, don't run Freemius.
					 *
					 * @author Vova Feldman (@svovaf)
					 * @since  1.1.7.3
					 */
					return true;
				}
			}

			return false;
		}

		/**
		 * Triggered after code type has changed.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.9.1
		 */
		function _after_code_type_change() {
			$this->_logger->entrance();

			if ( $this->is_theme() ) {
				// Expire the cache of the previous tabs since the theme may
				// have setting updates after code type has changed.
				$this->_cache->expire( 'tabs' );
				$this->_cache->expire( 'tabs_stylesheets' );
			}

			if ( $this->is_registered() ) {
				if ( ! $this->is_addon() ) {
					add_action(
						is_admin() ? 'admin_init' : 'init',
						array( &$this, '_plugin_code_type_changed' )
					);
				}

				if ( $this->is_premium() ) {
					// Purge cached payments after switching to the premium version.
					// @todo This logic doesn't handle purging the cache for serviceware module upgrade.
					$this->get_api_user_scope()->purge_cache( "/plugins/{$this->_module_id}/payments.json?include_addons=true" );
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
			$this->_logger->entrance();

			if ( $this->is_premium() ) {
				$this->reconnect_locally();

				// Activated premium code.
				$this->do_action( 'after_premium_version_activation' );

				// Remove all sticky messages related to download of the premium version.
				$this->_admin_notices->remove_sticky( array(
					'trial_started',
					'plan_upgraded',
					'plan_changed',
					'license_activated',
				) );

				if ( ! $this->is_only_premium() ) {
                    $this->_admin_notices->add_sticky(
                        sprintf( $this->get_text_inline( 'Premium %s version was successfully activated.', 'premium-activated-message' ), $this->_module_type ),
                        'premium_activated',
                        $this->get_text_x_inline( 'W00t',
	                        'Used to express elation, enthusiasm, or triumph (especially in electronic communication).', 'woot' ) . '!'
                    );
                }
			} else {
				// Remove sticky message related to premium code activation.
				$this->_admin_notices->remove_sticky( 'premium_activated' );

				// Activated free code (after had the premium before).
				$this->do_action( 'after_free_version_reactivation' );

				if ( $this->is_paying() && ! $this->is_premium() ) {
					$this->_admin_notices->add_sticky(
						sprintf(
							/* translators: %s: License type (e.g. you have a professional license) */
							$this->get_text_inline( 'You have a %s license.', 'you-have-x-license' ),
							$this->_site->plan->title
						) . $this->get_complete_upgrade_instructions(),
						'plan_upgraded',
						$this->get_text_x_inline( 'Yee-haw', 'interjection expressing joy or exuberance', 'yee-haw' ) . '!'
					);
				}
			}

			// Schedule code type changes event.
			$this->schedule_install_sync();

			/**
			 * Unregister the uninstall hook for the other version of the plugin (with different code type) to avoid
			 * triggering a fatal error when uninstalling that plugin. For example, after deactivating the "free" version
			 * of a specific plugin, its uninstall hook should be unregistered after the "premium" version has been
			 * activated. If we don't do that, a fatal error will occur when we try to uninstall the "free" version since
			 * the main file of the "free" version will be loaded first before calling the hooked callback. Since the
			 * free and premium versions are almost identical (same class or have same functions), a fatal error like
			 * "Cannot redeclare class MyClass" or "Cannot redeclare my_function()" will occur.
			 */
			$this->unregister_uninstall_hook();

			$this->clear_module_main_file_cache();

			// Update is_premium of latest version.
			$this->_storage->prev_is_premium = $this->_plugin->is_premium;
		}

		#endregion

		#----------------------------------------------------------------------------------
		#region Add-ons
		#----------------------------------------------------------------------------------

		/**
		 * Check if add-on installed and activated on site.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param string|number $id_or_slug
		 * @param bool|null     $is_premium Since 1.2.1.7 can check for specified add-on version.
		 *
		 * @return bool
		 */
		function is_addon_activated( $id_or_slug, $is_premium = null ) {
			$this->_logger->entrance();

			$addon_id     = self::get_module_id( $id_or_slug );
			$is_activated = self::has_instance( $addon_id );

			if ( ! $is_activated ) {
				return false;
			}

			if ( is_bool( $is_premium ) ) {
				// Check if the specified code version is activate.
				$addon        = $this->get_addon_instance( $addon_id );
				$is_activated = ( $is_premium === $addon->is_premium() );
			}

			return $is_activated;
		}

		/**
		 * Check if add-on was connected to install
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7
		 *
		 * @param  string|number $id_or_slug
		 *
		 * @return bool
		 */
		function is_addon_connected( $id_or_slug ) {
			$this->_logger->entrance();

			$sites = self::get_all_sites( WP_FS__MODULE_TYPE_PLUGIN );

			$addon_id = self::get_module_id( $id_or_slug );
			$addon    = $this->get_addon( $addon_id );
			$slug     = $addon->slug;
			if ( ! isset( $sites[ $slug ] ) ) {
				return false;
			}

			$site = $sites[ $slug ];

			$plugin = FS_Plugin_Manager::instance( $addon_id )->get();

			if ( $plugin->parent_plugin_id != $this->_plugin->id ) {
				// The given slug do NOT belong to any of the plugin's add-ons.
				return false;
			}

			return ( is_object( $site ) &&
			         is_numeric( $site->id ) &&
			         is_numeric( $site->user_id ) &&
			         is_object( $site->plan )
			);
		}

		/**
		 * Determines if add-on installed.
		 *
		 * NOTE: This is a heuristic and only works if the folder/file named as the slug.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param  string|number $id_or_slug
		 *
		 * @return bool
		 */
		function is_addon_installed( $id_or_slug ) {
			$this->_logger->entrance();

			$addon_id = self::get_module_id( $id_or_slug );

			return file_exists( fs_normalize_path( WP_PLUGIN_DIR . '/' . $this->get_addon_basename( $addon_id ) ) );
		}

		/**
		 * Get add-on basename.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param  string|number $id_or_slug
		 *
		 * @return string
		 */
		function get_addon_basename( $id_or_slug ) {
			$addon_id = self::get_module_id( $id_or_slug );

			if ( $this->is_addon_activated( $addon_id ) ) {
				return self::instance( $addon_id )->get_plugin_basename();
			}

			$addon            = $this->get_addon( $addon_id );
			$premium_basename = "{$addon->slug}-premium/{$addon->slug}.php";

			if ( file_exists( fs_normalize_path( WP_PLUGIN_DIR . '/' . $premium_basename ) ) ) {
				return $premium_basename;
			}

			$all_plugins = $this->get_all_plugins();

			foreach ( $all_plugins as $basename => &$data ) {
				if ( $addon->slug === $data['slug'] ||
                    $addon->slug . '-premium' === $data['slug']
				) {
					return $basename;
				}
			}

			$free_basename = "{$addon->slug}/{$addon->slug}.php";

			return $free_basename;
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
			foreach ( self::$_instances as $instance ) {
				if ( $instance->is_addon() && is_object( $instance->_parent_plugin ) ) {
					if ( $this->_plugin->id == $instance->_parent_plugin->id ) {
						$installed_addons[] = $instance;
					}
				}
			}

			return $installed_addons;
		}

		/**
		 * Check if any add-ons of the plugin are installed.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.1.1
		 *
		 * @return bool
		 */
		function has_installed_addons() {
			if ( ! $this->has_addons() ) {
				return false;
			}

			foreach ( self::$_instances as $instance ) {
				if ( $instance->is_addon() && is_object( $instance->_parent_plugin ) ) {
					if ( $this->_plugin->id == $instance->_parent_plugin->id ) {
						return true;
					}
				}
			}

			return false;
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

		/**
		 * Deactivate add-on if it's premium only and the user does't have a valid license.
		 *
		 * @param bool $is_after_trial_cancel
		 *
		 * @return bool If add-on was deactivated.
		 */
		private function deactivate_premium_only_addon_without_license( $is_after_trial_cancel = false ) {
			if ( ! $this->has_free_plan() &&
			     ! $this->has_features_enabled_license() &&
			     ! $this->_has_premium_license()
			) {
				if ( $this->is_registered() ) {				
					// IF wrapper is turned off because activation_timestamp is currently only stored for plugins (not addons).
	//                if (empty($this->_storage->activation_timestamp) ||
	//                    (WP_FS__SCRIPT_START_TIME - $this->_storage->activation_timestamp) > 30
	//                ) {
					/**
					 * @todo When it's first fail, there's no reason to try and re-sync because the licenses were just synced after initial activation.
					 *
					 * Retry syncing the user add-on licenses.
					 */
					// Sync licenses.
					$this->_sync_licenses();
	//                }

					// Try to activate premium license.
					$this->_activate_license( true );
				}

				if ( ! $this->has_free_plan() &&
				     ! $this->has_features_enabled_license() &&
				     ! $this->_has_premium_license()
				) {
					// @todo Check if deactivate plugins also call the deactivation hook.

					$this->_parent->_admin_notices->add_sticky(
						sprintf(
							($is_after_trial_cancel ?
								$this->_parent->get_text_inline(
									'%s free trial was successfully cancelled. Since the add-on is premium only it was automatically deactivated. If you like to use it in the future, you\'ll have to purchase a license.',
									'addon-trial-cancelled-message'
								) :
								$this->_parent->get_text_inline(
									'%s is a premium only add-on. You have to purchase a license first before activating the plugin.',
									'addon-no-license-message'
								)
							),
							'<b>' . $this->_plugin->title . '</b>'
						) . ' ' . sprintf(
							'<a href="%s" aria-label="%s" class="button button-primary" style="margin-left: 10px; vertical-align: middle;">%s &nbsp;&#10140;</a>',
							$this->_parent->addon_url( $this->_slug ),
							esc_attr( sprintf( $this->_parent->get_text_inline( 'More information about %s', 'more-information-about-x' ), $this->_plugin->title ) ),
							$this->_parent->get_text_inline( 'Purchase License', 'purchase-license' )
						),
						'no_addon_license_' . $this->_slug,
						( $is_after_trial_cancel ? '' : $this->_parent->get_text_x_inline( 'Oops', 'exclamation', 'oops' ) . '...' ),
						( $is_after_trial_cancel ? 'success' : 'error' )
					);

					deactivate_plugins( array( $this->_plugin_basename ), true );

					return true;
				}
			}

			return false;
		}

		#endregion

		#----------------------------------------------------------------------------------
		#region Sandbox
		#----------------------------------------------------------------------------------

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
			FS_Plugin_Manager::instance( $this->_module_id )->update( $this->_plugin, true );
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

		#endregion

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
			if ( ! isset( $this->_is_anonymous ) ) {
				if ( ! isset( $this->_storage->is_anonymous ) ) {
					// Not skipped.
					$this->_is_anonymous = false;
				} else if ( is_bool( $this->_storage->is_anonymous ) ) {
					// For back compatibility, since the variable was boolean before.
					$this->_is_anonymous = $this->_storage->is_anonymous;

					// Upgrade stored data format to 1.1.3 format.
					$this->set_anonymous_mode( $this->_storage->is_anonymous );
				} else {
					// Version 1.1.3 and later.
					$this->_is_anonymous = $this->_storage->is_anonymous['is'];
				}
			}

			return $this->_is_anonymous;
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

		#----------------------------------------------------------------------------------
		#region Daily Sync Cron
		#----------------------------------------------------------------------------------

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 */
		private function run_manual_sync() {
			self::require_pluggable_essentials();

			if ( ! $this->is_user_admin() ) {
				return;
			}

			// Run manual sync.
			$this->_sync_cron();

			// Reschedule next cron to run 24 hours from now (performance optimization).
			$this->clear_sync_cron();

			$this->schedule_sync_cron( time() + WP_FS__TIME_24_HOURS_IN_SEC, false );
		}

		/**
		 * Data sync cron job. Replaces the background sync non blocking HTTP request
		 * that doesn't halt page loading.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 */
		function _sync_cron() {
			$this->_logger->entrance();

			// Store the last time data sync was executed.
			$this->_storage->sync_timestamp = time();

			// Check if API is temporary down.
			if ( FS_Api::is_temporary_down() ) {
				return;
			}

			// @todo Add logic that identifies API latency, and reschedule the next background sync randomly between 8-16 hours.

			if ( $this->is_registered() ) {
				if ( $this->has_paid_plan() ) {
					// Initiate background plan sync.
					$this->_sync_license( true );

					if ( $this->is_paying() ) {
						// Check for premium plugin updates.
						$this->check_updates( true );
					}
				} else {
					// Sync install (only if something changed locally).
					$this->sync_install();
				}
			}

			$this->do_action( 'after_sync_cron' );
		}

		/**
		 * Check if sync was executed in the last $period of seconds.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 *
		 * @param int $period In seconds
		 *
		 * @return bool
		 */
		private function is_sync_executed( $period = WP_FS__TIME_24_HOURS_IN_SEC ) {
			if ( ! isset( $this->_storage->sync_timestamp ) ) {
				return false;
			}

			return ( $this->_storage->sync_timestamp > ( WP_FS__SCRIPT_START_TIME - $period ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 *
		 * @return bool
		 */
		private function is_sync_cron_on() {
			/**
			 * @var object $sync_cron_data
			 */
			$sync_cron_data = $this->_storage->get( 'sync_cron', null );

			return ( ! is_null( $sync_cron_data ) && true === $sync_cron_data->on );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 *
		 * @param int  $start_at        Defaults to now.
		 * @param bool $randomize_start If true, schedule first job randomly during the next 12 hours. Otherwise,
		 *                              schedule job to start right away.
		 */
		private function schedule_sync_cron( $start_at = WP_FS__SCRIPT_START_TIME, $randomize_start = true ) {
			$this->_logger->entrance();

			if ( $randomize_start ) {
				// Schedule first sync with a random 12 hour time range from now.
				$start_at += rand( 0, ( WP_FS__TIME_24_HOURS_IN_SEC / 2 ) );
			}

			// Schedule daily WP cron.
			wp_schedule_event(
				$start_at,
				'daily',
				$this->get_action_tag( 'data_sync' )
			);

			$this->_storage->store( 'sync_cron', (object) array(
				'version'     => $this->get_plugin_version(),
				'sdk_version' => $this->version,
				'timestamp'   => WP_FS__SCRIPT_START_TIME,
				'on'          => true,
			) );
		}

		/**
		 * Add the actual sync function to the cron job hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 */
		private function hook_callback_to_sync_cron() {
			$this->add_action( 'data_sync', array( &$this, '_sync_cron' ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 */
		private function clear_sync_cron() {
			$this->_logger->entrance();

			if ( ! $this->is_sync_cron_on() ) {
				return;
			}

			$this->_storage->remove( 'sync_cron' );

			wp_clear_scheduled_hook( $this->get_action_tag( 'data_sync' ) );
		}

		/**
		 * Unix timestamp for next sync cron execution or false if not scheduled.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 *
		 * @return int|false
		 */
		function next_sync_cron() {
			$this->_logger->entrance();

			if ( ! $this->is_sync_cron_on() ) {
				return false;
			}

			return wp_next_scheduled( $this->get_action_tag( 'data_sync' ) );
		}

		/**
		 * Unix timestamp for previous sync cron execution or false if never executed.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 *
		 * @return int|false
		 */
		function last_sync_cron() {
			$this->_logger->entrance();

			return $this->_storage->get( 'sync_timestamp' );
		}

		#endregion Daily Sync Cron ------------------------------------------------------------------

		#----------------------------------------------------------------------------------
		#region Async Install Sync
		#----------------------------------------------------------------------------------

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 *
		 * @return bool
		 */
		private function is_install_sync_scheduled() {
			/**
			 * @var object $cron_data
			 */
			$cron_data = $this->_storage->get( 'install_sync_cron', null );

			return ( ! is_null( $cron_data ) && true === $cron_data->on );
		}

		/**
		 * Instead of running blocking install sync event, execute non blocking scheduled wp-cron.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 */
		private function schedule_install_sync() {
			$this->_logger->entrance();

			$this->clear_install_sync_cron();

			// Schedule immediate install sync.
			wp_schedule_single_event(
				WP_FS__SCRIPT_START_TIME,
				$this->get_action_tag( 'install_sync' )
			);

			$this->_storage->store( 'install_sync_cron', (object) array(
				'version'     => $this->get_plugin_version(),
				'sdk_version' => $this->version,
				'timestamp'   => WP_FS__SCRIPT_START_TIME,
				'on'          => true,
			) );
		}

		/**
		 * Unix timestamp for previous install sync cron execution or false if never executed.
		 *
		 * @todo   There's some very strange bug that $this->_storage->install_sync_timestamp value is not being
		 *         updated. But for sure the sync event is working.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 *
		 * @return int|false
		 */
		function last_install_sync() {
			$this->_logger->entrance();

			return $this->_storage->get( 'install_sync_timestamp' );
		}

		/**
		 * Unix timestamp for next install sync cron execution or false if not scheduled.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 *
		 * @return int|false
		 */
		function next_install_sync() {
			$this->_logger->entrance();

			if ( ! $this->is_install_sync_scheduled() ) {
				return false;
			}

			return wp_next_scheduled( $this->get_action_tag( 'install_sync' ) );
		}

		/**
		 * Add the actual install sync function to the cron job hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 */
		private function hook_callback_to_install_sync() {
			$this->add_action( 'install_sync', array( &$this, '_run_sync_install' ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 */
		private function clear_install_sync_cron() {
			$this->_logger->entrance();

			if ( ! $this->is_install_sync_scheduled() ) {
				return;
			}

			$this->_storage->remove( 'install_sync_cron' );

			wp_clear_scheduled_hook( $this->get_action_tag( 'install_sync' ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 */
		public function _run_sync_install() {
			$this->_logger->entrance();

			// Update last install sync timestamp.
			$this->_storage->install_sync_timestamp = time();

			$this->sync_install( array(), true );
		}

		#endregion Async Install Sync ------------------------------------------------------------------

		/**
		 * Show a notice that activation is currently pending.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @param bool|string $email
		 * @param bool        $is_pending_trial Since 1.2.1.5
		 */
		function _add_pending_activation_notice( $email = false, $is_pending_trial = false ) {
			if ( ! is_string( $email ) ) {
				$current_user = self::_get_current_wp_user();
				$email        = $current_user->user_email;
			}

			$this->_admin_notices->add_sticky(
				sprintf(
					$this->get_text_inline( 'You should receive an activation email for %s to your mailbox at %s. Please make sure you click the activation button in that email to %s.', 'pending-activation-message' ),
					'<b>' . $this->get_plugin_name() . '</b>',
					'<b>' . $email . '</b>',
					( $is_pending_trial ?
						$this->get_text_inline( 'start the trial', 'start-the-trial' ) :
						$this->get_text_inline( 'complete the install', 'complete-the-install' ) )
				),
				'activation_pending',
				'Thanks!'
			);
		}

		/**
		 * Check if currently in plugin activation.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.4
		 *
		 * @return bool
		 */
		function is_plugin_activation() {
			return get_option( 'fs_'
			                   . ( $this->is_plugin() ? '' : $this->_module_type . '_' )
			                   . "{$this->_slug}_activated", false );
		}

		/**
		 *
		 * NOTE: admin_menu action executed before admin_init.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 */
		function _admin_init_action() {
			/**
			 * Automatically redirect to connect/activation page after plugin activation.
			 *
			 * @since 1.1.7 Do NOT redirect to opt-in when running in network admin mode.
			 */
			if ( $this->is_plugin_activation() ) {
				delete_option( 'fs_'
				               . ( $this->is_plugin() ? '' : $this->_module_type . '_' )
				               . "{$this->_slug}_activated" );

				if ( ! function_exists( 'is_network_admin' ) || ! is_network_admin() ) {
					$this->_redirect_on_activation_hook();

					return;
				}
			}

			if ( fs_request_is_action( $this->get_unique_affix() . '_skip_activation' ) ) {
				check_admin_referer( $this->get_unique_affix() . '_skip_activation' );

				$this->skip_connection();

				fs_redirect( $this->get_after_activation_url( 'after_skip_url' ) );
			}

			if ( ! $this->is_addon() && ! $this->is_registered() && ! $this->is_anonymous() ) {
				if ( ! $this->is_pending_activation() ) {
					if ( ! $this->_menu->is_main_settings_page() ) {
						/**
						 * If a user visits any other admin page before activating the premium-only theme with a valid
						 * license, reactivate the previous theme.
						 *
						 * @author Leo Fajardo (@leorw)
						 * @since  1.2.2
						 */
						if ( $this->is_theme()
						     && $this->is_only_premium()
						     && ! $this->has_settings_menu()
						     && ! isset( $_REQUEST['fs_action'] )
						     && $this->can_activate_previous_theme()
						) {
							$this->activate_previous_theme();

							return;
						}

						if ( $this->is_plugin_new_install() || $this->is_only_premium() ) {
							// Show notice for new plugin installations.
							$this->_admin_notices->add(
								sprintf(
									$this->get_text_inline( 'You are just one step away - %s', 'you-are-step-away' ),
									sprintf( '<b><a href="%s">%s</a></b>',
										$this->get_activation_url(),
										sprintf( $this->get_text_x_inline( 'Complete "%s" Activation Now',
											'%s - plugin name. As complete "PluginX" activation now', 'activate-x-now' ), $this->get_plugin_name() )
									)
								),
								'',
								'update-nag'
							);
						} else {
							if ( ! isset( $this->_storage->sticky_optin_added ) ) {
								$this->_storage->sticky_optin_added = true;

								// Show notice for new plugin installations.
								$this->_admin_notices->add_sticky(
									sprintf(
										$this->get_text_inline( 'We made a few tweaks to the %s, %s', 'few-plugin-tweaks' ),
										$this->_module_type,
										sprintf( '<b><a href="%s">%s</a></b>',
											$this->get_activation_url(),
											sprintf( $this->get_text_inline( 'Opt in to make "%s" Better!', 'optin-x-now' ), $this->get_plugin_name() )
										)
									),
									'connect_account',
									'',
									'update-nag'
								);
							}

							if ( $this->has_filter( 'optin_pointer_element' ) ) {
								// Don't show admin nag if plugin update.
								wp_enqueue_script( 'wp-pointer' );
								wp_enqueue_style( 'wp-pointer' );

								$this->_enqueue_connect_essentials();

								add_action( 'admin_print_footer_scripts', array(
									$this,
									'_add_connect_pointer_script'
								) );
							}
						}
					}
				}

				if ( $this->is_theme() &&
				     $this->_menu->is_main_settings_page()
				) {
					$this->_show_theme_activation_optin_dialog();
				}
			}

			$this->_add_upgrade_action_link();
		}

		/**
		 * Enqueue connect requires scripts and styles.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.4
		 */
		function _enqueue_connect_essentials() {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'json2' );

			fs_enqueue_local_script( 'postmessage', 'nojquery.ba-postmessage.min.js' );
			fs_enqueue_local_script( 'fs-postmessage', 'postmessage.js' );

			fs_enqueue_local_style( 'fs_connect', '/admin/connect.css' );
		}

		/**
		 * Add connect / opt-in pointer.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.4
		 */
		function _add_connect_pointer_script() {
			$vars            = array( 'id' => $this->_module_id );
			$pointer_content = fs_get_template( 'connect.php', $vars );
			?>
			<script type="text/javascript">// <![CDATA[
				jQuery(document).ready(function ($) {
					if ('undefined' !== typeof(jQuery().pointer)) {

						var element = <?php echo $this->apply_filters( 'optin_pointer_element', '$("#non_existing_element");' ) ?>;

						if (element.length > 0) {
							var optin = $(element).pointer($.extend(true, {}, {
								content     : <?php echo json_encode( $pointer_content ) ?>,
								position    : {
									edge : 'left',
									align: 'center'
								},
								buttons     : function () {
									// Don't show pointer buttons.
									return '';
								},
								pointerWidth: 482
							}, <?php echo $this->apply_filters( 'optin_pointer_options_json', '{}' ) ?>));

							<?php
							echo $this->apply_filters( 'optin_pointer_execute', "

							optin.pointer('open');

							// Tag the opt-in pointer with custom class.
							$('.wp-pointer #fs_connect')
								.parents('.wp-pointer.wp-pointer-top')
								.addClass('fs-opt-in-pointer');

							", 'element', 'optin' ) ?>
						}
					}
				});
				// ]]></script>
			<?php
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
			return fs_is_plugin_page( $this->_menu->get_raw_slug() ) ||
			       fs_is_plugin_page( $this->_slug );
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
			self::_delete_site_by_slug( $this->_slug, $this->_module_type, $store );
		}

		/**
		 * Delete site install from Database.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @param string $slug
		 * @param string $module_type
		 * @param bool   $store
		 */
		static function _delete_site_by_slug($slug, $module_type, $store = true ) {
			$sites = self::get_all_sites( $module_type );

			if ( isset( $sites[ $slug ] ) ) {
				unset( $sites[ $slug ] );
			}

			self::set_account_option_by_module( $module_type, 'sites', $sites, $store );
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

			$plans = self::get_all_plans( $this->_module_type );

			unset( $plans[ $this->_slug ] );

			$this->set_account_option( 'plans', $plans, $store );
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

			$all_licenses = self::get_all_licenses( $this->_module_type );

			if ( ! is_string( $plugin_slug ) ) {
				$plugin_slug = $this->_slug;
			}

			unset( $all_licenses[ $plugin_slug ] );

			$this->set_account_option( 'licenses', $all_licenses, $store );
		}

		/**
		 * Check if Freemius was added on new plugin installation.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.5
		 *
		 * @return bool
		 */
		function is_plugin_new_install() {
			return isset( $this->_storage->is_plugin_new_install ) &&
			       $this->_storage->is_plugin_new_install;
		}

		/**
		 * Check if it's the first plugin release that is running Freemius.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 *
		 * @return bool
		 */
		function is_first_freemius_powered_version() {
			return empty( $this->_storage->plugin_last_version );
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @return bool|string
		 */
		private function get_previous_theme_slug() {
			return isset( $this->_storage->previous_theme ) ?
				$this->_storage->previous_theme :
				false;
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @return string
		 */
		private function can_activate_previous_theme() {
			$slug = $this->get_previous_theme_slug();
			if ( false !== $slug && current_user_can( 'switch_themes' ) ) {
				$theme_instance = wp_get_theme( $slug );

				return $theme_instance->exists();
			}

			return false;
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @return string
		 */
		private function activate_previous_theme() {
			switch_theme( $this->get_previous_theme_slug() );
			unset( $this->_storage->previous_theme );

			global $pagenow;
			if ( 'themes.php' === $pagenow ) {
				/**
				 * Refresh the active theme information.
				 *
				 * @author Leo Fajardo (@leorw)
				 * @since  1.2.2
				 */
				fs_redirect( admin_url( $pagenow ) );
			}
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @return string
		 */
		function get_previous_theme_activation_url() {
			if ( ! $this->can_activate_previous_theme() ) {
				return '';
			}

			/**
			 * Activation URL
			 *
			 * @author Leo Fajardo (@leorw)
			 * @since  1.2.2
			 */
			return wp_nonce_url(
				admin_url( 'themes.php?action=activate&stylesheet=' . urlencode( $this->get_previous_theme_slug() ) ),
				'switch-theme_' . $this->get_previous_theme_slug()
			);
		}

		/**
		 * Saves the slug of the previous theme if it still exists so that it can be used by the logic in the opt-in
		 * form that decides whether to add a close button to the opt-in dialog or not. So after a premium-only theme is
		 * activated, the close button will appear and will reactivate the previous theme if clicked. If the previous
		 * theme doesn't exist, then there will be no close button.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @param  string        $slug_or_name Old theme's slug or name.
		 * @param  bool|WP_Theme $old_theme    WP_Theme instance of the old theme if it still exists.
		 */
		function _activate_theme_event_hook( $slug_or_name, $old_theme = false ) {
			$this->_storage->previous_theme = ( false !== $old_theme ) ?
				$old_theme->get_stylesheet() :
				$slug_or_name;

			$this->_activate_plugin_event_hook();
		}

		/**
		 * Plugin activated hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @uses   FS_Api
		 */
		function _activate_plugin_event_hook() {
			$this->_logger->entrance( 'slug = ' . $this->_slug );

			if ( ! $this->is_user_admin() ) {
				return;
			}

			$this->unregister_uninstall_hook();

			// Clear API cache on activation.
			FS_Api::clear_cache();

			$is_premium_version_activation = ( current_filter() !== ( 'activate_' . $this->_free_plugin_basename ) );

			$this->_logger->info( 'Activating ' . ( $is_premium_version_activation ? 'premium' : 'free' ) . ' plugin version.' );

			// 1. If running in the activation of the FREE module, get the basename of the PREMIUM.
			// 2. If running in the activation of the PREMIUM module, get the basename of the FREE.
			$other_version_basename = $is_premium_version_activation ?
				$this->_free_plugin_basename :
				$this->premium_plugin_basename();

			/**
			 * If the other module version is activate, deactivate it.
			 *
			 * @author Leo Fajardo (@leorw)
			 * @since  1.2.2
			 */
			if ( is_plugin_active( $other_version_basename ) ) {
				deactivate_plugins( $other_version_basename );
			}

			if ( $this->is_registered() ) {
				if ( $is_premium_version_activation ) {
					$this->reconnect_locally();
				}


				// Schedule re-activation event and sync.
//				$this->sync_install( array(), true );
				$this->schedule_install_sync();

				// If activating the premium module version, add an admin notice to congratulate for an upgrade completion.
				if ( $is_premium_version_activation ) {
					$this->_admin_notices->add(
						sprintf( $this->get_text_inline( 'The upgrade of %s was successfully completed.', 'successful-version-upgrade-message' ), sprintf( '<b>%s</b>', $this->_plugin->title ) ),
						$this->get_text_x_inline( 'W00t',
							'Used to express elation, enthusiasm, or triumph (especially in electronic communication).', 'woot' ) . '!'
					);
				}
			} else if ( $this->is_anonymous() ) {
				/**
				 * Reset "skipped" click cache on the following:
				 *  1. Freemius DEV mode.
				 *  2. WordPress DEBUG mode.
				 *  3. If a plugin and the user skipped the exact same version before.
				 *
				 * @since 1.2.2.7 Ulrich Pogson (@grapplerulrich) asked to not reset the SKIPPED flag if the exact same THEME version was activated before unless the developer is running with WP_DEBUG on, or Freemius debug mode on (WP_FS__DEV_MODE).
				 *
				 * @todo 4. If explicitly asked to retry after every activation.
				 */
				if ( WP_FS__DEV_MODE ||
				     (
				     	( $this->is_plugin() || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) &&
				        $this->get_plugin_version() == $this->_storage->is_anonymous['version']
				     )
				) {
					$this->reset_anonymous_mode();
				}
			}

			if ( ! isset( $this->_storage->is_plugin_new_install ) ) {
				/**
				 * If no previous version of plugin's version exist, it means that it's either
				 * the first time that the plugin installed on the site, or the plugin was installed
				 * before but didn't have Freemius integrated.
				 *
				 * Since register_activation_hook() do NOT fires on updates since 3.1, and only fires
				 * on manual activation via the dashboard, is_plugin_activation() is TRUE
				 * only after immediate activation.
				 *
				 * @since 1.1.4
				 * @link  https://make.wordpress.org/core/2010/10/27/plugin-activation-hooks-no-longer-fire-for-updates/
				 */
				$this->_storage->is_plugin_new_install = empty( $this->_storage->plugin_last_version );
			}

			if ( ! $this->_anonymous_mode &&
			     $this->has_api_connectivity( WP_FS__DEV_MODE ) &&
			     ! $this->_isAutoInstall
			) {
				// Store hint that the plugin was just activated to enable auto-redirection to settings.
				add_option( 'fs_'
				            . ( $this->is_plugin() ? '' : $this->_module_type . '_' )
				            . "{$this->_slug}_activated", true );
			}

			/**
			 * Activation hook is executed after the plugin's main file is loaded, therefore,
			 * after the plugin was loaded. The logic is located at activate_plugin()
			 * ./wp-admin/includes/plugin.php.
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since  1.1.9
			 */
			$this->_storage->was_plugin_loaded = true;
		}

		/**
		 * Delete account.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @param bool $check_user Enforce checking if user have plugins activation privileges.
		 */
		function delete_account_event( $check_user = true ) {
			$this->_logger->entrance( 'slug = ' . $this->_slug );

			if ( $check_user && ! $this->is_user_admin() ) {
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

			/**
			 * IMPORTANT:
			 *  Clear crons must be executed before clearing all storage.
			 *  Otherwise, the cron will not be cleared.
			 */
			$this->clear_sync_cron();
			$this->clear_install_sync_cron();

			// Clear all storage data.
			$this->_storage->clear_all( true, array(
				'connectivity_test',
				'is_on',
			) );

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
			if ( isset( $this->_storage->sticky_optin_added ) ) {
				unset( $this->_storage->sticky_optin_added );
			}

			if ( ! isset( $this->_storage->is_plugin_new_install ) ) {
				// Remember that plugin was already installed.
				$this->_storage->is_plugin_new_install = false;
			}

			// Hook to plugin uninstall.
			register_uninstall_hook( $this->_plugin_main_file_path, array( 'Freemius', '_uninstall_plugin_hook' ) );

			$this->clear_module_main_file_cache();
			$this->clear_sync_cron();
			$this->clear_install_sync_cron();

			if ( $this->is_registered() ) {
				// Send deactivation event.
				$this->sync_install( array(
					'is_active' => false,
				) );
			} else {
				if ( ! $this->has_api_connectivity() ) {
					// Reset connectivity test cache.
					unset( $this->_storage->connectivity_test );
				}
			}

			// Clear API cache on deactivation.
			FS_Api::clear_cache();

			$this->remove_sdk_reference();
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.6
		 */
		private function remove_sdk_reference() {
			global $fs_active_plugins;

			foreach ( $fs_active_plugins->plugins as $sdk_path => &$data ) {
				if ( $this->_plugin_basename == $data->plugin_path ) {
					unset( $fs_active_plugins->plugins[ $sdk_path ] );
					break;
				}
			}

			fs_fallback_to_newest_active_sdk();
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @param bool $is_anonymous
		 */
		private function set_anonymous_mode( $is_anonymous = true ) {
			// Store information regarding skip to try and opt-in the user
			// again in the future.
			$this->_storage->is_anonymous = array(
				'is'        => $is_anonymous,
				'timestamp' => WP_FS__SCRIPT_START_TIME,
				'version'   => $this->get_plugin_version(),
			);

			// Update anonymous mode cache.
			$this->_is_anonymous = $is_anonymous;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 */
		private function reset_anonymous_mode() {
			unset( $this->_storage->is_anonymous );

			/**
			 * Ensure that this field is also "false", otherwise, if the current module's type is "theme" and the module
			 * has no menus, the opt-in popup will not be shown immediately (in this case, the user will have to click
			 * on the admin notice that contains the opt-in link in order to trigger the opt-in popup).
			 *
			 * @author Leo Fajardo (@leorw)
			 * @since  1.2.2
			 */
			unset( $this->_is_anonymous );
		}

		/**
		 * Clears the anonymous mode and redirects to the opt-in screen.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7
		 */
		function connect_again() {
			if ( ! $this->is_anonymous() ) {
				return;
			}

			$this->reset_anonymous_mode();

			fs_redirect( $this->get_activation_url() );
		}

		/**
		 * Skip account connect, and set anonymous mode.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.1
		 */
		private function skip_connection() {
			$this->_logger->entrance();

			$this->_admin_notices->remove_sticky( 'connect_account' );

			$this->set_anonymous_mode();

			// Send anonymous skip event.
			// No user identified info nor any tracking will be sent after the user skips the opt-in.
			$this->get_api_plugin_scope()->call( 'skip.json', 'put', array(
				'uid' => $this->get_anonymous_id(),
			) );
		}

		/**
		 * Plugin version update hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 */
		private function update_plugin_version_event() {
			$this->_logger->entrance();

			if ( ! $this->is_registered() ) {
				return;
			}

			$this->schedule_install_sync();
//			$this->sync_install( array(), true );
		}

		/**
		 * Return a list of modified plugins since the last sync.
		 *
		 * Note:
		 *  There's no point to store a plugins counter since even if the number of
		 *  plugins didn't change, we still need to check if the versions are all the
		 *  same and the activity state is similar.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.8
		 *
		 * @return array|false
		 */
		private function get_plugins_data_for_api() {
			// Alias.
			$option_name = 'all_plugins';

			$all_cached_plugins = self::$_accounts->get_option( $option_name );

			if ( ! is_object( $all_cached_plugins ) ) {
				$all_cached_plugins = (object) array(
					'timestamp' => '',
					'md5'       => '',
					'plugins'   => array(),
				);
			}

			$time = time();

			if ( ! empty( $all_cached_plugins->timestamp ) &&
			     ( $time - $all_cached_plugins->timestamp ) < WP_FS__TIME_5_MIN_IN_SEC
			) {
				// Don't send plugin updates if last update was in the past 5 min.
				return false;
			}

			// Write timestamp to lock the logic.
			$all_cached_plugins->timestamp = $time;
			self::$_accounts->set_option( $option_name, $all_cached_plugins, true );

			// Reload options from DB.
			self::$_accounts->load( true );
			$all_cached_plugins = self::$_accounts->get_option( $option_name );

			if ( $time != $all_cached_plugins->timestamp ) {
				// If timestamp is different, then another thread captured the lock.
				return false;
			}

			// Check if there's a change in plugins.
			$all_plugins = self::get_all_plugins();

			// Check if plugins changed.
			ksort( $all_plugins );

			$plugins_signature = '';
			foreach ( $all_plugins as $basename => $data ) {
				$plugins_signature .= $data['slug'] . ',' .
				                      $data['Version'] . ',' .
				                      ( $data['is_active'] ? '1' : '0' ) . ';';
			}

			// Check if plugins status changed (version or active/inactive).
			$plugins_changed = ( $all_cached_plugins->md5 !== md5( $plugins_signature ) );

			$plugins_update_data = array();

			if ( $plugins_changed ) {
				// Change in plugins, report changes.

				// Update existing plugins info.
				foreach ( $all_cached_plugins->plugins as $basename => $data ) {
					if ( ! isset( $all_plugins[ $basename ] ) ) {
						// Plugin uninstalled.
						$uninstalled_plugin_data                   = $data;
						$uninstalled_plugin_data['is_active']      = false;
						$uninstalled_plugin_data['is_uninstalled'] = true;
						$plugins_update_data[]                     = $uninstalled_plugin_data;

						unset( $all_plugins[ $basename ] );
						unset( $all_cached_plugins->plugins[ $basename ] );
					} else if ( $data['is_active'] !== $all_plugins[ $basename ]['is_active'] ||
					            $data['version'] !== $all_plugins[ $basename ]['Version']
					) {
						// Plugin activated or deactivated, or version changed.
						$all_cached_plugins->plugins[ $basename ]['is_active'] = $all_plugins[ $basename ]['is_active'];
						$all_cached_plugins->plugins[ $basename ]['version']   = $all_plugins[ $basename ]['Version'];

						$plugins_update_data[] = $all_cached_plugins->plugins[ $basename ];
					}
				}

				// Find new plugins that weren't yet seen before.
				foreach ( $all_plugins as $basename => $data ) {
					if ( ! isset( $all_cached_plugins->plugins[ $basename ] ) ) {
						// New plugin.
						$new_plugin = array(
							'slug'           => $data['slug'],
							'version'        => $data['Version'],
							'title'          => $data['Name'],
							'is_active'      => $data['is_active'],
							'is_uninstalled' => false,
						);

						$plugins_update_data[]                    = $new_plugin;
						$all_cached_plugins->plugins[ $basename ] = $new_plugin;
					}
				}

				$all_cached_plugins->md5       = md5( $plugins_signature );
				$all_cached_plugins->timestamp = $time;
				self::$_accounts->set_option( $option_name, $all_cached_plugins, true );
			}

			return $plugins_update_data;
		}

		/**
		 * Return a list of modified themes since the last sync.
		 *
		 * Note:
		 *  There's no point to store a themes counter since even if the number of
		 *  themes didn't change, we still need to check if the versions are all the
		 *  same and the activity state is similar.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.8
		 *
		 * @return array|false
		 */
		private function get_themes_data_for_api() {
			// Alias.
			$option_name = 'all_themes';

			$all_cached_themes = self::$_accounts->get_option( $option_name );

			if ( ! is_object( $all_cached_themes ) ) {
				$all_cached_themes = (object) array(
					'timestamp' => '',
					'md5'       => '',
					'themes'    => array(),
				);
			}

			$time = time();

			if ( ! empty( $all_cached_themes->timestamp ) &&
			     ( $time - $all_cached_themes->timestamp ) < WP_FS__TIME_5_MIN_IN_SEC
			) {
				// Don't send theme updates if last update was in the past 5 min.
				return false;
			}

			// Write timestamp to lock the logic.
			$all_cached_themes->timestamp = $time;
			self::$_accounts->set_option( $option_name, $all_cached_themes, true );

			// Reload options from DB.
			self::$_accounts->load( true );
			$all_cached_themes = self::$_accounts->get_option( $option_name );

			if ( $time != $all_cached_themes->timestamp ) {
				// If timestamp is different, then another thread captured the lock.
				return false;
			}

			// Get active theme.
			$active_theme            = wp_get_theme();
			$active_theme_stylesheet = $active_theme->get_stylesheet();

			// Check if there's a change in themes.
			$all_themes = wp_get_themes();

			// Check if themes changed.
			ksort( $all_themes );

			$themes_signature = '';
			foreach ( $all_themes as $slug => $data ) {
				$is_active = ( $slug === $active_theme_stylesheet );
				$themes_signature .= $slug . ',' .
				                     $data->version . ',' .
				                     ( $is_active ? '1' : '0' ) . ';';
			}

			// Check if themes status changed (version or active/inactive).
			$themes_changed = ( $all_cached_themes->md5 !== md5( $themes_signature ) );

			$themes_update_data = array();

			if ( $themes_changed ) {
				// Change in themes, report changes.

				// Update existing themes info.
				foreach ( $all_cached_themes->themes as $slug => $data ) {
					$is_active = ( $slug === $active_theme_stylesheet );

					if ( ! isset( $all_themes[ $slug ] ) ) {
						// Plugin uninstalled.
						$uninstalled_theme_data                   = $data;
						$uninstalled_theme_data['is_active']      = false;
						$uninstalled_theme_data['is_uninstalled'] = true;
						$themes_update_data[]                     = $uninstalled_theme_data;

						unset( $all_themes[ $slug ] );
						unset( $all_cached_themes->themes[ $slug ] );
					} else if ( $data['is_active'] !== $is_active ||
					            $data['version'] !== $all_themes[ $slug ]->version
					) {
						// Plugin activated or deactivated, or version changed.

						$all_cached_themes->themes[ $slug ]['is_active'] = $is_active;
						$all_cached_themes->themes[ $slug ]['version']   = $all_themes[ $slug ]->version;

						$themes_update_data[] = $all_cached_themes->themes[ $slug ];
					}
				}

				// Find new themes that weren't yet seen before.
				foreach ( $all_themes as $slug => $data ) {
					if ( ! isset( $all_cached_themes->themes[ $slug ] ) ) {
						$is_active = ( $slug === $active_theme_stylesheet );

						// New plugin.
						$new_plugin = array(
							'slug'           => $slug,
							'version'        => $data->version,
							'title'          => $data->name,
							'is_active'      => $is_active,
							'is_uninstalled' => false,
						);

						$themes_update_data[]               = $new_plugin;
						$all_cached_themes->themes[ $slug ] = $new_plugin;
					}
				}

				$all_cached_themes->md5       = md5( $themes_signature );
				$all_cached_themes->timestamp = time();
				self::$_accounts->set_option( $option_name, $all_cached_themes, true );
			}

			return $themes_update_data;
		}

		/**
		 * Update install details.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.2
		 *
		 * @param string[] string           $override
		 * @param bool     $include_plugins Since 1.1.8 by default include plugin changes.
		 * @param bool     $include_themes  Since 1.1.8 by default include plugin changes.
		 *
		 * @return array
		 */
		private function get_install_data_for_api(
			array $override,
			$include_plugins = true,
			$include_themes = true
		) {
			/**
			 * @since 1.1.8 Also send plugin updates.
			 */
			if ( $include_plugins && ! isset( $override['plugins'] ) ) {
				$plugins = $this->get_plugins_data_for_api();
				if ( ! empty( $plugins ) ) {
					$override['plugins'] = $plugins;
				}
			}
			/**
			 * @since 1.1.8 Also send themes updates.
			 */
			if ( $include_themes && ! isset( $override['themes'] ) ) {
				$themes = $this->get_themes_data_for_api();
				if ( ! empty( $themes ) ) {
					$override['themes'] = $themes;
				}
			}

			return array_merge( array(
				'version'                      => $this->get_plugin_version(),
				'is_premium'                   => $this->is_premium(),
				'language'                     => get_bloginfo( 'language' ),
				'charset'                      => get_bloginfo( 'charset' ),
				'platform_version'             => get_bloginfo( 'version' ),
				'sdk_version'                  => $this->version,
				'programming_language_version' => phpversion(),
				'title'                        => get_bloginfo( 'name' ),
				'url'                          => get_site_url(),
				// Special params.
				'is_active'                    => true,
				'is_disconnected'              => $this->is_tracking_prohibited(),
				'is_uninstalled'               => false,
			), $override );
		}

		/**
		 * Update install only if changed.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param string[] string $override
		 * @param bool     $flush
		 *
		 * @return false|object|string
		 */
		private function send_install_update( $override = array(), $flush = false ) {
			$this->_logger->entrance();

			$check_properties = $this->get_install_data_for_api( $override );

			if ( $flush ) {
				$params = $check_properties;
			} else {
				$params           = array();
				$special          = array();
				$special_override = false;

				foreach ( $check_properties as $p => $v ) {
					if ( property_exists( $this->_site, $p ) ) {
						if ( ( is_bool( $this->_site->{$p} ) || ! empty( $this->_site->{$p} ) ) &&
						     $this->_site->{$p} != $v
						) {
							$this->_site->{$p} = $v;
							$params[ $p ]      = $v;
						}
					} else {
						$special[ $p ] = $v;

						if ( isset( $override[ $p ] ) ||
						     'plugins' === $p ||
						     'themes' === $p
						) {
							$special_override = true;
						}
					}
				}

				if ( $special_override || 0 < count( $params ) ) {
					// Add special params only if has at least one
					// standard param, or if explicitly requested to
					// override a special param or a param which is not exist
					// in the install object.
					$params = array_merge( $params, $special );
				}
			}

			if ( 0 < count( $params ) ) {
				// Update last install sync timestamp.
				$this->_storage->install_sync_timestamp = time();

				$params['uid'] = $this->get_anonymous_id();

				// Send updated values to FS.
				$site = $this->get_api_site_scope()->call( '/', 'put', $params );

				if ( $this->is_api_result_entity( $site ) ) {
					// I successfully sent install update, clear scheduled sync if exist.
					$this->clear_install_sync_cron();
				}

				return $site;
			}

			return false;
		}

		/**
		 * Update install only if changed.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param string[] string $override
		 * @param bool     $flush
		 */
		private function sync_install( $override = array(), $flush = false ) {
			$this->_logger->entrance();

			$site = $this->send_install_update( $override, $flush );

			if ( false === $site ) {
				// No sync required.
				return;
			}

			if ( ! $this->is_api_result_entity( $site ) ) {
				// Failed to sync, don't update locally.
				return;
			}

			$plan              = $this->get_plan();
			$this->_site       = new FS_Site( $site );
			$this->_site->plan = $plan;

			$this->_store_site( true );
		}

		/**
		 * Track install's custom event.
		 *
		 * IMPORTANT:
		 *      Custom event tracking is currently only supported for specific clients.
		 *      If you are not one of them, please don't use this method. If you will,
		 *      the API will simply ignore your request based on the plugin ID.
		 *
		 * Need custom tracking for your plugin or theme?
		 *      If you are interested in custom event tracking please contact yo@freemius.com
		 *      for further details.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1
		 *
		 * @param string $name       Event name.
		 * @param array  $properties Associative key/value array with primitive values only
		 * @param bool   $process_at A valid future date-time in the following format Y-m-d H:i:s.
		 * @param bool   $once       If true, event will be tracked only once. IMPORTANT: Still trigger the API call.
		 *
		 * @return object|false Event data or FALSE on failure.
		 *
		 * @throws \Freemius_InvalidArgumentException
		 */
		public function track_event( $name, $properties = array(), $process_at = false, $once = false ) {
			$this->_logger->entrance( http_build_query( array( 'name' => $name, 'once' => $once ) ) );

			if ( ! $this->is_registered() ) {
				return false;
			}

			$event = array( 'type' => $name );

			if ( is_numeric( $process_at ) && $process_at > time() ) {
				$event['process_at'] = $process_at;
			}

			if ( $once ) {
				$event['once'] = true;
			}

			if ( ! empty( $properties ) ) {
				// Verify associative array values are primitive.
				foreach ( $properties as $k => $v ) {
					if ( ! is_scalar( $v ) ) {
						throw new Freemius_InvalidArgumentException( 'The $properties argument must be an associative key/value array with primitive values only.' );
					}
				}

				$event['properties'] = $properties;
			}

			$result = $this->get_api_site_scope()->call( 'events.json', 'post', $event );

			return $this->is_api_error( $result ) ?
				false :
				$result;
		}

		/**
		 * Track install's custom event only once, but it still triggers the API call.
		 *
		 * IMPORTANT:
		 *      Custom event tracking is currently only supported for specific clients.
		 *      If you are not one of them, please don't use this method. If you will,
		 *      the API will simply ignore your request based on the plugin ID.
		 *
		 * Need custom tracking for your plugin or theme?
		 *      If you are interested in custom event tracking please contact yo@freemius.com
		 *      for further details.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1
		 *
		 * @param string $name       Event name.
		 * @param array  $properties Associative key/value array with primitive values only
		 * @param bool   $process_at A valid future date-time in the following format Y-m-d H:i:s.
		 *
		 * @return object|false Event data or FALSE on failure.
		 *
		 * @throws \Freemius_InvalidArgumentException
		 *
		 * @user   Freemius::track_event()
		 */
		public function track_event_once( $name, $properties = array(), $process_at = false ) {
			return $this->track_event( $name, $properties, $process_at, true );
		}

		/**
		 * Plugin uninstall hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
		 * @param bool $check_user Enforce checking if user have plugins activation privileges.
		 */
		function _uninstall_plugin_event( $check_user = true ) {
			$this->_logger->entrance( 'slug = ' . $this->_slug );

			if ( $check_user && ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$params           = array();
			$uninstall_reason = null;
			if ( isset( $this->_storage->uninstall_reason ) ) {
				$uninstall_reason      = $this->_storage->uninstall_reason;
				$params['reason_id']   = $uninstall_reason->id;
				$params['reason_info'] = $uninstall_reason->info;
			}

			if ( ! $this->is_registered() ) {
				// Send anonymous uninstall event only if user submitted a feedback.
				if ( isset( $uninstall_reason ) ) {
					if ( isset( $uninstall_reason->is_anonymous ) && ! $uninstall_reason->is_anonymous ) {
						$this->opt_in( false, false, false, false, true );
					} else {
						$params['uid'] = $this->get_anonymous_id();
						$this->get_api_plugin_scope()->call( 'uninstall.json', 'put', $params );
					}
				}
			} else {
				// Send uninstall event.
				$this->send_install_update( array_merge( $params, array(
					'is_active'      => false,
					'is_uninstalled' => true,
				) ) );
			}

			// @todo Decide if we want to delete plugin information from db.
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.1
		 *
		 * @return string
		 */
		function premium_plugin_basename() {
			return "{$this->_slug}-premium/" . basename( $this->_free_plugin_basename );
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

			define( 'WP_FS__UNINSTALL_MODE', true );

			$fs = self::get_instance_by_file( $plugin_file );

			if ( is_object( $fs ) ) {
				self::require_plugin_essentials();

				if ( is_plugin_active( $fs->_free_plugin_basename ) ||
				     is_plugin_active( $fs->premium_plugin_basename() )
				) {
					// Deleting Free or Premium plugin version while the other version still installed.
					return;
				}

				$fs->_uninstall_plugin_event();

				$fs->do_action( 'after_uninstall' );
			}
		}

		#----------------------------------------------------------------------------------
		#region Plugin Information
		#----------------------------------------------------------------------------------

		/**
		 * Load WordPress core plugin.php essential module.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.1
		 */
		private static function require_plugin_essentials() {
			if ( ! function_exists( 'get_plugins' ) ) {
				self::$_static_logger->log( 'Including wp-admin/includes/plugin.php...' );

				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
		}

		/**
		 * Load WordPress core pluggable.php module.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.2
		 */
		private static function require_pluggable_essentials() {
			if ( ! function_exists( 'wp_get_current_user' ) ) {
				require_once ABSPATH . 'wp-includes/pluggable.php';
			}
		}

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
				self::require_plugin_essentials();

				if ( $this->is_plugin() ) {
					/**
					 * @author Vova Feldman (@svovaf)
					 * @since  1.2.0 When using get_plugin_data() do NOT translate plugin data.
					 *
					 * @link   https://github.com/Freemius/wordpress-sdk/issues/77
					 */
					$plugin_data = get_plugin_data(
						$this->_plugin_main_file_path,
						false,
						false
					);
				} else {
					$theme_data = wp_get_theme();

					$plugin_data = array(
						'Name'        => $theme_data->get( 'Name' ),
						'Version'     => $theme_data->get( 'Version' ),
						'Author'      => $theme_data->get( 'Author' ),
						'Description' => $theme_data->get( 'Description' ),
						'PluginURI'   => $theme_data->get( 'ThemeURI' ),
					);
				}

				$this->_plugin_data = $plugin_data;
			}

			return $this->_plugin_data;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 * @since  1.2.2.5 If slug not set load slug by module ID.
		 *
		 * @return string Plugin slug.
		 */
		function get_slug() {
			if ( ! isset( $this->_slug ) ) {
				$id_slug_type_path_map = self::$_accounts->get_option( 'id_slug_type_path_map', array() );
				$this->_slug           = $id_slug_type_path_map[ $this->_module_id ]['slug'];
			}

			return $this->_slug;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 *
		 * @return string Plugin slug.
		 */
		function get_target_folder_name() {
			return $this->_slug . ( $this->can_use_premium_code() ? '-premium' : '' );
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
		 * @since  1.2.1.5
		 *
		 * @return string Freemius SDK version
		 */
		function get_sdk_version() {
			return $this->version;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 *
		 * @return number Parent plugin ID (if parent exist).
		 */
		function get_parent_id() {
			return $this->is_addon() ?
				$this->get_parent_instance()->get_id() :
				$this->_plugin->id;
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
		 * @since  1.1.1
		 *
		 * @return bool
		 */
		function has_secret_key() {
			return ! empty( $this->_plugin->secret_key );
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

				// Check if plugin name contains "(Premium)" suffix and remove it.
				$suffix     = ' (premium)';
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

			return $this->apply_filters( 'plugin_version', $plugin_data['Version'] );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 *
		 * @return string
		 */
		function get_plugin_title() {
			$this->_logger->entrance();

			$title = $this->_plugin->title;

			return $this->apply_filters( 'plugin_title', $title );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since 1.2.2.7
		 *
		 * @param bool $lowercase
		 *
		 * @return string
		 */
		function get_module_label( $lowercase = false ) {
			$label = $this->is_addon() ?
				$this->get_text_inline( 'Add-On', 'addon' ) :
				( $this->is_plugin() ?
					$this->get_text_inline( 'Plugin', 'plugin' ) :
					$this->get_text_inline( 'Theme', 'theme' ) );

			if ( $lowercase ) {
				$label = strtolower( $label );
			}

			return $label;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @return string
		 */
		function get_plugin_basename() {
			if ( ! isset( $this->_plugin_basename ) ) {
				if ( $this->is_plugin() ) {
					$this->_plugin_basename = plugin_basename( $this->_plugin_main_file_path );
				} else {
					$this->_plugin_basename = basename( dirname( $this->_plugin_main_file_path ) );
				}
			}

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
		private static function find_slug_by_basename( $plugin_base_name ) {
			$file_slug_map = self::$_accounts->get_option( 'file_slug_map', array() );

			if ( ! array( $file_slug_map ) || ! isset( $file_slug_map[ $plugin_base_name ] ) ) {
				return false;
			}

			return $file_slug_map[ $plugin_base_name ];
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
		 * @param string $module_type
		 *
		 * @return FS_Site[]
		 */
		private static function get_all_sites( $module_type = WP_FS__MODULE_TYPE_PLUGIN ) {
			$sites = self::get_account_option( 'sites', $module_type );

			if ( ! is_array( $sites ) ) {
				$sites = array();
			}

			return $sites;
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 *
		 * @since  1.2.2
		 *
		 * @param string $option_name
		 * @param string $module_type
		 *
		 * @return mixed
		 */
		private static function get_account_option( $option_name, $module_type ) {
			if ( WP_FS__MODULE_TYPE_PLUGIN !== $module_type ) {
				$option_name = $module_type . '_' . $option_name;
			}

			return self::$_accounts->get_option( $option_name, array() );
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 *
		 * @since  1.2.2
		 *
		 * @param string $option_name
		 * @param mixed  $option_value
		 * @param bool   $store
		 */
		private function set_account_option( $option_name, $option_value, $store ) {
			self::set_account_option_by_module(
				$this->_module_type,
				$option_name,
				$option_value,
				$store
			);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 *
		 * @since  1.2.2.7
		 *
		 * @param string $module_type
		 * @param string $option_name
		 * @param mixed  $option_value
		 * @param bool   $store
		 */
		private static function set_account_option_by_module( $module_type, $option_name, $option_value, $store ) {
			if ( WP_FS__MODULE_TYPE_PLUGIN != $module_type ) {
				$option_name = $module_type . '_' . $option_name;
			}

			self::$_accounts->set_option( $option_name, $option_value, $store );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param string $module_type
		 *
		 * @return FS_Plugin_License[]
		 */
		private static function get_all_licenses( $module_type = WP_FS__MODULE_TYPE_PLUGIN ) {
			$licenses = self::get_account_option( 'licenses', $module_type );

			if ( ! is_array( $licenses ) ) {
				$licenses = array();
			}

			return $licenses;
		}

		/**
		 * @param string|bool $module_type
		 *
		 * @return FS_Plugin_Plan[]
		 */
		private static function get_all_plans( $module_type = false ) {
			$plans = self::get_account_option( 'plans', $module_type );

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
		 * @return array<number,FS_Plugin[]>|false
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
		 * Check if user has connected his account (opted-in).
		 *
		 * Note:
		 *      If the user opted-in and opted-out on a later stage,
		 *      this will still return true. If you want to check if the
		 *      user is currently opted-in, use:
		 *          `$fs->is_registered() && $fs->is_tracking_allowed()`
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 * @return bool
		 */
		function is_registered() {
			return is_object( $this->_user );
		}

		/**
		 * Returns TRUE if the user opted-in and didn't disconnect (opt-out).
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.1.5
		 *
		 * @return bool
		 */
		function is_tracking_allowed() {
			return ( is_object( $this->_site ) && true !== $this->_site->is_disconnected );
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
		 * Get plugin add-ons.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @since  1.1.7.3 If not yet loaded, fetch data from the API.
		 *
		 * @param bool $flush
		 *
		 * @return FS_Plugin[]|false
		 */
		function get_addons( $flush = false ) {
			$this->_logger->entrance();

			if ( ! $this->_has_addons ) {
				return false;
			}

			$addons = $this->sync_addons( $flush );

			return ( ! is_array( $addons ) || empty( $addons ) ) ?
				false :
				$addons;
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
		 * Check if user has any
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.6
		 *
		 * @return bool
		 */
		function has_account_addons() {
			$addons = $this->get_account_addons();

			return is_array( $addons ) && ( 0 < count( $addons ) );
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
		 * @param bool   $flush
		 *
		 * @return FS_Plugin|false
		 */
		function get_addon_by_slug( $slug, $flush = false ) {
			$this->_logger->entrance();

			$addons = $this->get_addons( $flush );

			if ( is_array( $addons ) ) {
				foreach ( $addons as $addon ) {
					if ( $slug === $addon->slug ) {
						return $addon;
					}
				}
			}

			return false;
		}

		#----------------------------------------------------------------------------------
		#region Plans & Licensing
		#----------------------------------------------------------------------------------

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
		 * @return FS_Plugin_Plan|false
		 */
		function get_plan() {
			return is_object( $this->_site->plan ) ?
				$this->_site->plan :
				false;
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

			return $this->_site->is_trial();
		}

		/**
		 * Check if currently in a trial with payment method (credit card or paypal).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7
		 *
		 * @return bool
		 */
		function is_paid_trial() {
			$this->_logger->entrance();

			if ( ! $this->is_trial() ) {
				return false;
			}

			return $this->has_active_valid_license() && ( $this->_site->trial_plan_id == $this->_license->plan_id );
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
		 * Check if the user has an activate, non-expired license on current plugin's install.
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

			if ( ! $this->has_paid_plan() ) {
				return false;
			}

			return (
				! $this->is_trial() &&
				'free' !== $this->_site->plan->name &&
				$this->has_active_valid_license()
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

			if ( ! $this->has_paid_plan() ) {
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
		 * Check if user has any licenses associated with the plugin (including expired or blocking).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 *
		 * @return bool
		 */
		private function has_any_license() {
			return is_array( $this->_licenses ) && ( 0 < count( $this->_licenses ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @return FS_Plugin_License|false
		 */
		function _get_available_premium_license() {
			$this->_logger->entrance();

			if ( ! $this->has_paid_plan() ) {
				return false;
			}

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

			if ( $this->is_array_instanceof( $plans, 'FS_Plugin_Plan' ) ) {
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
		 * @return FS_Plugin_Plan|false
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
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.8.1
		 *
		 * @param string $name
		 *
		 * @return FS_Plugin_Plan|false
		 */
		private function get_plan_by_name( $name ) {
			$this->_logger->entrance();

			if ( ! is_array( $this->_plans ) || 0 === count( $this->_plans ) ) {
				$this->_sync_plans();
			}

			foreach ( $this->_plans as $plan ) {
				if ( $name == $plan->name ) {
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
		 * @param number|bool $site_license_id
		 *
		 * @return FS_Plugin_License[]|object
		 */
		function _sync_licenses( $site_license_id = false ) {
			$licenses = $this->_fetch_licenses( false, $site_license_id );

			if ( $this->is_array_instanceof( $licenses, 'FS_Plugin_License' ) ) {
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
		 * @return FS_Plugin_License|false
		 */
		function _get_license_by_id( $id ) {
			$this->_logger->entrance();

			if ( ! is_numeric( $id ) ) {
				return false;
			}

			if ( ! $this->has_any_license() ) {
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
		 * Check if module has only one plan.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 *
		 * @return bool
		 */
		function is_single_plan() {
			$this->_logger->entrance();

			if ( ! $this->is_registered() ||
			     ! is_array( $this->_plans ) ||
			     0 === count( $this->_plans )
			) {
				return true;
			}

			return ( 1 === count( $this->_plans ) );
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
			return $this->_has_paid_plans ||
			       FS_Plan_Manager::instance()->has_paid_plan( $this->_plans );
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
				/**
				 * @author Vova Feldman(@svovaf)
				 * @since  1.2.1.5
				 *
				 * Allow setting a trial from the SDK without calling the API.
				 * But, if the user did opt-in, continue using the real data from the API.
				 */
				if ( $this->_trial_days >= 0 ) {
					return true;
				}

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
			return ! $this->is_only_premium();
		}

		/**
		 * Displays a license activation dialog box when the user clicks on the "Activate License"
		 * or "Change License" link on the plugins
		 * page.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.1.9
		 */
		function _add_license_activation_dialog_box() {
			$vars = array(
				'id' => $this->_module_id,
			);

			fs_require_template( 'forms/license-activation.php', $vars );
			fs_require_template( 'forms/resend-key.php', $vars );
		}

		/**
		 * Displays the opt-out dialog box when the user clicks on the "Opt Out" link on the "Plugins"
		 * page.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.1.5
		 */
		function _add_optout_dialog() {
			if ( $this->is_theme() ) {
				$vars = null;
				fs_require_once_template( '/js/jquery.content-change.php', $vars );
			}

			$vars = array( 'id' => $this->_module_id );
			fs_require_template( 'forms/optout.php', $vars );
		}

		/**
		 * Prepare page to include all required UI and logic for the license activation dialog.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.0
		 */
		function _add_license_activation() {
			if ( ! $this->is_user_admin() ) {
				// Only admins can activate a license.
				return;
			}

			if ( ! $this->has_paid_plan() ) {
				// Module doesn't have any paid plans.
				return;
			}

			if ( ! $this->is_premium() ) {
				// Only add license activation logic to the premium version.
				return;
			}

			// Add license activation link and AJAX request handler.
			if ( self::is_plugins_page() ) {
				/**
				 * @since 1.2.0 Add license action link only on plugins page.
				 */
				$this->_add_license_action_link();
			}

			// Add license activation AJAX callback.
			$this->add_ajax_action( 'activate_license', array( &$this, '_activate_license_ajax_action' ) );

			// Add resend license AJAX callback.
			$this->add_ajax_action( 'resend_license_key', array( &$this, '_resend_license_key_ajax_action' ) );
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 * @since  1.1.9
		 */
		function _activate_license_ajax_action() {
			$this->_logger->entrance();

			$this->check_ajax_referer( 'activate_license' );

			$license_key = trim( fs_request_get( 'license_key' ) );

			if ( empty( $license_key ) ) {
				exit;
			}

			$plugin_id = fs_request_get( 'module_id', '', 'post' );
			$fs        = ( $plugin_id == $this->_module_id ) ?
				$this :
				$this->get_addon_instance( $plugin_id );

			$error     = false;
			$next_page = false;

			if ( $fs->is_registered() ) {
				$api     = $fs->get_api_site_scope();
				$install = $api->call( '/', 'put', array(
					'license_key' => $fs->apply_filters( 'license_key', $license_key )
				) );

				if ( isset( $install->error ) ) {
					$error = $install->error->message;
				} else {
                    $fs->_sync_license( true );

                    $next_page = $fs->is_addon() ?
                        $fs->get_parent_instance()->get_account_url() :
                        $fs->get_account_url();

                    $fs->reconnect_locally();
				}
			} else {
				$next_page = $fs->opt_in( false, false, false, $license_key );

				if ( isset( $next_page->error ) ) {
					$error = $next_page->error;
				}
			}

			$result = array(
				'success' => ( false === $error )
			);

			if ( false !== $error ) {
				$result['error'] = $error;
			} else {
				$result['next_page'] = $next_page;
			}

			echo json_encode( $result );

			exit;
		}

		/**
		 * Billing update AJAX callback.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 */
		function _update_billing_ajax_action() {
			$this->_logger->entrance();

			$this->check_ajax_referer( 'update_billing' );

			if ( ! $this->is_user_admin() ) {
				// Only for admins.
				self::shoot_ajax_failure();
			}

			$billing = fs_request_get( 'billing' );

			$api    = $this->get_api_user_scope();
			$result = $api->call( '/billing.json', 'put', array_merge( $billing, array(
				'plugin_id' => $this->get_parent_id(),
			) ) );

			if ( ! $this->is_api_result_entity( $result ) ) {
				self::shoot_ajax_failure();
			}

			// Purge cached billing.
			$this->get_api_user_scope()->purge_cache( 'billing.json' );

			self::shoot_ajax_success();
		}

		/**
		 * Trial start for anonymous users (AJAX callback).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 */
		function _start_trial_ajax_action() {
			$this->_logger->entrance();

			$this->check_ajax_referer( 'start_trial' );

			if ( ! $this->is_user_admin() ) {
				// Only for admins.
				self::shoot_ajax_failure();
			}

			$trial_data = fs_request_get( 'trial' );

			$next_page = $this->opt_in(
				false,
				false,
				false,
				false,
				false,
				$trial_data['plan_id']
			);

			if ( is_object( $next_page ) && $this->is_api_error( $next_page ) ) {
				self::shoot_ajax_failure(
					isset( $next_page->error ) ?
						$next_page->error->message :
						var_export( $next_page, true )
				);
			}

			$this->shoot_ajax_success( array(
				'next_page' => $next_page,
			) );
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.0
		 */
		function _resend_license_key_ajax_action() {
			$this->_logger->entrance();

			$this->check_ajax_referer( 'resend_license_key' );

			$email_address = sanitize_email( trim( fs_request_get( 'email', '', 'post' ) ) );

			if ( empty( $email_address ) ) {
				exit;
			}

			$error = false;

			$api    = $this->get_api_plugin_scope();
			$result = $api->call( '/licenses/resend.json', 'post',
				array(
					'email' => $email_address,
					'url'   => home_url(),
				)
			);

			if ( is_object( $result ) && isset( $result->error ) ) {
				$error = $result->error;

				if ( in_array( $error->code, array( 'invalid_email', 'no_user' ) ) ) {
					$error = $this->get_text_inline( "We couldn't find your email address in the system, are you sure it's the right address?", 'email-not-found' );
				} else if ( 'no_license' === $error->code ) {
					$error = $this->get_text_inline( "We can't see any active licenses associated with that email address, are you sure it's the right address?", 'no-active-licenses' );
				} else {
					$error = $error->message;
				}
			}

			$licenses = array(
				'success' => ( false === $error )
			);

			if ( false !== $error ) {
				$licenses['error'] = sprintf( '%s... %s', $this->get_text_x_inline( 'Oops', 'exclamation', 'oops' ), strtolower( $error ) );
			}

			echo json_encode( $licenses );

			exit;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.8
		 *
		 * @var string
		 */
		private static $_pagenow;

		/**
		 * Get current page or the referer if executing a WP AJAX request.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.8
		 *
		 * @return string
		 */
		static function get_current_page() {
			if ( ! isset( self::$_pagenow ) ) {
				global $pagenow;

				self::$_pagenow = $pagenow;

				if ( self::is_ajax() &&
				     'admin-ajax.php' === $pagenow
				) {
					$referer = fs_get_raw_referer();

					if ( is_string( $referer ) ) {
						$parts = explode( '?', $referer );

						self::$_pagenow = basename( $parts[0] );
					}
				}
			}

			return self::$_pagenow;
		}

		/**
		 * Helper method to check if user in the plugins page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 *
		 * @return bool
		 */
		static function is_plugins_page() {
			return ( 'plugins.php' === self::get_current_page() );
		}

		/**
		 * Helper method to check if user in the themes page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.6
		 *
		 * @return bool
		 */
		static function is_themes_page() {
			return ( 'themes.php' === self::get_current_page() );
		}

		#----------------------------------------------------------------------------------
		#region Affiliation
		#----------------------------------------------------------------------------------

        /**
         * @author Leo Fajardo
         * @since 1.2.3
         *
         * @return bool
         */
        function has_affiliate_program() {
            if ( ! is_object( $this->_plugin ) ) {
                return false;
            }

		    return $this->_plugin->has_affiliate_program();
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 1.2.3
         */
        private function fetch_affiliate_and_terms() {
            $this->_logger->entrance();

            if ( ! is_object( $this->plugin_affiliate_terms ) ) {
                $plugins_api     = $this->get_api_plugin_scope();
                $affiliate_terms = $plugins_api->get( '/aff.json?type=affiliation', true );

                if ( ! $this->is_api_result_entity( $affiliate_terms ) ) {
                    return;
                }

                $this->plugin_affiliate_terms = new FS_AffiliateTerms( $affiliate_terms );
            }

            if ( $this->is_registered() ) {
                $users_api = $this->get_api_user_scope();
                $result    = $users_api->get( "/plugins/{$this->_plugin->id}/aff/{$this->plugin_affiliate_terms->id}/affiliates.json", true );
                if ( $this->is_api_result_object( $result, 'affiliates' ) ) {
                    if ( ! empty( $result->affiliates ) ) {
                        $affiliate = new FS_Affiliate( $result->affiliates[0] );

                        if ( ! $affiliate->is_pending() && ! empty( $this->_storage->affiliate_application_data ) ) {
                            unset( $this->_storage->affiliate_application_data );
                        }

                        if ( $affiliate->is_using_custom_terms ) {
                            $affiliate_terms = $users_api->get( "/plugins/{$this->_plugin->id}/affiliates/{$affiliate->id}/aff/{$affiliate->custom_affiliate_terms_id}.json", true );
                            if ( $this->is_api_result_entity( $affiliate_terms ) ) {
                                $this->custom_affiliate_terms = new FS_AffiliateTerms( $affiliate_terms );
                            }
                        }

                        $this->affiliate = $affiliate;
                    }
                }
            }
        }

        /**
         * @author Leo Fajardo
         * @since 1.2.3
         *
         * @return FS_Affiliate
         */
        function get_affiliate() {
            return $this->affiliate;
        }


        /**
         * @author Leo Fajardo
         * @since 1.2.3
         *
         * @return FS_AffiliateTerms
         */
        function get_affiliate_terms() {
            return is_object( $this->custom_affiliate_terms ) ?
                $this->custom_affiliate_terms :
                $this->plugin_affiliate_terms;
        }

        /**
         * @author Leo Fajardo
         * @since 1.2.3
         *
         * @return FS_Affiliate|null
         */
        function _submit_affiliate_application() {
            $this->_logger->entrance();

            $this->check_ajax_referer( 'submit_affiliate_application' );

            if ( ! $this->is_user_admin() ) {
                // Only for admins.
                self::shoot_ajax_failure();
            }

            $affiliate = fs_request_get( 'affiliate' );

            if ( empty( $affiliate['promotion_methods'] ) ) {
                unset( $affiliate['promotion_methods'] );
            }

            if ( ! empty( $affiliate['additional_domains'] ) ) {
                $affiliate['additional_domains'] = array_unique( $affiliate['additional_domains'] );
            }

            if ( ! $this->is_registered() ) {
                // Opt in but don't track usage.
                $next_page = $this->opt_in(
                    false,
                    false,
                    false,
                    false,
                    false,
                    false,
                    true
                );

                if ( is_object( $next_page ) && $this->is_api_error( $next_page ) ) {
                    self::shoot_ajax_failure(
                        isset( $next_page->error ) ?
                            $next_page->error->message :
                            var_export( $next_page, true )
                    );
                } else if ( $this->is_pending_activation() ) {
                    self::shoot_ajax_failure( $this->get_text_inline( 'Account is pending activation.', 'account-is-pending-activation' ) );
                }
            }

            $api    = $this->get_api_user_scope();
            $result = $api->call(
                ( "/plugins/{$this->_plugin->id}/aff/{$this->plugin_affiliate_terms->id}/affiliates.json" ),
                'post',
                $affiliate
            );

            if ( $this->is_api_error( $result ) ) {
                self::shoot_ajax_failure(
                    isset( $result->error ) ?
                        $result->error->message :
                        var_export( $result, true )
                );
            }
            else
            {
                if ( $this->_admin_notices->has_sticky( 'affiliate_program' ) ) {
                    $this->_admin_notices->remove_sticky( 'affiliate_program' );
                }

                $affiliate_application_data = array(
                    'stats_description'            => $affiliate['stats_description'],
                    'promotion_method_description' => $affiliate['promotion_method_description'],
                );

                if ( ! empty( $affiliate['promotion_methods'] ) ) {
                    $affiliate_application_data['promotion_methods'] = $affiliate['promotion_methods'];
                }

                if ( ! empty( $affiliate['domain'] ) ) {
                    $affiliate_application_data['domain'] = $affiliate['domain'];
                }

                if ( ! empty( $affiliate['additional_domains'] ) ) {
                    $affiliate_application_data['additional_domains'] = $affiliate['additional_domains'];
                }

                $this->_storage->affiliate_application_data = $affiliate_application_data;
            }

            // Purge cached affiliate.
            $api->purge_cache( 'affiliate.json' );

            self::shoot_ajax_success( $result );
        }

        /**
         * @author Leo Fajardo
         * @since 1.2.3
         *
         * @return array|null
         */
        function get_affiliate_application_data() {
            if ( empty( $this->_storage->affiliate_application_data ) ) {
                return null;
            }

            return $this->_storage->affiliate_application_data;
        }

        #endregion Affiliation ------------------------------------------------------------

		#----------------------------------------------------------------------------------
		#region URL Generators
		#----------------------------------------------------------------------------------

		/**
		 * Alias to pricing_url().
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 * @uses   pricing_url()
		 *
		 * @param string $period Billing cycle
		 * @param bool   $is_trial
		 *
		 * @return string
		 */
		function get_upgrade_url( $period = WP_FS__PERIOD_ANNUALLY, $is_trial = false ) {
			return $this->pricing_url( $period, $is_trial );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @uses   get_upgrade_url()
		 *
		 * @return string
		 */
		function get_trial_url() {
			return $this->get_upgrade_url( WP_FS__PERIOD_ANNUALLY, true );
		}

		/**
		 * Plugin's pricing URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param string $billing_cycle Billing cycle
		 *
		 * @param bool   $is_trial
		 *
		 * @return string
		 */
		function pricing_url( $billing_cycle = WP_FS__PERIOD_ANNUALLY, $is_trial = false ) {
			$this->_logger->entrance();

			$params = array(
				'billing_cycle' => $billing_cycle
			);

			if ( $is_trial ) {
				$params['trial'] = 'true';
			}

			return $this->_get_admin_page_url( 'pricing', $params );
		}

		/**
		 * Checkout page URL.
		 *
		 * @author   Vova Feldman (@svovaf)
		 * @since    1.0.6
		 *
		 * @param string $billing_cycle Billing cycle
		 * @param bool   $is_trial
		 * @param array  $extra         (optional) Extra parameters, override other query params.
		 *
		 * @return string
		 */
		function checkout_url(
			$billing_cycle = WP_FS__PERIOD_ANNUALLY,
			$is_trial = false,
			$extra = array()
		) {
			$this->_logger->entrance();

			$params = array(
				'checkout'      => 'true',
				'billing_cycle' => $billing_cycle,
			);

			if ( $is_trial ) {
				$params['trial'] = 'true';
			}

			/**
			 * Params in extra override other params.
			 */
			$params = array_merge( $params, $extra );

			return $this->_get_admin_page_url( 'pricing', $params );
		}

		/**
		 * Add-on checkout URL.
		 *
		 * @author   Vova Feldman (@svovaf)
		 * @since    1.1.7
		 *
		 * @param number $addon_id
		 * @param number $pricing_id
		 * @param string $billing_cycle
		 * @param bool   $is_trial
		 *
		 * @return string
		 */
		function addon_checkout_url(
			$addon_id,
			$pricing_id,
			$billing_cycle = WP_FS__PERIOD_ANNUALLY,
			$is_trial = false
		) {
			return $this->checkout_url( $billing_cycle, $is_trial, array(
				'plugin_id'  => $addon_id,
				'pricing_id' => $pricing_id,
			) );
		}

		#endregion

		#endregion ------------------------------------------------------------------

		/**
		 * Check if plugin has any add-ons.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @since  1.1.7.3 Base logic only on the parameter provided by the developer in the init function.
		 *
		 * @return bool
		 */
		function has_addons() {
			$this->_logger->entrance();

			return $this->_has_addons;
		}

		/**
		 * Check if plugin can work in anonymous mode.
		 *
		 * @author     Vova Feldman (@svovaf)
		 * @since      1.0.9
		 *
		 * @return bool
		 *
		 * @deprecated Please use is_enable_anonymous() instead
		 */
		function enable_anonymous() {
			return $this->_enable_anonymous;
		}

		/**
		 * Check if plugin can work in anonymous mode.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.9
		 *
		 * @return bool
		 */
		function is_enable_anonymous() {
			return $this->_enable_anonymous;
		}

		/**
		 * Check if plugin is premium only (no free plans).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.9
		 *
		 * @return bool
		 */
		function is_only_premium() {
			return $this->_is_premium_only;
		}

		/**
		 * Checks if the plugin's type is "plugin". The other type is "theme".
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @return bool
		 */
		function is_plugin() {
			return ( WP_FS__MODULE_TYPE_PLUGIN === $this->_module_type );
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @return string
		 */
		function get_module_type() {
			if ( ! isset( $this->_module_type ) ) {
				$id_slug_type_path_map = self::$_accounts->get_option( 'id_slug_type_path_map', array() );
				$this->_module_type    = $id_slug_type_path_map[ $this->_module_id ]['type'];
			}

			return $this->_module_type;
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @return string
		 */
		function get_plugin_main_file_path() {
			return $this->_plugin_main_file_path;
		}

		/**
		 * Check if module has a premium code version.
		 *
		 * Serviceware module might be freemium without any
		 * premium code version, where the paid features
		 * are all part of the service.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.6
		 *
		 * @return bool
		 */
		function has_premium_version() {
			return $this->_has_premium_version;
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
		static function is_ajax() {
			return ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		}

		/**
		 * Check if it's an AJAX call targeted for the current module.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.0
		 *
		 * @param array|string $actions Collection of AJAX actions.
		 *
		 * @return bool
		 */
		function is_ajax_action( $actions ) {
			// Verify it's an ajax call.
			if ( ! self::is_ajax() ) {
				return false;
			}

			// Verify the call is relevant for the plugin.
			if ( $this->_module_id != fs_request_get( 'module_id' ) ) {
				return false;
			}

			// Verify it's one of the specified actions.
			if ( is_string( $actions ) ) {
				$actions = explode( ',', $actions );
			}

			if ( is_array( $actions ) && 0 < count( $actions ) ) {
				$ajax_action = fs_request_get( 'action' );

				foreach ( $actions as $action ) {
					if ( $ajax_action === $this->get_action_tag( $action ) ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Check if it's an AJAX call targeted for current request.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.0
		 *
		 * @param array|string $actions Collection of AJAX actions.
		 * @param number|null  $module_id
		 *
		 * @return bool
		 */
		static function is_ajax_action_static( $actions, $module_id = null ) {
			// Verify it's an ajax call.
			if ( ! self::is_ajax() ) {
				return false;
			}


			if ( ! empty( $module_id ) ) {
				// Verify the call is relevant for the plugin.
				if ( $module_id != fs_request_get( 'module_id' ) ) {
					return false;
				}
			}

			// Verify it's one of the specified actions.
			if ( is_string( $actions ) ) {
				$actions = explode( ',', $actions );
			}

			if ( is_array( $actions ) && 0 < count( $actions ) ) {
				$ajax_action = fs_request_get( 'action' );

				foreach ( $actions as $action ) {
					if ( $ajax_action === self::get_ajax_action_static( $action, $module_id ) ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7
		 *
		 * @return bool
		 */
		static function is_cron() {
			return ( defined( 'DOING_CRON' ) && DOING_CRON );
		}

		/**
		 * Check if a real user is visiting the admin dashboard.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7
		 *
		 * @return bool
		 */
		function is_user_in_admin() {
			return is_admin() && ! self::is_ajax() && ! self::is_cron();
		}

		/**
		 * Check if a real user is in the customizer view.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return bool
		 */
		static function is_customizer() {
			return is_customize_preview();
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
			if ( 0 < count( $params ) ) {
				foreach ( $params as $k => $v ) {
					$params[ $k ] = urlencode( $v );
				}
			}

			$page_param = $this->_menu->get_slug( $page );

            if ( empty( $page ) &&
                $this->is_theme() &&
                // Show the opt-in as an overlay for free wp.org themes or themes without any settings page.
                ( $this->is_free_wp_org_theme() || ! $this->has_settings_menu() ) ) {
                    $params[ $this->get_unique_affix() . '_show_optin' ] = 'true';

                    return add_query_arg(
                        $params,
                        admin_url( 'themes.php' )
                    );
            }

			if ( ! $this->has_settings_menu() ) {
				if ( ! empty( $page ) ) {
					// Module doesn't have a setting page, but since the request is for
					// a specific Freemius page, use the admin.php path.
					return add_query_arg( array_merge( $params, array(
						'page' => $page_param,
					) ), admin_url( 'admin.php' ) );
				} else {
					if ( $this->is_activation_mode() ) {
						/**
						 * @author Vova Feldman
						 * @since  1.2.1.6
						 *
						 * If plugin doesn't have a settings page, create one for the opt-in screen.
						 */
						return add_query_arg( array_merge( $params, array(
							'page' => $this->_slug,
						) ), admin_url( 'admin.php', 'admin' ) );
					} else {
						// Plugin without a settings page.
                        return add_query_arg(
                            $params,
                            admin_url( 'plugins.php' )
                        );
					}
				}
			}

			// Module has a submenu settings page.
			if ( ! $this->_menu->is_top_level() ) {
				$parent_slug = $this->_menu->get_parent_slug();
				$menu_file   = ( false !== strpos( $parent_slug, '.php' ) ) ?
					$parent_slug :
					'admin.php';

				return add_query_arg( array_merge( $params, array(
					'page' => $page_param,
				) ), admin_url( $menu_file, 'admin' ) );
			}

			// Module has a top level CPT settings page.
			if ( $this->_menu->is_cpt() ) {
				if ( empty( $page ) && $this->is_activation_mode() ) {
					return add_query_arg( array_merge( $params, array(
						'page' => $page_param
					) ), admin_url( 'admin.php', 'admin' ) );
				} else {
					if ( ! empty( $page ) ) {
						$params['page'] = $page_param;
					}

					return add_query_arg(
						$params,
						admin_url( $this->_menu->get_raw_slug(), 'admin' )
					);
				}
			}

			// Module has a custom top level settings page.
			return add_query_arg( array_merge( $params, array(
				'page' => $page_param,
			) ), admin_url( 'admin.php', 'admin' ) );
		}

		/**
		 * Check if currently in a specified admin page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @param string $page
		 *
		 * @return bool
		 */
		function is_admin_page( $page ) {
			return ( $this->_menu->get_slug( $page ) === fs_request_get( 'page', '', 'get' ) );
		}

		/**
		 * Get module's main admin setting page URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return string
		 */
		function main_menu_url() {
			return $this->_menu->main_menu_url();
		}

		/**
		 * Check if currently on the theme's setting page or
		 * on any of the Freemius added pages (via tabs).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return bool
		 */
		function is_theme_settings_page() {
			return fs_starts_with(
				fs_request_get( 'page', '', 'get' ),
				$this->_menu->get_slug()
			);
		}

		/**
		 * Plugin's account page + sync license URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.9.1
		 *
		 * @param bool|number $plugin_id
		 * @param bool        $add_action_nonce
		 * @param array       $params
		 *
		 * @return string
		 */
		function _get_sync_license_url( $plugin_id = false, $add_action_nonce = true, $params = array() ) {
			if ( is_numeric( $plugin_id ) ) {
				$params['plugin_id'] = $plugin_id;
			}

			return $this->get_account_url(
				$this->get_unique_affix() . '_sync_license',
				$params,
				$add_action_nonce
			);
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

			self::require_pluggable_essentials();

			return ( $add_action_nonce && is_string( $action ) ) ?
				fs_nonce_url( $this->_get_admin_page_url( 'account', $params ), $action ) :
				$this->_get_admin_page_url( 'account', $params );
		}

		/**
		 * @author  Vova Feldman (@svovaf)
		 * @since   1.2.0
		 *
		 * @param string $tab
		 * @param bool   $action
		 * @param array  $params
		 * @param bool   $add_action_nonce
		 *
		 * @return string
		 *
		 * @uses    get_account_url()
		 */
		function get_account_tab_url( $tab, $action = false, $params = array(), $add_action_nonce = true ) {
			$params['tab'] = $tab;

			return $this->get_account_url( $action, $params, $add_action_nonce );
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

		/**
		 * Add-on direct info URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.0
		 *
		 * @param string $slug
		 *
		 * @return string
		 */
		function addon_url( $slug ) {
			return $this->_get_admin_page_url( 'addons', array(
				'slug' => $slug
			) );
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
		private static function _encrypt( $str ) {
			if ( is_null( $str ) ) {
				return null;
			}

			/**
			 * The encrypt/decrypt functions are used to protect
			 * the user from messing up with some of the sensitive
			 * data stored for the module as a JSON in the database.
			 *
			 * I used the same suggested hack by the theme review team.
			 * For more details, look at the function `Base64UrlDecode()`
			 * in `./sdk/FreemiusBase.php`.
			 *
			 * @todo   Remove this hack once the base64 error is removed from the Theme Check.
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since  1.2.2
			 */
			$fn = 'base64' . '_encode';

			return $fn( $str );
		}

		static function _decrypt( $str ) {
			if ( is_null( $str ) ) {
				return null;
			}

			/**
			 * The encrypt/decrypt functions are used to protect
			 * the user from messing up with some of the sensitive
			 * data stored for the module as a JSON in the database.
			 *
			 * I used the same suggested hack by the theme review team.
			 * For more details, look at the function `Base64UrlDecode()`
			 * in `./sdk/FreemiusBase.php`.
			 *
			 * @todo   Remove this hack once the base64 error is removed from the Theme Check.
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since  1.2.2
			 */
			$fn = 'base64' . '_decode';

			return $fn( $str );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param FS_Entity $entity
		 *
		 * @return FS_Entity Return an encrypted clone entity.
		 */
		private static function _encrypt_entity( FS_Entity $entity ) {
			$clone = clone $entity;
			$props = get_object_vars( $entity );

			foreach ( $props as $key => $val ) {
				$clone->{$key} = self::_encrypt( $val );
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
		private static function decrypt_entity( FS_Entity $entity ) {
			$clone = clone $entity;
			$props = get_object_vars( $entity );

			foreach ( $props as $key => $val ) {
				$clone->{$key} = self::_decrypt( $val );
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

			self::_clean_admin_content_section();

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
				fs_redirect( $this->_get_admin_page_url() );
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

		#----------------------------------------------------------------------------------
		#region Account (Loading, Updates & Activation)
		#----------------------------------------------------------------------------------

		/***
		 * Load account information (user + site).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 */
		private function _load_account() {
			$this->_logger->entrance();

			$this->do_action( 'before_account_load' );

			$sites    = self::get_all_sites( $this->_module_type );
			$users    = self::get_all_users();
			$plans    = self::get_all_plans( $this->_module_type );
			$licenses = self::get_all_licenses( $this->_module_type );

			if ( $this->_logger->is_on() && is_admin() ) {
				$this->_logger->log( 'sites = ' . var_export( $sites, true ) );
				$this->_logger->log( 'users = ' . var_export( $users, true ) );
				$this->_logger->log( 'plans = ' . var_export( $plans, true ) );
				$this->_logger->log( 'licenses = ' . var_export( $licenses, true ) );
			}

			$site = isset( $sites[ $this->_slug ] ) ? $sites[ $this->_slug ] : false;

			if ( is_object( $site ) &&
			     is_numeric( $site->id ) &&
			     is_numeric( $site->user_id ) &&
			     is_object( $site->plan )
			) {
				// Load site.
				$this->_site       = clone $site;
				$this->_site->plan = self::decrypt_entity( $this->_site->plan );

                /**
                 * If the install owner's details are not stored locally, use the previous user's details if available.
                 *
                 * @author Leo Fajardo (@leorw)
                 */
				if ( ! isset( $users[ $this->_site->user_id ] ) && FS_User::is_valid_id( $this->_storage->prev_user_id ) ) {
                    $user_id = $this->_storage->prev_user_id;
                } else {
                    $user_id = $this->_site->user_id;
                }

				// Load relevant user.
				$this->_user = clone $users[ $user_id ];

				// Load plans.
				$this->_plans = $plans[ $this->_slug ];
				if ( ! is_array( $this->_plans ) || empty( $this->_plans ) ) {
					$this->_sync_plans();
				} else {
					for ( $i = 0, $len = count( $this->_plans ); $i < $len; $i ++ ) {
						if ( $this->_plans[ $i ] instanceof FS_Plugin_Plan ) {
							$this->_plans[ $i ] = self::decrypt_entity( $this->_plans[ $i ] );
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

			$this->send_install_update();

			$this->_store_account();

		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 *
		 * @param array $override_with
		 *
		 * @return array
		 */
		function get_opt_in_params( $override_with = array() ) {
			$this->_logger->entrance();

			$current_user = self::_get_current_wp_user();

			$activation_action = $this->get_unique_affix() . '_activate_new';
			$return_url        = $this->is_anonymous() ?
				// If skipped already, then return to the account page.
				$this->get_account_url( $activation_action, array(), false ) :
				// Return to the module's main page.
				$this->get_after_activation_url( 'after_connect_url', array( 'fs_action' => $activation_action ) );

			$params = array(
				'user_firstname'               => $current_user->user_firstname,
				'user_lastname'                => $current_user->user_lastname,
				'user_nickname'                => $current_user->user_nicename,
				'user_email'                   => $current_user->user_email,
				'user_ip'                      => WP_FS__REMOTE_ADDR,
				'plugin_slug'                  => $this->_slug,
				'plugin_id'                    => $this->get_id(),
				'plugin_public_key'            => $this->get_public_key(),
				'plugin_version'               => $this->get_plugin_version(),
				'return_url'                   => fs_nonce_url( $return_url, $activation_action ),
				'account_url'                  => fs_nonce_url( $this->_get_admin_page_url(
					'account',
					array( 'fs_action' => 'sync_user' )
				), 'sync_user' ),
				'site_uid'                     => $this->get_anonymous_id(),
				'site_url'                     => get_site_url(),
				'site_name'                    => get_bloginfo( 'name' ),
				'platform_version'             => get_bloginfo( 'version' ),
				'sdk_version'                  => $this->version,
				'programming_language_version' => phpversion(),
				'language'                     => get_bloginfo( 'language' ),
				'charset'                      => get_bloginfo( 'charset' ),
				'is_premium'                   => $this->is_premium(),
				'is_active'                    => true,
				'is_uninstalled'               => false,
			);

			if ( $this->is_pending_activation() &&
			     ! empty( $this->_storage->pending_license_key )
			) {
				$params['license_key'] = $this->_storage->pending_license_key;
			}

			if ( WP_FS__SKIP_EMAIL_ACTIVATION && $this->has_secret_key() ) {
				// Even though rand() is known for its security issues,
				// the timestamp adds another layer of protection.
				// It would be very hard for an attacker to get the secret key form here.
				// Plus, this should never run in production since the secret should never
				// be included in the production version.
				$params['ts']     = WP_FS__SCRIPT_START_TIME;
				$params['salt']   = md5( uniqid( rand() ) );
				$params['secure'] = md5(
					$params['ts'] .
					$params['salt'] .
					$this->get_secret_key()
				);
			}

			return array_merge( $params, $override_with );
		}

		/**
		 * 1. If successful opt-in or pending activation returns the next page that the user should be redirected to.
		 * 2. If there was an API error, return the API result.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 *
		 * @param string|bool $email
		 * @param string|bool $first
		 * @param string|bool $last
		 * @param string|bool $license_key
		 * @param bool        $is_uninstall       If "true", this means that the module is currently being uninstalled.
		 *                                        In this case, the user and site info will be sent to the server but no
		 *                                        data will be saved to the WP installation's database.
		 * @param number|bool $trial_plan_id
		 * @param bool        $is_disconnected Whether or not to opt in without tracking.
		 *
		 * @return string|object
		 * @use    WP_Error
		 */
		function opt_in(
			$email = false,
			$first = false,
			$last = false,
			$license_key = false,
			$is_uninstall = false,
			$trial_plan_id = false,
            $is_disconnected = false
		) {
			$this->_logger->entrance();

			if ( false === $email ) {
				$current_user = self::_get_current_wp_user();
				$email        = $current_user->user_email;
			}

			/**
			 * @since 1.2.1 If activating with license key, ignore the context-user
			 *              since the user will be automatically loaded from the license.
			 */
			if ( empty( $license_key ) ) {
				// Clean up pending license if opt-ing in again.
				$this->_storage->remove( 'pending_license_key' );

				if ( ! $is_uninstall ) {
					$fs_user = Freemius::_get_user_by_email( $email );
					if ( is_object( $fs_user ) && ! $this->is_pending_activation() ) {
						return $this->install_with_current_user( false, $trial_plan_id );
					}
				}
			}

			$user_info = array();
			if ( ! empty( $email ) ) {
				$user_info['user_email'] = $email;
			}
			if ( ! empty( $first ) ) {
				$user_info['user_firstname'] = $first;
			}
			if ( ! empty( $last ) ) {
				$user_info['user_lastname'] = $last;
			}

			$params = $this->get_opt_in_params( $user_info );

			$filtered_license_key = false;
			if ( is_string( $license_key ) ) {
				$filtered_license_key  = $this->apply_filters( 'license_key', $license_key );
				$params['license_key'] = $filtered_license_key;
			} else if ( FS_Plugin_Plan::is_valid_id( $trial_plan_id ) ) {
				$params['trial_plan_id'] = $trial_plan_id;
			}

			if ( $is_uninstall ) {
				$params['uninstall_params'] = array(
					'reason_id'   => $this->_storage->uninstall_reason->id,
					'reason_info' => $this->_storage->uninstall_reason->info
				);
			}

			if ( isset( $params['license_key'] ) ) {
				$fs_user = Freemius::_get_user_by_email( $email );

				if ( is_object( $fs_user ) ) {
					/**
					 * If opting in with a context license and the context WP Admin user already opted in
					 * before from the current site, add the user context security params to avoid the
					 * unnecessry email activation when the context license is owned by the same context user.
					 * 
					 * @author Leo Fajardo (@leorw)
					 * @since 1.2.3
					 */
					$params = array_merge( $params, FS_Security::instance()->get_context_params(
						$fs_user,
						false,
						'install_with_existing_user'
					) );
				}
			}

            $params['is_disconnected'] = $is_disconnected;
			$params['format']          = 'json';

			$url = WP_FS__ADDRESS . '/action/service/user/install/';
			if ( isset( $_COOKIE['XDEBUG_SESSION'] ) ) {
				$url = add_query_arg( 'XDEBUG_SESSION', 'PHPSTORM', $url );
			}

			$response = wp_remote_post( $url, array(
				'method'  => 'POST',
				'body'    => $params,
				'timeout' => 15,
			) );

			if ( $response instanceof WP_Error ) {
				if ( 'https://' === substr( $url, 0, 8 ) &&
				     isset( $response->errors ) &&
				     isset( $response->errors['http_request_failed'] )
				) {
					$http_error = strtolower( $response->errors['http_request_failed'][0] );

					if ( false !== strpos( $http_error, 'ssl' ) ) {
						// Failed due to old version of cURL or Open SSL (SSLv3 is not supported by CloudFlare).
						$url = 'http://' . substr( $url, 8 );

						$response = wp_remote_post( $url, array(
							'method'  => 'POST',
							'body'    => $params,
							'timeout' => 15,
						) );
					}
				}
			}

			if ( is_wp_error( $response ) ) {
				/**
				 * @var WP_Error $response
				 */
				$result = new stdClass();

				$error_code = $response->get_error_code();
				$error_type = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $error_code ) ) );

				$result->error = (object) array(
					'type'    => $error_type,
					'message' => $response->get_error_message(),
					'code'    => $error_code,
					'http'    => 402
				);

				return $result;
			}

			// Module is being uninstalled, don't handle the returned data.
			if ( $is_uninstall ) {
				return true;
			}

            /**
             * When json_decode() executed on PHP 5.2 with an invalid JSON, it will throw a PHP warning. Unfortunately, the new Theme Check doesn't allow PHP silencing and the theme review team isn't open to change that, therefore, instead of using `@json_decode()` we had to use the method without the `@` directive.
             *
             * @author Vova Feldman (@svovaf)
             * @since  1.2.3
             * @link   https://themes.trac.wordpress.org/ticket/46134#comment:5
             * @link   https://themes.trac.wordpress.org/ticket/46134#comment:9
             * @link   https://themes.trac.wordpress.org/ticket/46134#comment:12
             * @link   https://themes.trac.wordpress.org/ticket/46134#comment:14
             */
			$decoded = is_string( $response['body'] ) ?
                json_decode( $response['body'] ) :
                null;

			if ( empty( $decoded ) ) {
				return false;
			}

			if ( ! $this->is_api_result_object( $decoded ) ) {
				if ( ! empty( $params['license_key'] ) ) {
					// Pass the fully entered license key to the failure handler.
					$params['license_key'] = $license_key;
				}

				return $is_uninstall ?
					$decoded :
					$this->apply_filters( 'after_install_failure', $decoded, $params );
			} else if ( isset( $decoded->pending_activation ) && $decoded->pending_activation ) {
				// Pending activation, add message.
				return $this->set_pending_confirmation(
                    ( isset( $decoded->email ) ?
                        $decoded->email :
                        true ),
					false,
					$filtered_license_key,
					! empty( $params['trial_plan_id'] )
				);
			} else if ( isset( $decoded->install_secret_key ) ) {
				return $this->install_with_new_user(
					$decoded->user_id,
					$decoded->user_public_key,
					$decoded->user_secret_key,
					$decoded->install_id,
					$decoded->install_public_key,
					$decoded->install_secret_key,
					false
				);
			}

			return $decoded;
		}

		/**
		 * Set user and site identities.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param FS_User $user
		 * @param FS_Site $site
		 * @param bool    $redirect
		 * @param bool    $auto_install Since 1.2.1.7 If `true` and setting up an account with a valid license, will
		 *                              redirect (or return a URL) to the account page with a special parameter to
		 *                              trigger the auto installation processes.
		 *
		 * @return string If redirect is `false`, returns the next page the user should be redirected to.
		 */
		function setup_account(
			FS_User $user,
			FS_Site $site,
			$redirect = true,
			$auto_install = false
		) {
			$this->_user = $user;
			$this->_site = $site;

			$this->_sync_plans();

			$this->_enrich_site_plan( false );

			$this->_set_account( $user, $site );

			if ( $this->is_trial() ) {
				// Store trial plan information.
				$this->_enrich_site_trial_plan( true );
			}

			// If Freemius was OFF before, turn it on.
			$this->turn_on();

			$this->do_action( 'after_account_connection', $user, $site );

			if ( is_numeric( $site->license_id ) ) {
				$this->_license = $this->_get_license_by_id( $site->license_id );
			}

			$this->_admin_notices->remove_sticky( 'connect_account' );

			if ( $this->is_pending_activation() || ! $this->has_settings_menu() ) {
				// Remove pending activation sticky notice (if still exist).
				$this->_admin_notices->remove_sticky( 'activation_pending' );

				// Remove plugin from pending activation mode.
				unset( $this->_storage->is_pending_activation );

				if ( ! $this->is_paying_or_trial() ) {
					$this->_admin_notices->add_sticky(
						sprintf( $this->get_text_x_inline( '%s activation was successfully completed.',
							'pluginX activation was successfully...', 'plugin-x-activation-message' ), '<b>' . $this->get_plugin_name() . '</b>' ),
						'activation_complete'
					);
				}
			}

			if ( $this->is_paying_or_trial() ) {
				if ( ! $this->is_premium() || ! $this->has_premium_version() || ! $this->has_settings_menu() ) {
					if ( $this->is_paying() ) {
						$this->_admin_notices->add_sticky(
							sprintf(
								$this->get_text_inline( 'Your account was successfully activated with the %s plan.', 'activation-with-plan-x-message' ),
								$this->_site->plan->title
							) . $this->get_complete_upgrade_instructions(),
							'plan_upgraded',
							$this->get_text_x_inline( 'Yee-haw', 'interjection expressing joy or exuberance', 'yee-haw' ) . '!'
						);
					} else {
						$this->_admin_notices->add_sticky(
							sprintf(
								$this->get_text_inline( 'Your trial has been successfully started.', 'trial-started-message' ),
								'<i>' . $this->get_plugin_name() . '</i>'
							) . $this->get_complete_upgrade_instructions( $this->_storage->trial_plan->title ),
							'trial_started',
							$this->get_text_x_inline( 'Yee-haw', 'interjection expressing joy or exuberance', 'yee-haw' ) . '!'
						);
					}
				}

				$this->_admin_notices->remove_sticky( array(
					'trial_promotion',
				) );
			}

			$plugin_id = fs_request_get( 'plugin_id', false );

			// Store activation time ONLY for plugins (not add-ons).
			if ( ! is_numeric( $plugin_id ) || ( $plugin_id == $this->_plugin->id ) ) {
				$this->_storage->activation_timestamp = WP_FS__SCRIPT_START_TIME;
			}

			$next_page = '';

			$extra = array();
			if ( $auto_install ) {
				$extra['auto_install'] = 'true';
			}

			if ( is_numeric( $plugin_id ) ) {
				/**
				 * @author Leo Fajardo
				 * @since  1.2.1.6
				 *
				 * Also sync the license after an anonymous user subscribes.
				 */
				if ( $this->is_anonymous() || $plugin_id != $this->_plugin->id ) {
					// Add-on was installed - sync license right after install.
					$next_page = $this->_get_sync_license_url( $plugin_id, true, $extra );
				}
			} else {
				/**
				 * @author Vova Feldman (@svovaf)
				 * @since  1.1.9 If site installed with a valid license, sync license.
				 */
				if ( $this->is_paying() ) {
					$this->_sync_plugin_license( true );
				}

				// Reload the page with the keys.
				$next_page = $this->is_anonymous() ?
					// If user previously skipped, redirect to account page.
					$this->get_account_url( false, $extra ) :
					$this->get_after_activation_url( 'after_connect_url' );
			}

			if ( ! empty( $next_page ) && $redirect ) {
				fs_redirect( $next_page );
			}

			return $next_page;
		}

		/**
		 * Install plugin with new user information after approval.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 */
		function _install_with_new_user() {
			$this->_logger->entrance();

			if ( $this->is_registered() ) {
				return;
			}

			if ( ( $this->is_plugin() && fs_request_is_action( $this->get_unique_affix() . '_activate_new' ) ) ||
				// @todo This logic should be improved because it's executed on every load of a theme.
			     $this->is_theme()
			) {
//				check_admin_referer( $this->_slug . '_activate_new' );

				if ( fs_request_has( 'user_secret_key' ) ) {
					$this->install_with_new_user(
						fs_request_get( 'user_id' ),
						fs_request_get( 'user_public_key' ),
						fs_request_get( 'user_secret_key' ),
						fs_request_get( 'install_id' ),
						fs_request_get( 'install_public_key' ),
						fs_request_get( 'install_secret_key' ),
						true,
						fs_request_get_bool( 'auto_install' )
					);
				} else if ( fs_request_has( 'pending_activation' ) ) {
					$this->set_pending_confirmation( fs_request_get( 'user_email' ), true );
				}
			}
		}

		/**
		 * Install plugin with new user.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 *
		 * @param number $user_id
		 * @param string $user_public_key
		 * @param string $user_secret_key
		 * @param number $install_id
		 * @param string $install_public_key
		 * @param string $install_secret_key
		 * @param bool   $redirect
		 * @param bool   $auto_install Since 1.2.1.7 If `true` and setting up an account with a valid license, will
		 *                             redirect (or return a URL) to the account page with a special parameter to
		 *                             trigger the auto installation processes.
		 *
		 * @return string If redirect is `false`, returns the next page the user should be redirected to.
		 */
		private function install_with_new_user(
			$user_id,
			$user_public_key,
			$user_secret_key,
			$install_id,
			$install_public_key,
			$install_secret_key,
			$redirect = true,
			$auto_install = false
		) {
			$user             = new FS_User();
			$user->id         = $user_id;
			$user->public_key = $user_public_key;
			$user->secret_key = $user_secret_key;

			$this->_user = $user;
			$user_result = $this->get_api_user_scope()->get();
			$user        = new FS_User( $user_result );
			$this->_user = $user;

			$site             = new FS_Site();
			$site->id         = $install_id;
			$site->public_key = $install_public_key;
			$site->secret_key = $install_secret_key;

			$this->_site = $site;
			$site_result = $this->get_api_site_scope()->get();
			$site        = new FS_Site( $site_result );
			$this->_site = $site;

			return $this->setup_account(
				$this->_user,
				$this->_site,
				$redirect,
				$auto_install
			);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 *
		 * @param string|bool $email
		 * @param bool        $redirect
		 * @param string|bool $license_key      Since 1.2.1.5
		 * @param bool        $is_pending_trial Since 1.2.1.5
		 *
		 * @return string Since 1.2.1.5 if $redirect is `false`, return the pending activation page.
		 */
		private function set_pending_confirmation(
			$email = false,
			$redirect = true,
			$license_key = false,
			$is_pending_trial = false
		) {
			if ( $this->_ignore_pending_mode ) {
				/**
				 * If explicitly asked to ignore pending mode, set to anonymous mode
				 * if require confirmation before finalizing the opt-in.
				 *
				 * @author Vova Feldman
				 * @since  1.2.1.6
				 */
				$this->skip_connection();
			} else {
				// Install must be activated via email since
				// user with the same email already exist.
				$this->_storage->is_pending_activation = true;
				$this->_add_pending_activation_notice( $email, $is_pending_trial );
			}

			if ( ! empty( $license_key ) ) {
				$this->_storage->pending_license_key = $license_key;
			}

			// Remove the opt-in sticky notice.
			$this->_admin_notices->remove_sticky( array(
				'connect_account',
				'trial_promotion',
			) );

			$next_page = $this->get_after_activation_url( 'after_pending_connect_url' );

			// Reload the page with with pending activation message.
			if ( $redirect ) {
				fs_redirect( $next_page );
			}

			return $next_page;
		}

		/**
		 * Install plugin with current logged WP user info.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 */
		function _install_with_current_user() {
			$this->_logger->entrance();

			if ( $this->is_registered() ) {
				return;
			}

			if ( fs_request_is_action( $this->get_unique_affix() . '_activate_existing' ) && fs_request_is_post() ) {
//				check_admin_referer( 'activate_existing_' . $this->_plugin->public_key );

				/**
				 * @author Vova Feldman (@svovaf)
				 * @since  1.1.9 Add license key if given.
				 */
				$license_key = fs_request_get( 'license_secret_key' );

				$this->install_with_current_user( $license_key );
			}
		}


		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 *
		 * @param string|bool $license_key
		 * @param number|bool $trial_plan_id
		 * @param bool        $redirect
		 *
		 * @return string|object If redirect is `false`, returns the next page the user should be redirected to, or the
		 *                       API error object if failed to install.
		 */
		private function install_with_current_user(
			$license_key = false,
			$trial_plan_id = false,
			$redirect = true
		) {
			// Get current logged WP user.
			$current_user = self::_get_current_wp_user();

			// Find the relevant FS user by the email.
			$user = self::_get_user_by_email( $current_user->user_email );

			// We have to set the user before getting user scope API handler.
			$this->_user = $user;

			$extra_install_params = array(
				'uid' => $this->get_anonymous_id(),
			);

			if ( ! empty( $license_key ) ) {
				$filtered_license_key                = $this->apply_filters( 'license_key', $license_key );
				$extra_install_params['license_key'] = $filtered_license_key;
			} else if ( FS_Plugin_Plan::is_valid_id( $trial_plan_id ) ) {
				$extra_install_params['trial_plan_id'] = $trial_plan_id;
			}

			$args = $this->get_install_data_for_api( $extra_install_params, false, false );

			// Install the plugin.
			$install = $this->get_api_user_scope()->call(
				"/plugins/{$this->get_id()}/installs.json",
				'post',
				$args
			);

			if ( ! $this->is_api_result_entity( $install ) ) {
				if ( ! empty( $args['license_key'] ) ) {
					// Pass full the fully entered license key to the failure handler.
					$args['license_key'] = $license_key;
				}

				$install = $this->apply_filters( 'after_install_failure', $install, $args );

				$this->_admin_notices->add(
					sprintf( $this->get_text_inline( 'Couldn\'t activate %s.', 'could-not-activate-x' ), $this->get_plugin_name() ) . ' ' .
					$this->get_text_inline( 'Please contact us with the following message:', 'contact-us-with-error-message' ) . ' ' . '<b>' . $install->error->message . '</b>',
					$this->get_text_x_inline( 'Oops', 'exclamation', 'oops' ) . '...',
					'error'
				);

				if ( $redirect ) {
                    /**
                     * We set the user before getting the user scope API handler, so the user became temporarily
                     * registered (`is_registered() = true`). Since the API returned an error and we will redirect,
                     * we have to set the user to `null`, otherwise, the user will be redirected to the wrong
                     * activation page based on the return value of `is_registered()`. In addition, in case the
                     * context plugin doesn't have a settings menu and the default page is the `Plugins` page,
                     * misleading plugin activation errors will be shown on the `Plugins` page.
                     *
                     * @author Leo Fajardo (@leorw)
                     */
                    $this->_user = null;

                    fs_redirect( $this->get_activation_url( array( 'error' => $install->error->message ) ) );
				}

				return $install;
			}

			$site        = new FS_Site( $install );
			$this->_site = $site;

			return $this->setup_account( $this->_user, $this->_site, $redirect );
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
			$addon_install = $parent_fs->get_api_site_scope()->call(
				"/addons/{$this->_plugin->id}/installs.json",
				'post',
				$this->get_install_data_for_api( array(
					'uid' => $this->get_anonymous_id(),
				), false, false )
			);

			if ( isset( $addon_install->error ) ) {
				$this->_admin_notices->add(
					sprintf( $this->get_text_inline( 'Couldn\'t activate %s.', 'could-not-activate-x' ), $this->get_plugin_name() ) . ' ' .
					$this->get_text_inline( 'Please contact us with the following message:', 'contact-us-with-error-message' ) . ' ' . '<b>' . $addon_install->error->message . '</b>',
					$this->get_text_x_inline( 'Oops', 'exclamation', 'oops' ) . '...',
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

		/**
		 * Tries to activate parent account based on add-on's info.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @param Freemius $parent_fs
		 */
		private function activate_parent_account( Freemius $parent_fs ) {
			if ( ! $this->is_addon() ) {
				// This is not an add-on.
				return;
			}

			if ( $parent_fs->is_registered() ) {
				// Already activated.
				return;
			}

			// Activate parent with add-on's user credentials.
			$parent_install = $this->get_api_user_scope()->call(
				"/plugins/{$parent_fs->_plugin->id}/installs.json",
				'post',
				$parent_fs->get_install_data_for_api( array(
					'uid' => $parent_fs->get_anonymous_id(),
				), false, false )
			);

			if ( isset( $parent_install->error ) ) {
				$this->_admin_notices->add(
					sprintf( $this->get_text_inline( 'Couldn\'t activate %s.', 'could-not-activate-x' ), $this->get_plugin_name() ) . ' ' .
					$this->get_text_inline( 'Please contact us with the following message:', 'contact-us-with-error-message' ) . ' ' . '<b>' . $parent_install->error->message . '</b>',
					$this->get_text_x_inline( 'Oops', 'exclamation', 'oops' ) . '...',
					'error'
				);

				return;
			}

            $parent_fs->_admin_notices->remove_sticky( 'connect_account' );

            if ( $parent_fs->is_pending_activation() ) {
                $parent_fs->_admin_notices->remove_sticky( 'activation_pending' );

                unset( $parent_fs->_storage->is_pending_activation );
            }

			// First of all, set site info - otherwise we won't
			// be able to invoke API calls.
			$parent_fs->_site = new FS_Site( $parent_install );

			// Sync add-on plans.
			$parent_fs->_sync_plans();

			// Get site's current plan.
			$parent_fs->_site->plan = $parent_fs->_get_plan_by_id( $parent_fs->_site->plan->id );

			// Get user information based on parent's plugin.
			$user = $this->get_user();

			$parent_fs->_set_account( $user, $parent_fs->_site );
		}

		#endregion

		#----------------------------------------------------------------------------------
		#region Admin Menu Items
		#----------------------------------------------------------------------------------

		private $_menu_items = array();

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.8
		 *
		 * @return array
		 */
		function get_menu_items() {
			return $this->_menu_items;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return string
		 */
		function get_menu_slug() {
			return $this->_menu->get_slug();
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		function _prepare_admin_menu() {
//			if ( ! $this->is_on() ) {
//				return;
//			}

			if ( ! $this->has_api_connectivity() && ! $this->is_enable_anonymous() ) {
				$this->_menu->remove_menu_item();
			} else {
				$this->do_action( 'before_admin_menu_init' );

				$this->add_menu_action();
				$this->add_submenu_items();
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
				if ( $this->is_plugin() || ( $this->has_settings_menu() && ! $this->is_free_wp_org_theme() ) ) {
					$this->override_plugin_menu_with_activation();
				} else {
					/**
					 * Handle theme opt-in when the opt-in form shows as a dialog box in the themes page.
					 */
					if ( fs_request_is_action( $this->get_unique_affix() . '_activate_existing' ) ) {
						add_action( 'load-themes.php', array( &$this, '_install_with_current_user' ) );
					} else if ( fs_request_is_action( $this->get_unique_affix() . '_activate_new' ) ||
					            fs_request_get_bool( 'pending_activation' )
					) {
						add_action( 'load-themes.php', array( &$this, '_install_with_new_user' ) );
					}
				}
			} else {
				if ( ! $this->is_registered() ) {
					// If not registered try to install user.
					if ( fs_request_is_action( $this->get_unique_affix() . '_activate_new' ) ) {
						$this->_install_with_new_user();
					}
				} else if (
					fs_request_is_action( 'sync_user' ) &&
					( ! $this->has_settings_menu() || $this->is_free_wp_org_theme() )
				) {
					$this->_handle_account_user_sync();
				}
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 */
		function _redirect_on_clicked_menu_link() {
			$this->_logger->entrance();

			$page = strtolower( isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '' );

			$this->_logger->log( 'page = ' . $page );

			foreach ( $this->_menu_items as $priority => $items ) {
				foreach ( $items as $item ) {
					if ( isset( $item['url'] ) ) {
						if ( $page === $this->_menu->get_slug( strtolower( $item['menu_slug'] ) ) ) {
							$this->_logger->log( 'Redirecting to ' . $item['url'] );

							fs_redirect( $item['url'] );
						}
					}
				}
			}
		}

		/**
		 * Remove plugin's all admin menu items & pages, and replace with activation page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 */
		private function override_plugin_menu_with_activation() {
			$this->_logger->entrance();

			$hook = false;

			if ( ! $this->_menu->has_menu() ) {
				// Add the opt-in page without a menu item.
				$hook = FS_Admin_Menu_Manager::add_subpage(
					null,
					$this->get_plugin_name(),
					$this->get_plugin_name(),
					'manage_options',
					$this->_slug,
					array( &$this, '_connect_page_render' )
				);
			} else if ( $this->_menu->is_top_level() ) {
				$hook = $this->_menu->override_menu_item( array( &$this, '_connect_page_render' ) );

				if ( false === $hook ) {
					// Create new menu item just for the opt-in.
					$hook = FS_Admin_Menu_Manager::add_page(
						$this->get_plugin_name(),
						$this->get_plugin_name(),
						'manage_options',
						$this->_menu->get_slug(),
						array( &$this, '_connect_page_render' )
					);
				}
			} else {
				$menus = array( $this->_menu->get_parent_slug() );

				if ( $this->_menu->is_override_exact() ) {
					// Make sure the current page is matching the activation page.
					if ( ! $this->is_matching_url( $this->get_activation_url() ) ) {
						return;
					}
				}

				foreach ( $menus as $parent_slug ) {
					$hook = $this->_menu->override_submenu_action(
						$parent_slug,
						$this->_menu->get_raw_slug(),
						array( &$this, '_connect_page_render' )
					);

					if ( false !== $hook ) {
						// Found plugin's submenu item.
						break;
					}
				}
			}

			if ( $this->is_activation_page() ) {
				// Clean admin page from distracting content.
				self::_clean_admin_content_section();
			}

			if ( false !== $hook ) {
				if ( fs_request_is_action( $this->get_unique_affix() . '_activate_existing' ) ) {
                    $this->_install_with_current_user();
				} else if ( fs_request_is_action( $this->get_unique_affix() . '_activate_new' ) ) {
                    $this->_install_with_new_user();
				}
			}
		}

		/**
		 * @author Leo Fajardo (leorw)
		 * @since  1.2.1
		 *
		 * return string
		 */
		function get_top_level_menu_capability() {
			global $menu;

			$top_level_menu_slug = $this->get_top_level_menu_slug();

			foreach ( $menu as $menu_info ) {
				/**
				 * The second element in the menu info array is the capability/role that has access to the menu and the
				 * third element is the menu slug.
				 */
				if ( $menu_info[2] === $top_level_menu_slug ) {
					return $menu_info[1];
				}
			}

			return 'read';
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 *
		 * @return string
		 */
		private function get_top_level_menu_slug() {
			return ( $this->is_addon() ?
				$this->get_parent_instance()->_menu->get_top_level_menu_slug() :
				$this->_menu->get_top_level_menu_slug() );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return string
		 */
		function get_pricing_cta_label() {
			$label = $this->get_text_inline( 'Upgrade', 'upgrade' );

			if ( $this->is_in_trial_promotion() &&
			     ! $this->is_paying_or_trial()
			) {
				// If running a trial promotion, modify the pricing to load the trial.
				$label = $this->get_text_inline( 'Start Trial', 'start-trial' );
			} else if ( $this->is_paying() ) {
				$label = $this->get_text_inline( 'Pricing', 'pricing' );
			}

			return $label;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return bool
		 */
		function is_pricing_page_visible() {
			return (
				// Has at least one paid plan.
				$this->has_paid_plan() &&
				// Didn't ask to hide the pricing page.
				$this->is_page_visible( 'pricing' ) &&
				// Don't have a valid active license or has more than one plan.
				( ! $this->is_paying() || ! $this->is_single_plan() )
			);
		}

		/**
		 * Add default Freemius menu items.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 */
		private function add_submenu_items() {
			$this->_logger->entrance();

			if ( ! $this->is_addon() ) {
				/**
				 * @since 1.2.2.7 Also add submenu items when running in a free .org theme so the tabs will be visible.
				 */
				if ( ! $this->is_activation_mode() || $this->is_free_wp_org_theme() ) {
                    if ( $this->has_affiliate_program() ) {
                        // Add affiliation page.
                        $this->add_submenu_item(
                            $this->get_text_inline( 'Affiliation', 'affiliation' ),
                            array( &$this, '_affiliation_page_render' ),
                            $this->get_plugin_name() . ' &ndash; ' . $this->get_text_inline( 'Affiliation', 'affiliation' ),
                            'manage_options',
                            'affiliation',
                            'Freemius::_clean_admin_content_section',
                            WP_FS__DEFAULT_PRIORITY,
                            $this->is_submenu_item_visible( 'affiliation' )
                        );
                    }

					if ( $this->is_registered() ) {
						$show_account = (
							$this->is_submenu_item_visible( 'account' ) &&
							/**
							 * @since 1.2.2.7 Don't show the Account for free WP.org themes without any paid plans.
							 */
							( ! $this->is_free_wp_org_theme() || $this->has_paid_plan() )
						);

						// Add user account page.
						$this->add_submenu_item(
							$this->get_text_inline( 'Account', 'account' ),
							array( &$this, '_account_page_render' ),
							$this->get_plugin_name() . ' &ndash; ' . $this->get_text_inline( 'Account', 'account' ),
							'manage_options',
							'account',
							array( &$this, '_account_page_load' ),
							WP_FS__DEFAULT_PRIORITY,
							$show_account
						);
					}

					// Add contact page.
					$this->add_submenu_item(
						$this->get_text_inline( 'Contact Us', 'contact-us' ),
						array( &$this, '_contact_page_render' ),
						$this->get_plugin_name() . ' &ndash; ' . $this->get_text_inline( 'Contact Us', 'contact-us' ),
						'manage_options',
						'contact',
						'Freemius::_clean_admin_content_section',
						WP_FS__DEFAULT_PRIORITY,
						$this->is_submenu_item_visible( 'contact' )
					);

					if ( $this->has_addons() ) {
						$this->add_submenu_item(
							$this->get_text_inline( 'Add-Ons', 'add-ons' ),
							array( &$this, '_addons_page_render' ),
							$this->get_plugin_name() . ' &ndash; ' . $this->get_text_inline( 'Add-Ons', 'add-ons' ),
							'manage_options',
							'addons',
							array( &$this, '_addons_page_load' ),
							WP_FS__LOWEST_PRIORITY - 1,
							$this->is_submenu_item_visible( 'addons' )
						);
					}

					$show_pricing = (
						$this->is_submenu_item_visible( 'pricing' ) &&
						$this->is_pricing_page_visible()
					);

					$pricing_cta_text = $this->get_pricing_cta_label();
					$pricing_class    = 'upgrade-mode';
					if ( $show_pricing ) {
						if ( $this->is_in_trial_promotion() &&
						     ! $this->is_paying_or_trial()
						) {
							// If running a trial promotion, modify the pricing to load the trial.
							$pricing_class    = 'trial-mode';
						} else if ( $this->is_paying() ) {
							$pricing_class    = '';
						}
					}

					// Add upgrade/pricing page.
					$this->add_submenu_item(
                        $pricing_cta_text . '&nbsp;&nbsp;' . ( is_rtl() ? '&#x2190;' : '&#x27a4;' ),
						array( &$this, '_pricing_page_render' ),
						$this->get_plugin_name() . ' &ndash; ' . $this->get_text_x_inline( 'Pricing', 'noun', 'pricing' ),
						'manage_options',
						'pricing',
						'Freemius::_clean_admin_content_section',
						WP_FS__LOWEST_PRIORITY,
						$show_pricing,
						$pricing_class
					);
				}
			}


			if ( 0 < count( $this->_menu_items ) ) {
				if ( ! $this->_menu->is_top_level() ) {
					fs_enqueue_local_style( 'fs_common', '/admin/common.css' );

					// Append submenu items right after the plugin's submenu item.
					$this->order_sub_submenu_items();
				} else {
					// Append submenu items.
					$this->embed_submenu_items();
				}
			}
		}

		/**
		 * Moved the actual submenu item additions to a separated function,
		 * in order to support sub-submenu items when the plugin's settings
		 * only have a submenu and not top-level menu item.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.4
		 */
		private function embed_submenu_items() {
			$item_template = $this->_menu->is_top_level() ?
				'<span class="fs-submenu-item %s %s %s">%s</span>' :
				'<span class="fs-submenu-item fs-sub %s %s %s">%s</span>';

			$top_level_menu_capability = $this->get_top_level_menu_capability();

			ksort( $this->_menu_items );

			foreach ( $this->_menu_items as $priority => $items ) {
				foreach ( $items as $item ) {
					$capability = ( ! empty( $item['capability'] ) ? $item['capability'] : $top_level_menu_capability );

					$menu_item = sprintf(
						$item_template,
						$this->get_unique_affix(),
						$item['menu_slug'],
						! empty( $item['class'] ) ? $item['class'] : '',
						$item['menu_title']
					);

					$menu_slug = $this->_menu->get_slug( $item['menu_slug'] );

					if ( ! isset( $item['url'] ) ) {
						$hook = FS_Admin_Menu_Manager::add_subpage(
							$item['show_submenu'] ?
								$this->get_top_level_menu_slug() :
								null,
							$item['page_title'],
							$menu_item,
							$capability,
							$menu_slug,
							$item['render_function']
						);

						if ( false !== $item['before_render_function'] ) {
							add_action( "load-$hook", $item['before_render_function'] );
						}
					} else {
						FS_Admin_Menu_Manager::add_subpage(
							$item['show_submenu'] ?
								$this->get_top_level_menu_slug() :
								null,
							$item['page_title'],
							$menu_item,
							$capability,
							$menu_slug,
							array( $this, '' )
						);
					}
				}
			}
		}

		/**
		 * Re-order the submenu items so all Freemius added new submenu items
		 * are added right after the plugin's settings submenu item.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.4
		 */
		private function order_sub_submenu_items() {
			global $submenu;

			$menu_slug = $this->_menu->get_top_level_menu_slug();

			/**
			 * Before "admin_menu" fires, WordPress will loop over the default submenus and remove pages for which the user
			 * does not have permissions. So in case a plugin does not have top-level menu but does have submenus under any
			 * of the default menus, only users that have the right role can access its sub-submenus (Account, Contact Us,
			 * Support Forum, etc.) since $submenu[ $menu_slug ] will be empty if the user doesn't have permission.
			 *
			 * In case a plugin does not have submenus under any of the default menus but does have submenus under the menu
			 * of another plugin, only users that have the right role can access its sub-submenus since we will use the
			 * capability needed to access the parent menu as the capability for the submenus that we will add.
			 */
			if ( empty( $submenu[ $menu_slug ] ) ) {
				return;
			}

			$top_level_menu = &$submenu[ $menu_slug ];

			$all_submenu_items_after = array();

			$found_submenu_item = false;

			foreach ( $top_level_menu as $submenu_id => $meta ) {
				if ( $found_submenu_item ) {
					// Remove all submenu items after the plugin's submenu item.
					$all_submenu_items_after[] = $meta;
					unset( $top_level_menu[ $submenu_id ] );
				}

				if ( $this->_menu->get_raw_slug() === $meta[2] ) {
					// Found the submenu item, put all below.
					$found_submenu_item = true;
					continue;
				}
			}

			// Embed all plugin's new submenu items.
			$this->embed_submenu_items();

			// Start with specially high number to make sure it's appended.
			$i = max( 10000, max( array_keys( $top_level_menu ) ) + 1 );
			foreach ( $all_submenu_items_after as $meta ) {
				$top_level_menu[ $i ] = $meta;
				$i ++;
			}

			// Sort submenu items.
			ksort( $top_level_menu );
		}

		/**
		 * Helper method to return the module's support forum URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return string
		 */
		function get_support_forum_url() {
			return $this->apply_filters( 'support_forum_url', "https://wordpress.org/support/{$this->_module_type}/{$this->_slug}" );
		}

		/**
		 * Displays the Support Forum link when enabled.
		 *
		 * Can be filtered like so:
		 *
		 *  function _fs_show_support_menu( $is_visible, $menu_id ) {
		 *      if ( 'support' === $menu_id ) {
		 *            return _fs->is_registered();
		 *        }
		 *        return $is_visible;
		 *    }
		 *    _fs()->add_filter('is_submenu_visible', '_fs_show_support_menu', 10, 2);
		 *
		 */
		function _add_default_submenu_items() {
			if ( ! $this->is_on() ) {
				return;
			}

			if ( ! $this->is_activation_mode() ) {
				$this->add_submenu_link_item(
					$this->apply_filters( 'support_forum_submenu', $this->get_text_inline( 'Support Forum', 'support-forum' ) ),
					$this->get_support_forum_url(),
					'wp-support-forum',
					null,
					50,
					$this->is_submenu_item_visible( 'support' )
				);
			}
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
		 * @param string        $class Since 1.2.1.5 can add custom classes to menu items.
		 */
		function add_submenu_item(
			$menu_title,
			$render_function,
			$page_title = false,
			$capability = 'manage_options',
			$menu_slug = false,
			$before_render_function = false,
			$priority = WP_FS__DEFAULT_PRIORITY,
			$show_submenu = true,
			$class = ''
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
						$show_submenu,
						$class
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
				'menu_slug'              => is_string( $menu_slug ) ? $menu_slug : strtolower( $menu_title ),
				'render_function'        => $render_function,
				'before_render_function' => $before_render_function,
				'show_submenu'           => $show_submenu,
				'class'                  => $class,
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
		 * @param bool   $show_submenu
		 */
		function add_submenu_link_item(
			$menu_title,
			$url,
			$menu_slug = false,
			$capability = 'read',
			$priority = WP_FS__DEFAULT_PRIORITY,
			$show_submenu = true
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
				'menu_title'             => $menu_title,
				'capability'             => $capability,
				'menu_slug'              => is_string( $menu_slug ) ? $menu_slug : strtolower( $menu_title ),
				'url'                    => $url,
				'page_title'             => $menu_title,
				'render_function'        => 'fs_dummy',
				'before_render_function' => '',
				'show_submenu'           => $show_submenu,
			);
		}

		#endregion ------------------------------------------------------------------


		#--------------------------------------------------------------------------------
		#region Actions / Hooks / Filters
		#--------------------------------------------------------------------------------

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7
		 *
		 * @param string $tag
		 *
		 * @return string
		 */
		public function get_action_tag( $tag ) {
			return self::get_action_tag_static( $tag, $this->_slug, $this->is_plugin() );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.6
		 *
		 * @param string $tag
		 * @param string $slug
		 * @param bool   $is_plugin
		 *
		 * @return string
		 */
		static function get_action_tag_static( $tag, $slug = '', $is_plugin = true ) {
			$action = "fs_{$tag}";

			if ( ! empty( $slug ) ) {
				$action .= '_' . self::get_module_unique_affix( $slug, $is_plugin );
			}

			return $action;
		}

		/**
		 * Returns a string that can be used to generate a unique action name,
		 * option name, HTML element ID, or HTML element class.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @return string
		 */
		public function get_unique_affix() {
			return self::get_module_unique_affix( $this->_slug, $this->is_plugin() );
		}

		/**
		 * Returns a string that can be used to generate a unique action name,
		 * option name, HTML element ID, or HTML element class.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.5
		 *
		 * @param string $slug
		 * @param bool   $is_plugin
		 *
		 * @return string
		 */
		static function get_module_unique_affix( $slug, $is_plugin = true ) {
			$affix = $slug;

			if ( ! $is_plugin ) {
				$affix .= '-' . WP_FS__MODULE_TYPE_THEME;
			}

			return $affix;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1
		 * @since  1.2.2.5 The AJAX action names are based on the module ID, not like the non-AJAX actions that are
		 *         based on the slug for backward compatibility.
		 *
		 * @param string $tag
		 *
		 * @return string
		 */
		function get_ajax_action( $tag ) {
			return self::get_ajax_action_static( $tag, $this->_module_id );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 *
		 * @param string $tag
		 *
		 * @return string
		 */
		function get_ajax_security( $tag ) {
			return wp_create_nonce( $this->get_ajax_action( $tag ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 *
		 * @param string $tag
		 */
		function check_ajax_referer( $tag ) {
			check_ajax_referer( $this->get_ajax_action( $tag ), 'security' );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.6
		 * @since  1.2.2.5 The AJAX action names are based on the module ID, not like the non-AJAX actions that are
		 *         based on the slug for backward compatibility.
		 *
		 * @param string      $tag
		 * @param number|null $module_id
		 *
		 * @return string
		 */
		private static function get_ajax_action_static( $tag, $module_id = null ) {
			$action = "fs_{$tag}";

			if ( ! empty( $module_id ) ) {
				$action .= "_{$module_id}";
			}

			return $action;
		}

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
					array( $this->get_action_tag( $tag ) ),
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
		function add_action(
			$tag,
			$function_to_add,
			$priority = WP_FS__DEFAULT_PRIORITY,
			$accepted_args = 1
		) {
			$this->_logger->entrance( $tag );

			add_action( $this->get_action_tag( $tag ), $function_to_add, $priority, $accepted_args );
		}

		/**
		 * Add AJAX action, specific for the current context plugin.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1
		 *
		 * @param string   $tag
		 * @param callable $function_to_add
		 * @param int      $priority
		 *
		 * @uses   add_action()
		 *
		 * @return bool True if action added, false if no need to add the action since the AJAX call isn't matching.
		 */
		function add_ajax_action(
			$tag,
			$function_to_add,
			$priority = WP_FS__DEFAULT_PRIORITY
		) {
			$this->_logger->entrance( $tag );

			return self::add_ajax_action_static(
				$tag,
				$function_to_add,
				$priority,
				$this->_module_id
			);
		}

		/**
		 * Add AJAX action.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.6
		 *
		 * @param string      $tag
		 * @param callable    $function_to_add
		 * @param int         $priority
		 * @param number|null $module_id
		 *
		 * @return bool True if action added, false if no need to add the action since the AJAX call isn't matching.
		 * @uses   add_action()
		 *
		 */
		static function add_ajax_action_static(
			$tag,
			$function_to_add,
			$priority = WP_FS__DEFAULT_PRIORITY,
			$module_id = null
		) {
			self::$_static_logger->entrance( $tag );

			if ( ! self::is_ajax_action_static( $tag, $module_id ) ) {
				return false;
			}

			add_action(
				'wp_ajax_' . self::get_ajax_action_static( $tag, $module_id ),
				$function_to_add,
				$priority,
				0
			);

			self::$_static_logger->info( "$tag AJAX callback action added." );

			return true;
		}

		/**
		 * Send a JSON response back to an Ajax request.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 *
		 * @param mixed $response
		 */
		static function shoot_ajax_response( $response ) {
			wp_send_json( $response );
		}

		/**
		 * Send a JSON response back to an Ajax request, indicating success.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 *
		 * @param mixed $data Data to encode as JSON, then print and exit.
		 */
		static function shoot_ajax_success( $data = null ) {
			wp_send_json_success( $data );
		}

		/**
		 * Send a JSON response back to an Ajax request, indicating failure.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 *
		 * @param mixed $error Optional error message.
		 */
		static function shoot_ajax_failure( $error = '' ) {
			$result = array( 'success' => false );
			if ( ! empty( $error ) ) {
				$result['error'] = $error;
			}

			wp_send_json( $result );
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
			array_unshift( $args, $this->get_unique_affix() );

			return call_user_func_array( 'fs_apply_filter', $args );
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
		function add_filter( $tag, $function_to_add, $priority = WP_FS__DEFAULT_PRIORITY, $accepted_args = 1 ) {
			$this->_logger->entrance( $tag );

			add_filter( $this->get_action_tag( $tag ), $function_to_add, $priority, $accepted_args );
		}

		/**
		 * Check if has filter.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.4
		 *
		 * @param string        $tag
		 * @param callable|bool $function_to_check Optional. The callback to check for. Default false.
		 *
		 * @return false|int
		 *
		 * @uses   has_filter()
		 */
		function has_filter( $tag, $function_to_check = false ) {
			$this->_logger->entrance( $tag );

			return has_filter( $this->get_action_tag( $tag ), $function_to_check );
		}

		#endregion

		/**
		 * Override default i18n text phrases.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.6
		 *
		 * @param string[] string $key_value
		 *
		 * @uses   fs_override_i18n()
		 */
		function override_i18n( $key_value ) {
			fs_override_i18n( $key_value, $this->_slug );
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

			if ( empty( $this->_site->id ) ) {
				$this->_logger->error( "Empty install ID, can't store site." );

				return;
			}

			$encrypted_site       = clone $this->_site;
			$encrypted_site->plan = self::_encrypt_entity( $this->_site->plan );

			$sites = self::get_all_sites( $this->_module_type );

            if ( empty( $this->_storage->prev_user_id ) && $this->_user->id != $this->_site->user_id ) {
                /**
                 * Store the current user ID as the previous user ID so that the previous user can be used
                 * as the install's owner while the new owner's details are not yet available.
                 *
                 * This will be executed only in the `replica` site. For example, there are 2 sites, namely `original`
                 * and `replica`, then an ownership change was initiated and completed in the `original`, the `replica`
                 * will be using the previous user until it is updated again (e.g.: until the next clone of `original`
                 * into `replica`.
                 *
                 * @author Leo Fajardo (@leorw)
                 */
                $this->_storage->prev_user_id = $sites[ $this->_slug ]->user_id;
            }

			$sites[ $this->_slug ] = $encrypted_site;

			$this->set_account_option( 'sites', $sites, $store );
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

			$plans = self::get_all_plans( $this->_module_type );

			// Copy plans.
			$encrypted_plans = array();
			for ( $i = 0, $len = count( $this->_plans ); $i < $len; $i ++ ) {
				$encrypted_plans[] = self::_encrypt_entity( $this->_plans[ $i ] );
			}

			$plans[ $this->_slug ] = $encrypted_plans;

			$this->set_account_option( 'plans', $plans, $store );
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

			$all_licenses = self::get_all_licenses( $this->_module_type );

			if ( ! is_string( $plugin_slug ) ) {
				$plugin_slug = $this->_slug;
				$licenses    = $this->_licenses;
			}

			if ( ! isset( $all_licenses[ $plugin_slug ] ) ) {
				$all_licenses[ $plugin_slug ] = array();
			}

			$all_licenses[ $plugin_slug ][ $this->_user->id ] = $licenses;

			$this->set_account_option( 'licenses', $all_licenses, $store );
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

			if ( empty( $this->_user->id ) ) {
				$this->_logger->error( "Empty user ID, can't store user." );

				return;
			}

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

				$is_menu_item_account_visible = $this->is_submenu_item_visible( 'account' );

				if ( $user->is_verified &&
				     ( ! isset( $this->_user->is_verified ) || false === $this->_user->is_verified )
				) {
					$this->_user->is_verified = true;

					$this->do_action( 'account_email_verified', $user->email );

					$this->_admin_notices->add(
						$this->get_text_inline( 'Your email has been successfully verified - you are AWESOME!', 'email-verified-message' ),
						$this->get_text_x_inline( 'Right on', 'a positive response', 'right-on' ) . '!',
						'success',
						// Make admin sticky if account menu item is invisible,
						// since the page will be auto redirected to the plugin's
						// main settings page, and the non-sticky message
						// will disappear.
						! $is_menu_item_account_visible,
						false,
						'email_verified'
					);
				}

				// Flush user details to DB.
				$this->_store_user();

				$this->do_action( 'after_account_user_sync', $user );

				/**
				 * If account menu item is hidden, redirect to plugin's main settings page.
				 *
				 * @author Vova Feldman (@svovaf)
				 * @since  1.1.6
				 *
				 * @link   https://github.com/Freemius/wordpress-sdk/issues/6
				 */
				if ( ! $is_menu_item_account_visible ) {
					fs_redirect( $this->_get_admin_page_url() );
				}
			}
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

            /**
             * @since 1.2.3 When running in DEV mode, retrieve pending plans as well.
             */
            $result = $api->get( '/plans.json?show_pending=' . ( $this->has_secret_key() ? 'true' : 'false' ), true );

            if ( $this->is_api_result_object( $result, 'plans' ) && is_array( $result->plans ) ) {
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
		 * @param number|bool $site_license_id
		 *
		 * @return FS_Plugin_License[]|object
		 */
		private function _fetch_licenses( $plugin_id = false, $site_license_id = false ) {
			$this->_logger->entrance();

			$api = $this->get_api_user_scope();

			if ( ! is_numeric( $plugin_id ) ) {
				$plugin_id = $this->_plugin->id;
			}

			$result = $api->get( "/plugins/{$plugin_id}/licenses.json", true );

			$is_site_license_synced = false;

			$api_errors = array();

			if ( $this->is_api_result_object( $result, 'licenses' ) &&
			     is_array( $result->licenses )
			) {
				for ( $i = 0, $len = count( $result->licenses ); $i < $len; $i ++ ) {
					$result->licenses[ $i ] = new FS_Plugin_License( $result->licenses[ $i ] );

					if ( ( ! $is_site_license_synced ) && is_numeric( $site_license_id ) ) {
						$is_site_license_synced = ( $site_license_id == $result->licenses[ $i ]->id );
					}
				}

				$result = $result->licenses;
			} else {
				$api_errors[] = $result;
				$result       = array();
			}

			if ( ! $is_site_license_synced ) {
				$api = $this->get_api_site_scope();

				if ( is_numeric( $site_license_id ) ) {
					// Try to retrieve a foreign license that is linked to the install.
					$api_result = $api->call( '/licenses.json' );

					if ( $this->is_api_result_object( $api_result, 'licenses' ) &&
					     is_array( $api_result->licenses )
					) {
						$licenses = $api_result->licenses;

						if ( ! empty( $licenses ) ) {
							$result[] = new FS_Plugin_License( $licenses[0] );
						}
					} else {
						$api_errors[] = $api_result;
					}
				} else if ( is_object( $this->_license ) ) {
					// Fetch foreign license by ID and license key.
					$license = $api->get( "/licenses/{$this->_license->id}.json?license_key=" .
					                      urlencode( $this->_license->secret_key ) );

					if ( $this->is_api_result_entity( $license ) ) {
						$result[] = new FS_Plugin_License( $license );
					} else {
						$api_errors[] = $license;
					}
				}
			}

			if ( is_array( $result ) && 0 < count( $result ) ) {
				// If found at least one license, return license collection even if there are errors.
				return $result;
			}

			if ( ! empty( $api_errors ) ) {
				// If found any errors and no licenses, return first error.
				return $api_errors[0];
			}

			// Fallback to empty licenses list.
			return $result;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.0
		 * @uses   FS_Api
		 *
		 * @param number|bool $plugin_id
		 * @param bool        $flush
		 *
		 * @return FS_Payment[]|object
		 */
		function _fetch_payments( $plugin_id = false, $flush = false ) {
			$this->_logger->entrance();

			$api = $this->get_api_user_scope();

			if ( ! is_numeric( $plugin_id ) ) {
				$plugin_id = $this->_plugin->id;
			}

			$result = $api->get( "/plugins/{$plugin_id}/payments.json?include_addons=true", $flush );

			if ( ! isset( $result->error ) ) {
				for ( $i = 0, $len = count( $result->payments ); $i < $len; $i ++ ) {
					$result->payments[ $i ] = new FS_Payment( $result->payments[ $i ] );
				}
				$result = $result->payments;
			}

			return $result;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 * @uses   FS_Api
		 *
		 * @param bool $flush
		 *
		 * @return \FS_Billing|mixed
		 */
		function _fetch_billing( $flush = false ) {
			require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-billing.php';

			$billing = $this->get_api_user_scope()->get( 'billing.json', $flush );

			if ( $this->is_api_result_entity( $billing ) ) {
				$billing = new FS_Billing( $billing );
			}

			return $billing;
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
		 * @param bool        $flush Since 1.1.7.3
		 * @param int         $expiration Since 1.2.2.7
		 *
		 * @return object|false New plugin tag info if exist.
		 */
		private function _fetch_newer_version( $plugin_id = false, $flush = true, $expiration = WP_FS__TIME_24_HOURS_IN_SEC ) {
			$latest_tag = $this->_fetch_latest_version( $plugin_id, $flush, $expiration );

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
		 * @param bool        $flush      Since 1.1.7.3
		 * @param int         $expiration Since 1.2.2.7
		 *
		 * @return bool|FS_Plugin_Tag
		 */
		function get_update( $plugin_id = false, $flush = true, $expiration = WP_FS__TIME_24_HOURS_IN_SEC ) {
			$this->_logger->entrance();

			if ( ! is_numeric( $plugin_id ) ) {
				$plugin_id = $this->_plugin->id;
			}

			$this->check_updates( true, $plugin_id, $flush, $expiration );
			$updates = $this->get_all_updates();

			return isset( $updates[ $plugin_id ] ) && is_object( $updates[ $plugin_id ] ) ? $updates[ $plugin_id ] : false;
		}

		/**
		 * Check if site assigned with active license.
		 *
		 * @author     Vova Feldman (@svovaf)
		 * @since      1.0.6
		 *
		 * @deprecated Please use has_active_valid_license() instead because license can be cancelled.
		 */
		function has_active_license() {
			return (
				is_object( $this->_license ) &&
				is_numeric( $this->_license->id ) &&
				! $this->_license->is_expired()
			);
		}

		/**
		 * Check if site assigned with active & valid (not expired) license.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1
		 */
		function has_active_valid_license() {
			return (
				is_object( $this->_license ) &&
				is_numeric( $this->_license->id ) &&
				$this->_license->is_active() &&
				$this->_license->is_valid()
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
		 * Check if user is a trial or have feature enabled license.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7
		 *
		 * @return bool
		 */
		function can_use_premium_code() {
			return $this->is_trial() || $this->has_features_enabled_license();
		}

		/**
		 * Checks if the current user can activate plugins or switch themes. Note that this method should only be used
		 * after the `init` action is triggered because it is using `current_user_can()` which is only functional after
		 * the context user is authenticated.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2
		 *
		 * @return bool
		 */
		function is_user_admin() {
			return ( $this->is_plugin() && current_user_can( 'activate_plugins' ) )
			       || ( $this->is_theme() && current_user_can( 'switch_themes' ) );
		}

		/**
		 * Sync site's plan.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @uses   FS_Api
		 *
		 * @param bool $background Hints the method if it's a background sync. If false, it means that was initiated by
		 *                         the admin.
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
			if ( $this->is_array_instanceof( $licenses, 'FS_Plugin_License' ) ) {
				$this->_update_licenses( $licenses, $addon->slug );

				if ( ! $this->is_addon_installed( $addon->id ) && FS_License_Manager::has_premium_license( $licenses ) ) {
					$plans_result = $this->get_api_site_or_plugin_scope()->get( "/addons/{$addon_id}/plans.json" );

					if ( ! isset( $plans_result->error ) ) {
						$plans = array();
						foreach ( $plans_result->plans as $plan ) {
							$plans[] = new FS_Plugin_Plan( $plan );
						}

						$this->_admin_notices->add_sticky(
							sprintf(
								( FS_Plan_Manager::instance()->has_free_plan( $plans ) ?
									$this->get_text_inline( 'Your %s Add-on plan was successfully upgraded.', 'addon-successfully-upgraded-message' ) :
									/* translators: %s:product name, e.g. Facebook add-on was successfully... */
									$this->get_text_inline( '%s Add-on was successfully purchased.', 'addon-successfully-purchased-message' ) ),
								$addon->title
							) . ' ' . $this->get_latest_download_link(
								$this->get_text_inline( 'Download the latest version', 'download-latest-version' ),
								$addon_id
							),
							'addon_plan_upgraded_' . $addon->slug,
							$this->get_text_x_inline( 'Yee-haw', 'interjection expressing joy or exuberance', 'yee-haw' ) . '!'
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
		 * @param bool $background Hints the method if it's a background sync. If false, it means that was initiated by
		 *                         the admin.
		 */
		private function _sync_plugin_license( $background = false ) {
			$this->_logger->entrance();

			/**
			 * Sync site info.
			 *
			 * @todo This line will execute install sync on a daily basis, even if running the free version (for opted-in users). The reason we want to keep it that way is for cases when the user was a paying customer, then there was a failure in subscription payment, and then after some time the payment was successful. This could be heavily optimized. For example, we can skip the $flush if the current install was never associated with a paid version.
			 */
			$site = $this->send_install_update( array(), true );

			$plan_change = 'none';

			if ( ! $this->is_api_result_entity( $site ) ) {
				// Show API messages only if not background sync or if paying customer.
				if ( ! $background || $this->is_paying() ) {
					// Try to ping API to see if not blocked.
					if ( ! FS_Api::test() ) {
						/**
						 * Failed to ping API - blocked!
						 *
						 * @author Vova Feldman (@svovaf)
						 * @since  1.1.6 Only show message related to one of the Freemius powered plugins. Once it will be resolved it will fix the issue for all plugins anyways. There's no point to scare users with multiple error messages.
						 */
						$api = $this->get_api_site_scope();

						if ( ! self::$_global_admin_notices->has_sticky( 'api_blocked' ) ) {
							self::$_global_admin_notices->add(
								sprintf(
									$this->get_text_x_inline( 'Your server is blocking the access to Freemius\' API, which is crucial for %1s synchronization. Please contact your host to whitelist %2s', '%1s - plugin title, %2s - API domain', 'server-blocking-access' ),
									$this->get_plugin_name(),
									'<a href="' . $api->get_url() . '" target="_blank">' . $api->get_url() . '</a>'
								) . '<br> ' . $this->get_text_inline( 'Error received from the server:', 'server-error-message' ) . var_export( $site->error, true ),
								$this->get_text_x_inline( 'Oops', 'exclamation', 'oops' ) . '...',
								'error',
								$background,
								false,
								'api_blocked'
							);
						}
					} else {
						// Authentication params are broken.
						$this->_admin_notices->add(
							$this->get_text_inline( 'It seems like one of the authentication parameters is wrong. Update your Public Key, Secret Key & User ID, and try again.', 'wrong-authentication-param-message' ),
							$this->get_text_x_inline( 'Oops', 'exclamation', 'oops' ) . '...',
							'error'
						);
					}
				}

				// No reason to continue with license sync while there are API issues.
				return;
			}

			// Remove sticky API connectivity message.
			self::$_global_admin_notices->remove_sticky( 'api_blocked' );

			$site = new FS_Site( $site );

			// Sync plans.
			$this->_sync_plans();

			if ( ! $this->has_paid_plan() ) {
				$this->_site = $site;
				$this->_enrich_site_plan( true );
				$this->_store_site();
			} else {
				/**
				 * Sync licenses. Pass the site's license ID so that the foreign licenses will be fetched if the license
				 * associated with that ID is not included in the user's licenses collection.
				 */
				$this->_sync_licenses( $site->license_id );

				// Check if plan / license changed.
				if ( ! FS_Entity::equals( $site->plan, $this->_site->plan ) ||
				     // Check if trial started.
				     $site->trial_plan_id != $this->_site->trial_plan_id ||
				     $site->trial_ends != $this->_site->trial_ends ||
				     // Check if license changed.
				     $site->license_id != $this->_site->license_id
				) {
					if ( $site->is_trial() && ( ! $this->_site->is_trial() || $site->trial_ends != $this->_site->trial_ends ) ) {
						// New trial started.
						$this->_site = $site;
						$plan_change = 'trial_started';

						// Store trial plan information.
						$this->_enrich_site_trial_plan( true );

						// For trial with subscription use-case.
						$new_license = is_null( $site->license_id ) ? null : $this->_get_license_by_id( $site->license_id );

						if ( is_object( $new_license ) && $new_license->is_valid() ) {
							$this->_site = $site;
							$this->_update_site_license( $new_license );
							$this->_store_licenses();
							$this->_enrich_site_plan( true );

							$this->_sync_site_subscription( $this->_license );
						}
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
						$new_license = is_null( $site->license_id ) ?
							null :
							$this->_get_license_by_id( $site->license_id );

						if ( $is_free && is_null( $new_license ) && $this->has_any_license() && $this->_license->is_cancelled ) {
							// License cancelled.
							$this->_site = $site;
							$this->_update_site_license( $new_license );
							$this->_store_licenses();
							$this->_enrich_site_plan( true );

							$plan_change = 'cancelled';
						} else if ( $is_free && ( ( ! is_object( $new_license ) || $new_license->is_expired() ) ) ) {
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

			$hmm_text = $this->get_text_x_inline( 'Hmm', 'something somebody says when they are thinking about what you have just said.', 'hmm' ) . '...';

			if ( $this->has_paid_plan() ) {
				switch ( $plan_change ) {
					case 'none':
						if ( ! $background && is_admin() ) {
							$plan = $this->is_trial() ?
								$this->_storage->trial_plan :
								$this->_site->plan;

							if ( $plan->is_free() ) {
								$this->_admin_notices->add(
									sprintf(
										$this->get_text_inline( 'It looks like you are still on the %s plan. If you did upgrade or change your plan, it\'s probably an issue on our side - sorry.', 'plan-did-not-change-message' ),
										'<i><b>' . $plan->title . ( $this->is_trial() ? ' ' . $this->get_text_x_inline( 'Trial', 'trial period', 'trial' ) : '' ) . '</b></i>'
									) . ' ' . sprintf(
										'<a href="%s">%s</a>',
										$this->contact_url(
											'bug',
											sprintf( $this->get_text_inline( 'I have upgraded my account but when I try to Sync the License, the plan remains %s.', 'plan-did-not-change-email-message' ),
												strtoupper( $plan->name )
											)
										),
										$this->get_text_inline( 'Please contact us here', 'contact-us-here' )
									),
									$hmm_text
								);
							}
						}
						break;
					case 'upgraded':
						$this->_admin_notices->add_sticky(
							sprintf(
								$this->get_text_inline( 'Your plan was successfully upgraded.', 'plan-upgraded-message' ),
								'<i>' . $this->get_plugin_name() . '</i>'
							) . $this->get_complete_upgrade_instructions(),
							'plan_upgraded',
							$this->get_text_x_inline( 'Yee-haw', 'interjection expressing joy or exuberance', 'yee-haw' ) . '!'
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
								$this->get_text_inline( 'Your plan was successfully changed to %s.', 'plan-changed-to-x-message' ),
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
							sprintf( $this->get_text_inline( 'Your license has expired. You can still continue using the free %s forever.', 'license-expired-blocking-message' ), $this->_module_type ),
							'license_expired',
							$hmm_text
						);
						$this->_admin_notices->remove_sticky( 'plan_upgraded' );
						break;
					case 'cancelled':
						$this->_admin_notices->add(
							$this->get_text_inline( 'Your license has been cancelled. If you think it\'s a mistake, please contact support.', 'license-cancelled' ) . ' ' .
							sprintf(
								'<a href="%s">%s</a>',
								$this->contact_url( 'bug' ),
								$this->get_text_inline( 'Please contact us here', 'contact-us-here' )
							),
							$hmm_text,
							'error'
						);
						$this->_admin_notices->remove_sticky( 'plan_upgraded' );
						break;
					case 'expired':
						$this->_admin_notices->add_sticky(
							sprintf( $this->get_text_inline( 'Your license has expired. You can still continue using all the %s features, but you\'ll need to renew your license to continue getting updates and support.', 'license-expired-non-blocking-message' ), $this->_site->plan->title ),
							'license_expired',
							$hmm_text
						);
						$this->_admin_notices->remove_sticky( 'plan_upgraded' );
						break;
					case 'trial_started':
						$this->_admin_notices->add_sticky(
							sprintf(
								$this->get_text_inline( 'Your trial has been successfully started.', 'trial-started-message' ),
								'<i>' . $this->get_plugin_name() . '</i>'
							) . $this->get_complete_upgrade_instructions( $this->_storage->trial_plan->title ),
							'trial_started',
							$this->get_text_x_inline( 'Yee-haw', 'interjection expressing joy or exuberance', 'yee-haw' ) . '!'
						);

						$this->_admin_notices->remove_sticky( array(
							'trial_promotion',
						) );
						break;
					case 'trial_expired':
						$this->_admin_notices->add_sticky(
							$this->get_text_inline( 'Your trial has expired. You can still continue using all our free features.', 'trial-expired-message' ),
							'trial_expired',
							$hmm_text
						);
						$this->_admin_notices->remove_sticky( array(
							'trial_started',
							'trial_promotion',
							'plan_upgraded',
						) );
						break;
				}
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

			$license_id = fs_request_get( 'license_id' );

			if ( FS_Plugin_License::is_valid_id( $license_id ) && $license_id == $this->_site->license_id ) {
				// License is already activated.
				return;
			}

			$premium_license = FS_Plugin_License::is_valid_id( $license_id ) ?
				$this->_get_license_by_id( $license_id ) :
				$this->_get_available_premium_license();

			if ( ! is_object( $premium_license ) ) {
				return;
			}

			/**
			 * If the premium license is already associated with the install, just
			 * update the license reference (activation is not required).
			 *
			 * @since 1.1.9
			 */
			if ( $premium_license->id == $this->_site->license_id ) {
				// License is already activated.
				$this->_update_site_license( $premium_license );
				$this->_enrich_site_plan( false );
				$this->_store_account();

				return;
			}

			if ( $this->_site->user_id != $premium_license->user_id ) {
				$api_request_params = array( 'license_key' => $premium_license->secret_key );
			} else {
				$api_request_params = array();
			}

			$api     = $this->get_api_site_scope();
			$license = $api->call( "/licenses/{$premium_license->id}.json", 'put', $api_request_params );

			if ( ! $this->is_api_result_entity( $license ) ) {
				if ( ! $background ) {
					$this->_admin_notices->add( sprintf(
						'%s %s',
						$this->get_text_inline( 'It looks like the license could not be activated.', 'license-activation-failed-message' ),
						( is_object( $license ) && isset( $license->error ) ?
							$license->error->message :
							sprintf( '%s<br><code>%s</code>',
								$this->get_text_inline( 'Error received from the server:', 'server-error-message' ),
								var_export( $license, true )
							)
						)
					),
						$this->get_text_x_inline( 'Hmm', 'something somebody says when they are thinking about what you have just said.', 'hmm' ) . '...',
						'error'
					);
				}

				return;
			}
			$premium_license = new FS_Plugin_License( $license );

			// Updated site plan.
			$site = $this->get_api_site_scope()->get( '/', true );
			if ( $this->is_api_result_entity( $site ) ) {
				$this->_site = new FS_Site( $site );
			}
			$this->_update_site_license( $premium_license );
			$this->_enrich_site_plan( false );

			$this->_store_account();

			if ( ! $background ) {
				$this->_admin_notices->add_sticky(
					$this->get_text_inline( 'Your license was successfully activated.', 'license-activated-message' ) .
					$this->get_complete_upgrade_instructions(),
					'license_activated',
					$this->get_text_x_inline( 'Yee-haw', 'interjection expressing joy or exuberance', 'yee-haw' ) . '!'
				);
			}

			$this->_admin_notices->remove_sticky( array(
				'trial_promotion',
				'license_expired',
			) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @param bool $show_notice
		 */
		protected function _deactivate_license( $show_notice = true ) {
			$this->_logger->entrance();

			$hmm_text = $this->get_text_x_inline( 'Hmm', 'something somebody says when they are thinking about what you have just said.', 'hmm' ) . '...';

			if ( ! is_object( $this->_license ) ) {
				$this->_admin_notices->add(
					sprintf( $this->get_text_inline( 'It looks like your site currently doesn\'t have an active license.', 'no-active-license-message' ), $this->_site->plan->title ),
					$hmm_text
				);

				return;
			}

			$api     = $this->get_api_site_scope();
			$license = $api->call( "/licenses/{$this->_site->license_id}.json", 'delete' );

			if ( isset( $license->error ) ) {
				$this->_admin_notices->add(
					$this->get_text_inline( 'It looks like the license deactivation failed.', 'license-deactivation-failed-message' ) . '<br> ' .
					$this->get_text_inline( 'Error received from the server:', 'server-error-message' ) . ' ' . var_export( $license->error, true ),
					$hmm_text,
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
					sprintf( $this->get_text_inline( 'Your license was successfully deactivated, you are back to the %s plan.', 'license-deactivation-message' ), $this->_site->plan->title ),
					$this->get_text_inline( 'O.K', 'ok' )
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
			if ( $this->is_api_result_entity( $site ) ) {
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
					sprintf( $this->get_text_inline( 'Your plan was successfully downgraded. Your %s plan license will expire in %s.', 'plan-x-downgraded-message' ),
						$plan->title,
						human_time_diff( time(), strtotime( $this->_license->expiration ) )
					)
				);

				// Store site updates.
				$this->_store_site();
			} else {
				$this->_admin_notices->add(
					$this->get_text_inline( 'Seems like we are having some temporary issue with your plan downgrade. Please try again in few minutes.', 'plan-downgraded-failure-message' ),
					$this->get_text_x_inline( 'Oops', 'exclamation', 'oops' ) . '...',
					'error'
				);
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.8.1
		 *
		 * @param bool|string $plan_name
		 *
		 * @return bool If trial was successfully started.
		 */
		function start_trial( $plan_name = false ) {
			$this->_logger->entrance();

			// Alias.
			$oops_text = $this->get_text_x_inline( 'Oops', 'exclamation', 'oops' ) . '...';

			if ( $this->is_trial() ) {
				// Already in trial mode.
				$this->_admin_notices->add(
					sprintf( $this->get_text_inline( 'You are already running the %s in a trial mode.', 'in-trial-mode' ), $this->_module_type ),
					$oops_text,
					'error'
				);

				return false;
			}

			if ( $this->_site->is_trial_utilized() ) {
				// Trial was already utilized.
				$this->_admin_notices->add(
					$this->get_text_inline( 'You already utilized a trial before.', 'trial-utilized' ),
					$oops_text,
					'error'
				);

				return false;
			}

			if ( false !== $plan_name ) {
				$plan = $this->get_plan_by_name( $plan_name );

				if ( false === $plan ) {
					// Plan doesn't exist.
					$this->_admin_notices->add(
						sprintf( $this->get_text_inline( 'Plan %s do not exist, therefore, can\'t start a trial.', 'trial-plan-x-not-exist' ), $plan_name ),
						$oops_text,
						'error'
					);

					return false;
				}

				if ( ! $plan->has_trial() ) {
					// Plan doesn't exist.
					$this->_admin_notices->add(
						sprintf( $this->get_text_inline( 'Plan %s does not support a trial period.', 'plan-x-no-trial' ), $plan_name ),
						$oops_text,
						'error'
					);

					return false;
				}
			} else {
				if ( ! $this->has_trial_plan() ) {
					// None of the plans have a trial.
					$this->_admin_notices->add(
						sprintf( $this->get_text_inline( 'None of the %s\'s plans supports a trial period.', 'no-trials' ), $this->_module_type ),
						$oops_text,
						'error'
					);

					return false;
				}

				$plans_with_trial = FS_Plan_Manager::instance()->get_trial_plans( $this->_plans );

				$plan = $plans_with_trial[0];
			}

			$api  = $this->get_api_site_scope();
			$plan = $api->call( "plans/{$plan->id}/trials.json", 'post' );

			if ( ! $this->is_api_result_entity( $plan ) ) {
				// Some API error while trying to start the trial.
				$this->_admin_notices->add(
					sprintf( $this->get_text_inline( 'Unexpected API error. Please contact the %s\'s author with the following error.', 'unexpected-api-error' ), $this->_module_type )
					. ' ' . var_export( $plan, true ),
					$oops_text,
					'error'
				);

				return false;
			}

			// Sync license.
			$this->_sync_license();

			return $this->is_trial();
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

			// Alias.
			$oops_text = $this->get_text_x_inline( 'Oops', 'exclamation', 'oops' ) . '...';

			if ( ! $this->is_trial() ) {
				$this->_admin_notices->add(
					$this->get_text_inline( 'It looks like you are not in trial mode anymore so there\'s nothing to cancel :)', 'trial-cancel-no-trial-message' ),
					$oops_text,
					'error'
				);

				return;
			}

			$api  = $this->get_api_site_scope();
			$site = $api->call( 'trials.json', 'delete' );

			$trial_cancelled = false;

			if ( $this->is_api_result_entity( $site ) ) {
				$prev_trial_ends = $this->_site->trial_ends;

				if ( $this->is_paid_trial() ) {
					$this->_license->expiration   = $site->trial_ends;
					$this->_license->is_cancelled = true;
					$this->_update_site_license( $this->_license );
					$this->_store_licenses();

					// Clear subscription reference.
					$this->_sync_site_subscription( null );
				}

				// Update site info.
				$this->_site = new FS_Site( $site );
				$this->_enrich_site_plan();

				$trial_cancelled = ( $prev_trial_ends != $site->trial_ends );
			} else {
				// handle different error cases.

			}

			if ( $trial_cancelled ) {
				// Remove previous sticky messages about upgrade or trial (if exist).
				$this->_admin_notices->remove_sticky( array(
					'trial_started',
					'trial_promotion',
					'plan_upgraded',
				) );

				// Store site updates.
				$this->_store_site();

				if ( ! $this->is_addon() ||
				     ! $this->deactivate_premium_only_addon_without_license( true )
				) {
					$this->_admin_notices->add(
						sprintf( $this->get_text_inline( 'Your %s free trial was successfully cancelled.', 'trial-cancel-message' ), $this->_storage->trial_plan->title )
					);
				}

				// Clear trial plan information.
				unset( $this->_storage->trial_plan );
			} else {
				$this->_admin_notices->add(
					$this->get_text_inline( 'Seems like we are having some temporary issue with your trial cancellation. Please try again in few minutes.', 'trial-cancel-failure-message' ),
					$oops_text,
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
			return $this->has_active_valid_license() ||
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

			// If add-on, then append add-on ID.
			$endpoint = ( $is_addon ? "/addons/$addon_id" : '' ) .
			            '/updates/latest.' . $type;

			// If add-on and not yet activated, try to fetch based on server licensing.
			if ( is_bool( $is_premium ) ) {
				$endpoint = add_query_arg( 'is_premium', json_encode( $is_premium ), $endpoint );
			}

			if ( $this->has_secret_key() ) {
				$endpoint = add_query_arg( 'type', 'all', $endpoint );
			}

			return $endpoint;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param bool|number $addon_id
		 * @param bool        $flush      Since 1.1.7.3
		 * @param int         $expiration Since 1.2.2.7
		 *
		 * @return object|false Plugin latest tag info.
		 */
		function _fetch_latest_version( $addon_id = false, $flush = true, $expiration = WP_FS__TIME_24_HOURS_IN_SEC ) {
			$this->_logger->entrance();

			/**
			 * @since 1.1.7.3 Check for plugin updates from Freemius only if opted-in.
			 * @since 1.1.7.4 Also check updates for add-ons.
			 */
			if ( ! $this->is_registered() &&
			     ! $this->_is_addon_id( $addon_id )
			) {
				return false;
			}

			$tag = $this->get_api_site_or_plugin_scope()->get(
				$this->_get_latest_version_endpoint( $addon_id, 'json' ),
				$flush,
				$expiration
			);

			$latest_version = ( is_object( $tag ) && isset( $tag->version ) ) ? $tag->version : 'couldn\'t get';

			$this->_logger->departure( 'Latest version ' . $latest_version );

			return ( is_object( $tag ) && isset( $tag->version ) ) ? $tag : false;
		}

		#----------------------------------------------------------------------------------
		#region Download Plugin
		#----------------------------------------------------------------------------------

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
		private function download_latest_directly( $plugin_id = false ) {
			$this->_logger->entrance();

			wp_redirect( $this->get_latest_download_api_url( $plugin_id ) );
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
		private function get_latest_download_api_url( $plugin_id = false ) {
			$this->_logger->entrance();

			return $this->get_api_site_scope()->get_signed_url(
				$this->_get_latest_version_endpoint( $plugin_id, 'zip' )
			);
		}

		/**
		 * Get payment invoice URL.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.0
		 *
		 * @param bool|number $payment_id
		 *
		 * @return string
		 */
		function _get_invoice_api_url( $payment_id = false ) {
			$this->_logger->entrance();

			return $this->get_api_user_scope()->get_signed_url(
				"/payments/{$payment_id}/invoice.pdf"
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
		private function get_latest_download_link( $label, $plugin_id = false ) {
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
		function _get_latest_download_local_url( $plugin_id = false ) {
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
		 * @param bool        $background Hints the method if it's a background updates check. If false, it means that
		 *                                was initiated by the admin.
		 * @param bool|number $plugin_id
		 * @param bool        $flush      Since 1.1.7.3
		 * @param int         $expiration Since 1.2.2.7
		 */
		private function check_updates(
			$background = false,
			$plugin_id = false,
			$flush = true,
			$expiration = WP_FS__TIME_24_HOURS_IN_SEC
		) {
			$this->_logger->entrance();

			// Check if there's a newer version for download.
			$new_version = $this->_fetch_newer_version( $plugin_id, $flush, $expiration );

			$update = null;
			if ( is_object( $new_version ) ) {
				$update = new FS_Plugin_Tag( $new_version );

				if ( ! $background ) {
					$this->_admin_notices->add(
						sprintf(
                            /* translators: %s: Numeric version number (e.g. '2.1.9' */
							$this->get_text_inline( 'Version %s was released.', 'version-x-released' ) . ' ' . $this->get_text_inline( 'Please download %s.', 'please-download-x' ),
							$update->version,
							sprintf(
								'<a href="%s" target="_blank">%s</a>',
								$this->get_account_url( 'download_latest' ),
								sprintf(
                                    /* translators: %s: plan name (e.g. latest "Professional" version) */
								    $this->get_text_inline( 'the latest %s version here', 'latest-x-version' ),
                                    $this->_site->plan->title
                                )
							)
						),
						$this->get_text_inline( 'New', 'new' ) . '!'
					);
				}
			} else if ( false === $new_version && ! $background ) {
				$this->_admin_notices->add(
					$this->get_text_inline( 'Seems like you got the latest release.', 'you-have-latest' ),
					$this->get_text_inline( 'You are all good!', 'you-are-good' )
				);
			}

			$this->_store_update( $update, true, $plugin_id );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param bool $flush Since 1.1.7.3 add 24 hour cache by default.
		 *
		 * @return FS_Plugin[]
		 *
		 * @uses   FS_Api
		 */
		private function sync_addons( $flush = false ) {
			$this->_logger->entrance();

			$api = $this->get_api_site_or_plugin_scope();

			/**
			 * @since 1.2.1
			 *
			 * If there's a cached version of the add-ons and not asking
			 * for a flush, just use the currently stored add-ons.
			 */
			if ( ! $flush && $api->is_cached( '/addons.json?enriched=true' ) ) {
				$addons = self::get_all_addons();

				return $addons[ $this->_plugin->id ];
			}

			$result = $api->get( '/addons.json?enriched=true', $flush );

			$addons = array();
			if ( $this->is_api_result_object( $result, 'plugins' ) &&
			     is_array( $result->plugins )
			) {
				for ( $i = 0, $len = count( $result->plugins ); $i < $len; $i ++ ) {
					$addons[ $i ] = new FS_Plugin( $result->plugins[ $i ] );
				}

				$this->_store_addons( $addons, true );
			}

			return $addons;
		}

		/**
		 * Handle user email update.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 * @uses   FS_Api
		 *
		 * @param string $new_email
		 *
		 * @return object
		 */
		private function update_email( $new_email ) {
			$this->_logger->entrance();


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

		#----------------------------------------------------------------------------------
		#region API Error Handling
		#----------------------------------------------------------------------------------

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.1
		 *
		 * @param mixed $result
		 *
		 * @return bool Is API result contains an error.
		 */
		private function is_api_error( $result ) {
			return FS_Api::is_api_error( $result );
		}

		/**
		 * Checks if given API result is a non-empty and not an error object.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 *
		 * @param mixed       $result
		 * @param string|null $required_property Optional property we want to verify that is set.
		 *
		 * @return bool
		 */
		function is_api_result_object( $result, $required_property = null ) {
			return FS_Api::is_api_result_object( $result, $required_property );
		}

		/**
		 * Checks if given API result is a non-empty entity object with non-empty ID.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 *
		 * @param mixed $result
		 *
		 * @return bool
		 */
		private function is_api_result_entity( $result ) {
			return FS_Api::is_api_result_entity( $result );
		}

		#endregion

		/**
		 * Make sure a given argument is an array of a specific type.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 *
		 * @param mixed  $array
		 * @param string $class
		 *
		 * @return bool
		 */
		private function is_array_instanceof( $array, $class ) {
			return ( is_array( $array ) && ( empty( $array ) || $array[0] instanceof $class ) );
		}

		/**
		 * Start install ownership change.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.1
		 * @uses   FS_Api
		 *
		 * @param string $candidate_email
		 *
		 * @return bool Is ownership change successfully initiated.
		 */
		private function init_change_owner( $candidate_email ) {
			$this->_logger->entrance();

			$api    = $this->get_api_site_scope();
			$result = $api->call( "/users/{$this->_user->id}.json", 'put', array(
				'email'             => $candidate_email,
				'after_confirm_url' => $this->_get_admin_page_url(
					'account',
					array( 'fs_action' => 'change_owner' )
				),
			) );

			return ! $this->is_api_error( $result );
		}

		/**
		 * Handle install ownership change.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.1
		 * @uses   FS_Api
		 *
		 * @return bool Was ownership change successfully complete.
		 */
		private function complete_change_owner() {
			$this->_logger->entrance();

			$site_result = $this->get_api_site_scope( true )->get();
			$site        = new FS_Site( $site_result );
			$this->_site = $site;

			$user     = new FS_User();
			$user->id = fs_request_get( 'user_id' );

			// Validate install's user and given user.
			if ( $user->id != $this->_site->user_id ) {
				return false;
			}

			$user->public_key = fs_request_get( 'user_public_key' );
			$user->secret_key = fs_request_get( 'user_secret_key' );

			// Fetch new user information.
			$this->_user = $user;
			$user_result = $this->get_api_user_scope( true )->get();
			$user        = new FS_User( $user_result );
			$this->_user = $user;

			$this->_set_account( $user, $site );

			return true;
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
		private function update_user_name() {
			$this->_logger->entrance();
			$name = fs_request_get( 'fs_user_name_' . $this->get_unique_affix(), '' );

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
		private function verify_email() {
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
				$this->_admin_notices->add( sprintf(
					$this->get_text_inline( 'Verification mail was just sent to %s. If you can\'t find it after 5 min, please check your spam box.', 'verification-email-sent-message' ),
					sprintf( '<a href="mailto:%1s">%2s</a>', esc_url( $this->_user->email ), $this->_user->email )
				) );
			} else {
				// handle different error cases.

			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.2
		 *
		 * @param array $params
		 *
		 * @return string
		 */
		function get_activation_url( $params = array() ) {
			if ( $this->is_addon() && $this->has_free_plan() ) {
				/**
				 * @author Vova Feldman (@svovaf)
				 * @since  1.2.1.7 Add-on's activation is the parent's module activation.
				 */
				return $this->get_parent_instance()->get_activation_url( $params );
			}

			return $this->apply_filters( 'connect_url', $this->_get_admin_page_url( '', $params ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 *
		 * @param array $params
		 *
		 * @return string
		 */
		function get_reconnect_url( $params = array() ) {
			$params['fs_action']       = 'reset_anonymous_mode';
			$params['fs_unique_affix'] = $this->get_unique_affix();

			return $this->get_activation_url( $params );
		}

		/**
		 * Get the URL of the page that should be loaded after the user connect
		 * or skip in the opt-in screen.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @param string $filter Filter name.
		 * @param array  $params Since 1.2.2.7
		 *
		 * @return string
		 */
		function get_after_activation_url( $filter, $params = array() ) {
			if ( $this->is_free_wp_org_theme() &&
			     fs_request_has( 'pending_activation' )
			) {
				$first_time_path = '';
			} else {
				$first_time_path = $this->_menu->get_first_time_path();
			}

			return add_query_arg( $params, $this->apply_filters(
				$filter,
				empty( $first_time_path ) ?
					$this->_get_admin_page_url() :
					$first_time_path
			) );
		}

		/**
		 * Handle account page updates / edits / actions.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 */
		private function _handle_account_edits() {
			if ( ! $this->is_user_admin() ) {
				return;
			}

			$plugin_id = fs_request_get( 'plugin_id', $this->get_id() );
			$action    = fs_get_action();

			// Alias.
			$oops_text = $this->get_text_x_inline( 'Oops', 'exclamation', 'oops' ) . '...';

			switch ( $action ) {
				case 'delete_account':
					check_admin_referer( $action );

					if ( $plugin_id == $this->get_id() ) {
						$this->delete_account_event();

						// Clear user and site.
						$this->_site = null;
						$this->_user = null;

						fs_redirect( $this->get_activation_url() );
					} else {
						if ( $this->is_addon_activated( $plugin_id ) ) {
							$fs_addon = self::get_instance_by_id( $plugin_id );
							$fs_addon->delete_account_event();

							fs_redirect( $this->_get_admin_page_url( 'account' ) );
						}
					}

					return;

				case 'downgrade_account':
					check_admin_referer( $action );

					if ( $plugin_id == $this->get_id() ) {
						$this->_downgrade_site();
					} else if ( $this->is_addon_activated( $plugin_id ) ) {
						$fs_addon = self::get_instance_by_id( $plugin_id );
						$fs_addon->_downgrade_site();
					}

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

                        if ( $this->is_only_premium() ) {
                            // Clear user and site.
                            $this->_site = null;
                            $this->_user = null;

                            fs_redirect( $this->get_activation_url() );
                        }
					} else {
						if ( $this->is_addon_activated( $plugin_id ) ) {
							$fs_addon = self::get_instance_by_id( $plugin_id );
							$fs_addon->_deactivate_license();
						}
					}

					return;

				case 'check_updates':
					check_admin_referer( $action );
					$this->check_updates();

					return;

				case 'change_owner':
					$state = fs_request_get( 'state', 'init' );
					switch ( $state ) {
						case 'init':
							$candidate_email = fs_request_get( 'candidate_email', '' );

							if ( $this->init_change_owner( $candidate_email ) ) {
								$this->_admin_notices->add( sprintf( $this->get_text_inline( 'Please check your mailbox, you should receive an email via %s to confirm the ownership change. From security reasons, you must confirm the change within the next 15 min. If you cannot find the email, please check your spam folder.', 'change-owner-request-sent-x' ), '<b>' . $this->_user->email . '</b>' ) );
							}
							break;
						case 'owner_confirmed':
							$candidate_email = fs_request_get( 'candidate_email', '' );

							$this->_admin_notices->add( sprintf( $this->get_text_inline( 'Thanks for confirming the ownership change. An email was just sent to %s for final approval.', 'change-owner-request_owner-confirmed' ), '<b>' . $candidate_email . '</b>' ) );
							break;
						case 'candidate_confirmed':
							if ( $this->complete_change_owner() ) {
								$this->_admin_notices->add_sticky(
									sprintf( $this->get_text_inline( '%s is the new owner of the account.', 'change-owner-request_candidate-confirmed' ), '<b>' . $this->_user->email . '</b>' ),
									'ownership_changed',
									$this->get_text_x_inline( 'Congrats', 'as congratulations', 'congrats' ) . '!'
								);
							} else {
								// @todo Handle failed ownership change message.
							}
							break;
					}

					return;

				case 'update_email':
					check_admin_referer( 'update_email' );

					$new_email = fs_request_get( 'fs_email_' . $this->get_unique_affix(), '' );
					$result    = $this->update_email( $new_email );

					if ( isset( $result->error ) ) {
						switch ( $result->error->code ) {
							case 'user_exist':
								$this->_admin_notices->add(
									$this->get_text_inline( 'Sorry, we could not complete the email update. Another user with the same email is already registered.', 'user-exist-message' ) . ' ' .
									sprintf( $this->get_text_inline( 'If you would like to give up the ownership of the %s\'s account to %s click the Change Ownership button.', 'user-exist-message_ownership' ), $this->_module_type, '<b>' . $new_email . '</b>' ) .
									sprintf(
										'<a style="margin-left: 10px;" href="%s"><button class="button button-primary">%s &nbsp;&#10140;</button></a>',
										$this->get_account_url( 'change_owner', array(
											'state'           => 'init',
											'candidate_email' => $new_email
										) ),
										$this->get_text_inline( 'Change Ownership', 'change-ownership' )
									),
									$oops_text,
									'error'
								);
								break;
						}
					} else {
						$this->_admin_notices->add( $this->get_text_inline( 'Your email was successfully updated. You should receive an email with confirmation instructions in few moments.', 'email-updated-message' ) );
					}

					return;

				case 'update_user_name':
					check_admin_referer( 'update_user_name' );

					$result = $this->update_user_name();

					if ( isset( $result->error ) ) {
						$this->_admin_notices->add(
							$this->get_text_inline( 'Please provide your full name.', 'name-update-failed-message' ),
							$oops_text,
							'error'
						);
					} else {
						$this->_admin_notices->add( $this->get_text_inline( 'Your name was successfully updated.', 'name-updated-message' ) );
					}

					return;

				#region Actions that might be called from external links (e.g. email)

				case 'cancel_trial':
					if ( $plugin_id == $this->get_id() ) {
						$this->_cancel_trial();
					} else {
						if ( $this->is_addon_activated( $plugin_id ) ) {
							$fs_addon = self::get_instance_by_id( $plugin_id );
							$fs_addon->_cancel_trial();
						}
					}

					return;

				case 'verify_email':
					$this->verify_email();

					return;

				case 'sync_user':
					$this->_handle_account_user_sync();

					return;

				case $this->get_unique_affix() . '_sync_license':
					$this->_sync_license();

					return;

				case 'download_latest':
					$this->download_latest_directly( $plugin_id );

					return;

				#endregion
			}

			if ( WP_FS__IS_POST_REQUEST ) {
				$properties = array( 'site_secret_key', 'site_id', 'site_public_key' );
				foreach ( $properties as $p ) {
					if ( 'update_' . $p === $action ) {
						check_admin_referer( $action );

						$this->_logger->log( $action );

						$site_property                      = substr( $p, strlen( 'site_' ) );
						$site_property_value                = fs_request_get( 'fs_' . $p . '_' . $this->get_unique_affix(), '' );
						$this->get_site()->{$site_property} = $site_property_value;

						// Store account after modification.
						$this->_store_site();

						$this->do_action( 'account_property_edit', 'site', $site_property, $site_property_value );

						$this->_admin_notices->add( sprintf(
							/* translators: %s: User's account property (e.g. email address, name) */
							$this->get_text_inline( 'You have successfully updated your %s.', 'x-updated' ),
							'<b>' . str_replace( '_', ' ', $p ) . '</b>'
						) );

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

			if ( $this->has_addons() ) {
				wp_enqueue_script( 'plugin-install' );
				add_thickbox();

				function fs_addons_body_class( $classes ) {
					$classes .= ' plugins-php';

					return $classes;
				}

				add_filter( 'admin_body_class', 'fs_addons_body_class' );
			}

			if ( $this->has_paid_plan() &&
			     ! $this->has_any_license() &&
			     ! $this->is_sync_executed() &&
			     $this->is_tracking_allowed()
			) {
				/**
				 * If no licenses found and no sync job was executed during the last 24 hours,
				 * just execute the sync job right away (blocking execution).
				 *
				 * @since 1.1.7.3
				 */
				$this->run_manual_sync();
			}

			$this->_handle_account_edits();

			$this->do_action( 'account_page_load_before_departure' );
		}

		/**
		 * Renders the "Affiliation" page.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.3
		 */
		function _affiliation_page_render() {
			$this->_logger->entrance();

            fs_enqueue_local_style( 'fs_affiliation', '/admin/affiliation.css' );

            $vars = array( 'id' => $this->_module_id );
			echo $this->apply_filters( "/forms/affiliation.php", fs_get_template( '/forms/affiliation.php', $vars ) );
		}


		/**
		 * Render account page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 */
		function _account_page_render() {
			$this->_logger->entrance();

			$template = 'account.php';
			$vars     = array( 'id' => $this->_module_id );

			/**
			 * Added filter to the template to allow developers wrapping the template
			 * in custom HTML (e.g. within a wizard/tabs).
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since  1.2.1.6
			 */
			echo $this->apply_filters( "templates/{$template}", fs_get_template( $template, $vars ) );
		}

		/**
		 * Render account connect page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 */
		function _connect_page_render() {
			$this->_logger->entrance();

			$vars = array( 'id' => $this->_module_id );

			/**
			 * Added filter to the template to allow developers wrapping the template
			 * in custom HTML (e.g. within a wizard/tabs).
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since  1.2.1.6
			 */
			echo $this->apply_filters( 'templates/connect.php', fs_get_template( 'connect.php', $vars ) );
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
					sprintf( $this->get_text_inline( 'Just letting you know that the add-ons information of %s is being pulled from an external server.', 'addons-info-external-message' ), '<b>' . $this->get_plugin_name() . '</b>' ),
					$this->get_text_x_inline( 'Heads up', 'advance notice of something that will need attention.', 'heads-up' ),
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

			$vars = array( 'id' => $this->_module_id );

			/**
			 * Added filter to the template to allow developers wrapping the template
			 * in custom HTML (e.g. within a wizard/tabs).
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since  1.2.1.6
			 */
			echo $this->apply_filters( 'templates/add-ons.php', fs_get_template( 'add-ons.php', $vars ) );
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

			$vars = array( 'id' => $this->_module_id );

			if ( 'true' === fs_request_get( 'checkout', false ) ) {
				fs_require_once_template( 'checkout.php', $vars );
			} else {
				fs_require_once_template( 'pricing.php', $vars );
			}
		}

		#----------------------------------------------------------------------------------
		#region Contact Us
		#----------------------------------------------------------------------------------

		/**
		 * Render contact-us page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 */
		function _contact_page_render() {
			$this->_logger->entrance();

			$vars = array( 'id' => $this->_module_id );
			fs_require_once_template( 'contact.php', $vars );
		}

		#endregion ------------------------------------------------------------------------

		/**
		 * Hide all admin notices to prevent distractions.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @uses   remove_all_actions()
		 */
		private static function _hide_admin_notices() {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'network_admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'user_admin_notices' );
		}

		static function _clean_admin_content_section_hook() {
			self::_hide_admin_notices();

			// Hide footer.
			echo '<style>#wpfooter { display: none !important; }</style>';
		}

		/**
		 * Attach to admin_head hook to hide all admin notices.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 */
		static function _clean_admin_content_section() {
			add_action( 'admin_head', 'Freemius::_clean_admin_content_section_hook' );
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
		 * @param bool $flush
		 *
		 * @return FS_Api
		 */
		function get_api_user_scope( $flush = false ) {
			if ( ! isset( $this->_user_api ) || $flush ) {
				$this->_user_api = FS_Api::instance(
					$this->_module_id,
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
		 * @param bool $flush
		 *
		 * @return FS_Api
		 */
		function get_api_site_scope( $flush = false ) {
			if ( ! isset( $this->_site_api ) || $flush ) {
				$this->_site_api = FS_Api::instance(
					$this->_module_id,
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
					$this->_module_id,
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
		 * During trial promotion the "upgrade" submenu item turns to
		 * "start trial" to encourage the trial. Since we want to keep
		 * the same menu item handler and there's no robust way to
		 * add new arguments to the menu item link's querystring,
		 * use JavaScript to find the menu item and update the href of
		 * the link.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 */
		function _fix_start_trial_menu_item_url() {
			$template_args = array( 'id' => $this->_module_id );
			fs_require_template( 'add-trial-to-pricing.php', $template_args );
		}

		/**
		 * Check if module is currently in a trial promotion mode.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return bool
		 */
		function is_in_trial_promotion() {
			return $this->_admin_notices->has_sticky( 'trial_promotion' );
		}

		/**
		 * Show trial promotional notice (if any trial exist).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool If trial notice added.
		 */
		function _add_trial_notice() {
			if ( ! $this->is_user_admin() ) {
				return false;
			}

			if ( ! $this->is_user_in_admin() ) {
				return false;
			}

			// Check if trial message is already shown.
			if ( $this->is_in_trial_promotion() ) {
				add_action( 'admin_footer', array( &$this, '_fix_start_trial_menu_item_url' ) );

				$this->_menu->add_counter_to_menu_item( 1, 'fs-trial' );

				return false;
			}

			if ( $this->is_premium() && ! WP_FS__DEV_MODE ) {
				// Don't show trial if running the premium code, unless running in DEV mode.
				return false;
			}

			if ( ! $this->has_trial_plan() ) {
				// No plans with trial.
				return false;
			}

			if ( ! $this->apply_filters( 'show_trial', true ) ) {
				// Developer explicitly asked not to show the trial promo.
				return false;
			}

			if ( $this->is_registered() ) {
				// Check if trial already utilized.
				if ( $this->_site->is_trial_utilized() ) {
					return false;
				}

				if ( $this->is_paying_or_trial() ) {
					// Don't show trial if paying or already in trial.
					return false;
				}
			}

			if ( $this->is_activation_mode() || $this->is_pending_activation() ) {
				// If not yet opted-in/skipped, or pending activation, don't show trial.
				return false;
			}

			$last_time_trial_promotion_shown = $this->_storage->get( 'trial_promotion_shown', false );
			$was_promotion_shown_before      = ( false !== $last_time_trial_promotion_shown );

			// Show promotion if never shown before and 24 hours after initial activation with FS.
			if ( ! $was_promotion_shown_before &&
			     $this->_storage->install_timestamp > ( time() - WP_FS__TIME_24_HOURS_IN_SEC )
			) {
				return false;
			}

			// OR if promotion was shown before, try showing it every 30 days.
			if ( $was_promotion_shown_before &&
			     30 * WP_FS__TIME_24_HOURS_IN_SEC > time() - $last_time_trial_promotion_shown
			) {
				return false;
			}

			$trial_period    = $this->_trial_days;
			$require_payment = $this->_is_trial_require_payment;
			$trial_url       = $this->get_trial_url();
			$plans_string    = strtolower( $this->get_text_inline( 'Awesome', 'awesome' ) );

			if ( $this->is_registered() ) {
				// If opted-in, override trial with up to date data from API.
				$trial_plans       = FS_Plan_Manager::instance()->get_trial_plans( $this->_plans );
				$trial_plans_count = count( $trial_plans );

				if ( 0 === $trial_plans_count ) {
					// If there's no plans with a trial just exit.
					return false;
				}

				/**
				 * @var FS_Plugin_Plan $paid_plan
				 */
				$paid_plan       = $trial_plans[0];
				$require_payment = $paid_plan->is_require_subscription;
				$trial_period    = $paid_plan->trial_period;

				$total_paid_plans = count( $this->_plans ) - ( FS_Plan_Manager::instance()->has_free_plan( $this->_plans ) ? 1 : 0 );

				if ( $total_paid_plans !== $trial_plans_count ) {
					// Not all paid plans have a trial - generate a string of those that have it.
					for ( $i = 0; $i < $trial_plans_count; $i ++ ) {
						$plans_string .= sprintf(
							' <a href="%s">%s</a>',
							$trial_url,
							$trial_plans[ $i ]->title
						);

						if ( $i < $trial_plans_count - 2 ) {
							$plans_string .= ', ';
						} else if ( $i == $trial_plans_count - 2 ) {
							$plans_string .= ' and ';
						}
					}
				}
			}

			$message = sprintf(
				$this->get_text_x_inline( 'Hey', 'exclamation', 'hey' ) . '! ' . $this->get_text_inline( 'How do you like %s so far? Test all our %s premium features with a %d-day free trial.', 'trial-x-promotion-message' ),
				sprintf( '<b>%s</b>', $this->get_plugin_name() ),
				$plans_string,
				$trial_period
			);

			// "No Credit-Card Required" or "No Commitment for N Days".
			$cc_string = $require_payment ?
				sprintf( $this->get_text_inline( 'No commitment for %s days - cancel anytime!', 'no-commitment-for-x-days' ), $trial_period ) :
				$this->get_text_inline( 'No credit card required', 'no-cc-required' ) . '!';


			// Start trial button.
			$button = ' ' . sprintf(
					'<a style="margin-left: 10px; vertical-align: super;" href="%s"><button class="button button-primary">%s &nbsp;&#10140;</button></a>',
					$trial_url,
					$this->get_text_x_inline( 'Start free trial', 'call to action', 'start-free-trial' )
				);

			$this->_admin_notices->add_sticky(
				$this->apply_filters( 'trial_promotion_message', "{$message} {$cc_string} {$button}" ),
				'trial_promotion',
				'',
				'promotion'
			);

			$this->_storage->trial_promotion_shown = WP_FS__SCRIPT_START_TIME;

			return true;
		}

		/**
		 * Lets users/customers know that the product has an affiliate program.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.2.11
		 *
		 * @return bool Returns true if the notice has been added.
		 */
		function _add_affiliate_program_notice() {
			if ( ! $this->is_user_admin() ) {
				return false;
			}

			if ( ! $this->is_user_in_admin() ) {
				return false;
			}

			// Check if the notice is already shown.
			if ( $this->_admin_notices->has_sticky( 'affiliate_program' ) ) {
				return false;
			}

            if (
                // Product has no affiliate program.
                ! $this->has_affiliate_program() ||
                // User is already an affiliate.
                is_object( $this->affiliate ) ||
                // User has applied for an affiliate account.
                ! empty( $this->_storage->affiliate_application_data ) ) {
                return false;
            }

            if ( ! $this->apply_filters( 'show_affiliate_program_notice', true ) ) {
                // Developer explicitly asked not to show the notice about the affiliate program.
                return false;
            }

            if ( $this->is_activation_mode() || $this->is_pending_activation() ) {
                // If not yet opted in/skipped, or pending activation, don't show the notice.
                return false;
            }

            $last_time_notice_was_shown = $this->_storage->get( 'affiliate_program_notice_shown', false );
            $was_notice_shown_before    = ( false !== $last_time_notice_was_shown );

            /**
             * Do not show the notice if it was already shown before or less than 30 days have passed since the initial
             * activation with FS.
             */
            if ( $was_notice_shown_before ||
                $this->_storage->install_timestamp > ( time() - ( WP_FS__TIME_24_HOURS_IN_SEC * 30 ) )
            ) {
                return false;
            }

			if ( ! $this->is_paying() &&
                FS_Plugin::AFFILIATE_MODERATION_CUSTOMERS == $this->_plugin->affiliate_moderation ) {
			    // If the user is not a customer and the affiliate program is only for customers, don't show the notice.
                return false;
			}

			$message = sprintf(
				$this->get_text_inline( 'Hey there, did you know that %s has an affiliate program? If you like the %s you can become our ambassador and earn some cash!', 'become-an-ambassador-admin-notice' ),
				sprintf( '<strong>%s</strong>', $this->get_plugin_name() ),
				$this->get_module_label( true )
			);

			// HTML code for the "Learn more..." button.
			$button = ' ' . sprintf(
					'<a style="display: block; margin-top: 10px;" href="%s"><button class="button button-primary">%s &nbsp;&#10140;</button></a>',
                    $this->_get_admin_page_url( 'affiliation' ),
					$this->get_text_inline( 'Learn more', 'learn-more' ) . '...'
				);

			$this->_admin_notices->add_sticky(
				$this->apply_filters( 'affiliate_program_notice', "{$message} {$button}" ),
				'affiliate_program',
				'',
				'promotion'
			);

			$this->_storage->affiliate_program_notice_shown = WP_FS__SCRIPT_START_TIME;

			return true;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 */
		function _enqueue_common_css() {
			if ( $this->has_paid_plan() && ! $this->is_paying() ) {
				// Add basic CSS for admin-notices and menu-item colors.
				fs_enqueue_local_style( 'fs_common', '/admin/common.css' );
			}
		}

		/**
		 * @author Leo Fajardo (leorw)
		 * @since  1.2.2
		 */
		function _show_theme_activation_optin_dialog() {
			fs_enqueue_local_style( 'fs_connect', '/admin/connect.css' );

			add_action( 'admin_footer-themes.php', array( &$this, '_add_fs_theme_activation_dialog' ) );
		}

		/**
		 * @author Leo Fajardo (leorw)
		 * @since  1.2.2
		 */
		function _add_fs_theme_activation_dialog() {
			$vars = array( 'id' => $this->_module_id );
			fs_require_once_template( 'connect.php', $vars );
		}

		/* Action Links
		------------------------------------------------------------------------------------------------------------------*/
		private $_action_links_hooked = false;
		private $_action_links = array();

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
			), WP_FS__DEFAULT_PRIORITY, 2 );
			add_filter( 'network_admin_plugin_action_links_' . $this->_plugin_basename, array(
				&$this,
				'_modify_plugin_action_links_hook'
			), WP_FS__DEFAULT_PRIORITY, 2 );
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
		function add_plugin_action_link( $label, $url, $external = false, $priority = WP_FS__DEFAULT_PRIORITY, $key = false ) {
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
						$this->get_text_inline( 'Upgrade', 'upgrade' ),
						$this->get_upgrade_url(),
						false,
						7,
						'upgrade'
					);
				}

				if ( $this->has_addons() ) {
					$this->add_plugin_action_link(
						$this->get_text_inline( 'Add-Ons', 'add-ons' ),
						$this->_get_admin_page_url( 'addons' ),
						false,
						9,
						'addons'
					);
				}
			}
		}

		/**
		 * Adds "Activate License" or "Change License" link to the main Plugins page link actions collection.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.1.9
		 */
		function _add_license_action_link() {
			$this->_logger->entrance();

			if ( $this->is_free_plan() && $this->is_addon() ) {
				return;
			}

			if ( ! self::is_ajax() ) {
				// Inject license activation dialog UI and client side code.
				add_action( 'admin_footer', array( &$this, '_add_license_activation_dialog_box' ) );
			}

			$link_text = $this->is_free_plan() ?
				$this->get_text_inline( 'Activate License', 'activate-license' ) :
				$this->get_text_inline( 'Change License', 'change-license' );

			$this->add_plugin_action_link(
				$link_text,
				'#',
				false,
				11,
				( 'activate-license ' . $this->get_unique_affix() )
			);
		}

		/**
		 * Adds "Opt in" or "Opt out" link to the main "Plugins" page link actions collection.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since  1.2.1.5
		 */
		function _add_tracking_links() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$this->_logger->entrance();

			if ( fs_request_is_action_secure( $this->get_unique_affix() . '_reconnect' ) ) {
				if ( ! $this->is_registered() && $this->is_anonymous() ) {
					$this->connect_again();

					return;
				}
			}

			if ( ( $this->is_plugin() && ! self::is_plugins_page() ) ||
			     ( $this->is_theme() && ! self::is_themes_page() )
			) {
				// Only show tracking links on the plugins and themes pages.
				return;
			}

			if ( ! $this->is_enable_anonymous() ) {
				// Don't allow to opt-out if anonymous mode is disabled.
				return;
			}

			if ( ! $this->is_free_plan() ) {
				// Don't allow to opt-out if running in paid plan.
				return;
			}

			if ( $this->add_ajax_action( 'stop_tracking', array( &$this, '_stop_tracking_callback' ) ) ) {
				return;
			}

			if ( $this->add_ajax_action( 'allow_tracking', array( &$this, '_allow_tracking_callback' ) ) ) {
				return;
			}

			$url = '#';

			if ( $this->is_registered() ) {
				if ( $this->is_tracking_allowed() ) {
					$link_text_id = $this->get_text_inline( 'Opt Out', 'opt-out' );
				} else {
					$link_text_id = $this->get_text_inline( 'Opt In', 'opt-in' );
				}

				add_action( 'admin_footer', array( &$this, '_add_optout_dialog' ) );
			} else {
				$link_text_id = $this->get_text_inline( 'Opt In', 'opt-in' );

				$params = ! $this->is_anonymous() ?
					array() :
					array(
						'nonce'     => wp_create_nonce( $this->get_unique_affix() . '_reconnect' ),
						'fs_action' => ( $this->get_unique_affix() . '_reconnect' ),
					);

				$url = $this->get_activation_url( $params );
			}

			if ( $this->is_plugin() && self::is_plugins_page() ) {
				$this->add_plugin_action_link(
                    $link_text_id,
					$url,
					false,
					13,
					"opt-in-or-opt-out {$this->_slug}"
				);
			}
		}

		/**
		 * Get the URL of the page that should be loaded right after the plugin activation.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 *
		 * @return string
		 */
		function get_after_plugin_activation_redirect_url() {
			$url       = false;

			if ( ! $this->is_addon() || ! $this->has_free_plan() ) {
				$first_time_path = $this->_menu->get_first_time_path();
				$url             = $this->is_activation_mode() ?
					$this->get_activation_url() :
					( empty( $first_time_path ) ?
						$this->_get_admin_page_url() :
						$first_time_path );
			} else {
				$plugin_fs = false;

				if ( $this->is_parent_plugin_installed() ) {
					$plugin_fs = self::get_parent_instance();
				}

				if ( is_object( $plugin_fs ) ) {
					if ( ! $plugin_fs->is_registered() ) {
						// Forward to parent plugin connect when parent not registered.
						$url = $plugin_fs->get_activation_url();
					} else {
						// Forward to account page.
						$url = $plugin_fs->_get_admin_page_url( 'account' );
					}
				}
			}

			return $url;
		}

		/**
		 * Forward page to activation page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 */
		function _redirect_on_activation_hook() {
			$url = $this->get_after_plugin_activation_redirect_url();

			if ( is_string( $url ) ) {
				fs_redirect( $url );
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

			$passed_deactivate = false;
			$deactivate_link   = '';
			$before_deactivate = array();
			$after_deactivate  = array();
			foreach ( $links as $key => $link ) {
				if ( 'deactivate' === $key ) {
					$deactivate_link   = $link;
					$passed_deactivate = true;
					continue;
				}

				if ( ! $passed_deactivate ) {
					$before_deactivate[ $key ] = $link;
				} else {
					$after_deactivate[ $key ] = $link;
				}
			}

			ksort( $this->_action_links );

			foreach ( $this->_action_links as $new_links ) {
				foreach ( $new_links as $link ) {
					$before_deactivate[ $link['key'] ] = '<a href="' . $link['href'] . '"' . ( $link['external'] ? ' target="_blank"' : '' ) . '>' . $link['label'] . '</a>';
				}
			}

			if ( ! empty( $deactivate_link ) ) {
				/**
				 * This HTML element is used to identify the correct plugin when attaching an event to its Deactivate link.
				 *
				 * @since 1.2.1.6 Always show the deactivation feedback form since we added automatic free version deactivation upon premium code activation.
				 */
				$deactivate_link .= '<i class="fs-module-id" data-module-id="' . $this->_module_id . '"></i>';

				// Append deactivation link.
				$before_deactivate['deactivate'] = $deactivate_link;
			}

			return array_merge( $before_deactivate, $after_deactivate );
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

		/**
		 * Adds sticky admin message.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.0
		 *
		 * @param string $message
		 * @param string $id
		 * @param string $title
		 * @param string $type
		 */
		function add_sticky_admin_message( $message, $id, $title = '', $type = 'success' ) {
			$this->_admin_notices->add_sticky( $message, $id, $title, $type );
		}

		/**
		 * Helper function that returns the final steps for the upgrade completion.
		 *
		 * If the module is already running the premium code, returns an empty string.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1
		 *
		 * @param string $plan_title
		 *
		 * @return string
		 */
		private function get_complete_upgrade_instructions( $plan_title = '' ) {
			if ( ! $this->has_premium_version() || $this->is_premium() ) {
				return '';
			}

			if ( empty( $plan_title ) ) {
				$plan_title = $this->_site->plan->title;
			}

			// @since 1.2.1.5 The free version is auto deactivated.
			$deactivation_step = version_compare( $this->version, '1.2.1.5', '<' ) ?
				( '<li>' . $this->esc_html_inline( 'Deactivate the free version', 'deactivate-free-version' ) . '.</li>' ) :
				'';

			return sprintf(
				' %s: <ol><li>%s.</li>%s<li>%s (<a href="%s" target="_blank">%s</a>).</li></ol>',
				$this->get_text_inline( 'Please follow these steps to complete the upgrade', 'follow-steps-to-complete-upgrade' ),
				$this->get_latest_download_link( sprintf(
					/* translators: %s: Plan title */
					$this->get_text_inline( 'Download the latest %s version', 'download-latest-x-version' ),
					$plan_title
				) ),
				$deactivation_step,
				$this->get_text_inline( 'Upload and activate the downloaded version', 'upload-and-activate' ),
				'//bit.ly/upload-wp-' . $this->_module_type . 's',
				$this->get_text_inline( 'How to upload and activate?', 'howto-upload-activate' )
			);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 *
		 * @param string $key
		 *
		 * @return string
		 */
		function get_text( $key ) {
			return fs_text( $key, $this->_slug );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.3
		 *
         * @param string $text Translatable string.
         * @param string $key  String key for overrides.
		 *
		 * @return string
		 */
		function get_text_inline( $text, $key = '' ) {
			return _fs_text_inline( $text, $key, $this->_slug );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.3
		 *
         * @param string $text Translatable string.
		 * @param string $context Context information for the translators.
         * @param string $key  String key for overrides.
		 *
		 * @return string
		 */
		function get_text_x_inline( $text, $context, $key ) {
			return _fs_text_x_inline( $text, $context, $key, $this->_slug );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.3
		 *
		 * @param string $text Translatable string.
		 * @param string $key  String key for overrides.
		 *
		 * @return string
		 */
		function esc_html_inline( $text, $key ) {
            return esc_html( _fs_text_inline( $text, $key, $this->_slug ) );
		}

		#----------------------------------------------------------------------------------
		#region Versioning
		#----------------------------------------------------------------------------------

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
		 */
		function set_plugin_upgrade_complete() {
			$this->_storage->plugin_upgrade_mode = false;
		}

		#endregion

		#----------------------------------------------------------------------------------
		#region Permissions
		#----------------------------------------------------------------------------------

		/**
		 * Check if specific permission requested.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.6
		 *
		 * @param string $permission
		 *
		 * @return bool
		 */
		function is_permission_requested( $permission ) {
			return isset( $this->_permissions[ $permission ] ) && ( true === $this->_permissions[ $permission ] );
		}

		#endregion

		#----------------------------------------------------------------------------------
		#region Auto Activation
		#----------------------------------------------------------------------------------

		/**
		 * Hints the SDK if running an auto-installation.
		 *
		 * @var bool
		 */
		private $_isAutoInstall = false;

		/**
		 * After upgrade callback to install and auto activate a plugin.
		 * This code will only be executed on explicit request from the user,
		 * following the practice Jetpack are using with their theme installations.
		 *
		 * @link   https://make.wordpress.org/plugins/2017/03/16/clarification-of-guideline-8-executable-code-and-installs/
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 */
		function _install_premium_version_ajax_action() {
			$this->_logger->entrance();

			$this->check_ajax_referer( 'install_premium_version' );

			if ( ! $this->is_registered() ) {
				// Not registered.
				self::shoot_ajax_failure( array(
					'message' => $this->get_text_inline( 'Auto installation only works for opted-in users.', 'auto-install-error-not-opted-in' ),
					'code'    => 'premium_installed',
				) );
			}

			$plugin_id = fs_request_get( 'target_module_id', $this->get_id() );

			if ( ! FS_Plugin::is_valid_id( $plugin_id ) ) {
				// Invalid ID.
				self::shoot_ajax_failure( array(
					'message' => $this->get_text_inline( 'Invalid module ID.', 'auto-install-error-invalid-id' ),
					'code'    => 'invalid_module_id',
				) );
			}

			if ( $plugin_id == $this->get_id() ) {
				if ( $this->is_premium() ) {
					// Already using the premium code version.
					self::shoot_ajax_failure( array(
						'message' => $this->get_text_inline( 'Premium version already active.', 'auto-install-error-premium-activated' ),
						'code'    => 'premium_installed',
					) );
				}
				if ( ! $this->can_use_premium_code() ) {
					// Don't have access to the premium code.
					self::shoot_ajax_failure( array(
						'message' => $this->get_text_inline( 'You do not have a valid license to access the premium version.', 'auto-install-error-invalid-license' ),
						'code'    => 'invalid_license',
					) );
				}
				if ( ! $this->has_release_on_freemius() ) {
					// Plugin is a serviceware, no premium code version.
					self::shoot_ajax_failure( array(
						'message' => $this->get_text_inline( 'Plugin is a "Serviceware" which means it does not have a premium code version.', 'auto-install-error-serviceware' ),
						'code'    => 'premium_version_missing',
					) );
				}
			} else {
				$addon = $this->get_addon( $plugin_id );

				if ( ! is_object( $addon ) ) {
					// Invalid add-on ID.
					self::shoot_ajax_failure( array(
						'message' => $this->get_text_inline( 'Invalid module ID.', 'auto-install-error-invalid-id' ),
						'code'    => 'invalid_module_id',
					) );
				}

				if ( $this->is_addon_activated( $plugin_id, true ) ) {
					// Premium add-on version is already activated.
					self::shoot_ajax_failure( array(
						'message' => $this->get_text_inline( 'Premium add-on version already installed.', 'auto-install-error-premium-addon-activated' ),
						'code'    => 'premium_installed',
					) );
				}
			}

			$this->_isAutoInstall = true;

			// Try to install and activate.
			$updater = new FS_Plugin_Updater( $this );
			$result  = $updater->install_and_activate_plugin( $plugin_id );

			if ( is_array( $result ) && ! empty( $result['message'] ) ) {
				self::shoot_ajax_failure( array(
					'message' => $result['message'],
					'code'    => $result['code'],
				) );
			}

			self::shoot_ajax_success( $result );
		}

		/**
		 * Displays module activation dialog box after a successful upgrade
		 * where the user explicitly requested to auto download and install
		 * the premium version.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.7
		 */
		function _add_auto_installation_dialog_box() {
			$this->_logger->entrance();

			if ( ! $this->is_registered() ) {
				// Not registered.
				return;
			}

			$plugin_id = fs_request_get( 'plugin_id', $this->get_id() );

			if ( ! FS_Plugin::is_valid_id( $plugin_id ) ) {
				// Invalid module ID.
				return;
			}

			if ( $plugin_id == $this->get_id() ) {
				if ( $this->is_premium() ) {
					// Already using the premium code version.
					return;
				}
				if ( ! $this->can_use_premium_code() ) {
					// Don't have access to the premium code.
					return;
				}
				if ( ! $this->has_release_on_freemius() ) {
					// Plugin is a serviceware, no premium code version.
					return;
				}
			} else {
				$addon = $this->get_addon( $plugin_id );

				if ( ! is_object( $addon ) ) {
					// Invalid add-on ID.
					return;
				}

				if ( $this->is_addon_activated( $plugin_id, true ) ) {
					// Premium add-on version is already activated.
					return;
				}
			}

            $vars = array(
                'id'               => $this->_module_id,
                'target_module_id' => $plugin_id,
                'slug'             => $this->_slug,
            );

			fs_require_template( 'auto-installation.php', $vars );
		}

		#endregion

		#--------------------------------------------------------------------------------
		#region Tabs Integration
		#--------------------------------------------------------------------------------

		#region Module's Original Tabs

		/**
		 * Inject a JavaScript logic to capture the theme tabs HTML.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 */
		function _tabs_capture() {
			$this->_logger->entrance();

			if ( ! $this->is_theme_settings_page() ||
			     ! $this->is_matching_url( $this->main_menu_url() )
			) {
				return;
			}

			$params = array(
				'id' => $this->_module_id,
			);

			fs_require_once_template( 'tabs-capture-js.php', $params );
		}

		/**
		 * Cache theme's tabs HTML for a week. The cache will also be set as expired
		 * after version and type (free/premium) changes, in addition to the week period.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 */
		function _store_tabs_ajax_action() {
			$this->_logger->entrance();

			$this->check_ajax_referer( 'store_tabs' );

			// Init filesystem if not yet initiated.
			WP_Filesystem();

			// Get POST body HTML data.
			global $wp_filesystem;
			$tabs_html = $wp_filesystem->get_contents( "php://input" );

			if ( is_string( $tabs_html ) ) {
				$tabs_html = trim( $tabs_html );
			}

			if ( ! is_string( $tabs_html ) || empty( $tabs_html ) ) {
				self::shoot_ajax_failure();
			}

			$this->_cache->set( 'tabs', $tabs_html, 7 * WP_FS__TIME_24_HOURS_IN_SEC );

			self::shoot_ajax_success();
		}

		/**
		 * Cache theme's settings page custom styles. The cache will also be set as expired
		 * after version and type (free/premium) changes, in addition to the week period.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 */
		function _store_tabs_styles() {
			$this->_logger->entrance();

			if ( ! $this->is_theme_settings_page() ||
			     ! $this->is_matching_url( $this->main_menu_url() )
			) {
				return;
			}

			$wp_styles = wp_styles();

			$theme_styles_url = get_template_directory_uri();

			$stylesheets = array();
			foreach ( $wp_styles->queue as $handler ) {
				if ( fs_starts_with( $handler, 'fs_' ) ) {
					// Assume that stylesheets that their handler starts with "fs_" belong to the SDK.
					continue;
				}

				/**
				 * @var _WP_Dependency $stylesheet
				 */
				$stylesheet = $wp_styles->registered[ $handler ];

				if ( fs_starts_with( $stylesheet->src, $theme_styles_url ) ) {
					$stylesheets[] = $stylesheet->src;
				}
			}

			if ( ! empty( $stylesheets ) ) {
				$this->_cache->set( 'tabs_stylesheets', $stylesheets, 7 * WP_FS__TIME_24_HOURS_IN_SEC );
			}
		}

		/**
		 * Check if module's original settings page has any tabs.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return bool
		 */
		private function has_tabs() {
			return $this->_cache->has( 'tabs' );
		}

		/**
		 * Get module's settings page HTML content, starting
		 * from the beginning of the <div class="wrap"> element,
		 * until the tabs HTML (including).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return string
		 */
		private function get_tabs_html() {
			$this->_logger->entrance();

			return $this->_cache->get( 'tabs' );
		}

		/**
		 * Check if page should include tabs.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return bool
		 */
		private function should_page_include_tabs()
		{
			if ( ! $this->has_settings_menu() ) {
				// Don't add tabs if no settings at all.
				return false;
			}

			if ( ! $this->is_theme() ) {
				// Only add tabs to themes for now.
				return false;
			}

			if ( ! $this->has_paid_plan() && ! $this->has_addons() ) {
				// Only add tabs to monetizing themes.
				return false;
			}

			if ( ! $this->is_theme_settings_page() ) {
				// Only add tabs if browsing one of the theme's setting pages.
				return false;
			}

			if ( $this->is_admin_page( 'pricing' ) && fs_request_get_bool( 'checkout' ) ) {
				// Don't add tabs on checkout page, we want to reduce distractions
				// as much as possible.
				return false;
			}

			return true;
		}

		/**
		 * Add the tabs HTML before the setting's page content and
		 * enqueue any required stylesheets.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return bool If tabs were included.
		 */
		function _add_tabs_before_content() {
			$this->_logger->entrance();

			if ( ! $this->should_page_include_tabs() ) {
				return false;
			}

			/**
			 * Enqueue the original stylesheets that are included in the
			 * theme settings page. That way, if the theme settings has
			 * some custom _styled_ content above the tabs UI, this
			 * will make sure that the styling is preserved.
			 */
			$stylesheets = $this->_cache->get( 'tabs_stylesheets', array() );
			if ( is_array( $stylesheets ) ) {
				for ( $i = 0, $len = count( $stylesheets ); $i < $len; $i ++ ) {
					wp_enqueue_style( "fs_{$this->_module_id}_tabs_{$i}", $stylesheets[ $i ] );
				}
			}

			// Cut closing </div> tag.
			echo substr( trim( $this->get_tabs_html() ), 0, - 6 );

			return true;
		}

		/**
		 * Add the tabs closing HTML after the setting's page content.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @return bool If tabs closing HTML was included.
		 */
		function _add_tabs_after_content() {
			$this->_logger->entrance();

			if ( ! $this->should_page_include_tabs() ) {
				return false;
			}

			echo '</div>';

			return true;
		}

		#endregion

		/**
		 * Add in-page JavaScript to inject the Freemius tabs into
		 * the module's setting tabs section.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 */
		function _add_freemius_tabs() {
			$this->_logger->entrance();

			if ( ! $this->should_page_include_tabs() ) {
				return;
			}

			$params = array( 'id' => $this->_module_id );
			fs_require_once_template( 'tabs.php', $params );
		}

		#endregion

		#--------------------------------------------------------------------------------
		#region Customizer Integration for Themes
		#--------------------------------------------------------------------------------

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 *
		 * @param WP_Customize_Manager $customizer
		 */
		function _customizer_register($customizer) {
			$this->_logger->entrance();

			if ( $this->is_pricing_page_visible() ) {
				require_once WP_FS__DIR_INCLUDES . '/customizer/class-fs-customizer-upsell-control.php';

				$customizer->add_section( 'freemius_upsell', array(
					'title'    => '&#9733; ' . $this->get_text_inline( 'View paid features', 'view-paid-features' ),
					'priority' => 1,
				) );
				$customizer->add_setting( 'freemius_upsell', array(
					'sanitize_callback' => 'esc_html',
				) );

				$customizer->add_control( new FS_Customizer_Upsell_Control( $customizer, 'freemius_upsell', array(
					'fs'       => $this,
					'section'  => 'freemius_upsell',
					'priority' => 100,
				) ) );
			}

			if ( $this->is_page_visible( 'contact' ) || $this->is_page_visible( 'support' ) ) {
				require_once WP_FS__DIR_INCLUDES . '/customizer/class-fs-customizer-support-section.php';

				// Main Documentation Link In Customizer Root.
				$customizer->add_section( new FS_Customizer_Support_Section( $customizer, 'freemius_support', array(
					'fs'       => $this,
					'priority' => 1000,
				) ) );
			}
		}

		#endregion

		/**
		 * If the theme has a paid version, add some custom
		 * styling to the theme's premium version (if exists)
		 * to highlight that it's the premium version of the
		 * same theme, making it easier for identification
		 * after the user upgrades and upload it to the site.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.2.7
		 */
		function _style_premium_theme() {
			$this->_logger->entrance();

			if ( ! self::is_themes_page() ) {
				// Only include in the themes page.
				return;
			}

			if ( ! $this->has_paid_plan() ) {
				// Only include if has any paid plans.
				return;
			}

			$params = null;
			fs_require_once_template( '/js/jquery.content-change.php', $params );

			$params = array(
				'slug' => $this->_slug,
				'id'   => $this->_module_id,
			);

			fs_require_template( '/js/style-premium-theme.php', $params );
		}

		#----------------------------------------------------------------------------------
		#region Marketing
		#----------------------------------------------------------------------------------

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
			throw new Exception( 'not implemented' );
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
			throw new Exception( 'not implemented' );
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
			throw new Exception( 'not implemented' );
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
			throw new Exception( 'not implemented' );
		}

		#endregion
	}
