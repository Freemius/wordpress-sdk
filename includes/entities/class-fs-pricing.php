<?php
	/**
	 * @package     Freemius for EDD Add-On
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class FS_Pricing extends FS_Entity {

		#region Properties

		/**
		 * @var number
		 */
		public $plan_id;
		/**
		 * @var int
		 */
		public $licenses;
		/**
		 * @var null|float
		 */
		public $monthly_price;
		/**
		 * @var null|float
		 */
		public $annual_price;
		/**
		 * @var null|float
		 */
		public $lifetime_price;

		#endregion Properties

		/**
		 * @param object|bool $pricing
		 */
		function __construct( $pricing = false ) {
			parent::__construct( $pricing );
		}

		static function get_type() {
			return 'pricing';
		}
	}