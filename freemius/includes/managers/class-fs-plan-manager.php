<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.6
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class FS_Plan_Manager {
		/**
		 * @param FS_Plugin_License[] $licenses
		 *
		 * @return bool
		 */
		static function has_premium_license( $licenses ) {
			if (is_array($licenses)) {
				foreach ( $licenses as $license ) {
					if ( !$license->is_utilized() && $license->is_features_enabled() ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Check if plugin has any paid plans.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @param FS_Plugin_Plan[] $plans
		 *
		 * @return bool
		 */
		static function has_paid_plan($plans) {
			if ( ! is_array( $plans ) || 0 === count( $plans ) ) {
				return false;
			}

			for ( $i = 0, $len = count( $plans ); $i < $len; $i ++ ) {
				if ( 'free' !== $plans[ $i ]->name ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if plugin has any free plan, or is it premium only.
		 *
		 * Note: If no plans configured, assume plugin is free.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @param FS_Plugin_Plan[] $plans
		 *
		 * @return bool
		 */
		static function has_free_plan($plans) {
			if ( ! is_array( $plans ) || 0 === count( $plans ) ) {
				return true;
			}

			for ( $i = 0, $len = count( $plans ); $i < $len; $i ++ ) {
				if ( 'free' === $plans[ $i ]->name ) {
					return true;
				}
			}

			return false;
		}
	}