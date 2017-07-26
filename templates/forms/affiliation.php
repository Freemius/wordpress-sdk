<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 */
	$slug = $VARS['slug'];
	/**
	 * @var Freemius $fs
	 */
	$fs = freemius( $slug );

	$user            = $fs->get_user();
	$affiliate       = $fs->get_affiliate();
	$affiliate_terms = $fs->get_affiliate_terms();

	$module_type = $fs->is_plugin() ?
        WP_FS__MODULE_TYPE_PLUGIN :
        WP_FS__MODULE_TYPE_THEME ;

	$commission = $affiliate_terms->get_formatted_commission();

    if ( ! is_object( $affiliate ) || $affiliate->is_active() ) {
        fs_enqueue_local_style( 'dialog-boxes', '/admin/dialog-boxes.css' );
    }
?>
<div id="fs_affiliation_content_wrapper" class="wrap">
    <form method="post" action="">
        <div id="poststuff">
            <div class="postbox">
                <div class="inside">
                    <div class="entry-content">
                        <?php if ( ! is_object( $affiliate ) ) : ?>
                        <div id="application_messages_container">
                            <p><?php echo sprintf( fs_text( 'become-an-ambassador', $slug ), $module_type ) ?></p>
                            <p><?php echo sprintf( fs_text( 'refer-new-customers', $slug ), $module_type, $commission ) ?></p>
                        </div>
                        <?php endif ?>
                        <h3><?php fs_echo( 'program-summary', $slug ) ?></h3>
                        <ul>
                            <li><?php echo sprintf( fs_text( 'commission-new-license', $slug ), $commission ) ?></li>
                            <li><?php fs_echo( 'unlimited-commissions', $slug ) ?></li>
                            <li><?php echo sprintf( fs_text( 'minimum-payout-amount', $slug ), $commission ) ?></li>
                            <li><?php fs_echo( 'payouts-unit-and-processing', $slug ) ?></li>
                            <li><?php fs_echo( 'commission-payment', $slug ) ?></li>
                        </ul>
                        <div id="application_form_container" style="display: none">
                            <h3><?php fs_echo( 'affiliate', $slug ) ?></h3>
                            <form>
                                <?php if ( $fs->is_registered() ) : ?>
                                    <input id="email_address" type="hidden" value="<?php echo $fs->get_user()->email ?>">
                                <?php else : ?>
                                    <div class="input-container input-container-text">
                                        <label class="input-label"><?php fs_echo( 'email-address', $slug ) ?></label>
                                        <input id="email_address" type="text">
                                    </div>
                                <?php endif ?>
                                <div class="input-container input-container-text">
                                    <label class="input-label"><?php fs_echo( 'full-name', $slug ) ?></label>
                                    <input id="full_name" type="text">
                                </div>
                                <div class="input-container input-container-text">
                                    <label class="input-label"><?php fs_echo( 'paypal-account-email-address', $slug ) ?></label>
                                    <input id="paypal_email" type="text">
                                </div>
                                <div class="input-container">
                                    <label class="input-label"><?php fs_echo( 'promotion-methods', $slug ) ?></label>
                                    <div>
                                        <input id="promotion_method_social_media" type="checkbox" value="social_media" name="promotion_methods" />
                                        <label for="promotion_method_social_media"><?php fs_echo( 'social-media', $slug ) ?></label>
                                    </div>
                                    <div>
                                        <input id="promotion_method_mobile_apps" type="checkbox" value="mobile_apps" name="promotion_methods" />
                                        <label for="promotion_method_mobile_apps"><?php fs_echo( 'mobile-apps', $slug ) ?></label>
                                    </div>
                                </div>
                                <?php if ( ! $affiliate_terms->is_any_site_allowed ) : ?>
                                <div class="input-container input-container-text">
                                    <label class="input-label"><?php fs_echo( 'where-to-promote-the-module', $slug ) ?></label>
                                    <input type="text">
                                    <a id="add_domain" href="#"><?php fs_echo( 'add-another-domain', $slug ) ?></a>
                                    <div id="additional_domains_container">

                                    </div>
                                </div>
                                <?php endif ?>
                                <div class="input-container input-container-text">
                                    <label class="input-label">Website, email, and social media statistics (optional)</label>
                                    <textarea id="statistics_information" placeholder=""></textarea>
                                    <span>Please fell free to provide any relevant website or social media statistics, e.g. monthly unique site visits, number of email subscribers, followers, etc. (we will keep this information confidential).</span>
                                </div>
                                <div class="input-container input-container-text">
                                    <label class="input-label">How will you promote us?</label>
                                    <textarea id="promotion_method_description" placeholder=""></textarea>
                                    <span>Please provide details on how you intend to promote <?php echo $fs->get_plugin_title() ?> (please be as specific as possible).</span>
                                </div>
                            </form>
                        </div>
                        <?php if ( is_object( $affiliate ) ) : ?>
                            <?php if ( $affiliate->is_pending() ) : ?>
                                <div id="thankyou_message" class="updated">
                                    <p><strong><?php fs_echo( 'affiliate-application-thank-you', $slug ) ?></strong></p>
                                </div>
                            <?php elseif ( $affiliate->is_suspended() ) : ?>
                                <div class="notice notice-warning">
                                    <p><strong><?php fs_echo( 'affiliate-account-suspended', $slug ) ?></strong></p>
                                </div>
                            <?php elseif ( $affiliate->is_rejected() ) : ?>
                                <div class="error">
                                    <p><strong><?php fs_echo( 'affiliate-application-rejected', $slug ) ?></strong></p>
                                </div>
                            <?php endif ?>
                        <?php endif ?>
                        <div id="error_message" class="error" style="display: none">
                            <p><strong></strong></p>
                        </div>
                        <div id="message" class="updated" style="display: none">
                            <p><strong></strong></p>
                        </div>
                        <a id="cancel_button" href="#" class="button button-secondary button-cancel" style="display: none"><?php fs_echo( 'cancel', $slug ) ?></a>
                        <a id="submit_button" class="button button-primary" title="<?php fs_echo( 'apply-to-become-an-affiliate', $slug ) ?>" href="#" style="display: none"><?php fs_echo( 'apply-to-become-an-affiliate', $slug ); ?></a>
                        <a id="apply_button" class="button button-primary" title="<?php fs_echo( 'become-an-affiliate', $slug ) ?>" href="#"><?php fs_echo( 'become-an-affiliate', $slug ); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <script type="text/javascript">
        jQuery(function( $ ) {
            var
                $fullName                 = $( '#full_name' ),
                $socialMedia              = $( '#promotion_method_social_media' ),
                $mobileApps               = $( '#promotion_method_mobile_apps' ),
                $applyButton              = $( '#apply_button' ),
                $submitButton             = $( '#submit_button' ),
                $cancelButton             = $( '#cancel_button' ),
                $applicationFormContainer = $( '#application_form_container' ),
                $messageContainer         = $( '#message' ),
                $errorMessageContainer    = $( '#error_message' );

            $applyButton.click(function( evt ) {
                evt.preventDefault();

                var $this = $( this );
                $this.hide();

                $applicationFormContainer.show();
                $cancelButton.show();
                $submitButton.show();

                $fullName.focus();
            });

            $submitButton.click(function( evt ) {
                evt.preventDefault();

                $errorMessageContainer.hide();

                var
                    $this              = $( this ),
                    $emailAddress      = $( '#email_address' ),
                    emailAddress       = '',
                    paypalEmailAddress = $( '#paypal_email' ).val().trim();

                if ( $this.hasClass( 'disabled' ) ) {
                    return;
                }

                if ( $emailAddress.length > 0 ) {
                    emailAddress = $emailAddress.val().trim();

                    if ( 0 === emailAddress.length ) {
                        $errorMessageContainer.find( 'strong' ).text( '<?php fs_echo( 'email-address-is-required', $slug ) ?>' );
                        $errorMessageContainer.show();
                        return;
                    }
                }

                if ( 0 === paypalEmailAddress.length ) {
                    $errorMessageContainer.find( 'strong' ).text( '<?php fs_echo( 'paypal-email-address-is-required', $slug ) ?>' );
                    $errorMessageContainer.show();
                    return;
                }

                var
                    fullName                   = $fullName.val(),
                    promotionMethods           = [],
                    statisticsInformation      = $( '#statistics_information' ).val(),
                    promotionMethodDescription = $( '#promotion_method_description' ).val();

                if ( $socialMedia.attr( 'checked' ) ) {
                    promotionMethods.push( 'social_media' );
                }

                if ( $mobileApps.attr( 'checked' ) ) {
                    promotionMethods.push( 'mobile_apps' );
                }

                $.ajax({
                    url       : ajaxurl,
                    method    : 'POST',
                    data      : {
                        action   : '<?php echo $fs->get_ajax_action( 'submit_affiliate_application' ) ?>',
                        security : '<?php echo $fs->get_ajax_security( 'submit_affiliate_application' ) ?>',
                        slug     : '<?php echo $slug ?>',
                        affiliate: {
                            full_name                   : fullName,
                            email                       : emailAddress,
                            paypal_email                : paypalEmailAddress,
                            promotion_methods           : promotionMethods.join( ',' ),
                            stats_description           : statisticsInformation,
                            promotion_method_description: promotionMethodDescription
                        }
                    },
                    beforeSend: function() {
                        $cancelButton.addClass( 'disabled' );
                        $submitButton.addClass( 'disabled' );
                        $submitButton.text( 'Processing...' );
                    },
                    success   : function( result ) {
                        if ( ! result.error ) {
                            $messageContainer.find( 'strong' ).text( '<?php fs_esc_attr_echo( 'affiliate-application-thank-you', $slug ) ?>' );
                            $messageContainer.show();

                            $cancelButton.hide();
                            $submitButton.hide();
                        } else if ( result.error.length > 0 ) {
                            $errorMessageContainer.find( 'strong' ).text( result.error );
                            $errorMessageContainer.show();
                        }
                    },
                    complete  : function() {
                        $cancelButton.removeClass( 'disabled' );
                        $submitButton.removeClass( 'disabled' );
                        $submitButton.text( '<?php fs_echo( 'apply-to-become-an-affiliate', $slug ) ?>' )
                    }
                });
            });

            function isAnonymousFeedback() {
                if ( ! isAnonymous ) {
                    return false;
                }

                return ( isReasonSelected( dontShareDataReasonID ) || $anonymousFeedback.find( 'input' ).prop( 'checked' ) );
            }

//            function showMessage(message) {
//                $modal.find('.message').text(message).show();
//            }
//
//            function enableDeactivateButton() {
//                $modal.find('.button-deactivate').removeClass('disabled');
//            }
//
//            function disableDeactivateButton() {
//                $modal.find('.button-deactivate').addClass('disabled');
//            }
//
//            function updateButtonLabels() {
//                var $deactivateButton = $modal.find('.button-deactivate');
//
//                // Reset the deactivate button's text.
//                if ('confirm' === getCurrentPanel()) {
//                    $deactivateButton.text('<?php //printf( fs_text( 'deactivation-modal-button-confirm', $slug ) ); ?>//');
//                } else {
//                    $deactivateButton.text('<?php //printf( fs_text( 'skip-deactivate', $slug ) ); ?>//');
//                }
//            }
        });
    </script>
</div>
<?php
    $params = array(
        'page'           => 'affiliation',
        'module_id'      => $fs->get_id(),
        'module_slug'    => $slug,
        'module_version' => $fs->get_plugin_version(),
    );
    fs_require_template( 'powered-by.php', $params );
?>