<?php
/**
 * Integrate the Freemius SDK. Include this file in the plugin to initiate Freemius.
 *
 * @package     Freemius
 * @copyright   Copyright (c) 2015, Freemius, Inc.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Freemius SDK Version.
 *
 * @var string
 */
$this_sdk_version = '1.1.8.1';

// Region SDK Selection Logic --------------------------------------------------------------------!
/**
 * Special logic added on 1.1.6 to make sure that every Freemius powered plugin
 * will ALWAYS be loaded with the newest SDK from the active Freemius powered plugins.
 *
 * Since Freemius SDK is backward compatible, this will make sure that all Freemius powered
 * plugins will run correctly.
 *
 * @since 1.1.6
 */

global $fs_active_plugins;

$this_sdk_relative_path = plugin_basename( dirname( __FILE__ ) );

if ( ! isset( $fs_active_plugins ) ) {
	// Require SDK essentials.
	include_once dirname( __FILE__ ) . '/includes/fs-essential-functions.php';

	// Load all Freemius powered active plugins.
	$fs_active_plugins = get_option( 'fs_active_plugins', new stdClass() );

	if ( ! isset( $fs_active_plugins->plugins ) ) {
		$fs_active_plugins->plugins = array();
	}
}

if ( ! function_exists( 'fs_find_direct_caller_plugin_file' ) ) {
	include_once dirname( __FILE__ ) . '/includes/supplements/fs-essential-functions-1.1.7.1.php';
}

// Update current SDK info based on the SDK path.
if ( ! isset( $fs_active_plugins->plugins[ $this_sdk_relative_path ] )
	|| $this_sdk_version != $fs_active_plugins->plugins[ $this_sdk_relative_path ]->version
) {
	$fs_active_plugins->plugins[ $this_sdk_relative_path ] = (object) array(
		'version'     => $this_sdk_version,
		'timestamp'   => time(),
		'plugin_path' => plugin_basename( fs_find_direct_caller_plugin_file( __FILE__ ) ),
	);
}

$is_current_sdk_newest = isset( $fs_active_plugins->newest ) && ( $this_sdk_relative_path == $fs_active_plugins->newest->sdk_path );

