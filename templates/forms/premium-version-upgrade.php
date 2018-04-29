<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       2.0.2
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var Freemius $fs
	 */
	$fs   = freemius( $VARS['id'] );
	$slug = $fs->get_slug();

	$plugin_data     = $fs->get_plugin_data();
    $plugin_name     = $plugin_data['Name'];
    $plugin_basename = $fs->get_plugin_basename();

    $message = sprintf(
        fs_text_inline( 'There is a new version of %s available.', 'new-version-available-message', $slug ) .
        fs_text_inline( ' %sRenew your license now%s to access version %s features and support.', 'renew-license-now', $slug ),
        $plugin_name,
        '<a href="' . $fs->pricing_url() . '">',
        '</a>',
        $VARS['new_version']
    );

    $modal_content_html = "<p>{$message}</p>";

    $header_title = fs_text_inline( 'New Version Available', 'new-version-available', $slug );

    $renew_license_button_text = fs_text_inline( 'Renew license', 'renew-license', $slug );

    fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
?>
<script type="text/javascript">
(function( $ ) {
	$( document ).ready(function() {
		var modalContentHtml = <?php echo json_encode( $modal_content_html ) ?>,
			modalHtml =
				'<div class="fs-modal fs-modal-upgrade-premium-version">'
				+ '	<div class="fs-modal-dialog">'
				+ '		<div class="fs-modal-header">'
				+ '		    <h4><?php echo esc_js( $header_title ) ?></h4>'
				+ '         <a href="!#" class="fs-close"><i class="dashicons dashicons-no" title="<?php echo esc_js( fs_text_x_inline( 'Dismiss', 'close a window', 'dismiss', $slug ) ) ?>"></i></a>'
				+ '		</div>'
				+ '		<div class="fs-modal-body">'
				+ '			<div class="fs-modal-panel active">' + modalContentHtml + '</div>'
				+ '		</div>'
				+ '		<div class="fs-modal-footer">'
				+ '			<a class="button button-primary button-renew-license" tabindex="3" href="<?php echo $fs->pricing_url() ?>"><?php echo esc_js( $renew_license_button_text ) ?></a>'
                + '			<button class="button button-secondary button-close" tabindex="4"><?php fs_esc_js_echo_inline( 'Cancel', 'cancel', $slug ) ?></button>'
				+ '		</div>'
				+ '	</div>'
				+ '</div>',
			$modal = $( modalHtml ),
			$upgradePremiumVersionCheckbox = $( 'input[type="checkbox"][value="<?php echo $plugin_basename ?>"]' ),
            isPluginsPage = <?php echo Freemius::is_plugins_page() ? 'true' : 'false' ?>;

		$modal.appendTo( $( 'body' ) );

		function registerEventHandlers() {
            $upgradePremiumVersionCheckbox.click(function( evt ) {
				evt.preventDefault();

				showModal( evt );
			});

			// If the user has clicked outside the window, close the modal.
			$modal.on( 'click', '.fs-close, .button-secondary', function() {
				closeModal();
				return false;
			});

			if ( isPluginsPage ) {
                $( 'body' ).on( 'change', 'select[id*="bulk-action-selector"]', function() {
                    if ( 'update-selected' === $( this ).val() ) {
                        var module = $( '.wp-list-table.plugins' ).find( 'input[type="checkbox"][value="<?php echo $plugin_basename ?>"]' );
                        if ( module.length > 0 ) {
                            setTimeout(function() {
                                module.prop( 'checked', false );
                            }, 1);
                        }
                    }
                });
            }

			$( 'body' ).on( 'click', '[id*="select-all"]', function() {
                var
                    parent = $( this ).parents( 'table:first' ),
                    module = parent.find( 'input[type="checkbox"][value="<?php echo $plugin_basename ?>"]' );

                if ( 0 === module.length > 0 ) {
                    return true;
                }

                if ( isPluginsPage ) {
                    if ( 'update-selected' !== $( '#bulk-action-selector-top' ).val() &&
                        'update-selected' !== $( '#bulk-action-selector-bottom' ).val() ) {
                        return true;
                    }
                }

                if ( module.length > 0 ) {
                    setTimeout(function() {
                        module.prop( 'checked', false );
                    }, 1);
                }
            });
		}

		registerEventHandlers();

		function showModal() {
			// Display the dialog box.
			$modal.addClass( 'active' );
			$( 'body' ).addClass( 'has-fs-modal' );
		}

		function closeModal() {
			$modal.removeClass( 'active' );
			$( 'body' ).removeClass( 'has-fs-modal' );
		}
	});
})( jQuery );
</script>