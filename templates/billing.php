<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2016, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.2.0
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

	/**
	 * @var FS_Plugin_Tag $update
	 */
	$update = $fs->get_update( false, false );

	$is_paying              = $fs->is_paying();
	$user                   = $fs->get_user();
	$site                   = $fs->get_site();
	$name                   = $user->get_name();
	$license                = $fs->_get_license();
	$subscription           = $fs->_get_subscription();
	$plan                   = $fs->get_plan();
	$is_active_subscription = ( is_object( $subscription ) && $subscription->is_active() );
	$is_paid_trial          = $fs->is_paid_trial();
	$show_upgrade           = ( ! $is_paying && ! $is_paid_trial );
?>

	<div id="fs_account" class="wrap">
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo $fs->get_account_url() ?>" class="nav-tab"><?php _efs( 'account', $slug ) ?></a>
			<?php if ( $fs->has_addons() ) : ?>
				<a href="<?php echo $fs->_get_admin_page_url( 'addons' ) ?>"
				   class="nav-tab"><?php _efs( 'add-ons', $slug ) ?></a>
			<?php endif ?>
			<?php if ( $fs->is_not_paying() && $fs->has_paid_plan() ) : ?>
				<a href="<?php echo $fs->get_upgrade_url() ?>" class="nav-tab"><?php _efs( 'upgrade', $slug ) ?></a>
				<?php if ( $fs->apply_filters( 'show_trial', true ) && ! $fs->is_trial_utilized() && $fs->has_trial_plan() ) : ?>
					<a href="<?php echo $fs->get_trial_url() ?>"
					   class="nav-tab"><?php _efs( 'free-trial', $slug ) ?></a>
				<?php endif ?>
			<?php endif ?>
			<?php if ( ! $plan->is_free() ) : ?>
				<a href="<?php echo $fs->get_account_tab_url( 'billing' ) ?>"
				   class="nav-tab nav-tab-active"><?php _efs( 'billing', $slug ) ?></a>
			<?php endif ?>
		</h2>

		<div id="poststuff">
			<div>
				<div class="has-sidebar has-right-sidebar">
					<div class="has-sidebar-content">
						<div class="postbox">
							<h3><?php _efs( 'payments', $slug ) ?></h3>

							<?php
								$payments  = $fs->_fetch_payments();
							?>

							<div class="inside">
								<table class="widefat">
									<thead>
									<tr>
										<th><?php _efs( 'id', $slug ) ?></th>
										<th><?php _efs( 'date', $slug ) ?></th>
										<!--		<th>--><?php //_efs( 'transaction' ) ?><!--</th>-->
										<th><?php _efs( 'amount', $slug ) ?></th>
										<th><?php _efs( 'invoice', $slug ) ?></th>
									</tr>
									</thead>
									<tbody>
									<?php $odd = true ?>
									<?php foreach ( $payments as $payment ) : ?>
										<tr<?php echo $odd ? ' class="alternate"' : '' ?>>
											<td><?php echo $payment->id ?></td>
											<td><?php echo date( 'M j, Y', strtotime( $payment->created ) ) ?></td>
											<td>$<?php echo $payment->gross ?></td>
											<td><a href="<?php echo $fs->_get_invoice_api_url($payment->id) ?>" class="button button-small"
											       target="_blank"><?php _efs( 'invoice', $slug ) ?></a></td>
										</tr>
										<?php $odd = ! $odd; endforeach ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
	$params = array(
		'page'           => 'account',
		'module_id'      => $fs->get_id(),
		'module_slug'    => $slug,
		'module_version' => $fs->get_plugin_version(),
	);
	fs_require_template( 'powered-by.php', $params );
?>