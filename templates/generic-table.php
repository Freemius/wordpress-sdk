<?php
	if ( ! function_exists( 'fs_debug_generate_table' ) ) {
		function fs_debug_generate_table( $data, $options = array() ) {
			$styles = [
				'default' => [ '#ffffff', '#f6f7f7' ],
				'success' => [ '#dfffdf', '#b3ffb3' ],
				'info'    => [ '#d9d9ff', '#b3b3ff' ],
				'warning' => [ '#fff5cc', '#ffeb99' ],
				'error'   => [ '#ffcccc', '#ff9999' ],
			];

			$is_hidden = isset( $options['hidden'] ) ? $options['hidden'] : false;

			$output = '<div class="container-fluid">';

			$output .= '<h2>';

			$output .= '<a href="javascript:;" onclick="toggleTableVisibility(this)">' . ($is_hidden ? '▶' : '▼') . '</a> ';

			if (isset($options['title'])) {
				$output .= esc_html($options['title']);
			}

			$output .= '</h2>';

			$output .= '<table class="widefat' . ($is_hidden ? ' hidden' : '') . '"';

			if ( isset( $data['attributes'] ) ) {
				foreach ( $data['attributes'] as $key => $value ) {
					$output .= ' ' . $key . '="' . $value . '"';
				}
			}

			$output .= '>';

			if ( isset( $data['headers'] ) ) {
				$output .= '<thead><tr>';

				foreach ( $data['headers'] as $header ) {
					$output .= '<th>' . $header['val'] . '</th>';
				}
				$output .= '</tr></thead>';
			}

			if ( isset( $data['data'] ) ) {
				$output    .= '<body>';
				$row_count = 0;

				foreach ( $data['data'] as $row ) {
					$style_name   = isset( $row['row_style'] ) ? $row['row_style'] : 'default';
					$style        = isset( $styles[ $style_name ] ) ? $styles[ $style_name ] : $styles['default'];
					$row_bg_color = $style[ $row_count % 2 ];
					$output       .= '<tr style="background: ' . $row_bg_color . '">';
					if ( isset( $data['headers'] ) ) {
						foreach ( $data['headers'] as $header ) {
							if ( isset( $row[ $header['key'] ] ) ) {
								$output .= '<td>' . fs_debug_render_cell( $row[ $header['key'] ] ) . '</td>';
							}
						}
					} else {
						foreach ( $row as $value ) {
							$output .= '<td>' . fs_debug_render_cell( $value ) . '</td>';
						}
					}
					$output .= '</tr>';
					$row_count ++;
				}
				$output .= '</tbody>';
			}
			$output .= '</table></div>';

			$output .= "<script>
                window.toggleTableVisibility = function(button) {
                    button.innerText = button.innerText === '▶' ? '▼' : '▶';
                    const table = button.closest('.container-fluid').querySelector('table');
                    table.classList.toggle('hidden');
                }
                </script>";


			$output .= '<hr/>';

			return $output;
		}
	}

	if ( ! function_exists( 'fs_debug_render_link' ) ) {
		function fs_debug_render_link( $element ) {
			$classes = '';
			if ( is_array( $element['classes'] ) ) {
				$classes = join( ' ', $element['classes'] );
			}
			$output = '<a class="' . $classes . '" href="' . $element['href'] . '">';
			$output .= $element['label'];
			$output .= '</a>';

			return $output;
		}
	}

	if ( ! function_exists( 'fs_debug_render_button' ) ) {
		function fs_debug_render_button( $element ) {
			$output = '<form action="" method="POST">';

			// Sanitize the 'fs_action' attribute before embedding it in HTML
			$fs_action_sanitized = esc_attr( $element['fs_action'] );
			$output              .= '<input type="hidden" name="fs_action" value="' . $fs_action_sanitized . '">';

			// Generating the nonce, which is already sanitized by WordPress through wp_nonce_field
			$output .= wp_nonce_field( $fs_action_sanitized, 'wpnonce', true, false );

			foreach ( $element as $key => $value ) {
				if ( in_array( $key, array( 'fs_action', 'label', 'classes' ) ) || is_null( $value ) ) {
					continue;
				}
				// Sanitize attribute names and values before embedding them in HTML
				$key_sanitized   = esc_attr( $key );
				$value_sanitized = esc_attr( $value );
				$output          .= '<input type="hidden" name="' . $key_sanitized . '" value="' . $value_sanitized . '">';
			}

			// Sanitize the button content (label) before embedding it in HTML
			$label_sanitized = esc_html( $element['label'] );
			$classes         = '';
			if ( is_array( $element['classes'] ) ) {
				$classes = join( ' ', $element['classes'] );
			}

			$output .= '<button type="submit" class="' . $classes . '">' . $label_sanitized . '</button>';
			$output .= '</form>';

			return $output;
		}
	}

	if ( ! function_exists( 'fs_debug_render_cell' ) ) {
		function fs_debug_render_cell( $raw_value ) {
			$output = '';
			if ( is_array( $raw_value ) ) {
				foreach ( $raw_value as $element ) {
					if ( isset( $element['fs_action'] ) ) {
						$output .= fs_debug_render_button( $element );
					} else if ( isset( $element['href'] ) ) {
						$output .= fs_debug_render_link( $element );
					}
				}
			} else {
				$output = $raw_value;
			}

			return $output;
		}
	}

	if ( ! function_exists( 'fs_debug_format_time' ) ) {
		/**
		 * @param $time
		 *
		 * @return string
		 */
		function fs_debug_format_time( $time ) {
			if ( is_numeric( $time ) ) {
				$sec_text   = fs_text_x_inline( 'sec', 'seconds' );
				$in_x_text  = fs_text_inline( 'In %s', 'in-x' );
				$x_ago_text = fs_text_inline( '%s ago', 'x-ago' );
				$diff       = abs( WP_FS__SCRIPT_START_TIME - $time );
				$human_diff = ( $diff < MINUTE_IN_SECONDS ) ?
					$diff . ' ' . $sec_text :
					human_time_diff( WP_FS__SCRIPT_START_TIME, $time );

				return esc_html( sprintf(
					( ( WP_FS__SCRIPT_START_TIME < $time ) ?
						$in_x_text :
						$x_ago_text ),
					$human_diff
				) );
			}

			return '';
		}
	}

	if ( ! function_exists( 'fs_debug_get_plan_name' ) ) {
		function fs_debug_get_plan_name( $site, $slug, $module_type, $all_plans, $fs_options ) {
			$plan_name = '';
			if ( FS_Plugin_Plan::is_valid_id( $site->plan_id ) ) {
				if ( false === $all_plans ) {
					$option_name = 'plans';
					if ( WP_FS__MODULE_TYPE_PLUGIN !== $module_type ) {
						$option_name = $module_type . '_' . $option_name;
					}

					$all_plans = fs_get_entities( $fs_options->get_option( $option_name, array() ),
						FS_Plugin_Plan::get_class_name() );
				}

				foreach ( $all_plans[ $slug ] as $plan ) {
					$plan_id = Freemius::_decrypt( $plan->id );

					if ( $site->plan_id == $plan_id ) {
						$plan_name = Freemius::_decrypt( $plan->name );
						break;
					}
				}
			}

			return $plan_name;
		}
	}

	if ( ! function_exists( 'fs_debug_get_secret_key' ) ) {
		function fs_debug_get_secret_key( $site, $module_type, $slug ) {
			$plugin_storage = FS_Storage::instance( $module_type, $slug );

			return $plugin_storage->is_whitelabeled ?
				FS_Plugin_License::mask_secret_key_for_html( $site->secret_key ) :
				esc_html( $site->secret_key );
		}
	}
