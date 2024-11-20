<?php
    $title = fs_esc_html_x_inline( 'Values', 'title for key/values table in debug page' );

	if ( ! defined( 'FS_API__ADDRESS' ) ) {
		define( 'FS_API__ADDRESS', '://api.freemius.com' );
	}
	if ( ! defined( 'FS_API__SANDBOX_ADDRESS' ) ) {
		define( 'FS_API__SANDBOX_ADDRESS', '://sandbox-api.freemius.com' );
	}

	echo fs_debug_generate_table( array(
		'headers' => array(
			array(
				'key' => 'key',
				'val' => fs_esc_html_inline( 'Key', 'key' ),
			),
			array(
				'key' => 'val',
				'val' => fs_esc_html_inline( 'Value', 'value' ),
			),
		),
		'data'    => array(
			array(
				'key' => 'WP_FS__REMOTE_ADDR',
				'val' => WP_FS__REMOTE_ADDR,
			),
			array(
				'key' => 'WP_FS__ADDRESS_PRODUCTION',
				'val' => WP_FS__ADDRESS_PRODUCTION,
			),
			array(
				'key' => 'FS_API__ADDRESS',
				'val' => FS_API__ADDRESS,
			),
			array(
				'key' => 'FS_API__SANDBOX_ADDRESS',
				'val' => FS_API__SANDBOX_ADDRESS,
			),
			array(
				'key' => 'WP_FS__DIR',
				'val' => WP_FS__DIR,
			),
			array(
				'key' => 'wp_using_ext_object_cache()',
				'val' => wp_using_ext_object_cache() ? 'true' : 'false',
			),
			array(
				'key' => 'Freemius::get_unfiltered_site_url()',
				'val' => Freemius::get_unfiltered_site_url(),
			),
		),
	), array('title' => $title) );
