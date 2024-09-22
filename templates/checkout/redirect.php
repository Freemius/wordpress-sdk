<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array    $VARS
	 * @var Freemius $fs
	 */
	$fs          = freemius( $VARS['id'] );
	$fs_checkout = FS_Checkout_Manager::instance();

	$plugin_id = fs_request_get( 'plugin_id' );
	if ( ! FS_Plugin::is_valid_id( $plugin_id ) ) {
		$plugin_id = $fs->get_id();
	}

	$plan_id  = fs_request_get( 'plan_id' );
	$licenses = fs_request_get( 'licenses' );

	$query_params = $fs_checkout->get_query_params(
		$fs,
		$plugin_id,
		$plan_id,
		$licenses
	);

	// The return URL is a special page which will process the result.
	$return_url                 = $fs_checkout->get_checkout_redirect_return_url( $fs );
	$query_params['return_url'] = $return_url;

	// Add the cancel URL to the same pricing page the request originated from.
	$query_params['cancel_url'] = $fs->pricing_url(
		fs_request_get( 'billing_cycle', 'annual' ),
		fs_request_get_bool( 'trial' )
	);

	if ( has_site_icon() ) {
		$query_params['cancel_icon'] = get_site_icon_url();
	}

	// If the user didn't connect his account with Freemius,
	// once he accepts the Terms of Service and Privacy Policy,
	// and then click the purchase button, the context information
	// of the user will be shared with Freemius in order to complete the
	// purchase workflow and activate the license for the right user.
	$install_data                 = array_merge(
		$fs->get_opt_in_params(),
		array(
			'activation_url' => fs_nonce_url(
				$fs->_get_admin_page_url(
					'',
					array(
						'fs_action' => $fs->get_unique_affix() . '_activate_new',
						'plugin_id' => $plugin_id,
					)
				),
				$fs->get_unique_affix() . '_activate_new'
			),
		)
	);
	$query_params['install_data'] = json_encode( $install_data );

	$query_params['_fs_dashboard_independent'] = true;

	fs_redirect( $fs_checkout->get_full_checkout_url( $query_params ) );
