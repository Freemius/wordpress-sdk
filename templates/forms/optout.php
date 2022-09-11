<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.2.1.5
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 * @var Freemius $fs
	 */
	$fs   = freemius( $VARS['id'] );
	$slug = $fs->get_slug();

	$action = $fs->is_tracking_allowed() ?
		'stop_tracking' :
		'allow_tracking';

	$reconnect_url = $fs->get_activation_url( array(
		'nonce'     => wp_create_nonce( $fs->get_unique_affix() . '_reconnect' ),
		'fs_action' => ( $fs->get_unique_affix() . '_reconnect' ),
	) );

	$plugin_title                   = "<strong>{$fs->get_plugin()->title}</strong>";
	$opt_out_text                   = fs_text_x_inline( 'Opt Out', 'verb', 'opt-out', $slug );
	$opt_in_text                    = fs_text_x_inline( 'Opt In', 'verb', 'opt-in', $slug );

	if ( $fs->is_premium() ) {
		$opt_in_message_appreciation = fs_text_inline( 'Connectivity to the licensing engine was successfully re-established. Automatic security & feature updates are now available through the WP Admin Dashboard.', 'premium-opt-in-message-appreciation', $slug );
	} else {
		$opt_in_message_appreciation = sprintf( fs_text_inline( 'We appreciate your help in making the %s better by letting us track some diagnostic data.', 'opt-in-message-appreciation', $slug ), $fs->get_module_type() );
	}

	$admin_notice_params = array(
		'id'      => '',
		'slug'    => $fs->get_id(),
		'type'    => 'success',
		'sticky'  => false,
		'plugin'  => $fs->get_plugin()->title,
		'message' => $opt_in_message_appreciation
	);

	$admin_notice_html = fs_get_template( 'admin-notice.php', $admin_notice_params );

    $permission_manager = FS_Permission_Manager::instance( $fs );

	fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
	fs_enqueue_local_style( 'fs_optout', '/admin/optout.css' );
	fs_enqueue_local_style( 'fs_common', '/admin/common.css' );

    if ( ! $fs->is_premium() ) {
        $optional_permissions = array( $permission_manager->get_extensions_permission( false,
            false,
            true
        ) );

        $permission_groups = array(
            array(
                'id'          => 'communication',
                'type'        => 'required',
                'title'       => $fs->get_text_inline( 'Communication', 'communication' ),
                'desc'        => '',
                'permissions' => $permission_manager->get_opt_in_required_permissions( true ),
                'is_enabled'  => $fs->is_registered(),
                'prompt'      => array(
                    sprintf( $fs->esc_html_inline( "Sharing your name and email allows us to keep you in the loop about new features and important updates, warn you about security issues before they become public knowledge, and send you special offers.",
                        'opt-out-message_user' ), $plugin_title ),
                    sprintf(
                        $fs->esc_html_inline( 'By clicking "Opt Out", %s will no longer be able to view your name and email.',
                            'opt-out-message-clicking-opt-out' ),
                        "<b>{$plugin_title}</b>"
                    ),
                ),
                'prompt_cancel_label' => $fs->get_text_inline( 'Stay Connected', 'stay-connected' )
            ),
            array(
                'id'          => 'diagnostic',
                'type'        => 'required',
                'title'       => $fs->get_text_inline( 'Diagnostic Info', 'diagnostic-info' ),
                'desc'        => '',
                'permissions' => $permission_manager->get_opt_in_diagnostic_permissions( true ),
                'is_enabled'  => $fs->is_tracking_allowed(),
                'prompt'      => array(
                    sprintf(
                        $fs->esc_html_inline( 'Sharing diagnostic data helps to provide additional functionality that\'s relevant to your website, avoid WordPress or PHP version incompatibilities that can break your website, and recognize which languages & regions the %s should be translated and tailored to.',
                            'opt-out-message-clicking-opt-out' ),
                        $fs->get_module_type()
                    ),
                    sprintf(
                        $fs->esc_html_inline( 'By clicking "Opt Out", diagnostic data will no longer be sent to %s.',
                            'opt-out-message-clicking-opt-out' ),
                        "<b>{$plugin_title}</b>"
                    ),
                ),
                'prompt_cancel_label' => $fs->get_text_inline( 'Keep Sharing', 'keep-sharing' )
            ),
            array(
                'id'          => 'extensions',
                'type'        => 'optional',
                'title'       => $fs->get_text_inline( 'Extensions', 'extensions' ),
                'desc'        => '',
                'permissions' => $optional_permissions,
            ),
        );
    } else {
        $optional_permissions = $permission_manager->get_license_optional_permissions( false, true );

        $permission_groups = array(
            array(
                'id'          => 'essentials',
                'type'        => 'required',
                'title'       => $fs->esc_html_inline( 'Required', 'required' ),
                'desc'        => sprintf( $fs->esc_html_inline( 'For delivery of security & feature updates, and license management, %s needs to',
                        'license-sync-disclaimer' ),
                        '<b>' . esc_html( $fs->get_plugin_title() ) . '</b>' ) . ':',
                'permissions' => $permission_manager->get_license_required_permissions(),
                'prompt'      => array(
                    sprintf( $fs->esc_html_inline( 'For automatic delivery of security & feature updates directly to your WordPress Admin Dashboard, %2$s needs to view the website’s homepage URL, %1$s version, SDK version, and whether the %1$s is active or uninstalled.', 'premium-opt-out-message-usage-tracking' ), $fs->get_module_type(), $plugin_title ),
                    sprintf( $fs->esc_html_inline( 'By opting out from sharing this information with the updates server, you’ll have to check for new %1$s releases and manually download & install them. Missing an update can put your site at risk, so we highly recommend keeping these essential permissions on.', 'opt-out-message-clicking-opt-out' ), $fs->get_module_type(), $plugin_title ),
                ),
                'prompt_cancel_label' => $fs->get_text_inline( 'Keep automatic updates', 'premium-opt-out-cancel' )
            ),
            array(
                'id'          => 'optional',
                'type'        => 'optional',
                'title'       => $fs->esc_html_inline( 'Optional', 'optional' ),
                'desc'        => sprintf( $fs->esc_html_inline( 'For ___ ______ short explanation of the values, you can optionally allow PluginX to view',
                        'optional-permissions-disclaimer' ),
                        '<b>' . esc_html( $fs->get_plugin_title() ) . '</b>' ) . ':',
                'permissions' => $optional_permissions,
            ),
        );
    }

    $ajax_action = 'toggle_permission_tracking';

    $form_id = "fs_opt_out_{$fs->get_id()}";
