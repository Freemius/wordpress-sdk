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
     * @var Freemius $fs
     */
    $fs = $VARS['fs'];
?>
<?php if ( ! $VARS[ 'inline' ] ) : ?>
<script type="text/javascript">
    (function ($) {
<?php endif ?>
        var isUpdatingPermission = false;

        function updatePermissions(
            permissions,
            isEnabled,
            success,
            failure,
            complete
        ) {
            var request = {
                url: ajaxurl,
                method: 'POST',
                data: {
                    action          : '<?php echo $fs->get_ajax_action( 'update_tracking_permission' ) ?>',
                    security        : '<?php echo $fs->get_ajax_security( 'update_tracking_permission' ) ?>',
                    module_id       : <?php echo $fs->get_id() ?>,
                    _wp_http_referer: '<?php echo $fs->current_page_url() ?>',
                    permissions     : permissions,
                    is_enabled      : isEnabled
                },
                success: function( resultObj ) {
                    if ( resultObj.success ) {
                        if (success) {
                            success();
                        }
                    } else {
                        if (failure) {
                            failure();
                        }
                    }
                }
            };

            if (complete) {
                request.complete = complete;
            }

            $.ajax(request);
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
                    permissionID,
                    $switch.hasClass('fs-on'),
                    function () {
                        $switchFeedback.addClass('success');
                        $switchFeedback.html( '<i class="dashicons dashicons-yes"></i> <?php echo esc_js( fs_text_inline( 'Saved', 'saved', $fs->get_slug() ) ) ?>' );
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