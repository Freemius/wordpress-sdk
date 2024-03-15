<?php
	foreach ( $VARS['module_types'] as $module_type ) {
		$licenses = $VARS[ $module_type . '_licenses' ];

		if ( is_array( $licenses ) && count( $licenses ) > 0 ) {
			$data = [
				'attributes' => [
					'id'    => 'fs_' . $module_type . '_licenses',
					'class' => 'widefat',
				],
				'headers'    => [
					[ 'key' => 'id', 'val' => fs_esc_html_inline( 'ID', 'id' ) ],
					[ 'key' => 'plugin_id', 'val' => fs_esc_html_inline( 'Plugin ID' ) ],
					[ 'key' => 'user_id', 'val' => fs_esc_html_inline( 'User ID' ) ],
					[ 'key' => 'plan_id', 'val' => fs_esc_html_inline( 'Plan ID' ) ],
					[ 'key' => 'quota', 'val' => fs_esc_html_inline( 'Quota' ) ],
					[ 'key' => 'activated', 'val' => fs_esc_html_inline( 'Activated' ) ],
					[ 'key' => 'blocking', 'val' => fs_esc_html_inline( 'Blocking' ) ],
					[ 'key' => 'type', 'val' => fs_esc_html_inline( 'Type' ) ],
					[ 'key' => 'license_key', 'val' => fs_esc_html_inline( 'License Key' ) ],
					[ 'key' => 'expiration', 'val' => fs_esc_html_x_inline( 'Expiration', 'as expiration date' ) ],
				],
				'data'       => [],
			];

			foreach ( $licenses as $license ) {
				$data['data'][] = [
					'id'          => $license->id,
					'plugin_id'   => $license->plugin_id,
					'user_id'     => $license->user_id,
					'plan_id'     => $license->plan_id,
					'quota'       => $license->is_unlimited() ? 'Unlimited' : ( $license->is_single_site() ? 'Single Site' : $license->quota ),
					'activated'   => $license->activated,
					'blocking'    => $license->is_block_features ? 'Blocking' : 'Flexible',
					'type'        => $license->is_whitelabeled ? 'Whitelabeled' : 'Normal',
					'license_key' => ( $license->is_whitelabeled || ! isset( $user_ids_map[ $license->user_id ] ) ) ? $license->get_html_escaped_masked_secret_key() : esc_html( $license->secret_key ),
					'expiration'  => $license->expiration,
				];
			}

			echo '<h2>' . esc_html( sprintf( fs_text_inline( '%s Licenses', 'module-licenses' ),
					( WP_FS__MODULE_TYPE_PLUGIN === $module_type ? fs_text_inline( 'Plugin',
						'plugin' ) : fs_text_inline( 'Theme', 'theme' ) ) ) ) . '</h2>';

			fs_debug_generate_table( $data );
		}
	}