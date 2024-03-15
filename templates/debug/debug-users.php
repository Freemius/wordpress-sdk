<?php
	$users = $VARS['users'];

	$headers = [
		[ 'key' => 'id', 'val' => fs_esc_html_inline( 'ID', 'id' ) ],
		[ 'key' => 'name', 'val' => fs_esc_html_inline( 'Name' ) ],
		[ 'key' => 'email', 'val' => fs_esc_html_inline( 'Email' ) ],
		[ 'key' => 'verified', 'val' => fs_esc_html_inline( 'Verified' ) ],
		[ 'key' => 'public_key', 'val' => fs_esc_html_inline( 'Public Key' ) ],
		[ 'key' => 'secret_key', 'val' => fs_esc_html_inline( 'Secret Key' ) ],
		[ 'key' => 'actions', 'val' => fs_esc_html_inline( 'Actions' ) ],
	];

	$data = [];
	foreach ( $users as $user_id => $user ) {
		$has_developer_license = isset( $users_with_developer_license_by_id[ $user->id ] );

		$row = [
			'id'         => $user->id,
			'name'       => $has_developer_license ? '' : esc_html( $user->get_name() ),
			'email'      => $has_developer_license ? '' : '<a href="mailto:' . esc_attr( $user->email ) . '">' . esc_html( $user->email ) . '</a>',
			'verified'   => $has_developer_license ? '' : esc_html( json_encode( $user->is_verified ) ),
			'public_key' => esc_html( $user->public_key ),
			'secret_key' => $has_developer_license ? FS_Plugin_License::mask_secret_key_for_html( $user->secret_key ) : esc_html( $user->secret_key ),
			'actions'    => array(),
		];

		if ( $has_developer_license ) {
			$row['actions'][] = array(
				'fs_action' => 'delete_user',
				'user_id'   => esc_attr( $user->id ),
				'classes'   => array( 'button' ),
				'label'     => fs_esc_html_x_inline( 'Delete', 'verb', 'delete' ),
			);
		}

		$data[] = $row;
	}

	$tableData = [
		'attributes' => [ 'id' => 'fs_users', 'class' => 'widefat' ],
		'headers'    => $headers,
		'data'       => $data,
	];

	echo fs_debug_generate_table( $tableData, array('title' => fs_esc_html_inline( 'Users' )) );

