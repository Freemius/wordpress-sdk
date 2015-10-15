<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.5
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class FS_Plugin_License extends FS_Entity {
		public $plan_id;
		public $activated;
		public $activated_local;
		public $quota;
		public $expiration;
		public $is_free_localhost;
		public $is_block_features;

		/**
		 * @param stdClass|bool $license
		 */
		function __construct( $license = false ) {
			if ( ! ( $license instanceof stdClass ) ) {
				return;
			}

			parent::__construct( $license );

			$this->plan_id           = $license->plan_id;
			$this->activated         = $license->activated;
			$this->activated_local   = $license->activated_local;
			$this->quota             = $license->quota;
			$this->expiration        = $license->expiration;
			$this->is_free_localhost = $license->is_free_localhost;
			$this->is_block_features = $license->is_block_features;
		}

		static function get_type() {
			return 'license';
		}

		/**
		 * Check how many site activations left.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @return int
		 */
		function left() {
			if ( $this->is_expired() ) {
				return 0;
			}

			return ( $this->quota - $this->activated - ( $this->is_free_localhost ? 0 : $this->activated_local ) );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.5
		 *
		 * @return bool
		 */
		function is_expired() {
			return ! $this->is_lifetime() && ( strtotime( $this->expiration ) < WP_FS__SCRIPT_START_TIME );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return bool
		 */
		function is_lifetime() {
			return is_null( $this->expiration );
		}

		/**
		 * Check if license is fully utilized.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param bool $is_localhost
		 *
		 * @return bool
		 */
		function is_utilized( $is_localhost = null ) {
			if ( is_null( $is_localhost ) ) {
				$is_localhost = WP_FS__IS_LOCALHOST_FOR_SERVER;
			}

			return ! ( $this->is_free_localhost && $is_localhost ) &&
			       ( $this->quota <= $this->activated + ( $this->is_free_localhost ? 0 : $this->activated_local ) );
		}

		/**
		 * Check if license's plan features are enabled.
		 *
		 *  - Either if plan not expired
		 *  - If expired, based on the configuration to block features or not.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return bool
		 */
		function is_features_enabled() {
			return ( ! $this->is_block_features || ! $this->is_expired() );
		}

		/**
		 * Subscription considered to be new without any payments
		 * if the license expires in less than 24 hours
		 * from the license creation.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function is_first_payment_pending() {
			return ( WP_FS__TIME_24_HOURS_IN_SEC >= strtotime( $this->expiration ) - strtotime( $this->created ) );
		}
	}