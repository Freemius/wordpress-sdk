<?php
    /**
    * @package   Freemius
    * @copyright Copyright (c) 2015, Freemius, Inc.
    * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
    * @since     2.5.1
    */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    $clone_resolution_options_html = <<< HTML
    <div class="fs-clone-resolution-options-container">
        <table>
            <tbody></tbody>
        </table>
    </div>
HTML;

    $modal_content_html = <<< HTML
    <div class="notice notice-error inline"><p></p></div>
    {$clone_resolution_options_html}
HTML;

    fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
?>
<script type="text/javascript">
(function( $ ) {
    $( document ).ready(function() {
        var modalContentHtml            = <?php echo json_encode( $modal_content_html ) ?>,
            modalHtml                   =
                '<div class="fs-modal fs-modal-clone-resolution">'
                + '	<div class="fs-modal-dialog">'
                + '		<div class="fs-modal-header">'
                + '		    <h4>Clone Resolution (<span id="fs-product-title"></span>)</h4>'
                + '         <a href="!#" class="fs-close"><i class="dashicons dashicons-no" title="<?php echo esc_js( fs_text_x_inline( 'Dismiss', 'close window', 'dismiss' ) ) ?>"></i></a>'
                + '		</div>'
                + '		<div class="fs-modal-body">'
                + '			<div class="fs-modal-panel active">' + modalContentHtml + '</div>'
                + '		</div>'
                + '		<div class="fs-modal-footer">'
                + '			<button class="button button-secondary button-close" tabindex="4"><?php fs_esc_js_echo_inline( 'Cancel', 'cancel' ) ?></button>'
                + '		</div>'
                + '	</div>'
                + '</div>',
            $modal         = $( modalHtml ),
            setLoadingMode = function ( $button ) {
                $button
                    .addClass( 'fs-loading' )
                    .prop( 'disabled', true )
                    .find( '.fs-ajax-spinner').show();

                $( document.body ).css( { 'cursor': 'wait' } );
            },
            resetLoadingMode            = function ( $button ) {
                $button
                    .removeClass( 'fs-loading' )
                    .prop('disabled', false)
                    .find( '.fs-ajax-spinner' ).hide();

                $( document.body ).css( {'cursor': 'initial'} );
            };

        $modal.appendTo( $( 'body' ) );

        function registerEventHandlers() {
            $( '.fs-resolve-clone-button' ).click( function ( evt ) {
                evt.preventDefault();

                var $button = $( this ),
                    $form   = $button.parent();

                setLoadingMode( $button );

                $.post( <?php echo Freemius::ajax_url() ?>, {
                    action    : '<?php echo Freemius::get_ajax_action_static( 'get_clone_resolution_message' ) ?>',
                    security  : '<?php echo Freemius::get_ajax_security_static( 'get_clone_resolution_message' ) ?>',
                    product_id: $form.find('input[name="module_id"]').val()
                }, function ( response ) {
                    resetLoadingMode( $button );

                    if ( response.data ) {
                        $modal.find( '.fs-clone-resolution-options-container tbody' ).html( response.data.message );
                        $modal.find( '#fs-product-title' ).html( response.data.product_title );
                        showModal( evt );
                    }
                } );
            } );

            // If the user has clicked outside the window, close the modal.
            $modal.on( 'click', '.fs-close, .button-secondary', function () {
                closeModal();
                return false;
            } );
        }

        registerEventHandlers();

        function showModal() {
            // Display the dialog box.
            $modal.addClass( 'active' );
            $( 'body' ).addClass( 'has-fs-modal' );
        }

        function closeModal() {
            $modal.removeClass( 'active' );
            $( 'body' ).removeClass( 'has-fs-modal' );
        }
    } );
} )( jQuery );
</script>