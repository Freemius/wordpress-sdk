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
	 * @var Freemius $fs
	 */
	$fs   = freemius( $VARS['id'] );

	$slug = $fs->get_slug();

	/**
	 * @var FS_Plugin_Tag $update
	 */
	$update = $fs->get_update( false, false, WP_FS__TIME_24_HOURS_IN_SEC / 24 );

	$is_paying              = $fs->is_paying();
	$user                   = $fs->get_user();
	$site                   = $fs->get_site();
	$name                   = $user->get_name();
	$license                = $fs->_get_license();
	$subscription           = $fs->_get_subscription();
	$plan                   = $fs->get_plan();
	$is_active_subscription = ( is_object( $subscription ) && $subscription->is_active() );
	$is_paid_trial          = $fs->is_paid_trial();
	$show_upgrade           = ( $fs->has_paid_plan() && ! $is_paying && ! $is_paid_trial );

	if ( $fs->has_paid_plan() ) {
		$fs->_add_license_activation_dialog_box();
	}

	if ( fs_request_get_bool( 'auto_install' ) ) {
		$fs->_add_auto_installation_dialog_box();
	}

	$has_tabs = $fs->_add_tabs_before_content();

	if ( $has_tabs ) {
		$query_params['tabs'] = 'true';
	}
?>
	<div class="wrap fs-section">
		<?php if ( ! $has_tabs && ! $fs->apply_filters( 'hide_account_tabs', false ) ) : ?>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo $fs->get_account_url() ?>"
			   class="nav-tab nav-tab-active"><?php fs_echo( 'account', $slug ) ?></a>
			<?php if ( $fs->has_addons() ) : ?>
				<a href="<?php echo $fs->_get_admin_page_url( 'addons' ) ?>"
				   class="nav-tab"><?php fs_echo( 'add-ons', $slug ) ?></a>
			<?php endif ?>
			<?php if ( $show_upgrade ) : ?>
				<a href="<?php echo $fs->get_upgrade_url() ?>" class="nav-tab"><?php fs_echo( 'upgrade', $slug ) ?></a>
				<?php if ( $fs->apply_filters( 'show_trial', true ) && ! $fs->is_trial_utilized() && $fs->has_trial_plan() ) : ?>
					<a href="<?php echo $fs->get_trial_url() ?>" class="nav-tab"><?php fs_echo( 'free-trial', $slug ) ?></a>
				<?php endif ?>
			<?php endif ?>
		</h2>
		<?php endif ?>

		<div id="poststuff">
			<div id="fs_account">
				<div class="has-sidebar has-right-sidebar">
					<div class="has-sidebar-content">
						<div class="postbox">
							<h3><span class="dashicons dashicons-businessman"></span> <?php fs_echo( 'account-details', $slug ) ?></h3>
							<div class="fs-header-actions">
								<ul>
									<?php if ( ! $is_paying ) : ?>
										<li>
											<form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>" method="POST">
												<input type="hidden" name="fs_action" value="delete_account">
												<?php wp_nonce_field( 'delete_account' ) ?>
												<a href="#" onclick="if (confirm('<?php
													if ( $is_active_subscription ) {
														echo esc_attr( sprintf( fs_text( 'delete-account-x-confirm', $slug ), $plan->title ) );
													} else {
														echo sprintf( fs_text( 'delete-account-confirm', $slug ), $fs->get_module_type() );
													}
												?>'))  this.parentNode.submit(); return false;"><i
														class="dashicons dashicons-no"></i> <?php fs_echo( 'delete-account', $slug ) ?></a>
											</form>
										</li>
										<li>&nbsp;&bull;&nbsp;</li>
									<?php endif ?>
									<?php if ( $is_paying ) : ?>
										<li>
											<form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>" method="POST">
												<input type="hidden" name="fs_action" value="deactivate_license">
												<?php wp_nonce_field( 'deactivate_license' ) ?>
												<a href="#"
												   onclick="if (confirm('<?php fs_echo( 'deactivate-license-confirm', $slug ) ?>')) this.parentNode.submit(); return false;"><i
														class="dashicons dashicons-admin-network"></i> <?php fs_echo( 'deactivate-license', $slug ) ?>
												</a>
											</form>
										</li>
										<li>&nbsp;&bull;&nbsp;</li>
										<?php if ( ! $license->is_lifetime() &&
										           $is_active_subscription
										) : ?>
											<li>
												<form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>" method="POST">
													<input type="hidden" name="fs_action" value="downgrade_account">
													<?php wp_nonce_field( 'downgrade_account' ) ?>
													<a href="#"
													   onclick="if (confirm('<?php printf( fs_text( 'downgrade-x-confirm', $slug ), $plan->title, human_time_diff( time(), strtotime( $license->expiration ) ) ) ?> <?php if ( ! $license->is_block_features ) {
														   printf( fs_text( 'after-downgrade-non-blocking', $slug ), $plan->title, $fs->get_module_type() );
													   } else {
														   printf( fs_text( 'after-downgrade-blocking', $slug ), $plan->title );
													   }?> <?php fs_echo( 'proceed-confirmation', $slug ) ?>')) this.parentNode.submit(); return false;"><i class="dashicons dashicons-download"></i> <?php fs_echo( ( $fs->is_only_premium() ? 'cancel-subscription' : 'downgrade' ), $slug ) ?></a>
												</form>
											</li>
											<li>&nbsp;&bull;&nbsp;</li>
										<?php endif ?>
										<?php if ( ! $fs->is_single_plan() ) : ?>
											<li>
												<a href="<?php echo $fs->get_upgrade_url() ?>"><i
														class="dashicons dashicons-grid-view"></i> <?php fs_echo( 'change-plan', $slug ) ?></a>
											</li>
											<li>&nbsp;&bull;&nbsp;</li>
										<?php endif ?>
									<?php elseif ( $is_paid_trial ) : ?>
										<li>
											<form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>" method="POST">
												<input type="hidden" name="fs_action" value="cancel_trial">
												<?php wp_nonce_field( 'cancel_trial' ) ?>
												<a href="#"
												   onclick="if (confirm('<?php fs_echo( 'cancel-trial-confirm' ) ?>')) this.parentNode.submit(); return false;"><i
														class="dashicons dashicons-download"></i> <?php fs_echo( 'cancel-trial', $slug ) ?></a>
											</form>
										</li>
										<li>&nbsp;&bull;&nbsp;</li>
									<?php endif ?>
									<li>
										<form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>" method="POST">
											<input type="hidden" name="fs_action" value="<?php echo $fs->get_unique_affix() ?>_sync_license">
											<?php wp_nonce_field( $fs->get_unique_affix() . '_sync_license' ) ?>
											<a href="#" onclick="this.parentNode.submit(); return false;"><i
													class="dashicons dashicons-image-rotate"></i> <?php fs_echo( 'sync', $slug ) ?></a>
										</form>
									</li>

								</ul>
							</div>
							<div class="inside">
								<table id="fs_account_details" cellspacing="0" class="fs-key-value-table">
									<?php
										$hide_license_key = $fs->apply_filters( 'hide_license_key', false );

										$profile   = array();
										$profile[] = array(
											'id'    => 'user_name',
											'title' => fs_text( 'name', $slug ),
											'value' => $name
										);
										//					if (isset($user->email) && false !== strpos($user->email, '@'))
										$profile[] = array(
											'id'    => 'email',
											'title' => fs_text( 'email', $slug ),
											'value' => $user->email
										);

										if ( is_numeric( $user->id ) ) {
											$profile[] = array(
												'id'    => 'user_id',
												'title' => fs_text( 'user-id', $slug ),
												'value' => $user->id
											);
										}

										$profile[] = array(
											'id'    => 'site_id',
											'title' => fs_text( 'site-id', $slug ),
											'value' => is_string( $site->id ) ?
												$site->id :
												fs_text( 'no-id', $slug )
										);

										$profile[] = array(
											'id'    => 'site_public_key',
											'title' => fs_text( 'public-key', $slug ),
											'value' => $site->public_key
										);

										$profile[] = array(
											'id'    => 'site_secret_key',
											'title' => fs_text( 'secret-key', $slug ),
											'value' => ( ( is_string( $site->secret_key ) ) ?
												$site->secret_key :
												fs_text( 'no-secret', $slug )
											)
										);

										$profile[] = array(
											'id'    => 'version',
											'title' => fs_text( 'version', $slug ),
											'value' => $fs->get_plugin_version()
										);

										if ( $fs->has_paid_plan() ) {
											if ( $fs->is_trial() ) {
												$trial_plan = $fs->get_trial_plan();

												$profile[] = array(
													'id'    => 'plan',
													'title' => fs_text( 'plan', $slug ),
													'value' => ( is_string( $trial_plan->name ) ?
														strtoupper( $trial_plan->title ) :
														fs_text( 'trial', $slug ) )
												);
											} else {
												$profile[] = array(
													'id'    => 'plan',
													'title' => fs_text( 'plan', $slug ),
													'value' => is_string( $site->plan->name ) ?
														strtoupper( $site->plan->title ) :
														strtoupper( fs_text( 'free', $slug ) )
												);

												if ( is_object( $license ) ) {
													if ( ! $hide_license_key ) {
														$profile[] = array(
															'id'    => 'license_key',
															'title' => fs_text( 'License Key', $slug ),
															'value' => $license->secret_key,
														);
													}
												}
											}
										}
									?>
									<?php $odd = true;
										foreach ( $profile as $p ) : ?>
											<?php
											if ( 'plan' === $p['id'] && ! $fs->has_paid_plan() ) {
												// If plugin don't have any paid plans, there's no reason
												// to show current plan.
												continue;
											}
											?>
											<tr class="fs-field-<?php echo $p['id'] ?><?php if ( $odd ) : ?> alternate<?php endif ?>">
												<td>
													<nobr><?php echo $p['title'] ?>:</nobr>
												</td>
												<td<?php if ( 'plan' === $p['id'] ) { echo ' colspan="2"'; }?>>
													<?php if ( in_array( $p['id'], array( 'license_key', 'site_secret_key' ) ) ) : ?>
														<code><?php echo htmlspecialchars( substr( $p['value'], 0, 6 ) ) . str_pad( '', 23 * 6, '&bull;' ) . htmlspecialchars( substr( $p['value'], - 3 ) ) ?></code>
														<input type="text" value="<?php echo htmlspecialchars( $p['value'] ) ?>" style="display: none"
														       readonly/>
													<?php else : ?>
														<code><?php echo htmlspecialchars( $p['value'] ) ?></code>
													<?php endif ?>
													<?php if ( 'email' === $p['id'] && ! $user->is_verified() ) : ?>
														<label class="fs-tag fs-warn"><?php fs_esc_html_echo( 'not-verified', $slug ) ?></label>
													<?php endif ?>
													<?php if ( 'plan' === $p['id'] ) : ?>
														<?php if ( $fs->is_trial() ) : ?>
															<label class="fs-tag fs-success"><?php fs_esc_html_echo( 'trial', $slug ) ?></label>
														<?php endif ?>
														<?php if ( is_object( $license ) && ! $license->is_lifetime() ) : ?>
															<?php if ( ! $is_active_subscription && ! $license->is_first_payment_pending() ) : ?>
																<label
																	class="fs-tag fs-warn"><?php echo esc_html( sprintf( fs_text( 'expires-in', $slug ), human_time_diff( time(), strtotime( $license->expiration ) ) ) ) ?></label>
															<?php elseif ( $is_active_subscription && ! $subscription->is_first_payment_pending() ) : ?>
																<label class="fs-tag fs-success"><?php echo esc_html( sprintf( fs_text( 'renews-in', $slug ), human_time_diff( time(), strtotime( $subscription->next_payment ) ) ) ) ?></label>
															<?php endif ?>
														<?php elseif ( $fs->is_trial() ) : ?>
															<label class="fs-tag fs-warn"><?php echo esc_html( sprintf( fs_text( 'expires-in', $slug ), human_time_diff( time(), strtotime( $site->trial_ends ) ) ) ) ?></label>
														<?php endif ?>
														<div class="button-group">
															<?php $available_license = $fs->is_free_plan() ? $fs->_get_available_premium_license() : false ?>
															<?php if ( false !== $available_license && ( $available_license->left() > 0 || ( $site->is_localhost() && $available_license->is_free_localhost ) ) ) : ?>
																<?php $premium_plan = $fs->_get_plan_by_id( $available_license->plan_id ) ?>
																<form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>"
																      method="POST">
																	<input type="hidden" name="fs_action" value="activate_license">
																	<input type="hidden" name="license_id" value="<?php echo $available_license->id ?>">
																	<?php wp_nonce_field( 'activate_license' ) ?>
																	<input type="submit" class="button button-primary"
																	       value="<?php echo esc_attr( sprintf(
																		       fs_text( 'activate-x-plan', $slug ) . '%s',
																		       $premium_plan->title,
																		       ( $site->is_localhost() && $available_license->is_free_localhost ) ?
																			       ' [' . fs_text( 'localhost', $slug ) . ']' :
																			       ( $available_license->is_single_site() ?
																				       '' :
																				       ' [' . ( 1 < $available_license->left() ?
																					       sprintf( fs_text( 'x-left', $slug ), $available_license->left() ) :
																					       strtolower( fs_text( 'last-license', $slug ) ) ) . ']'
																			       )
																	       ) ) ?> ">
																</form>
															<?php else : ?>
																<form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>"
																      method="POST" class="button-group">
																	<?php if ( $show_upgrade && $fs->is_premium() ) : ?>
																		<a class="button activate-license-trigger <?php echo $fs->get_unique_affix() ?>" href="#"><?php fs_esc_html_echo( 'activate-license', $slug ) ?></a>
																	<?php endif ?>
																	<input type="submit" class="button"
																	       value="<?php fs_esc_attr_echo( 'sync-license', $slug ) ?>">
																	<input type="hidden" name="fs_action"
																	       value="<?php echo $fs->get_unique_affix() ?>_sync_license">
																	<?php wp_nonce_field( $fs->get_unique_affix() . '_sync_license' ) ?>
																	<?php if ( $show_upgrade || ! $fs->is_single_plan() ) : ?>
																	<a href="<?php echo $fs->get_upgrade_url() ?>"
																	   class="button<?php
																		   echo $show_upgrade ?
																			   ' button-primary fs-upgrade' :
																			   ' fs-change-plan'; ?> button-upgrade"><i
																			class="dashicons dashicons-cart"></i> <?php fs_esc_html_echo( $show_upgrade ? 'upgrade' : 'change-plan', $slug ) ?></a>
																	<?php endif ?>
																</form>
															<?php endif ?>
														</div>
													<?php elseif ( 'version' === $p['id'] && $fs->has_paid_plan() ) : ?>
														<?php if ( $fs->has_premium_version() ) : ?>
															<?php if ( $fs->is_premium() ) : ?>
																<label
																	class="fs-tag fs-<?php echo $fs->can_use_premium_code() ? 'success' : 'warn' ?>"><?php fs_esc_html_echo( 'premium-version', $slug ) ?></label>
															<?php elseif ( $fs->can_use_premium_code() ) : ?>
																<label class="fs-tag fs-warn"><?php fs_esc_html_echo( 'free-version', $slug ) ?></label>
															<?php endif ?>
														<?php endif ?>
													<?php endif ?>
												</td>
												<?php if ( 'plan' !== $p['id'] ) : ?>
													<td class="fs-right">
														<?php if ( 'email' === $p['id'] && ! $user->is_verified() ) : ?>
															<form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>" method="POST">
																<input type="hidden" name="fs_action" value="verify_email">
																<?php wp_nonce_field( 'verify_email' ) ?>
																<input type="submit" class="button button-small"
																       value="<?php fs_esc_attr_echo( 'verify-email', $slug ) ?>">
															</form>
														<?php endif ?>
														<?php if ( 'version' === $p['id'] ) : ?>
															<?php if ( $fs->has_release_on_freemius() ) : ?>
																<div class="button-group">
																	<?php if ( $is_paying || $fs->is_trial() ) : ?>
																		<?php if ( ! $fs->is_allowed_to_install() ) : ?>
																			<a target="_blank" class="button button-primary"
																			   href="<?php echo $fs->_get_latest_download_local_url() ?>"><?php echo sprintf( fs_text( 'download-x-version', $slug ), ( $fs->is_trial() ? $trial_plan->title : $site->plan->title ) ) . ( is_object( $update ) ? ' [' . $update->version . ']' : '' ) ?></a>
																		<?php elseif ( is_object( $update ) ) : ?>
																			<?php
																			$module_type = $fs->get_module_type();
																			?>
																			<a class="button button-primary"
																			   href="<?php echo wp_nonce_url( self_admin_url( "update.php?action=upgrade-{$module_type}&{$module_type}=" . $fs->get_plugin_basename() ), "upgrade-{$module_type}_" . $fs->get_plugin_basename() ) ?>"><?php echo fs_esc_html( 'install-update-now', $slug ) . ' [' . $update->version . ']' ?></a>
																		<?php endif ?>
																	<?php endif; ?>
																</div>
															<?php endif ?>
															<?php
														elseif ( in_array( $p['id'], array( 'license_key', 'site_secret_key' ) ) ) : ?>
															<button class="button button-small fs-toggle-visibility"><?php fs_esc_html_echo( 'show', $slug ) ?></button>
															<?php if ('license_key' === $p['id']) : ?>
																<button class="button button-small activate-license-trigger <?php echo $fs->get_unique_affix() ?>"><?php fs_esc_html_echo( 'change-license', $slug ) ?></button>
															<?php endif ?>
															<?php
														elseif (/*in_array($p['id'], array('site_secret_key', 'site_id', 'site_public_key')) ||*/
														( is_string( $user->secret_key ) && in_array( $p['id'], array(
																'email',
																'user_name'
															) ) )
														) : ?>
															<form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>" method="POST"
															      onsubmit="var val = prompt('<?php echo esc_attr( sprintf( fs_text( 'what-is-your-x', $slug ), $p['title'] ) ) ?>', '<?php echo $p['value'] ?>'); if (null == val || '' === val) return false; jQuery('input[name=fs_<?php echo $p['id'] ?>_<?php echo $fs->get_unique_affix() ?>]').val(val); return true;">
																<input type="hidden" name="fs_action" value="update_<?php echo $p['id'] ?>">
																<input type="hidden" name="fs_<?php echo $p['id'] ?>_<?php echo $fs->get_unique_affix() ?>"
																       value="">
																<?php wp_nonce_field( 'update_' . $p['id'] ) ?>
																<input type="submit" class="button button-small"
																       value="<?php fs_esc_attr_echo( 'edit', $slug ) ?>">
															</form>
														<?php endif ?>
													</td>
												<?php endif ?>
											</tr>
											<?php $odd = ! $odd;
										endforeach ?>
								</table>
							</div>
						</div>
						<script type="text/javascript">
							(function ($) {
								$('.fs-toggle-visibility').click(function () {
									var
										$this = $(this),
										$parent = $this.closest('tr'),
										$input = $parent.find('input');

									$parent.find('code').toggle();
									$input.toggle();

									if ($input.is(':visible')) {
										$this.html(<?php fs_json_encode_echo( 'hide', $slug ) ?>);
										setTimeout(function () {
											$input.select().focus();
										}, 100);
									}
									else {
										$this.html(<?php fs_json_encode_echo( 'show', $slug ) ?>);
									}
								});
							}(jQuery));

						</script>

						<?php
							$account_addons = $fs->get_account_addons();
							if ( ! is_array( $account_addons ) ) {
								$account_addons = array();
							}

							$installed_addons     = $fs->get_installed_addons();
							$installed_addons_ids = array();
							foreach ( $installed_addons as $fs_addon ) {
								$installed_addons_ids[] = $fs_addon->get_id();
							}

							$addons_to_show = array_unique( array_merge( $installed_addons_ids, $account_addons ) );
						?>
						<?php if ( 0 < count( $addons_to_show ) ) : ?>
							<!-- Add-Ons -->
							<div class="postbox">
								<div class="">
									<!--				<div class="inside">-->
									<table id="fs_addons" class="widefat">
										<thead>
										<tr>
											<th><h3><?php fs_esc_html_echo( 'add-ons', $slug ) ?></h3></th>
											<th><?php fs_esc_html_echo( 'id', $slug ) ?></th>
											<th><?php fs_esc_html_echo( 'version', $slug ) ?></th>
											<th><?php fs_esc_html_echo( 'plan', $slug ) ?></th>
											<th><?php fs_esc_html_echo( 'license', $slug ) ?></th>
											<th></th>
											<?php if ( defined( 'WP_FS__DEV_MODE' ) && WP_FS__DEV_MODE ) : ?>
												<th></th>
											<?php endif ?>
										</tr>
										</thead>
										<tbody>
										<?php $odd = true;
											foreach ( $addons_to_show as $addon_id ) : ?>
												<?php
												$addon              = $fs->get_addon( $addon_id );
												$is_addon_activated = $fs->is_addon_activated( $addon_id );
												$is_addon_connected = $fs->is_addon_connected( $addon_id );

												$fs_addon = $is_addon_connected ? freemius( $addon_id ) : false;
												if ( is_object( $fs_addon ) ) {
													$is_paying                  = $fs_addon->is_paying();
													$user                       = $fs_addon->get_user();
													$site                       = $fs_addon->get_site();
													$license                    = $fs_addon->_get_license();
													$subscription               = $fs_addon->_get_subscription();
													$plan                       = $fs_addon->get_plan();
													$is_active_subscription     = ( is_object( $subscription ) && $subscription->is_active() );
													$is_paid_trial              = $fs_addon->is_paid_trial();
													$show_upgrade               = ( ! $is_paying && ! $is_paid_trial && ! $fs_addon->_has_premium_license() );
													$is_current_license_expired = is_object( $license ) && $license->is_expired();
												}

												//					var_dump( $is_paid_trial, $license, $site, $subscription );

												?>
												<tr<?php if ( $odd ) {
													echo ' class="alternate"';
												} ?>>
													<td>
														<!-- Title -->
														<?php echo $addon->title ?>
													</td>
													<?php if ( $is_addon_connected ) : ?>
														<?php // Add-on Installed ?>
														<?php $addon_site = $fs_addon->get_site(); ?>
														<td>
															<!-- ID -->
															<?php echo $addon_site->id ?>
														</td>
														<td>
															<!-- Version -->
															<?php echo $fs_addon->get_plugin_version() ?>
														</td>
														<td>
															<!-- Plan Title -->
															<?php echo is_string( $addon_site->plan->name ) ? strtoupper( $addon_site->plan->title ) : 'FREE' ?>
														</td>
														<td>
															<!-- Expiration -->
															<?php
																$tags = array();

																if ( $fs_addon->is_trial() ) {
																	$tags[] = array( 'label' => fs_text( 'trial', $slug ), 'type' => 'success' );

																	$tags[] = array(
																		'label' => sprintf( fs_text( ( $is_paid_trial ? 'renews-in' : 'expires-in' ), $slug ), human_time_diff( time(), strtotime( $site->trial_ends ) ) ),
																		'type'  => ( $is_paid_trial ? 'success' : 'warn' )
																	);
																} else {
																	if ( is_object( $license ) ) {
																		if ( $license->is_cancelled ) {
																			$tags[] = array(
																				'label' => fs_text( 'cancelled', $slug ),
																				'type'  => 'error'
																			);
																		} else if ( $license->is_expired() ) {
																			$tags[] = array(
																				'label' => fs_text( 'expired', $slug ),
																				'type'  => 'error'
																			);
																		} else if ( $license->is_lifetime() ) {
																			$tags[] = array(
																				'label' => fs_text( 'no-expiration', $slug ),
																				'type'  => 'success'
																			);
																		} else if ( ! $is_active_subscription && ! $license->is_first_payment_pending() ) {
																			$tags[] = array(
																				'label' => sprintf( fs_text( 'expires-in', $slug ), human_time_diff( time(), strtotime( $license->expiration ) ) ),
																				'type'  => 'warn'
																			);
																		} else if ( $is_active_subscription && ! $subscription->is_first_payment_pending() ) {
																			$tags[] = array(
																				'label' => sprintf( fs_text( 'renews-in', $slug ), human_time_diff( time(), strtotime( $subscription->next_payment ) ) ),
																				'type'  => 'success'
																			);
																		}
																	}
																}

																foreach ( $tags as $t ) {
																	printf( '<label class="fs-tag fs-%s">%s</label>' . "\n", $t['type'], $t['label'] );
																}
															?>
														</td>
														<?php
														$buttons = array();
														if ( $is_addon_activated ) {
															if ( $is_paying ) {
																$buttons[] = fs_ui_get_action_button(
																	$fs->get_id(),
																	'account',
																	'deactivate_license',
																	fs_text( 'deactivate-license', $slug ),
																	array( 'plugin_id' => $addon_id ),
																	false
																);

																$human_readable_license_expiration = human_time_diff( time(), strtotime( $license->expiration ) );
																$downgrade_confirmation_message    = sprintf( fs_text( 'downgrade-x-confirm', $slug ),
																	$plan->title,
																	$human_readable_license_expiration );

																$after_downgrade_message = ! $license->is_block_features ?
																	sprintf( fs_text( 'after-downgrade-non-blocking', $slug ), $plan->title, $fs->get_module_type() ) :
																	sprintf( fs_text( 'after-downgrade-blocking', $slug ), $plan->title );

																if ( ! $license->is_lifetime() && $is_active_subscription ) {
																	$buttons[] = fs_ui_get_action_button(
																		$fs->get_id(),
																		'account',
																		'downgrade_account',
																		fs_text( 'downgrade', $slug ),
																		array( 'plugin_id' => $addon_id ),
																		false,
																		false,
																		( $downgrade_confirmation_message . ' ' . $after_downgrade_message ),
																		'POST'
																	);
																}
															} else if ( $is_paid_trial ) {
																$buttons[] = fs_ui_get_action_button(
																	$fs->get_id(),
																	'account',
																	'cancel_trial',
																	fs_text( 'cancel-trial', $slug ),
																	array( 'plugin_id' => $addon_id ),
																	false,
																	'dashicons dashicons-download',
																	fs_text( 'cancel-trial-confirm', $slug ),
																	'POST'
																);
															} else {
																$premium_license = $fs_addon->_get_available_premium_license();

																if ( is_object( $premium_license ) ) {
																	$site = $fs_addon->get_site();

																	$buttons[] = fs_ui_get_action_button(
																		$fs->get_id(),
																		'account',
																		'activate_license',
																		sprintf( fs_text( 'activate-x-plan', $slug ), $fs_addon->get_plan_title(), ( $site->is_localhost() && $premium_license->is_free_localhost ) ? '[localhost]' : ( 1 < $premium_license->left() ? $premium_license->left() . ' left' : '' ) ),
																		array(
																			'plugin_id'  => $addon_id,
																			'license_id' => $premium_license->id,
																		)
																	);
																}
															}

															if ( 0 == count( $buttons ) ) {
																// Add sync license only if non of the other CTAs are visible.
																$buttons[] = fs_ui_get_action_button(
																	$fs->get_id(),
																	'account',
																	$fs->get_unique_affix() . '_sync_license',
																	fs_text( 'sync-license', $slug ),
																	array( 'plugin_id' => $addon_id ),
																	false
																);

															}
														} else if ( ! $show_upgrade ) {
															if ( $fs->is_addon_installed( $addon_id ) ) {
																$addon_file = $fs->get_addon_basename( $addon_id );
																$buttons[]  = sprintf(
																	'<a class="button button-primary edit" href="%s" title="%s">%s</a>',
																	wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $addon_file, 'activate-plugin_' . $addon_file ),
																	fs_esc_attr( 'activate-this-addon', $slug ),
																	fs_text( 'activate', $slug )
																);
															} else {
																if ( $fs->is_allowed_to_install() ) {
																	$buttons[] = sprintf(
																		'<a class="button button-primary edit" href="%s">%s</a>',
																		wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $addon->slug ), 'install-plugin_' . $addon->slug ),
																		fs_text( 'install-now', $slug )
																	);
																} else {
																	$buttons[] = sprintf(
																		'<a target="_blank" class="button button-primary edit" href="%s">%s</a>',
																		$fs->_get_latest_download_local_url( $addon_id ),
																		fs_text( 'download-latest', $slug )
																	);
																}
															}
														}

														if ( $show_upgrade ) {
															$buttons[] = sprintf( '<a href="%s" class="thickbox button button-primary" aria-label="%s" data-title="%s"><i class="dashicons dashicons-cart"></i> %s</a>',
																esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&parent_plugin_id=' . $fs->get_id() . '&plugin=' . $addon->slug .
																                            '&TB_iframe=true&width=600&height=550' ) ),
																esc_attr( sprintf( fs_text( 'more-information-about-x', $slug ), $addon->title ) ),
																esc_attr( $addon->title ),
																fs_text( ( $fs_addon->has_free_plan() ? 'upgrade' : 'purchase' ), $slug )
															);
														}

														$buttons_count = count( $buttons );
														?>

														<td>
															<!-- Actions -->
															<?php if ($buttons_count > 1) : ?>
															<div class="button-group">
																<?php endif ?>
																<?php foreach ( $buttons as $button ) : ?>
																	<?php echo $button ?>
																<?php endforeach ?>
																<?php if ($buttons_count > 1) : ?>
															</div>
														<?php endif ?>
														</td>
													<?php else : ?>
														<?php // Add-on NOT Installed or was never connected.
														?>
														<td colspan="4">
															<!-- Action -->
															<?php if ( $fs->is_addon_installed( $addon_id ) ) : ?>
																<?php $addon_file = $fs->get_addon_basename( $addon_id ) ?>
																<a class="button button-primary"
																   href="<?php echo wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $addon_file, 'activate-plugin_' . $addon_file ) ?>"
																   title="<?php fs_esc_attr_echo( 'activate-this-addon', $slug ) ?>"
																   class="edit"><?php fs_esc_html_echo( 'activate', $slug ) ?></a>
															<?php else : ?>
																<?php if ( $fs->is_allowed_to_install() ) : ?>
																	<a class="button button-primary"
																	   href="<?php echo wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $addon->slug ), 'install-plugin_' . $addon->slug ) ?>"><?php fs_esc_html_echo( 'install-now', $slug ) ?></a>
																<?php else : ?>
																	<a target="_blank" class="button button-primary"
																	   href="<?php echo $fs->_get_latest_download_local_url( $addon_id ) ?>"><?php fs_esc_html_echo( 'download-latest', $slug ) ?></a>
																<?php endif ?>
															<?php endif ?>
														</td>
													<?php endif ?>
													<?php if ( ! $is_paying && defined( 'WP_FS__DEV_MODE' ) && WP_FS__DEV_MODE ) : ?>
														<td>
															<!-- Optional Delete Action -->
															<?php
																if ( $is_addon_activated ) {
																	fs_ui_action_button(
																		$fs->get_id(), 'account',
																		'delete_account',
																		fs_text( 'delete', $slug ),
																		array( 'plugin_id' => $addon_id ),
																		false
																	);
																}
															?>
														</td>
													<?php endif ?>
												</tr>
												<?php $odd = ! $odd;
											endforeach ?>
										</tbody>
									</table>
								</div>
							</div>
						<?php endif ?>

						<?php $fs->do_action( 'after_account_details' ) ?>

						<?php
							$view_params = array( 'id' => $VARS['id'] );
							fs_require_once_template( 'account/billing.php', $view_params );
							fs_require_once_template( 'account/payments.php', $view_params );
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
	if ( $has_tabs ) {
		$fs->_add_tabs_after_content();
	}

	$params = array(
		'page'           => 'account',
		'module_id'      => $fs->get_id(),
		'module_type'    => $fs->get_module_type(),
		'module_slug'    => $slug,
		'module_version' => $fs->get_plugin_version(),
	);
	fs_require_template( 'powered-by.php', $params );