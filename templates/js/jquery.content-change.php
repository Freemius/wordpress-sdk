<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.2.2.7
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
?>
<script type="text/javascript">
	(function ($) {
		if ($.fn.contentChange)
			return;

		/**
		 * Content change event listener.
		 *
		 * @url http://stackoverflow.com/questions/3233991/jquery-watch-div/3234646#3234646
		 *
		 * @param {function} callback
		 *
		 * @returns {object[]}
		 */
		$.fn.contentChange = function (callback) {
			var elements = $(this);

			elements.each(function () {
				var element = $(this);

				element.data("lastContents", element.html());

				window.watchContentChange = window.watchContentChange ?
					window.watchContentChange :
					[];

				window.watchContentChange.push({
					"element" : element,
					"callback": callback
				});
			});

			return elements;
		};
	})(jQuery);
</script>