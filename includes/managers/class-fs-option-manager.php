<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * 3-layer lazy options manager.
	 *      layer 3: Memory
	 *      layer 2: Cache (if there's any caching plugin and if WP_FS__DEBUG_SDK is FALSE)
	 *      layer 1: Database (options table). All options stored as one option record in the DB to reduce number of DB
	 *      queries.
	 *
	 * If load() is not explicitly called, starts as empty manager. Same thing about saving the data - you have to
	 * explicitly call store().
	 *
	 * Class Freemius_Option_Manager
	 */
	class FS_Option_Manager {
		/**
		 * @var string
		 */
		private $_id;
		/**
		 * @var array
		 */
		private $_options;
		/**
		 * @var FS_Logger
		 */
		private $_logger;

		/**
		 * @var FS_Option_Manager[]
		 */
		private static $_MANAGERS = array();

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @param string $id
		 * @param bool   $load
		 */
		private function __construct( $id, $load = false ) {
			$this->_logger = FS_Logger::get_logger( WP_FS__SLUG . '_opt_mngr_' . $id, WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );

			$this->_logger->entrance();
			$this->_logger->log( 'id = ' . $id );

			$this->_id = $id;

			if ( $load ) {
				$this->load();
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @param $id
		 * @param $load
		 *
		 * @return FS_Option_Manager
		 */
		static function get_manager( $id, $load = false ) {
			$id = strtolower( $id );

			if ( ! isset( self::$_MANAGERS[ $id ] ) ) {
				self::$_MANAGERS[ $id ] = new FS_Option_Manager( $id, $load );
			} // If load required but not yet loaded, load.
			else if ( $load && ! self::$_MANAGERS[ $id ]->is_loaded() ) {
				self::$_MANAGERS[ $id ]->load();
			}

			return self::$_MANAGERS[ $id ];
		}

		private function _get_option_manager_name() {
//			return WP_FS__SLUG . '_' . $this->_id;
			return $this->_id;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @param bool $flush
		 */
		function load( $flush = false ) {
			$this->_logger->entrance();

			$option_name = $this->_get_option_manager_name();

			if ( $flush || ! isset( $this->_options ) ) {
				if ( isset( $this->_options ) ) {
					// Clear prev options.
					$this->clear();
				}

				if ( ! WP_FS__DEBUG_SDK ) {
					$this->_options = wp_cache_get( $option_name, WP_FS__SLUG );
				}

//				$this->_logger->info('wp_cache_get = ' . var_export($this->_options, true));

//				if ( is_array( $this->_options ) ) {
//					$this->clear();
//				}

				$cached = true;

				if ( empty( $this->_options ) ) {
					$this->_options = get_option( $option_name );

					if ( is_string( $this->_options ) ) {
						$this->_options = json_decode( $this->_options );
					}

//					$this->_logger->info('get_option = ' . var_export($this->_options, true));

					if ( false === $this->_options ) {
						$this->clear();
					}

					$cached = false;
				}

				if ( ! WP_FS__DEBUG_SDK && ! $cached ) // Set non encoded cache.
				{
					wp_cache_set( $option_name, $this->_options, WP_FS__SLUG );
				}
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @return bool
		 */
		function is_loaded() {
			return isset( $this->_options );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @return bool
		 */
		function is_empty() {
			return ( $this->is_loaded() && false === $this->_options );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param bool $flush
		 */
		function clear( $flush = false ) {
			$this->_logger->entrance();

			$this->_options = array();

			if ( $flush ) {
				$this->store();
			}
		}

		/**
		 * Delete options manager from DB.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 */
		function delete() {
			delete_option( $this->_get_option_manager_name() );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param string $option
		 *
		 * @return bool
		 */
		function has_option( $option ) {
			return array_key_exists( $option, $this->_options );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @param string $option
		 * @param mixed  $default
		 *
		 * @return mixed
		 */
		function get_option( $option, $default = null ) {
			$this->_logger->entrance( 'option = ' . $option );

			if ( is_array( $this->_options ) ) {
				$value = isset( $this->_options[ $option ] ) ?
                    $this->_options[ $option ] :
                    $default;
			} else if ( is_object( $this->_options ) ) {
                $value = isset( $this->_options->{$option} ) ?
                    $this->_options->{$option} :
                    $default;
			} else {
                $value = $default;
            }

            /**
             * If it's an object, return a clone of the object, otherwise,
             * external changes of the object will actually change the value
             * of the object in the options manager which may lead to an unexpected
             * behaviour and data integrity when a store() call is triggered.
             *
             * Example:
             *      $object1    = $options->get_option( 'object1' );
             *      $object1->x = 123;
             *
             *      $object2    = $options->get_option( 'object2' );
             *      $object2->y = 'dummy';
             *
             *      $options->set_option( 'object2', $object2, true );
             *
             * If we don't return a clone of option 'object1', setting 'object2'
             * will also store the updated value of 'object1' which is quite not
             * an expected behaviour.
             *
             * @author Vova Feldman
             */
			return is_object($value) ? clone $value : $value;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @param string $option
		 * @param mixed  $value
		 * @param bool   $flush
		 */
		function set_option( $option, $value, $flush = false ) {
			$this->_logger->entrance( 'option = ' . $option );

			if ( ! $this->is_loaded() ) {
				$this->clear();
			}

            /**
             * If it's an object, store a clone of the object, otherwise,
             * external changes of the object will actually change the value
             * of the object in the options manager which may lead to an unexpected
             * behaviour and data integrity when a store() call is triggered.
             *
             * Example:
             *      $object1    = new stdClass();
             *      $object1->x = 123;
             *
             *      $options->set_option( 'object1', $object1 );
             *
             *      $object1->x = 456;
             *
             *      $options->set_option( 'object2', $object2, true );
             *
             * If we don't set the option as a clone of option 'object1', setting 'object2'
             * will also store the updated value of 'object1' ($object1->x = 456 instead of
             * $object1->x = 123) which is quite not an expected behaviour.
             *
             * @author Vova Feldman
             */
            $copy = is_object($value) ? clone $value : $value;

			if ( is_array( $this->_options ) ) {
				$this->_options[ $option ] = $copy;
			} else if ( is_object( $this->_options ) ) {
				$this->_options->{$option} = $copy;
			}

			if ( $flush ) {
				$this->store();
			}
		}

		/**
		 * Unset option.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 *
		 * @param string $option
		 * @param bool   $flush
		 */
		function unset_option( $option, $flush = false ) {
			$this->_logger->entrance( 'option = ' . $option );

			if ( is_array( $this->_options ) ) {
				if ( ! isset( $this->_options[ $option ] ) ) {
					return;
				}

				unset( $this->_options[ $option ] );

			} else if ( is_object( $this->_options ) ) {
				if ( ! isset( $this->_options->{$option} ) ) {
					return;
				}

				unset( $this->_options->{$option} );
			}

			if ( $flush ) {
				$this->store();
			}
		}

		/**
		 * Dump options to database.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.3
		 */
		function store() {
			$this->_logger->entrance();

			$option_name = $this->_get_option_manager_name();

			if ( $this->_logger->is_on() ) {
				$this->_logger->info( $option_name . ' = ' . var_export( $this->_options, true ) );
			}

			// Update DB.
			update_option( $option_name, $this->_options );

			if ( ! WP_FS__DEBUG_SDK ) {
				wp_cache_set( $option_name, $this->_options, WP_FS__SLUG );
			}
		}
	}