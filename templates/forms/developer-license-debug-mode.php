<?php
	/**
	 * @package   Freemius
	 * @copyright Copyright (c) 2015, Freemius, Inc.
	 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since     2.3.1
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
     * @var array $VARS
     *
	 * @var Freemius $fs
	 */
	$fs           = freemius( $VARS['id'] );
	$slug         = $fs->get_slug();
    $unique_affix = $fs->get_unique_affix();

    $message_above_input_field = is_object( $fs->_get_license() ) ?
        fs_text_inline( 'Please enter the license key to enable the debug mode:', 'submit-developer-license-message', $slug ) :
        fs_text_inline(
            sprintf( 'To enter the debug mode, please enter the secret key of the license owner (User ID=%d), which you can find in your "My Profile" section of your User Dashboard:', $fs->get_developer_license()->user_id ),
            'submit-addon-developer-license-message',
            $slug
        );

    $submit_button_text = fs_text_inline( 'Submit License', 'submit-license', $slug );
	$license_key_text   = fs_text_inline( 'License key', 'license-key' , $slug );
    $license_input_html = "<input class='fs-license-key' type='text' placeholder='{$license_key_text}' tabindex='1' />";

	$modal_content_html = <<< HTML
	<div class="notice notice-error inline license-submission-message"><p></p></div>
	<p>{$message_above_input_field}</p>
	{$license_input_html}
HTML;

	fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
?>
<script type="text/javascript">
( function( $ ) {
	$( document ).ready( function() {
		var modalContentHtml          = <?php echo json_encode( $modal_content_html ) ?>,
			modalHtml                 =
				'<div class="fs-modal fs-modal-developer-license-debug-mode fs-modal-developer-license-debug-mode-<?php echo $unique_affix ?>">'
				+ '	<div class="fs-modal-dialog">'
				+ '		<div class="fs-modal-body">'
				+ '			<div class="fs-modal-panel active">' + modalContentHtml + '</div>'
				+ '		</div>'
				+ '		<div class="fs-modal-footer">'
				+ '			<button class="button button-secondary button-close" tabindex="4"><?php fs_esc_js_echo_inline( 'Cancel', 'cancel', $slug ) ?></button>'
				+ '			<button class="button button-primary button-submit-license"  tabindex="3"><?php echo esc_js( $submit_button_text ) ?></button>'
				+ '		</div>'
				+ '	</div>'
				+ '</div>',
			$modal                    = $( modalHtml ),
            $debugLicenseLink         = $( '.debug-license-trigger' ),
			$submitLicenseButton      = $modal.find( '.button-submit-license' ),
			$licenseKeyInput          = $modal.find( 'input.fs-license-key' ),
			$licenseSubmissionMessage = $modal.find( '.license-submission-message' );

		$modal.appendTo( $( 'body' ) );

		function registerEventHandlers() {
            $debugLicenseLink.click(function (evt) {
                evt.preventDefault();

                var $parent     = $debugLicenseLink.parent(),
                    isDebugMode = <?php echo $fs->has_developer_license() ? 'false' : 'true' ?>;

                if ( isDebugMode ) {
                    setDeveloperLicenseDebugMode();
                    return true;
                }

                showModal( evt );
            });

			$modal.on( 'input propertychange', 'input.fs-license-key', function () {
				var licenseKey = $( this ).val().trim();

				/**
				 * If license key is not empty, enable the license submission button.
				 */
				if ( licenseKey.length > 0 ) {
					enableSubmitLicenseButton();
				}
			});

			$modal.on( 'blur', 'input.fs-license-key', function () {
				var licenseKey = $( this ).val().trim();

                /**
                 * If license key is empty, disable the license submission button.
                 */
                if ( 0 === licenseKey.length ) {
                   disableSubmitLicenseButton();
                }
			});

			$modal.on( 'click', '.button-submit-license', function ( evt ) {
				evt.preventDefault();

				if ( $( this ).hasClass( 'disabled' ) ) {
					return;
				}

				var licenseKey = $licenseKeyInput.val().trim();

				disableSubmitLicenseButton();

				if ( 0 === licenseKey.length ) {
					return;
				}

                setDeveloperLicenseDebugMode( licenseKey );
			});

			// If the user has clicked outside the window, close the modal.
			$modal.on( 'click', '.fs-close, .button-secondary', function () {
				closeModal();
				return false;
			} );
		}

		registerEventHandlers();

		function setDeveloperLicenseDebugMode( licenseKey ) {
            var data = {
                action       : '<?php echo $fs->get_ajax_action( 'set_developer_license_debug_mode' ) ?>',
                security     : '<?php echo $fs->get_ajax_security( 'set_developer_license_debug_mode' ) ?>',
                license_key  : licenseKey,
                is_debug_mode: <?php echo $fs->has_developer_license() ? 'true' : 'false' ?>,
                module_id    : '<?php echo $fs->get_id() ?>'
            };

            $.ajax( {
                url       : ajaxurl,
                method    : 'POST',
                data      : data,
                beforeSend: function () {
                    $submitLicenseButton.text( '<?php fs_esc_js_echo_inline( 'Processing', 'processing', $slug ) ?>...' );
                },
                success   : function ( result ) {
                    if ( result.success ) {
                        closeModal();

                        // Reload the "Account" page so that the pricing/upgrade link will be properly hidden/shown.
                        window.location.reload();
                    } else {
                        showError( result.error.message ? result.error.message : result.error );
                        resetSubmitLicenseButton();
                    }
                },
                error     : function () {
                    showError( <?php echo json_encode( fs_text_inline( 'An unknown error has occurred.', 'unknown-error', $slug ) ) ?> );
                    resetSubmitLicenseButton();
                }
            });
        }

		function showModal( evt ) {
			resetModal();

			// Display the dialog box.
			$modal.addClass( 'active' );
			$( 'body' ).addClass( 'has-fs-modal' );

            $licenseKeyInput.val( '' );
            $licenseKeyInput.focus();
		}

		function closeModal() {
			$modal.removeClass( 'active' );
			$( 'body' ).removeClass( 'has-fs-modal' );
		}

		function resetSubmitLicenseButton() {
			enableSubmitLicenseButton();
			$submitLicenseButton.text( <?php echo json_encode( $submit_button_text ) ?> );
		}

		function resetModal() {
			hideError();
			resetSubmitLicenseButton();
		}

		function enableSubmitLicenseButton() {
			$submitLicenseButton.removeClass( 'disabled' );
		}

		function disableSubmitLicenseButton() {
			$submitLicenseButton.addClass( 'disabled' );
		}

		function hideError() {
			$licenseSubmissionMessage.hide();
		}

		function showError( msg ) {
			$licenseSubmissionMessage.find( ' > p' ).html( msg );
			$licenseSubmissionMessage.show();
		}
	} );
} )( jQuery );
</script>