if ( ! isset( $fs_active_plugins->newest ) ) {
	/**
	 * This will be executed only once, for the first time a Freemius powered plugin is activated.
	 */
	fs_update_sdk_newest_version( $this_sdk_relative_path, $fs_active_plugins->plugins[ $this_sdk_relative_path ]->plugin_path );

	$is_current_sdk_newest = true;
} else if ( version_compare( $fs_active_plugins->newest->version, $this_sdk_version, '<' ) ) {
		/**
		 * Current SDK is newer than the newest stored SDK.
		 */
		fs_update_sdk_newest_version( $this_sdk_relative_path, $fs_active_plugins->plugins[ $this_sdk_relative_path ]->plugin_path );

	if ( class_exists( 'Freemius' ) ) {
		// Older SDK version was already loaded.
		if ( ! $fs_active_plugins->newest->in_activation ) {
			// Re-order plugins to load this plugin first.
			fs_newest_sdk_plugin_first();
		}

		// Refresh page.
		if ( fs_redirect( $_SERVER['REQUEST_URI'] ) ) {
			exit();
		}
	}
} else {
	if ( ! function_exists( 'get_plugins' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$is_newest_sdk_plugin_activate = is_plugin_active( $fs_active_plugins->newest->plugin_path );

	if ( $is_current_sdk_newest
	&& ! $is_newest_sdk_plugin_activate
	&& ! $fs_active_plugins->newest->in_activation
	) {
		// If current SDK is the newest and the plugin is NOT active, it means
		// that the current plugin in activation mode.
		$fs_active_plugins->newest->in_activation = true;
		update_option( 'fs_active_plugins', $fs_active_plugins );
	}

	$is_newest_sdk_path_valid = ( $is_newest_sdk_plugin_activate || $fs_active_plugins->newest->in_activation ) && file_exists( fs_normalize_path( WP_PLUGIN_DIR . '/' . $this_sdk_relative_path . '/start.php' ) );

	if ( ! $is_newest_sdk_path_valid && ! $is_current_sdk_newest ) {
		// Plugin with newest SDK is no longer active, or SDK was moved to a different location.
		unset( $fs_active_plugins->plugins[ $fs_active_plugins->newest->sdk_path ] );
	}

	if ( ! ( $is_newest_sdk_plugin_activate || $fs_active_plugins->newest->in_activation )
	|| ! $is_newest_sdk_path_valid
	// Is newest SDK downgraded.
	|| ( $this_sdk_relative_path == $fs_active_plugins->newest->sdk_path
	&& version_compare( $fs_active_plugins->newest->version, $this_sdk_version, '>' ) )
	) {
		/**
		 * Plugin with newest SDK is no longer active.
		 *    OR
		 * The newest SDK was in the current plugin. BUT, seems like the version of
		 * the SDK was downgraded to a lower SDK.
		 */
		// Find the active plugin with the newest SDK version and update the newest reference.
		fs_fallback_to_newest_active_sdk();
	} else {
		if ( $is_newest_sdk_plugin_activate
			&& $this_sdk_relative_path == $fs_active_plugins->newest->sdk_path
			&& ( $fs_active_plugins->newest->in_activation
				|| ( class_exists( 'Freemius' ) && ( ! defined( 'WP_FS__SDK_VERSION' ) || version_compare( WP_FS__SDK_VERSION, $this_sdk_version, '<' ) ) )         )
		) {
			if ( $fs_active_plugins->newest->in_activation ) {
				// Plugin no more in activation.
				$fs_active_plugins->newest->in_activation = false;
				update_option( 'fs_active_plugins', $fs_active_plugins );
			}

			// Reorder plugins to load plugin with newest SDK first.
			if ( fs_newest_sdk_plugin_first() ) {
				// Refresh page after re-order to make sure activated plugin loads newest SDK.
				if ( class_exists( 'Freemius' ) ) {
					if ( fs_redirect( $_SERVER['REQUEST_URI'] ) ) {
						exit();
					}
				}
			}
		}
	}
}

if ( class_exists( 'Freemius' ) ) {
	// SDK was already loaded.
	return;
}

if ( version_compare( $this_sdk_version, $fs_active_plugins->newest->version, '<' ) ) {
	$newest_sdk_starter = fs_normalize_path( WP_PLUGIN_DIR . '/' . $fs_active_plugins->newest->sdk_path . '/start.php' );

	if ( file_exists( $newest_sdk_starter ) ) {
		// Reorder plugins to load plugin with newest SDK first.
		fs_newest_sdk_plugin_first();

		// There's a newer SDK version, load it instead of the current one!
		include_once $newest_sdk_starter;

		return;
	}
}

// Endregion SDK Selection Logic --------------------------------------------------------------------!
// Region Hooks & Filters Collection --------------------------------------------------------------------!
/**
 * Freemius hooks (actions & filters) tags structure:
 *
 *      fs_{filter/action_name}_{plugin_slug}
 *
 * --------------------------------------------------------
 *
 * Usage with WordPress' add_action() / add_filter():
 *
 *      add_action('fs_{filter/action_name}_{plugin_slug}', $callable);
 *
 * --------------------------------------------------------
 *
 * Usage with Freemius' instance add_action() / add_filter():
 *
 *      // No need to add 'fs_' prefix nor '_{plugin_slug}' suffix.
 *      my_freemius()->add_action('{action_name}', $callable);
 *
 * --------------------------------------------------------
 *
 * Freemius filters collection:
 *
 *      fs_connect_url_{plugin_slug}
 *      fs_trial_promotion_message_{plugin_slug}
 *      fs_is_long_term_user_{plugin_slug}
 *      fs_uninstall_reasons_{plugin_slug}
 *      fs_is_plugin_update_{plugin_slug}
 *      fs_api_domains_{plugin_slug}
 *      fs_email_template_sections_{plugin_slug}
 *      fs_support_forum_submenu_{plugin_slug}
 *      fs_support_forum_url_{plugin_slug}
 *      fs_connect_message_{plugin_slug}
 *      fs_connect_message_on_update_{plugin_slug}
 *      fs_uninstall_confirmation_message_{plugin_slug}
 *      fs_pending_activation_message_{plugin_slug}
 *      fs_is_submenu_visible_{plugin_slug}
 *      fs_plugin_icon_{plugin_slug}
 *      fs_show_trial_{plugin_slug}
 *
 * --------------------------------------------------------
 *
 * Freemius actions collection:
 *
 *      fs_after_license_loaded_{plugin_slug}
 *      fs_after_license_change_{plugin_slug}
 *      fs_after_plans_sync_{plugin_slug}
 *
 *      fs_after_account_details_{plugin_slug}
 *      fs_after_account_user_sync_{plugin_slug}
 *      fs_after_account_plan_sync_{plugin_slug}
 *      fs_before_account_load_{plugin_slug}
 *      fs_after_account_connection_{plugin_slug}
 *      fs_account_property_edit_{plugin_slug}
 *      fs_account_email_verified_{plugin_slug}
 *      fs_account_page_load_before_departure_{plugin_slug}
 *      fs_before_account_delete_{plugin_slug}
 *      fs_after_account_delete_{plugin_slug}
 *
 *      fs_sdk_version_update_{plugin_slug}
 *      fs_plugin_version_update_{plugin_slug}
 *
 *      fs_initiated_{plugin_slug}
 *      fs_after_init_plugin_registered_{plugin_slug}
 *      fs_after_init_plugin_anonymous_{plugin_slug}
 *      fs_after_init_plugin_pending_activations_{plugin_slug}
 *      fs_after_init_addon_registered_{plugin_slug}
 *      fs_after_init_addon_anonymous_{plugin_slug}
 *      fs_after_init_addon_pending_activations_{plugin_slug}
 *
 *      fs_after_premium_version_activation_{plugin_slug}
 *      fs_after_free_version_reactivation_{plugin_slug}
 *
 *      fs_after_uninstall_{plugin_slug}
 *      fs_before_admin_menu_init_{plugin_slug}
 */

// Endregion Hooks & Filters Collection --------------------------------------------------------------------!
if ( ! class_exists( 'Freemius' ) ) {

	if ( ! defined( 'WP_FS__SDK_VERSION' ) ) {
		define( 'WP_FS__SDK_VERSION', $this_sdk_version );
	}

	// Configuration should be loaded first.
	include_once dirname( __FILE__ ) . '/config.php';

	// Logger must be loaded before any other.
	include_once WP_FS__DIR_INCLUDES . '/class-fs-logger.php';
	include_once WP_FS__DIR_INCLUDES . '/debug/debug-bar-start.php';

	include_once WP_FS__DIR_INCLUDES . '/fs-core-functions.php';
	// require_once WP_FS__DIR_INCLUDES . '/managers/class-fs-abstract-manager.php';
	include_once WP_FS__DIR_INCLUDES . '/managers/class-fs-option-manager.php';
	include_once WP_FS__DIR_INCLUDES . '/managers/class-fs-cache-manager.php';
	include_once WP_FS__DIR_INCLUDES . '/managers/class-fs-admin-notice-manager.php';
	include_once WP_FS__DIR_INCLUDES . '/managers/class-fs-admin-menu-manager.php';
	include_once WP_FS__DIR_INCLUDES . '/managers/class-fs-key-value-storage.php';
	include_once WP_FS__DIR_INCLUDES . '/managers/class-fs-license-manager.php';
	include_once WP_FS__DIR_INCLUDES . '/managers/class-fs-plan-manager.php';
	include_once WP_FS__DIR_INCLUDES . '/managers/class-fs-plugin-manager.php';
	include_once WP_FS__DIR_INCLUDES . '/entities/class-fs-entity.php';
	include_once WP_FS__DIR_INCLUDES . '/entities/class-fs-scope-entity.php';
	include_once WP_FS__DIR_INCLUDES . '/entities/class-fs-user.php';
	include_once WP_FS__DIR_INCLUDES . '/entities/class-fs-site.php';
	include_once WP_FS__DIR_INCLUDES . '/entities/class-fs-plugin.php';
	include_once WP_FS__DIR_INCLUDES . '/entities/class-fs-plugin-info.php';
	include_once WP_FS__DIR_INCLUDES . '/entities/class-fs-plugin-tag.php';
	include_once WP_FS__DIR_INCLUDES . '/entities/class-fs-plugin-plan.php';
	include_once WP_FS__DIR_INCLUDES . '/entities/class-fs-pricing.php';
	include_once WP_FS__DIR_INCLUDES . '/entities/class-fs-plugin-license.php';
	include_once WP_FS__DIR_INCLUDES . '/entities/class-fs-subscription.php';
	include_once WP_FS__DIR_INCLUDES . '/class-fs-api.php';
	include_once WP_FS__DIR_INCLUDES . '/class-fs-plugin-updater.php';
	include_once WP_FS__DIR_INCLUDES . '/class-fs-security.php';
	include_once WP_FS__DIR_INCLUDES . '/class-freemius-abstract.php';
	include_once WP_FS__DIR_INCLUDES . '/class-freemius.php';

	/**
	 * Quick shortcut to get Freemius for specified plugin.
	 * Used by various templates.
	 *
	 * @param string $slug Plugin slug.
	 *
	 * @return Freemius
	 */
	function freemius( $slug ) {

		return Freemius::instance( $slug );
	}

	/**
	 * Initialise Freemius
	 *
	 * @param string $slug       Plugin slug.
	 * @param number $plugin_id  Plugin ID.
	 * @param string $public_key Public Key.
	 * @param bool   $is_live    Is live or test plugin.
	 * @param bool   $is_premium Hints freemius if running the premium plugin or not.
	 *
	 * @return Freemius
	 */
	function fs_init( $slug, $plugin_id, $public_key, $is_live = true, $is_premium = true ) {

		$fs = Freemius::instance( $slug );
		$fs->init( $plugin_id, $public_key, $is_live, $is_premium );

		return $fs;
	}

	/**
	 * Dynamically initialise Freemius
	 *
	 * @param array   [string]string $plugin Array of plugin settings.
	 *
	 * @return Freemius
	 * @throws Freemius_Exception
	 */
	function fs_dynamic_init( $plugin ) {

		$fs = Freemius::instance( $plugin['slug'] );
		$fs->dynamic_init( $plugin );

		return $fs;
	}

	/**
	 * Dump log.
	 *
	 * @access public
	 * @return void
	 */
	function fs_dump_log() {

		FS_Logger::dump();
	}
}
