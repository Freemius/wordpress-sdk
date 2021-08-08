<?php
    /**
     * @package   Freemius
     * @copyright Copyright (c) 2015, Freemius, Inc.
     * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since     2.4.3
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }
?>
<script type="text/javascript">
    ( function( $ ) {
        var $errorMessage = null;

        $( document ).ready( function() {
            var $cloneResolutionNotice = $( 'div[data-id="clone_resolution_options_notice"], div[data-id="temporary_duplicate_notice"]' );

            if ( 1 === $cloneResolutionNotice.length ) {
                $errorMessage = $cloneResolutionNotice.find( '#fs_clone_resolution_error_message' );

                $cloneResolutionNotice.on( 'click', '.button, #fs_temporary_duplicate_license_activation_link', function( evt ) {
                    evt.preventDefault();

                    var $this  = $( this ),
                        cursor = $this.css( 'cursor' );

                    if ( $this.hasClass( 'disabled' ) ) {
                        return;
                    }

                    $.ajax( {
                        url       : ajaxurl,
                        method    : 'POST',
                        data      : {
                            action      : '<?php echo $VARS['ajax_action'] ?>',
                            security    : '<?php echo wp_create_nonce( $VARS['ajax_action'] ) ?>',
                            clone_action: $this.data( 'clone-action' )
                        },
                        beforeSend: function() {
                            hideError();

                            $this.css( { 'cursor': 'wait' } );

                            $cloneResolutionNotice.find( '.button' ).addClass( 'disabled' );
                        },
                        success   : function( resultObj ) {
                            if ( resultObj.success ) {
                                $cloneResolutionNotice.remove();
                            } else if ( '' !== resultObj.error.redirect_url ) {
                                window.location = resultObj.error.redirect_url;
                            } else {
                                showError( resultObj.error.message );
                            }
                        },
                        complete  : function() {
                            $this.css( { 'cursor': cursor } );
                            $cloneResolutionNotice.find( '.button' ).removeClass( 'disabled' );
                        }
                    } );
                } );
            }
        } );

        function hideError() {
            $errorMessage.hide();
        }

        function showError( msg ) {
            $errorMessage.text( msg );
            $errorMessage.show();
        }
    } )( jQuery );
</script>