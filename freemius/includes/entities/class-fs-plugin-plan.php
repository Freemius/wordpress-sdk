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

	class FS_Plugin_Plan extends FS_Entity {
		public $title;
		public $name;

		/**
		 * @param stdClass|bool $plan
		 */
		function __construct( $plan = false )
		{
			if (!($plan instanceof stdClass))
				return;

			parent::__construct($plan);

			$this->title = $plan->title;
			$this->name = strtolower($plan->name);
		}

		static function get_type()
		{
			return 'plan';
		}
	}