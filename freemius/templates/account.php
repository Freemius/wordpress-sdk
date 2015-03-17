<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.3
	 */

	$slug = $VARS['slug'];
	/**
	 * @var Freemius
	 */
	$fs = fs($slug);

	/**
	 * @var FS_Plugin_Tag
	 */
	$update = $fs->get_update();
?>

<div class="wrap">
	<h2 class="nav-tab-wrapper">
		<a href="<?php $fs->get_account_url() ?>" class="nav-tab nav-tab-active"><?php _e('Account', WP_FS__SLUG) ?></a>
		<?php if ($fs->is_not_paying()) : ?>
			<a href="<?php echo $fs->get_upgrade_url() ?>" class="nav-tab"><?php _e('Upgrade', WP_FS__SLUG) ?></a>
		<?php endif ?>
	</h2>
	<div id="poststuff">
		<div id="fs_account_settings">
			<div class="has-sidebar has-right-sidebar">
				<div class="has-sidebar-content">
					<div class="postbox">
						<h3><?php _e('Account Details', WP_FS__SLUG) ?></h3>
						<div class="fs-header-actions">
							<ul>
								<li>
									<form action="" method="POST">
										<input type="hidden" name="fs_action" value="delete_account">
										<?php wp_nonce_field('delete_account') ?>
										<a href="#" onclick="if (confirm('<?php _e('Are you sure you want to delete the account?', WP_FS__SLUG) ?>')) this.parentNode.submit(); return false;"><?php _e('Delete Account', WP_FS__SLUG) ?></a>
									</form>
								</li>
								<?php if ($fs->is_paying__fs__()) : ?>
									<li>
										&nbsp;•&nbsp;
										<form action="" method="POST">
											<input type="hidden" name="fs_action" value="deactivate_license">
											<?php wp_nonce_field('deactivate_license') ?>
											<a href="#" onclick="if (confirm('<?php _e('Deactivating your license will block all premium features, but will enable you to activate the license on another site. Are you sure you want to proceed?', WP_FS__SLUG) ?>')) this.parentNode.submit(); return false;"><?php _e('Deactivate License', WP_FS__SLUG) ?></a>
										</form>
									</li>
									<li>
										&nbsp;•&nbsp;
										<form action="" method="POST">
											<input type="hidden" name="fs_action" value="downgrade_account">
											<?php wp_nonce_field('downgrade_account') ?>
											<a href="#" onclick="if (confirm('<?php _e('Downgrading your plan will automatically stop all recurring payments and will immediately change your plan to Free. Are you sure you want to proceed?', WP_FS__SLUG) ?>')) this.parentNode.submit(); return false;"><?php _e('Downgrade', WP_FS__SLUG) ?></a>
										</form>
									</li>
									<li>
										&nbsp;•&nbsp;
										<a href="<?php echo $fs->get_upgrade_url() ?>"><?php _e('Change Plan', WP_FS__SLUG) ?></a>
									</li>
								<?php endif ?>
							</ul>
						</div>
						<div class="inside">
							<table cellspacing="0">
								<?php
									$profile = array();
									$user = $fs->get_user();
									$site = $fs->get_site();
									$name = $user->get_name();
									$profile[] = array('id' => 'user_name', 'title' => __('Name', WP_FS__SLUG), 'value' => $name);
									if (isset($user->email) && false !== strpos($user->email, '@'))
										$profile[] = array('id' => 'email', 'title' => __('Email', WP_FS__SLUG), 'value' => $user->email);
									if (is_numeric($user->id))
										$profile[] = array('id' => 'user_id', 'title' => __('User ID', WP_FS__SLUG), 'value' => $user->id);

									$profile[] = array('id' => 'site_id', 'title' => __('Site ID', WP_FS__SLUG), 'value' => is_string($site->id) ? $site->id : 'No ID');

									$profile[] = array('id' => 'site_public_key', 'title' => __('Public Key', WP_FS__SLUG), 'value' => $site->public_key);

									$profile[] = array('id' => 'site_secret_key', 'title' => __('Secret Key', WP_FS__SLUG), 'value' => ((is_string($site->secret_key)) ? $site->secret_key : __('No Secret', WP_FS__SLUG)));

									$profile[] = array('id' => 'plan', 'title' => __('Plan', WP_FS__SLUG), 'value' => is_string($site->plan->name) ? strtoupper($site->plan->title) : 'FREE');

									$profile[] = array('id' => 'version', 'title' => __('Version', WP_FS__SLUG), 'value' => $fs->get_plugin_version());
								?>
								<?php $odd = true; foreach ($profile as $p) : ?>
									<tr class="fs-field-<?php echo $p['id'] ?><?php if ($odd) :?> alternate<?php endif ?>">
										<td>
											<nobr><?php echo $p['title'] ?>:</nobr>
										</td>
										<td>
											<code><?php echo htmlspecialchars($p['value']) ?></code>
											<?php if ('email' === $p['id'] && !$user->is_verified()) : ?>
												<label class=""><?php _e('not verified', WP_FS__SLUG) ?></label>
											<?php endif ?>
										</td>
										<td class="fs-right">
											<?php if ('email' === $p['id'] && !$user->is_verified()) : ?>
												<form action="" method="POST">
													<input type="hidden" name="fs_action" value="verify_email">
													<?php wp_nonce_field('verify_email') ?>
													<input type="submit" class="button button-small" value="<?php _e('Verify Email', WP_FS__SLUG) ?>">
												</form>
											<?php endif ?>
											<?php if ('plan' === $p['id']) : ?>
												<div class="button-group">
													<?php if ( $fs->is_not_paying() ) : ?>
														<?php $license = $fs->_get_premium_license() ?>
															<?php if (false !== $license && ($license->left() > 0 || ($site->is_localhost() && $license->is_free_localhost))) : ?>
															<form action="" method="POST">
																<?php $plan = $fs->_get_plan_by_id($license->plan_id) ?>
																<input type="hidden" name="fs_action" value="activate_license">
																<?php wp_nonce_field('activate_license') ?>
																<input type="submit" class="button button-primary" value="<?php printf( __('Activate %s Plan', WP_FS__SLUG), $plan->title, ($site->is_localhost() && $license->is_free_localhost) ? '[localhost]' : (1 < $license->left() ? $license->left() . ' left' : '' )) ?> ">
															</form>
															<?php else : ?>
															<form action="" method="POST" class="button-group">
																<input type="submit" class="button" value="<?php _e('Sync License', WP_FS__SLUG) ?>">
																<input type="hidden" name="fs_action" value="sync_license">
																<?php wp_nonce_field('sync_license') ?>
																<a href="<?php echo $fs->get_upgrade_url() ?>" class="button button-primary button-upgrade"><?php _e('Upgrade', WP_FS__SLUG) ?></a>
															</form>
															<?php endif ?>
													<?php endif; ?>
												</div>
											<?php elseif ('version' === $p['id']) : ?>
												<div class="button-group">
													<?php if ( $fs->is_paying__fs__() ) : ?>
														<?php if (is_object($update) || !$fs->is_premium()) : ?>
															<form action="" method="POST" class="button-group">
																<input type="submit" class="button button-primary" value="<?php echo is_object($update) ? sprintf( __('Download Update [%1s]', WP_FS__SLUG), $update->version) : sprintf( __('Download %1s Version', WP_FS__SLUG), $site->plan->title) ?>">
																<input type="hidden" name="fs_action" value="download_latest">
																<?php wp_nonce_field('download_latest') ?>
															</form>
															<!--															<form action="" method="POST" class="button-group">-->
															<!--																<input type="hidden" name="fs_action" value="install_latest">-->
															<!--																--><?php //wp_nonce_field('install_latest') ?>
															<!--																<input type="submit" class="button button-primary" value="--><?php //_e('Update Now', WP_FS__SLUG) ?><!--">-->
															<!--															</form>-->
														<?php else : ?>
															<form action="" method="POST" class="button-group">
																<input type="hidden" name="fs_action" value="check_updates">
																<?php wp_nonce_field('check_updates') ?>
																<input type="submit" class="button" value="<?php _e('Check Updates', WP_FS__SLUG) ?>">
															</form>
														<?php endif ?>
													<?php endif; ?>
												</div>
											<?php elseif (/*in_array($p['id'], array('site_secret_key', 'site_id', 'site_public_key')) ||*/ (is_string($user->secret_key) && in_array($p['id'], array('email', 'user_name'))) ) : ?>
												<form action="" method="POST" onsubmit="var val = prompt('<?php echo __('What is your', WP_FS__SLUG) . ' ' . $p['title'] . '?' ?>', '<?php echo $p['value'] ?>'); if (null == val || '' === val) return false; jQuery('input[name=fs_<?php echo $p['id'] ?>_<?php echo $slug ?>]').val(val); return true;">
													<input type="hidden" name="fs_action" value="update_<?php echo $p['id'] ?>">
													<input type="hidden" name="fs_<?php echo $p['id'] ?>_<?php echo $slug ?>" value="">
													<?php wp_nonce_field('update_' . $p['id']) ?>
													<input type="submit" class="button button-small" value="<?php _e('Edit', WP_FS__SLUG) ?>">
												</form>
											<?php endif ?>
										</td>
									</tr>
									<?php $odd = !$odd; endforeach ?>
							</table>
						</div>
					</div>

					<?php $fs->do_action( 'fs_after_account_details' ) ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php fs_require_template('powered-by.php') ?>