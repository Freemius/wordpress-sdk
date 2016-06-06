<?php
/**
 *
 *
 * @package   Freemius
 * @copyright Copyright (c) 2015, Freemius, Inc.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FS_Plugin_Info_Dialog
 *
 * @author    Vova Feldman (@svovaf)
 * @since 1.1.7
 */
class FS_Plugin_Info_Dialog
{
	/**
	 *
	 *
	 * @since 1.1.7
	 * @var FS_Logger
	 */
	private $_logger;

	/**
	 *
	 *
	 * @since 1.1.7
	 * @var Freemius
	 */
	private $_fs;

	function __construct( Freemius $fs ) {
		$this->_fs = $fs;

		$this->_logger = FS_Logger::get_logger( WP_FS__SLUG . '_' . $fs->get_slug() . '_info', WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );

		// Remove default plugin information action.
		remove_all_actions( 'install_plugins_pre_plugin-information' );

		// Override action with custom plugins function for add-ons.
		add_action( 'install_plugins_pre_plugin-information', array( &$this, 'install_plugin_information' ) );

		// Override request for plugin information for Add-ons.
		add_filter(
			'fs_plugins_api',
			array( &$this, '_get_addon_info_filter' ),
		WP_FS__DEFAULT_PRIORITY, 3 );
	}

