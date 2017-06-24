<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.2.2.7
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Class FS_Customizer_Upsell_Control
	 */
	class FS_Customizer_Upsell_Control extends WP_Customize_Control {

		/**
		 * Control type
		 *
		 * @var string control type
		 */
		public $type = 'freemius-upsell-control';

		/**
		 * @var Freemius
		 */
		public $freemius = null;

		/**
		 * @param WP_Customize_Manager  $manager the customize manager class.
		 * @param string                $id      id.
		 * @param array                 $args    customizer manager parameters.
		 */
		public function __construct( WP_Customize_Manager $manager, $id, array $args ) {
			$manager->register_control_type( 'FS_Customizer_Upsell_Control' );

			parent::__construct( $manager, $id, $args );
		}

		/**
		 * Enqueue resources for the control.
		 */
		public function enqueue() {
			fs_enqueue_local_style('fs_customizer', 'customizer.css');
		}

		/**
		 * Json conversion
		 */
		public function to_json() {
			$pricing_cta = esc_html($this->freemius->get_text( $this->freemius->get_pricing_cta_label() )) . '&nbsp;&nbsp;' . ( is_rtl() ? '&#x2190;' : '&#x27a4;' );

			parent::to_json();

			$this->json['button_text']        = $pricing_cta;
			$this->json['button_url']         = $this->freemius->get_upgrade_url();

			// Load features.
			$pricing = $this->freemius->get_api_plugin_scope()->get( 'pricing.json' );

			if ($this->freemius->is_api_result_object($pricing, 'plans')){

			}

			$this->json['plans']            = $pricing->plans;

			$this->json['strings']            = array(
				'plan' => $this->freemius->get_text('plan'),
			);
		}

		/**
		 * Control content
		 */
		public function content_template() {
			?>
			<div id="fs_customizer_upsell">
				<# if ( data.plans ) { #>
					<ul class="fs-customizer-plans">
						<# for (i in data.plans) { #>
							<# if ( 'free' != data.plans[i].name || (null != data.plans[i].features && 0 < data.plans[i].features.length) ) { #>
								<li class="fs-customizer-plan">
									<div class="fs-accordion-section-open">
										<h2 class="fs-accordion-section-title menu-item">
											<span>{{ data.plans[i].title }}</span>
											<button type="button" class="button-link item-edit" aria-expanded="true">
												<span class="screen-reader-text">Toggle section: {{ data.plans[i].title }} {{ data.strings.plan }}</span>
												<span class="toggle-indicator" aria-hidden="true"></span>
											</button>
										</h2>
										<div class="fs-accordion-section-content">
											<# if ( data.plans[i].description ) { #>
												<h3>{{ data.plans[i].description }}</h3>
											<# } #>
											<# if ( data.plans[i].features ) { #>
												<ul>
													<# for ( j in data.plans[i].features ) { #>
														<li><span class="dashicons dashicons-yes"></span><span><# if ( data.plans[i].features[j].value ) { #>{{ data.plans[i].features[j].value }} <# } #>{{ data.plans[i].features[j].title }}</span></li>
														<# } #>
												</ul>
												<# } #>
													<# if ( 'free' != data.plans[i].name ) { #>
														<a href="{{ data.button_url }}" class="button button-primary" target="_blank">{{{ data.button_text }}}</a>
														<# } #>
										</div>
									</div>
								</li>
							<# } #>
						<# } #>
					</ul>
				<# } #>
			</div>
		<?php }
	}