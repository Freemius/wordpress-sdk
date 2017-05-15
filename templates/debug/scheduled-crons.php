<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.1.7.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$fs_options      = FS_Option_Manager::get_manager( WP_FS__ACCOUNTS_OPTION_NAME, true );
	$plugins         = $fs_options->get_option( 'plugins' );
	$scheduled_crons = array();
	if ( is_array( $plugins ) && 0 < count( $plugins ) ) {
		foreach ( $plugins as $slug => $data ) {
			/**
			 * @author Vova Feldman
			 *
			 * @since 1.2.1 Don't load data from inactive modules.
			 */
			if ( is_plugin_active( $data->file ) ) {
				$fs = freemius( $slug );

				$next_execution = $fs->next_sync_cron();
				$last_execution = $fs->last_sync_cron();

				if ( false !== $next_execution ) {
					$scheduled_crons[ $slug ][] = array(
						'name' => $fs->get_plugin_name(),
						'slug' => $slug,
						'type' => 'sync_cron',
						'last' => $last_execution,
						'next' => $next_execution,
					);
				}

				$next_install_execution = $fs->next_install_sync();
				$last_install_execution = $fs->last_install_sync();

				if ( false !== $next_install_execution ||
				     false !== $last_install_execution
				) {
					$scheduled_crons[ $slug ][] = array(
						'name' => $fs->get_plugin_name(),
						'slug' => $slug,
						'type' => 'install_sync',
						'last' => $last_install_execution,
						'next' => $next_install_execution,
					);
				}
			}
		}
	}
?>
<h1><?php fs_echo( 'scheduled-crons' ) ?></h1>
<table class="widefat">
	<thead>
	<tr>
		<th><?php fs_echo( 'slug' ) ?></th>
		<th><?php fs_echo( 'plugin' ) ?></th>
		<th><?php fs_echo( 'type' ) ?></th>
		<th><?php fs_echo( 'Last' ) ?></th>
		<th><?php fs_echo( 'Next' ) ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ( $scheduled_crons as $slug => $crons ) : ?>
		<?php foreach ( $crons as $cron ) : ?>
			<tr>
				<td><?php echo $slug ?></td>
				<td><?php echo $cron['name'] ?></td>
				<td><?php echo $cron['type'] ?></td>
				<td><?php
						if ( is_numeric( $cron['last'] ) ) {
							$diff       = abs( WP_FS__SCRIPT_START_TIME - $cron['last'] );
							$human_diff = ( $diff < MINUTE_IN_SECONDS ) ?
								$diff . ' ' . fs_text( 'sec' ) :
								human_time_diff( WP_FS__SCRIPT_START_TIME, $cron['last'] );

							if ( WP_FS__SCRIPT_START_TIME < $cron['last'] ) {
								printf( fs_text( 'in-x' ), $human_diff );
							} else {
								printf( fs_text( 'x-ago' ), $human_diff );
							}

//							echo ' ' . $cron['last'];
						}
					?></td>
				<td><?php
						if ( is_numeric( $cron['next'] ) ) {
							$diff       = abs( WP_FS__SCRIPT_START_TIME - $cron['next'] );
							$human_diff = ( $diff < MINUTE_IN_SECONDS ) ?
								$diff . ' ' . fs_text( 'sec' ) :
								human_time_diff( WP_FS__SCRIPT_START_TIME, $cron['next'] );

							if ( WP_FS__SCRIPT_START_TIME < $cron['next'] ) {
								printf( fs_text( 'in-x' ), $human_diff );
							} else {
								printf( fs_text( 'x-ago' ), $human_diff );
							}
						}
					?></td>
			</tr>
		<?php endforeach ?>
	<?php endforeach ?>
	</tbody>
</table>
