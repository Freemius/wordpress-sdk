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
    $fs                 = $VARS['freemius'];
    $premium_plan_title = $VARS['premium_plan_title'];
    $slug               = $VARS['slug'];

    $button_id = "fs-disconnect-button-{$fs->get_id()}";

    $product_type = $fs->get_module_label( true );

    $message = $premium_plan_title ?
        sprintf( fs_text_inline( 'Your %1$s plan is not active on this site at the moment and you can activate it on any other site. Only disconnect if you no longer want to use this %2$s anymore. If you wish to stop the recurring payment of the %1$s plan instead, then kindly click the CANCEL button, activate the %1$s plan and click on the "Downgrade" button to downgrade your account. Are you sure you would like to continue with this disconnection?', 'disconnect-x-confirm', $slug ), $premium_plan_title, $product_type ) :
        sprintf( fs_text_inline( 'Only disconnect if you no longer want to use this %s anymore. Are you sure you would like to continue with the disconnection?', 'disconnect-confirm', $slug ), $product_type );

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