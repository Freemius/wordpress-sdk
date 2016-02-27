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
			$fs             = freemius( $slug );
			$next_execution = $fs->next_sync_cron();
			$last_execution = $fs->last_sync_cron();
			if ( false !== $next_execution ) {
				$scheduled_crons[ $slug ] = array(
					'name' => $fs->get_plugin_name(),
					'slug' => $slug,
					'last' => $last_execution,
					'next' => $next_execution,
				);
			}
		}
	}
?>
<h1><?php _efs( 'scheduled-crons' ) ?></h1>
<table class="widefat">
	<thead>
	<tr>
		<th><?php _efs( 'slug' ) ?></th>
		<th><?php _efs( 'plugin' ) ?></th>
		<th><?php _efs( 'Last' ) ?></th>
		<th><?php _efs( 'Next' ) ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ( $scheduled_crons as $cron ) : ?>
		<tr>
			<td><?php echo $cron['slug'] ?></td>
			<td><?php echo $cron['name'] ?></td>
			<td><?php
					$diff       = abs( WP_FS__SCRIPT_START_TIME - $cron['last'] );
					$human_diff = ( $diff < MINUTE_IN_SECONDS ) ?
						$diff . ' ' . __fs( 'sec' ) :
						human_time_diff( WP_FS__SCRIPT_START_TIME, $cron['last'] );

					if ( WP_FS__SCRIPT_START_TIME < $cron['last'] ) {
						printf( __fs( 'in-x' ), $human_diff );
					} else {
						printf( __fs( 'x-ago' ), $human_diff );
					}
				?></td>
			<td><?php
					$diff       = abs( WP_FS__SCRIPT_START_TIME - $cron['next'] );
					$human_diff = ( $diff < MINUTE_IN_SECONDS ) ?
						$diff . ' ' . __fs( 'sec' ) :
						human_time_diff( WP_FS__SCRIPT_START_TIME, $cron['next'] );

					if ( WP_FS__SCRIPT_START_TIME < $cron['next'] ) {
						printf( __fs( 'in-x' ), $human_diff );
					} else {
						printf( __fs( 'x-ago' ), $human_diff );
					}
				?></td>
		</tr>
	<?php endforeach ?>
	</tbody>
</table>
