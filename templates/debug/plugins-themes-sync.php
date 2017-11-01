<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.1.7.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$fs_options  = FS_Option_Manager::get_manager( WP_FS__ACCOUNTS_OPTION_NAME, true );
	$all_plugins = $fs_options->get_option( 'all_plugins' );
	$all_themes  = $fs_options->get_option( 'all_themes' );
?>
<h1><?php fs_esc_html_echo_inline( 'Plugins & Themes Sync', 'plugins-themes-sync' ) ?></h1>
<table class="widefat">
	<thead>
	<tr>
		<th></th>
		<th><?php fs_echo_inline( 'total' ) ?></th>
		<th><?php fs_echo_inline( 'Last' ) ?></th>
	</tr>
	</thead>
	<tbody>
	<?php if ( is_object( $all_plugins ) ) : ?>
		<tr>
			<td><?php fs_echo_inline( 'plugins' ) ?></td>
			<td><?php echo count( $all_plugins->plugins ) ?></td>
			<td><?php
					if ( isset( $all_plugins->timestamp ) && is_numeric( $all_plugins->timestamp ) ) {
						$diff       = abs( WP_FS__SCRIPT_START_TIME - $all_plugins->timestamp );
						$human_diff = ( $diff < MINUTE_IN_SECONDS ) ?
							$diff . ' ' . fs_text_inline( 'sec' ) :
							human_time_diff( WP_FS__SCRIPT_START_TIME, $all_plugins->timestamp );

						if ( WP_FS__SCRIPT_START_TIME < $all_plugins->timestamp ) {
							printf( fs_text_inline( 'in-x' ), $human_diff );
						} else {
							printf( fs_text_inline( 'x-ago' ), $human_diff );
						}
					}
				?></td>
		</tr>
	<?php endif ?>
	<?php if ( is_object( $all_themes ) ) : ?>
		<tr>
			<td><?php fs_echo_inline( 'themes' ) ?></td>
			<td><?php echo count( $all_themes->themes ) ?></td>
			<td><?php
					if ( isset( $all_themes->timestamp ) && is_numeric( $all_themes->timestamp ) ) {
						$diff       = abs( WP_FS__SCRIPT_START_TIME - $all_themes->timestamp );
						$human_diff = ( $diff < MINUTE_IN_SECONDS ) ?
							$diff . ' ' . fs_text_inline( 'sec' ) :
							human_time_diff( WP_FS__SCRIPT_START_TIME, $all_themes->timestamp );

						if ( WP_FS__SCRIPT_START_TIME < $all_themes->timestamp ) {
							printf( fs_text_inline( 'in-x' ), $human_diff );
						} else {
							printf( fs_text_inline( 'x-ago' ), $human_diff );
						}
					}
				?></td>
		</tr>
	<?php endif ?>
	</tbody>
</table>
