<?php
	$title = fs_esc_html_inline( 'Scheduled Crons' );

	$fs_options      = FS_Options::instance( WP_FS__ACCOUNTS_OPTION_NAME, true );
	$scheduled_crons = array();

	foreach ( $VARS['module_types'] as $module_type ) {
		$modules = fs_get_entities( $fs_options->get_option( $module_type . 's' ), FS_Plugin::get_class_name() );
		if ( is_array( $modules ) && count( $modules ) > 0 ) {
			foreach ( $modules as $slug => $data ) {
				if ( WP_FS__MODULE_TYPE_THEME === $module_type ) {
					$current_theme = wp_get_theme();
					$is_active     = ( $current_theme->stylesheet === $data->file );
				} else {
					$is_active = is_plugin_active( $data->file );
				}

				/**
				 * @author Vova Feldman
				 *
				 * @since  1.2.1 Don't load data from inactive modules.
				 */
				if ( $is_active ) {
					$fs = freemius( $data->id );

					$next_execution = $fs->next_sync_cron();
					$last_execution = $fs->last_sync_cron();

					if ( false !== $next_execution ) {
						$scheduled_crons[ $slug ][] = array(
							'name'        => $fs->get_plugin_name(),
							'slug'        => $slug,
							'module_type' => $fs->get_module_type(),
							'type'        => 'sync_cron',
							'last'        => $last_execution,
							'next'        => $next_execution,
						);
					}

					$next_install_execution = $fs->next_install_sync();
					$last_install_execution = $fs->last_install_sync();

					if ( false !== $next_install_execution ||
					     false !== $last_install_execution
					) {
						$scheduled_crons[ $slug ][] = array(
							'name'        => $fs->get_plugin_name(),
							'slug'        => $slug,
							'module_type' => $fs->get_module_type(),
							'type'        => 'install_sync',
							'last'        => $last_install_execution,
							'next'        => $next_install_execution,
						);
					}
				}
			}
		}
	}

	$data = [
		'attributes' => [
			'id'    => 'active-modules-table',
			'class' => 'wp-list-table widefat fixed striped',
		],
		'headers'    => [
			[ 'key' => 'name', 'val' => 'Name' ],
			[ 'key' => 'slug', 'val' => 'Slug' ],
			[ 'key' => 'module_type', 'val' => 'Module Type' ],
			[ 'key' => 'type', 'val' => 'Sync Type' ],
			[ 'key' => 'last', 'val' => 'Last Execution' ],
			[ 'key' => 'next', 'val' => 'Next Execution' ],
		],
		'data'       => [],
	];

	foreach ( $scheduled_crons as $slug => $crons ) {
		foreach ( $crons as $cron ) {
			$data['data'][] = [
				'name'        => $cron['name'],
				'slug'        => $cron['slug'],
				'module_type' => $cron['module_type'],
				'type'        => $cron['type'],
				'last'        => fs_debug_format_time( $cron['last'] ),
				'next'        => fs_debug_format_time( $cron['next'] ),
			];
		}
	}

	echo fs_debug_generate_table( $data, array( 'title' => $title, 'hidden' => true ) );