	/**
	 * Generate add-on plugin information.
	 *
	 * @author    Vova Feldman (@svovaf)
	 * @param array       $data   __comment_missing__
	 * @param string      $action __comment_missing__
	 * @param object|null $args
	 *
	 * @return array|null
	 * @since 1.0.6
	 */
	function _get_addon_info_filter( $data, $action = '', $args = null ) {
		$this->_logger->entrance();

		$parent_plugin_id = fs_request_get( 'parent_plugin_id', false );

		if ( $this->_fs->get_id() != $parent_plugin_id ||
			( 'plugin_information' !== $action ) ||
			! isset( $args->slug ) ) {
			return $data;
		}

		// Find add-on by slug.
		$addons         = $this->_fs->get_addons();
		$selected_addon = false;
		foreach ( $addons as $addon ) {
			if ( $addon->slug == $args->slug ) {
				$selected_addon = $addon;
				break;
			}
		}

		if ( false === $selected_addon ) {
			return $data;
		}

		if ( ! isset( $selected_addon->info ) ) {
			// Setup some default info.
			$selected_addon->info                  = new stdClass();
			$selected_addon->info->selling_point_0 = 'Selling Point 1';
			$selected_addon->info->selling_point_1 = 'Selling Point 2';
			$selected_addon->info->selling_point_2 = 'Selling Point 3';
			$selected_addon->info->description     = '<p>Tell your users all about your add-on</p>';
		}

		fs_enqueue_local_style( 'fs_addons', '/admin/add-ons.css' );

		$data = $args;

		$is_free = true;

		// Load add-on pricing.
		$has_pricing  = false;
		$has_features = false;
		$plans        = false;
		$plans_result = $this->_fs->get_api_site_or_plugin_scope()->get( "/addons/{$selected_addon->id}/plans.json" );
		if ( ! isset( $plans_result->error ) ) {
			$plans = $plans_result->plans;
			if ( is_array( $plans ) ) {
				for ( $i = 0, $len = count( $plans ); $i < $len; $i ++ ) {
					$plans[ $i ] = new FS_Plugin_Plan( $plans[ $i ] );
					$plan        = $plans[ $i ];

					$pricing_result = $this->_fs->get_api_site_or_plugin_scope()->get( "/addons/{$selected_addon->id}/plans/{$plan->id}/pricing.json" );
					if ( ! isset( $pricing_result->error ) ) {
						// Update plan's pricing.
						$plan->pricing = $pricing_result->pricing;

						if ( is_array( $plan->pricing ) && ! empty( $plan->pricing ) ) {
							$is_free = false;

							foreach ( $plan->pricing as &$pricing ) {
								$pricing = new FS_Pricing( $pricing );
							}
						}

						$has_pricing = true;
					}

					$features_result = $this->_fs->get_api_site_or_plugin_scope()->get( "/addons/{$selected_addon->id}/plans/{$plan->id}/features.json" );
					if ( ! isset( $features_result->error ) &&
						is_array( $features_result->features ) &&
						0 < count( $features_result->features ) ) {
						// Update plan's pricing.
						$plan->features = $features_result->features;

						$has_features = true;
					}
				}
			}
		}

		// Fetch latest version from Freemius.
		$latest = $this->_fs->_fetch_latest_version( $selected_addon->id );

		if ( ! $is_free ) {
			// If paid add-on, then it's not on wordpress.org
			$is_wordpress_org = false;
		} else {
			// If no versions found, then assume it's a .org plugin.
			$is_wordpress_org = ( false === $latest );
		}

		if ( $is_wordpress_org ) {
			$repo_data = FS_Plugin_Updater::_fetch_plugin_info_from_repository('plugin_information', (object) array(
					'slug'   => $selected_addon->slug,
					'is_ssl' => is_ssl(),
					'fields' => array(
						'banners'         => true,
						'reviews'         => true,
						'downloaded'      => false,
						'active_installs' => true,
					),
			) );

			if ( ! empty( $repo_data ) ) {
				$data                 = $repo_data;
				$data->wp_org_missing = false;
			} else {
				// Couldn't find plugin on .org.
				$is_wordpress_org = false;

				// Plugin is missing, not on Freemius nor WP.org.
				$data->wp_org_missing = true;
			}
		}

		if ( ! $is_wordpress_org ) {
			$data->checkout_link = $this->_fs->checkout_url();
			$data->fs_missing    = ( false === $latest );

			if ( $is_free ) {
				$data->download_link = $this->_fs->_get_latest_download_local_url( $selected_addon->id );
			}
		}

		if ( ! $is_wordpress_org ) {
			// Fetch as much as possible info from local files.
			$plugin_local_data = $this->_fs->get_plugin_data();
			$data->name        = $selected_addon->title;
			$data->author      = $plugin_local_data['Author'];
			$view_vars         = array( 'plugin' => $selected_addon );
			$data->sections    = array( 'description' => fs_get_template( '/plugin-info/description.php', $view_vars ) );

			if ( ! empty( $selected_addon->info->banner_url ) ) {
				$data->banners = array( 'low' => $selected_addon->info->banner_url );
			}

			if ( ! empty( $selected_addon->info->screenshots ) ) {
				$view_vars                     = array(
					'screenshots' => $selected_addon->info->screenshots,
					'plugin'      => $selected_addon,
				);
				$data->sections['screenshots'] = fs_get_template( '/plugin-info/screenshots.php', $view_vars );
			}

			if ( is_object( $latest ) ) {
				$data->version      = $latest->version;
				$data->last_updated = ! is_null( $latest->updated ) ? $latest->updated : $latest->created;
				$data->requires     = $latest->requires_platform_version;
				$data->tested       = $latest->tested_up_to_version;
			} else {
				// Add dummy version.
				$data->version = '1.0.0';

				// Add message to developer to deploy the plugin through Freemius.
			}
		}

		if ( $has_pricing ) {
			// Add plans to data.
			$data->plans = $plans;

			if ( $has_features ) {
				$view_vars                  = array(
					'plans'  => $plans,
					'plugin' => $selected_addon,
				);
				$data->sections['features'] = fs_get_template( '/plugin-info/features.php', $view_vars );
			}
		}

		$data->is_paid  = ! $is_free;
		$data->external = ! $is_wordpress_org;

		return $data;
	}

	/**
	 *
	 *
	 * @author    Vova Feldman (@svovaf)
	 * @param FS_Plugin_Plan $plan
	 *
	 * @return string
	 * @since 1.1.7
	 */
	private function get_billing_cycle( FS_Plugin_Plan $plan ) {
		$billing_cycle = null;

		if ( 1 === count( $plan->pricing ) && 1 == $plan->pricing[0]->licenses ) {
			$pricing = $plan->pricing[0];
			if ( isset( $pricing->annual_price ) ) {
				$billing_cycle = 'annual';
			} else if ( isset( $pricing->monthly_price ) ) {
					$billing_cycle = 'monthly';
			} else if ( isset( $pricing->lifetime_price ) ) {
				$billing_cycle = 'lifetime';
			}
		} else {
			foreach ( $plan->pricing as $pricing ) {
				if ( isset( $pricing->annual_price ) ) {
					$billing_cycle = 'annual';
				} else if ( isset( $pricing->monthly_price ) ) {
						$billing_cycle = 'monthly';
				} else if ( isset( $pricing->lifetime_price ) ) {
					$billing_cycle = 'lifetime';
				}

				if ( ! is_null( $billing_cycle ) ) {
					break;
				}
			}
		}

		return $billing_cycle;
	}

