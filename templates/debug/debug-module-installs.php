<?php
	$data = [];

	/** @var array $VARS */
	foreach ( $VARS['module_types'] as $module_type ) {

		$sites_map = $VARS[ $module_type . '_sites' ];
		$all_plans = false;

		if ( is_array( $sites_map ) && count( $sites_map ) > 0 ) {
			$data['attributes'] = [
				'id'    => 'fs_' . $module_type . '_installs',
				'class' => 'widefat',
			];

			$data['headers'] = [
				[ 'key' => 'id', 'val' => 'ID' ],
				$VARS['is_multisite'] ? [ 'key' => 'blog_id', 'val' => 'Blog ID' ] : null,
				$VARS['is_multisite'] ? [ 'key' => 'address', 'val' => 'Address' ] : null,
				[ 'key' => 'slug', 'val' => 'Slug' ],
				[ 'key' => 'user_id', 'val' => 'User ID' ],
				[ 'key' => 'license_id', 'val' => 'License ID' ],
				[ 'key' => 'plan', 'val' => 'Plan' ],
				[ 'key' => 'public_key', 'val' => 'Public Key' ],
				[ 'key' => 'secret_key', 'val' => 'Secret Key' ],
				[ 'key' => 'actions', 'val' => 'Actions' ],
			];

			$data['headers'] = array_filter( $data['headers'] );

			$data['data'] = [];

			foreach ( $sites_map as $slug => $sites ) {
				foreach ( $sites as $site ) {
					$row = [
						'id'         => $site->id,
						'slug'       => $slug,
						'user_id'    => $site->user_id,
						'license_id' => ! empty( $site->license_id ) ? $site->license_id : '',
						'plan'       => fs_debug_get_plan_name( $site,
							$slug,
							$module_type,
							$all_plans,
							$VARS['fs_options'] ),
						'public_key' => $site->public_key,
						'secret_key' => fs_debug_get_secret_key( $site, $module_type, $slug ),
						'actions'    => array(
							array(
								'fs_action'   => 'delete_install',
								'module_id'   => $site->plugin_id,
								'blog_id'     => $VARS['is_multisite'] ? $site->blog_id : null,
								'module_type' => $module_type,
								'slug'        => $slug,
								'label'       => fs_esc_html_x_inline( 'Delete', 'verb', 'delete' ),
								'classes'     => array( 'button' ),
							),
						),
					];

					if ( $VARS['is_multisite'] ) {
						$row['blog_id'] = $site->blog_id;
						$row['address'] = $site->url; // Assumi che questo sia l'indirizzo. Potresti voler applicare fs_strip_url_protocol()
					}

					$data['data'][] = $row;
				}
			}

			$title = esc_html( sprintf( fs_text_inline( '%s Installs', 'module-installs' ),
				( WP_FS__MODULE_TYPE_PLUGIN === $module_type ? fs_text_inline( 'Plugin',
					'plugin' ) : fs_text_inline( 'Theme', 'theme' ) ) ) );
			$title .= ' / ';
			$title .= fs_esc_html_x_inline( 'Sites', 'like websites', 'sites' );

			echo fs_debug_generate_table( $data, array( 'title' => $title ) );
		}
	}