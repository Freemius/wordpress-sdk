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

	// region Properties
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

	// endregion Properties
	/**
	 * @param object|bool $subscription
	 */
	function __construct( $subscription = false ) {
		parent::__construct( $subscription );
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

	/**
	 * @author Vova Feldman (@svovaf)
	 * @since  1.1.7
	 */
	function has_trial() {
		return ! is_null( $this->trial_ends );
	}
}
