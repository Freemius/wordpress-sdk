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

        function updatePermissions(
            pluginID,
            permissions,
            isEnabled,
            success,
            failure,
            complete
        ) {
            var
                $permissionsContainer = $( '#fs_opt_out_' + pluginID + ' .fs-permissions' );

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action          : $permissionsContainer.attr('data-action'),
                    security        : $permissionsContainer.attr('data-security'),
                    module_id       : pluginID,
                    _wp_http_referer: '<?php echo Freemius::current_page_url() ?>',
                    permissions     : permissions,
                    is_enabled      : isEnabled
                },
                success: function ( resultObj ) {
                    if ( resultObj.success ) {
                        if (success) {
                            success();
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

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action          : $optOutButton.attr('data-action'),
                    security        : $optOutButton.attr('data-security'),
                    is_enabled      : isEnabled,
                    permissions     : permissions.join(','),
                    module_id       : pluginID,
                    _wp_http_referer: '<?php echo Freemius::current_page_url() ?>',
                },
                beforeSend: function() {
                    $optOutButton.text( isEnabled ?
                        '<?php fs_esc_js_echo_inline( 'Opting in', 'opting-in' ) ?>...' :
                        '<?php fs_esc_js_echo_inline( 'Opting out', 'opting-out' ) ?>...'
                    );
                },
                success: function( resultObj ) {
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
                    } else {
                        $optOutButton.text( isEnabled ?
                            '<?php fs_esc_js_echo_inline( 'Opt In', 'opt-in' ) ?>' :
                            '<?php fs_esc_js_echo_inline( 'Opt Out', 'opt-out' ) ?>'
                        );

                        if (failure) {
                            failure( resultObj );
                        }
                    }
                },
                error: function ( resultObj ) {
                    $optOutButton.text( isEnabled ?
                        '<?php fs_esc_js_echo_inline( 'Opt In', 'opt-in' ) ?>' :
                        '<?php fs_esc_js_echo_inline( 'Opt Out', 'opt-out' ) ?>'
                    );

                    if (failure) {
                        failure( resultObj );
                    }
                },
                complete: function () {
                    if (complete) {
                        complete();
                    }

                    isUpdatingPermissionGroup = false;
                }
            });
        }

        $( '.fs-permissions .fs-switch' ).on( 'click', function () {
            if (isUpdatingPermission) {
                return false;
            }

            var $switch = $(this),
                $permission = $switch.parent();

            $switch
                .toggleClass( 'fs-on' )
                .toggleClass( 'fs-off' );

            $permission.toggleClass( 'fs-disabled' );

            <?php if ( $VARS[ 'interactive' ] ) : ?>
                isUpdatingPermission = true;

                // Remove previously added feedback element.
                $switch.parents( '.fs-modal-dialog' )
                       .find( '.fs-switch-feedback' )
                       .remove();

                var $switchFeedback = $( '<span class="fs-switch-feedback"><i class="fs-ajax-spinner"></i></span>' );

                $switch.after( $switchFeedback )

                var permissionID = $permission.attr( 'id' ).substring( 'fs_permission_'.length );

                updatePermissions(
                    $switch.parents( '.fs-modal-opt-out' ).attr( 'data-plugin-id' ),
                    permissionID,
                    $switch.hasClass('fs-on'),
                    function () {
                        $switchFeedback.addClass('success');
                        $switchFeedback.html( '<i class="dashicons dashicons-yes"></i> <?php echo esc_js( fs_text_inline( 'Saved', 'saved' ) ) ?>' );
                    },
                    function () {
                        // Revert switch.
                        $switch
                            .toggleClass( 'fs-on' )
                            .toggleClass( 'fs-off' );
                    },
                    function () {
                        isUpdatingPermission = false;
                    }
                )
            <?php endif ?>
        });
<?php if ( ! $VARS[ 'inline' ] ) : ?>
    })(jQuery);
</script>
<?php endif ?>