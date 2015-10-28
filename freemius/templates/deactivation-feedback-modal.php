<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.1.2
	 */
    $slug         = $VARS['slug'];
    $fs           = freemius( $slug );
    
    $confirmation_message = $fs->apply_filters( 'uninstall_confirmation_message', '' );
    
    $reasons = $VARS['reasons'];
	
    $reasons_list_items_html = '';

	foreach ( $reasons as $reason ) {
		$list_item_classes = 'reason' . ( ! empty( $reason['input_type'] ) ? ' has-input' : '' );
		$reasons_list_items_html .= '<li class="' . $list_item_classes . '" data-input-type="' . $reason['input_type'] . '" data-input-placeholder="' . $reason['input_placeholder'] . '"><label><input type="radio" name="selected-reason" value="' . $reason['id'] . '"/> <span>' . $reason['text'] . '.</span></label></li>';
	}
	?>
	<script>
		(function( $ ) {
			var reasonsHtml		= <?php echo json_encode( $reasons_list_items_html ); ?>,
				modalHtml		=
				'<div class="freemius-modal<?php echo empty( $confirmation_message ) ? ' no-confirmation-message' : ''; ?>">'
				+	'	<div class="freemius-modal-dialog">'
				+	'		<div class="freemius-modal-body">'
				+	'			<div class="freemius-modal-panel panel-confirmation"><p><?php echo $confirmation_message; ?></p></div>'
				+	'			<div class="freemius-modal-panel panel-reasons"><p><strong><?php printf( __fs( 'deactivation-share-reason' ) ); ?>:</strong></p><ul id="reasons-list">' + reasonsHtml + '</ul></div>'
				+	'		</div>'
				+	'		<div class="freemius-modal-footer">'
				+	'			<a href="#" class="button button-secondary button-deactivate"></a>'
				+	'			<a href="#" class="button button-primary button-close"><?php printf( __fs( 'deactivation-modal-button-cancel' ) ); ?></a>'
				+	'		</div>'
				+	'	</div>'
				+	'</div>',
				$modal			= $( modalHtml ),
				$deactivateLink = $( '#the-list .deactivate > [data-slug=<?php echo $VARS['slug']; ?>].fs-slug' ).prev();
				
			$modal.appendTo( $( 'body' ) );

			registerEventHandlers();
			
			function registerEventHandlers() {
				$deactivateLink.click(function ( evt ) {
					evt.preventDefault();

					showModal();
				});
				
				$modal.on( 'click', '.button', function( evt ) {
					evt.preventDefault();
					
					if ( $( this ).hasClass( 'disabled' ) ) {
						return;
					}
					
					var _parent = $( this ).parents( '.freemius-modal:first' );
					var _this = $( this );

					if ( _this.hasClass( 'button-close' ) ) {
						$modal.removeClass( 'active' );
					} else if ( _this.hasClass( 'allow-deactivate' ) ) {
                        var $radio           = $( 'input[type="radio"]:checked' );
						
						if ( 0 === $radio.length ) {
							// If no selected reason, just deactivate the plugin.
							window.location.href = $deactivateLink.attr( 'href' );
							return;
						}
						
                        var	$selected_reason = $radio.parents( 'li:first' ),
							$input           = $selected_reason.find( 'textarea, input[type="text"]' );
							
						$.ajax({
							url: ajaxurl,
							method: 'POST',
							data: {
								'action'      : 'submit-uninstall-reason',
								'reason_id'   : $radio.val(),
								'reason_info' : ( 0 !== $input.length ) ? $input.val().trim() : ''
							},
							beforeSend: function() {
								_parent.find( '.button' ).addClass( 'disabled' );
								_parent.find( '.button-secondary' ).text( 'Processing...' );
							},
							complete: function() {
								// Do not show the dialog box, deactivate the plugin.
								window.location.href = $deactivateLink.attr( 'href' );
							}
						});
					} else if ( _this.hasClass( 'button-deactivate' ) ) {
						// Change the Deactivate button's text and show the reasons panel.
						_parent.find( '.button-deactivate').addClass( 'allow-deactivate' );
                        
                        showPanel( 'reasons' );
					}
				});

				$modal.on( 'click', 'input[type="radio"]', function() {
					var _parent = $( this ).parents( 'li:first' );
					
					$modal.find( '.reason-input' ).remove();
					$modal.find( '.button-deactivate').text( '<?php printf( __fs( 'deactivation-modal-button-submit' ) ); ?>' );

					if ( _parent.hasClass( 'has-input' ) ) {
						var inputType		 = _parent.data( 'input-type' ),
							inputPlaceholder = _parent.data( 'input-placeholder' ),
							reasonInputHtml  = '<div class="reason-input">' + ( ( 'textfield' === inputType ) ? '<input type="text" />' : '<textarea rows="5"></textarea>' ) + '</div>'; 
						
						_parent.append( $( reasonInputHtml ) );
						_parent.find( 'input, textarea' ).attr( 'placeholder', inputPlaceholder ).focus();
					}
				});
			}
			
			function showModal() {
				resetModal();
				
				// Display the dialog box.
				$modal.addClass( 'active' );
			}
			
			function resetModal() {
				$modal.find( '.button' ).removeClass( 'disabled' );
				
				// Uncheck all radio buttons.
				$modal.find( 'input[type="radio"]' ).prop( 'checked', false );

				// Remove all input fields ( textfield, textarea ).
				$modal.find( '.reason-input' ).remove();
				
				var $deactivateButton = $modal.find( '.button-deactivate' );
				
				// Reset the deactivate button's text.
				$deactivateButton.text( '<?php printf( __fs( 'deactivation-modal-button-deactivate' ) ); ?>' );
				
				/*
				 * If the modal dialog has no confirmation message, that is, it has only one panel, then ensure
				 * that clicking the deactivate button will actually deactivate the plugin.
				 */
				if ( $modal.hasClass( 'no-confirmation-message' ) ) {
					$deactivateButton.addClass( 'allow-deactivate' );
				} else {
					$deactivateButton.removeClass( 'allow-deactivate' );
				}
				
                showDefaultPanel();
			}
            
            function showDefaultPanel() {
                if ( $modal.hasClass( 'no-confirmation-message' ) ) {
                    // If no confirmation message, show the reasons panel immediately.
    				$modal.find( '.panel-confirmation').removeClass( 'active' );
    				$modal.find( '.panel-reasons').addClass( 'active' );
                } else {
                    // Show the confirmation message first if it is available, then hide the reasons panel.
    				$modal.find( '.panel-reasons').removeClass( 'active' );
    				$modal.find( '.panel-confirmation').addClass( 'active' );
                }                   
            }
            
            function showPanel( panelType ) {
                $modal.find( '.freemius-modal-panel' ).removeClass( 'active ');
                $modal.find( '.panel-' + panelType ).addClass( 'active' );
            }
		})( jQuery );
	</script>
