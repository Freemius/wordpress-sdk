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

    class FS_Garbage_Collector {
        #--------------------------------------------------------------------------------
        #region Singleton
        #--------------------------------------------------------------------------------

        private $products_to_skip_by_type_and_slug;

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

        function run() {
            require_once WP_FS__DIR_INCLUDES . '/class-fs-lock.php';

            $lock = new FS_Lock( 'garbage_collection' );

            if ( $lock->is_locked() ) {
                return;
            }

            // Create a 1-day lock.
            $lock->lock( WP_FS__TIME_24_HOURS_IN_SEC );

            $this->clean_up();
       }

        private function clean_up() {
            $_accounts = FS_Options::instance( WP_FS__ACCOUNTS_OPTION_NAME, true );

            $products_to_delete = array();

            foreach ( $this->get_product_types() as $product_type ) {
                $products_to_delete[ $product_type ] = $this->get_products_to_delete( $_accounts, $product_type );
            }

            $update_options = false;

            if ( ! empty( $products_to_delete ) ) {
                if ( $this->delete_products( $_accounts, $products_to_delete ) ) {
                    $this->delete_inactive_users( $_accounts );

                    $update_options = true;
                }
            }

            if ( $update_options ) {
                $_accounts->store();
            }
        }

        /**
         * @param FS_Options $_accounts
         * @param string     $product_type
         *
         * @return array
         */
        private function get_products_to_delete( $_accounts, $product_type ) {
            $option_name      = ( $product_type . 's' );
            $all_product_data = $_accounts->get_option( $option_name, array() );

            $products_to_delete = array();

            $has_updated_option = false;

            foreach ( $all_product_data as $slug => $product_data ) {
                if ( ! is_object( $product_data ) ) {
                    continue;
                }

                if (
                    empty( $product_data->last_load_timestamp ) ||
                    ! is_numeric( $product_data->last_load_timestamp )
                ) {
                    // Set to the current time so that if the product is no longer used, its data will be deleted after 4 weeks.
                    $product_data->last_load_timestamp = time();

                    $has_updated_option = true;

                    continue;
                }

                if ( $this->is_product_active( $slug, $product_data ) ) {
                    continue;
                }

                $is_addon = FS_Plugin::is_valid_id( $product_data->parent_plugin_id );

                if ( ! $is_addon ) {
                    $products_to_delete[] = $product_data;
                } else {
                    /**
                     * If add-on, add to the beginning of the array so that add-ons are removed before their parent. This is to prevent an unexpected issue when an add-on exists but its parent was already removed.
                     */
                    array_unshift( $products_to_delete, $product_data );
                }
            }

            if ( $has_updated_option ) {
                $_accounts->store();
            }

            return $products_to_delete;
        }

        /**
         * @param string $slug
         * @param object $product_data
         *
         * @return bool
         */
        private function is_product_active( $slug, $product_data ) {
            $products_to_skip_by_type_and_slug = $this->get_products_to_skip_by_type_and_slug();

            if ( isset( $products_to_skip_by_type_and_slug[ $product_data->type ][ $slug ] ) ) {
                return true;
            }

            if ( $product_data->last_load_timestamp > ( time() - ( WP_FS__TIME_WEEK_IN_SEC * 4 ) ) ) {
                // Last activation was within the last 4 weeks.
                return true;
            }

            return false;
        }

        /**
         * @param FS_Options $_accounts
         * @param array[]    $products_to_delete
         *
         * @return bool
         */
        private function delete_products( $_accounts, $products_to_delete ) {
            $products_option_names_by_type       = $this->get_product_option_names_by_type();
            $products_options_by_type            = array();
            $has_loaded_products_options_by_type = array_fill_keys( array_keys( $products_to_delete ), false );

            $has_updated_option = false;

            foreach( $products_to_delete as $product_type => $products ) {
                foreach( $products as $product ) {
                    $slug = $product->slug;

                    if ( ! $has_loaded_products_options_by_type[ $product_type ] ) {
                        $products_options_by_type[ $product_type ]            = $this->load_options( $_accounts, $products_option_names_by_type[ $product_type ] );
                        $has_loaded_products_options_by_type[ $product_type ] = true;
                    }

                    foreach( $products_options_by_type[ $product_type ] as $option_name => $products_options ) {
                        $updated = false;

                        if ( ! is_array( $products_options ) ) {
                            if (
                                is_object( $products_options ) &&
                                in_array( $option_name, array( "all_{$product_type}s", "active_{$product_type}s" ) )
                            ) {
                                if ( isset( $products_options->{ "{$product_type}s" }[ $product->file ] ) ) {
                                    unset( $products_options->{ "{$product_type}s" }[ $product->file ] );
                                    $updated = true;
                                }
                            }

                            if ( ! $updated ) {
                                continue;
                            }
                        }

                        if ( ! $updated ) {
                            if ( isset( $products_options[ $slug ] ) ) {
                                unset( $products_options[ $slug ] );
                                $updated = true;
                            } else if ( isset( $products_options[ "{$slug}:{$product_type}" ] ) ) {
                                unset( $products_options[ "{$slug}:{$product_type}" ] );
                                $updated = true;
                            } else if ( isset( $products_options[ $product->id ] ) ) {
                                unset( $products_options[ $product->id ] );
                                $updated = true;
                            } else if ( isset( $products_options[ $product->file ] ) ) {
                                unset( $products_options[ $product->file ] );
                                $updated = true;
                            }
                        }

                        if ( $updated ) {
                            $_accounts->set_option( $option_name, $products_options );

                            $products_options_by_type[ $product_type ][ $option_name ] = $products_options;

                            $has_updated_option = true;
                        }
                    }
                }
            }

            return $has_updated_option;
        }

        /**
         * @param FS_Options $_accounts
         * @param array      $installs_count_by_user_id
         *
         * @return bool
         */
        private function delete_inactive_users( $_accounts ) {
            $users = Freemius::get_all_users();

            $user_has_install = array();

            foreach ( $this->get_product_types() as $product_type ) {
                $option_name = ( WP_FS__MODULE_TYPE_PLUGIN !== $product_type ) ?
                    "{$product_type}_sites" :
                    'sites';

                $installs = $_accounts->get_option( $option_name, array() );

                foreach ( $installs as $install ) {
                    $user_has_install[ $install->user_id ] = true;
                }
            }

            if ( count( $users ) === count( $user_has_install ) ) {
                return false;
            }

            $products_user_id_license_ids_map = $_accounts->get_option( 'user_id_license_ids_map' );

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

                    $_accounts->set_option( 'users', $users );
                    $_accounts->set_option( 'user_id_license_ids_map', $products_user_id_license_ids_map );

                    $has_updated_option = true;
                }
            }

            return $has_updated_option;
        }

        /**
         * @return string[]
         */
        private function get_product_types() {
            return array(
                WP_FS__MODULE_TYPE_PLUGIN,
                WP_FS__MODULE_TYPE_THEME,
            );
        }

        /**
         * @return array[]
         */
        private function get_product_option_names_by_type() {
            return array(
                WP_FS__MODULE_TYPE_PLUGIN => array(
                    'admin_notices',
                    'updates',
                    'sites',
                    'all_licenses',
                    'plans',
                    'addons',
                    'plugins',
                    'active_plugins',
                    'all_plugins',
                    'id_slug_type_path_map',
                    'file_slug_map',
                ),
                WP_FS__MODULE_TYPE_THEME  => array(
                    'admin_notices',
                    'updates',
                    'theme_sites',
                    'all_licenses',
                    'theme_plans',
                    'addons',
                    'themes',
                    'active_themes',
                    'all_themes',
                    'id_slug_type_path_map',
                    'file_slug_map',
                )
            );
        }

        /**
         * @return array
         */
        private function get_products_to_skip_by_type_and_slug() {
            if ( ! isset( $this->products_to_skip_by_type_and_slug ) ) {
                $this->products_to_skip_by_type_and_slug = array_fill_keys( $this->get_product_types(), array() );

                $instances = Freemius::_get_all_instances();

                // Iterate over the active instances so we can determine the products to skip.
                foreach( $instances as $instance ) {
                    $this->products_to_skip_by_type_and_slug[ $instance->get_module_type() ][ $instance->get_slug() ] = true;
                }
            }

            return $this->products_to_skip_by_type_and_slug;
        }

        /**
         * @param FS_Options $_accounts
         * @param string[]   $option_names
         *
         * @return array
         */
        private function load_options( $_accounts, $option_names ) {
            $products_options_by_type = array();

            foreach ( $option_names as $option_name ) {
                $products_options_by_type[ $option_name ] = $_accounts->get_option( $option_name );
            }

            return $products_options_by_type;
        }
    }