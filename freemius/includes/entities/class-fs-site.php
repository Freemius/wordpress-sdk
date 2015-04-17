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
		public $license;
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
//			$this->license_id = $site->license_id;
			if (is_numeric($site->license_id)) {
				$this->license     = new FS_Plugin_License();
				$this->license->id = $site->license_id;
			}
			// @todo Add trial workflow support.
			$this->is_trial = false;
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
		 * Check if site assigned with active license.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 */
		function has_active_license()
		{
			return (
				is_object($this->license) &&
			    is_numeric($this->license->id) &&
				// Make sure site's plan and license' plan are consistent.
				(is_object($this->plan) && $this->license->plan_id == $this->plan->id) &&
				!$this->license->is_expired()
			);
		}

		/**
		 * Check if site assigned with license which with enabled features.
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @return bool
		 */
		function has_features_enabled_license()
		{
			return (
				is_object($this->license) &&
				is_numeric($this->license->id) &&
				// Make sure site's plan and license' plan are consistent.
				(is_object($this->plan) && $this->license->plan_id == $this->plan->id) &&
				$this->license->is_features_enabled()
			);
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param \FS_Site $site
		 * @param string   $property Object property name to evaluate
		 *
		 * @return bool
		 */
		private function is_different_object_ids(FS_Site $site, $property)
		{
			if (is_null($site->{$property}) && is_null($this->{$property}))
				return false;
			else if (is_object($site->{$property}) && is_object($this->{$property}))
				return ($site->{$property}->id != $this->{$property}->id);
			else if (is_object($site->{$property}))
				return is_numeric($site->{$property}->id);
			else
				return is_numeric($this->{$property}->id);
		}

		/**
		 * Check if given site has different license or plan.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.6
		 *
		 * @param \FS_Site $site
		 *
		 * @return bool
		 */
		function is_different_plan_or_license(FS_Site $site)
		{
			return $this->is_different_object_ids($site, 'license') ||
			       $this->is_different_object_ids($site, 'plan');
		}
	}