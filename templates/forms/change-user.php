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

	$change_user_message = fs_text_inline( 'By changing the user, you agree to transfer the account ownership to:', 'change-user--message', $slug );

	$header_title = fs_text_inline( 'Change User', 'change-user', $slug );

	if ( $fs->is_registered() ) {
		$user_change_button_text = fs_text_inline( 'I Agree - Change User', 'agree-change-user', $slug );
	}

    $foreign_licenses_info = $fs->get_foreign_licenses_data();

    $user_change_options_html = <<< HTML
    <div class="fs-user-change-options-container">
        <table>
            <tbody>
HTML;

        $user_change_options_html .= '';

        foreach ( $foreign_licenses_info as $foreign_license_info ) {
            $user_change_options_html .= <<< HTML
                <tr class="fs-email-address-container">
                    <td><input id="fs_email_address_{$foreign_license_info->user_id}" type="radio" name="fs_email_address" value="{$foreign_license_info->user_id}"></td>
                    <td><label for="fs_email_address_{$foreign_license_info->user_id}">{$foreign_license_info->owner_email}</label></td>
                </tr>
HTML;
        }

        $user_change_options_html .= <<< HTML
                <tr class="fs-other-email-address-container-row">
                    <td><input id="fs_email_address" type="radio" name="fs_email_address" value="other"></td>
                    <td class="fs-other-email-address-container">
                        <div>
                            <label for="fs_email_address">Other: </label>
                            <div>
                                <input id="fs_other_email_address" class="fs-email-address" type="text" placeholder="Enter email address" tabindex="1">
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
HTML;

	$modal_content_html = <<< HTML
	<div class="notice notice-error inline fs-change-user-result-message"><p></p></div>
	<p>{$change_user_message}</p>
	{$user_change_options_html}
HTML;

	fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
?>
<script type="text/javascript">
(function( $ ) {
	$( document ).ready(function() {
		var modalContentHtml = <?php echo json_encode( $modal_content_html ) ?>,
			modalHtml =
				'<div class="fs-modal fs-modal-change-user fs-modal-change-user-<?php echo $unique_affix ?>">'
				+ '	<div class="fs-modal-dialog">'
				+ '		<div class="fs-modal-header">'
				+ '		    <h4><?php echo esc_js( $header_title ) ?></h4>'
				+ '         <a href="!#" class="fs-close"><i class="dashicons dashicons-no" title="<?php echo esc_js( fs_text_x_inline( 'Dismiss', 'close window', 'dismiss', $slug ) ) ?>"></i></a>'
				+ '		</div>'
				+ '		<div class="fs-modal-body">'
				+ '			<div class="fs-modal-panel active">' + modalContentHtml + '</div>'
				+ '		</div>'
				+ '		<div class="fs-modal-footer">'
				+ '			<button class="button button-secondary button-close" tabindex="4"><?php fs_esc_js_echo_inline( 'Cancel', 'cancel', $slug ) ?></button>'
				+ '			<button class="button button-primary fs-button-change-user" tabindex="3"><?php echo esc_js( $user_change_button_text ) ?></button>'
				+ '		</div>'
				+ '	</div>'
				+ '</div>',
			$modal = $( modalHtml ),
			$userChangeButton        = $modal.find( '.fs-button-change-user' ),
			$emailAddressInput       = $modal.find( 'input#fs_email_address' ),
			$changeUserResultMessage = $modal.find( '.fs-change-user-result-message' ),
            $otherEmailAddress       = $modal.find( '#fs_other_email_address' );

        $modal.appendTo($('body'));

        var previousEmailAddress = null,
            /**
             * @author Leo Fajardo (@leorw)
             * @since 2.3.1
             */
            resetLoadingMode = function() {
                // Reset loading mode.
                $userChangeButton.text( <?php echo json_encode( $user_change_button_text ) ?> );
                $userChangeButton.prop( 'disabled', false );
                $( document.body ).css( { 'cursor': 'auto' } );
                $( '.fs-loading' ).removeClass( 'fs-loading' );

                console.log( 'resetLoadingMode - Primary button was enabled' );
            };

		function registerEventHandlers() {
            $( '#fs_change_user' ).click(function (evt) {
				evt.preventDefault();

				showModal( evt );
			});

			$modal.on( 'input propertychange', 'input.fs-email-address', function () {
				var emailAddress = $( this ).val().trim();

				if ( emailAddress.length > 0 ) {
					enableUserChangeButton();
				}
			});

			$modal.on( 'blur', 'input.fs-email-address', function( evt ) {
				var emailAddress = $( this ).val().trim();

                if ( 0 === emailAddress.length ) {
                   disableUserChangeButton();
                }
			});

			$modal.on( 'click', '.button-change-user', function ( evt ) {
				evt.preventDefault();

				if ( $( this ).hasClass( 'disabled' ) ) {
					return;
				}

				disableUserChangeButton();
			});

			// If the user has clicked outside the window, close the modal.
			$modal.on('click', '.fs-close, .button-secondary', function () {
				closeModal();
				return false;
			});
		}

		registerEventHandlers();

		function showModal( evt ) {
			resetModal();

			// Display the dialog box.
			$modal.addClass( 'active' );
			$( 'body' ).addClass( 'has-fs-modal' );
		}

		function closeModal() {
			$modal.removeClass('active');
			$('body').removeClass('has-fs-modal');
		}

		function resetUserChangeButton() {
			enableUserChangeButton();
			$userChangeButton.text( <?php echo json_encode( $user_change_button_text ) ?> );
		}

		function resetModal() {
			hideError();
			resetUserChangeButton();
		}

		function enableUserChangeButton() {
			$userChangeButton.removeClass( 'disabled' );
		}

		function disableUserChangeButton() {
			$userChangeButton.addClass( 'disabled' );
		}

		function hideError() {
			$changeUserResultMessage.hide();
		}

		function showError( msg ) {
            $changeUserResultMessage.find( ' > p' ).html( msg );
            $changeUserResultMessage.show();
		}
	});
})( jQuery );
</script>
