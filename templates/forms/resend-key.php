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

	/**
	 * @var array $VARS
	 */
	$slug = $VARS['slug'];
	$fs   = freemius( $slug );

	$message_above_input_field = __fs( 'ask-for-upgrade-email-address', $slug );
	$send_button_text          = __fs( 'send-license-key', $slug );
	$cancel_button_text        = __fs( 'deactivation-modal-button-cancel', $slug );
	$email_address_placeholder = __fs( 'email-address', $slug );

	$modal_content_html = <<< HTML
	<div class="notice notice-error inline license-resend-message"><p></p></div>
	<p>{$message_above_input_field}</p>
	<div class="input-container">
		<div class="button-container">
			<a href="#" class="button button-primary button-send-license-key disabled" tabindex="2">{$send_button_text}</a>
		</div>
	    <div class="email-address-container">
	        <input class="email-address" type="text" placeholder="{$email_address_placeholder}" tabindex="1">
	    </div>
    </div>
HTML;

	fs_enqueue_local_style( 'dialog-boxes', '/admin/dialog-boxes.css' );
?>
<script type="text/javascript">
	(function ($) {
		$(document).ready(function () {
			var modalContentHtml = <?php echo json_encode( $modal_content_html ); ?>,
			    modalHtml =
				    '<div class="fs-modal fs-modal-license-key-resend">'
				    + '	<div class="fs-modal-dialog">'
				    + '		<div class="fs-modal-header">'
				    + '		    <h4><?php echo $send_button_text ?></h4>'
				    + '         <a href="#!" class="fs-close" tabindex="3" title="Close"><i class="dashicons dashicons-no" title="<?php _efs( 'dismiss' ) ?>"></i></a>'
				    + '		</div>'
				    + '		<div class="fs-modal-body">'
				    + '			<div class="fs-modal-panel active">' + modalContentHtml + '</div>'
				    + '		</div>'
				    + '	</div>'
				    + '</div>',
			    $modal = $(modalHtml),
			    $sendLicenseKeyButton = $modal.find('.button-send-license-key'),
			    $emailAddressInput = $modal.find('input.email-address'),
			    $licenseResendMessage = $modal.find('.license-resend-message'),
			    moduleSlug = '<?php echo $slug; ?>',
			    isChild = false;

			$modal.appendTo($('body'));

			registerEventHandlers();

			function registerEventHandlers() {
				$('a.show-license-resend-modal-' + moduleSlug).click(function (evt) {
					evt.preventDefault();

					showModal();
				});

				$modal.on('input propertychange', 'input.email-address', function () {

					var emailAddress = $(this).val().trim();

					/**
					 * If email address is not empty, enable the send license key button.
					 */
					if (emailAddress.length > 0) {
						enableSendLicenseKeyButton();
					}
				});

				$modal.on('blur', 'input.email-address', function () {
					var emailAddress = $(this).val().trim();

					/**
					 * If email address is empty, disable the send license key button.
					 */
					if (0 === emailAddress.length) {
						disableSendLicenseKeyButton();
					}
				});

				$modal.on('click', '.fs-close', function (){
					closeModal();
					return false;
				});

				$modal.on('click', '.button', function (evt) {
					evt.preventDefault();

					if ($(this).hasClass('disabled')) {
						return;
					}

					var emailAddress = $emailAddressInput.val().trim();

					disableSendLicenseKeyButton();

					if (0 === emailAddress.length) {
						return;
					}

					$.ajax({
						url       : ajaxurl,
						method    : 'POST',
						data      : {
							action: '<?php echo $fs->get_action_tag( 'resend_license_key' ) ?>',
							slug  : moduleSlug,
							email : emailAddress
						},
						beforeSend: function () {
							$sendLicenseKeyButton.text('<?php _efs( 'sending-license-key', $slug ) ?>...');
						},
						success   : function (result) {
							var resultObj = $.parseJSON(result);
							if (resultObj.success) {
								closeModal();
							} else {
								showError(resultObj.error);
								resetSendLicenseKeyButton();
							}
						}
					});
				});
			}

			function showModal() {
				resetModal();

				// Display the dialog box.
				$modal.addClass('active');
				$emailAddressInput.focus();

				var $body = $('body');

				isChild = $body.hasClass('has-fs-modal');
				if (isChild) {
					return;
				}

				$body.addClass('has-fs-modal');
			}

			function closeModal() {
				$modal.removeClass('active');

				// If child modal, do not remove the "has-fs-modal" class of the <body> element to keep its scrollbars hidden.
				if (isChild) {
					return;
				}

				$('body').removeClass('has-fs-modal');
			}

			function resetSendLicenseKeyButton() {
				enableSendLicenseKeyButton();
				$sendLicenseKeyButton.text('<?php echo $send_button_text; ?>');
			}

			function resetModal() {
				hideError();
				resetSendLicenseKeyButton();
				$emailAddressInput.val('');
			}

			function enableSendLicenseKeyButton() {
				$sendLicenseKeyButton.removeClass('disabled');
			}

			function disableSendLicenseKeyButton() {
				$sendLicenseKeyButton.addClass('disabled');
			}

			function hideError() {
				$licenseResendMessage.hide();
			}

			function showError(msg) {
				$licenseResendMessage.find(' > p').html(msg);
				$licenseResendMessage.show();
			}
		});
	})(jQuery);
</script>