?>
<div id="<?php echo $form_id ?>"
     class="fs-modal fs-modal-opt-out"
     data-plugin-id="<?php echo $fs->get_id() ?>"
     data-action="<?php echo $fs->get_ajax_action( $ajax_action ) ?>"
     data-security="<?php echo $fs->get_ajax_security( $ajax_action ) ?>"
     style="display: none">
    <div class="fs-modal-dialog">
        <div class="fs-modal-header">
            <h4><?php echo esc_html( $opt_out_text ) ?></h4>
        </div>
        <div class="fs-opt-out-permissions">
            <div class="fs-modal-body">
                <div class="notice notice-error inline opt-out-error-message"><p></p></div>
                <div class="fs-permissions fs-open">
                <?php foreach ( $permission_groups as $i => $permission_group ) : ?>
                    <?php $permission_manager->render_permissions_group( $permission_group ) ?>
                    <?php if ( $i < count( $permission_groups ) - 1 ) : ?><hr><?php endif ?>
                <?php endforeach ?>
                </div>
            </div>
            <div class="fs-modal-footer">
                <button class="button button-primary button-close" tabindex="1"><?php echo $fs->esc_html_inline( 'Done', 'done' ) ?></button>
            </div>
        </div>
        <?php foreach ( $permission_groups as $i => $permission_group ) : ?>
            <?php if ( ! empty( $permission_group[ 'prompt' ] ) ) : ?>
                <div class="fs-<?php echo $permission_group[ 'id' ] ?>-opt-out fs-opt-out-disclaimer" data-group="<?php echo $permission_group[ 'id' ] ?>" style="display: none">
                    <div class="fs-modal-body">
                        <div class="fs-modal-panel active">
                            <div class="notice notice-error inline opt-out-error-message"><p></p></div>
                            <?php foreach ( $permission_group[ 'prompt' ] as $p ) : ?>
                                <p><?php echo $p ?></p>
                            <?php endforeach ?>
                        </div>
                    </div>
                    <div class="fs-modal-footer">
                        <a class="fs-opt-out-button" tabindex="2" href="#"><?php echo esc_html( $opt_out_text ) ?></a>
                        <button class="button button-primary" tabindex="1"><?php echo esc_html( $permission_group[ 'prompt_cancel_label' ] ) ?></button>
                    </div>
                </div>
            <?php endif ?>
        <?php endforeach ?>
    </div>
