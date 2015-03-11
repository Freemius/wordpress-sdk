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
		public $license_id;
		/**
		 * @var FS_Plugin_Plan $plan
		 */
		public $plan;
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
			return (false !== strpos($_SERVER['HTTP_HOST'], 'localhost'));
//			return (substr($_SERVER['REMOTE_ADDR'], 0, 4) == '127.' || $_SERVER['REMOTE_ADDR'] == '::1');
		}
	}