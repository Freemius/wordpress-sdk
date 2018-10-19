<?php
/**
 * @package     Freemius
 * @copyright   Copyright (c) 2015, Freemius, Inc.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
 * @since       2.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @var array $VARS
 */
$fs   = freemius( $VARS['id'] );
$slug = $fs->get_slug();

$confirmation_message = $fs->apply_filters( 'uninstall_confirmation_message', '' );

$subscription_cancellation_html                 = '';
$subscription_cancellation_context              = '';
$subscription_cancellation_confirmation_message = '';

$prices_increase_text = '';

$has_trial = false;

if ((!fs_is_network_admin())) {
    $license = $fs->_get_license();
} else {
    $license = null;
}

/**
 * If the installation is associated with a non-lifetime license, which is either a single-site or only activated on a single production site (or zero), and connected to an active subscription, suggest the customer to cancel the subscription upon deactivation.
 *
 * @author Leo Fajardo (@leorw) (Comment added by Vova Feldman @svovaf)
 * @since 2.2.0
 */
if ( is_object( $license ) &&
    ! $license->is_lifetime() &&
    ( $license->is_single_site() || 1 >= $license->activated )
) {
    $subscription = $fs->_get_subscription( $license->id );

    if ( is_object( $subscription ) && $subscription->is_active() ) {
        $has_trial = $fs->is_paid_trial();

        $subscription_cancellation_context = $has_trial ?
            fs_text_inline( 'trial', 'trial', $slug ) :
            fs_text_inline( 'subscription', 'subscription', $slug );

        $plan         = $fs->get_plan();
        $module_label = $fs->get_module_label( true );

        $subscription_cancellation_html .= sprintf(
            '<div class="notice notice-error inline"><p></p></div><p>%s</p>',
            esc_html( sprintf(
                (
                    Freemius::is_plugins_page() ?
                        sprintf(
                            fs_text_inline( "Deactivating or uninstalling the %s will automatically disable the license, which you'll be able to use on another site.", 'deactivation-or-uninstall-message', $slug ),
                            $module_label
                        ) :
                        fs_text_inline( 'Deactivating your license will block all premium features, but will enable activating the license on another site.', 'deactivating-license', $slug )
                ) . ' ' .
                fs_text_inline(
                    "In case you are NOT planning on using this %s on this site (or any other site) - would you like to cancel the %s as well?",
                    'cancel-x-confirm',
                    $slug
                ),
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

$title = '';
if ( ! empty( $subscription_cancellation_html ) ) {
    $title = sprintf(
        fs_esc_attr_inline( 'Cancel %s?', 'cancel-x' , $slug ),
        ucfirst( $subscription_cancellation_context )
    );
} else if ( is_object( $license ) && ! Freemius::is_plugins_page() ) {
    $title = fs_esc_attr_inline( 'Deactivate license?', 'deactivate-license', $slug );

    $subscription_cancellation_html = sprintf(
        '<p>%s</p>',
        fs_text_inline( 'Deactivating your license will block all premium features, but will enable activating the license on another site. Are you sure you want to proceed?', 'deactivate-license-confirm', $slug )
    );
}

if ( empty( $subscription_cancellation_html ) ) {
    return;
}

fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
?>
<script type="text/javascript">
    (function($) {
        var modalHtml =
                '<div class="fs-modal fs-modal-subscription-cancellation">'
                + '	<div class="fs-modal-dialog">'
                + '		<div class="fs-modal-header">'
                + '		    <h4><?php echo $title ?></h4>'
                + '		</div>'
                + '		<div class="fs-modal-body">'
                + '			<div class="fs-modal-panel active">' + <?php echo json_encode( $subscription_cancellation_html ) ?> + '<p class="fs-price-increase-warning" style="display: none;">' + <?php echo json_encode( $prices_increase_text ) ?> + '</p></div>'
                + '		</div>'
                + '		<div class="fs-modal-footer">'
                + '			<a href="#" class="button button-secondary button-close"></a>'
                + '			<a href="#" class="button button-primary button-deactivate disabled"></a>'
                + '		</div>'
                + '	</div>'
                + '</div>',
            $modal = $(modalHtml),
            $deactivateLink = null,
            redirectLink = '',
            $errorMessage         = $modal.find( '.notice-error' );

        <?php if ( Freemius::is_plugins_page() ) : ?>
            $deactivateLink = $( '#the-list .deactivate > [data-module-id=<?php echo $fs->get_id() ?>].fs-module-id' ).prev();
        <?php else : ?>
            $deactivateLink = $( '.fs-deactivate-license' );
        <?php endif ?>

        $modal.appendTo($('body'));

        registerEventHandlers();

        function registerEventHandlers() {
            <?php
            if ( $fs->is_plugin() ) { ?>
            $deactivateLink.click(function (evt) {
                if ( $modal.is( ':visible' ) ) {
                    return true;
                }

                evt.preventDefault();
                evt.stopImmediatePropagation();

                redirectLink = $(this).attr('href');

                showModal();
            });
            <?php } ?>

            $modal.on('click', '.fs-modal-footer .button', function (evt) {
                evt.preventDefault();

                if ($(this).hasClass('disabled')) {
                    return;
                }

                var _parent = $(this).parents('.fs-modal:first');
                var _this = $(this);

                if ( _this.hasClass( 'button-primary' ) ) {
                    if ( 'true' !== $( 'input[name="cancel-subscription"]:checked' ).val() ) {
                        <?php if ( Freemius::is_plugins_page() ) : ?>
                        $deactivateLink.click();
                        $modal.removeClass('active');
                        <?php else : ?>
                        setLoading( $modal.find('.button-deactivate'), '<?php fs_esc_js_echo_inline('Deactivating', 'deactivating' ) ?>...' );
                        setLoading( $deactivateLink, '<?php fs_esc_js_echo_inline('Deactivating', 'deactivating' ) ?>...' );
                        $deactivateLink.parent().submit();
                        <?php endif ?>
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

                                        <?php if ( Freemius::is_plugins_page() ) : ?>
                                            $deactivateLink.click();
                                            $modal.removeClass('active');
                                        <?php else : ?>
                                            setLoading( $deactivateLink, '<?php fs_esc_js_echo_inline('Deactivating', 'deactivating' ) ?>...' );
                                            $deactivateLink.parent().submit();
                                        <?php endif ?>
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
                }
            });

            $modal.on('click', 'input[type="radio"]', function () {
                var
                    $selectedOption = $( this ),
                    $primaryButton  = $modal.find( '.button-primary' ),
                    isSelected      = ( 'true' === $selectedOption.val() );

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
                    ( ! $target.hasClass( 'button-close' ) ) &&
                    ( $target.parents( '.fs-modal-body' ).length > 0 || $target.parents( '.fs-modal-footer' ).length > 0 )
                ) {
                    return;
                }

                closeModal();

                return false;
            });
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
            updateButtonLabels();

            if ( 0 === $modal.find( '.subscription-actions' ).length ) {
                $modal.find('.button-deactivate').removeClass('disabled');
            } else {
                $modal.find('.button-deactivate').addClass('disabled');
            }

            $modal.find('.fs-price-increase-warning').hide();

            // Uncheck all radio buttons.
            $modal.find('input[type="radio"]').prop('checked', false);

            $modal.find('.message').hide();
        }

        function showMessage(message) {
            $modal.find('.message').text(message).show();
        }

        function updateButtonLabels() {
            $modal.find('.button-primary').text( <?php fs_json_encode_echo_inline( 'Proceed', 'proceed', $slug ) ?> );

            $modal.find('.button-secondary').text( <?php fs_json_encode_echo( 'cancel', $slug ) ?> );
        }
    })( jQuery );
</script>
