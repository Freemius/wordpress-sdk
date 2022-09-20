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
    $fs              = $VARS['freemius'];
    $paid_plan_title = $VARS['paid_plan_title'];
    $slug            = $VARS['slug'];

    $button_id = "fs_disconnect_button_{$fs->get_id()}";

    $product_type = $fs->get_module_label( true );

    $message = $paid_plan_title ?
        /** translators: %1$s is replaced by the paid plan name, %2$s is replaced by the type of the module */
        sprintf( fs_text_inline( 'Disconnecting the website will permanently remove it from your User Dashboard\'s account. If you wish to cancel your %1$s plan\'s subscription instead, please navigate to the User Dashboard (https://users.freemius.com) and cancel it there. Are you sure you would like to disconnect the website permanently?', 'disconnect-x-confirm', $slug ), $paid_plan_title, $product_type ) :
        sprintf( fs_text_inline( 'Only disconnect if you no longer want to use this %s anymore. Are you sure you would like to continue with the disconnection?', 'disconnect-confirm', $slug ), $product_type );

?>
<script type="text/javascript">
    // Wrap in a IFFE to prevent leaking global variables.
    ( function( $ ) {
        var disconnectButtonID = <?php echo wp_json_encode( $button_id ) ?>;
        var message = <?php echo wp_json_encode( $message ) ?>;

        $( document ).ready( function() {
            $( '#' + disconnectButtonID ).click( function( e ) {
                // Prevent the form being submitted.
                e.preventDefault();

                // Show the confirm and if yes, then submit the form.
                if ( confirm( message ) ) {
                    this.parentNode.submit();
                }
            } );
        } );
    } )( jQuery );
</script>
<form action="<?php echo esc_attr( $fs->_get_admin_page_url( 'account' )); ?>" method="POST">
    <input type="hidden" name="fs_action" value="delete_account">
    <?php wp_nonce_field( 'delete_account' ) ?>

    <a href="#" class="fs-button-inline" id="<?php echo esc_attr( $button_id ); ?>">
        <i class="dashicons dashicons-no"></i>
        <?php fs_esc_html_echo_inline( 'Disconnect', 'disconnect', $slug ) ?>
    </a>
</form>