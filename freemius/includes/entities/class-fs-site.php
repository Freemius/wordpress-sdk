<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class FS_Site extends FS_Scope_Entity {
		public $slug;
		public $user_id;
		public $version;
//		public $license_id;
		/**
		 * @var FS_Plugin_Plan $plan
		 */
		public $plan;
		/**
		 * @var FS_Plugin_License $license
		 */
//		public $license;
		/**
		 * @var number
		 */
		public $license_id;
		public $trial_plan_id;
		public $trial_ends;
		public $is_trial;

		/**
		 * @param stdClass|bool $site
		 */
		function __construct( $site = false ) {
			$this->plan = new FS_Plugin_Plan();

			if ( ! ( $site instanceof stdClass ) ) {
				return;
			}

			parent::__construct($site);

			$this->user_id = $site->user_id;
			$this->plan->id = $site->plan_id;
			$this->license_id = $site->license_id;
//			if (is_numeric($site->license_id)) {
//				$this->license     = new FS_Plugin_License();
//				$this->license->id = $site->license_id;
//			}

			/**
			 * Added trial properties.
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since  1.0.9
			 */
			$this->trial_plan_id = $site->trial_plan_id;
			$this->trial_ends = $site->trial_ends;
		}

		static function get_type()
		{
			return 'install';
		}

		function is_localhost()
		{
			// The server has no way to verify if localhost unless localhost appears in domain.
			return WP_FS__IS_LOCALHOST_FOR_SERVER;
//			return (substr($_SERVER['REMOTE_ADDR'], 0, 4) == '127.' || $_SERVER['REMOTE_ADDR'] == '::1');
		}

		/**
		 * Check if site in trial.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function is_trial()
		{
			return is_numeric($this->trial_plan_id) && (strtotime($this->trial_ends) > WP_FS__SCRIPT_START_TIME);
		}

		/**
		 * Check if user already utilized the trial with the current install.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return bool
		 */
		function is_trial_utilized()
		{
			return is_numeric($this->trial_plan_id);
		}
	}