<?php
    /**
     * @package     Freemius
     * @copyright   Copyright (c) 2016, Freemius, Inc.
     * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since       2.5.0
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    /**
     * @var array $VARS
     * @var Freemius $fs
     */
    $fs                     = $VARS['freemius'];
    $is_license_available   = $VARS['is_license_available'];
    $slug                   = $VARS['slug'];
    $plan                   = $VARS['plan'];


    $button_id = 'fs-disconnect-button-' . $fs->get_id();
    $message   = $is_license_available ?
        sprintf( fs_text_inline( 'Disconnecting the website from your account will also automatically deactivate your %s plan license so you can use it on other sites. If you want to terminate the recurring payments as well, click the "Cancel" button, and first "Downgrade" your account. Are you sure you would like to continue with the disconnection?', 'disconnect-x-confirm', $slug ), $plan->title ) :
        sprintf( fs_text_inline( 'Only disconnect if you no longer want to use this %s anymore. Are you sure you would like to continue with the disconnection?', 'disconnect-confirm', $slug ), $fs->get_module_label( true ) );

?>
<script type="text/javascript">
    // Wrap in a IFFE to prevent leaking global variables.
    (function() {
        var disconnect_button_id = <?php echo wp_json_encode( $button_id ); ?>;
        var message = <?php echo wp_json_encode( $message ); ?>;

        jQuery(document).ready(function($) {
            $('#' + disconnect_button_id).click(function(e) {
                // Prevent the form being submitted.
                e.preventDefault();

                // Show the confirm and if yes, then submit the form.
                if (confirm(message)) {
                    this.parentNode.submit();
                }
            });
        });
    })();
</script>
<form action="<?php echo esc_attr( $fs->_get_admin_page_url( 'account' )); ?>" method="POST">
    <input type="hidden" name="fs_action" value="delete_account">
    <?php wp_nonce_field( 'delete_account' ) ?>

    <button class="fs-button-inline" id="<?php echo esc_attr( $button_id ); ?>"><i
                class="dashicons dashicons-no"></i> <?php fs_esc_html_echo_inline( 'Disconnect', 'disconnect', $slug ) ?></button>
</form>