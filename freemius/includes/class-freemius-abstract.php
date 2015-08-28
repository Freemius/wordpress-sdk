<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.7
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}


	/**
	 * - Each instance of Freemius class represents a single plugin
	 * install by a single user (the installer of the plugin).
	 *
	 * - Each website can only have one install of the same plugin.
	 *
	 * - Install entity is only created after a user connects his account with Freemius.
	 *
	 * Class Freemius_Abstract
	 */
	abstract class Freemius_Abstract {
		/**
		 * Check if plugin using the free plan.
		 *
		 * @since 1.0.4
		 *
		 * @return bool
		 */
		abstract function is_free_plan();

		/**
		 * Check if user in trial or in free plan (not paying).
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since 1.0.4
		 *
		 * @return bool
		 */
		function is_not_paying() {
			return ($this->is_trial() || $this->is_free_plan());
		}

		/**
		 * Check if the user has an activated and valid paid license on current plugin's install.
		 *
		 * @since 1.0.4
		 *
		 * @return bool
		 */
		abstract function is_paying__fs__();

		/**
		 * Check if the user is paying or in trial.
		 *
		 * @since 1.0.9
		 *
		 * @return bool
		 */
		function is_paying_or_trial(){
			return ($this->is_paying() || $this->is_trial());
		}

		/**
		 * @since  1.0.2
		 *
		 * @param string $plan Plan name
		 * @param bool   $exact If true, looks for exact plan. If false, also check "higher" plans.
		 *
		 * @return bool
		 */
		abstract function is_plan( $plan, $exact = false );

		/**
		 * Check if running payments in sandbox mode.
		 *
		 * @since 1.0.4
		 *
		 * @return bool
		 */
		abstract function is_payments_sandbox();

		/**
		 * Check if running test vs. live plugin.
		 *
		 * @since 1.0.5
		 *
		 * @return bool
		 */
		abstract function is_live();

		/**
		 * Check if running premium plugin code.
		 *
		 * @since 1.0.5
		 *
		 * @return bool
		 */
		abstract function is_premium();
		#region Trial ------------------------------------------------------------------

		/**
		 * Check if the user in a trial.
		 *
		 * @since 1.0.3
		 *
		 * @return bool
		 */
		abstract function is_trial();

		/**
		 * Check if trial already utilized.
		 *
		 * @since 1.0.9
		 *
		 * @return bool
		 */
		abstract function is_trial_utilized();

		#endregion Trial ------------------------------------------------------------------

		/**
		 * Check if the user skipped connecting the account with Freemius.
		 *
		 * @since 1.0.7
		 *
		 * @return bool
		 */
		abstract function is_anonymous();

		/**
		 * Check if user registered with Freemius by connecting his account.
		 *
		 * @since 1.0.1

		/**
		 * Check if plan based on trial. If not in trial mode, should return false.
		 *
		 * @since  1.0.9
		 *
		 * @param string $plan Plan name
		 * @param bool   $exact If true, looks for exact plan. If false, also check "higher" plans.
		 *
		 * @return bool
		 */
		abstract function is_trial_plan( $plan, $exact = false );

		/**
		 * Check if plan matches active license' plan or active trial license' plan.
		 *
		 * @since  1.0.9
		 *
		 * @param string $plan Plan name
		 * @param bool   $exact If true, looks for exact plan. If false, also check "higher" plans.
		 *
		 * @return bool
		 */
		function is_plan_or_trial( $plan, $exact = false ) {
			return $this->is_plan( $plan, $exact ) ||
			       $this->is_trial_plan( $plan, $exact );
		}

		/**
		 * Check if plugin has any paid plans.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return bool
		 */
		abstract function has_paid_plan();

		/**
		 * Check if plugin has any free plan, or is it premium only.
		 *
		 * Note: If no plans configured, assume plugin is free.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return bool
		 */
		abstract function has_free_plan();
		#region Marketing ------------------------------------------------------------------

		/**
		 * Check if current user purchased any other plugins before.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		abstract function has_purchased_before();
		/**
		 * Check if current user classified as an agency.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		abstract function is_agency();
		/**
		 * Check if current user classified as a developer.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		abstract function is_developer();
		/**
		 * Check if current user classified as a business.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		abstract function is_business();

		#endregion ------------------------------------------------------------------
	}