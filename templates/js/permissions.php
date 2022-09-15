<?php
    /**
     * @package     Freemius
     * @copyright   Copyright (c) 2015, Freemius, Inc.
     * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since       2.5.1
     */
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    /**
     * @var array $VARS
     */
    $opt_out_text = fs_text_x_inline( 'Opt Out', 'verb', 'opt-out' );
    $opt_in_text  = fs_text_x_inline( 'Opt In', 'verb', 'opt-in' );

?>
<?php if ( ! $VARS[ 'inline' ] ) : ?>
    <script type="text/javascript">
        (function ($) {
            <?php endif ?>
            var isUpdatingPermission = false;

            /**
             * @param {Number} pluginID
             * @param {Array} permissions
             * @param {Boolean} isEnabled
             * @param {Callback} [success]
             * @param {Callback} [failure]
             * @param {Callback} [complete]
             */
            function updatePermissions(
                pluginID,
                permissions,
                isEnabled,
                success,
                failure,
                complete
            ) {
                var
                    $permissionsContainer = $( '#fs_opt_out_' + pluginID );

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action          : $permissionsContainer.attr('data-action'),
                        security        : $permissionsContainer.attr('data-security'),
                        module_id       : pluginID,
                        _wp_http_referer: '<?php echo Freemius::current_page_url() ?>',
                        permissions     : permissions.join(','),
                        is_enabled      : isEnabled
                    },
                    success: function ( resultObj ) {
                        if ( resultObj.success ) {
                            if (success) {
                                success( resultObj );
                            }
                        } else {
                            if (failure) {
                                failure( resultObj );
                            }
                        }
                    },
                    error: failure,
                    complete: complete
                });
            }

            function toggleGroupOptOut( $button, isEnabled ) {
                $button.text( isEnabled ?
                    '<?php echo esc_js( $opt_out_text ) ?>' :
                    '<?php echo esc_js( $opt_in_text ) ?>'
                );

                $button.attr( 'data-is-enabled', isEnabled ? 'true' : 'false' );
            }

            var isUpdatingPermissionGroup = false;

            /**
             * @param {object} $permissionsSection
             *
             * @returns {string[]}
             */
            function getGroupPermissionIDs( $permissionsSection ) {
                var permissions = [];
                $permissionsSection.find( 'ul li').each( function() {
                    permissions.push( $( this ).attr( 'data-permission-id' ) );
                });

                return permissions;
            }

            function setUpdatingLabel( $optOutButton, isEnabled ) {
                $optOutButton.text( isEnabled ?
                    '<?php fs_esc_js_echo_inline( 'Opting in', 'opting-in' ) ?>...' :
                    '<?php fs_esc_js_echo_inline( 'Opting out', 'opting-out' ) ?>...'
                );
            }

            function updateGroupPermissions(
                pluginID,
                groupID,
                isEnabled,
                success,
                failure,
                complete
            ) {
                if (isUpdatingPermissionGroup) {
                    return;
                }

                isUpdatingPermissionGroup = true;

                var
                    $modal              = $( '#fs_opt_out_' + pluginID ),
                    $permissionsSection = $modal.find( '.fs-permissions-section.fs-' + groupID + '-permissions' ),
                    $optOutButton       = $permissionsSection.find( '.fs-opt-out-button' ),
                    $permissions        = $permissionsSection.find( 'ul li'),
                    permissions         = [];

                $permissions.each( function() {
                    permissions.push( $( this ).attr( 'data-permission-id' ) );
                });

                setUpdatingLabel( $optOutButton, isEnabled );

                updatePermissions(
                    pluginID,
                    permissions,
                    isEnabled,
                    function( resultObj ) {
                        if ( resultObj.success ) {
                            toggleGroupOptOut( $optOutButton, isEnabled );

                            // Update permissions state.
                            $permissions.toggleClass( 'fs-disabled', ! isEnabled );

                            // Update switches state, if there are any.
                            $permissions.find( '.fs-switch' )
                                        .toggleClass( 'fs-on', isEnabled )
                                        .toggleClass( 'fs-off', ! isEnabled );

                            if (success) {
                                success();
                            }
                        }
                    },
                    function ( resultObj ) {
                        $optOutButton.text( isEnabled ?
                            '<?php fs_esc_js_echo_inline( 'Opt In', 'opt-in' ) ?>' :
                            '<?php fs_esc_js_echo_inline( 'Opt Out', 'opt-out' ) ?>'
                        );

                        if (failure) {
                            failure( resultObj );
                        }
                    },
                    function () {
                        if (complete) {
                            complete();
                        }

                        isUpdatingPermissionGroup = false;
                    }
                );
            }

            $( '.fs-permissions .fs-switch' ).on( 'click', function () {
                if ( isUpdatingPermission ) {
                    return false;
                }

                var $switch = $( this ),
                    $permission = $switch.parent();

                $switch
                    .toggleClass( 'fs-on' )
                    .toggleClass( 'fs-off' );

                $permission.toggleClass( 'fs-disabled' );

                var $optOutContainer = $switch.closest( '.fs-modal-opt-out' );

                if ( 0 === $optOutContainer.length ) {
                    return;
                }

                isUpdatingPermission = true;

                // Remove previously added feedback element.
                $switch.closest( '.fs-modal-dialog' )
                       .find( '.fs-switch-feedback' )
                       .remove();

                var $switchFeedback = $( '<span class="fs-switch-feedback"><i class="fs-ajax-spinner"></i></span>' );

                $switch.after( $switchFeedback )

                var
                    permissionID = $permission.attr( 'id' ).substring( 'fs_permission_'.length ),
                    isEnabled = $switch.hasClass('fs-on');

                updatePermissions(
                    $optOutContainer.attr( 'data-plugin-id' ),
                    [permissionID],
                    isEnabled,
                    function () {
                        $switchFeedback.addClass('success');
                        $switchFeedback.html( '<i class="dashicons dashicons-yes"></i> <?php echo esc_js( fs_text_inline( 'Saved', 'saved' ) ) ?>' );

                        var
                            $permissionsGroup = $switch.closest( '.fs-permissions-section' ),
                            $groupPermissions = $permissionsGroup.find('ul li');

                        var allGroupPermissionsUseSameValue = false;

                        if (
                            isEnabled &&
                            0 === $groupPermissions.filter( '.fs-disabled' ).length )
                        {
                            allGroupPermissionsUseSameValue = true;
                        } else if (
                            ! isEnabled &&
                            $groupPermissions.length === $groupPermissions.filter( '.fs-disabled' ).length
                        ) {
                            allGroupPermissionsUseSameValue = true;
                        }

                        if ( allGroupPermissionsUseSameValue ) {
                            toggleGroupOptOut( $permissionsGroup.find( '.fs-opt-out-button' ), isEnabled );
                        }
                    },
                    function () {
                        // Revert switch.
                        $switch
                            .toggleClass( 'fs-on' )
                            .toggleClass( 'fs-off' );

                        $switchFeedback.remove();
                    },
                    function () {
                        isUpdatingPermission = false;
                    }
                )
            });

            $( '.fs-opt-out-permissions .fs-opt-out-button' ).on( 'click', function ( evt ) {
                evt.preventDefault();

                if (isUpdatingPermissionGroup) {
                    return;
                }

                var
                    $optOutButton     = $( this ),
                    groupID           = $optOutButton.attr( 'data-group' ),
                    isEnabled         = ( 'true' === $optOutButton.attr( 'data-is-enabled' ) ),
                    $modal            = $optOutButton.closest( '.fs-modal-opt-out' ),
                    pluginID          = $modal.attr( 'data-plugin-id' ),
                    $optOutDisclaimer = $modal.find( '.fs-' + groupID + '-opt-out' ),
                    isConfirmRequired = ( 0 < $optOutDisclaimer.length ),
                    $errorMessage     = $modal.find( '.fs-opt-out-permissions .opt-out-error-message' );

                $errorMessage.hide();

                if ( isConfirmRequired ) {
                    if ( isEnabled ) {
                        // Move to disclaimer window.
                        $modal.find('.fs-opt-out-permissions')
                              .hide();

                        $optOutDisclaimer.show();
                    } else {
                        // Opt-in.
                        updateGroupPermissions(
                            pluginID,
                            groupID,
                            ! isEnabled,
                            ( 'communication' !== groupID ) ?
                                null :
                                function () {
                                    window.location.reload();
                                },
                            function ( resultObj ) {
                                $errorMessage.find( ' > p' ).html( resultObj.error );
                                $errorMessage.show();
                            }
                        );
                    }
                } else {
                    isUpdatingPermissionGroup = true;

                    // Remove previously added feedback element.
                    $modal.find( '.fs-switch-feedback' )
                          .remove();

                    var $switches = $optOutButton.closest( '.fs-permissions-section' )
                                                 .find( '.fs-permission .fs-switch' );

                    var switchStates = [];
                    for (var i = 0; i < $switches.length; i++) {
                        switchStates.push($($switches[i]).hasClass(
                            isEnabled ? 'fs-on' : 'fs-off'
                        ));
                    }

                    $switches
                        .removeClass( isEnabled ? 'fs-on' : 'fs-off' )
                        .addClass( isEnabled ? 'fs-off' : 'fs-on' );

                    $switches.closest( '.fs-permission' )
                             .toggleClass( 'fs-disabled', isEnabled );

                    var $switchFeedback = $( '<span class="fs-switch-feedback"><i class="fs-ajax-spinner"></i></span>' );

                    $optOutButton.after( $switchFeedback )

                    setUpdatingLabel( $optOutButton, isEnabled );

                    updatePermissions(
                        pluginID,
                        getGroupPermissionIDs( $modal.find( '.fs-permissions-section.fs-' + groupID + '-permissions' ) ),
                        ! isEnabled,
                        function () {
                            $switchFeedback.addClass( 'success' );
                            $switchFeedback.html( '<i class="dashicons dashicons-yes"></i> <?php echo esc_js( fs_text_inline( 'Saved', 'saved' ) ) ?>' );

                            toggleGroupOptOut( $optOutButton, ! isEnabled );
                        },
                        function () {
                            // Revert switches to their previous state.
                            for (var i = 0; i < switchStates.length; i++) {
                                if (switchStates[i]) {
                                    $($switches[i]).addClass( isEnabled ? 'fs-on' : 'fs-off' )
                                                   .removeClass( 'fs-disabled' )
                                                   .removeClass( isEnabled ? 'fs-off' : 'fs-on' );
                                }
                            }

                            toggleGroupOptOut( $optOutButton, isEnabled );
                        },
                        function () {
                            isUpdatingPermissionGroup = false;
                        }
                    )
                }
            });
            <?php if ( ! $VARS[ 'inline' ] ) : ?>
        })(jQuery);
    </script>
<?php endif ?>