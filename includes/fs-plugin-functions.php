<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.6
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Display plugin information in dialog box form.
	 *
	 * @since 2.7.0
	 */
	function fs_install_plugin_information() {
		global $tab;

		if ( empty( $_REQUEST['plugin'] ) ) {
			return;
		}

		$args = array(
			'slug'   => wp_unslash( $_REQUEST['plugin'] ),
			'is_ssl' => is_ssl(),
			'fields' => array( 'banners' => true, 'reviews' => true )
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
				'class'  => array()
			),
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
//			'table' => array(),
//			'td' => array(),
//			'tr' => array(),
//			'th' => array(),
//			'thead' => array(),
//			'tbody' => array(),
		);

		$plugins_section_titles = array(
			'description'  => _x( 'Description', 'Plugin installer section title' ),
			'installation' => _x( 'Installation', 'Plugin installer section title' ),
			'faq'          => _x( 'FAQ', 'Plugin installer section title' ),
			'screenshots'  => _x( 'Screenshots', 'Plugin installer section title' ),
			'changelog'    => _x( 'Changelog', 'Plugin installer section title' ),
			'reviews'      => _x( 'Reviews', 'Plugin installer section title' ),
			'other_notes'  => _x( 'Other Notes', 'Plugin installer section title' ),
			'features'     => __fs( 'features-and-pricing' ),
		);

		// Sanitize HTML
//		foreach ( (array) $api->sections as $section_name => $content ) {
//			$api->sections[$section_name] = wp_kses( $content, $plugins_allowedtags );
//		}

		foreach ( array( 'version', 'author', 'requires', 'tested', 'homepage', 'downloaded', 'slug' ) as $key ) {
			if ( isset( $api->$key ) ) {
				$api->$key = wp_kses( $api->$key, $plugins_allowedtags );
			}
		}

		$_tab = esc_attr( $tab );

		$section = isset( $_REQUEST['section'] ) ? wp_unslash( $_REQUEST['section'] ) : 'description'; // Default to the Description tab, Do not translate, API returns English.
		if ( empty( $section ) || ! isset( $api->sections[ $section ] ) ) {
			$section_titles = array_keys( (array) $api->sections );
			$section        = array_shift( $section_titles );
		}

		iframe_header( __( 'Plugin Install' ) );

		$_with_banner = '';

