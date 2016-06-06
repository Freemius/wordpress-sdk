<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.1.2
	 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	$slug = $VARS['slug'];
	$fs   = freemius( $slug );

	$confirmation_message = $fs->apply_filters( 'uninstall_confirmation_message', '' );

	$reasons = $VARS['reasons'];

	$reasons_list_items_html = '';

foreach ( $reasons as $reason ) {
	$list_item_classes = 'reason' . ( ! empty( $reason['input_type'] ) ? ' has-input' : '' );
	$reasons_list_items_html .= '<li class="' . $list_item_classes . '" data-input-type="' . $reason['input_type'] . '" data-input-placeholder="' . $reason['input_placeholder'] . '"><label><span><input type="radio" name="selected-reason" value="' . $reason['id'] . '"/></span><span>' . $reason['text'] . '</span></label></li>';
}
?>
<script type="text/javascript">
(function ($) {
	var reasonsHtml = <?php echo json_encode( $reasons_list_items_html ); ?>,
	    modalHtml =
		    '<div class="fs-modal<?php echo empty( $confirmation_message ) ? ' no-confirmation-message' : ''; ?>">'
		    + '	<div class="fs-modal-dialog">'
		    + '		<div class="fs-modal-body">'
		    + '			<div class="fs-modal-panel" data-panel-id="confirm"><p><?php echo $confirmation_message; ?></p></div>'
		    + '			<div class="fs-modal-panel active" data-panel-id="reasons"><h3><strong><?php printf( __fs( 'deactivation-share-reason' , $slug ) ); ?>:</strong></h3><ul id="reasons-list">' + reasonsHtml + '</ul></div>'
		    + '		</div>'
		    + '		<div class="fs-modal-footer">'
		    + '			<a href="#" class="button button-secondary button-deactivate"></a>'
		    + '			<a href="#" class="button button-primary button-close"><?php printf( __fs( 'deactivation-modal-button-cancel' , $slug ) ); ?></a>'
		    + '		</div>'
		    + '	</div>'
		    + '</div>',
	    $modal = $(modalHtml),
	    $deactivateLink = $('#the-list .deactivate > [data-slug=<?php echo $VARS['slug']; ?>].fs-slug').prev(),
	    selectedReasonID = false;

	$modal.appendTo($('body'));

	registerEventHandlers();

	function registerEventHandlers() {
		$deactivateLink.click(function (evt) {
			evt.preventDefault();

			showModal();
		});

		$modal.on('input propertychange', '.reason-input input', function () {
			if (!isOtherReasonSelected()) {
				return;
			}

			var reason = $(this).val().trim();

			/**
			 * If reason is not empty, remove the error-message class of the message container
			 * to change the message color back to default.
			 */
			if (reason.length > 0) {
				$('.message').removeClass('error-message');
				enableDeactivateButton();
			}
		});

		$modal.on('blur', '.reason-input input', function () {
			var $userReason = $(this);

			setTimeout(function () {
				if (!isOtherReasonSelected()) {
					return;
				}

				/**
				 * If reason is empty, add the error-message class to the message container
				 * to change the message color to red.
				 */
				if (0 === $userReason.val().trim().length) {
					$('.message').addClass('error-message');
					disableDeactivateButton();
				}
			}, 150);
		});

		$modal.on('click', '.button', function (evt) {
			evt.preventDefault();

			if ($(this).hasClass('disabled')) {
				return;
			}

			var _parent = $(this).parents('.fs-modal:first');
			var _this = $(this);

			if (_this.hasClass('allow-deactivate')) {
				var $radio = $('input[type="radio"]:checked');

				if (0 === $radio.length) {
					// If no selected reason, just deactivate the plugin.
					window.location.href = $deactivateLink.attr('href');
					return;
				}

				var $selected_reason = $radio.parents('li:first'),
				    $input = $selected_reason.find('textarea, input[type="text"]'),
				    userReason = ( 0 !== $input.length ) ? $input.val().trim() : '';

				if (isOtherReasonSelected() && ( '' === userReason )) {
					return;
				}

				$.ajax({
					url       : ajaxurl,
					method    : 'POST',
					data      : {
						'action'     : 'submit-uninstall-reason',
						'reason_id'  : $radio.val(),
						'reason_info': userReason
					},
					beforeSend: function () {
						_parent.find('.button').addClass('disabled');
						_parent.find('.button-secondary').text('Processing...');
					},
					complete  : function () {
						// Do not show the dialog box, deactivate the plugin.
						window.location.href = $deactivateLink.attr('href');
					}
				});
			} else if (_this.hasClass('button-deactivate')) {
				// Change the Deactivate button's text and show the reasons panel.
				_parent.find('.button-deactivate').addClass('allow-deactivate');

				showPanel('reasons');
			}
		});

		$modal.on('click', 'input[type="radio"]', function () {
			var $selectedReasonOption = $(this);

			// If the selection has not changed, do not proceed.
			if (selectedReasonID === $selectedReasonOption.val())
				return;

			selectedReasonID = $selectedReasonOption.val();

			var _parent = $(this).parents('li:first');

			$modal.find('.reason-input').remove();
			$modal.find('.button-deactivate').text('<?php printf( __fs( 'deactivation-modal-button-submit' , $slug ) ); ?>');

			enableDeactivateButton();

			if (_parent.hasClass('has-input')) {
				var inputType = _parent.data('input-type'),
				    inputPlaceholder = _parent.data('input-placeholder'),
				    reasonInputHtml = '<div class="reason-input"><span class="message"></span>' + ( ( 'textfield' === inputType ) ? '<input type="text" />' : '<textarea rows="5"></textarea>' ) + '</div>';

				_parent.append($(reasonInputHtml));
				_parent.find('input, textarea').attr('placeholder', inputPlaceholder).focus();

				if (isOtherReasonSelected()) {
					showMessage('<?php printf( __fs( 'ask-for-reason-message' , $slug ) ); ?>');
					disableDeactivateButton();
				}
			}
		});

		// If the user has clicked outside the window, cancel it.
		$modal.on('click', function (evt) {
			var $target = $(evt.target);

			// If the user has clicked anywhere in the modal dialog, just return.
			if ($target.hasClass('fs-modal-body') || $target.hasClass('fs-modal-footer')) {
				return;
			}

			// If the user has not clicked the close button and the clicked element is inside the modal dialog, just return.
			if (!$target.hasClass('button-close') && ( $target.parents('.fs-modal-body').length > 0 || $target.parents('.fs-modal-footer').length > 0 )) {
				return;
			}

			closeModal();
		});
	}

	function isOtherReasonSelected() {
		// Get the selected radio input element.
		var $selectedReasonOption = $modal.find('input[type="radio"]:checked'),
		    selectedReason = $selectedReasonOption.parent().next().text().trim();

		return ( 'Other' === selectedReason );
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
		selectedReasonID = false;

		enableDeactivateButton();

		// Uncheck all radio buttons.
		$modal.find('input[type="radio"]').prop('checked', false);

		// Remove all input fields ( textfield, textarea ).
		$modal.find('.reason-input').remove();

		$modal.find('.message').hide();

		var $deactivateButton = $modal.find('.button-deactivate');

		/*
		 * If the modal dialog has no confirmation message, that is, it has only one panel, then ensure
		 * that clicking the deactivate button will actually deactivate the plugin.
		 */
		if ($modal.hasClass('no-confirmation-message')) {
			$deactivateButton.addClass('allow-deactivate');

			showPanel('reasons');
		} else {
			$deactivateButton.removeClass('allow-deactivate');

			showPanel('confirm');
		}
	}

	function showMessage(message) {
		$modal.find('.message').text(message).show();
	}

	function enableDeactivateButton() {
		$modal.find('.button-deactivate').removeClass('disabled');
	}

	function disableDeactivateButton() {
		$modal.find('.button-deactivate').addClass('disabled');
	}

	function showPanel(panelType) {
		$modal.find('.fs-modal-panel').removeClass('active ');
		$modal.find('[data-panel-id="' + panelType + '"]').addClass('active');

		updateButtonLabels();
	}

	function updateButtonLabels() {
		var $deactivateButton = $modal.find('.button-deactivate');

		// Reset the deactivate button's text.
		if ('confirm' === getCurrentPanel()) {
			$deactivateButton.text('<?php printf( __fs( 'deactivation-modal-button-confirm' , $slug ) ); ?>');
		} else {
			$deactivateButton.text('<?php printf( __fs( 'skip-deactivate' , $slug ) ); ?>');
		}
	}

	function getCurrentPanel() {
		return $modal.find('.fs-modal-panel.active').attr('data-panel-id');
	}
})(jQuery);
</script>
