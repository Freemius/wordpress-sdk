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

    /**
     * @var FS_Plugin_License[] $foreign_licenses
     */
    $foreign_licenses = $VARS['foreign_licenses'];

	$change_user_message = fs_text_inline( 'By changing the user, you agree to transfer the account ownership to:', 'change-user--message', $slug );

	$header_title = fs_text_inline( 'Change User', 'change-user', $slug );

	if ( $fs->is_registered() ) {
		$user_change_button_text = fs_text_inline( 'I Agree - Change User', 'agree-change-user', $slug );
	}

	$plugin_ids   = array();
	$license_keys = array();

	foreach ( $foreign_licenses as $foreign_license ) {
	    $plugin_ids[]   = $foreign_license->plugin_id;
	    $license_keys[] = $foreign_license->secret_key;
    }

    $foreign_licenses_info = $fs->fetch_licenses_user_data( $plugin_ids, $license_keys );

    $user_change_options_html = <<< HTML
    <div class="fs-user-change-options-container">
        <table>
            <tbody>
HTML;

        $user_change_options_html .= '';

        foreach ( $foreign_licenses_info as $user_id => $foreign_license_info ) {
            $user_change_options_html .= <<< HTML
                <tr class="fs-email-address-container">
                    <td><input id="fs_email_address_{$user_id}" type="radio" name="fs_email_address" value="{$user_id}"></td>
                    <td><label for="fs_email_address_{$user_id}">{$foreign_license_info->user_email}</label></td>
                </tr>
HTML;
        }

        $user_change_options_html .= <<< HTML
                <tr class="fs-other-email-address-container-row">
                    <td><input id="fs_other_email_address_radio" type="radio" name="fs_email_address" value="other"></td>
                    <td class="fs-other-email-address-container">
                        <div>
                            <label for="fs_email_address">Other: </label>
                            <div>
                                <input id="fs_other_email_address_text_field" class="fs-email-address" type="text" placeholder="Enter email address" tabindex="1">
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
		var modalContentHtml            = <?php echo json_encode( $modal_content_html ) ?>,
			modalHtml                   =
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
			$modal                      = $( modalHtml ),
			$userChangeButton           = $modal.find( '.fs-button-change-user' ),
			$otherEmailAddressRadio     = $modal.find( '#fs_other_email_address_radio' ),
			$changeUserResultMessage    = $modal.find( '.fs-change-user-result-message' ),
            $otherEmailAddressTextField = $modal.find( '#fs_other_email_address_text_field' ),
            $licenseOwners              = $modal.find( 'input[type="radio"][name="fs_email_address"]' );

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
            },
            /**
             * @author Leo Fajardo (@leorw)
             * @since 2.3.1
             */
            setLoadingMode = function () {
                $( document.body ).css( { 'cursor': 'wait' } );
            };

		function registerEventHandlers() {
            var $otherEmailAddressContainer = $modal.find( '.fs-other-email-address-container' );

            $licenseOwners.change(function() {
                var otherEmailAddress           = $otherEmailAddressTextField.val().trim(),
                    otherEmailAddressIsSelected = isOtherEmailAddressSelected();

                if ( otherEmailAddressIsSelected ) {
                    setTimeout(function() {
                        $otherEmailAddressTextField.focus();
                    });
                }

                if ( otherEmailAddress.length > 0 || ! otherEmailAddressIsSelected ) {
                    enableUserChangeButton();
                } else {
                    disableUserChangeButton();
                }
            });

            $otherEmailAddressContainer.click(function() {
                $otherEmailAddressRadio.click();
            });

            $( '#fs_change_user' ).click( function (evt) {
				evt.preventDefault();

				showModal( evt );
			} );

            /**
             * Disable the user change button when the email address is empty.
             *
             * @author Leo Fajardo (@leorw)
             * @since 2.3.1
             */
            $modal.on( 'keyup paste delete cut', 'input#fs_other_email_address_text_field', function () {
                setTimeout( function () {
                    var emailAddress = $otherEmailAddressRadio.val().trim();

                    if ( emailAddress === previousEmailAddress ) {
                        return;
                    }

                    if ( '' === emailAddress ) {
                        disableUserChangeButton();
                    } else {
                        enableUserChangeButton();
                    }

                    previousEmailAddress = emailAddress;
                }, 100 );
            } ).focus();

			$modal.on( 'input propertychange', 'input#fs_other_email_address_text_field', function () {
				var emailAddress = $( this ).val().trim();

				/**
                 * If email address is not empty, enable the user change button.
				 */
				if ( emailAddress.length > 0 ) {
					enableUserChangeButton();
				}
			});

			$modal.on( 'blur', 'input#fs_other_email_address_text_field', function( evt ) {
				var
                    emailAddress            = $( this ).val().trim(),
                    $focusedElement         = $( evt.relatedTarget ),
                    hasSelectedLicenseOwner = ( $focusedElement.parents( '.fs-email-address-container' ).length > 0 );

                /**
                 * If email address is empty, disable the user change button.
                 */
                if ( 0 === emailAddress.length || ! hasSelectedLicenseOwner ) {
                   disableUserChangeButton();
                }
			});

			$modal.on( 'click', '.fs-button-change-user', function ( evt ) {
				evt.preventDefault();

				if ( $( this ).hasClass( 'disabled' ) ) {
					return;
				}

				var emailAddress   = '',
                    licenseOwnerID = null;

                if ( isOtherEmailAddressSelected() ) {
                    emailAddress = $otherEmailAddressTextField.val().trim();

                    if (0 === emailAddress.length) {
                        return;
                    }
                } else {
                    licenseOwnerID = $licenseOwners.filter( ':checked' ).val();
                }

				disableUserChangeButton();

                var data = {
                    action           : '<?php echo $fs->get_ajax_action( 'change_user' ) ?>',
                    security         : '<?php echo $fs->get_ajax_security( 'change_user' ) ?>',
                    new_email_address: emailAddress,
                    new_user_id      : licenseOwnerID,
                    module_id        : '<?php echo $fs->get_id() ?>'
                };

				$.ajax( {
					url: ajaxurl,
					method: 'POST',
                    data: data,
					beforeSend: function () {
						$userChangeButton.text( '<?php fs_esc_js_echo_inline( 'Processing', 'processing', $slug ) ?>...' );
					},
					success: function( result ) {
						if ( result.success ) {
							closeModal();

							// Redirect to the "Account" page.
							window.location.reload();
						} else {
							showError( result.error.message ? result.error.message : result.error );
							resetUserChangeButton();
						}
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

        /**
         * @author Leo Fajardo (@leorw)
         * @since 2.3.1
         *
         * @returns {Boolean}
         */
        function isOtherEmailAddressSelected() {
            return ( 'other' === $licenseOwners.filter( ':checked' ).val() );
        }

		function showModal() {
			resetModal();

			// Display the dialog box.
			$modal.addClass( 'active' );
			$( 'body' ).addClass( 'has-fs-modal' );

            $licenseOwners.attr( 'checked', false );
            $licenseOwners.get( 0 ).click();

            $otherEmailAddressTextField.val( '' );
		}

		function closeModal() {
			$modal.removeClass( 'active' );
			$( 'body' ).removeClass( 'has-fs-modal' );
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
