<?php
    /**
     * @package   Freemius
     * @copyright Copyright (c) 2015, Freemius, Inc.
     * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since     2.6.0
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    interface FS_I_Garbage_Collector {
        function clean();
    }

    class FS_Product_Garbage_Collector implements FS_I_Garbage_Collector {
        /**
         * @var FS_Options
         */
        private $_accounts;

        /**
         * @var string[]
         */
        private $_options_names;

        /**
         * @var string
         */
        private $_type;

        /**
         * @var string
         */
        private $_plural_type;

        function __construct( FS_Options $_accounts, $option_names, $type ) {
            $this->_accounts      = $_accounts;
            $this->_options_names = $option_names;
            $this->_type          = $type;
            $this->_plural_type   = ( $type . 's' );
        }

        function clean() {
            $options            = $this->load_options();
            $has_updated_option = false;

            $products_to_clean = $this->get_products_to_clean();

            foreach( $products_to_clean as $product ) {
                $slug = $product->slug;

                foreach( $options as $option_name => $option ) {
                    $updated = false;

                    if ( ! is_array( $option ) ) {
                        if (
                            is_object( $option ) &&
                            in_array( $option_name, array( 'all_' . $this->_plural_type, 'active_' . $this->_plural_type ) )
                        ) {
                            if ( isset( $option->{ $this->_plural_type }[ $product->file ] ) ) {
                                unset( $option->{ $this->_plural_type }[ $product->file ] );
                                $updated = true;
                            }
                        }

                        if ( ! $updated ) {
                            continue;
                        }
                    }

                    if ( ! $updated ) {
                        if ( array_key_exists( $slug, $option ) ) {
                            unset( $option[ $slug ] );
                            $updated = true;
                        } else if ( array_key_exists( "{$slug}:{$this->_type}", $option ) ) {
                            unset( $option[ "{$slug}:{$this->_type}" ] );
                            $updated = true;
                        } else if ( isset( $product->id ) && array_key_exists( $product->id, $option ) ) {
                            unset( $option[ $product->id ] );
                            $updated = true;
                        } else if ( isset( $product->file ) && array_key_exists( $product->file, $option ) ) {
                            unset( $option[ $product->file ] );
                            $updated = true;
                        }
                    }

                    if ( $updated ) {
                        $this->_accounts->set_option( $option_name, $option );

                        $options[ $option_name ] = $option;

                        $has_updated_option = true;
                    }
                }
            }

            return $has_updated_option;
        }

        private function get_all_option_names() {
            return array_merge(
                array(
                    'admin_notices',
                    'updates',
                    'all_licenses',
                    'addons',
                    'id_slug_type_path_map',
                    'file_slug_map',
                ),
                $this->_options_names
            );
        }

        private function get_products() {
            if ( ! empty( $this->_products ) ) {
                return $this->_products;
            }

            $products        = $this->_accounts->get_option( $this->_plural_type, array() );
            $this->_products = $this->maybe_set_products_last_load_timestamp( $products, $products, $this->_plural_type );

            $other_product_option_names = array(
                'active_' . $this->_plural_type,
                'all_' . $this->_plural_type
            );

            foreach ( $other_product_option_names as $other_option_name ) {
                $products_from_other_options = array();

                $other_option = $this->_accounts->get_option( $other_option_name, array() );

                if (
                    is_object( $other_option ) &&
                    is_array( $other_option->{ $this->_plural_type } )
                ) {
                    foreach( $other_option->{ $this->_plural_type } as $key => $other_product ) {
                        $products_from_other_options[ $key ] = $other_product;
                    }
                }

                $products_from_other_options = $this->maybe_set_products_last_load_timestamp(
                    $products_from_other_options,
                    $other_option,
                    $other_option_name,
                    $this->_plural_type
                );

                foreach( $products_from_other_options as $file => $product ) {
                    $product['file'] = $file;

                    if ( ! isset( $product['slug'] ) ) {
                        $this->_products[ $product['slug'] ] = (object) $product;
                    }
                }
            }

            return $this->_products;
        }

        private function get_products_to_clean() {
            $products_to_clean = array();

            $products = $this->get_products();

            foreach ( $products as $slug => $product_data ) {
                if ( ! is_object( $product_data ) ) {
                    continue;
                }

                if ( $this->is_product_active( $slug, $product_data ) ) {
                    continue;
                }

                $is_addon = ( ! empty( $product_data->parent_plugin_id ) );

                if ( ! $is_addon ) {
                    $products_to_clean[] = $product_data;
                } else {
                    /**
                     * If add-on, add to the beginning of the array so that add-ons are removed before their parent. This is to prevent an unexpected issue when an add-on exists but its parent was already removed.
                     */
                    array_unshift( $products_to_clean, $product_data );
                }
            }

            return $products_to_clean;
        }

        /**
         * @return array
         */
        private function get_products_to_skip_by_slug() {
            $products_to_skip_by_slug = array();

            $instances = Freemius::_get_all_instances();

            // Iterate over the active instances so we can determine the products to skip.
            foreach( $instances as $instance ) {
                $products_to_skip_by_slug[ $instance->get_slug() ] = true;
            }

            return $products_to_skip_by_slug;
        }

        /**
         * @param string $slug
         * @param object $product_data
         *
         * @return bool
         */
        private function is_product_active( $slug, $product_data ) {
            $products_to_skip_by_slug = $this->get_products_to_skip_by_slug();

            if ( isset( $products_to_skip_by_slug[ $slug ] ) ) {
                return true;
            }

            if ( $product_data->last_load_timestamp > ( time() - ( WP_FS__TIME_WEEK_IN_SEC * 4 ) ) ) {
                // Last activation was within the last 4 weeks.
                return true;
            }

            return false;
        }

        private function load_options() {
            $options      = array();
            $option_names = $this->get_all_option_names();

            foreach ( $option_names as $option_name ) {
                $options[ $option_name ] = $this->_accounts->get_option( $option_name, array() );
            }

            return $options;
        }

        private function maybe_set_products_last_load_timestamp(
            $products,
            $option,
            $option_name,
            $child_option_name = null
        ) {
            foreach ( $products as $key => $product_data ) {
                if ( ! is_object( $product_data ) && ! is_array( $product_data ) ) {
                    continue;
                }

                if (
                    ( is_object( $product_data ) && empty( $product_data->last_load_timestamp ) ) ||
                    ( is_array( $product_data ) && empty( $product_data['last_load_timestamp'] ) )
                ) {
                    // Set to the current time so that if the product is no longer used, its data will be deleted after 4 weeks.
                    if ( is_object( $product_data ) ) {
                        $product_data->last_load_timestamp = time();
                    } else {
                        $product_data['last_load_timestamp'] = time();
                    }

                    if ( ! empty( $child_option_name ) ) {
                        if ( is_object( $option ) ) {
                            $option->{ $child_option_name }[ $key ] = $product_data;
                        } else {
                            $option[ $child_option_name ][ $key ] = $product_data;
                        }

                        $products[ $key ] = $product_data;
                    }
                }
            }

            $this->_accounts->set_option( $option_name, $option );

            return $products;
        }
    }

    class FS_User_Garbage_Collector implements FS_I_Garbage_Collector {
        private $_accounts;

        private $_types;

        function __construct( FS_Options $_accounts, array $types ) {
            $this->_accounts = $_accounts;
            $this->_types    = $types;
        }

        function clean() {
            $users = Freemius::get_all_users();

            $user_has_install_map = $this->get_user_has_install_map();

            if ( count( $users ) === count( $user_has_install_map ) ) {
                return false;
            }

            $products_user_id_license_ids_map = $this->_accounts->get_option( 'user_id_license_ids_map', array() );

            $has_updated_option = false;

            foreach ( $users as $user_id => $user ) {
                if ( ! isset( $user_has_install[ $user_id ] ) ) {
                    unset( $users[ $user_id ] );

                    foreach( $products_user_id_license_ids_map as $product_id => $user_id_license_ids_map ) {
                        unset( $user_id_license_ids_map[ $user_id ] );

                        if ( empty( $user_id_license_ids_map ) ) {
                            unset( $products_user_id_license_ids_map[ $product_id ] );
                        } else {
                            $products_user_id_license_ids_map[ $product_id ] = $user_id_license_ids_map;
                        }
                    }

                    $this->_accounts->set_option( 'users', $users );
                    $this->_accounts->set_option( 'user_id_license_ids_map', $products_user_id_license_ids_map );

                    $has_updated_option = true;
                }
            }

            return $has_updated_option;
        }

        private function get_user_has_install_map() {
            $user_has_install_map = array();

            foreach ( $this->_types as $product_type ) {
                $option_name = ( WP_FS__MODULE_TYPE_PLUGIN !== $product_type ) ?
                    "{$product_type}_sites" :
                    'sites';

                $installs = $this->_accounts->get_option( $option_name, array() );

                foreach ( $installs as $install ) {
                    $user_has_install_map[ $install->user_id ] = true;
                }
            }

            return $user_has_install_map;
        }
    }

    // Main entry-level class.
    class FS_Garbage_Collector implements FS_I_Garbage_Collector {
        /**
         * @var FS_Garbage_Collector
         * @since 2.6.0
         */
        private static $_instance;

        /**
         * @return FS_Garbage_Collector
         */
        static function instance() {
            if ( ! isset( self::$_instance ) ) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        #endregion

        private function __construct() {
        }

        function clean() {
            require_once WP_FS__DIR_INCLUDES . '/class-fs-lock.php';

            $lock = new FS_Lock( 'garbage_collection' );

            if ( $lock->is_locked() ) {
                return;
            }

            // Create a 1-day lock.
            $lock->lock( WP_FS__TIME_24_HOURS_IN_SEC );

            $_accounts = FS_Options::instance( WP_FS__ACCOUNTS_OPTION_NAME, true );

            $products_cleaners = array();

            $products_cleaners[ WP_FS__MODULE_TYPE_PLUGIN ] = new FS_Product_Garbage_Collector(
                $_accounts,
                array(
                    'sites',
                    'plans',
                    'plugins',
                    'active_plugins',
                    'all_plugins',
                ),
                WP_FS__MODULE_TYPE_PLUGIN
            );

            $products_cleaners[ WP_FS__MODULE_TYPE_THEME ] = new FS_Product_Garbage_Collector(
                $_accounts,
                array(
                    'theme_sites',
                    'theme_plans',
                    'themes',
                    'active_themes',
                    'all_themes',
                ),
                WP_FS__MODULE_TYPE_THEME
            );

            $has_cleaned = false;

            foreach ( $products_cleaners as $products_cleaner ) {
                if ( $products_cleaner->clean() ) {
                    $has_cleaned = true;
                }
            }

            if ( $has_cleaned ) {
                $user_cleaner = new FS_User_Garbage_Collector(
                    $_accounts,
                    array_keys( $products_cleaners )
                );

                $user_cleaner->clean();
            }

            // Always store regardless of whether there were cleaned products or not since during the process, the logic may set the last load timestamp of some products.
            $_accounts->store();
        }
    }