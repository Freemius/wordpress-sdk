<?php
	$addons = $VARS['addons'];

	foreach ( $addons as $plugin_id => $plugin_addons ) {
		$headers = [
			[ 'key' => 'id', 'val' => fs_esc_html_inline( 'ID', 'id' ) ],
			[ 'key' => 'title', 'val' => fs_esc_html_inline( 'Title' ) ],
			[ 'key' => 'slug', 'val' => fs_esc_html_inline( 'Slug' ) ],
			[ 'key' => 'version', 'val' => fs_esc_html_x_inline( 'Version', 'product version' ) ],
			[ 'key' => 'public_key', 'val' => fs_esc_html_inline( 'Public Key' ) ],
			[ 'key' => 'secret_key', 'val' => fs_esc_html_inline( 'Secret Key' ) ],
		];

		$data = [];
		foreach ( $plugin_addons as $addon ) {
			$data[] = [
				'id'         => $addon->id,
				'title'      => esc_html( $addon->title ),
				'slug'       => esc_html( $addon->slug ),
				'version'    => esc_html( $addon->version ),
				'public_key' => esc_html( $addon->public_key ),
				'secret_key' => esc_html( $addon->secret_key ),
			];
		}

		$tableData = [
			'attributes' => [ 'id' => 'fs_addons_' . esc_attr( $plugin_id ), 'class' => 'widefat' ],
			'headers'    => $headers,
			'data'       => $data,
		];

		$title = esc_html( sprintf( fs_text_inline( 'Add Ons of module %s', 'addons-of-x' ), $plugin_id ) );

		echo fs_debug_generate_table( $tableData, array('title' => $title) );
	}
