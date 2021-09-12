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

        function init() {
            if ( Freemius::is_admin_post() ) {
                add_action( 'admin_post_fs_clone_resolution', array( &$this, '_handle_clone_resolution' ) );
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
        function maybe_run_clone_resolution_handler() {
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

            Freemius::handle_clones();
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
        function add_manual_clone_resolution_admin_notice(
            $product_titles,
            $site_urls,
            $current_url,
            $has_license = false,
            $is_premium = false
        ) {
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
                    $sites_list .= sprintf( '<li>%s</li>', $site_url );
                }

                $sites_list = '<ol>' . $sites_list . '</ol>';
            }

            /**
             * %1$s - single product's title or product titles list.
             * %2$s - site's URL.
             * %3$s - single install's URL or install URLs list.
             * %4$s - duplicate type options (temporary or long term).
             * %5$s - migration or "new home" option.
             * %6$s - new website option.
             */
            $notice = ( $notice_header . '%4$s%5$s%6$s' );

            $above_mentioned_sites_text = fs_text_inline( 'the above-mentioned sites', 'above-mentioned-sites' );

            $message = sprintf(
                $notice,
                // %1$s
                ( 1 === $total_products ?
                    sprintf( '<b>%s</b>', $product_titles[0] ) :
                    ( 1 === $total_sites ?
                        sprintf( '<div>%s</div>', $products_list ) :
                        sprintf( '<div><p><strong>%s</strong>:</p>%s</div>', fs_esc_html_x_inline( 'Products', 'Clone resolution admin notice products list label', 'products' ), $products_list ) )
                ),
                // %2$s
                sprintf( '<strong>%s</strong>', $current_url ),
                // %3$s
                ( 1 === $total_sites ?
                    sprintf( '<strong>%s</strong>', $site_urls[0] ) :
                    $sites_list ),
                // %4$s
                sprintf(
                    '<div class="fs-clone-resolution-options-container fs-duplicate-site-options"><p>%s</p><p>%s</p><div>%s</div></div>',
                    sprintf(
                        fs_esc_html_inline( 'Is %s a duplicate of %s?', 'duplicate-site-confirmation-message' ),
                        sprintf( '<strong>%s</strong>', $current_url),
                        ( 1 === $total_sites ?
                            sprintf( '<strong>%s</strong>', $site_urls[0] ) :
                            $above_mentioned_sites_text )
                    ),
                    sprintf(
                        fs_esc_html_inline( 'Yes, %s is a duplicate of %s for the purpose of testing, staging, or development.', 'duplicate-site-message' ),
                        sprintf( '<strong>%s</strong>', $current_url ),
                        ( 1 === $total_sites ?
                            sprintf( '<strong>%s</strong>', $site_urls[0] ) :
                            $above_mentioned_sites_text )
                    ),
                    $this->has_temporary_duplicate_mode_expired() ?
                        sprintf(
                            '<button class="button" data-clone-action="temporary_duplicate">%s</button>',
                            fs_text_inline( 'Temporary Duplicate', 'temporary-duplicate' )
                        ) :
                        sprintf(
                            '<button class="button" data-clone-action="temporary_duplicate">%s</button><button class="button" data-clone-action="long_term_duplicate">%s</button>',
                            fs_text_inline( 'Temporary Duplicate', 'temporary-duplicate' ),
                            fs_text_inline( 'Long-Term Duplicate', 'long-term-duplicate' )
                        )
                ),
                // %5$s
                sprintf(
                    '<div class="fs-clone-resolution-options-container fs-migrate-site-option"><p>%s</p><p>%s</p><div>%s</div></div>',
                    sprintf(
                        fs_esc_html_inline( 'Is %s the new home of %s?', 'migrate-site-confirmation-message' ),
                        sprintf( '<strong>%s</strong>', $current_url),
                        ( 1 === $total_sites ?
                            sprintf( '<strong>%s</strong>', $site_urls[0] ) :
                            $above_mentioned_sites_text )
                    ),
                    sprintf(
                        fs_esc_html_inline( 'Yes, %s is replacing %s. I would like to migrate my %s from %s to %s.', 'migrate-site-message' ),
                        sprintf( '<strong>%s</strong>', $current_url),
                        ( 1 === $total_sites ?
                            sprintf( '<strong>%s</strong>', $site_urls[0] ) :
                            $above_mentioned_sites_text ),
                        ( $has_license ? fs_text_inline( 'license', 'license' ) : fs_text_inline( 'data', 'data' ) ),
                        ( 1 === $total_sites ?
                            sprintf( '<strong>%s</strong>', $site_urls[0] ) :
                            $above_mentioned_sites_text ),
                        sprintf( '<strong>%s</strong>', $current_url)
                    ),
                    sprintf(
                        '<button class="button" data-clone-action="new_home">%s</button>',
                        $has_license ?
                            fs_text_inline( 'Migrate License', 'migrate-product-license' ) :
                            fs_text_inline( 'Migrate', 'migrate-product-data' )
                    )
                ),
                // %6$s
                sprintf(
                    '<div class="fs-clone-resolution-options-container fs-new-site-option"><p>%s</p><p>%s</p><div>%s</div></div>',
                    sprintf(
                        fs_esc_html_inline( 'Is %s a new website?', 'new-site-confirmation-message' ),
                        sprintf( '<strong>%s</strong>', $current_url)
                    ),
                    sprintf(
                        fs_esc_html_inline( 'Yes, %s is a new and different website that is separate from %s. %s', 'new-site-message' ),
                        sprintf( '<strong>%s</strong>', $current_url),
                        ( 1 === $total_sites ?
                            sprintf( '<strong>%s</strong>', $site_urls[0] ) :
                            $above_mentioned_sites_text ),
                        $is_premium ?
                            fs_text_inline( 'It requires license activation.', 'new-site-requires-license-activation-message' ) :
                            ''
                    ),
                    sprintf(
                        '<button class="button" data-clone-action="new_website">%s</button>',
                        ( ! $is_premium || ! $has_license ) ?
                            fs_text_inline( 'New Website', 'new-website' ) :
                            fs_text_inline( 'Activate License', 'activate-license' )
                    )
                )
            );

            $this->_notices->add(
                $message,
                '',
                'promotion',
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
        function store_temporary_duplicate_timestamp() {
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
            return isset( $this->_data[ $name ] ) ?
                $this->_data[ $name ] :
                null;
        }

        #endregion
    }