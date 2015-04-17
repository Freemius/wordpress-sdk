<?php
    /*
    Plugin Name: Freemius Test Plugin Add-On
    Version: 1.0.0
    Author: Freemius
    Author URI: https://freemius.com
    License: GPLv2
    Domain Path: /langs
    */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    if (!class_exists('Test_Plugin_Addon')) {

        if ( ! defined( 'WP_TP__SLUG' ) ) {
            define( 'WP_TP__SLUG', 'test-plugin' );
        }

        define( 'WP_TPA__SLUG', 'test-plugin-addon' );

        class Test_Plugin_Addon {
            function __construct() {
                add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
            }

            function admin_menu() {
                // Add sub-menu settings item.
                add_submenu_page(
                    // Use parent plugin slug.
                    WP_TP__SLUG,
                    __( 'Test Plugin Add On Settings', WP_TPA__SLUG ),
                    __( 'Add On Settings', WP_TPA__SLUG ),
                    'manage_options',
                    WP_TPA__SLUG,
                    array( &$this, 'render_settings' )
                );
            }

            function render_settings() 
            {
                ?>
                <h2><?php _e('Test Plugin Add On Settings', WP_TP__SLUG) ?></h2>
                <p>
                    Welcome to Freemius test plugin.
                </p>
            <?php
            }
        }

        function test_plugin_addon() {
            if ( ! class_exists( 'Test_Plugin' ) ) {
                // Add admin notice since add-on should not work without the plugin.
                
                return;
            }

            global $test_plugin_addon;
            if ( ! isset( $test_plugin_addon ) ) {
                $test_plugin_addon = new Test_Plugin_Addon();
            }

            return $test_plugin_addon;
        }

        // Init add-on only after all active plugins code
        // was included to make sure the parent plugin loaded first.
        add_action( 'plugins_loaded', 'test_plugin_addon' );
    }