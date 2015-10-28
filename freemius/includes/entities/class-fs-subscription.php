<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.9
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class FS_Subscription extends FS_Entity {

		#region Properties

		/**
		 * @var number
		 */
		public $user_id;
		/**
		 * @var number
		 */
		public $install_id;
		/**
		 * @var number
		 */
		public $plan_id;
		/**
		 * @var number
		 */
		public $license_id;
		/**
		 * @var float
		 */
		public $total_gross;
		/**
		 * @var float
		 */
		public $amount_per_cycle;
		/**
		 * @var int # of months
		 */
		public $billing_cycle;
		/**
		 * @var float
		 */
		public $outstanding_balance;
		/**
		 * @var int
		 */
		public $failed_payments;
		/**
		 * @var string
		 */
		public $gateway;
		/**
		 * @var string
		 */
		public $external_id;
		/**
		 * @var string|null
		 */
		public $trial_ends;
		/**
		 * @var string|null Datetime of the next payment, or null if cancelled
		 */
		public $next_payment;
		/**
		 * @var string|null
		 */
		public $vat_id;
		/**
		 * @var string Two characters country code
		 */
		public $country_code;

		#endregion Properties

		/**
		 * @param object|bool $subscription
		 */
		function __construct( $subscription = false ) {
			if ( ! ( $subscription instanceof stdClass ) ) {
				return;
			}

			parent::__construct( $subscription );

			$this->user_id             = $subscription->user_id;
			$this->install_id          = $subscription->install_id;
			$this->plan_id             = $subscription->plan_id;
			$this->license_id          = $subscription->license_id;
			$this->total_gross         = $subscription->total_gross;
			$this->amount_per_cycle    = $subscription->amount_per_cycle;
			$this->billing_cycle       = $subscription->billing_cycle;
			$this->outstanding_balance = $subscription->outstanding_balance;
			$this->failed_payments     = $subscription->failed_payments;
			$this->gateway             = $subscription->gateway;
			$this->external_id         = $subscription->external_id;
			$this->trial_ends          = $subscription->trial_ends;
			$this->next_payment        = $subscription->next_payment;
			$this->vat_id              = $subscription->vat_id;
			$this->country_code        = $subscription->country_code;
		}

		static function get_type() {
			return 'subscription';
		}

		/**
		 * Check if subscription is active.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function is_active() {
			return ! empty( $this->next_payment ) &&
			       ( strtotime( $this->next_payment ) > WP_FS__SCRIPT_START_TIME );
		}

		/**
		 * Subscription considered to be new without any payments
		 * if the next payment should be made within less than 24 hours
		 * from the subscription creation.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function is_first_payment_pending() {
			return ( WP_FS__TIME_24_HOURS_IN_SEC >= strtotime( $this->next_payment ) - strtotime( $this->created ) );
		}
	}