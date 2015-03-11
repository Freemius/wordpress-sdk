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

	class FS_Entity {
		public $id;
		public $updated;

		/**
		 * @param bool|stdClass $entity
		 */
		function __construct( $entity = false )
		{
			if (!($entity instanceof stdClass))
				return;

			$this->id = $entity->id;
		}

		static function get_type()
		{
			return 'type';
		}
	}