<?php
	global $fs_active_plugins;

	$title = fs_esc_html_x_inline( 'SDK Versions', 'as software development kit versions', 'sdk-versions' );

	echo fs_debug_generate_table(
		array(
			'attributes' => array(
				'id' => 'fs_sdk',
			),
			'headers'    => array(
				array(
					'key' => 'version',
					'val' => fs_esc_html_x_inline( 'Version', 'product version' ),
				),
				array(
					'key' => 'sdk_path',
					'val' => fs_esc_html_inline( 'SDK Path' ),
				),
				array(
					'key' => 'module_path',
					'val' => fs_esc_html_inline( 'Module Path' ),
				),
				array(
					'key' => 'is_active',
					'val' => fs_esc_html_inline( 'Is Active' ),
				),
			),
			'data'       => array_map( function ( $item ) {
				$is_active = ( WP_FS__SDK_VERSION == $item->version );

				return array(
					'version'     => $item->version,
					'sdk_path'    => '2',
					'module_path' => $item->plugin_path,
					'is_active'   => ( $is_active ) ? 'Active' : 'Inactive',
					'row_style'   => ( $is_active ) ? 'success' : 'default',
				);
			}, $fs_active_plugins->plugins ),

		), array(
			'title' => $title,
		)
	);
