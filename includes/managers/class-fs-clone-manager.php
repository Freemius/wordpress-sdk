<?php
    /**
     * @package   Freemius
     * @copyright Copyright (c) 2015, Freemius, Inc.
     * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @author    Leo Fajardo (@leorw)
     * @since     2.4.3
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    /**
     * Manages the detection of clones and provides the logged-in WordPress user with options for manually resolving them.
     *
     * @property int    $clone_identification_timestamp
     * @property int    $temporary_duplicate_mode_selection_timestamp
     * @property int    $temporary_duplicate_notice_shown_timestamp
     * @property string $request_handler_id
     * @property int    $request_handler_timestamp
     * @property int    $request_handler_retries_count
     */
    class FS_Clone_Manager {
        /**
         * @var FS_Option_Manager
         */
        private $_storage;
        /**
         * @var array {
         * @type int    $clone_identification_timestamp
         * @type int    $temporary_duplicate_mode_selection_timestamp
         * @type int    $temporary_duplicate_notice_shown_timestamp
         * @type string $request_handler_id
         * @type int    $request_handler_timestamp
         * @type int    $request_handler_retries_count
         * }
         */
        private $_data;
        /**
         * @var FS_Admin_Notices
         */
        private $_notices;
        /**
         * @var FS_Logger
         */
        protected $_logger;

        /**
         * @var int 3 minutes
         */
        const CLONE_RESOLUTION_MAX_EXECUTION_TIME = 180;
        /**
         * @var int
         */
        const CLONE_RESOLUTION_MAX_RETRIES = 3;
        /**
         * @var int
         */
        const TEMPORARY_DUPLICATE_PERIOD = WP_FS__TIME_WEEK_IN_SEC * 2;
        /**
         * @var string
         */
        const OPTION_NAME = 'clone_resolution';
        /**
         * @var string
         */
        const OPTION_TEMPORARY_DUPLICATE = 'temporary_duplicate';
        /**
         * @var string
         */
        const OPTION_NEW_HOME = 'new_home';

        #--------------------------------------------------------------------------------
        #region Singleton
        #--------------------------------------------------------------------------------

        /**
         * @var FS_Clone_Manager
         */
        private static $_instance;

        /**
         * @return FS_Clone_Manager
         */
        static function instance() {
            if ( ! isset( self::$_instance ) ) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        #endregion

        private function __construct() {
            $this->_storage = FS_Option_Manager::get_manager( WP_FS___OPTION_PREFIX . 'clone_management', true );
            $this->_data    = $this->_storage->get_option( self::OPTION_NAME, array() );
            $this->_notices = FS_Admin_Notices::instance( self::OPTION_NAME );
            $this->_logger  = FS_Logger::get_logger( WP_FS__SLUG . '_' . '_clone_manager', WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );

            $defaults = array(
                'clone_identification_timestamp'               => null,
                'temporary_duplicate_mode_selection_timestamp' => null,
                'temporary_duplicate_notice_shown_timestamp'   => null,
                'request_handler_id'                           => null,
                'request_handler_timestamp'                    => null,
                'request_handler_retries_count'                => null,
            );

            if ( ! is_array( $this->_data ) ) {
                $this->_data = $defaults;
            } else {
                foreach ( $defaults as $name => $value ) {
                    $this->_data[ $name ] = isset( $this->_data[ $name ] ) ?
                        $this->_data[ $name ] :
                        $value;
                }
            }
        }

        /**
         * Retrieves the timestamp that was stored when a clone was identified.
         *
         * @return int|null
         */
        function get_clone_identification_timestamp() {
            return $this->clone_identification_timestamp;
        }

        /**
         * Stores the time when a clone was identified.
         */
        function store_clone_identification_timestamp() {
            $this->clone_identification_timestamp = time();
        }

        /**
         * Retrieves the timestamp for the temporary duplicate mode's expiration.
         *
         * @return int
         */
        function get_temporary_duplicate_expiration_timestamp() {
            $temporary_duplicate_mode_start_timestamp = $this->was_temporary_duplicate_mode_selected() ?
                $this->temporary_duplicate_mode_selection_timestamp :
                $this->get_clone_identification_timestamp();

            return ( $temporary_duplicate_mode_start_timestamp + self::TEMPORARY_DUPLICATE_PERIOD );
        }

        /**
         * Determines if the SDK should handle clones. The SDK handles clones only up to 3 times with 3 min interval.
         *
         * @return bool
         */
        private function should_handle_clones() {
            if ( ! isset( $this->request_handler_timestamp ) ) {
                return true;
            }

            if ( $this->request_handler_retries_count >= self::CLONE_RESOLUTION_MAX_RETRIES ) {
                return false;
            }

            // Give the logic that handles clones enough time to finish (it is given 3 minutes for now).
            return ( time() > ( $this->request_handler_timestamp + self::CLONE_RESOLUTION_MAX_EXECUTION_TIME ) );
        }

        /**
         * Executes the clones handler logic if it should be executed, i.e., based on the return value of the should_handle_clones() method.
         */
        function maybe_run_clone_resolution() {
            if ( ! $this->should_handle_clones() ) {
                return;
            }

            $this->request_handler_retries_count = isset( $this->request_handler_retries_count ) ?
                ( $this->request_handler_retries_count + 1 ) :
                1;

            $this->request_handler_timestamp = time();

            $handler_id               = ( rand() . microtime() );
            $this->request_handler_id = $handler_id;

            // Add cookies to trigger request with the same user access permissions.
            $cookies = array();
            foreach ( $_COOKIE as $name => $value ) {
                $cookies[] = new WP_Http_Cookie( array(
                    'name'  => $name,
                    'value' => $value,
                ) );
            }

            wp_remote_post(
                admin_url( 'admin-post.php' ),
                array(
                    'method'    => 'POST',
                    'body'      => array(
                        'action'     => 'fs_clone_resolution',
                        'handler_id' => $handler_id,
                    ),
                    'timeout'   => 0.01,
                    'blocking'  => false,
                    'sslverify' => false,
                    'cookies'   => $cookies,
                )
            );
        }

        /**
         * Executes the clones handler logic.
         */
        function _handle_clone_resolution() {
            $handler_id = fs_request_get( 'handler_id' );

            if ( empty( $handler_id ) ) {
                return;
            }

            if (
                ! isset( $this->request_handler_id ) ||
                $this->request_handler_id !== $handler_id
            ) {
                return;
            }

            $this->handle_clones();
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.4.3
         */
        function _init() {
            if ( is_admin() ) {
                if ( Freemius::is_admin_post() ) {
                    add_action( 'admin_post_fs_clone_resolution', array( $this, '_handle_clone_resolution' ) );
                }

                if ( ! empty( $this->get_clone_identification_timestamp() ) ) {
                    if ( Freemius::is_ajax() ) {
                        Freemius::add_ajax_action_static( 'handle_clone_resolution', array( $this, '_clone_resolution_action_ajax_handler' ) );
                    } else if ( ! Freemius::is_cron() && ! Freemius::is_admin_post() ) {
                        $this->maybe_show_clone_admin_notice();

                        add_action( 'admin_footer', array( $this, '_add_clone_resolution_javascript' ) );
                    }
                }
            }
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.4.3
         */
        function _add_clone_resolution_javascript() {
            $vars = array( 'ajax_action' => Freemius::get_ajax_action_static( 'handle_clone_resolution' ) );

            fs_require_once_template( 'clone-resolution-js.php', $vars );
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.4.3
         */
        function _clone_resolution_action_ajax_handler() {
            $this->_logger->entrance();

            check_ajax_referer( Freemius::get_ajax_action_static( 'handle_clone_resolution' ), 'security' );

            $clone_action = fs_request_get( 'clone_action' );

            if ( empty( $clone_action ) ) {
                Freemius::shoot_ajax_failure( array(
                    'message'      => fs_text_inline( 'Invalid clone resolution action.', 'invalid-clone-resolution-action-error' ),
                    'redirect_url' => '',
                ) );
            }

            $result = array();

            if ( self::OPTION_TEMPORARY_DUPLICATE === $clone_action ) {
                $this->store_temporary_duplicate_timestamp();
            } else {
                $result = $this->resolve_cloned_sites( $clone_action );
            }

            Freemius::shoot_ajax_success( $result );
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.4.3
         *
         * @param string $clone_action
         */
        private function resolve_cloned_sites( $clone_action ) {
            $this->_logger->entrance();

            $instances_with_clone_count = 0;
            $instance_with_error        = null;
            $has_error                  = false;

            $instances = Freemius::_get_all_instances();

            foreach ( $instances as $instance ) {
                if ( ! $instance->is_registered() ) {
                    continue;
                }

                if ( ! $instance->is_clone() ) {
                    continue;
                }

                $instances_with_clone_count ++;

                if ( FS_Clone_Manager::OPTION_NEW_HOME === $clone_action ) {
                    $instance->sync_install( array( 'is_new_site' => true ), true );
                } else {
                    $instance->_handle_long_term_duplicate();

                    if ( ! is_object( $instance->get_site() ) ) {
                        $has_error = true;

                        if ( ! is_object( $instance_with_error ) ) {
                            $instance_with_error = $instance;
                        }
                    }
                }
            }

            $redirect_url = '';

            if (
                1 === $instances_with_clone_count &&
                $has_error
            ) {
                $redirect_url = $instance_with_error->get_activation_url();
            }

            return ( array( 'redirect_url' => $redirect_url ) );
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.4.3
         */
        private function handle_clones() {
            $this->_logger->entrance();

            $current_url = fs_strip_url_protocol( untrailingslashit( get_site_url() ) );
            $has_clone   = false;

            $is_localhost = FS_Site::is_localhost_by_address( $current_url );

            $instances = Freemius::_get_all_instances();

            foreach ( $instances as $instance ) {
                if ( ! $instance->is_registered() ) {
                    continue;
                }

                if ( ! $instance->is_clone() ) {
                    continue;
                }

                $current_install = $instance->get_site();

                // Try to find a different install of the context product that is associated with the current URL and load it.
                $result = $instance->get_api_user_scope()->get( "/plugins/{$instance->get_id()}/installs.json?search=" . urlencode( $current_url ) . "&all=true", true );

                $associated_install = null;

                if ( $instance->is_api_result_object( $result, 'installs' ) ) {
                    foreach ( $result->installs as $install ) {
                        if ( $install->id == $current_install->id ) {
                            continue;
                        }

                        // Found a different install that is associated with the current URL, load it and replace the current install with it if no updated install is found.
                        $associated_install = $install;

                        break;
                    }
                }

                if ( is_object( $associated_install ) ) {
                    // Replace the current install with a different install that is associated with the current URL.
                    $instance->store_site( new FS_Site( clone $associated_install ) );
                    $instance->sync_install( array( 'is_new_site' => true ), true );

                    continue;
                }

                if ( ! WP_FS__IS_LOCALHOST_FOR_SERVER && ! $is_localhost ) {
                    $has_clone = true;
                    continue;
                }

                $license_key = false;

                if ( $instance->is_premium() ) {
                    $license = $instance->_get_license();

                    if ( is_object( $license ) && ! $license->is_utilized( $is_localhost ) ) {
                        $license_key = $license->secret_key;
                    }
                }

                $instance->delete_current_install( true );

                $instance->opt_in(
                    false,
                    false,
                    false,
                    $license_key,
                    false,
                    false,
                    false,
                    null,
                    array(),
                    false
                );

                if ( ! is_object( $instance->get_site() ) ) {
                    $instance->restore_backup_site();
                    $has_clone = true;
                }
            }

            if ( $has_clone ) {
                $this->store_clone_identification_timestamp();
                $this->clear_temporary_duplicate_notice_shown_timestamp();
            }
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.4.3
         */
        private function maybe_show_clone_admin_notice() {
            $this->_logger->entrance();

            $first_instance_with_clone = null;

            $site_urls                        = array();
            $sites_with_license_urls          = array();
            $sites_with_premium_version_count = 0;
            $product_titles                   = array();

            $instances = Freemius::_get_all_instances();

            foreach ( $instances as $instance ) {
                if ( ! $instance->is_registered()  ) {
                    continue;
                }

                if ( ! $instance->is_clone() ) {
                    continue;
                }

                $install = $instance->get_site();

                $site_urls[]      = $install->url;
                $product_titles[] = $instance->get_plugin_title();

                if ( is_null( $first_instance_with_clone ) ) {
                    $first_instance_with_clone = $instance;
                }

                if ( is_object( $instance->_get_license() ) ) {
                    $sites_with_license_urls[] = $install->url;
                }

                if ( $instance->is_premium() ) {
                    $sites_with_premium_version_count ++;
                }
            }

            if ( empty( $sites_with_license_urls ) ) {
                $this->remove_temporary_duplicate_notice();
            }

            if ( empty( $site_urls ) && empty( $sites_with_license_urls ) ) {
                return;
            }

            $site_urls               = array_unique( $site_urls );
            $sites_with_license_urls = array_unique( $sites_with_license_urls );

            $module_label              = fs_text_inline( 'products', 'products' );
            $admin_notice_module_title = null;

            $has_temporary_duplicate_mode_expired = $this->has_temporary_duplicate_mode_expired();

            if (
                ! $this->was_temporary_duplicate_mode_selected() ||
                $has_temporary_duplicate_mode_expired
            ) {
                if ( ! empty( $site_urls ) ) {
                    if ( $has_temporary_duplicate_mode_expired ) {
                        $this->remove_temporary_duplicate_notice();
                    }

                    fs_enqueue_local_style( 'fs_clone_resolution_notice', '/admin/clone-resolution.css' );

                    $this->add_manual_clone_resolution_admin_notice(
                        $product_titles,
                        $site_urls,
                        get_site_url(),
                        ( count( $site_urls ) === count( $sites_with_license_urls ) ),
                        ( count( $site_urls ) === $sites_with_premium_version_count )
                    );
                }

                return;
            }

            if ( empty( $sites_with_license_urls ) ) {
                return;
            }

            if ( $this->is_temporary_duplicate_notice_shown() ) {
                return;
            }

            $last_time_temporary_duplicate_notice_shown  = $this->last_time_temporary_duplicate_notice_was_shown();
            $was_temporary_duplicate_notice_shown_before = is_numeric( $last_time_temporary_duplicate_notice_shown );

            if ( $was_temporary_duplicate_notice_shown_before ) {
                $temporary_duplicate_mode_expiration_timestamp = $this->get_temporary_duplicate_expiration_timestamp();
                $current_time                                  = time();

                if (
                    $current_time > $temporary_duplicate_mode_expiration_timestamp ||
                    $current_time < ( $temporary_duplicate_mode_expiration_timestamp - ( 2 * WP_FS__TIME_24_HOURS_IN_SEC ) )
                ) {
                    // Do not show the notice if the temporary duplicate mode has already expired or it will expire more than 2 days from now.
                    return;
                }
            }

            if ( 1 === count( $sites_with_license_urls ) ) {
                $module_label              = $first_instance_with_clone->get_module_label( true );
                $admin_notice_module_title = $first_instance_with_clone->get_plugin_title();
            }

            fs_enqueue_local_style( 'fs_clone_resolution_notice', '/admin/clone-resolution.css' );

            $this->add_temporary_duplicate_sticky_notice(
                $this->get_temporary_duplicate_admin_notice_string( $sites_with_license_urls, $product_titles, $module_label ),
                $admin_notice_module_title
            );
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.4.3
         *
         * @return string
         */
        private function get_temporary_duplicate_admin_notice_string( $site_urls, $product_titles, $module_label ) {
            $this->_logger->entrance();

            $temporary_duplicate_end_date = $this->get_temporary_duplicate_expiration_timestamp();
            $temporary_duplicate_end_date = date( 'M j, Y', $temporary_duplicate_end_date );

            $current_url = fs_strip_url_protocol( get_site_url() );

            $total_sites = count( $site_urls );
            $sites_list  = '';

            $total_products = count( $product_titles );
            $products_list  = '';

            if ( $total_sites > 1 ) {
                foreach ( $site_urls as $site_url ) {
                    $sites_list .= sprintf( '<li>%s</li>', $site_url );
                }

                $sites_list = '<ol class="fs-sites-list">' . $sites_list . '</ol>';
            }

            if ( $total_products > 1 ) {
                foreach ( $product_titles as $product_title ) {
                    $products_list .= sprintf( '<li>%s</li>', $product_title );
                }

                $products_list = '<ol>' . $products_list . '</ol>';
            }

            return sprintf(
                sprintf(
                    '<div>%s</div>',
                    ( 1 === $total_sites ?
                        sprintf( '<p>%s</p>', fs_esc_html_inline( 'This website, %s, is a temporary duplicate of %s.', 'temporary-duplicate-message' ) ) :
                        sprintf( '<p>%s:</p>', fs_esc_html_inline( 'This website, %s, is a temporary duplicate of these sites', 'temporary-duplicate-of-sites-message' ) ) . '%s' )
                ) . '%s',
                sprintf( '<strong>%s</strong>', $current_url ),
                ( 1 === $total_sites ?
                    sprintf( '<strong>%s</strong>', $site_urls[0] ) :
                    $sites_list ),
                sprintf(
                    '<div class="fs-clone-resolution-options-container fs-duplicate-site-options"><p>%s</p>%s<p>%s</p></div>',
                    sprintf(
                        fs_esc_html_inline( "%s automatic security & feature updates and paid functionality will keep working without interruptions until %s (or when your license expires, whatever comes first).", 'duplicate-site-confirmation-message' ),
                        ( 1 === $total_products ?
                            sprintf(
                                fs_esc_html_x_inline( "The %s's", '"The <product_label>", e.g.: "The plugin"', 'the-product-x'),
                                "<strong>{$module_label}</strong>"
                            ) :
                            fs_esc_html_inline( "The following products'", 'the-following-products' ) ),
                        sprintf( '<strong>%s</strong>', $temporary_duplicate_end_date )
                    ),
                    ( 1 === $total_products ?
                        '' :
                        sprintf( '<div>%s</div>', $products_list )
                    ),
                    sprintf(
                        fs_esc_html_inline( 'If this is a long term duplicate, to keep automatic updates and paid functionality after %s, please %s.', 'duplicate-site-message' ),
                        sprintf( '<strong>%s</strong>', $temporary_duplicate_end_date),
                        sprintf( '<a href="#" id="fs_temporary_duplicate_license_activation_link" data-clone-action="temporary_duplicate_license_activation">%s</a>', fs_esc_html_inline( 'activate a license here', 'activate-license-here' ) )
                    )
                )
            );
        }
        
        /**
         * Adds a notice that provides the logged-in WordPress user with manual clone resolution options.
         *
         * @param string[] $product_titles
         * @param string[] $site_urls
         * @param string   $current_url
         * @param bool     $has_license
         * @param bool     $is_premium
         */
        private function add_manual_clone_resolution_admin_notice(
            $product_titles,
            $site_urls,
            $current_url,
            $has_license = false,
            $is_premium = false
        ) {
            $this->_logger->entrance();

            $total_sites = count( $site_urls );
            $sites_list  = '';

            $total_products = count( $product_titles );
            $products_list  = '';

            if ( 1 === $total_products ) {
                $notice_header = sprintf(
                    '<div class="fs-notice-header"><p>%s</p></div>',
                    fs_esc_html_inline( '%1$s has been placed into safe mode because we noticed that %2$s is an exact copy of %3$s.', 'single-cloned-site-safe-mode-message' )
                );
            } else {
                $notice_header = sprintf(
                    '<div class="fs-notice-header"><p>%s</p></div>',
                    ( 1 === $total_sites ) ?
                        fs_esc_html_inline( 'The following products have been placed into safe mode because we noticed that %2$s is an exact copy of %3$s:%1$s', 'multiple-products-cloned-site-safe-mode-message' ) :
                        fs_esc_html_inline( 'The following products have been placed into safe mode because we noticed that %2$s is an exact copy of these sites:%3$s%1$s', 'multiple-products-multiple-cloned-sites-safe-mode-message' )
                );

                foreach ( $product_titles as $product_title ) {
                    $products_list .= sprintf( '<li>%s</li>', $product_title );
                }

                $products_list = '<ol>' . $products_list . '</ol>';

                foreach ( $site_urls as $site_url ) {
                    $sites_list .= sprintf(
                        '<li><a href="%s" target="_blank">%s</a></li>',
                        $site_url,
                        fs_strip_url_protocol( $site_url )
                    );
                }

                $sites_list = '<ol>' . $sites_list . '</ol>';
            }

            $remote_site_link = '<b>' . (1 === $total_sites ?
                sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    $site_urls[0],
                    fs_strip_url_protocol( $site_urls[0] )
                ) :
                fs_text_inline( 'the above-mentioned sites', 'above-mentioned-sites' )) . '</b>';

            $current_site_link = sprintf(
                '<b><a href="%s" target="_blank">%s</a></b>',
                $current_url,
                fs_strip_url_protocol( $current_url )
            );

            $button_template = '<button class="button" data-clone-action="%s">%s</button>';
            $option_template = '<div class="fs-clone-resolution-option"><strong>%s</strong><p>%s</p><div>%s</div></div>';

            $duplicate_option = sprintf(
                $option_template,
                fs_esc_html_inline( 'Is %2$s a duplicate of %3$s?', 'duplicate-site-confirmation-message' ),
                fs_esc_html_inline( 'Yes, %2$s is a duplicate of %3$s for the purpose of testing, staging, or development.', 'duplicate-site-message' ),
                sprintf(
                    $button_template,
                    'long_term_duplicate',
                    fs_text_inline( 'Long-Term Duplicate', 'long-term-duplicate' )
                ) .
                ($this->has_temporary_duplicate_mode_expired() ?
                    '' :
                    sprintf(
                        $button_template,
                        'temporary_duplicate',
                        fs_text_inline( 'Temporary Duplicate', 'temporary-duplicate' )
                    ))
            );

            $migration_option = sprintf(
                $option_template,
                fs_esc_html_inline( 'Is %2$s the new home of %3$s?', 'migrate-site-confirmation-message' ),
                sprintf(
                    fs_esc_html_inline( 'Yes, %%2$s is replacing %%3$s. I would like to migrate my %s from %%3$s to %%2$s.', 'migrate-site-message' ),
                    ( $has_license ? fs_text_inline( 'license', 'license' ) : fs_text_inline( 'data', 'data' ) )
                ),
                sprintf(
                    $button_template,
                    'new_home',
                    $has_license ?
                        fs_text_inline( 'Migrate License', 'migrate-product-license' ) :
                        fs_text_inline( 'Migrate', 'migrate-product-data' )
                )
            );

            $new_website = sprintf(
                $option_template,
                fs_esc_html_inline( 'Is %2$s a new website?', 'new-site-confirmation-message' ),
                fs_esc_html_inline( 'Yes, %2$s is a new and different website that is separate from %3$s.', 'new-site-message' ) .
                ($is_premium ?
                    ' ' . fs_text_inline( 'It requires license activation.', 'new-site-requires-license-activation-message' ) :
                    ''
                ),
                sprintf(
                    $button_template,
                    'new_website',
                    ( ! $is_premium || ! $has_license ) ?
                        fs_text_inline( 'New Website', 'new-website' ) :
                        fs_text_inline( 'Activate License', 'activate-license' )
                )
            );

            /**
             * %1$s - single product's title or product titles list.
             * %2$s - site's URL.
             * %3$s - single install's URL or install URLs list.
             */
            $message = sprintf(
                $notice_header .
                '<div class="fs-clone-resolution-options-container">' .
                $duplicate_option .
                $migration_option .
                $new_website . '</div>',
                // %1$s
                ( 1 === $total_products ?
                    sprintf( '<b>%s</b>', $product_titles[0] ) :
                    ( 1 === $total_sites ?
                        sprintf( '<div>%s</div>', $products_list ) :
                        sprintf( '<div><p><strong>%s</strong>:</p>%s</div>', fs_esc_html_x_inline( 'Products', 'Clone resolution admin notice products list label', 'products' ), $products_list ) )
                ),
                // %2$s
                $current_site_link,
                // %3$s
                ( 1 === $total_sites ?
                    $remote_site_link :
                    $sites_list )
            );

            $this->_notices->add(
                $message,
                '',
                'warn',
                false,
                'clone_resolution_options_notice'
            );
        }

        /**
         * Determines if the temporary duplicate mode has already expired.
         *
         * @return bool
         */
        function has_temporary_duplicate_mode_expired() {
            $temporary_duplicate_mode_start_timestamp = $this->was_temporary_duplicate_mode_selected() ?
                $this->temporary_duplicate_mode_selection_timestamp :
                $this->get_clone_identification_timestamp();

            if ( ! is_numeric( $temporary_duplicate_mode_start_timestamp ) ) {
                return false;
            }

            return ( time() > ( $temporary_duplicate_mode_start_timestamp + self::TEMPORARY_DUPLICATE_PERIOD ) );
        }

        /**
         * Determines if the logged-in WordPress user manually selected the temporary duplicate mode for the site.
         *
         * @return bool
         */
        function was_temporary_duplicate_mode_selected() {
            return (
                isset( $this->temporary_duplicate_mode_selection_timestamp ) &&
                is_numeric( $this->temporary_duplicate_mode_selection_timestamp )
            );
        }

        /**
         * Stores the time when the logged-in WordPress user selected the temporary duplicate mode for the site.
         */
        private function store_temporary_duplicate_timestamp() {
            $this->temporary_duplicate_mode_selection_timestamp = time();
        }

        /**
         * Removes the notice that is shown when the logged-in WordPress user has selected the temporary duplicate mode for the site.
         */
        function remove_temporary_duplicate_notice() {
            $this->_notices->remove_sticky( 'temporary_duplicate_notice' );
        }

        /**
         * Determines if the temporary duplicate notice is currently being shown.
         *
         * @return bool
         */
        function is_temporary_duplicate_notice_shown() {
            return $this->_notices->has_sticky( 'temporary_duplicate_notice' );
        }

        /**
         * Determines the last time the temporary duplicate notice was shown.
         *
         * @return int|null
         */
        function last_time_temporary_duplicate_notice_was_shown() {
            return ( ! isset( $this->temporary_duplicate_notice_shown_timestamp ) ) ?
                null :
                $this->temporary_duplicate_notice_shown_timestamp;
        }

        /**
         * Clears the time that has been stored when the temporary duplicate notice was shown.
         */
        function clear_temporary_duplicate_notice_shown_timestamp() {
            $this->temporary_duplicate_notice_shown_timestamp = null;
        }

        /**
         * Adds a temporary duplicate notice that provides the logged-in WordPress user with an option to activate a license for the site.
         *
         * @param string      $message
         * @param string|null $plugin_title
         */
        function add_temporary_duplicate_sticky_notice( $message, $plugin_title = null ) {
            $this->_logger->entrance();

            $this->_notices->add_sticky(
                $message,
                'temporary_duplicate_notice',
                '',
                'promotion',
                null,
                null,
                $plugin_title,
                true
            );

            $this->temporary_duplicate_notice_shown_timestamp = time();
        }

        #--------------------------------------------------------------------------------
        #region Magic methods
        #--------------------------------------------------------------------------------

        function __set( $name, $value ) {
            if ( ! array_key_exists( $name, $this->_data ) ) {
                return;
            }

            $this->_data[ $name ] = $value;

            $this->_storage->set_option( self::OPTION_NAME, $this->_data, true );
        }

        function __isset( $name ) {
            return isset( $this->_data[ $name ] );
        }

        function __get( $name ) {
            return array_key_exists( $name, $this->_data ) ?
                $this->_data[ $name ] :
                null;
        }

        #endregion
    }