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
		public $plan_id;
		public $plan_title;
		public $plan_name;
		public $is_trial;

		static function get_type()
		{
			return 'install';
		}
	}