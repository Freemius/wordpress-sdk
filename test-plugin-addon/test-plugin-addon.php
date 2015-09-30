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

    if ( ! class_exists('Test_Plugin_Addon') ) {

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
            global $test_plugin_addon;
            
            if ( ! isset( $test_plugin_addon ) ) {
                $test_plugin_addon = new Test_Plugin_Addon();
            }

            return $test_plugin_addon;
        }
        
        function is_parent_plugin_activated()
        {
		    $active_plugins = get_option( 'active_plugins' );
		    foreach ( $active_plugins as $plugin_basename ) {
		        // Check if any of the active files is the parent plugin.
			    if ( false !== strpos( $plugin_basename, '/test-plugin.php' ) ) {
				    return true;
			    }
		    }

		    return false;
        }
        
        if ( class_exists( 'Test_Plugin' ) )
        {
            // If parent plugin already loaded, init add-on.
            test_plugin_addon();
        }
        else if ( is_parent_plugin_activated() )
        {
			// Init add-on only after the parent plugins is loaded.
			// 
			// Make sure the parent plugin has the following call:
			//  do_action( 'test_plugin_loaded' );
			//
			add_action( 'test_plugin_loaded', 'test_plugin_addon' );
        }
        else
        {
            // Parent plugin is not activated.
            //  1) Deactivate the add-on
            //  2) Show WP fata error message.
            
            deactivate_plugins( basename( __FILE__ ) );

		    wp_die( 
		        'The Add On cannot run without the parent plugin. Please activate the parent plugin and then try again.', 
		        'Error', 
		        array( 'back_link' => true ) 
		    );
        }
    }
