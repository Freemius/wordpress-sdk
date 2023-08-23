<?php
    /**
     * @package     Freemius
     * @copyright   Copyright (c) 2015, Freemius, Inc.
     * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since       2.6.0
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class FS_Garbage_Collector {
        #--------------------------------------------------------------------------------
        #region Singleton
        #--------------------------------------------------------------------------------

        /**
         * @var FS_Garbage_Collector
         * @since 2.6.0
         */
        private static $_instance;

        /**
         * @param Freemius $freemius
         *
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

        function init() {
            require_once WP_FS__DIR_INCLUDES . '/class-fs-lock.php';

            $lock = new FS_Lock( 'garbage_collection' );

            /**
             * Try to acquire lock for the next 60 sec based on the thread ID.
             */
            if ( ! $lock->try_lock( 60 ) ) {
                return false;
            }

            $this->clean_up();

            // Create a 1-day lock.
            $lock->lock( WP_FS__TIME_24_HOURS_IN_SEC );
        }

        private function clean_up() {
            $product_types = $this->get_product_types();

            $products_to_skip_by_type_and_slug = $this->get_products_to_skip_by_type_and_slug();

            $_accounts = FS_Options::instance( WP_FS__ACCOUNTS_OPTION_NAME, true );

            $products_slugs_by_type = array();
            $products_data_by_type  = array();

            foreach( $product_types as $product_type ) {
                $option_name      = ( $product_type . 's' );
                $all_product_data = $_accounts->get_option( $option_name, array() );

                foreach ( $all_product_data as $slug => $product_data ) {
                    if ( isset( $products_to_skip_by_type_and_slug[ $product_type ][ $slug ] ) ) {
                        continue;
                    }

                    if ( ! is_object( $product_data ) ) {
                        continue;
                    }

                    if (
                        empty( $product_data->last_load_timestamp ) ||
                        ! is_numeric( $product_data->last_load_timestamp )
                    ) {
                        continue;
                    }

                    if ( $product_data->last_load_timestamp > ( time() - ( WP_FS__TIME_WEEK_IN_SEC * 4 ) ) ) {
                        // Do not remove the data if the last activation was within the last 4 weeks.
                        continue;
                    }

                    $is_addon = FS_Plugin::is_valid_id( $product_data->parent_plugin_id );

                    if ( ! isset( $products_slugs_by_type[ $product_type ] ) ) {
                        $products_slugs_by_type[ $product_type ] = array();
                    }

                    $product = array(
                        'id'   => $product_data->id,
                        'slug' => $slug,
                        'file' => $product_data->file,
                    );

                    if ( ! $is_addon ) {
                        $products_slugs_by_type[ $product_type ][] = $product;
                    } else {
                        /**
                         * If add-on, add to the beginning of the array so that add-ons are removed before their parent. This is to prevent an unexpected issue when an add-on exists but its parent was already removed.
                         */
                        array_unshift( $products_slugs_by_type[ $product_type ], $product );
                    }

                    $products_data_by_type[ $product_type ][ $option_name ] = $product_data;
                }
            }

            if ( empty( $products_slugs_by_type ) ) {
                return;
            }

            $products_options_by_type = $this->get_product_options_by_type();

            $loaded_products_options_by_type = array_fill_keys( $product_types, false );

            $installs_count_by_user_id = array();

            $has_updated_option = false;

            foreach( $products_slugs_by_type as $product_type => $products ) {
                foreach( $products as $product ) {
                    $product_id   = $product['id'];
                    $slug         = $product['slug'];
                    $product_file = $product['file'];

                    if ( ! $loaded_products_options_by_type[ $product_type ] ) {
                        $products_data_by_type[ $product_type ]           = $this->load_options( $products_options_by_type[ $product_type ] );
                        $loaded_products_options_by_type[ $product_type ] = true;
                    }

                    foreach( $products_data_by_type[ $product_type ] as $option_name => $products_data ) {
                        $updated = false;

                        if ( ! is_array( $products_data ) ) {
                            if (
                                is_object( $products_data ) &&
                                in_array( $option_name, array( "all_{$product_type}s", "active_{$product_type}s" ) )
                            ) {
                                if ( isset( $products_data->{ "{$product_type}s" }[ $product_file ] ) ) {
                                    unset( $products_data->{ "{$product_type}s" }[ $product_file ] );
                                    $updated = true;
                                }
                            }

                            if ( ! $updated ) {
                                continue;
                            }
                        }

                        if ( ! $updated ) {
                            if ( isset( $products_data[ $slug ] ) ) {
                                if ( fs_ends_with( $option_name, 'sites' ) ) {
                                    $site = $products_data[ $slug ];

                                    $installs_count_by_user_id[ $site->user_id ] = isset( $installs_count_by_user_id[ $site->user_id ] ) ?
                                        $installs_count_by_user_id[ $site->user_id ] ++ :
                                        1;
                                }

                                unset( $products_data[ $slug ] );
                                $updated = true;
                            } else if ( isset( $products_data[ "{$slug}:{$product_type}" ] ) ) {
                                unset( $products_data[ "{$slug}:{$product_type}" ] );
                                $updated = true;
                            } else if ( isset( $products_data[ $product_id ] ) ) {
                                unset( $products_data[ $product_id ] );
                                $updated = true;
                            } else if ( isset( $products_data[ $product_file ] ) ) {
                                unset( $products_data[ $product_file ] );
                                $updated = true;
                            }
                        }

                        if ( $updated ) {
                            $_accounts->set_option( $option_name, $products_data );

                            $products_data_by_type[ $product_type ][ $option_name ] = $products_data;

                            $has_updated_option = true;
                        }
                    }
                }
            }

            if ( $has_updated_option ) {
                $users = Freemius::get_all_users();
            }

            // Handle deletion of user entities that are no longer associated with installs.
            if (
                $this->delete_users(
                    $users,
                    $products_slugs_by_type,
                    $products_data_by_type,
                    $installs_count_by_user_id
                )
            ) {
                $has_updated_option = true;
            }

            if ( $has_updated_option ) {
                $_accounts->store();
            }
        }

        private function get_product_types() {
            return array(
                WP_FS__MODULE_TYPE_PLUGIN,
                WP_FS__MODULE_TYPE_THEME,
            );
        }

        private function get_product_options_by_type() {
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

        private function get_products_to_skip_by_type_and_slug() {
            $products_to_skip_by_type_and_slug = array_fill_keys( $this->get_product_types(), array() );

            $instances = Freemius::_get_all_instances();

            // Iterate over the active instances so we can determine the products to skip.
            foreach( $instances as $instance ) {
                $products_to_skip_by_type_and_slug[ $instance->get_module_type() ][ $instance->get_slug() ] = true;
            }

            return $products_to_skip_by_type_and_slug;
        }

        private function delete_users(
            $users,
            $products_slugs_by_type,
            $products_data_by_type,
            $installs_count_by_user_id
        ) {
            $_accounts = FS_Options::instance( WP_FS__ACCOUNTS_OPTION_NAME );

            $has_updated_option = false;

            foreach( $products_slugs_by_type as $product_type => $product ) {
                foreach( $products_data_by_type[ $product_type ] as $products_data ) {
                    if ( ! is_array( $products_data ) ) {
                        continue;
                    }

                    foreach( $installs_count_by_user_id as $user_id => $count ) {
                        if ( 1 === $count) {
                            unset( $users[ $user_id ] );
                            unset( $products_data['user_id_license_ids_map'][ $user_id ] );

                            $_accounts->set_option( 'users', $users );
                            $_accounts->set_option( 'user_id_license_ids_map', $products_data['user_id_license_ids_map'] );

                            $has_updated_option = true;
                        }
                    }
                }
            }

            return $has_updated_option;
        }
        /**
         * @since 2.5.11
         * @param $option_names
         *
         * @return array
         */
        private function load_options( $option_names ) {
            $_accounts = FS_Options::instance( WP_FS__ACCOUNTS_OPTION_NAME );

            $all_data = array();

            foreach ( $option_names as $option_name ) {
                $all_data[ $option_name ] = $_accounts->get_option( $option_name );
            }

            return $all_data;
        }
    }