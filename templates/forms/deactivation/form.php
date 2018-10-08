<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.1.2
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$fs   = freemius( $VARS['id'] );
	$slug = $fs->get_slug();

	$confirmation_message = $fs->apply_filters( 'uninstall_confirmation_message', '' );

	$reasons = $VARS['reasons'];

    $subscription_cancellation_html                 = '';
    $subscription_cancellation_context              = '';
    $subscription_cancellation_confirmation_message = '';

    $has_trial = false;

    $license = ( ! fs_is_network_admin() ) ?
        $fs->_get_license() :
        null;

    if ( is_object( $license ) && ! $license->is_lifetime() && $license->is_single_site() ) {
        $subscription = $fs->_get_subscription( $license->id );

        if ( is_object( $subscription ) && $subscription->is_active() ) {
            $has_trial                         = $fs->is_paid_trial();
            $subscription_cancellation_context = $has_trial ?
                fs_text_inline( 'trial', 'trial', $slug ) :
                fs_text_inline( 'subscription', 'subscription', $slug );

            $plan         = $fs->get_plan();
            $module_label = $fs->get_module_label( true );

            $subscription_cancellation_html .= sprintf(
                '<div class="notice notice-error inline"><p></p></div><p>%s</p>',
                esc_html( sprintf(
                    fs_text_inline(
                        "Deactivating or uninstalling the %s will automatically disable the license, which you'll be able to use on another site. In case you are NOT planning on using this %s on this site (or any other site) - would you like to cancel the %s as well?",
                        'deactivation-or-uninstallation-message',
                        $slug
                    ),
                    $module_label,
                    $module_label,
                    $subscription_cancellation_context
                ) )
            );

            $cancel_subscription_action_label = sprintf(
                fs_esc_html_inline(
                    "Cancel %s - I no longer need any security & feature updates, nor support for %s because I'm not planning to use the %s on this, or any other site.",
                    'cancel-x',
                    $slug
                ),
                esc_html( $subscription_cancellation_context ),
                sprintf( '<strong>%s</strong>', esc_html( $fs->get_plugin_title() ) ),
                esc_html( $module_label )
            );

            $keep_subscription_active_action_label = esc_html( sprintf(
                fs_text_inline(
                    "Don't cancel %s - I'm still interested in getting security & feature updates, as well as be able to contact support.",
                    'dont-cancel-x',
                    $slug
                ),
                $subscription_cancellation_context
            ) );

            $subscription_cancellation_html .= <<< HTML
                <ul class="subscription-actions">
                    <li>
                        <label>
                            <input type="radio" name="cancel-subscription" value="false"/>
                            <span>{$keep_subscription_active_action_label}</span>
                        </label>
                    </li>
                    <li>
                        <label>
                            <input type="radio" name="cancel-subscription" value="true"/>
                            <span>{$cancel_subscription_action_label}</span>
                        </label>
                    </li>
                </ul>
HTML;

	        $downgrading_plan_text                      = fs_text_inline( 'Downgrading your plan', 'downgrading-plan', $slug );
	        $cancelling_subscription_text               = fs_text_inline( 'Cancelling the subscription', 'cancelling-subscription', $slug );
	        /* translators: %1s: Either 'Downgrading your plan' or 'Cancelling the subscription' */
	        $downgrade_x_confirm_text                   = fs_text_inline( '%1s will immediately stop all future recurring payments and your %s plan license will expire in %s.', 'downgrade-x-confirm', $slug );
	        $prices_increase_text                       = fs_text_inline( 'Please note that we will not be able to grandfather outdated pricing for renewals/new subscriptions after a cancellation. If you choose to renew the subscription manually in the future, after a price increase, which typically occurs once a year, you will be charged the updated price.', 'pricing-increase-warning', $slug );
	        $after_downgrade_non_blocking_text          = fs_text_inline( 'You can still enjoy all %s features but you will not have access to %s security & feature updates, nor support.', 'after-downgrade-non-blocking', $slug );
	        $after_downgrade_blocking_text              = fs_text_inline( 'Once your license expires you can still use the Free version but you will NOT have access to the %s features.', 'after-downgrade-blocking', $slug );
	        $after_downgrade_blocking_text_premium_only = fs_text_inline( 'Once your license expires you will no longer be able to use the %s, unless you activate it again with a valid premium license.', 'after-downgrade-blocking-premium-only', $slug );

            $subscription_cancellation_confirmation_message = $has_trial ?
                fs_text_inline( 'Cancelling the trial will immediately block access to all premium features. Are you sure?', 'cancel-trial-confirm', $slug ) :
                sprintf(
                    '%s %s %s %s',
                    sprintf(
                    	$downgrade_x_confirm_text,
	                    ($fs->is_only_premium() ? $cancelling_subscription_text : $downgrading_plan_text ),
	                    $plan->title,
	                    human_time_diff( time(), strtotime( $license->expiration ) )
                    ),
                    (
                        $license->is_block_features ?
                            (
                                $fs->is_only_premium() ?
                                    sprintf( $after_downgrade_blocking_text_premium_only, $module_label ) :
                                    sprintf( $after_downgrade_blocking_text, $plan->title )
                            ) :
                            sprintf( $after_downgrade_non_blocking_text, $plan->title, $fs->get_module_label( true ) )
                    ),
	                $prices_increase_text,
                    fs_esc_attr_inline( 'Are you sure you want to proceed?', 'proceed-confirmation', $slug )
                );
        }
    }

	$reasons_list_items_html = '';

	foreach ( $reasons as $reason ) {
		$list_item_classes    = 'reason' . ( ! empty( $reason['input_type'] ) ? ' has-input' : '' );

		if ( isset( $reason['internal_message'] ) && ! empty( $reason['internal_message'] ) ) {
			$list_item_classes .= ' has-internal-message';
			$reason_internal_message = $reason['internal_message'];
		} else {
			$reason_internal_message = '';
		}
		
		$reason_input_type = ( ! empty( $reason['input_type'] ) ? $reason['input_type'] : '' );
        $reason_input_placeholder = ( ! empty( $reason['input_placeholder'] ) ? $reason['input_placeholder'] : '' );
			
		$reason_list_item_html = <<< HTML
			<li class="{$list_item_classes}"
			 	data-input-type="{$reason_input_type}"
			 	data-input-placeholder="{$reason_input_placeholder}">
	            <label>
	            	<span>
	            		<input type="radio" name="selected-reason" value="{$reason['id']}"/>
                    </span>
                    <span>{$reason['text']}</span>
                </label>
                <div class="internal-message">{$reason_internal_message}</div>
            </li>
HTML;

		$reasons_list_items_html .= $reason_list_item_html;
	}

	$is_anonymous = ( ! $fs->is_registered() );
	if ( $is_anonymous ) {
		$anonymous_feedback_checkbox_html = sprintf(
			'<label class="anonymous-feedback-label"><input type="checkbox" class="anonymous-feedback-checkbox"> %s</label>',
			fs_esc_html_inline( 'Anonymous feedback', 'anonymous-feedback', $slug )
		);
	} else {
		$anonymous_feedback_checkbox_html = '';
	}

	// Aliases.
	$deactivate_text = fs_text_inline( 'Deactivate', 'deactivate', $slug );
	$theme_text      = fs_text_inline( 'Theme', 'theme', $slug );
	$activate_x_text = fs_text_inline( 'Activate %s', 'activate-x', $slug );

	$modal_classes = array();
	if ( empty( $confirmation_message ) ) {
	    $modal_classes[] = 'no-confirmation-message';
    }

	if ( ! empty( $subscription_cancellation_html ) ) {
	    $modal_classes[] = 'has-subscription-actions';
    }

	fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
