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

	class FS_Plugin_License extends FS_Entity {
		public $plan_id;
		public $activated;
		public $activated_local;
		public $quota;
		public $expiration;
		public $is_free_localhost;

		/**
		 * @param stdClass|bool $license
		 */
		function __construct( $license = false )
		{
			if (!($license instanceof stdClass))
				return;

			parent::__construct($license);

			$this->plan_id = $license->plan_id;
			$this->activated = $license->activated;
			$this->activated_local = $license->activated_local;
			$this->quota = $license->quota;
			$this->expiration = $license->expiration;
			$this->is_free_localhost = $license->is_free_localhost;
		}

		static function get_type()
		{
			return 'license';
		}

		function left()
		{
			if ($this->is_expired())
				return 0;

			return ($this->quota - $this->activated - ($this->is_free_localhost ? 0 : $this->activated_local));
		}

		function is_expired()
		{
			return !is_null($this->expiration) && (strtotime($this->expiration) < WP_FS__SCRIPT_START_TIME);
		}
	}