<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.0.4
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class FS_Plugin_Tag extends FS_Entity {
		public $version;
		public $url;

		function __construct( $tag = false ) {
			parent::__construct( $tag );
		}

		static function get_type() {
			return 'tag';
		}
	}