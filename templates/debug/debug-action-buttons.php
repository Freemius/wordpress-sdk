<h2><?php fs_esc_html_echo_inline( 'Actions', 'actions' ) ?></h2>
<table>
    <tbody>
    <tr>
        <td>
            <!-- Delete All Accounts -->
            <form action="" method="POST">
                <input type="hidden" name="fs_action" value="restart_freemius">
				<?php wp_nonce_field( 'restart_freemius' ) ?>
                <button class="button button-primary"
                        onclick="if (confirm('<?php fs_esc_attr_echo_inline( 'Are you sure you want to delete all Freemius data?',
					        'delete-all-confirm' ) ?>')) this.parentNode.submit(); return false;"><?php fs_esc_html_echo_inline( 'Delete All Accounts' ) ?></button>
            </form>
        </td>
        <td>
            <!-- Clear API Cache -->
            <form action="" method="POST">
                <input type="hidden" name="fs_clear_api_cache" value="true">
                <button class="button button-primary"><?php fs_esc_html_echo_inline( 'Clear API Cache' ) ?></button>
            </form>
        </td>
        <td>
            <!-- Clear Updates Transients -->
            <form action="" method="POST">
                <input type="hidden" name="fs_action" value="clear_updates_data">
				<?php wp_nonce_field( 'clear_updates_data' ) ?>
                <button class="button"><?php fs_esc_html_echo_inline( 'Clear Updates Transients' ) ?></button>
            </form>
        </td>
		<?php if ( Freemius::is_deactivation_snoozed() ) : ?>
            <td>
                <!-- Reset Deactivation Snoozing -->
                <form action="" method="POST">
                    <input type="hidden" name="fs_action" value="reset_deactivation_snoozing">
					<?php wp_nonce_field( 'reset_deactivation_snoozing' ) ?>
                    <button class="button"><?php fs_esc_html_echo_inline( 'Reset Deactivation Snoozing' ) ?> (Expires
                        in <?php echo( Freemius::deactivation_snooze_expires_at() - time() ) ?> sec)
                    </button>
                </form>
            </td>
		<?php endif ?>
        <td>
            <!-- Sync Data with Server -->
            <form action="" method="POST">
                <input type="hidden" name="background_sync" value="true">
                <button class="button button-primary"><?php fs_esc_html_echo_inline( 'Sync Data From Server' ) ?></button>
            </form>
        </td>
		<?php if ( fs_is_network_admin() && true !== $fs_options->get_option( 'ms_migration_complete',
				false,
				true ) ) : ?>
            <td>
                <!-- Migrate Options to Network -->
                <form action="" method="POST">
                    <input type="hidden" name="fs_action" value="migrate_options_to_network">
					<?php wp_nonce_field( 'migrate_options_to_network' ) ?>
                    <button class="button button-primary"><?php fs_esc_html_echo_inline( 'Migrate Options to Network' ) ?></button>
                </form>
            </td>
		<?php endif ?>
        <td>
            <button id="fs_load_db_option" class="button"><?php fs_esc_html_echo_inline( 'Load DB Option' ) ?></button>
        </td>
        <td>
            <button id="fs_set_db_option" class="button"><?php fs_esc_html_echo_inline( 'Set DB Option' ) ?></button>
        </td>
        <td>
			<?php
				$fs_debug_page_url = 'admin.php?page=freemius&fs_action=allow_clone_resolution_notice';
				$fs_debug_page_url = fs_is_network_admin() ?
					network_admin_url( $fs_debug_page_url ) :
					admin_url( $fs_debug_page_url );
			?>
            <a href="<?php echo wp_nonce_url( $fs_debug_page_url, 'fs_allow_clone_resolution_notice' ) ?>"
               class="button button-primary">Resolve Clone(s)</a>
        </td>
    </tr>
    </tbody>
</table>
<script type="text/javascript">
    (function ($) {
        $('#fs_load_db_option').click(function () {
            var optionName = prompt('Please enter the option name:');

            if (optionName) {
                $.post(<?php echo Freemius::ajax_url() ?>, {
                    action: 'fs_get_db_option',
                    // As such we don't need to use `wp_json_encode` method but using it to follow wp.org guideline.
                    _wpnonce   : <?php echo wp_json_encode( wp_create_nonce( 'fs_get_db_option' ) ); ?>,
                    option_name: optionName
                }, function (response) {
                    if (response.data.value)
                        prompt('The option value is:', response.data.value);
                    else
                        alert('Oops... Option does not exist in the DB.');
                });
            }
        });

        $('#fs_set_db_option').click(function () {
            var optionName = prompt('Please enter the option name:');

            if (optionName) {
                var optionValue = prompt('Please enter the option value:');

                if (optionValue) {
                    $.post(<?php echo Freemius::ajax_url() ?>, {
                        action: 'fs_set_db_option',
                        // As such we don't need to use `wp_json_encode` method but using it to follow wp.org guideline.
                        _wpnonce    : <?php echo wp_json_encode( wp_create_nonce( 'fs_set_db_option' ) ); ?>,
                        option_name : optionName,
                        option_value: optionValue
                    }, function () {
                        alert('Option was successfully set.');
                    });
                }
            }
        });
    })(jQuery);
</script>
