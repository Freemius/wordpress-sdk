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
	 * @var FS_Plugin[]
	 */
	$addons = $fs->get_addons();
?>

	<div id="fs_addons" class="wrap">
		<h2><?php printf(__('Add Ons for %s', WP_FS__SLUG), $fs->get_plugin_name()) ?></h2>
		<div id="poststuff">
			<ul class="fs-cards-list">
				<?php foreach ($addons as $addon) : ?>
					<li class="fs-card">
						<?php
							echo sprintf( '<a href="%s" class="thickbox fs-overlay" aria-label="%s" data-title="%s"></a>',
								esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&parent_plugin_id=' . $fs->get_id() . '&plugin=' . $addon->slug .
								                            '&TB_iframe=true&width=600&height=550' ) ),
								esc_attr( sprintf( __( 'More information about %s' ), $addon->title ) ),
								esc_attr( $addon->title )
							);
						?>
<!--						<a href="http://fswp:8080/wp-admin/plugin-install.php?tab=plugin-information&plugin=hello-dolly&TB_iframe=true&width=772&height=903" class="fs-overlay" target="_self"></a>-->
						<div class="fs-inner">
							<ul>
								<li class="fs-card-banner" style="background-image: url('<?php echo $addon->info->card_banner_url ?>');"></li>
								<li class="fs-title"><?php echo $addon->title ?></li>
								<li class="fs-offer">
									<span class="fs-price">$39.99</span>
								</li>
								<li class="fs-description"><?php echo !empty($addon->info->short_description) ? $addon->info->short_description : 'SHORT DESCRIPTION' ?></li>
							</ul>
						</div>
					</li>
				<?php endforeach ?>
			</ul>
		</div>
	</div>
<?php fs_require_template('powered-by.php') ?>