<?php
	foreach ( $VARS['module_types'] as $module_type ) {
		$modules = fs_get_entities( $VARS['fs_options']->get_option( $module_type . 's' ),
			FS_Plugin::get_class_name() );

		$current_theme = wp_get_theme();

		if ( is_array( $modules ) && count( $modules ) > 0 ) {
			$data = [
				'attributes' => [
					'id'    => 'fs_' . $module_type,
					'class' => 'widefat',
				],
				'headers'    => [
					[ 'key' => 'id', 'val' => 'ID' ],
					[ 'key' => 'slug', 'val' => 'Slug' ],
					[ 'key' => 'version', 'val' => 'Version' ],
					[ 'key' => 'title', 'val' => 'Title' ],
					[ 'key' => 'api', 'val' => 'API' ],
					[ 'key' => 'freemius_state', 'val' => 'Freemius State' ],
					[ 'key' => 'module_path', 'val' => 'Module Path' ],
					[ 'key' => 'public_key', 'val' => 'Public Key' ],
					$VARS['is_multisite'] ? [ 'key' => 'network_blog', 'val' => 'Network Blog' ] : null,
					$VARS['is_multisite'] ? [ 'key' => 'network_user', 'val' => 'Network User' ] : null,
					[ 'key' => 'actions', 'val' => 'Actions' ],
				],
				'data'       => [],
			];

			$data['headers'] = array_filter( $data['headers'] );

			foreach ( $modules as $slug => $module_data ) {
				// Determine if the module is active based on the module type and current theme.
				$is_active = ( WP_FS__MODULE_TYPE_THEME !== $module_type && is_plugin_active( $module_data->file ) ) ||
				             ( $current_theme->stylesheet === $module_data->file ) ||
				             ( is_child_theme() && ( $current_theme->parent() instanceof WP_Theme ) && $current_theme->parent()->stylesheet === $module_data->file );

				$freemius_status = fs_text_x_inline( 'Off', 'as turned off' );

				$row_style               = 'default';
				$api_connectivity_status = fs_text_x_inline( 'Unknown',
					'API connectivity state is unknown' ); // Default status

				if ( $is_active ) {
					$fs                                       = freemius( $module_data->id );
					$active_modules_by_id[ $module_data->id ] = true; // Track active modules

					$has_api_connectivity = $fs->has_api_connectivity();

					if ( $has_api_connectivity === true ) {
						if ( $fs->is_on() ) {
							$row_style               = 'success';
							$api_connectivity_status = fs_text_x_inline( 'Connected', 'as connection was successful' );
							$freemius_status         = fs_text_x_inline( 'On', 'as turned on' );
						}
					} else if ( $has_api_connectivity === false ) {
						$row_style               = 'error';
						$api_connectivity_status = fs_text_x_inline( 'Blocked', 'as connection blocked' );
					}
				}

				// Create the row data, setting 'row_style' based on 'is_active' status
				$row = [
					'id'             => esc_html( $module_data->id ),
					'slug'           => esc_html( $slug ),
					'version'        => esc_html( $module_data->version ),
					'title'          => esc_html( $module_data->title ),
					'api'            => esc_html( $api_connectivity_status ),
					'freemius_state' => $freemius_status,
					'module_path'    => $module_data->file,
					'public_key'     => $module_data->public_key,
					'actions'        => array(),
					'row_style'      => $row_style,
				];

				if ( $is_active ) {
					if ( $fs->has_trial_plan() ) {
						$row['actions'][] = array(
							'fs_action' => 'simulate_trial',
							'module_id' => $fs->get_id(),
							'label'     => fs_esc_html_inline( 'Simulate Trial Promotion' ),
							'classes'   => array( 'button', 'button-primary', 'simulate-trial' ),
						);
					}

					if ( $fs->is_registered() ) {
						$row['actions'][] = array(
							'href'    => $fs->get_account_url(),
							'label'   => fs_esc_html_inline( 'Account', 'account' ),
							'classes' => array( 'button' ),
						);
					}

					if ( fs_is_network_admin() && ! $fs->is_network_upgrade_mode() ) {
						$row['actions'][] = array(
							'fs_action' => 'simulate_network_upgrade',
							'module_id' => $fs->get_id(),
							'label'     => fs_esc_html_inline( 'Simulate Network Upgrade' ),
						);
					}
				}
				$data['data'][] = $row;
			}

			$title = esc_html( ( WP_FS__MODULE_TYPE_PLUGIN == $module_type ) ? fs_text_inline( 'Plugins', 'plugins' ) : fs_text_inline( 'Themes', 'themes' ) );
			echo fs_debug_generate_table( $data, array( 'title' => $title ) );
		}
	}