	/**
	 *
	 *
	 * @author    Vova Feldman (@svovaf)
	 * @param FS_Plugin_Plan $plan
	 * @param FS_Pricing     $pricing
	 *
	 * @return float|null|string
	 * @since 1.1.7
	 */
	private function get_price_tag( FS_Plugin_Plan $plan, FS_Pricing $pricing ) {
		$price_tag = '';
		if ( isset( $pricing->annual_price ) ) {
			$price_tag = $pricing->annual_price . ( $plan->is_block_features ? ' / year' : '' );
		} else if ( isset( $pricing->monthly_price ) ) {
				$price_tag = $pricing->monthly_price . ' / mo';
		} else if ( isset( $pricing->lifetime_price ) ) {
			$price_tag = $pricing->lifetime_price;
		}

		return '$' . $price_tag;
	}

	/**
	 *
	 *
	 * @author    Vova Feldman (@svovaf)
	 * @param object              $api __comment_missing__
	 * @param FS_Plugin_Plan|null $plan
	 *
	 * @return string
	 * @since 1.1.7
	 */
	private function get_plugin_cta( $api, $plan = null ) {
		if ( ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' )) ) {

			if ( ! empty( $api->checkout_link ) && isset( $api->plans ) && 0 < is_array( $api->plans ) ) {
				if ( is_null( $plan ) ) {
					$plan = $api->plans[0];
				}

				return ' <a class="button button-primary right" href="' . $this->_fs->addon_checkout_url(
					$plan->plugin_id,
					$plan->pricing[0]->id,
					$this->get_billing_cycle( $plan ),
				$plan->has_trial()) . '" target="_parent">' .
					( ! $plan->has_trial() ?
					__fs( 'purchase', $api->slug ) :
					sprintf( __fs( 'start-free-x', $api->slug ), $this->get_trial_period( $plan ) )) .
					'</a>';

				// @todo Add Cart concept.
				// echo ' <a class="button right" href="' . $status['url'] . '" target="_parent">' . __('Add to Cart') . '</a>';
			} else if ( ! empty( $api->download_link ) ) {
					$status = install_plugin_install_status( $api );

					// Hosted on WordPress.org.
				switch ( $status['status'] ) {
					case 'install':
						if ( $api->external &&
							$this->_fs->is_org_repo_compliant() ||
							! $this->_fs->is_premium() ) {
							/**
							 * Add-on hosted on Freemius, not yet installed, and core
							 * plugin is wordpress.org compliant. Therefore, require a download
							 * since installing external plugins is not allowed by the wp.org guidelines.
							 */
							return ' <a class="button button-primary right" href="' . esc_url( $api->download_link ) . '" target="_blank">' . __fs( 'download-latest', $api->slug ) . '</a>';
						} else {
							if ( $status['url'] ) {
								return '<a class="button button-primary right" href="' . $status['url'] . '" target="_parent">' . __( 'Install Now' ) . '</a>';
							}
						}
						break;
					case 'update_available':
						if ( $status['url'] ) {
							return '<a class="button button-primary right" href="' . $status['url'] . '" target="_parent">' . __( 'Install Update Now' ) . '</a>';
						}
						break;
					case 'newer_installed':
						return '<a class="button button-primary right disabled">' . sprintf( __( 'Newer Version (%s) Installed' ), $status['version'] ) . '</a>';
					break;
					case 'latest_installed':
						return '<a class="button button-primary right disabled">' . __( 'Latest Version Installed' ) . '</a>';
					break;
				}
			}
		}
	}

	/**
	 *
	 *
	 * @author    Vova Feldman (@svovaf)
	 * @param FS_Plugin_Plan $plan
	 *
	 * @return string
	 * @since 1.1.7
	 */
	private function get_trial_period( $plan ) {
		$trial_period = (int) $plan->trial_period;

		switch ( $trial_period ) {
			case 30:
			return 'month';
			case 60:
			return '2 months';
			default:
			return "{$plan->trial_period} days";
		}
	}

