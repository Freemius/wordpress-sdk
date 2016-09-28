<?php
	/**
	 * Sticky admin notices JavaScript handler for dismissing notice messages
	 * by sending AJAX call to the server in order to remove the message from the Database.
	 *
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.7
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
?>
<script type="text/javascript" >
	jQuery( document ).ready(function( $ ) {
		$( '.fs-notice.fs-sticky .fs-close' ).click(function() {
			var
				notice           = $( this ).parents( '.fs-notice' ),
				id               = notice.attr( 'data-id' ),
				ajaxActionSuffix = notice.attr( 'data-manager-id' ).replace( ':', '-' );

			notice.fadeOut( 'fast', function() {
				var data = {
					action    : 'fs_dismiss_notice_action_' + ajaxActionSuffix,
					message_id: id
				};

				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				$.post( ajaxurl, data, function( response ) {

				});

				notice.remove();
			});
		});
	});
</script>