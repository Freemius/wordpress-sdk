<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.1.9
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$slug = $VARS['slug'];
	$fs   = freemius( $slug );

	$message_above_input_field = __fs( 'activate-license-message', $slug );
	$message_below_input_field = '';

	if ( $fs->is_registered() ) {
		$activate_button_text = __fs( $fs->is_free_plan() ? 'activate-license' : 'update-license', $slug );
	} else {
		$freemius_site_url = $fs->has_paid_plan() ?
			'https://freemius.com/wordpress/' :
			// Insights platform information.
			'https://freemius.com/wordpress/usage-tracking/';

		$freemius_link = '<a href="' . $freemius_site_url . '" target="_blank">freemius.com</a>';

		$message_below_input_field = sprintf( __fs( 'license-sync-disclaimer', $slug ), $freemius_link );

		$activate_button_text = __fs( 'agree-activate-license', $slug );
	}

	$license_key_text = __fs(  'license-key' , $slug );

	$modal_content_html = <<< HTML
	<p>{$message_above_input_field}</p>
	<input class="license_key" type="text" placeholder="{$license_key_text}" />
	<p>{$message_below_input_field}</p>
HTML;
?>
<script type="text/javascript">
(function( $ ) {
	$( document ).ready(function() {
		var modalContentHtml = <?php echo json_encode($modal_content_html); ?>,
			modalHtml =
				'<div class="fs-modal">'
				+ '	<div class="fs-modal-dialog">'
				+ '		<div class="fs-modal-body">'
				+ '			<div class="fs-modal-panel active">' + modalContentHtml + '</div>'
				+ '		</div>'
				+ '		<div class="fs-modal-footer">'
				+ '			<a href="#" class="button button-secondary button-close"><?php _efs('deactivation-modal-button-cancel', $slug); ?></a>'
				+ '			<a href="#" class="button button-primary button-activate-license"><?php echo $activate_button_text; ?></a>'
				+ '		</div>'
				+ '	</div>'
				+ '</div>',
			$modal = $(modalHtml),
			$activateLicenseLink = $('span.activate-license.<?php echo $VARS['slug']; ?>').find('a');

		$modal.appendTo($('body'));

		registerEventHandlers();

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

			$modal.on('click', '.button', function (evt) {
				evt.preventDefault();

				if ($(this).hasClass('disabled')) {
					return;
				}

				var
					activateButton = $('.button-activate-license'),
					licenseKey = $('input.license_key').val().trim();

				activateButton.addClass('disabled');

				if (0 === licenseKey.length) {
					return;
				}

				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						'action': 'activate-license',
						'license-key': licenseKey
					},
					beforeSend: function () {
						activateButton.text('<?php _efs('activating-license', $slug); ?>');
					},
					complete: function () {
						closeModal();
					}
				});
			});

			// If the user has clicked outside the window, close the modal.
			$modal.on('click', function (evt) {
				var $target = $(evt.target);

				// If the user has clicked anywhere in the modal dialog, just return.
				if ($target.hasClass('fs-modal-body') || $target.hasClass('fs-modal-footer')) {
					return;
				}

				// If the user has not clicked the close button and the clicked element is inside the modal dialog, just return.
				if (( !$target.hasClass('button-close') ) && ( $target.parents('.fs-modal-body').length > 0 || $target.parents('.fs-modal-footer').length > 0 )) {
					return;
				}

				closeModal();
			});
		}

		function showModal() {
			resetModal();

			// Display the dialog box.
			$modal.addClass('active');
			$('body').addClass('has-fs-modal');

			$('input.license_key').focus();
		}

		function closeModal() {
			$modal.removeClass('active');
			$('body').removeClass('has-fs-modal');
		}

		function resetModal() {
			$('input.license_key').val('');

			enableActivateLicenseButton();
			$modal.find('.button-activate-license').text('<?php echo $activate_button_text; ?>');
		}

		function enableActivateLicenseButton() {
			$modal.find('.button-activate-license').removeClass('disabled');
		}

		function disableActivateLicenseButton() {
			$modal.find('.button-activate-license').addClass('disabled');
		}
	});
})( jQuery );
</script>
