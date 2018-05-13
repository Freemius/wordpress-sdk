<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       2.1.0
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

    /**
     * @var array $VARS
     */
    $fs = freemius( $VARS['id'] );
?>
<script type="text/javascript">
	jQuery( document ).ready(function( $ ) {
	    var $gdprOptinNotice = $( 'div[data-id^="gdpr_optin_actions"]' );
	    if ( 0 === $gdprOptinNotice.length ) {
	        return;
        }

        $gdprOptinNotice.on( 'click', '.button', function() {
			var
                allowMarketing = $( this ).hasClass( 'allow-marketing' ),
                cursor         = $( this ).css( 'cursor' ),
                $products      = $gdprOptinNotice.find( 'span[data-plugin-id]' ),
                pluginIDs      = [];

			if ( $products.length > 0 ) {
			    $products.each(function() {
			        pluginIDs.push( $( this ).data( 'plugin-id' ) );
                });
            }

            $.ajax({
                url       : ajaxurl + '?' + $.param({
                    action   : '<?php echo $fs->get_ajax_action( 'gdpr_optin_action' ) ?>',
                    security : '<?php echo $fs->get_ajax_security( 'gdpr_optin_action' ) ?>',
                    module_id: '<?php echo $fs->get_id() ?>'
                }),
                method    : 'POST',
                data      : {
                    allow_marketing: allowMarketing,
                    plugin_ids     : pluginIDs
                },
                beforeSend: function() {
                    $gdprOptinNotice.find( '.button' ).addClass( 'disabled' );
                    $( this ).css({'cursor': 'wait'});
                },
                complete  : function() {
                    $( this ).css({'cursor': cursor});

                    $gdprOptinNotice.remove();
                }
            });
		});
	});
</script>