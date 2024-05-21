<?php if ( FS_Logger::is_storage_logging_on() ) : ?>

	<h2><?php fs_esc_html_echo_inline( 'Debug Log', 'debug-log' ) ?></h2>

	<div id="fs_debug_filters">
		<select name="type">
			<option value="" selected="selected"><?php fs_esc_html_echo_inline( 'All Types', 'all-types' ) ?></option>
			<option value="warn_error">Warnings & Errors</option>
			<option value="error">Errors</option>
			<option value="warn">Warnings</option>
			<option value="info">Info</option>
		</select>
		<select name="request_type">
			<option value="" selected="selected"><?php fs_esc_html_echo_inline( 'All Requests',
					'all-requests' ) ?></option>
			<option value="call">Sync</option>
			<option value="ajax">AJAX</option>
			<option value="cron">WP Cron</option>
		</select>
		<input name="file" type="text" placeholder="<?php fs_esc_attr_echo_inline( 'File' ) ?>"/>
		<input name="function" type="text" placeholder="<?php fs_esc_attr_echo_inline( 'Function' ) ?>"/>
		<input name="process_id" type="text" placeholder="<?php fs_esc_attr_echo_inline( 'Process ID' ) ?>"/>
		<input name="logger" type="text" placeholder="<?php fs_esc_attr_echo_inline( 'Logger' ) ?>"/>
		<input name="message" type="text" placeholder="<?php fs_esc_attr_echo_inline( 'Message' ) ?>"/>
		<div style="margin: 10px 0">
			<button id="fs_filter" class="button" style="float: left"><i
					class="dashicons dashicons-filter"></i> <?php fs_esc_html_echo_inline( 'Filter', 'filter' ) ?>
			</button>

			<form action="" method="POST" style="float: left; margin-left: 10px;">
				<input type="hidden" name="fs_action" value="download_logs">
				<?php wp_nonce_field( 'download_logs' ) ?>
				<div class="fs-filters"></div>
				<button id="fs_download" class="button" type="submit"><i
						class="dashicons dashicons-download"></i> <?php fs_esc_html_echo_inline( 'Download' ) ?>
				</button>
			</form>
			<div style="clear: both"></div>
		</div>
	</div>

	<div id="fs_log_book" style="height: 300px; overflow: auto;">
		<table class="widefat">
			<thead>
			<tr>
				<th>#</th>
				<th><?php fs_esc_html_echo_inline( 'Type' ) ?></th>
				<th><?php fs_esc_html_echo_inline( 'ID', 'id' ) ?></th>
				<th><?php fs_esc_html_echo_inline( 'Function' ) ?></th>
				<th><?php fs_esc_html_echo_inline( 'Message' ) ?></th>
				<th><?php fs_esc_html_echo_inline( 'File' ) ?></th>
				<th><?php fs_esc_html_echo_inline( 'Timestamp' ) ?></th>
			</tr>
			</thead>
			<tbody>
			<tr style="display: none">
				<td>{$log.log_order}.</td>
				<td class="fs-col--type">{$log.type}</td>
				<td class="fs-col--logger">{$log.logger}</td>
				<td class="fs-col--function">{$log.function}</td>
				<td class="fs-col--message">
					<a href="#" onclick="jQuery(this).parent().find('div').toggle(); return false;">
						<nobr>{$log.message_short}</nobr>
					</a>
					<div style="display: none;">{$log.message}</div>
				</td>
				<td class="fs-col--file">{$log.file}:{$log.line}</td>
				<td class="fs-col--timestamp">{$log.created}</td>
			</tr>

			</tbody>
		</table>
	</div>
	<script type="text/javascript">
        jQuery(document).ready(function ($) {
            var filtersChanged = false,
                offset = 0,
                limit = 200,
                prevFiltersSignature = null;

            var getFilters = function () {
                var filters = {},
                    signature = '';

                $('#fs_debug_filters').find('select, input').each(function (i, e) {
                    var $element = $(e);

                    if ('hidden' === $element.attr('type'))
                        return;

                    var val = $element.val();
                    if ('' !== val.trim()) {
                        var name = $(e).attr('name');
                        filters[name] = val;
                        signature += name + '=' + val + '~';
                    }
                });

                if (signature != prevFiltersSignature) {
                    filtersChanged = true;
                    prevFiltersSignature = signature;
                } else {
                    filtersChanged = false;
                }

                return filters;
            };

            $('#fs_download').parent().submit(function () {
                var filters = getFilters(),
                    hiddenFields = '';

                for (var f in filters) {
                    if (filters.hasOwnProperty(f)) {
                        hiddenFields += '<input type="hidden" name="filters[' + f + ']" value="' + filters[f] + '" />';
                    }
                }

                $(this).find('.fs-filters').html(hiddenFields);
            });

            var loadLogs = function () {
                var $tbody = $('#fs_log_book tbody'),
                    template = $tbody.find('tr:first-child').html(),
                    filters = getFilters();

                if (!filtersChanged) {
                    offset += limit;
                } else {
                    // Cleanup table for new filter (only keep template row).
                    $tbody.find('tr').each(function (i, e) {
                        if (0 == i)
                            return;

                        $(e).remove();
                    });

                    offset = 0;
                }

                $.post(<?php echo Freemius::ajax_url() ?>, {
                    action: 'fs_get_debug_log',
                    // As such we don't need to use `wp_json_encode` method but using it to follow wp.org guideline.
                    _wpnonce: <?php echo wp_json_encode( wp_create_nonce( 'fs_get_debug_log' ) ); ?>,
                    filters : filters,
                    offset  : offset,
                    limit   : limit
                }, function (response) {

                    for (var i = 0; i < response.data.length; i++) {
                        var templateCopy = template;

                        response.data[i].message_short = (response.data[i].message.length > 32) ?
                            response.data[i].message.substr(0, 32) + '...' :
                            response.data[i].message;

                        for (var p in response.data[i]) {
                            if (response.data[i].hasOwnProperty(p)) {
                                templateCopy = templateCopy.replace('{$log.' + p + '}', response.data[i][p]);
                            }
                        }

                        $tbody.append('<tr' + (i % 2 ? ' class="alternate"' : '') + '>' + templateCopy + '</tr>');
                    }
                });
            };

            $('#fs_filter').click(function () {
                loadLogs();

                return false;
            });

            loadLogs();
        });
	</script>
<?php endif ?>