<?php
    /**
     * @package     Freemius
     * @copyright   Copyright (c) 2015, Freemius, Inc.
     * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since       1.1.1
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    global $fs_active_plugins;

    require_once 'generic-table.php';

    $off_text     = fs_text_x_inline( 'Off', 'as turned off' );
    $on_text      = fs_text_x_inline( 'On', 'as turned on' );
    $page_title   = fs_text_inline( 'Freemius Debug' );
    $sdk_text     = fs_text_inline( 'SDK' );
    $version_text = 'v.' . $fs_active_plugins->newest->version;

    // For some reason css was missing
    fs_enqueue_local_style( 'fs_common', '/admin/common.css' );

    $has_any_active_clone = false;

    $common_params = array_merge(
        $VARS,
        array(
            'module_types' => array(
                WP_FS__MODULE_TYPE_PLUGIN,
                WP_FS__MODULE_TYPE_THEME,
            ),
            'is_multisite' => is_multisite(),
            'fs_options'   => FS_Options::instance( WP_FS__ACCOUNTS_OPTION_NAME, true ),
        ) );

    $auto_off_timestamp = wp_next_scheduled( 'fs_debug_turn_off_logging_hook' ) * 1000;

    $common_params = array_merge(
        $VARS,
        array(
            'module_types' => array(
                WP_FS__MODULE_TYPE_PLUGIN,
                WP_FS__MODULE_TYPE_THEME,
            ),
            'is_multisite' => is_multisite(),
            'fs_options'   => FS_Options::instance( WP_FS__ACCOUNTS_OPTION_NAME, true ),
        ) );

    $auto_off_timestamp = wp_next_scheduled( 'fs_debug_turn_off_logging_hook' ) * 1000;

?>
<h1><?php echo $page_title . ' - ' . $sdk_text . ' ' . $version_text ?></h1>
<?php
    fs_require_template( 'debug/debug-logger-switch.php' );

    fs_require_template( 'debug/debug-action-buttons.php' );

    fs_require_template( 'debug/debug-defined-variables.php' );

    fs_require_template( 'debug/debug-sdk-versions.php' );

    fs_require_template( 'debug/debug-modules.php', $common_params );

    fs_require_template( 'debug/debug-module-installs.php', $common_params );

    fs_require_template( 'debug/debug-addons.php', $common_params );

    fs_require_template( 'debug/debug-users.php', $common_params );

    fs_require_template( 'debug/debug-licenses.php', $common_params );

    fs_require_template( 'debug/debug-scheduled-crons.php', $common_params );

    fs_require_template( 'debug/debug-logger.php', $common_params );
