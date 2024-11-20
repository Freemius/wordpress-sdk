<div>
	<!-- Debugging Switch -->
	<span class="fs-switch-label"><?php fs_esc_html_echo_x_inline( 'Debugging', 'as code debugging' ) ?></span>
	<div class="fs-switch fs-round <?php echo WP_FS__DEBUG_SDK ? 'fs-on' : 'fs-off' ?>">
		<div class="fs-toggle"></div>
	</div>
	<script type="text/javascript">
        (function ($) {
            $(document).ready(function () {
                // Switch toggle
                $('.fs-switch').click(function () {
                    $(this)
                        .toggleClass('fs-on')
                        .toggleClass('fs-off');

                    $.post( <?php echo Freemius::ajax_url() ?>, {
                        action: 'fs_toggle_debug_mode',
                        // As such we don't need to use `wp_json_encode` method but using it to follow wp.org guideline.
                        _wpnonce: <?php echo wp_json_encode( wp_create_nonce( 'fs_toggle_debug_mode' ) ); ?>,
                        is_on   : ($(this).hasClass('fs-on') ? 1 : 0)
                    }, function (response) {
                        if (1 == response) {
                            // Refresh page on success.
                            location.reload();
                        }
                    });
                });
            });
        }(jQuery));
	</script>
</div>