	/**
	 * Display plugin information in dialog box form.
	 *
	 * Based on core install_plugin_information() function.
	 *
	 * @author    Vova Feldman (@svovaf)
	 * @since 1.0.6
	 */
	function install_plugin_information() {
		global $tab;

		if ( empty( $_REQUEST['plugin'] ) ) {
			return;
		}

		$args = array(
		'slug'   => wp_unslash( $_REQUEST['plugin'] ),
			'is_ssl' => is_ssl(),
			'fields' => array(
				'banners'         => true,
				'reviews'         => true,
				'downloaded'      => false,
				'active_installs' => true,
			),
		);

		if ( is_array( $args ) ) {
			$args = (object) $args;
		}

		if ( ! isset( $args->per_page ) ) {
			$args->per_page = 24;
		}

		if ( ! isset( $args->locale ) ) {
			$args->locale = get_locale();
		}

		$api = apply_filters( 'fs_plugins_api', false, 'plugin_information', $args );

		if ( is_wp_error( $api ) ) {
			wp_die( $api );
		}

		$plugins_allowedtags = array(
			'a'       => array(
		'href'   => array(),
				'title'  => array(),
				'target' => array(),
				// Add image style for screenshots.
				'class'  => array(),
			),,
			'style'   => array(),
			'abbr'    => array( 'title' => array() ),
			'acronym' => array( 'title' => array() ),
			'code'    => array(),
			'pre'     => array(),
			'em'      => array(),
			'strong'  => array(),
			'div'     => array( 'class' => array() ),
			'span'    => array( 'class' => array() ),
			'p'       => array(),
			'ul'      => array(),
			'ol'      => array(),
			'li'      => array( 'class' => array() ),
			'i'       => array( 'class' => array() ),
			'h1'      => array(),
			'h2'      => array(),
			'h3'      => array(),
			'h4'      => array(),
			'h5'      => array(),
			'h6'      => array(),
			'img'     => array( 'src' => array(), 'class' => array(), 'alt' => array() ),
			// 'table' => array(),
			// 'td' => array(),
			// 'tr' => array(),
			// 'th' => array(),
			// 'thead' => array(),
			// 'tbody' => array(),);
			$plugins_section_titles = array(
			'description'  => _x( 'Description', 'Plugin installer section title' ),
			'installation' => _x( 'Installation', 'Plugin installer section title' ),
			'faq'          => _x( 'FAQ', 'Plugin installer section title' ),
			'screenshots'  => _x( 'Screenshots', 'Plugin installer section title' ),
			'changelog'    => _x( 'Changelog', 'Plugin installer section title' ),
			'reviews'      => _x( 'Reviews', 'Plugin installer section title' ),
			'other_notes'  => _x( 'Other Notes', 'Plugin installer section title' ),
			);

			// Sanitize HTML
			// foreach ((array) $api->sections as $section_name => $content) {
			// $api->sections[$section_name] = wp_kses($content, $plugins_allowedtags);
			// }
			foreach ( array( 'version', 'author', 'requires', 'tested', 'homepage', 'downloaded', 'slug' ) as $key ) {
				if ( isset( $api->$key ) ) {
					$api->$key = wp_kses( $api->$key, $plugins_allowedtags );
				}
			}

			// Add after $api->slug is ready.
			$plugins_section_titles['features'] = __fs( 'features-and-pricing', $api->slug );

			$_tab = esc_attr( $tab );

			$section = isset( $_REQUEST['section'] ) ? wp_unslash( $_REQUEST['section'] ) : 'description'; // Default to the Description tab, Do not translate, API returns English.
			if ( empty( $section ) || ! isset( $api->sections[ $section ] ) ) {
				$section_titles = array_keys( (array) $api->sections );
				$section        = array_shift( $section_titles );
			}

			iframe_header( __( 'Plugin Install' ) );

			$_with_banner = '';

			// var_dump($api->banners);
			if ( ! empty( $api->banners ) && ( ! empty( $api->banners['low'] ) || ! empty( $api->banners['high'] )) ) {
				$_with_banner = 'with-banner';
				$low          = empty( $api->banners['low'] ) ? $api->banners['high'] : $api->banners['low'];
				$high         = empty( $api->banners['high'] ) ? $api->banners['low'] : $api->banners['high'];
		?>
				<style type="text/css">
					#plugin-information-title.with-banner
					{
						background-image: url(<?php echo esc_url( $low ); ?>);
					}

					@media only screen and ( -webkit-min-device-pixel-ratio: 1.5 )
					{
						#plugin-information-title.with-banner
						{
							background-image: url(<?php echo esc_url( $high ); ?>);
						}
					}
				</style>
			<?php
			}

			echo '<div id="plugin-information-scrollable">';
			echo "<div id='{$_tab}-title' class='{$_with_banner}'><div class='vignette'></div><h2>{$api->name}</h2></div>";
			echo "<div id='{$_tab}-tabs' class='{$_with_banner}'>\n";

			foreach ( (array) $api->sections as $section_name => $content ) {
				if ( 'reviews' === $section_name && ( empty( $api->ratings ) || 0 === array_sum( (array) $api->ratings )) ) {
					continue;
				}

				if ( isset( $plugins_section_titles[ $section_name ] ) ) {
					$title = $plugins_section_titles[ $section_name ];
				} else {
					$title = ucwords( str_replace( '_', ' ', $section_name ) );
				}

					$class       = ( $section_name === $section ) ? ' class="current"' : '';
					$href        = add_query_arg( array( 'tab' => $tab, 'section' => $section_name ) );
					$href        = esc_url( $href );
					$san_section = esc_attr( $section_name );
					echo "\t<a name='$san_section' href='$href' $class>$title</a>\n";
			}

			echo "</div>\n";

?>
		<div id="<?php echo $_tab; ?>-content" class='<?php echo $_with_banner; ?>'>
			<div class="fyi">
			<?php if ( $api->is_paid ) : ?>
				<?php if ( isset( $api->plans ) ) : ?>
					<div class="plugin-information-pricing">
					<?php foreach ( $api->plans as $plan ) : ?>
						<?php
						/**
						 *
						 *
						 * @var FS_Plugin_Plan $plan
						 */
?>
						<?php $first_pricing = $plan->pricing[0] ?>
						<?php $is_multi_cycle = $first_pricing->is_multi_cycle() ?>
						<div class="fs-plan<?php if ( ! $is_multi_cycle ) {
							echo ' fs-single-cycle';
} ?>" data-plan-id="<?php echo $plan->id ?>">
							<h3 data-plan="<?php echo $plan->id ?>"><?php printf( __fs( 'x-plan', $api->slug ), $plan->title ) ?></h3>
							<?php $has_annual = $first_pricing->has_annual() ?>
							<?php $has_monthly = $first_pricing->has_monthly() ?>
							<div class="nav-tab-wrapper">
								<?php $billing_cycles = array( 'monthly', 'annual', 'lifetime' ) ?>
								<?php $i = 0;
								foreach ( $billing_cycles as $cycle ) : ?>
															<?php $prop = "{$cycle}_price";
															if ( isset( $first_pricing->{$prop} ) ) : ?>
																								<?php $is_featured = ( 'annual' === $cycle && $is_multi_cycle ) ?>
																								<?php
																								$prices = array();
																								foreach ( $plan->pricing as $pricing ) {
																									if ( isset( $pricing->{$prop} ) ) {
																										$prices[] = array(
																											'id'       => $pricing->id,
																											'licenses' => $pricing->licenses,
																											'price'    => $pricing->{$prop},
																										);
																									}
																								}
																					?>
																								<a class="nav-tab" data-billing-cycle="<?php echo $cycle ?>"
																			   data-pricing="<?php esc_attr_e( json_encode( $prices ) ) ?>">
																				<?php if ( $is_featured ) : ?>
													<label>&#9733; <?php _efs( 'best', $api->slug ) ?> &#9733;</label>
												<?php endif ?>
																				<?php _efs( $cycle, $api->slug ) ?>
																			</a>
																		<?php endif ?>
															<?php $i ++; endforeach ?>
								<?php wp_enqueue_script( 'jquery' ) ?>
								<script type="text/javascript">
									(function ($, undef)
{
										var
											_formatBillingFrequency = function (cycle)
	{
												switch (cycle) {
													case 'monthly':
														return '<?php printf( __fs( 'billed-x', $api->slug ), __fs( 'monthly', $api->slug ) ) ?>';
													case 'annual':
														return '<?php printf( __fs( 'billed-x', $api->slug ), __fs( 'annually', $api->slug ) ) ?>';
													case 'lifetime':
														return '<?php printf( __fs( 'billed-x', $api->slug ), __fs( 'once', $api->slug ) ) ?>';
												}
											},
											_formatLicensesTitle = function (pricing)
	{
												switch (pricing.licenses) {
													case 1:
														return '<?php _efs( 'license-single-site', $api->slug ) ?>';
													case null:
														return '<?php _efs( 'license-unlimited', $api->slug ) ?>';
													default:
														return '<?php _efs( 'license-x-sites', $api->slug ) ?>'.replace('%s', pricing.licenses);
												}
											},
											_formatPrice = function (pricing, cycle, multipleLicenses)
	{
												if (undef === multipleLicenses) multipleLicenses = true;

												var priceCycle;
												switch (cycle) {
													case 'monthly':
														priceCycle = ' / <?php _efs( 'mo', $api->slug ) ?>';
														break;
													case 'lifetime':
														priceCycle = '';
														break;
													case 'annual':
													default:
														priceCycle = ' / <?php _efs( 'year', $api->slug ) ?>';
														break;
												}

												if (!multipleLicenses && 1 == pricing.licenses) {
													return '$' + pricing.price + priceCycle;
												}

												return _formatLicensesTitle(pricing) + ' - <var class="fs-price">$' + pricing.price + priceCycle + '</var>';
											},
											_checkoutUrl = function (plan, pricing, cycle)
	{
												return '<?php echo esc_url_raw( remove_query_arg( 'billing_cycle', add_query_arg( array( 'plugin_id' => $plan->plugin_id ), $api->checkout_link ) ) ) ?>' +
												'&plan_id=' + plan +
												'&pricing_id=' + pricing +
												'&billing_cycle=' + cycle<?php if ( $plan->has_trial() ) { echo " + '&trial=true'"; }?>;
											},
											_updateCtaUrl = function (plan, pricing, cycle)
	{
												$('.plugin-information-pricing .button, #plugin-information-footer .button').attr('href', _checkoutUrl(plan, pricing, cycle));
											};

										$(document).ready(function ()
{
											var $plan = $('.plugin-information-pricing .fs-plan[data-plan-id=<?php echo $plan->id ?>]');
											$plan.find('input[type=radio]').live('click', function ()
	{
												_updateCtaUrl($plan.attr('data-plan-id'),
													$(this).val(),
													$plan.find('.nav-tab-active').attr('data-billing-cycle'));

												$plan.find('.fs-trial-terms .fs-price').html($(this).parents('label').find('.fs-price').html());
											});

											$plan.find('.nav-tab').click(function ()
{
												if ($(this) .hasClass('nav-tab-active'))
													return;

												var $this = $(this),
												    billingCycle = $this.attr('data-billing-cycle'),
												    pricing = JSON.parse($this.attr('data-pricing')),
												    $pricesList = $this.parents('.fs-plan').find('.fs-pricing-body .fs-licenses'),
												    html = '';

												// Un-select previously selected tab.
												$plan.find('.nav-tab').removeClass('nav-tab-active');

												// Select current tab.
												$this.addClass('nav-tab-active');

												// Render licenses prices.
												if (1 == pricing.length) {
													html = '<li><label><?php _efs( 'price', $api->slug ) ?>: ' + _formatPrice(pricing[0], billingCycle, false) + '</label></li>';
												} else {
													for (var i = 0; i < pricing.length; i++) {
														html += '<li><label><input name="pricing-<?php echo $plan->id ?>" type="radio" value="' + pricing[i].id + '">' + _formatPrice(pricing[i], billingCycle) + '</label></li>';
													}
												}
												$pricesList.html(html);

												if (1 < pricing.length) {
													// Select first license option.
													$pricesList.find('li:first input').click();
												} else {
													_updateCtaUrl($plan.attr('data-plan-id'),
														pricing[0].id,
														billingCycle
													);
												}

												// Update billing frequency.
												$plan.find('.fs-billing-frequency').html(_formatBillingFrequency(billingCycle));

												if ('annual' === billingCycle) {
													$plan.find('.fs-annual-discount').show();
												} else {
													$plan.find('.fs-annual-discount').hide();
												}
											});

											<?php if ( $has_annual ) : ?>
											// Select annual by default.
											$plan.find('.nav-tab[data-billing-cycle=annual]').click();
											<?php else : ?>
											// Select first tab.
											$plan.find('.nav-tab:first').click();
											<?php endif ?>
										});
									}(jQuery));
								</script>
							</div>
							<div class="fs-pricing-body">
								<span class="fs-billing-frequency"></span>
								<?php $annual_discount = ( $has_annual && $has_monthly ) ? $plan->pricing[0]->annual_discount_percentage() : 0 ?>
								<?php if ( $annual_discount > 0 ) : ?>
									<span
										class="fs-annual-discount"><?php printf( __fs( 'save-x', $api->slug ), $annual_discount . '%' ) ?></span>
								<?php endif ?>
								<ul class="fs-licenses">
								</ul>
								<?php echo $this->get_plugin_cta( $api, $plan ) ?>
								<div style="clear:both"></div>
								<?php if ( $plan->has_trial() ) : ?>
									<?php $trial_period = $this->get_trial_period( $plan ) ?>
									<ul class="fs-trial-terms">
										<li>
											<i class="dashicons dashicons-yes"></i><?php printf( __fs( 'no-commitment-x', $api->slug ), $trial_period ) ?>
										</li>
										<li>
											<i class="dashicons dashicons-yes"></i><?php printf( __fs( 'after-x-pay-as-little-y', $api->slug ), $trial_period, '<var class="fs-price">' . $this->get_price_tag( $plan, $plan->pricing[0] ) . '</var>' ) ?>
										</li>
									</ul>
								<?php endif ?>
							</div>
						</div>
						</div>
					<?php endforeach ?>
				<?php endif ?>
			<?php endif ?>
			<div>
				<h3><?php _efs( 'details', $api->slug ) ?></h3>
				<ul>
					<?php if ( ! empty( $api->version ) ) { ?>
						<li><strong><?php _e( 'Version:' ); ?></strong> <?php echo $api->version; ?></li>
					<?php
}
if ( ! empty( $api->author ) ) {
?>
<li>
<strong><?php _e( 'Author:' ); ?></strong> <?php echo links_add_target( $api->author, '_blank' ); ?>
</li>
<?php
}
if ( ! empty( $api->last_updated ) ) {
?>
<li><strong><?php _e( 'Last Updated:' ); ?></strong> <span
	title="<?php echo $api->last_updated; ?>">
<?php printf( __( '%s ago' ), human_time_diff( strtotime( $api->last_updated ) ) ); ?>
</span></li>
<?php
}
if ( ! empty( $api->requires ) ) {
?>
	<li>
		<strong><?php _e( 'Requires WordPress Version:' ); ?></strong> <?php printf( __( '%s or higher' ), $api->requires ); ?>
	</li>
<?php
}
if ( ! empty( $api->tested ) ) {
?>
			<li><strong><?php _e( 'Compatible up to:' ); ?></strong> <?php echo $api->tested; ?>
			</li>
		<?php
}
if ( ! empty( $api->downloaded ) ) {
?>
			<li>
				<strong><?php _e( 'Downloaded:' ); ?></strong> <?php printf( _n( '%s time', '%s times', $api->downloaded ), number_format_i18n( $api->downloaded ) ); ?>
			</li>
		<?php
}
if ( ! empty( $api->slug ) && empty( $api->external ) ) {
?>
			<li><a target="_blank"
				   href="https://wordpress.org/plugins/<?php echo $api->slug; ?>/"><?php _e( 'WordPress.org Plugin Page &#187;' ); ?></a>
			</li>
		<?php
}
if ( ! empty( $api->homepage ) ) {
?>
			<li><a target="_blank"
				   href="<?php echo esc_url( $api->homepage ); ?>"><?php _e( 'Plugin Homepage &#187;' ); ?></a>
			</li>
		<?php
}
if ( ! empty( $api->donate_link ) && empty( $api->contributors ) ) {
?>
			<li><a target="_blank"
				   href="<?php echo esc_url( $api->donate_link ); ?>"><?php _e( 'Donate to this plugin &#187;' ); ?></a>
			</li>
		<?php
}
						?>
				</ul>
			</div>
			<?php if ( ! empty( $api->rating ) ) { ?>
				<h3><?php _e( 'Average Rating' ); ?></h3>
				<?php wp_star_rating( array(
					'rating' => $api->rating,
					'type'   => 'percent',
					'number' => $api->num_ratings,
				) ); ?>
				<small><?php printf( _n( '(based on %s rating)', '(based on %s ratings)', $api->num_ratings ), number_format_i18n( $api->num_ratings ) ); ?></small>
			<?php
}

if ( ! empty( $api->ratings ) && array_sum( (array) $api->ratings ) > 0 ) {
	foreach ( $api->ratings as $key => $ratecount ) {
		// Avoid div-by-zero.
		$_rating = $api->num_ratings ? ( $ratecount / $api->num_ratings ) : 0;
?>
				<div class="counter-container">
			<span class="counter-label"><a
					href="https://wordpress.org/support/view/plugin-reviews/<?php echo $api->slug; ?>?filter=<?php echo $key; ?>"
					target="_blank"
					title="<?php echo esc_attr( sprintf( _n( 'Click to see reviews that provided a rating of %d star', 'Click to see reviews that provided a rating of %d stars', $key ), $key ) ); ?>"><?php printf( _n( '%d star', '%d stars', $key ), $key ); ?></a></span>
			<span class="counter-back">
				<span class="counter-bar" style="width: <?php echo 92 * $_rating; ?>px;"></span>
			</span>
					<span class="counter-count"><?php echo number_format_i18n( $ratecount ); ?></span>
				</div>
			<?php
	}
}
if ( ! empty( $api->contributors ) ) {
?>
	<h3><?php _e( 'Contributors' ); ?></h3>
	<ul class="contributors">
		<?php
		foreach ( (array) $api->contributors as $contrib_username => $contrib_profile ) {
			if ( empty( $contrib_username ) && empty( $contrib_profile ) ) {
				continue;
			}
			if ( empty( $contrib_username ) ) {
				$contrib_username = preg_replace( '/^.+\/(.+)\/?$/', '\1', $contrib_profile );
			}
			$contrib_username = sanitize_user( $contrib_username );
			if ( empty( $contrib_profile ) ) {
				echo "<li><img src='https://wordpress.org/grav-redirect.php?user={$contrib_username}&amp;s=36' width='18' height='18' />{$contrib_username}</li>";
			} else {
				echo "<li><a href='{$contrib_profile}' target='_blank'><img src='https://wordpress.org/grav-redirect.php?user={$contrib_username}&amp;s=36' width='18' height='18' />{$contrib_username}</a></li>";
			}
		}
?>
	</ul>
	<?php if ( ! empty( $api->donate_link ) ) { ?>
						<a target="_blank"
						   href="<?php echo esc_url( $api->donate_link ); ?>"><?php _e( 'Donate to this plugin &#187;' ); ?></a>
					<?php
}
	?>
<?php
}
				?>
			</div>
			<div id="section-holder" class="wrap">
	<?php
	if ( ! empty( $api->tested ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $api->tested ) ), $api->tested, '>' ) ) {
		echo '<div class="notice notice-warning"><p>' . '<strong>' . __( 'Warning:' ) . '</strong> ' . __( 'This plugin has not been tested with your current version of WordPress.' ) . '</p></div>';
	} else if ( ! empty( $api->requires ) && version_compare( substr( $GLOBALS['wp_version'], 0, strlen( $api->requires ) ), $api->requires, '<' ) ) {
		echo '<div class="notice notice-warning"><p>' . '<strong>' . __( 'Warning:' ) . '</strong> ' . __( 'This plugin has not been marked as compatible with your version of WordPress.' ) . '</p></div>';
	}

	foreach ( (array) $api->sections as $section_name => $content ) {
		$content = links_add_base_url( $content, 'https://wordpress.org/plugins/' . $api->slug . '/' );
		$content = links_add_target( $content, '_blank' );

		$san_section = esc_attr( $section_name );

		$display = ( $section_name === $section ) ? 'block' : 'none';

		if ( 'description' === $section_name &&
		( ( ! $api->external && $api->wp_org_missing ) ||
			( $api->external && $api->fs_missing ) )
		) {
			$missing_notice = array(
				'type'    => 'error',
				'id'      => md5( microtime() ),
				'message' => __fs( ( $api->is_paid ? 'paid-addon-not-deployed' : 'free-addon-not-deployed' ), $api->slug ),
			);
			fs_require_template( 'admin-notice.php', $missing_notice );
		}
		echo "\t<div id='section-{$san_section}' class='section' style='display: {$display};'>\n";
		echo $content;
		echo "\t</div>\n";
	}
		echo "</div>\n";
		echo "</div>\n";
		echo "</div>\n"; // #plugin-information-scrollable
		echo "<div id='$tab-footer'>\n";

		echo $this->get_plugin_cta( $api );

		echo "</div>\n";

		iframe_footer();
		exit;
	}
}
