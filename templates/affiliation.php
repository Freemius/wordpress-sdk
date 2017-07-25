<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 */
	$slug = $VARS['slug'];
	/**
	 * @var Freemius $fs
	 */
	$fs = freemius( $slug );

	$user            = $fs->get_user();
	$affiliate       = $fs->get_affiliate();
	$affiliate_terms = $fs->get_affiliate_terms();

	$module_type = $fs->is_plugin() ?
        WP_FS__MODULE_TYPE_PLUGIN :
        WP_FS__MODULE_TYPE_THEME ;

	$commission = '$' . $affiliate_terms->commission;
?>
<div class="wrap">
    <form id="fs-affiliation-page" method="post" action="">
        <div id="poststuff">
            <div class="postbox">
                <div class="inside">
                    <?php if ( is_object( $affiliate ) ) : ?>
                        <?php if ( $affiliate->is_pending() ) : ?>
                        <div class="updated">
                            <p><strong><?php fs_echo( 'affiliate-application-thank-you', $slug ) ?></strong></p>
                        </div>
                        <?php elseif ( $affiliate->is_suspended() ) : ?>
                        <div class="notice notice-warning">
                            <p><strong><?php fs_echo( 'affiliate-account-suspended', $slug ) ?></strong></p>
                        </div>
                        <?php elseif ( $affiliate->is_rejected() ) : ?>
                            <div class="error">
                                <p><strong><?php fs_echo( 'affiliate-application-rejected', $slug ) ?></strong></p>
                            </div>
                        <?php endif ?>
                    <?php endif ?>

                    <?php if ( ! is_object( $affiliate ) || $affiliate->is_active() ) : ?>
                    <div class="entry-content">
                        <?php if ( ! is_object( $affiliate ) ) : ?>
                        <p><?php echo sprintf( fs_text( 'become-an-ambassador', $slug ), $module_type ) ?></p>
                        <p><?php echo sprintf( fs_text( 'refer-new-customers', $slug ), $module_type, $commission ) ?></p>
                        <?php endif ?>
                        <h3><?php fs_echo( 'program-summary', $slug ) ?></h3>
                        <ul>
                            <li><?php echo sprintf( fs_text( 'commission-new-license', $slug ), $commission ) ?></li>
                            <li><?php fs_echo( 'unlimited-commissions', $slug ) ?></li>
                            <li><?php echo sprintf( fs_text( 'minimum-payout-amount', $slug ), $commission ) ?></li>
                            <li><?php fs_echo( 'payouts-unit-and-processing', $slug ) ?></li>
                            <li><?php fs_echo( 'commission-payment', $slug ) ?></li>
                        </ul>
                        <?php if ( ! is_object( $affiliate ) ) : ?>
                        <p><a id="apply-affiliate" class="button button-primary" title="<?php fs_echo( 'become-an-affiliate', $slug ) ?>" href="#"><?php fs_echo( 'apply-to-become-an-affiliate', $slug ); ?></a></p>
                        <?php endif ?>
                    </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </form>
    <script>
    </script>
</div>
<?php
    $params = array(
        'page'           => 'affiliation',
        'module_id'      => $fs->get_id(),
        'module_slug'    => $slug,
        'module_version' => $fs->get_plugin_version(),
    );
    fs_require_template( 'powered-by.php', $params );
?>