</div>

<script type="text/javascript">
	(function( $ ) {
		$( document ).ready(function() {
			var $modal              = $('#<?php echo $form_id ?>'),
                $adminNotice        = $( <?php echo json_encode( $admin_notice_html ) ?> ),
                action              = '<?php echo $action ?>',
                actionLinkSelector  = 'span.opt-in-or-opt-out.<?php echo $slug ?> a',
                //$primaryOptOutButton = $modal.find( '.fs-modal-footer .fs-opt-out-button' ),
                //$optOutErrorMessage = $modal.find( '.opt-out-error-message' ),
                $body               = $( 'body' ),
                moduleID            = '<?php echo $fs->get_id() ?>';

			$modal.data( 'action', action );

			function registerActionLinkClick() {
                $body.on( 'click', actionLinkSelector, function( evt ) {
					evt.preventDefault();

                    showModal();
					//if ( 'stop_tracking' === $modal.data( 'action' ) ) {
					//	showModal();
					//} else {
					//	optIn();
					//}

					return false;
				});
			}

            function backToPermissionsList() {
                $modal.find( '.fs-opt-out-disclaimer' )
                      .hide();

                $modal.find( '.fs-opt-out-permissions' )
                      .show();
            }

            function removeFeedbackIndicators() {
                $modal.find( '.fs-switch-feedback' )
                      .remove();
            }

			function registerEventHandlers() {
				registerActionLinkClick();

                //$primaryOptOutButton.on( 'click', function( evt ) {
				//	evt.preventDefault();
                //
				//	if ( $( this ).hasClass( 'disabled' ) ) {
				//		return;
				//	}
                //
				//	disableOptOutButton();
				//	optOut();
				//});

                $modal.on( 'click', '.fs-opt-out-disclaimer .button-primary', function ( evt ) {
                    backToPermissionsList();
                });

                var isUpdatingPermissions = false;

                $modal.on( 'click', '.fs-modal-footer .fs-opt-out-button', function ( evt ) {
                    var
                        $optOutButton     = $( this ),
                        $actionLink       = $( actionLinkSelector ),
                        isEnabled         = true,
                        $optOutDisclaimer = $( $optOutButton.parents( '.fs-opt-out-disclaimer' )[ 0 ] ),
                        groupID           = $optOutDisclaimer.attr( 'data-group' ),
                        $errorMessage     = $optOutDisclaimer.find( '.opt-out-error-message' );

                    $optOutButton.text( isEnabled ?
                        '<?php fs_esc_js_echo_inline( 'Opting out', 'opting-out', $slug ) ?>...' :
                        '<?php fs_esc_js_echo_inline( 'Opting in', 'opting-in', $slug ) ?>...'
                    );

                    hideError( $errorMessage );

                    updateGroupPermissions(
                        moduleID,
                        groupID,
                        ! isEnabled,
                        function () {
                            if ( 'communication' === groupID ) {
                                window.location.reload();
                            } else {
                                if (isEnabled) {
                                    action = 'stop_tracking';
                                    $actionLink.text('<?php echo esc_js( $opt_out_text ) ?>');
                                } else {
                                    action = 'allow_tracking';
                                    $actionLink.text('<?php echo esc_js( $opt_in_text ) ?>');
                                }

                                backToPermissionsList();

                                $modal.data('action', action);
                            }
                        },
                        function ( resultObj ) {
                            $optOutButton.text( '<?php fs_esc_js_echo_inline( 'Opt Out', 'opt-out' ) ?>' );

                            showError( $errorMessage, resultObj.error );
                        },
                        function () {
                            if ( 'communication' !== groupID ) {
                                $optOutButton.text( <?php echo json_encode( $opt_out_text ) ?> );
                            }
                        }
                    );
                } );


				// If the user has clicked outside the window, close the modal.
				$modal.on( 'click', '.fs-close, .button-close', function() {
					closeModal();
					return false;
				});
			}

			<?php if ( $fs->is_registered( true ) ) : ?>
			registerEventHandlers();
			<?php endif ?>

			function showModal() {
				resetModal();

				// Display the dialog box.
                $modal.show();
				$modal.addClass( 'active' );
				$body.addClass( 'has-fs-modal' );
			}

			function closeModal() {
				$modal.removeClass( 'active' );
				$body.removeClass( 'has-fs-modal' );
                $modal.hide();
			}

			function resetOptOutButton() {
				//enableOptOutButton();
				//$primaryOptOutButton.text( <?php //echo json_encode( $opt_out_text ) ?>// );
			}

			function resetModal() {
				hideError();
                removeFeedbackIndicators();
				resetOptOutButton();
			}

			function optIn() {
				sendRequest();
			}

			function optOut() {
				sendRequest();
			}

			function sendRequest() {
			    var
                    $actionLink = $( actionLinkSelector ),
                    isEnabled = ( 'true' === $actionLink.attr( 'data-is-enabled' ) );

				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action    : $actionLink.attr( 'data-action' ),
						security  : $actionLink.attr( 'data-security' ),
                        is_enabled: isEnabled,
						module_id : moduleID,
                        _wp_http_referer: '<?php echo Freemius::current_page_url() ?>'
					},
					beforeSend: function() {
                        $actionLink.text( isEnabled ?
                            '<?php echo esc_js( $opt_in_text ) ?>' :
                            '<?php echo esc_js( $opt_out_text ) ?>'
                        );
					},
					success: function( resultObj ) {
						if ( resultObj.success ) {
							if ( isEnabled ) {
								action = 'stop_tracking';
								$actionLink.text( '<?php echo esc_js( $opt_out_text ) ?>' );
								showOptInAppreciationMessageAndScrollToTop();
							} else {
								action = 'allow_tracking';
								$actionLink.text( '<?php echo esc_js( $opt_in_text ) ?>' );
								closeModal();

                                backToPermissionsList();

								if ( $adminNotice.length > 0 ) {
									$adminNotice.remove();
								}
							}

							$modal.data( 'action', action );
						} else {
							showError( resultObj.error );
							resetOptOutButton();
						}
					}
				});
			}

			function hideError( $optOutErrorMessage ) {
                $optOutErrorMessage = $optOutErrorMessage || $modal.find( '.opt-out-error-message' );
				$optOutErrorMessage.hide();
			}

			function showOptInAppreciationMessageAndScrollToTop() {
				$adminNotice.insertAfter( $( '#wpbody-content' ).find( ' > .wrap > h1' ) );
				window.scrollTo(0, 0);
			}

			function showError( $optOutErrorMessage, msg ) {
				$optOutErrorMessage.find( ' > p' ).html( msg );
				$optOutErrorMessage.show();
			}

			<?php if ( $fs->is_theme() ) : ?>
			/**
			 * Add opt-in/out button to the active theme's buttons collection
			 * in the theme's extended details overlay.
			 *
			 * @author Vova Feldman (@svovaf)
			 * @since 1.2.2.7
			 */
			$('.theme-overlay').contentChange(function () {
				if (0 === $('.theme-overlay.active').length) {
					// Add opt-in/out button only to the currently active theme.
					return;
				}

				if ($('#fs_theme_opt_in_out').length > 0){
					// Button already there.
					return;
				}

				var label = (('stop_tracking' == action) ?
					    '<?php echo esc_js( $opt_out_text ) ?>' :
				        '<?php echo esc_js( $opt_in_text ) ?>'),
                    href = (('stop_tracking' != action) ?
                        '<?php echo ( $fs->is_registered( true ) ? '' : esc_js( $reconnect_url ) ) ?>' :
                        '');

				var $actionLink = $('<a id="fs_theme_opt_in_out" href="' + encodeURI(href) + '" class="button">' + label + '</a>');

				actionLinkSelector = '#fs_theme_opt_in_out';

				$modal.data( 'action', action );

				$('.theme-wrap .theme-actions .active-theme').append($actionLink);

				if ('' === href) {
					registerActionLinkClick();
				}
			});
			<?php endif ?>

            <?php $permission_manager->require_permissions_js( true ) ?>
		});
	})( jQuery );
</script>
