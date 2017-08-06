<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.1.9
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var Freemius $fs
	 */
	$fs   = freemius( $VARS['id'] );
	$slug = $fs->get_slug();

	$cant_find_license_key_text = fs_text( 'cant-find-license-key', $slug );
	$message_above_input_field  = fs_text( 'activate-license-message', $slug );
	$message_below_input_field  = '';

	$header_title = fs_text( $fs->is_free_plan() ? 'activate-license' : 'update-license', $slug );

	if ( $fs->is_registered() ) {
		$activate_button_text = $header_title;
	} else {
		$freemius_site_url = $fs->has_paid_plan() ?
			'https://freemius.com/wordpress/' :
			// Insights platform information.
			'https://freemius.com/wordpress/usage-tracking/';

		$freemius_link = '<a href="' . $freemius_site_url . '" target="_blank" tabindex="0">freemius.com</a>';

		$message_below_input_field = sprintf(
			fs_text( 'license-sync-disclaimer', $slug ),
			$fs->get_module_label( true ),
			$freemius_link
		);

		$activate_button_text = fs_text( 'agree-activate-license', $slug );
	}

	$license_key_text = fs_text( 'license-key' , $slug );

	/**
	 * IMPORTANT:
	 *  DO NOT ADD MAXLENGTH OR LIMIT THE LICENSE KEY LENGTH SINCE
	 *  WE DO WANT TO ALLOW INPUT OF LONGER KEYS (E.G. WooCommerce Keys)
	 *  FOR MIGRATED MODULES.
	 */
	$modal_content_html = <<< HTML
	<div class="notice notice-error inline license-activation-message"><p></p></div>
	<p>{$message_above_input_field}</p>
	<input class="license_key" type="text" placeholder="{$license_key_text}" tabindex="1" />
	<a class="show-license-resend-modal show-license-resend-modal-{$fs->get_unique_affix()}" href="!#" tabindex="2">{$cant_find_license_key_text}</a>
	<p>{$message_below_input_field}</p>
HTML;

	fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
?>
<script type="text/javascript">
(function( $ ) {
	$( document ).ready(function() {
		var modalContentHtml = <?php echo json_encode($modal_content_html); ?>,
			modalHtml =
				'<div class="fs-modal fs-modal-license-activation">'
				+ '	<div class="fs-modal-dialog">'
				+ '		<div class="fs-modal-header">'
				+ '		    <h4><?php echo esc_js($header_title) ?></h4>'
				+ '         <a href="!#" class="fs-close"><i class="dashicons dashicons-no" title="<?php fs_esc_attr_echo( 'dismiss', $slug ) ?>"></i></a>'
				+ '		</div>'
				+ '		<div class="fs-modal-body">'
				+ '			<div class="fs-modal-panel active">' + modalContentHtml + '</div>'
				+ '		</div>'
				+ '		<div class="fs-modal-footer">'
				+ '			<button class="button button-secondary button-close" tabindex="4"><?php fs_esc_js_echo( 'cancel', $slug ) ?></button>'
				+ '			<button class="button button-primary button-activate-license"  tabindex="3"><?php echo esc_js( $activate_button_text ) ?></button>'
				+ '		</div>'
				+ '	</div>'
				+ '</div>',
			$modal = $(modalHtml),
			$activateLicenseLink      = $('span.activate-license.<?php echo $fs->get_unique_affix() ?> a, .activate-license-trigger.<?php echo $fs->get_unique_affix() ?>'),
			$activateLicenseButton    = $modal.find('.button-activate-license'),
			$licenseKeyInput          = $modal.find('input.license_key'),
			$licenseActivationMessage = $modal.find( '.license-activation-message' );

		$modal.appendTo($('body'));

		function registerEventHandlers() {
			$activateLicenseLink.click(function (evt) {
				evt.preventDefault();

				showModal();
			});

			$modal.on('input propertychange', 'input.license_key', function () {

				var licenseKey = $(this).val().trim();

				/**
				 * If license key is not empty, enable the license activation button.
				 */
				if (licenseKey.length > 0) {
					enableActivateLicenseButton();
				}
			});

			$modal.on('blur', 'input.license_key', function () {
				var licenseKey = $(this).val().trim();

				/**
				 * If license key is empty, disable the license activation button.
				 */
				if (0 === licenseKey.length) {
					disableActivateLicenseButton();
				}
			});

			$modal.on('click', '.button-activate-license', function (evt) {
				evt.preventDefault();

				if ($(this).hasClass('disabled')) {
					return;
				}

				var licenseKey = $licenseKeyInput.val().trim();

				disableActivateLicenseButton();

				if (0 === licenseKey.length) {
					return;
				}

				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action     : '<?php echo $fs->get_ajax_action( 'activate_license' ) ?>',
						security   : '<?php echo $fs->get_ajax_security( 'activate_license' ) ?>',
						license_key: licenseKey,
						module_id  : '<?php echo $fs->get_id() ?>'
					},
					beforeSend: function () {
						$activateLicenseButton.text( <?php fs_json_encode_echo( 'activating-license', $slug ) ?> );
					},
					success: function( result ) {
						var resultObj = $.parseJSON( result );
						if ( resultObj.success ) {
							closeModal();

							// Redirect to the "Account" page and sync the license.
							window.location.href = resultObj.next_page;
						} else {
							showError( resultObj.error.message ? resultObj.error.message : resultObj.error );
							resetActivateLicenseButton();
						}
					}
				});
			});

			// If the user has clicked outside the window, close the modal.
			$modal.on('click', '.fs-close, .button-secondary', function () {
				closeModal();
				return false;
			});
		}

		registerEventHandlers();

		function showModal() {
			resetModal();

			// Display the dialog box.
			$modal.addClass('active');
			$('body').addClass('has-fs-modal');

			$licenseKeyInput.focus();
		}

		function closeModal() {
			$modal.removeClass('active');
			$('body').removeClass('has-fs-modal');
		}

		function resetActivateLicenseButton() {
			enableActivateLicenseButton();
			$activateLicenseButton.text( <?php echo json_encode( $activate_button_text ) ?> );
		}

		function resetModal() {
			hideError();
			resetActivateLicenseButton();
			$licenseKeyInput.val( '' );
		}

		function enableActivateLicenseButton() {
			$activateLicenseButton.removeClass( 'disabled' );
		}

		function disableActivateLicenseButton() {
			$activateLicenseButton.addClass( 'disabled' );
		}

		function hideError() {
			$licenseActivationMessage.hide();
		}

		function showError( msg ) {
			$licenseActivationMessage.find( ' > p' ).html( msg );
			$licenseActivationMessage.show();
		}
	});
})( jQuery );
</script>