//	var_dump($api->banners);
		if ( ! empty( $api->banners ) && ( ! empty( $api->banners['low'] ) || ! empty( $api->banners['high'] ) ) ) {
			$_with_banner = 'with-banner';
			$low          = empty( $api->banners['low'] ) ? $api->banners['high'] : $api->banners['low'];
			$high         = empty( $api->banners['high'] ) ? $api->banners['low'] : $api->banners['high'];
			?>
			<style type="text/css">
				#plugin-information-title.with-banner
				{
					background-image: url( <?php echo esc_url( $low ); ?> );
				}

				@media only screen and ( -webkit-min-device-pixel-ratio: 1.5 )
				{
					#plugin-information-title.with-banner
					{
						background-image: url( <?php echo esc_url( $high ); ?> );
					}
				}
			</style>
		<?php
		}

		echo '<div id="plugin-information-scrollable">';
		echo "<div id='{$_tab}-title' class='{$_with_banner}'><div class='vignette'></div><h2>{$api->name}</h2></div>";
		echo "<div id='{$_tab}-tabs' class='{$_with_banner}'>\n";

		foreach ( (array) $api->sections as $section_name => $content ) {
			if ( 'reviews' === $section_name && ( empty( $api->ratings ) || 0 === array_sum( (array) $api->ratings ) ) ) {
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
			<?php if ( isset( $api->plans ) ) : ?>
				<div class="plugin-information-pricing">
					<?php foreach ($api->plans as $plan) : ?>
					<h3 data-plan="<?php echo $plan->id ?>"><?php printf( __fs( 'x-plan' ), $plan->title ) ?></h3>
					<ul>
						<?php $billing_cycle = 'annual' ?>
						<?php if ( 1 === count( $plan->pricing ) && 1 == $plan->pricing[0]->licenses ) : ?>
							<?php $pricing = $plan->pricing[0] ?>
							<li><label><?php _efs( 'price' ) ?>: $<?php
										if ( isset( $pricing->annual_price ) ) {
											echo $pricing->annual_price . ( $plan->is_block_features ? ' / year' : '' );
											$billing_cycle = 'annual';
										} else if ( isset( $pricing->monthly_price ) ) {
											echo $pricing->monthly_price . ' / mo';
											$billing_cycle = 'monthly';
										} else if ( isset( $pricing->lifetime_price ) ) {
											echo $pricing->lifetime_price;
											$billing_cycle = 'lifetime';
										}
									?></label></li>
						<?php else : ?>
							<?php $first = true;
							foreach ( $plan->pricing as $pricing ) : ?>
								<li><label><input name="pricing-<?php echo $plan->id ?>" type="radio"
								                  value="<?php echo $pricing->id ?>"<?php checked( $first, true ) ?>><?php
											switch ( $pricing->licenses ) {
												case '1':
													_efs( 'license-single-site' );
													break;
												case null:
													_efs( 'license-unlimited' );
													break;
												default:
													printf( __fs( 'license-x-sites' ), $pricing->licenses );
													break;
											}
										?> - $<?php
											if ( isset( $pricing->annual_price ) ) {
												echo $pricing->annual_price . ( $plan->is_block_features ? ' / year' : '' );
												$billing_cycle = 'annual';
											} else if ( isset( $pricing->monthly_price ) ) {
												echo $pricing->monthly_price . ' / mo';
												$billing_cycle = 'monthly';
											} else if ( isset( $pricing->lifetime_price ) ) {
												echo $pricing->lifetime_price;
												$billing_cycle = 'lifetime';
											}
										?></label></li>
								<?php $first = false; endforeach ?>
						<?php endif ?>
					</ul>
					<?php echo ' <a class="button button-primary right" href="' . esc_url( add_query_arg( array(
							'plugin_id'     => $plan->plugin_id,
							'plan_id'       => $plan->id,
							'pricing_id'    => $plan->pricing[0]->id,
							'billing_cycle' => $billing_cycle,
						), $api->checkout_link ) ) . '" target="_parent">' . __fs( 'purchase' ) . '</a>' ?>
				</div>
			<?php endforeach ?>
			<?php wp_enqueue_script( 'jquery' ); ?>
				<script type="text/javascript">
					(function ($) {
						$('.plugin-information-pricing input[type=radio]').click(function () {
							var checkout_url = '<?php echo esc_url_raw(add_query_arg(array(
								'plugin_id' => $plan->plugin_id,
								'billing_cycle' => $billing_cycle,
							), $api->checkout_link)) ?>&plan_id=' +
								$(this).parents('.plugin-information-pricing').find('h3').attr('data-plan') +
								'&pricing_id=' + $(this).val();

							$('.plugin-information-pricing .button, #plugin-information-footer .button').attr('href', checkout_url);
						});
					})(jQuery);
				</script>
			<?php endif ?>
			<div>
				<h3><?php _efs( 'details' ) ?></h3>
				<ul>
					<?php if ( ! empty( $api->version ) ) { ?>
						<li><strong><?php _e( 'Version:' ); ?></strong> <?php echo $api->version; ?></li>
					<?php }
						if ( ! empty( $api->author ) ) { ?>
							<li>
								<strong><?php _e( 'Author:' ); ?></strong> <?php echo links_add_target( $api->author, '_blank' ); ?>
							</li>
						<?php }
						if ( ! empty( $api->last_updated ) ) { ?>
							<li><strong><?php _e( 'Last Updated:' ); ?></strong> <span
									title="<?php echo $api->last_updated; ?>">
				<?php printf( __( '%s ago' ), human_time_diff( strtotime( $api->last_updated ) ) ); ?>
			</span></li>
						<?php }
						if ( ! empty( $api->requires ) ) { ?>
							<li>
								<strong><?php _e( 'Requires WordPress Version:' ); ?></strong> <?php printf( __( '%s or higher' ), $api->requires ); ?>
							</li>
						<?php }
						if ( ! empty( $api->tested ) ) { ?>
							<li><strong><?php _e( 'Compatible up to:' ); ?></strong> <?php echo $api->tested; ?></li>
						<?php }
						if ( ! empty( $api->downloaded ) ) { ?>
							<li>
								<strong><?php _e( 'Downloaded:' ); ?></strong> <?php printf( _n( '%s time', '%s times', $api->downloaded ), number_format_i18n( $api->downloaded ) ); ?>
							</li>
						<?php }
						if ( ! empty( $api->slug ) && empty( $api->external ) ) { ?>
							<li><a target="_blank"
							       href="https://wordpress.org/plugins/<?php echo $api->slug; ?>/"><?php _e( 'WordPress.org Plugin Page &#187;' ); ?></a>
							</li>
						<?php }
						if ( ! empty( $api->homepage ) ) { ?>
							<li><a target="_blank"
							       href="<?php echo esc_url( $api->homepage ); ?>"><?php _e( 'Plugin Homepage &#187;' ); ?></a>
							</li>
						<?php }
						if ( ! empty( $api->donate_link ) && empty( $api->contributors ) ) { ?>
							<li><a target="_blank"
							       href="<?php echo esc_url( $api->donate_link ); ?>"><?php _e( 'Donate to this plugin &#187;' ); ?></a>
							</li>
						<?php } ?>
				</ul>
			</div>
			<?php if ( ! empty( $api->rating ) ) { ?>
				<h3><?php _e( 'Average Rating' ); ?></h3>
				<?php wp_star_rating( array(
						'rating' => $api->rating,
						'type'   => 'percent',
						'number' => $api->num_ratings
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
					<?php } ?>
				<?php } ?>
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

			echo "\t<div id='section-{$san_section}' class='section' style='display: {$display};'>\n";
			echo $content;
			echo "\t</div>\n";
		}
	echo "</div>\n";
	echo "</div>\n";
	echo "</div>\n"; // #plugin-information-scrollable
	echo "<div id='$tab-footer'>\n";
	if ( ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) ) {

		if ( ! empty( $api->checkout_link ) && isset( $api->plans ) && 0 < is_array( $api->plans ) ) {
			echo ' <a class="button button-primary right" href="' . esc_url( add_query_arg( array(
					'plugin_id'     => $plan->plugin_id,
					'plan_id'       => $plan->id,
					'pricing_id'    => $plan->pricing[0]->id,
					'billing_cycle' => $billing_cycle,
				), $api->checkout_link ) ) . '" target="_parent">' . __fs( 'purchase' ) . '</a>';

			// @todo Add Cart concept.
//			echo ' <a class="button right" href="' . $status['url'] . '" target="_parent">' . __( 'Add to Cart' ) . '</a>';

		} else if ( ! empty( $api->download_link ) ) {
			$status = install_plugin_install_status( $api );
			switch ( $status['status'] ) {
				case 'install':
					if ( $status['url'] ) {
						echo '<a class="button button-primary right" href="' . $status['url'] . '" target="_parent">' . __( 'Install Now' ) . '</a>';
					}
					break;
				case 'update_available':
					if ( $status['url'] ) {
						echo '<a class="button button-primary right" href="' . $status['url'] . '" target="_parent">' . __( 'Install Update Now' ) . '</a>';
					}
					break;
				case 'newer_installed':
					echo '<a class="button button-primary right disabled">' . sprintf( __( 'Newer Version (%s) Installed' ), $status['version'] ) . '</a>';
					break;
				case 'latest_installed':
					echo '<a class="button button-primary right disabled">' . __( 'Latest Version Installed' ) . '</a>';
					break;
			}
		}
	}
	echo "</div>\n";

	iframe_footer();
	exit;
}
