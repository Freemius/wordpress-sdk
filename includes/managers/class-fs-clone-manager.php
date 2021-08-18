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

    class FS_Clone_Manager {
        /**
         * @var FS_Option_Manager
         */
        private $_storage;
        /**
         * @var array {
         * @type bool   $is_temporary_duplicate
         * @type int    $clone_identification_timestamp
         * @type int    $request_handler_timestamp
         * @type string $request_handler_id
         * }
         */
        private $_data;
        /**
         * @var string
         */
        private $_option_name;
        /**
         * @var FS_Admin_Notices
         */
        private $_notices;

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
            $this->_storage     = FS_Option_Manager::get_manager( WP_FS__CLONE_MANAGEMENT_OPTION_NAME, true );
            $this->_option_name = 'clone_resolution';
            $this->_data        = $this->_storage->get_option( $this->_option_name, array() );
            $this->_notices     = FS_Admin_Notices::instance( 'clone_resolution' );

            if ( ! is_array( $this->_data ) ) {
                $this->_data = array();
            }
        }

        function init() {
            if ( Freemius::is_admin_post() ) {
                add_action( 'admin_post_fs_clone_resolution', array( &$this, '_handle_clone_resolution' ) );

                return;
            }

            $this->initiate_clone_resolution_handler();
        }

        /**
         * @return int|null
         */
        function get_clone_identification_timestamp() {
            return empty( $this->_data[ 'clone_identification_timestamp' ] ) ?
                null :
                $this->_data[ 'clone_identification_timestamp' ];
        }

        /**
         * @return int
         */
        function get_temporary_duplicate_expiration() {
            $timestamp = $this->get_clone_identification_timestamp();

            return min( $timestamp + ( WP_FS__TIME_WEEK_IN_SEC * 2 ), time() );
        }

        function store_clone_identification_timestamp() {
            $this->update_option( 'clone_identification_timestamp', time() );
        }

        private function initiate_clone_resolution_handler() {
            if (
                ! empty( $this->_data ) &&
                (
                    // If less than 10 minutes have passed since the last resolution has started, do not make another request to give the previous request enough time to finish.
                    isset( $this->_data[ 'request_handler_timestamp' ] ) &&
                    is_numeric( $this->_data[ 'request_handler_timestamp' ] ) &&
                    time() > ( $this->_data[ 'request_handler_timestamp' ] + WP_FS__TIME_10_MIN_IN_SEC )
                )
            ) {
                return;
            }

            $this->update_option( 'request_handler_timestamp', time() );

            $handler_id = ( rand() . microtime() );
            $this->update_option( 'request_handler_id', $handler_id );

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

        function _handle_clone_resolution() {
            $handler_id = fs_request_get( 'handler_id' );

            if ( empty( $handler_id ) ) {
                return;
            }

            if (
                empty( $this->_data ) ||
                empty( $this->_data['request_handler_id'] ) ||
                $this->_data['request_handler_id'] !== $handler_id
            ) {
                return;
            }

            Freemius::handle_clones();
        }

        /**
         * @param string[] $product_titles
         * @param string[] $site_urls
         * @param string   $current_url
         * @param bool     $has_license
         * @param bool     $is_premium
         * @param string   $module_label
         */
        function add_manual_clone_resolution_admin_notice(
            $product_titles,
            $site_urls,
            $current_url,
            $has_license = false,
            $is_premium = false,
            $module_label = 'product'
        ) {
            $total_sites = count( $site_urls );
            $sites_list  = '';

            $total_products = count( $product_titles );
            $products_list  = '';

            if ( 1 === $total_products ) {
                $notice_header = sprintf(
                    '<div class="fs-notice-header"><p id="fs_clone_resolution_error_message" style="display: none"></p><p>%s</p></div>',
                    fs_esc_html_inline( '%1$s has been placed into safe mode because we noticed that %2$s is an exact copy of %3$s.', 'single-cloned-site-safe-mode-message' )
                );
            } else {
                $notice_header = sprintf(
                    '<div class="fs-notice-header"><p id="fs_clone_resolution_error_message" style="display: none"></p><p>%s</p></div>',
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

            $message = sprintf(
                $notice,
                // %1$s
                ( 1 === $total_products ?
                    sprintf( '<b>%s</b>', $product_titles[0] ) :
                    sprintf( '<div><p><strong>%s</strong>:</p>%s</div>', fs_esc_html_x_inline( 'Products', 'clone resolution admin notice', 'products' ), $products_list ) ),
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
                        sprintf( '<strong>%s</strong>', $current_url), sprintf( '<strong>%s</strong>', $site_urls[0] )
                    ),
                    sprintf(
                        fs_esc_html_inline( 'Yes, %s is a duplicate of %s for the purpose of testing, staging, or development.', 'duplicate-site-message' ),
                        sprintf( '<strong>%s</strong>', $current_url), sprintf( '<strong>%s</strong>', $site_urls[0] )
                    ),
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
                        sprintf( '<strong>%s</strong>', $current_url), sprintf( '<strong>%s</strong>', $site_urls[0] )
                    ),
                    sprintf(
                        fs_esc_html_inline( 'Yes, %s is replacing %s. I would like to migrate my %s from %s to %s.', 'migrate-site-message' ),
                        sprintf( '<strong>%s</strong>', $current_url), sprintf( '<strong>%s</strong>', $site_urls[0] ),
                        $module_label,
                        sprintf( '<strong>%s</strong>', $current_url), sprintf( '<strong>%s</strong>', $site_urls[0] )
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
                        fs_esc_html_inline( '%s is new and different website that is separate from %s. %s', 'new-site-message' ),
                        sprintf( '<strong>%s</strong>', $current_url), sprintf( '<strong>%s</strong>', $site_urls[0] ),
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
         * @param string     $name
         * @param int|string $value
         */
        private function update_option( $name, $value ) {
            $this->_data[ $name ] = $value;

            $this->_storage->set_option( $this->_option_name, $this->_data, true );
        }

        /**
         * @param bool $check_only_if_stored
         *
         * @return bool
         */
        function is_temporary_duplicate( $check_only_if_stored = true ) {
            if ( ! isset( $this->_data['is_temporary_duplicate'] ) ) {
                if ( $check_only_if_stored ) {
                    return false;
                }
            } else if ( true !== $this->_data['is_temporary_duplicate'] ) {
                return false;
            }

            $clone_identification_timestamp = $this->get_clone_identification_timestamp();

            return ( ( $clone_identification_timestamp + ( WP_FS__TIME_WEEK_IN_SEC * 2 ) ) > time() );
        }

        function store_temporary_duplicate_flag() {
            $this->update_option( 'is_temporary_duplicate', true );
        }

        /**
         * Removes the notice that is shown when the user has flagged the clones as temporary duplicate.
         */
        function remove_opt_in_notice() {
            $this->_notices->remove_sticky( 'temporary_duplicate_notice' );
        }

        /**
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
        }
    }