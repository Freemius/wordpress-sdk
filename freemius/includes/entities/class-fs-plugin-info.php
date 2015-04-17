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

	class FS_Plugin_Info extends FS_Entity {
		public $plugin_id;
		public $description;
		public $short_description;
		public $banner_url;
		public $card_banner_url;
		public $selling_point_0;
		public $selling_point_1;
		public $selling_point_2;
		public $screenshots;

		/**
		 * @param stdClass|bool $plugin_info
		 */
		function __construct( $plugin_info = false ) {
			if ( ! ( $plugin_info instanceof stdClass ) ) {
				return;
			}

			parent::__construct( $plugin_info );

			$this->plugin_id         = $plugin_info->plugin_id;
			$this->description       = $plugin_info->description;
			$this->short_description = $plugin_info->short_description;
			$this->banner_url        = $plugin_info->banner_url;
			$this->card_banner_url   = $plugin_info->card_banner_url;
			$this->selling_point_0   = $plugin_info->selling_point_0;
			$this->selling_point_1   = $plugin_info->selling_point_1;
			$this->selling_point_2   = $plugin_info->selling_point_2;
			$this->screenshots       = $plugin_info->screenshots;
		}

		static function get_type()
		{
			return 'plugin';
		}
	}