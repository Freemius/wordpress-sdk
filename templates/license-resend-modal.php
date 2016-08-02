<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.2.0
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$slug = $VARS['slug'];
	$fs   = freemius( $slug );

	$message_above_input_field  = __fs( 'ask-for-upgrade-email-address', $slug );
	$send_button_text           = __fs( 'send-license-key' , $slug );

	$modal_content_html = <<< HTML
	<div class="notice notice-error inline license-resend-message"><p></p></div>
	<p>{$message_above_input_field}</p>
	<div class="input-container">
		<a href="#" class="button button-primary button-send-license-key disabled">{$send_button_text}</a>
	    <div>
	        <input class="email-address" type="text">
	    </div>
    </div>
HTML;
?>
<script type="text/javascript">
(function( $ ) {
	$( document ).ready(function() {
		var modalContentHtml = <?php echo json_encode( $modal_content_html ); ?>,
			modalHtml =
				'<div class="fs-modal fs-modal-resend-license-key">'
				+ '	<div class="fs-modal-dialog">'
				+ '		<div class="fs-modal-body">'
				+ '			<div class="fs-modal-panel active">' + modalContentHtml + '</div>'
				+ '		</div>'
				+ '	</div>'
				+ '</div>',
			$modal = $( modalHtml ),
			$sendLicenseKeyButton = $modal.find( '.button-send-license-key' ),
			$emailAddressInput    = $modal.find( 'input.email-address' ),
			$licenseResendMessage = $modal.find( '.license-resend-message' ),
			moduleSlug            = '<?php echo $slug; ?>';

		$modal.appendTo( $( 'body' ) );

		registerEventHandlers();

		function registerEventHandlers() {
			$( 'a.show-license-resend-modal-' + moduleSlug ).click(function( evt ) {
				evt.preventDefault();

				showModal();
			});

			$modal.on( 'input propertychange', 'input.email-address', function() {

				var emailAddress = $( this ).val().trim();

				/**
				 * If email address is not empty, enable the send license key button.
				 */
				if ( emailAddress.length > 0 ) {
					enableSendLicenseKeyButton();
				}
			});

			$modal.on( 'blur', 'input.email-address', function() {
				var emailAddress = $( this ).val().trim();

				/**
				 * If email address is empty, disable the send license key button.
				 */
				if ( 0 === emailAddress.length ) {
					disableSendLicenseKeyButton();
				}
			});

			$modal.on( 'click', '.button', function( evt ) {
				evt.preventDefault();

				if ( $( this ).hasClass( 'disabled' ) ) {
					return;
				}

				var emailAddress = $emailAddressInput.val().trim();

				disableSendLicenseKeyButton();

				if ( 0 === emailAddress.length ) {
					return;
				}

				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						'action'       : moduleSlug + '_resend_license_key',
						'email-address': emailAddress
					},
					beforeSend: function() {
						$sendLicenseKeyButton.text( '<?php _efs( 'sending-license-key', $slug ); ?>' );
					},
					success: function( result ) {
						var resultObj = $.parseJSON( result );
						if ( resultObj.success ) {
							closeModal();
						} else {
							showError( resultObj.error );
							resetSendLicenseKeyButton();
						}
					}
				});
			});

			// If the user has clicked outside the window, close the modal.
			$modal.on( 'click', function( evt ) {
				var $target = $( evt.target );

				// If the user has clicked anywhere in the modal dialog, just return.
				if ( $target.hasClass( 'fs-modal-body' ) || $target.parents( '.fs-modal-body' ).length > 0 ) {
					return;
				}

				closeModal();
			});
		}

		function showModal() {
			resetModal();

			// Display the dialog box.
			$modal.addClass( 'active' );
			$( 'body' ).addClass( 'has-fs-modal' );

			$emailAddressInput.focus();
		}

		function closeModal() {
			$modal.removeClass( 'active' );
			$( 'body' ).removeClass( 'has-fs-modal' );
		}

		function resetSendLicenseKeyButton() {
			enableSendLicenseKeyButton();
			$sendLicenseKeyButton.text( '<?php echo $send_button_text; ?>' );
		}

		function resetModal() {
			hideError();
			resetSendLicenseKeyButton();
			$emailAddressInput.val( '' );
		}

		function enableSendLicenseKeyButton() {
			$sendLicenseKeyButton.removeClass( 'disabled' );
		}

		function disableSendLicenseKeyButton() {
			$sendLicenseKeyButton.addClass( 'disabled' );
		}

		function hideError() {
			$licenseResendMessage.hide();
		}

		function showError( msg ) {
			$licenseResendMessage.find( ' > p' ).html( msg );
			$licenseResendMessage.show();
		}
	});
})( jQuery );
</script>
