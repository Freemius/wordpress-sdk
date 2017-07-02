<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.2.1.8
	 *
	 * @var array $VARS
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
?>
<div class="fs-secure-notice">
	<i class="dashicons dashicons-lock"></i>
	<span><?php
			if ( ! empty( $VARS['message'] ) ) {
				echo $VARS['message'];
			} else {
				/**
				 * @var Freemius $fs
				 */
				$fs = freemius( $VARS['id'] );

				echo sprintf(
					     $fs->get_text( 'secure-x-page-header' ),
					     $VARS['page']
				     ) .
				     ' - ' .
				     sprintf(
					     '<a class="fs-security-proof" href="%s" target="_blank">%s</a>',
					     'https://www.mcafeesecure.com/verify?host=' . WP_FS__ROOT_DOMAIN_PRODUCTION,
					     'Freemius Inc. [US]'
				     );
			}
		?></span>
</div>