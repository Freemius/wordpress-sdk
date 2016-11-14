<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.2.2
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 */
	$slug = $VARS['slug'];
	$fs   = freemius( $slug );

	$plugin_title                     = "<strong>{$fs->get_plugin()->title}</strong>";
	$opt_out_button_text              = ucfirst( strtolower( __fs( 'opt-out', $slug ) ) );
    $opt_out_message_appreciation     = __fs( 'opt-out-message-appreciation', $slug );
    $opt_out_message_usage_tracking   = sprintf( __fs( 'opt-out-message-usage-tracking', $slug ),
													$plugin_title );
    $opt_out_message_clicking_opt_out = sprintf( __fs( 'opt-out-message-clicking-opt-out', $slug ),
		                                            $plugin_title,
													'<a href="http://freemius.com" target="_blank">freemius.com</a>' );

	$modal_content_html = <<< HTML
		<h2><strong>{$opt_out_message_appreciation}</strong></h2>
		<div class="notice notice-error inline opt-out-error-message"><p></p></div>
		<p>{$opt_out_message_usage_tracking}</p>
		<p>{$opt_out_message_clicking_opt_out}</p>
HTML;

	fs_enqueue_local_style( 'dialog-boxes', '/admin/dialog-boxes.css' );
?>
<script type="text/javascript">
(function( $ ) {
	$( document ).ready(function() {
		var modalContentHtml = <?php echo json_encode( $modal_content_html ) ?>,
			modalHtml =
				'<div class="fs-modal fs-modal-opt-out">'
				+ '	<div class="fs-modal-dialog">'
				+ '		<div class="fs-modal-body">'
				+ '			<div class="fs-modal-panel active">' + modalContentHtml + '</div>'
				+ '		</div>'
				+ '		<div class="fs-modal-footer">'
				+ '			<button class="button button-secondary button-opt-out" tabindex="1"><?php echo $opt_out_button_text ?></button>'
				+ '			<button class="button button-primary button-close" tabindex="2"><?php _efs( 'opt-out-cancel', $slug ) ?></button>'
				+ '		</div>'
				+ '	</div>'
				+ '</div>',
			$modal              = $(modalHtml),
			$optOutLink         = $('span.opt-out.<?php echo $VARS['slug'] ?> a, .opt-out-trigger.<?php echo $VARS['slug'] ?>'),
			$optOutButton       = $modal.find('.button-opt-out'),
			$optOutErrorMessage = $modal.find( '.opt-out-error-message' ),
			pluginSlug          = '<?php echo $slug ?>';

		$modal.appendTo( $( 'body' ) );

		function registerEventHandlers() {
			$optOutLink.click(function( evt ) {
				evt.preventDefault();

				showModal();
			});

			$modal.on( 'click', '.button-opt-out', function( evt ) {
				evt.preventDefault();

				if ( $( this ).hasClass( 'disabled' ) ) {
					return;
				}

				disableOptOutButton();

				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action : '<?php echo $fs->get_action_tag( 'opt-out' ) ?>',
						slug   : pluginSlug
					},
					beforeSend: function() {
						$optOutButton.text( '<?php _efs( 'opting-out', $slug ) ?>' );
					},
					success: function( result ) {
						var resultObj = $.parseJSON( result );
						if ( resultObj.success ) {
							closeModal();
							location.reload();
						} else {
							showError( resultObj.error );
							resetOptOutButton();
						}
					}
				});
			});

			// If the user has clicked outside the window, close the modal.
			$modal.on( 'click', '.fs-close, .button-close', function() {
				closeModal();
				return false;
			});
		}

		registerEventHandlers();

		function showModal() {
			resetModal();

			// Display the dialog box.
			$modal.addClass( 'active' );
			$( 'body' ).addClass( 'has-fs-modal' );
		}

		function closeModal() {
			$modal.removeClass( 'active' );
			$( 'body' ).removeClass( 'has-fs-modal' );
		}

		function resetOptOutButton() {
			enableOptOutButton();
			$optOutButton.text( '<?php echo $opt_out_button_text; ?>' );
		}

		function resetModal() {
			hideError();
			resetOptOutButton();
		}

		function enableOptOutButton() {
			$optOutButton.removeClass( 'disabled' );
		}

		function disableOptOutButton() {
			$optOutButton.addClass( 'disabled' );
		}

		function hideError() {
			$optOutErrorMessage.hide();
		}

		function showError( msg ) {
			$optOutErrorMessage.find( ' > p' ).html( msg );
			$optOutErrorMessage.show();
		}
	});
})( jQuery );
</script>