?>
<script type="text/javascript">
(function ($) {
	var reasonsHtml = <?php echo json_encode( $reasons_list_items_html ) ?>,
	    modalHtml =
		    '<div class="fs-modal fs-modal-deactivation-feedback<?php echo ! empty( $modal_classes ) ? ( ' ' . implode(' ', $modal_classes ) ) : ''; ?>">'
		    + '	<div class="fs-modal-dialog">'
		    + '		<div class="fs-modal-header">'
		    + '		    <h4><?php fs_esc_attr_echo_inline( 'Quick Feedback', 'quick-feedback' , $slug ) ?></h4>'
		    + '		</div>'
		    + '		<div class="fs-modal-body">'
		    + '			<div class="fs-modal-panel" data-panel-id="confirm"><p><?php echo $confirmation_message; ?></p></div>'
		    + '			<div class="fs-modal-panel<?php echo empty( $subscription_cancellation_html ) ? ' active' : '' ?>" data-panel-id="reasons"><h3><strong><?php echo esc_js( sprintf( fs_text_inline( 'If you have a moment, please let us know why you are %s', 'deactivation-share-reason' , $slug ), ( $fs->is_plugin() ? fs_text_inline( 'deactivating', 'deactivating', $slug ) : fs_text_inline( 'switching', 'switching', $slug ) ) ) ) ?>:</strong></h3><ul id="reasons-list">' + reasonsHtml + '</ul></div>'
            + '			<div class="fs-modal-panel<?php echo ! empty( $subscription_cancellation_html ) ? ' active' : '' ?>" data-panel-id="subscription-actions">' + <?php echo json_encode( $subscription_cancellation_html ) ?> + '<p class="fs-price-increase-warning" style="display: none;">' + <?php echo json_encode( $prices_increase_text ) ?> + '</p></div>'
		    + '		</div>'
		    + '		<div class="fs-modal-footer">'
			+ '         <?php echo $anonymous_feedback_checkbox_html ?>'
		    + '			<a href="#" class="button button-secondary button-deactivate"></a>'
		    + '			<a href="#" class="button button-primary button-close"><?php fs_esc_js_echo_inline( 'Cancel', 'cancel', $slug ) ?></a>'
		    + '		</div>'
		    + '	</div>'
		    + '</div>',
	    $modal = $(modalHtml),
	    $deactivateLink = $('#the-list .deactivate > [data-module-id=<?php echo $fs->get_id() ?>].fs-module-id').prev(),
	    selectedReasonID = false,
	    redirectLink = '',
		$anonymousFeedback    = $modal.find( '.anonymous-feedback-label' ),
		isAnonymous           = <?php echo ( $is_anonymous ? 'true' : 'false' ); ?>,
		otherReasonID         = <?php echo Freemius::REASON_OTHER; ?>,
		dontShareDataReasonID = <?php echo Freemius::REASON_DONT_LIKE_TO_SHARE_MY_INFORMATION; ?>,
        deleteThemeUpdateData = <?php echo $fs->is_theme() && $fs->is_premium() && ! $fs->has_any_active_valid_license() ? 'true' : 'false' ?>,
        $errorMessage         = $modal.find( '.notice-error' );

	$modal.appendTo($('body'));

	registerEventHandlers();

	function registerEventHandlers() {
		<?php
		if ( $fs->is_plugin() ) { ?>
		$deactivateLink.click(function (evt) {
			evt.preventDefault();

			redirectLink = $(this).attr('href');

			showModal();
		});
		<?php
		/**
		 * For "theme" module type, the modal is shown when the current user clicks on
		 * the "Activate" button of any other theme. The "Activate" button is actually
		 * a link to the "Themes" page (/wp-admin/themes.php) containing query params
		 * that tell WordPress to deactivate the current theme and activate a different theme.
		 *
		 * @author Leo Fajardo (@leorw)
		 * @since 1.2.2
		 *        
		 * @since 1.2.2.7 Don't trigger the deactivation feedback form if activating the premium version of the theme.
		 */
		} else { ?>
		$('body').on('click', '.theme-browser .theme:not([data-slug=<?php echo $slug ?>-premium]) .theme-actions .button.activate', function (evt) {
			evt.preventDefault();

			redirectLink = $(this).attr('href');

			showModal();
		});
		<?php
		} ?>

		$modal.on('input propertychange', '.reason-input input', function () {
			if (!isOtherReasonSelected()) {
				return;
			}

			var reason = $(this).val().trim();

			/**
			 * If reason is not empty, remove the error-message class of the message container
			 * to change the message color back to default.
			 */
			if (reason.length > 0) {
				$('.message').removeClass('error-message');
				enableDeactivateButton();
			}
		});

		$modal.on('blur', '.reason-input input', function () {
			var $userReason = $(this);

			setTimeout(function () {
				if (!isOtherReasonSelected()) {
					return;
				}

				/**
				 * If reason is empty, add the error-message class to the message container
				 * to change the message color to red.
				 */
				if (0 === $userReason.val().trim().length) {
					$('.message').addClass('error-message');
					disableDeactivateButton();
				}
			}, 150);
		});

		$modal.on('click', '.fs-modal-footer .button', function (evt) {
			evt.preventDefault();

			if ($(this).hasClass('disabled')) {
				return;
			}

			var _parent = $(this).parents('.fs-modal:first');
			var _this = $(this);

            <?php if ( ! empty( $subscription_cancellation_html ) ) : ?>
			if ( 'subscription-actions' === getCurrentPanel() && _this.hasClass( 'button-primary' ) ) {
                if ( 'true' !== $( 'input[name="cancel-subscription"]:checked' ).val() ) {
                    setTimeout(function() {
                        showPanel( $modal.hasClass( 'no-confirmation-message' ) ? 'reasons' : 'confirm' );
                    });
                } else {
                    if ( confirm( <?php echo json_encode( $subscription_cancellation_confirmation_message ) ?> ) ) {
                        $.ajax({
                            url       : ajaxurl,
                            method    : 'POST',
                            data      : {
                                action   : '<?php echo $fs->get_ajax_action( 'cancel_subscription_or_trial' ) ?>',
                                security : '<?php echo $fs->get_ajax_security( 'cancel_subscription_or_trial' ) ?>',
                                module_id: '<?php echo $fs->get_id() ?>'
                            },
                            beforeSend: function() {
                                $errorMessage.hide();

                                _parent.find( '.fs-modal-footer .button' ).addClass( 'disabled' );
                                _parent.find( '.fs-modal-footer .button-primary' ).text( '<?php echo esc_js(
                                    sprintf( fs_text_inline( 'Cancelling %s...', 'cancelling-x' , $slug ), $subscription_cancellation_context )
                                ) ?>' );
                            },
                            success: function( result ) {
                                if ( result.success ) {
                                    _parent.removeClass( 'has-subscription-actions' );
                                    _parent.find( '.fs-modal-footer .button-primary' ).removeClass( 'warn' );

                                    setTimeout(function() {
                                        showPanel( $modal.hasClass( 'no-confirmation-message' ) ? 'reasons' : 'confirm' );
                                    });
                                } else {
                                    $errorMessage.find( '> p' ).html( result.error );
                                    $errorMessage.show();

                                    _parent.find( '.fs-modal-footer .button' ).removeClass( 'disabled' );
                                    _parent.find( '.fs-modal-footer .button-primary' ).html( <?php echo json_encode( sprintf(
                                        fs_text_inline( 'Cancel %s & Proceed', 'cancel-x-and-proceed', $slug ),
                                        ucfirst( $subscription_cancellation_context )
                                    ) ) ?> );
                                }
                            }
                        });
                    }
                }

                return;
            }
            <?php endif ?>

			if (_this.hasClass('allow-deactivate')) {
				var $radio = $('input[type="radio"]:checked');

				if (0 === $radio.length) {
				    if ( ! deleteThemeUpdateData ) {
                        // If no selected reason, just deactivate the plugin.
                        window.location.href = redirectLink;
                    } else {
                        $.ajax({
                            url       : ajaxurl,
                            method    : 'POST',
                            data      : {
                                action   : '<?php echo $fs->get_ajax_action( 'delete_theme_update_data' ) ?>',
                                security : '<?php echo $fs->get_ajax_security( 'delete_theme_update_data' ) ?>',
                                module_id: '<?php echo $fs->get_id() ?>'
                            },
                            beforeSend: function() {
                                _parent.find( '.fs-modal-footer .button' ).addClass( 'disabled' );
                                _parent.find( '.fs-modal-footer .button-secondary' ).text( 'Processing...' );
                            },
                            complete  : function() {
                                window.location.href = redirectLink;
                            }
                        });
                    }

					return;
				}

				var $selected_reason = $radio.parents('li:first'),
				    $input = $selected_reason.find('textarea, input[type="text"]'),
				    userReason = ( 0 !== $input.length ) ? $input.val().trim() : '';

				if (isOtherReasonSelected() && ( '' === userReason )) {
					return;
				}

				$.ajax({
					url       : ajaxurl,
					method    : 'POST',
					data      : {
						action      : '<?php echo $fs->get_ajax_action( 'submit_uninstall_reason' ) ?>',
						security    : '<?php echo $fs->get_ajax_security( 'submit_uninstall_reason' ) ?>',
						module_id   : '<?php echo $fs->get_id() ?>',
						reason_id   : $radio.val(),
						reason_info : userReason,
						is_anonymous: isAnonymousFeedback()
					},
					beforeSend: function () {
						_parent.find('.fs-modal-footer .button').addClass('disabled');
						_parent.find('.fs-modal-footer .button-secondary').text('Processing...');
					},
					complete  : function () {
						// Do not show the dialog box, deactivate the plugin.
						window.location.href = redirectLink;
					}
				});
			} else if (_this.hasClass('button-deactivate')) {
                if ( 'subscription-actions' === getCurrentPanel() ) {
                    closeModal();
                }

				// Change the Deactivate button's text and show the reasons panel.
				_parent.find('.button-deactivate').addClass('allow-deactivate');

				showPanel('reasons');
			}
		});

		$modal.on('click', 'input[type="radio"]', function () {
            var $selectedOption = $( this );

            <?php if ( ! empty( $subscription_cancellation_html ) ) : ?>
            if ( 'subscription-actions' === getCurrentPanel() ) {
                var $primaryButton = $modal.find( '.button-primary' ),
                    isSelected     = ( 'true' === $selectedOption.val() );

                if ( isSelected ) {
                    $primaryButton.html( <?php echo json_encode( sprintf(
                        fs_text_inline( 'Cancel %s & Proceed', 'cancel-x-and-proceed', $slug ),
                        ucfirst( $subscription_cancellation_context )
                    ) ) ?> );

	                $modal.find('.fs-price-increase-warning').show();
                } else {
                    $primaryButton.html( <?php echo fs_json_encode_echo_inline( 'Proceed', 'proceed', $slug ) ?> );
	                $modal.find('.fs-price-increase-warning').hide();
                }

                $primaryButton.toggleClass( 'warn', isSelected );
                $primaryButton.removeClass( 'disabled' );

                return;
            }
            <?php endif ?>

			// If the selection has not changed, do not proceed.
			if (selectedReasonID === $selectedOption.val())
				return;

			selectedReasonID = $selectedOption.val();

			if ( isAnonymous ) {
				if ( isReasonSelected( dontShareDataReasonID ) ) {
					$anonymousFeedback.hide();
				} else {
					$anonymousFeedback.show();
				}
			}

			var _parent = $(this).parents('li:first');

			$modal.find('.reason-input').remove();
			$modal.find( '.internal-message' ).hide();
			$modal.find('.button-deactivate').html('<?php echo esc_js( sprintf(
				fs_text_inline( 'Submit & %s', 'deactivation-modal-button-submit' , $slug ),
				$fs->is_plugin() ?
					$deactivate_text :
					sprintf( $activate_x_text, $theme_text )
			) ) ?>');

			enableDeactivateButton();

			if ( _parent.hasClass( 'has-internal-message' ) ) {
				_parent.find( '.internal-message' ).show();
			}

			if (_parent.hasClass('has-input')) {
				var inputType = _parent.data('input-type'),
				    inputPlaceholder = _parent.data('input-placeholder'),
				    reasonInputHtml = '<div class="reason-input"><span class="message"></span>' + ( ( 'textfield' === inputType ) ? '<input type="text" />' : '<textarea rows="5"></textarea>' ) + '</div>';

				_parent.append($(reasonInputHtml));
				_parent.find('input, textarea').attr('placeholder', inputPlaceholder).focus();

				if (isOtherReasonSelected()) {
					showMessage('<?php echo esc_js( fs_text_inline( 'Kindly tell us the reason so we can improve.', 'ask-for-reason-message' , $slug ) ); ?>');
					disableDeactivateButton();
				}
			}
		});

		// If the user has clicked outside the window, cancel it.
		$modal.on('click', function (evt) {
			var $target = $(evt.target);

			// If the user has clicked anywhere in the modal dialog, just return.
			if ($target.hasClass('fs-modal-body') || $target.hasClass('fs-modal-footer')) {
				return;
			}

			// If the user has not clicked the close button and the clicked element is inside the modal dialog, just return.
			if (
			    ( ! $target.hasClass( 'button-close' ) || 'subscription-actions' === getCurrentPanel() ) &&
                ( $target.parents( '.fs-modal-body' ).length > 0 || $target.parents( '.fs-modal-footer' ).length > 0 )
            ) {
				return;
			}

			closeModal();

			return false;
		});
	}

	function isAnonymousFeedback() {
		if ( ! isAnonymous ) {
			return false;
		}

		return ( isReasonSelected( dontShareDataReasonID ) || $anonymousFeedback.find( 'input' ).prop( 'checked' ) );
	}

	function isReasonSelected( reasonID ) {
		// Get the selected radio input element.
		var $selectedReasonOption = $modal.find('input[type="radio"]:checked');

		return ( reasonID == $selectedReasonOption.val() );
	}

	function isOtherReasonSelected() {
		return isReasonSelected( otherReasonID );
	}

	function showModal() {
		resetModal();

		// Display the dialog box.
		$modal.addClass('active');

		$('body').addClass('has-fs-modal');
	}

	function closeModal() {
		$modal.removeClass('active');

		$('body').removeClass('has-fs-modal');
	}

	function resetModal() {
		selectedReasonID = false;

		enableDeactivateButton();

		// Uncheck all radio buttons.
		$modal.find('input[type="radio"]').prop('checked', false);

		// Remove all input fields ( textfield, textarea ).
		$modal.find('.reason-input').remove();

		$modal.find('.message').hide();

		if ( isAnonymous ) {
			$anonymousFeedback.find( 'input' ).prop( 'checked', false );

			// Hide, since by default there is no selected reason.
			$anonymousFeedback.hide();
		}

		/*
		 * If the modal dialog has no confirmation message, that is, it has only one panel, then ensure
		 * that clicking the deactivate button will actually deactivate the plugin.
		 */
		if ( $modal.hasClass( 'has-subscription-actions' ) ) {
            showPanel( 'subscription-actions' );
        } else if ( $modal.hasClass( 'no-confirmation-message' ) ) {
			showPanel( 'reasons' );
		} else {
			showPanel( 'confirm' );
		}
	}

	function showMessage(message) {
		$modal.find('.message').text(message).show();
	}

	function enableDeactivateButton() {
		$modal.find('.button-deactivate').removeClass('disabled');
	}

	function disableDeactivateButton() {
		$modal.find('.button-deactivate').addClass('disabled');
	}

	function showPanel(panelType) {
        $errorMessage.hide();

        $modal.find( '.fs-modal-panel' ).removeClass( 'active' );
		$modal.find( '[data-panel-id="' + panelType + '"]' ).addClass( 'active' );

        var $deactivateButton = $modal.find( '.button-deactivate' ),
            $primaryButton    = $modal.find( '.button-primary' ),
            currentPanel      = getCurrentPanel();

        if ( 'subscription-actions' === currentPanel || 'confirm' === currentPanel ) {
            $deactivateButton.removeClass( 'allow-deactivate' );
        } else {
            $deactivateButton.addClass( 'allow-deactivate' );
        }

        if ( 'subscription-actions' !== currentPanel ) {
            $modal.find( '.fs-modal-header h4' ).text( <?php fs_json_encode_echo( 'quick-feedback', $slug ) ?> );
            $primaryButton.removeClass( 'disabled' );
            $deactivateButton.removeClass( 'disabled' );
        } else {
            $modal.find( '.fs-modal-header h4' ).text( <?php
                echo json_encode( sprintf(
                    fs_text_inline( 'Cancel %s?', 'cancel-x' , $slug ),
                    ucfirst( $subscription_cancellation_context )
                ) ) ?> );

            $primaryButton.removeClass( 'warn' );
            $primaryButton.addClass( 'disabled' );
        }

		updateButtonLabels();
	}

	function updateButtonLabels() {
        var $primaryButton   = $modal.find('.button-primary'),
		    $secondaryButton = $modal.find('.button-deactivate'),
            currentPanel     = getCurrentPanel();

        $primaryButton.text( <?php fs_json_encode_echo( 'cancel', $slug ) ?> );

		if ('confirm' === currentPanel) {
            $secondaryButton.text( <?php echo json_encode( sprintf(
                fs_text_inline('Yes - %s', 'deactivation-modal-button-confirm', $slug),
                $fs->is_plugin() ?
                    $deactivate_text :
                    sprintf($activate_x_text, $theme_text)
            ) ) ?> );
        } else if ( 'subscription-actions' === currentPanel ) {
            $primaryButton.text( <?php fs_json_encode_echo_inline( 'Proceed', 'proceed', $slug ) ?> );

            $secondaryButton.text( <?php fs_json_encode_echo( 'cancel', $slug ) ?> );
		} else {
			$secondaryButton.html( <?php echo json_encode( sprintf(
				fs_text_inline('Skip & %s', 'skip-and-x', $slug ),
				$fs->is_plugin() ?
					$deactivate_text :
					sprintf( $activate_x_text, $theme_text )
			) ) ?> );
		}
	}

	function getCurrentPanel() {
		return $modal.find('.fs-modal-panel.active').attr('data-panel-id');
	}
})(jQuery);
</script>
