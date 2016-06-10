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
		 * @since 1.1.6
		 * @var string[]bool.
		 */
		private $_permissions;

		/**
		 * @var FS_Key_Value_Storage
		 */
		private $_storage;

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
		 * @var Freemius[]
		 */
		private static $_instances = array();


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

			if ( 'true' === fs_request_get( 'fs_clear_api_cache' ) ||
			     'true' === fs_request_is_action( 'restart_freemius' )
			) {
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

			if ( ! isset( $this->_storage->is_plugin_new_install ) ) {
				$this->_storage->is_plugin_new_install = false;
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

				/**
				 * Part of the mechanism to identify new plugin install vs. plugin update.
				 *
				 * @author Vova Feldman (@svovaf)
				 * @since  1.1.9
				 */
				if ( empty( $this->_storage->was_plugin_loaded ) ) {
					if ( $this->is_activation_mode( false ) ) {
						add_action( 'plugins_loaded', array( &$this, '_plugins_loaded' ) );
					} else {
						// If was activated before, then it was already loaded before.
						$this->_plugins_loaded();
					}
				}

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

			$this->add_action( 'sdk_version_update', array( &$this, '_data_migration' ), WP_FS__DEFAULT_PRIORITY, 2 );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		private function _register_account_hooks() {
			if ( is_admin() ) {
				if ( ! $this->is_ajax() ) {
					if ( $this->apply_filters( 'show_trial', true ) && $this->has_trial_plan() ) {
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

				// If user is paying or in trial and have the free version installed,
				// assume that the deactivation is for the upgrade process.
				if ( ! $this->is_paying_or_trial() || $this->is_premium() ) {
					add_action( 'wp_ajax_submit-uninstall-reason', array( &$this, '_submit_uninstall_reason_action' ) );

					global $pagenow;
					if ( 'plugins.php' === $pagenow ) {
						add_action( 'admin_footer', array( &$this, '_add_deactivation_feedback_dialog_box' ) );
					}
				}
			}
		}

		/**
		 * Leverage backtrace to find caller plugin file path.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return string
		 *
		 * @uses   fs_find_caller_plugin_file
		 */
		private function _find_caller_plugin_file() {
			// Try to load the cached value of the file path.
			if ( isset( $this->_storage->plugin_main_file ) ) {
				if ( file_exists( $this->_storage->plugin_main_file->path ) ) {
					return $this->_storage->plugin_main_file->path;
				}
			}

			$plugin_file = fs_find_caller_plugin_file();

			$this->_storage->plugin_main_file = (object) array(
				'path' => fs_normalize_path( $plugin_file ),
			);

			return $plugin_file;
		}


		#region Deactivation Feedback Form ------------------------------------------------------------------

		/**
		 * Displays a confirmation and feedback dialog box when the user clicks on the "Deactivate" link on the plugins
		 * page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @author Leo Fajardo (@leorw)
		 * @since  1.1.2
		 */
		function _add_deactivation_feedback_dialog_box() {
			fs_enqueue_local_style( 'fs_deactivation_feedback', '/admin/deactivation-feedback.css' );

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
				'slug'    => $this->_slug
			);

			/**
			 * @todo Deactivation form core functions should be loaded only once! Otherwise, when there are multiple Freemius powered plugins the same code is loaded multiple times. The only thing that should be loaded differently is the various deactivation reasons object based on the state of the plugin.
			 */
			fs_require_template( 'deactivation-feedback-modal.php', $vars );
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
			$reason_found_better_plugin = array(
				'id'                => 2,
				'text'              => __fs( 'reason-found-a-better-plugin', $this->_slug ),
				'input_type'        => 'textfield',
				'input_placeholder' => __fs( 'placeholder-plugin-name', $this->_slug )
			);

			$reason_temporary_deactivation = array(
				'id'                => 15,
				'text'              => __fs( 'reason-temporary-deactivation', $this->_slug ),
				'input_type'        => '',
				'input_placeholder' => ''
			);

			$reason_other = array(
				'id'                => 7,
				'text'              => __fs( 'reason-other', $this->_slug ),
				'input_type'        => 'textfield',
				'input_placeholder' => ''
			);

			$long_term_user_reasons = array(
				array(
					'id'                => 1,
					'text'              => __fs( 'reason-no-longer-needed', $this->_slug ),
					'input_type'        => '',
					'input_placeholder' => ''
				),
				$reason_found_better_plugin,
				array(
					'id'                => 3,
					'text'              => __fs( 'reason-needed-for-a-short-period', $this->_slug ),
					'input_type'        => '',
					'input_placeholder' => ''
				),
				array(
					'id'                => 4,
					'text'              => __fs( 'reason-broke-my-site', $this->_slug ),
					'input_type'        => '',
					'input_placeholder' => ''
				),
				array(
					'id'                => 5,
					'text'              => __fs( 'reason-suddenly-stopped-working', $this->_slug ),
					'input_type'        => '',
					'input_placeholder' => ''
				)
			);

			if ( $this->is_paying() ) {
				$long_term_user_reasons[] = array(
					'id'                => 6,
					'text'              => __fs( 'reason-cant-pay-anymore', $this->_slug ),
					'input_type'        => 'textfield',
					'input_placeholder' => __fs( 'placeholder-comfortable-price', $this->_slug )
				);
			}

			$long_term_user_reasons[] = $reason_temporary_deactivation;
			$long_term_user_reasons[] = $reason_other;

			$uninstall_reasons = array(
				'long-term'                                   => $long_term_user_reasons,
				'non-registered-and-non-anonymous-short-term' => array(
					array(
						'id'                => 8,
						'text'              => __fs( 'reason-didnt-work', $this->_slug ),
						'input_type'        => '',
						'input_placeholder' => ''
					),
					array(
						'id'                => 9,
						'text'              => __fs( 'reason-dont-like-to-share-my-information', $this->_slug ),
						'input_type'        => '',
						'input_placeholder' => ''
					),
					$reason_found_better_plugin,
					$reason_temporary_deactivation,
					$reason_other
				),
				'short-term'                                  => array(
					array(
						'id'                => 10,
						'text'              => __fs( 'reason-couldnt-make-it-work', $this->_slug ),
						'input_type'        => '',
						'input_placeholder' => ''
					),
					$reason_found_better_plugin,
					array(
						'id'                => 11,
						'text'              => __fs( 'reason-great-but-need-specific-feature', $this->_slug ),
						'input_type'        => 'textarea',
						'input_placeholder' => __fs( 'placeholder-feature', $this->_slug )
					),
					array(
						'id'                => 12,
						'text'              => __fs( 'reason-not-working', $this->_slug ),
						'input_type'        => 'textarea',
						'input_placeholder' => __fs( 'placeholder-share-what-didnt-work', $this->_slug )
					),
					array(
						'id'                => 13,
						'text'              => __fs( 'reason-not-what-i-was-looking-for', $this->_slug ),
						'input_type'        => 'textarea',
						'input_placeholder' => __fs( 'placeholder-what-youve-been-looking-for', $this->_slug )
					),
					array(
						'id'                => 14,
						'text'              => __fs( 'reason-didnt-work-as-expected', $this->_slug ),
						'input_type'        => 'textarea',
						'input_placeholder' => __fs( 'placeholder-what-did-you-expect', $this->_slug )
					),
					$reason_temporary_deactivation,
					$reason_other
				)
			);

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
			if ( ! isset( $_POST['reason_id'] ) ) {
				exit;
			}

			$reason_info = isset( $_REQUEST['reason_info'] ) ? trim( stripslashes( $_REQUEST['reason_info'] ) ) : '';

			$reason = (object) array(
				'id'   => $_POST['reason_id'],
				'info' => substr( $reason_info, 0, 128 )
			);

			$this->_storage->store( 'uninstall_reason', $reason );

			// Print '1' for successful operation.
			echo 1;
			exit;
		}

		#endregion Deactivation Feedback Form ------------------------------------------------------------------

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
			$slug = self::find_slug_by_basename( $plugin_file );

			return ( false !== $slug ) ?
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
		 * @param bool $and_on
		 *
		 * @return bool
		 */
		function is_activation_mode( $and_on = true ) {
			return (
				( $this->is_on() || ! $and_on ) &&
				! $this->is_registered() &&
				( ! $this->is_enable_anonymous() ||
				  ( ! $this->is_anonymous() && ! $this->is_pending_activation() ) )
			);
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

			// Configure which Freemius powered plugins should be auto updated.
//			add_filter( 'auto_update_plugin', '_include_plugins_in_auto_update', 10, 2 );

			add_action( 'admin_menu', array( 'Freemius', 'add_debug_page' ) );

			add_action( "wp_ajax_fs_toggle_debug_mode", array( 'Freemius', '_toggle_debug_mode' ) );

			self::$_statics_loaded = true;
		}

		#region Debugging ------------------------------------------------------------------

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.8
		 */
		static function add_debug_page() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			self::$_static_logger->entrance();

			$title = sprintf( '%s [v.%s]', __fs( 'freemius-debug' ), WP_FS__SDK_VERSION );

			if ( WP_FS__DEV_MODE ) {
				// Add top-level debug menu item.
				$hook = add_menu_page(
					$title,
					$title,
					'manage_options',
					'freemius',
					array( 'Freemius', '_debug_page_render' )
				);
			} else {
				// Add hidden debug page.
				$hook = add_submenu_page(
					null,
					$title,
					$title,
					'manage_options',
					'freemius',
					array( 'Freemius', '_debug_page_render' )
				);
			}

			add_action( "load-$hook", array( 'Freemius', '_debug_page_actions' ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 */
		static function _toggle_debug_mode() {
			if ( in_array( $_POST['is_on'], array( 0, 1 ) ) ) {
				update_option( 'fs_debug_mode', $_POST['is_on'] );
			}

			exit;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.8
		 */
		static function _debug_page_actions() {
			self::_clean_admin_content_section();

			if ( fs_request_is_action( 'restart_freemius' ) ) {
				check_admin_referer( 'restart_freemius' );

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

			fs_enqueue_local_style( 'fs_account', '/admin/debug.css' );
			fs_require_once_template( 'debug.php', $vars );
		}

		#endregion ------------------------------------------------------------------

		#region Connectivity Issues ------------------------------------------------------------------

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
					'is_ajax'   => json_encode( $this->is_ajax() ),
					'is_cron'   => json_encode( $this->is_cron() ),
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
				 */
				$this->_is_on = $this->_storage->connectivity_test['is_active'] ||
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

				$unique_id = md5( $key );

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
		 * Generate API connectivity issue message.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @param mixed $api_result
		 * @param bool  $is_first_failure
		 */
		function _add_connectivity_issue_message( $api_result, $is_first_failure = true ) {
			if ( $this->_enable_anonymous ) {
				// Don't add message if can run anonymously.
				return;
			}

			if ( ! function_exists( 'wp_nonce_url' ) ) {
				require_once( ABSPATH . 'wp-includes/functions.php' );
			}

			$current_user = self::_get_current_wp_user();
//			$admin_email = get_option( 'admin_email' );
			$admin_email = $current_user->user_email;

			$message = false;
			if ( is_object( $api_result ) &&
			     isset( $api_result->error ) &&
			     isset( $api_result->error->code )
			) {
				switch ( $api_result->error->code ) {
					case 'curl_missing':
						$message = sprintf(
							__fs( 'x-requires-access-to-api', $this->_slug ) . ' ' .
							__fs( 'curl-missing-message', $this->_slug ) . ' ' .
							' %s',
							'<b>' . $this->get_plugin_name() . '</b>',
							sprintf(
								'<ol id="fs_firewall_issue_options"><li>%s</li><li>%s</li><li>%s</li></ol>',
								sprintf(
									'<a class="fs-resolve" data-type="curl" href="#"><b>%s</b></a>%s',
									__fs( 'curl-missing-no-clue-title', $this->_slug ),
									' - ' . sprintf(
										__fs( 'curl-missing-no-clue-desc', $this->_slug ),
										'<a href="mailto:' . $admin_email . '">' . $admin_email . '</a>'
									)
								),
								sprintf(
									'<b>%s</b> - %s',
									__fs( 'sysadmin-title', $this->_slug ),
									__fs( 'curl-missing-sysadmin-desc', $this->_slug )
								),
								sprintf(
									'<a href="%s"><b>%s</b></a>%s',
									wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $this->_plugin_basename . '&amp;plugin_status=' . 'all' . '&amp;paged=' . '1' . '&amp;s=' . '', 'deactivate-plugin_' . $this->_plugin_basename ),
									__fs( 'deactivate-plugin-title', $this->_slug ),
									' - ' . __fs( 'deactivate-plugin-desc', 'freemius', $this->_slug )
								)
							)
						);
						break;
					case 'cloudflare_ddos_protection':
						$message = sprintf(
							__fs( 'x-requires-access-to-api', $this->_slug ) . ' ' .
							__fs( 'cloudflare-blocks-connection-message', $this->_slug ) . ' ' .
							__fs( 'happy-to-resolve-issue-asap', $this->_slug ) .
							' %s',
							'<b>' . $this->get_plugin_name() . '</b>',
							sprintf(
								'<ol id="fs_firewall_issue_options"><li>%s</li><li>%s</li><li>%s</li></ol>',
								sprintf(
									'<a class="fs-resolve" data-type="cloudflare" href="#"><b>%s</b></a>%s',
									__fs( 'fix-issue-title', $this->_slug ),
									' - ' . sprintf(
										__fs( 'fix-issue-desc', $this->_slug ),
										'<a href="mailto:' . $admin_email . '">' . $admin_email . '</a>'
									)
								),
								sprintf(
									'<a href="%s" target="_blank"><b>%s</b></a>%s',
									sprintf( 'https://wordpress.org/plugins/%s/download/', $this->_slug ),
									__fs( 'install-previous-title', $this->_slug ),
									' - ' . __fs( 'install-previous-desc', $this->_slug )
								),
								sprintf(
									'<a href="%s"><b>%s</b></a>%s',
									wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $this->_plugin_basename . '&amp;plugin_status=' . 'all' . '&amp;paged=' . '1' . '&amp;s=' . '', 'deactivate-plugin_' . $this->_plugin_basename ),
									__fs( 'deactivate-plugin-title', $this->_slug ),
									' - ' . __fs( 'deactivate-plugin-desc', $this->_slug )
								)
							)
						);
						break;
					case 'squid_cache_block':
						$message = sprintf(
							__fs( 'x-requires-access-to-api', $this->_slug ) . ' ' .
							__fs( 'squid-blocks-connection-message', $this->_slug ) .
							' %s',
							'<b>' . $this->get_plugin_name() . '</b>',
							sprintf(
								'<ol id="fs_firewall_issue_options"><li>%s</li><li>%s</li><li>%s</li></ol>',
								sprintf(
									'<a class="fs-resolve" data-type="squid" href="#"><b>%s</b></a>%s',
									__fs( 'squid-no-clue-title', $this->_slug ),
									' - ' . sprintf(
										__fs( 'squid-no-clue-desc', $this->_slug ),
										'<a href="mailto:' . $admin_email . '">' . $admin_email . '</a>'
									)
								),
								sprintf(
									'<b>%s</b> - %s',
									__fs( 'sysadmin-title', $this->_slug ),
									sprintf(
										__fs( 'squid-sysadmin-desc', $this->_slug ),
										// We use a filter since the plugin might require additional API connectivity.
										'<b>' . implode( ', ', $this->apply_filters( 'api_domains', array( 'api.freemius.com' ) ) ) . '</b>' )
								),
								sprintf(
									'<a href="%s"><b>%s</b></a>%s',
									wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $this->_plugin_basename . '&amp;plugin_status=' . 'all' . '&amp;paged=' . '1' . '&amp;s=' . '', 'deactivate-plugin_' . $this->_plugin_basename ),
									__fs( 'deactivate-plugin-title', $this->_slug ),
									' - ' . __fs( 'deactivate-plugin-desc', $this->_slug )
								)
							)
						);
						break;
//					default:
//						$message = __fs( 'connectivity-test-fails-message', $this->_slug );
//						break;
				}
			}

			$message_id = 'failed_connect_api';
			$type       = 'error';

			if ( false === $message ) {
				if ( $is_first_failure ) {
					// First attempt failed.
					$message = sprintf(
						__fs( 'x-requires-access-to-api', $this->_slug ) . ' ' .
						__fs( 'connectivity-test-fails-message', $this->_slug ) . ' ' .
						__fs( 'connectivity-test-maybe-temporary', $this->_slug ) . '<br><br>' .
						'%s',
						'<b>' . $this->get_plugin_name() . '</b>',
						sprintf(
							'<div id="fs_firewall_issue_options">%s %s</div>',
							sprintf(
								'<a  class="button button-primary fs-resolve" data-type="retry_ping" href="#">%s</a>',
								__fs( 'yes-do-your-thing', $this->_slug )
							),
							sprintf(
								'<a href="%s" class="button">%s</a>',
								wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $this->_plugin_basename . '&amp;plugin_status=' . 'all' . '&amp;paged=' . '1' . '&amp;s=' . '', 'deactivate-plugin_' . $this->_plugin_basename ),
								__fs( 'no-deactivate', $this->_slug )
							)
						)
					);

					$message_id = 'failed_connect_api_first';
					$type       = 'promotion';
				} else {
					// Second connectivity attempt failed.
					$message = sprintf(
						__fs( 'x-requires-access-to-api', $this->_slug ) . ' ' .
						__fs( 'connectivity-test-fails-message', $this->_slug ) . ' ' .
						__fs( 'happy-to-resolve-issue-asap', $this->_slug ) .
						' %s',
						'<b>' . $this->get_plugin_name() . '</b>',
						sprintf(
							'<ol id="fs_firewall_issue_options"><li>%s</li><li>%s</li><li>%s</li></ol>',
							sprintf(
								'<a class="fs-resolve" data-type="general" href="#"><b>%s</b></a>%s',
								__fs( 'fix-issue-title', $this->_slug ),
								' - ' . sprintf(
									__fs( 'fix-issue-desc', $this->_slug ),
									'<a href="mailto:' . $admin_email . '">' . $admin_email . '</a>'
								)
							),
							sprintf(
								'<a href="%s" target="_blank"><b>%s</b></a>%s',
								sprintf( 'https://wordpress.org/plugins/%s/download/', $this->_slug ),
								__fs( 'install-previous-title', $this->_slug ),
								' - ' . __fs( 'install-previous-desc', $this->_slug )
							),
							sprintf(
								'<a href="%s"><b>%s</b></a>%s',
								wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $this->_plugin_basename . '&amp;plugin_status=' . 'all' . '&amp;paged=' . '1' . '&amp;s=' . '', 'deactivate-plugin_' . $this->_plugin_basename ),
								__fs( 'deactivate-plugin-title', $this->_slug ),
								' - ' . __fs( 'deactivate-plugin-desc', $this->_slug )
							)
						)
					);
				}
			}

			$this->_admin_notices->add_sticky(
				$message,
				$message_id,
				__fs( 'oops', $this->_slug ) . '...',
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

			if ( 'squid' === $error_type ) {
				// Override the 'Site' email section.
				$custom_email_sections['site'] = array(
					'rows' => array(
						'hosting_company' => array( 'Hosting Company', fs_request_get( 'hosting_company' ) )
					)
				);
			}

			// Add 'API Error' custom email section.
			$custom_email_sections['api_error'] = array(
				'title' => 'API Error',
				'rows'  => array(
					'ping' => array( is_string( $pong ) ? htmlentities( $pong ) : json_encode( $pong ) )
				)
			);

			// Add PHP info for deeper investigation.
			ob_start();
			phpinfo();
			$php_info                          = ob_get_clean();
			$custom_email_sections['php_info'] = array(
				'title' => 'PHP Info',
				'rows'  => array(
					'info' => array( $php_info )
				)
			);

			// Send email with technical details to resolve CloudFlare's firewall unnecessary protection.
			$this->send_email(
				'api@freemius.com',                              // recipient
				$title . ' [' . $this->get_plugin_name() . ']',  // subject
				$custom_email_sections,
				array( "Reply-To: $admin_email <$admin_email>" ) // headers
			);

			$this->_admin_notices->add_sticky(
				sprintf(
					__fs( 'fix-request-sent-message', $this->_slug ),
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

		#endregion Connectivity Issues ------------------------------------------------------------------

		#region Email ------------------------------------------------------------------

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
			$headers[] = 'Content-type: text/html';

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

			// Generate the default email sections.
			$sections = array(
				'sdk'     => array(
					'title' => 'SDK',
					'rows'  => array(
						'fs_version'   => array( 'FS Version', $this->version ),
						'curl_version' => array( 'cURL Version', $curl_version_information['version'] )
					)
				),
				'plugin'  => array(
					'title' => 'Plugin',
					'rows'  => array(
						'name'    => array( 'Name', $this->get_plugin_name() ),
						'version' => array( 'Version', $this->get_plugin_version() )
					)
				),
				'site'    => array(
					'title' => 'Site',
					'rows'  => array(
						'unique_id'   => array( 'Address', $this->get_anonymous_id() ),
						'address'     => array( 'Address', site_url() ),
						'host'        => array(
							'HTTP_HOST',
							( ! empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '' )
						),
						'server_addr' => array(
							'SERVER_ADDR',
							'<a href="http://www.projecthoneypot.org/ip_' . $server_ip . '">' . $server_ip . '</a>'
						)
					)
				),
				'user'    => array(
					'title' => 'User',
					'rows'  => array(
						'email' => array( 'Email', $current_user->user_email ),
						'first' => array( 'First', $current_user->user_firstname ),
						'last'  => array( 'Last', $current_user->user_lastname )
					)
				),
				'plugins' => array(
					'title' => 'Plugins',
					'rows'  => array(
						'active_plugins' => array( 'Active Plugins', $active_plugin_string )
					)
				),
			);

			// Allow the sections to be modified by other code.
			$sections = $this->apply_filters( 'email_template_sections', $sections );

			return $sections;
		}

		#endregion Email ------------------------------------------------------------------

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
							if ( ! $this->_enable_anonymous ) {
								// If anonymous mode is disabled, add firewall admin-notice message.
								add_action( 'admin_footer', array( 'Freemius', '_add_firewall_issues_javascript' ) );

								add_action( "wp_ajax_{$this->_slug}_resolve_firewall_issues", array(
									&$this,
									'_email_about_firewall_issue'
								) );

								add_action( "wp_ajax_{$this->_slug}_retry_connectivity_test", array(
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

			if ( $this->has_api_connectivity() && $this->is_user_in_admin() ) {
				/**
				 * Schedule daily data sync cron if:
				 *
				 *  1. User opted-in (for tracking).
				 *  2. If plugin has add-ons (update add-ons data).
				 *  3. If skipped, but later upgraded (opted-in via upgrade).
				 *
				 * @author Vova Feldman (@svovaf)
				 * @since  1.1.7.3
				 *
				 */
				if ( $this->is_registered() ||
				     ( ! $this->is_activation_mode() && $this->_has_addons )
				) {

					$this->hook_callback_to_sync_cron();

					if ( ! $this->is_sync_cron_on() ) {
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
				global $pagenow;
				if ( 'plugins.php' === $pagenow ) {
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
								sprintf( __fs( 'addon-x-cannot-run-without-y', $this->_slug ), $this->get_plugin_name(), $parent_name ) :
								sprintf( __fs( 'addon-x-cannot-run-without-parent', $this->_slug ), $this->get_plugin_name() )
							),
							__fs( 'oops', $this->_slug ) . '...',
							'error'
						);

						return;
					} else {
						if ( $this->_parent->is_registered() && ! $this->is_registered() ) {
							// If parent plugin activated, automatically install add-on for the user.
							$this->_activate_addon_account( $this->_parent );
						}

						// @todo This should be only executed on activation. It should be migrated to register_activation_hook() together with other activation related logic.
						if ( $this->is_premium() ) {
							// Remove add-on download admin-notice.
							$this->_parent->_admin_notices->remove_sticky( array(
								'addon_plan_upgraded_' . $this->_slug,
								'no_addon_license_' . $this->_slug,
							) );
						}

						$this->deactivate_premium_only_addon_without_license();
					}
				} else {
					add_action( 'admin_init', array( &$this, '_admin_init_action' ) );

					if ( $this->has_addons() &&
					     'plugin-information' === fs_request_get( 'tab', false ) &&
					     $this->get_id() == fs_request_get( 'parent_plugin_id', false )
					) {
						require_once WP_FS__DIR_INCLUDES . '/fs-plugin-info-dialog.php';

						new FS_Plugin_Info_Dialog( $this );
					}
				}

				if ( $this->is_premium() ) {
					new FS_Plugin_Updater( $this );
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

			// Add license activation link and AJAX request handler.
			if ( $this->has_paid_plan() ) {
				$this->_add_license_action_link();

				global $pagenow;
				if ( 'plugins.php' === $pagenow ) {
					add_action( 'admin_footer', array( &$this, '_add_license_activation_dialog_box' ) );
				}

				add_action( 'wp_ajax_activate-license', array( &$this, '_activate_license_ajax_action' ) );
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
				$parent_name = $this->get_option( $plugin_info['parent'], 'name', null );
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
				'file'             => $this->_plugin_basename,
				'is_premium'       => $this->get_bool_option( $plugin_info, 'is_premium', true ),
				'is_live'          => $this->get_bool_option( $plugin_info, 'is_live', true ),
//				'secret_key' => $secret_key,
			) );

			if ( $plugin->is_updated() ) {
				// Update plugin details.
				$this->_plugin = FS_Plugin_Manager::instance( $this->_slug )->store( $plugin );
			}
			// Set the secret key after storing the plugin, we don't want to store the key in the storage.
			$this->_plugin->secret_key = $secret_key;

			if ( ! isset( $plugin_info['menu'] ) ) {
				// Back compatibility to 1.1.2
				$plugin_info['menu'] = array(
					'slug' => isset( $plugin_info['menu_slug'] ) ?
						$plugin_info['menu_slug'] :
						$this->_slug
				);
			}

			$this->_menu = FS_Admin_Menu_Manager::instance( $this->_slug );
			$this->_menu->init( $plugin_info['menu'], $this->is_addon() );

			$this->_has_addons       = $this->get_bool_option( $plugin_info, 'has_addons', false );
			$this->_has_paid_plans   = $this->get_bool_option( $plugin_info, 'has_paid_plans', true );
			$this->_is_org_compliant = $this->get_bool_option( $plugin_info, 'is_org_compliant', true );
			$this->_is_premium_only  = $this->get_bool_option( $plugin_info, 'is_premium_only', false );
			if ( $this->_is_premium_only ) {
				// If premium only plugin, disable anonymous mode.
				$this->_enable_anonymous = false;
				$this->_anonymous_mode   = false;
			} else {
				$this->_enable_anonymous = $this->get_bool_option( $plugin_info, 'enable_anonymous', true );
				$this->_anonymous_mode   = $this->get_bool_option( $plugin_info, 'anonymous_mode', false );
			}
			$this->_permissions = $this->get_option( $plugin_info, 'permissions', array() );
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

				if ( $this->is_cron() ) {
					/**
					 * If in activation mode, don't execute Freemius during wp crons
					 * (wp crons have HTTP context - called as HTTP request).
					 *
					 * @author Vova Feldman (@svovaf)
					 * @since  1.1.7.3
					 */
					return true;
				}

				if ( $this->is_ajax() &&
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
		 * Handles plugin's code type change (free <--> premium).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		function _plugin_code_type_changed() {
			// Schedule code type changes event.
//			$this->sync_install();
			$this->schedule_install_sync();

			if ( $this->is_premium() ) {
				// Activated premium code.
				$this->do_action( 'after_premium_version_activation' );

				// Remove all sticky messages related to download of the premium version.
				$this->_admin_notices->remove_sticky( array(
					'trial_started',
					'plan_upgraded',
					'plan_changed',
				) );

				$this->_admin_notices->add_sticky(
					__fs( 'premium-activated-message', $this->_slug ),
					'premium_activated',
					__fs( 'woot', $this->_slug ) . '!'
				);
			} else {
				// Activated free code (after had the premium before).
				$this->do_action( 'after_free_version_reactivation' );

				if ( $this->is_paying() && ! $this->is_premium() ) {
					$this->_admin_notices->add_sticky(
						sprintf(
							__fs( 'you-have-x-license', $this->_slug ),
							$this->_site->plan->title
						) . ' ' . $this->_get_latest_download_link( sprintf(
							__fs( 'download-x-version-now', $this->_slug ),
							$this->_site->plan->title
						) ),
						'plan_upgraded',
						__fs( 'yee-haw', $this->_slug ) . '!'
					);
				}
			}

			// Update is_premium of latest version.
			$this->_storage->prev_is_premium = $this->_plugin->is_premium;
		}

		#endregion Initialization ------------------------------------------------------------------

		#region Add-ons -------------------------------------------------------------------------

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
		 * Check if add-on was connected to install
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7
		 *
		 * @param string $slug
		 *
		 * @return bool
		 */
		function is_addon_connected( $slug ) {
			$sites = self::get_all_sites();

			if ( ! isset( $sites[ $slug ] ) ) {
				return false;
			}

			$site = $sites[ $slug ];

			$plugin = FS_Plugin_Manager::instance( $slug )->get();

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

			$premium_basename = $slug . '-premium/' . $slug . '.php';

			if ( file_exists( fs_normalize_path( WP_PLUGIN_DIR . '/' . $premium_basename ) ) ) {
				return $premium_basename;
			}

			$free_basename = $slug . '/' . $slug . '.php';

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
			foreach ( self::$_instances as $slug => $instance ) {
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

			foreach ( self::$_instances as $slug => $instance ) {
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

				if ( ! $this->has_free_plan() &&
				     ! $this->has_features_enabled_license() &&
				     ! $this->_has_premium_license()
				) {
					// @todo Check if deactivate plugins also call the deactivation hook.

					deactivate_plugins( array( $this->_plugin_basename ), true );

					$this->_parent->_admin_notices->add_sticky(
						sprintf(
							__fs( ( $is_after_trial_cancel ?
									'addon-trial-cancelled-message' :
									'addon-no-license-message' ),
								$this->_parent->_slug
							),
							'<b>' . $this->_plugin->title . '</b>'
						) . ' ' . sprintf(
							'<a href="%s" aria-label="%s" class="button button-primary" style="margin-left: 10px; vertical-align: middle;">%s &nbsp;&#10140;</a>',
							$this->_parent->addon_url( $this->_slug ),
							esc_attr( sprintf( __fs( 'more-information-about-x', $this->_parent->_slug ), $this->_plugin->title ) ),
							__fs( 'purchase-license', $this->_parent->_slug )
						),
						'no_addon_license_' . $this->_slug,
						( $is_after_trial_cancel ? '' : __fs( 'oops', $this->_parent->_slug ) . '...' ),
						( $is_after_trial_cancel ? 'success' : 'error' )
					);

					return true;
				}
			}

			return false;
		}

		#endregion ------------------------------------------------------------------

		#region Sandbox ------------------------------------------------------------------

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

		#endregion Sandbox ------------------------------------------------------------------

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

		#region Daily Sync Cron ------------------------------------------------------------------

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.3
		 */
		private function run_manual_sync() {
			$this->require_pluggable_essentials();

			if ( ! current_user_can( 'activate_plugins' ) ) {
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
						$this->_check_updates( true );
					}
				} else {
					// Sync install (only if something changed locally).
					$this->sync_install();
				}
			}

			if ( ! $this->is_addon() && $this->_has_addons ) {
				// Sync add-ons collection.
				$this->_sync_addons( true );
			}
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

		#region Async Install Sync ------------------------------------------------------------------

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
		 */
		function _add_pending_activation_notice( $email = false ) {
			if ( ! is_string( $email ) ) {
				$current_user = self::_get_current_wp_user();
				$email        = $current_user->user_email;
			}

			$this->_admin_notices->add_sticky(
				sprintf(
					__fs( 'pending-activation-message', $this->_slug ),
					'<b>' . $this->get_plugin_name() . '</b>',
					'<b>' . $email . '</b>'
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
			return get_option( "fs_{$this->_slug}_activated", false );
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
				delete_option( "fs_{$this->_slug}_activated" );

				if ( ! function_exists( 'is_network_admin' ) || ! is_network_admin() ) {
					$this->_redirect_on_activation_hook();

					return;
				}
			}

			if ( fs_request_is_action( $this->_slug . '_skip_activation' ) ) {
				check_admin_referer( $this->_slug . '_skip_activation' );

				$this->skip_connection();

				if ( fs_redirect( $this->get_after_activation_url( 'after_skip_url' ) ) ) {
					exit();
				}
			}

			if ( ! $this->is_addon() && ! $this->is_registered() && ! $this->is_anonymous() ) {
				if ( ! $this->is_pending_activation() ) {
					if ( ! $this->_menu->is_activation_page() ) {
						if ( $this->is_plugin_new_install() || $this->is_only_premium() ) {
							// Show notice for new plugin installations.
							$this->_admin_notices->add(
								sprintf(
									__fs( 'you-are-step-away', $this->_slug ),
									sprintf( '<b><a href="%s">%s</a></b>',
										$this->get_activation_url(),
										sprintf( __fs( 'activate-x-now', $this->_slug ), $this->get_plugin_name() )
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
										__fs( 'few-plugin-tweaks', $this->_slug ),
										sprintf( '<b><a href="%s">%s</a></b>',
											$this->get_activation_url(),
											sprintf( __fs( 'optin-x-now', $this->_slug ), $this->get_plugin_name() )
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
			$vars            = array( 'slug' => $this->_slug );
			$pointer_content = fs_get_template( 'connect.php', $vars );
			?>
			<script type="text/javascript">// <![CDATA[
				jQuery(document).ready(function ($) {
					if ('undefined' !== typeof(jQuery().pointer)) {

						var element = <?php echo $this->apply_filters('optin_pointer_element', '$("#non_existing_element");') ?>;

						if (element.length > 0) {
							var optin = $(element).pointer($.extend(true, {}, {
								content     : <?php echo json_encode($pointer_content) ?>,
								position    : {
									edge : 'left',
									align: 'center'
								},
								buttons     : function () {
									// Don't show pointer buttons.
									return '';
								},
								pointerWidth: 482
							}, <?php echo $this->apply_filters('optin_pointer_options_json', '{}') ?>));

							<?php
							echo $this->apply_filters('optin_pointer_execute', "

							optin.pointer('open');

							// Tag the opt-in pointer with custom class.
							$('.wp-pointer #fs_connect')
								.parents('.wp-pointer.wp-pointer-top')
								.addClass('fs-opt-in-pointer');

							", 'element', 'optin') ?>
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
		 * Plugin activated hook.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.1
		 *
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
				// Schedule re-activation event and sync.
//				$this->sync_install( array(), true );
				$this->schedule_install_sync();

				/**
				 * @todo Work on automatic deactivation of the Free plugin version. It doesn't work since the slug of the free & premium versions is identical. Therefore, only one instance of Freemius is created and the activation hook of the premium version is not being added.
				 */
				if ( $this->_plugin_basename !== $this->_free_plugin_basename ) {
					// Deactivate Free plugin version on premium plugin activation.
					deactivate_plugins( $this->_free_plugin_basename );

					$this->_admin_notices->add(
						sprintf( __fs( 'successful-version-upgrade-message', $this->_slug ), sprintf( '<b>%s</b>', $this->_plugin->title ) ),
						__fs( 'woot', $this->_slug ) . '!'
					);
				}
			} else if ( $this->is_anonymous() ) {
				/**
				 * Reset "skipped" click cache on the following:
				 *  1. Development mode.
				 *  2. If the user skipped the exact same version before.
				 *
				 * @todo 3. If explicitly asked to retry after every activation.
				 */
				if ( WP_FS__DEV_MODE ||
				     $this->get_plugin_version() == $this->_storage->is_anonymous['version']
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

			if ( ! $this->_anonymous_mode && $this->has_api_connectivity( WP_FS__DEV_MODE ) ) {
				// Store hint that the plugin was just activated to enable auto-redirection to settings.
				add_option( "fs_{$this->_slug}_activated", true );
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

			if ( fs_redirect( $this->get_activation_url() ) ) {
				exit();
			}
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
			$active_theme = wp_get_theme();

			// Check if there's a change in themes.
			$all_themes = wp_get_themes();

			// Check if themes changed.
			ksort( $all_themes );

			$themes_signature = '';
			foreach ( $all_themes as $slug => $data ) {
				$is_active = ( $slug === $active_theme->stylesheet );
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
					$is_active = ( $slug === $active_theme->stylesheet );

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
						$is_active = ( $slug === $active_theme->stylesheet );

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
				'programming_language_version' => phpversion(),
				'title'                        => get_bloginfo( 'name' ),
				'url'                          => get_site_url(),
				// Special params.
				'is_active'                    => true,
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
						if ( ! empty( $this->_site->{$p} ) &&
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

				if ( ! $this->is_api_error( $site ) ) {
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
		 *
		 * @return false|object|string
		 */
		private function sync_install( $override = array(), $flush = false ) {
			$this->_logger->entrance();

			$site = $this->send_install_update( $override, $flush );

			if ( false === $site ) {
				// No sync required.
				return;
			}

			if ( $this->is_api_error( $site ) ) {
				// Failed to sync, don't update locally.
				return;
			}

			$plan              = $this->get_plan();
			$this->_site       = new FS_Site( $site );
			$this->_site->plan = $plan;

			$this->_store_site( true );
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

			$params = array();
			if ( isset( $this->_storage->uninstall_reason ) ) {
				$params['reason_id']   = $this->_storage->uninstall_reason->id;
				$params['reason_info'] = $this->_storage->uninstall_reason->info;
			}

			if ( ! $this->is_registered() && isset( $this->_storage->uninstall_reason ) ) {
				// Send anonymous uninstall event only if user submitted a feedback.
				$params['uid'] = $this->get_anonymous_id();
				$this->get_api_plugin_scope()->call( 'uninstall.json', 'put', $params );
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
		private function premium_plugin_basename() {
			return preg_replace( '/\//', '-premium/', $this->_free_plugin_basename, 1 );
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

		#region Plugin Information ------------------------------------------------------------------

		/**
		 * Load WordPress core plugin.php essential module.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.1
		 */
		private static function require_plugin_essentials() {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
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
				require_once( ABSPATH . 'wp-includes/pluggable.php' );
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

			return $this->apply_filters( 'plugin_version', $plugin_data['Version'] );
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
		 * Get plugin add-ons.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @since  1.1.7.3 If not yet loaded, fetch data from the API.
		 *
		 * @return FS_Plugin[]|false
		 */
		function get_addons() {
			$this->_logger->entrance();

			$all_addons = self::get_all_addons();

			/**
			 * @since 1.1.7.3 If not yet loaded, fetch data from the API.
			 */
			if ( ! is_array( $all_addons ) ||
			     ! isset( $all_addons[ $this->_plugin->id ] ) ||
			     ! is_array( $all_addons[ $this->_plugin->id ] ) ||
			     empty( $all_addons[ $this->_plugin->id ] )
			) {
				if ( $this->_has_addons ) {
					$addons = $this->_sync_addons();

					if ( ! empty( $addons ) ) {
						$all_addons = self::get_all_addons();
					}
				}
			}

			if ( ! is_array( $all_addons ) ||
			     ! isset( $all_addons[ $this->_plugin->id ] ) ||
			     ! is_array( $all_addons[ $this->_plugin->id ] ) ||
			     empty( $all_addons[ $this->_plugin->id ] )
			) {
				return false;
			}

			return $all_addons[ $this->_plugin->id ];
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

			return $this->has_active_license() && ( $this->_site->trial_plan_id == $this->_license->plan_id );
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

			if ( ! $this->has_paid_plan() ) {
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
		 * @return FS_Plugin_License
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
			if ( ! $this->is_api_error( $plans ) ) {
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
		 * @return FS_Plugin_License[]|object
		 */
		function _sync_licenses() {
			$licenses = $this->_fetch_licenses();
			if ( ! $this->is_api_error( $licenses ) ) {
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
			return ! $this->is_only_premium() && FS_Plan_Manager::instance()->has_free_plan( $this->_plans );
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
			fs_enqueue_local_style( 'fs_license_action', '/admin/license-activation.css' );

			$vars = array(
				'slug' => $this->_slug
			);

			fs_require_template( 'license-activation-modal.php', $vars );
		}

		/**
		 * @author Leo Fajardo (@leorw)
		 * @since  1.1.9
		 */
		function _activate_license_ajax_action() {
			if ( ! isset( $_POST['license-key'] ) ) {
				exit;
			}

			$license_key = trim( $_POST['license-key'] );
			if ( empty( $license_key ) ) {
				exit;
			}

			if ( $this->is_registered() ) {
				$api = $this->get_api_site_scope();
				$api->call( '/', 'put',
					array(
						'license_key' => $license_key
					)
				);
			} else {
				$this->opt_in( false, false, false, $license_key );
			}

			// Print '1' for successful operation.
			echo 1;
			exit;
		}

		#region URL Generators

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
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7
		 *
		 * @return bool
		 */
		function is_cron() {
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
			return is_admin() && ! $this->is_ajax() && ! $this->is_cron();
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
			if ( ! $this->_menu->is_top_level() ) {
				$parent_slug = $this->_menu->get_parent_slug();
				$menu_file   = ( false !== strpos( $parent_slug, '.php' ) ) ?
					$parent_slug :
					'admin.php';

				return add_query_arg( array_merge( $params, array(
					'page' => $this->_menu->get_slug( $page ),
				) ), admin_url( $menu_file, 'admin' ) );
			}

			if ( $this->_menu->is_cpt() ) {
				if ( empty( $page ) && $this->is_activation_mode() ) {
					return add_query_arg( array_merge( $params, array(
						'page' => $this->_menu->get_slug()
					) ), admin_url( 'admin.php', 'admin' ) );
				} else {
					if ( ! empty( $page ) ) {
						$params['page'] = $this->_menu->get_slug( $page );
					}

					return add_query_arg( $params, admin_url( $this->_menu->get_raw_slug(), 'admin' ) );
				}
			} else {
				return add_query_arg( array_merge( $params, array(
					'page' => $this->_menu->get_slug( $page ),
				) ), admin_url( 'admin.php', 'admin' ) );
			}
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

			$site = isset( $sites[ $this->_slug ] ) ? $sites[ $this->_slug ] : false;

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

			$params = array(
				'user_firstname'    => $current_user->user_firstname,
				'user_lastname'     => $current_user->user_lastname,
				'user_nickname'     => $current_user->user_nicename,
				'user_email'        => $current_user->user_email,
				'user_ip'           => WP_FS__REMOTE_ADDR,
				'plugin_slug'       => $this->_slug,
				'plugin_id'         => $this->get_id(),
				'plugin_public_key' => $this->get_public_key(),
				'plugin_version'    => $this->get_plugin_version(),
				'return_url'        => wp_nonce_url( $this->_get_admin_page_url(
					'',
					array( 'fs_action' => $this->_slug . '_activate_new' )
				), $this->_slug . '_activate_new' ),
				'account_url'       => wp_nonce_url( $this->_get_admin_page_url(
					'account',
					array( 'fs_action' => 'sync_user' )
				), 'sync_user' ),
				'site_uid'          => $this->get_anonymous_id(),
				'site_url'          => get_site_url(),
				'site_name'         => get_bloginfo( 'name' ),
				'platform_version'  => get_bloginfo( 'version' ),
				'php_version'       => phpversion(),
				'language'          => get_bloginfo( 'language' ),
				'charset'           => get_bloginfo( 'charset' ),
			);

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
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 *
		 * @param string|bool $email
		 * @param string|bool $first
		 * @param string|bool $last
		 * @param string|bool $license_key
		 *
		 * @return bool Is successful opt-in (or set to pending).
		 */
		function opt_in( $email = false, $first = false, $last = false, $license_secret_key = false ) {
			$this->_logger->entrance();

			if ( false === $email ) {
				$current_user = self::_get_current_wp_user();
				$email        = $current_user->user_email;
			}

			$fs_user = Freemius::_get_user_by_email( $email );
			if ( is_object( $fs_user ) && ! $this->is_pending_activation() ) {
				$this->install_with_current_user( false );

				return true;
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

			if ( is_string( $license_secret_key ) ) {
				$params['license_secret_key'] = $license_secret_key;
			}

			$params['format'] = 'json';

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
				     isset( $response->errors['http_request_failed'] ) &&
				     false !== strpos( $response->errors['http_request_failed'][0], 'sslv3 alert handshake' )
				) {
					// Failed due to old version of cURL or Open SSL (SSLv3 is not supported by CloudFlare).
					$url = 'http://' . substr( $url, 8 );

					$response = wp_remote_post( $url, array(
						'method'  => 'POST',
						'body'    => $params,
						'timeout' => 15,
					) );
				}

				if ( $response instanceof WP_Error ) {
					return false;
				}
			}

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$decoded = @json_decode( $response['body'] );

			if ( empty( $decoded ) ) {
				return false;
			}

			if ( isset( $decoded->error ) ) {
				return false;
			} else if ( isset( $decoded->pending_activation ) && $decoded->pending_activation ) {
				// Pending activation, add message.
				$this->set_pending_confirmation( false, false );

				return true;
			} else if ( isset( $decoded->install_secret_key ) ) {
				$this->install_with_new_user(
					$decoded->user_id,
					$decoded->user_public_key,
					$decoded->user_secret_key,
					$decoded->install_id,
					$decoded->install_public_key,
					$decoded->install_secret_key,
					false
				);

				return true;
			}

			return false;
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
		 *
		 * @return bool False if account already set.
		 */
		function setup_account( FS_User $user, FS_Site $site, $redirect = true ) {
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

			if ( $this->is_pending_activation() ) {
				// Remove pending activation sticky notice (if still exist).
				$this->_admin_notices->remove_sticky( 'activation_pending' );

				// Remove plugin from pending activation mode.
				unset( $this->_storage->is_pending_activation );

				if ( ! $this->is_paying() ) {
					$this->_admin_notices->add_sticky(
						sprintf( __fs( 'plugin-x-activation-message', $this->_slug ), '<b>' . $this->get_plugin_name() . '</b>' ),
						'activation_complete'
					);
				}
			}

			if ( $this->is_paying() && ! $this->is_premium() ) {
				$this->_admin_notices->add_sticky(
					sprintf(
						__fs( 'activation-with-plan-x-message', $this->_slug ),
						$this->_site->plan->title
					) . ' ' . $this->_get_latest_download_link( sprintf(
						__fs( 'download-latest-x-version', $this->_slug ),
						$this->_site->plan->title
					) ),
					'plan_upgraded',
					__fs( 'yee-haw', $this->_slug ) . '!'
				);
			}

			$plugin_id = fs_request_get( 'plugin_id', false );

			// Store activation time ONLY for plugins (not add-ons).
			if ( ! is_numeric( $plugin_id ) || ( $plugin_id == $this->_plugin->id ) ) {
				$this->_storage->activation_timestamp = WP_FS__SCRIPT_START_TIME;
			}

			if ( is_numeric( $plugin_id ) ) {
				if ( $plugin_id != $this->_plugin->id ) {
					// Add-on was installed - sync license right after install.
					if ( $redirect && fs_redirect( fs_nonce_url( $this->_get_admin_page_url(
							'account',
							array(
								'fs_action' => $this->_slug . '_sync_license',
								'plugin_id' => $plugin_id
							)
						), $this->_slug . '_sync_license' ) )
					) {
						exit();
					}

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
				if ( $redirect && fs_redirect( $this->get_after_activation_url( 'after_connect_url' ) ) ) {
					exit();
				}
			}
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

			if ( fs_request_is_action( $this->_slug . '_activate_new' ) ) {
//				check_admin_referer( $this->_slug . '_activate_new' );

				$this->_admin_notices->remove_sticky( 'connect_account' );

				if ( fs_request_has( 'user_secret_key' ) ) {
					$this->install_with_new_user(
						fs_request_get( 'user_id' ),
						fs_request_get( 'user_public_key' ),
						fs_request_get( 'user_secret_key' ),
						fs_request_get( 'install_id' ),
						fs_request_get( 'install_public_key' ),
						fs_request_get( 'install_secret_key' )
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
		 */
		private function install_with_new_user(
			$user_id,
			$user_public_key,
			$user_secret_key,
			$install_id,
			$install_public_key,
			$install_secret_key,
			$redirect = true
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

			$this->setup_account( $this->_user, $this->_site, $redirect );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 *
		 * @param bool $email
		 * @param bool $redirect
		 */
		private function set_pending_confirmation( $email = false, $redirect = true ) {
			// Install must be activated via email since
			// user with the same email already exist.
			$this->_storage->is_pending_activation = true;
			$this->_add_pending_activation_notice( $email );

			// Reload the page with with pending activation message.
			if ( $redirect && fs_redirect( $this->get_after_activation_url( 'after_pending_connect_url' ) ) ) {
				exit();
			}
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

			if ( fs_request_is_action( $this->_slug . '_activate_existing' ) && fs_request_is_post() ) {
//				check_admin_referer( 'activate_existing_' . $this->_plugin->public_key );

				$this->install_with_current_user();
			}
		}


		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7.4
		 *
		 * @param bool $redirect
		 */
		private function install_with_current_user( $redirect = true ) {
			$this->_admin_notices->remove_sticky( 'connect_account' );

			// Get current logged WP user.
			$current_user = self::_get_current_wp_user();

			// Find the relevant FS user by the email.
			$user = self::_get_user_by_email( $current_user->user_email );

			// We have to set the user before getting user scope API handler.
			$this->_user = $user;

			$extra_install_params = array(
				'uid' => $this->get_anonymous_id(),
			);

			/**
			 * @author Vova Feldman (@svovaf)
			 * @since  1.1.9 Add license key if given.
			 */
			$license_key = fs_request_get( 'license_secret_key' );

			if ( ! empty( $license_key ) ) {
				$extra_install_params['license_secret_key'] = $license_key;
			}

			// Install the plugin.
			$install = $this->get_api_user_scope()->call(
				"/plugins/{$this->get_id()}/installs.json",
				'post',
				$this->get_install_data_for_api( $extra_install_params, false, false )
			);

			if ( isset( $install->error ) ) {
				$this->_admin_notices->add(
					sprintf( __fs( 'could-not-activate-x', $this->_slug ), $this->get_plugin_name() ) . ' ' .
					__fs( 'contact-us-with-error-message', $this->_slug ) . ' ' . '<b>' . $install->error->message . '</b>',
					__fs( 'oops', $this->_slug ) . '...',
					'error'
				);

				return;
			}

			$site        = new FS_Site( $install );
			$this->_site = $site;
//				$this->_enrich_site_plan( false );

//				$this->_set_account( $user, $site );
//				$this->_sync_plans();

			$this->setup_account( $this->_user, $this->_site, $redirect );
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
					sprintf( __fs( 'could-not-activate-x', $this->_slug ), $this->get_plugin_name() ) . ' ' .
					__fs( 'contact-us-with-error-message', $this->_slug ) . ' ' . '<b>' . $addon_install->error->message . '</b>',
					__fs( 'oops', $this->_slug ) . '...',
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

		private $_menu_items = array();

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
						if ( $page === strtolower( $item['menu_slug'] ) ) {
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

			if ( $this->_menu->is_top_level() ) {
				$hook = $this->_menu->override_menu_item( array( &$this, '_connect_page_render' ) );

				if ( false === $hook ) {
					// Create new menu item just for the opt-in.
					$hook = add_menu_page(
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
					$activation_url = strtolower( $this->get_activation_url() );
					$request_url    = strtolower( $_SERVER['REQUEST_URI'] );

					if ( parse_url( $activation_url, PHP_URL_PATH ) !== parse_url( $request_url, PHP_URL_PATH ) ) {
						// Different path - DO NOT OVERRIDE PAGE.
						return;
					}

					$activation_url_params = array();
					parse_str( parse_url( $activation_url, PHP_URL_QUERY ), $activation_url_params );

					$request_url_params = array();
					parse_str( parse_url( $request_url, PHP_URL_QUERY ), $request_url_params );


					foreach ( $activation_url_params as $key => $val ) {
						if ( ! isset( $request_url_params[ $key ] ) || $val != $request_url_params[ $key ] ) {
							// Not matching query string - DO NOT OVERRIDE PAGE.
							return;
						}
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

			if ( $this->_menu->is_activation_page() ) {
				// Clean admin page from distracting content.
				self::_clean_admin_content_section();
			}

			if ( false !== $hook ) {
				if ( fs_request_is_action( $this->_slug . '_activate_existing' ) ) {
					add_action( "load-$hook", array( &$this, '_install_with_current_user' ) );
				} else if ( fs_request_is_action( $this->_slug . '_activate_new' ) ) {
					add_action( "load-$hook", array( &$this, '_install_with_new_user' ) );
				}
			}
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
		 * Add default Freemius menu items.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.0
		 */
		private function add_submenu_items() {
			$this->_logger->entrance();

			$this->do_action( 'before_admin_menu_init' );

			if ( ! $this->is_addon() ) {
				if ( ! $this->is_activation_mode() ) {
					if ( $this->is_registered() ) {
						// Add user account page.
						$this->add_submenu_item(
							__fs( 'account', $this->_slug ),
							array( &$this, '_account_page_render' ),
							$this->get_plugin_name() . ' &ndash; ' . __fs( 'account', $this->_slug ),
							'manage_options',
							'account',
							array( &$this, '_account_page_load' ),
							WP_FS__DEFAULT_PRIORITY,
							$this->_menu->is_submenu_item_visible( 'account' )
						);
					}

					// Add contact page.
					$this->add_submenu_item(
						__fs( 'contact-us', $this->_slug ),
						array( &$this, '_contact_page_render' ),
						$this->get_plugin_name() . ' &ndash; ' . __fs( 'contact-us', $this->_slug ),
						'manage_options',
						'contact',
						'Freemius::_clean_admin_content_section',
						WP_FS__DEFAULT_PRIORITY,
						$this->_menu->is_submenu_item_visible( 'contact' )
					);

					if ( $this->has_addons() ) {
						$this->add_submenu_item(
							__fs( 'add-ons', $this->_slug ),
							array( &$this, '_addons_page_render' ),
							$this->get_plugin_name() . ' &ndash; ' . __fs( 'add-ons', $this->_slug ),
							'manage_options',
							'addons',
							array( &$this, '_addons_page_load' ),
							WP_FS__LOWEST_PRIORITY - 1,
							$this->_menu->is_submenu_item_visible( 'addons' )
						);
					}

					$show_pricing = ( $this->has_paid_plan() && $this->_menu->is_submenu_item_visible( 'pricing' ) );
					// If user don't have paid plans, add pricing page
					// to support add-ons checkout but don't add the submenu item.
					// || (isset( $_GET['page'] ) && $this->_menu->get_slug( 'pricing' ) == $_GET['page']);

					// Add upgrade/pricing page.
					$this->add_submenu_item(
						( $this->is_paying() ? __fs( 'pricing', $this->_slug ) : __fs( 'upgrade', $this->_slug ) . '&nbsp;&nbsp;&#x27a4;' ),
						array( &$this, '_pricing_page_render' ),
						$this->get_plugin_name() . ' &ndash; ' . __fs( 'pricing', $this->_slug ),
						'manage_options',
						'pricing',
						'Freemius::_clean_admin_content_section',
						WP_FS__LOWEST_PRIORITY,
						$show_pricing
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
				'<span class="fs-submenu-item">%s</span>' :
				'<span class="fs-submenu-item fs-sub">%s</span>';

			ksort( $this->_menu_items );

			foreach ( $this->_menu_items as $priority => $items ) {
				foreach ( $items as $item ) {
					if ( ! isset( $item['url'] ) ) {
						$hook = add_submenu_page(
							$item['show_submenu'] ?
								$this->get_top_level_menu_slug() :
								null,
							$item['page_title'],
							sprintf( $item_template, $item['menu_title'] ),
							$item['capability'],
							$item['menu_slug'],
							$item['render_function']
						);

						if ( false !== $item['before_render_function'] ) {
							add_action( "load-$hook", $item['before_render_function'] );
						}
					} else {
						add_submenu_page(
							$this->get_top_level_menu_slug(),
							$item['page_title'],
							sprintf( $item_template, $item['menu_title'] ),
							$item['capability'],
							$item['menu_slug'],
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
				if ( $this->_menu->is_submenu_item_visible( 'support' ) ) {
					$this->add_submenu_link_item(
						$this->apply_filters( 'support_forum_submenu', __fs( 'support-forum', $this->_slug ) ),
						$this->apply_filters( 'support_forum_url', 'https://wordpress.org/support/plugin/' . $this->_slug ),
						'wp-support-forum',
						'read',
						50
					);
				}
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
		 */
		function add_submenu_item(
			$menu_title,
			$render_function,
			$page_title = false,
			$capability = 'manage_options',
			$menu_slug = false,
			$before_render_function = false,
			$priority = WP_FS__DEFAULT_PRIORITY,
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
				'menu_slug'              => $this->_menu->get_slug( is_string( $menu_slug ) ? $menu_slug : strtolower( $menu_title ) ),
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
			$priority = WP_FS__DEFAULT_PRIORITY
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
				'menu_slug'              => $this->_menu->get_slug( is_string( $menu_slug ) ? $menu_slug : strtolower( $menu_title ) ),
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
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.7
		 *
		 * @param string $tag
		 *
		 * @return string
		 */
		private function get_action_tag( $tag ) {
			return 'fs_' . $tag . '_' . $this->_slug;
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
		function add_action( $tag, $function_to_add, $priority = WP_FS__DEFAULT_PRIORITY, $accepted_args = 1 ) {
			$this->_logger->entrance( $tag );

			add_action( $this->get_action_tag( $tag ), $function_to_add, $priority, $accepted_args );
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
			array_unshift( $args, $this->_slug );

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

			$encrypted_site       = clone $this->_site;
			$encrypted_site->plan = $this->_encrypt_entity( $this->_site->plan );

			$sites                 = self::get_all_sites();
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
				$encrypted_plans[] = $this->_encrypt_entity( $this->_plans[ $i ] );
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

				$is_menu_item_account_visible = $this->_menu->is_submenu_item_visible( 'account' );

				if ( $user->is_verified &&
				     ( ! isset( $this->_user->is_verified ) || false === $this->_user->is_verified )
				) {
					$this->_user->is_verified = true;

					$this->do_action( 'account_email_verified', $user->email );

					$this->_admin_notices->add(
						__fs( 'email-verified-message', $this->_slug ),
						__fs( 'right-on', $this->_slug ) . '!',
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
					if ( fs_redirect( $this->_get_admin_page_url() ) ) {
						exit();
					}
				}
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

			if ( ! $this->is_api_error( $result ) ) {
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
		 * @param bool        $flush Since 1.1.7.3
		 *
		 * @return object|false New plugin tag info if exist.
		 */
		private function _fetch_newer_version( $plugin_id = false, $flush = true ) {
			$latest_tag = $this->_fetch_latest_version( $plugin_id, $flush );

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
		 * @param bool        $flush Since 1.1.7.3
		 *
		 * @return bool|FS_Plugin_Tag
		 */
		function get_update( $plugin_id = false, $flush = true ) {
			$this->_logger->entrance();

			if ( ! is_numeric( $plugin_id ) ) {
				$plugin_id = $this->_plugin->id;
			}

			$this->_check_updates( true, $plugin_id, $flush );
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
			if ( ! isset( $licenses->error ) ) {
				$this->_update_licenses( $licenses, $addon->slug );

				if ( ! $this->is_addon_installed( $addon->slug ) && FS_License_Manager::has_premium_license( $licenses ) ) {
					$plans_result = $this->get_api_site_or_plugin_scope()->get( "/addons/{$addon_id}/plans.json" );

					if ( ! isset( $plans_result->error ) ) {
						$plans = array();
						foreach ( $plans_result->plans as $plan ) {
							$plans[] = new FS_Plugin_Plan( $plan );
						}

						$this->_admin_notices->add_sticky(
							FS_Plan_Manager::instance()->has_free_plan( $plans ) ?
								sprintf(
									__fs( 'addon-successfully-upgraded-message', $this->_slug ),
									$addon->title
								) . ' ' . $this->_get_latest_download_link(
									__fs( 'download-latest-version', $this->_slug ),
									$addon_id
								)
								:
								sprintf(
									__fs( 'addon-successfully-purchased-message', $this->_slug ),
									$addon->title
								) . ' ' . $this->_get_latest_download_link(
									__fs( 'download-latest-version', $this->_slug ),
									$addon_id
								),
							'addon_plan_upgraded_' . $addon->slug,
							__fs( 'yee-haw', $this->_slug ) . '!'
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

			// Sync site info.
			$site = $this->send_install_update( array(), true );

			$plan_change = 'none';

			if ( $this->is_api_error( $site ) ) {
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
									__fs( 'server-blocking-access', $this->_slug ),
									$this->get_plugin_name(),
									'<a href="' . $api->get_url() . '" target="_blank">' . $api->get_url() . '</a>'
								) . '<br> ' . __fs( 'server-error-message', $this->_slug ) . var_export( $site->error, true ),
								__fs( 'oops', $this->_slug ) . '...',
								'error',
								$background,
								false,
								'api_blocked'
							);
						}
					} else {
						// Authentication params are broken.
						$this->_admin_notices->add(
							__fs( 'wrong-authentication-param-message', $this->_slug ),
							__fs( 'oops', $this->_slug ) . '...',
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
					if ( $site->is_trial() && ( ! $this->_site->is_trial() || $site->trial_ends != $this->_site->trial_ends ) ) {
						// New trial started.
						$this->_site = $site;
						$plan_change = 'trial_started';

						// Store trial plan information.
						$this->_enrich_site_trial_plan( true );

						// For trial with subscription use-case.
						$new_license = is_null( $site->license_id ) ? null : $this->_get_license_by_id( $site->license_id );

						if ( is_object( $new_license ) && ! $new_license->is_expired() ) {
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

						if ( $is_free && is_null( $new_license ) && $this->has_license() && $this->_license->is_cancelled ) {
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

			if ( $this->has_paid_plan() ) {
				switch ( $plan_change ) {
					case 'none':
						if ( ! $background && is_admin() ) {
							$plan = $this->is_trial() ?
								$this->_storage->trial_plan :
								$this->_site->plan;

							$this->_admin_notices->add(
								sprintf(
									__fs( 'plan-did-not-change-message', $this->_slug ) . ' ' .
									sprintf(
										'<a href="%s">%s</a>',
										$this->contact_url(
											'bug',
											sprintf( __fs( 'plan-did-not-change-email-message', $this->_slug ),
												strtoupper( $plan->name )
											)
										),
										__fs( 'contact-us-here', $this->_slug )
									),
									'<i><b>' . $plan->title . ( $this->is_trial() ? ' ' . __fs( 'trial', $this->_slug ) : '' ) . '</b></i>'
								),
								__fs( 'hmm', $this->_slug ) . '...'
							);
						}
						break;
					case 'upgraded':
						$this->_admin_notices->add_sticky(
							sprintf(
								__fs( 'plan-upgraded-message', $this->_slug ),
								'<i>' . $this->get_plugin_name() . '</i>'
							) . ( $this->is_premium() ? '' : ' ' . $this->_get_latest_download_link( sprintf(
									__fs( 'download-latest-x-version', $this->_slug ),
									$this->_site->plan->title
								) )
							),
							'plan_upgraded',
							__fs( 'yee-haw', $this->_slug ) . '!'
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
								__fs( 'plan-changed-to-x-message', $this->_slug ),
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
							sprintf( __fs( 'license-expired-blocking-message', $this->_slug ) ),
							'license_expired',
							__fs( 'hmm', $this->_slug ) . '...'
						);
						$this->_admin_notices->remove_sticky( 'plan_upgraded' );
						break;
					case 'cancelled':
						$this->_admin_notices->add(
							__fs( 'license-cancelled', $this->_slug ) . ' ' .
							sprintf(
								'<a href="%s">%s</a>',
								$this->contact_url( 'bug' ),
								__fs( 'contact-us-here', $this->_slug )
							),
							__fs( 'hmm', $this->_slug ) . '...',
							'error'
						);
						$this->_admin_notices->remove_sticky( 'plan_upgraded' );
						break;
					case 'expired':
						$this->_admin_notices->add_sticky(
							sprintf( __fs( 'license-expired-non-blocking-message', $this->_slug ), $this->_site->plan->title ),
							'license_expired',
							__fs( 'hmm', $this->_slug ) . '...'
						);
						$this->_admin_notices->remove_sticky( 'plan_upgraded' );
						break;
					case 'trial_started':
						$this->_admin_notices->add_sticky(
							sprintf(
								__fs( 'trial-started-message', $this->_slug ),
								'<i>' . $this->get_plugin_name() . '</i>'
							) . ( $this->is_premium() ? '' : ' ' . $this->_get_latest_download_link( sprintf(
									__fs( 'download-latest-x-version', $this->_slug ),
									$this->_storage->trial_plan->title
								) ) ),
							'trial_started',
							__fs( 'yee-haw', $this->_slug ) . '!'
						);

						$this->_admin_notices->remove_sticky( array(
							'trial_promotion',
						) );
						break;
					case 'trial_expired':
						$this->_admin_notices->add_sticky(
							__fs( 'trial-expired-message', $this->_slug ),
							'trial_expired',
							__fs( 'hmm', $this->_slug ) . '...'
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

			$api     = $this->get_api_site_scope();
			$license = $api->call( "/licenses/{$premium_license->id}.json", 'put' );

			if ( $this->is_api_error( $license ) ) {
				if ( ! $background ) {
					$this->_admin_notices->add(
						__fs( 'license-activation-failed-message', $this->_slug ) . '<br> ' .
						__fs( 'server-error-message', $this->_slug ) . ' ' . var_export( $license, true ),
						__fs( 'hmm', $this->_slug ) . '...',
						'error'
					);
				}

				return;
			}

			$premium_license = new FS_Plugin_License( $license );

			// Updated site plan.
			$site = $this->get_api_site_scope()->get( '/', true );
			if ( ! $this->is_api_error( $site ) ) {
				$this->_site = new FS_Site( $site );
			}
			$this->_update_site_license( $premium_license );
			$this->_enrich_site_plan( false );

			$this->_store_account();

			if ( ! $background ) {
				$this->_admin_notices->add_sticky(
					__fs( 'license-activated-message', $this->_slug ) .
					( $this->is_premium() ? '' : ' ' . $this->_get_latest_download_link( sprintf(
							__fs( 'download-latest-x-version', $this->_slug ),
							$this->_site->plan->title
						) ) ),
					'license_activated',
					__fs( 'yee-haw', $this->_slug ) . '!'
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

			if ( ! is_object( $this->_license ) ) {
				$this->_admin_notices->add(
					sprintf( __fs( 'no-active-license-message', $this->_slug ), $this->_site->plan->title ),
					__fs( 'hmm', $this->_slug ) . '...'
				);

				return;
			}

			$api     = $this->get_api_site_scope();
			$license = $api->call( "/licenses/{$this->_site->license_id}.json", 'delete' );

			if ( isset( $license->error ) ) {
				$this->_admin_notices->add(
					__fs( 'license-deactivation-failed-message', $this->_slug ) . '<br> ' .
					__fs( 'server-error-message', $this->_slug ) . ' ' . var_export( $license->error, true ),
					__fs( 'hmm', $this->_slug ) . '...',
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
					sprintf( __fs( 'license-deactivation-message', $this->_slug ), $this->_site->plan->title ),
					__fs( 'ok', $this->_slug )
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
					sprintf( __fs( 'plan-x-downgraded-message', $this->_slug ),
						$plan->title,
						human_time_diff( time(), strtotime( $this->_license->expiration ) )
					)
				);

				// Store site updates.
				$this->_store_site();
			} else {
				$this->_admin_notices->add(
					__fs( 'plan-downgraded-failure-message', $this->_slug ),
					__fs( 'oops', $this->_slug ) . '...',
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

			if ( $this->is_trial() ) {
				// Already in trial mode.
				$this->_admin_notices->add(
					__fs( 'in-trial-mode', $this->_slug ),
					__fs( 'oops', $this->_slug ) . '...',
					'error'
				);

				return false;
			}

			if ( $this->_site->is_trial_utilized() ) {
				// Trial was already utilized.
				$this->_admin_notices->add(
					__fs( 'trial-utilized', $this->_slug ),
					__fs( 'oops', $this->_slug ) . '...',
					'error'
				);

				return false;
			}

			if ( false !== $plan_name ) {
				$plan = $this->get_plan_by_name( $plan_name );

				if ( false === $plan ) {
					// Plan doesn't exist.
					$this->_admin_notices->add(
						sprintf( __fs( 'trial-plan-x-not-exist', $this->_slug ), $plan_name ),
						__fs( 'oops', $this->_slug ) . '...',
						'error'
					);

					return false;
				}

				if ( ! $plan->has_trial() ) {
					// Plan doesn't exist.
					$this->_admin_notices->add(
						sprintf( __fs( 'plan-x-no-trial', $this->_slug ), $plan_name ),
						__fs( 'oops', $this->_slug ) . '...',
						'error'
					);

					return false;
				}
			} else {
				if ( ! $this->has_trial_plan() ) {
					// None of the plans have a trial.
					$this->_admin_notices->add(
						__fs( 'no-trials', $this->_slug ),
						__fs( 'oops', $this->_slug ) . '...',
						'error'
					);

					return false;
				}

				$plans_with_trial = FS_Plan_Manager::instance()->get_trial_plans( $this->_plans );

				$plan = $plans_with_trial[0];
			}

			$api  = $this->get_api_site_scope();
			$plan = $api->call( "plans/{$plan->id}/trials.json", 'post' );

			if ( $this->is_api_error( $plan ) ) {
				// Some API error while trying to start the trial.
				$this->_admin_notices->add(
					__fs( 'unexpected-api-error', $this->_slug ) . ' ' . var_export( $plan, true ),
					__fs( 'oops', $this->_slug ) . '...',
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

			if ( ! $this->is_trial() ) {
				$this->_admin_notices->add(
					__fs( 'trial-cancel-no-trial-message', $this->_slug ),
					__fs( 'oops', $this->_slug ) . '...',
					'error'
				);

				return;
			}

			$api  = $this->get_api_site_scope();
			$site = $api->call( 'trials.json', 'delete' );

			$trial_cancelled = false;

			if ( ! $this->is_api_error( $site ) ) {
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

				// Clear trial plan information.
				unset( $this->_storage->trial_plan );

				if ( ! $this->is_addon() ||
				     ! $this->deactivate_premium_only_addon_without_license( true )
				) {
					$this->_admin_notices->add(
						sprintf( __fs( 'trial-cancel-message', $this->_slug ), $this->_storage->trial_plan->title )
					);
				}
			} else {
				$this->_admin_notices->add(
					__fs( 'trial-cancel-failure-message', $this->_slug ),
					__fs( 'oops', $this->_slug ) . '...',
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
		 * @param bool        $flush Since 1.1.7.3
		 *
		 * @return object|false Plugin latest tag info.
		 */
		function _fetch_latest_version( $addon_id = false, $flush = true ) {
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
				$flush
			);

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
				header( "Content-Disposition: attachment; filename={$slug}" . ( ! $is_addon && $is_premium ? '-premium' : '' ) . ".zip" );
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
		 */
		private function _check_updates( $background = false, $plugin_id = false, $flush = true ) {
			$this->_logger->entrance();

			// Check if there's a newer version for download.
			$new_version = $this->_fetch_newer_version( $plugin_id, $flush );

			$update = null;
			if ( is_object( $new_version ) ) {
				$update = new FS_Plugin_Tag( $new_version );

				if ( ! $background ) {
					$this->_admin_notices->add(
						sprintf(
							__fs( 'version-x-released', $this->_slug ) . ' ' . __fs( 'please-download-x', $this->_slug ),
							$update->version,
							sprintf(
								'<a href="%s" target="_blank">%s</a>',
								$this->get_account_url( 'download_latest' ),
								sprintf( __fs( 'latest-x-version', $this->_slug ), $this->_site->plan->title )
							)
						),
						__fs( 'new', $this->_slug ) . '!'
					);
				}
			} else if ( false === $new_version && ! $background ) {
				$this->_admin_notices->add(
					__fs( 'you-have-latest', $this->_slug ),
					__fs( 'you-are-good', $this->_slug )
				);
			}

			$this->_store_update( $update, true, $plugin_id );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.4
		 *
		 * @param bool $flush Since 1.1.7.3 by default add 24 hour cache.
		 *
		 * @return FS_Plugin[]
		 *
		 * @uses   FS_Api
		 */
		private function _sync_addons( $flush = false ) {
			$this->_logger->entrance();

			$result = $this->get_api_site_or_plugin_scope()->get( '/addons.json?enriched=true', $flush );

			$addons = array();
			if ( ! $this->is_api_error( $result ) ) {
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
		private function _update_email( $new_email ) {
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

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.1
		 *
		 * @param mixed $result
		 *
		 * @return bool Is API result contains an error.
		 */
		private function is_api_error( $result ) {
			return ( is_object( $result ) && isset( $result->error ) ) ||
			       is_string( $result );
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
					__fs( 'verification-email-sent-message', $this->_slug ),
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
		 * @return string
		 */
		private function get_activation_url() {
			return $this->apply_filters( 'connect_url', $this->_get_admin_page_url() );
		}

		/**
		 * Get the URL of the page that should be loaded after the user connect or skip in the opt-in screen.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @param string $filter Filter name.
		 *
		 * @return string
		 */
		private function get_after_activation_url( $filter ) {
			$first_time_path = $this->_menu->get_first_time_path();

			return $this->apply_filters(
				$filter,
				empty( $first_time_path ) ?
					$this->_get_admin_page_url() :
					$first_time_path
			);
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
			$action    = fs_get_action();

			switch ( $action ) {
				case 'delete_account':
					check_admin_referer( $action );

					if ( $plugin_id == $this->get_id() ) {
						$this->delete_account_event();

						// Clear user and site.
						$this->_site = null;
						$this->_user = null;

						if ( fs_redirect( $this->get_activation_url() ) ) {
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

				case 'change_owner':
					$state = fs_request_get( 'state', 'init' );
					switch ( $state ) {
						case 'init':
							$candidate_email = fs_request_get( 'candidate_email', '' );

							if ( $this->init_change_owner( $candidate_email ) ) {
								$this->_admin_notices->add( sprintf( __fs( 'change-owner-request-sent-x', $this->_slug ), '<b>' . $this->_user->email . '</b>' ) );
							}
							break;
						case 'owner_confirmed':
							$candidate_email = fs_request_get( 'candidate_email', '' );

							$this->_admin_notices->add( sprintf( __fs( 'change-owner-request_owner-confirmed', $this->_slug ), '<b>' . $candidate_email . '</b>' ) );
							break;
						case 'candidate_confirmed':
							if ( $this->complete_change_owner() ) {
								$this->_admin_notices->add_sticky(
									sprintf( __fs( 'change-owner-request_candidate-confirmed', $this->_slug ), '<b>' . $this->_user->email . '</b>' ),
									'ownership_changed',
									__fs( 'congrats', $this->_slug ) . '!'
								);
							} else {
								// @todo Handle failed ownership change message.
							}
							break;
					}

					return;

				case 'update_email':
					check_admin_referer( 'update_email' );

					$new_email = fs_request_get( 'fs_email_' . $this->_slug, '' );
					$result    = $this->_update_email( $new_email );

					if ( isset( $result->error ) ) {
						switch ( $result->error->code ) {
							case 'user_exist':
								$this->_admin_notices->add(
									__fs( 'user-exist-message', $this->_slug ) . ' ' .
									sprintf( __fs( 'user-exist-message_ownership', $this->_slug ), '<b>' . $new_email . '</b>' ) .
									sprintf(
										'<a style="margin-left: 10px;" href="%s"><button class="button button-primary">%s &nbsp;&#10140;</button></a>',
										$this->get_account_url( 'change_owner', array(
											'state'           => 'init',
											'candidate_email' => $new_email
										) ),
										__fs( 'change-ownership', $this->_slug )
									),
									__fs( 'oops', $this->_slug ) . '...',
									'error'
								);
								break;
						}
					} else {
						$this->_admin_notices->add( __fs( 'email-updated-message', $this->_slug ) );
					}

					return;

				case 'update_user_name':
					check_admin_referer( 'update_user_name' );

					$result = $this->update_user_name();

					if ( isset( $result->error ) ) {
						$this->_admin_notices->add(
							__fs( 'name-update-failed-message', $this->_slug ),
							__fs( 'oops', $this->_slug ) . '...',
							'error'
						);
					} else {
						$this->_admin_notices->add( __fs( 'name-updated-message', $this->_slug ) );
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
					if ( 'update_' . $p === $action ) {
						check_admin_referer( $action );

						$this->_logger->log( $action );

						$site_property                      = substr( $p, strlen( 'site_' ) );
						$site_property_value                = fs_request_get( 'fs_' . $p . '_' . $this->_slug, '' );
						$this->get_site()->{$site_property} = $site_property_value;

						// Store account after modification.
						$this->_store_site();

						$this->do_action( 'account_property_edit', 'site', $site_property, $site_property_value );

						$this->_admin_notices->add( sprintf(
							__fs( 'x-updated', $this->_slug ),
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
			     ! $this->is_sync_executed()
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
			fs_require_once_template( 'connect.php', $vars );
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
					sprintf( __fs( 'addons-info-external-message', $this->_slug ), '<b>' . $this->get_plugin_name() . '</b>' ),
					__fs( 'heads-up', $this->_slug ),
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
		 * @param bool $flush
		 *
		 * @return FS_Api
		 */
		function get_api_site_scope( $flush = false ) {
			if ( ! isset( $this->_site_api ) || $flush ) {
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
				sprintf( __fs( 'no-commitment-for-x-days', $this->_slug ), $paid_plan->trial_period ) :
				__fs( 'no-cc-required', $this->_slug ) . '!';


			$total_paid_plans = count( $this->_plans ) - ( FS_Plan_Manager::instance()->has_free_plan( $this->_plans ) ? 1 : 0 );

			if ( $total_paid_plans === $trial_plans_count ) {
				// All paid plans have trials.
				$message = sprintf(
					__fs( 'hey', $this->_slug ) . '! ' . __fs( 'trial-x-promotion-message', $this->_slug ),
					sprintf( '<b>%s</b>', $this->get_plugin_name() ),
					strtolower( __fs( 'awesome', $this->_slug ) ),
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
					__fs( 'hey', $this->_slug ) . '! ' . __fs( 'trial-x-promotion-message', $this->_slug ),
					sprintf( '<b>%s</b>', $this->get_plugin_name() ),
					$plans_string,
					$paid_plan->trial_period
				);
			}

			$message .= ' ' . $cc_string;

			// Add start trial button.
			$message .= ' ' . sprintf(
					'<a style="margin-left: 10px; vertical-align: super;" href="%s"><button class="button button-primary">%s &nbsp;&#10140;</button></a>',
					$upgrade_url,
					__fs( 'start-free-trial', $this->_slug )
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
						__fs( 'upgrade', $this->_slug ),
						$this->get_upgrade_url(),
						false,
						7,
						'upgrade'
					);
				}

				if ( $this->has_addons() ) {
					$this->add_plugin_action_link(
						__fs( 'add-ons', $this->_slug ),
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

			$link_text = __fs( $this->is_free_plan() ? 'activate-license' : 'change-license', $this->_slug );

			$this->add_plugin_action_link(
				$link_text,
				'#',
				false,
				11,
				( 'activate-license ' . $this->_slug )
			);
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
			$plugin_fs = false;

			if ( ! $this->is_addon() ) {
				$first_time_path = $this->_menu->get_first_time_path();
				$plugin_fs       = $this;
				$url             = $plugin_fs->is_activation_mode() ?
					$plugin_fs->get_activation_url() :
					( empty( $first_time_path ) ?
						$this->_get_admin_page_url() :
						$first_time_path );
			} else {
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
				if ( ! $this->is_paying_or_trial() || $this->is_premium() ) {
					/*
					 * This HTML element is used to identify the correct plugin when attaching an event to its Deactivate link.
					 *
					 * If user is paying or in trial and have the free version installed,
					 * assume that the deactivation is for the upgrade process, so this is not needed.
					 */
					$deactivate_link .= '<i class="fs-slug" data-slug="' . $this->_slug . '"></i>';
				}

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

		#region Permissions ------------------------------------------------------------------

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

		#endregion Permissions ------------------------------------------------------------------

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