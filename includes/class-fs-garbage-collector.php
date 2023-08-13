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
            $instances = Freemius::_get_all_instances();

            $product_types = array(
                WP_FS__MODULE_TYPE_PLUGIN,
                WP_FS__MODULE_TYPE_THEME,
            );

            $products_to_skip_by_type_and_slug = array_fill_keys( $product_types, array() );

            // Iterate over the active instances so we can determine the products to skip.
            foreach( $instances as $instance ) {
                $products_to_skip_by_type_and_slug[ $instance->get_module_type() ][ $instance->get_slug() ] = true;
            }

            $_accounts = FS_Options::instance( WP_FS__ACCOUNTS_OPTION_NAME, true );

            $products_slugs_by_type = array();
            $products_data_by_type  = array();

            foreach( $product_types as $product_type ) {
                $option_name  = ( $product_type . 's' );
                $product_data = $_accounts->get_option( $option_name, array() );

                foreach ( $product_data as $slug => $data ) {
                    if ( isset( $products_to_skip_by_type_and_slug[ $product_type ][ $slug ] ) ) {
                        continue;
                    }

                    if ( ! is_object( $data ) ) {
                        continue;
                    }

                    if (
                        empty( $data->last_load_timestamp ) ||
                        ! is_numeric( $data->last_load_timestamp )
                    ) {
                        continue;
                    }

                    if ( $data->last_load_timestamp > ( time() - ( WP_FS__TIME_WEEK_IN_SEC * 4 ) ) ) {
                        // Do not remove the data if the last activation was within the last 4 weeks.
                        continue;
                    }

                    if ( ! isset( $products_slugs_by_type[ $product_type ] ) ) {
                        $products_slugs_by_type[ $product_type ] = array();
                    }

                    $products_slugs_by_type[ $product_type ][] = array(
                        'id'   => $data->id,
                        'slug' => $slug,
                        'file' => $data->file,
                    );

                    $products_data_by_type[ $product_type ][ $option_name ] = $data;
                }
            }

            if ( empty( $products_slugs_by_type ) ) {
                return;
            }

            $products_options_by_type = array(
                'plugin' => array(
                    'plugins',
                    'admin_notices',
                    'plans',
                    'sites',
                    'all_licenses',
                    'updates',
                    'id_slug_type_path_map',
                    'file_slug_map',
                ),
                'theme'  => array(
                    'themes',
                    'admin_notices',
                    'theme_plans',
                    'theme_sites',
                    'all_licenses',
                    'updates',
                    'id_slug_type_path_map',
                    'file_slug_map',
                )
            );

            $loaded_products_options_by_type = array_fill_keys( $product_types, false );

            $installs_count_by_user_id = array();

            foreach( $products_slugs_by_type as $product_type => $products ) {
                foreach( $products as $product ) {
                    $product_id   = $product['id'];
                    $slug         = $product['slug'];
                    $product_file = $product['product_file'];

                    if ( ! $loaded_products_options_by_type[ $product_type ] ) {
                        $products_data_by_type[ $product_type ]           = $this->load_options( $products_options_by_type[ $product_type ] );
                        $loaded_products_options_by_type[ $product_type ] = true;
                    }

                    foreach( $products_data_by_type[ $product_type ] as $option_name => $products_data ) {
                        if ( ! is_array( $products_data ) ) {
                            continue;
                        }

                        $update = false;

                        if ( isset( $products_data[ $slug ] ) ) {
                            if ( fs_ends_with( $option_name, 'sites' ) ) {
                                $site = $products_data[ $slug ];

                                $installs_count_by_user_id[ $site->user_id ] = isset( $installs_count_by_user_id[ $site->user_id ] ) ?
                                    $installs_count_by_user_id[ $site->user_id ] ++ :
                                    1;
                            }

                            unset( $products_data[ $slug ] );
                            $update = true;
                        } else if ( isset( $products_data[ "{$slug}:{$product_type}" ] ) ) {
                            unset( $products_data[ "{$slug}:{$product_type}" ] );
                            $update = true;
                        } else if ( isset( $products_data[ $product_id ] ) ) {
                            unset( $products_data[ $product_id ] );
                            $update = true;
                        } else if ( isset( $products_data[ $product_file ] ) ) {
                            unset( $products_data[ $product_file ] );
                            $update = true;
                        } else if ( in_array( $option_name, array( "all_{$product_type}s", "active_{$product_type}s" ) ) ) {
                            if ( isset( $products_data[ "{$product_type}s" ][ $product_file ] ) ) {
                                unset( $products_data[ "{$product_type}s" ][ $product_file ] );
                                $update = true;
                            }
                        }

                        if ( $update ) {
                            $_accounts->set_option( $option_name, $products_data, true );
                        }
                    }
                }
            }

            $users = Freemius::get_all_users();

            foreach( $products_slugs_by_type as $product_type => $product ) {
                foreach( $products_data_by_type[ $product_type ] as $products_data ) {
                    if ( ! is_array( $products_data ) ) {
                        continue;
                    }

                    foreach( $installs_count_by_user_id as $user_id => $count ) {
                        if ( 1 === $count) {
                            unset( $users[ $user_id ] );
                            unset( $products_data['user_id_license_ids_map'][ $user_id ] );

                            $_accounts->set_option( 'users', $users, true );
                            $_accounts->set_option( 'user_id_license_ids_map', $products_data['user_id_license_ids_map'], true );
                        }
                    }
                }
            }
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