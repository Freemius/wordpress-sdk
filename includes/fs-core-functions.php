<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	global $fs_core_logger;

	$fs_core_logger = FS_Logger::get_logger( WP_FS__SLUG . '_core', WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );

	if ( ! function_exists( 'fs_dummy' ) ) {
		function fs_dummy() {
		}
	}

	/* Url.
	--------------------------------------------------------------------------------------------*/
	function fs_get_url_daily_cache_killer() {
		return date( '\YY\Mm\Dd' );
	}

	/* Templates / Views.
	--------------------------------------------------------------------------------------------*/
	if ( ! function_exists( 'fs_get_template_path' ) ) {
		function fs_get_template_path( $path ) {
			return WP_FS__DIR_TEMPLATES . '/' . trim( $path, '/' );
		}

		function fs_include_template( $path, &$params = null ) {
			$VARS = &$params;
			include( fs_get_template_path( $path ) );
		}

		function fs_include_once_template( $path, &$params = null ) {
			$VARS = &$params;
			include_once( fs_get_template_path( $path ) );
		}

		function fs_require_template( $path, &$params = null ) {
			$VARS = &$params;
			require( fs_get_template_path( $path ) );
		}

		function fs_require_once_template( $path, &$params = null ) {
			$VARS = &$params;
			require_once( fs_get_template_path( $path ) );
		}

		function fs_get_template( $path, &$params = null ) {
			ob_start();

			$VARS = &$params;
			require_once( fs_get_template_path( $path ) );

			return ob_get_clean();
		}
	}

	/* Scripts and styles including.
	--------------------------------------------------------------------------------------------*/

	/**
	 * Generates an absolute URL to the given path. This function ensures that the URL will be correct whether the asset
	 * is inside a plugin's folder or a theme's folder.
	 *
	 * Examples:
	 * 1. "themes" folder
	 *    Path: C:/xampp/htdocs/fswp/wp-content/themes/twentytwelve/freemius/assets/css/admin/common.css
	 *    URL: http://fswp:8080/wp-content/themes/twentytwelve/freemius/assets/css/admin/common.css
	 *
	 * 2. "plugins" folder
	 *    Path: C:/xampp/htdocs/fswp/wp-content/plugins/rating-widget-premium/freemius/assets/css/admin/common.css
	 *    URL: http://fswp:8080/wp-content/plugins/rating-widget-premium/freemius/assets/css/admin/common.css
	 *
	 * @author Leo Fajardo (@leorw)
	 * @since  1.2.2
	 *
	 * @param  string $asset_abs_path Asset's absolute path.
	 *
	 * @return string Asset's URL.
	 */
	function fs_asset_url( $asset_abs_path ) {
		global $fs_core_logger;

		$wp_content_dir = fs_normalize_path( WP_CONTENT_DIR );
		$asset_abs_path = fs_normalize_path( $asset_abs_path );
		$asset_rel_path = str_replace( $wp_content_dir, '', $asset_abs_path );

		$asset_url = content_url( fs_normalize_path( $asset_rel_path ) );

		if ( $fs_core_logger->is_on() ) {
			$fs_core_logger->info( 'content_dir = ' . $wp_content_dir );
			$fs_core_logger->info( 'asset_abs_path = ' . $asset_abs_path );
			$fs_core_logger->info( 'asset_rel_path = ' . $asset_rel_path );
			$fs_core_logger->info( 'asset_url = ' . $asset_url );
		}

		return $asset_url;
	}

	function fs_enqueue_local_style( $handle, $path, $deps = array(), $ver = false, $media = 'all' ) {
		global $fs_core_logger;

		if ( $fs_core_logger->is_on() ) {
			$fs_core_logger->info( 'handle = ' . $handle . '; path = ' . $path . ';' );
		}

		wp_enqueue_style( $handle, fs_asset_url( WP_FS__DIR_CSS . '/' . trim( $path, '/' )  ), $deps, $ver, $media );
	}

	function fs_enqueue_local_script( $handle, $path, $deps = array(), $ver = false, $in_footer = 'all' ) {
		global $fs_core_logger;
		if ( $fs_core_logger->is_on() ) {
			$fs_core_logger->info( 'handle = ' . $handle . '; path = ' . $path . ';' );
			$fs_core_logger->info( 'plugin_basename = ' . plugins_url( WP_FS__DIR_JS . trim( $path, '/' ) ) );
			$fs_core_logger->info( 'plugins_url = ' . plugins_url( plugin_basename( WP_FS__DIR_JS . '/' . trim( $path, '/' ) ) ) );
		}

		wp_enqueue_script( $handle, fs_asset_url( WP_FS__DIR_JS . '/' . trim( $path, '/' ) ), $deps, $ver, $in_footer );
	}

	function fs_img_url( $path, $img_dir = WP_FS__DIR_IMG ) {
		return ( fs_asset_url( $img_dir . '/' . trim( $path, '/' ) ) );
	}

	/* Request handlers.
	--------------------------------------------------------------------------------------------*/
	/**
	 * @param string $key
	 * @param mixed  $def
	 *
	 * @return mixed
	 */
	function fs_request_get( $key, $def = false ) {
		return isset( $_REQUEST[ $key ] ) ? $_REQUEST[ $key ] : $def;
	}

	function fs_request_has( $key ) {
		return isset( $_REQUEST[ $key ] );
	}

	function fs_request_get_bool( $key, $def = false ) {
		if ( ! isset( $_REQUEST[ $key ] ) ) {
			return $def;
		}

		if ( 1 == $_REQUEST[ $key ] || 'true' === strtolower( $_REQUEST[ $key ] ) ) {
			return true;
		}

		if ( 0 == $_REQUEST[ $key ] || 'false' === strtolower( $_REQUEST[ $key ] ) ) {
			return false;
		}

		return $def;
	}

	function fs_request_is_post() {
		return ( 'post' === strtolower( $_SERVER['REQUEST_METHOD'] ) );
	}

	function fs_request_is_get() {
		return ( 'get' === strtolower( $_SERVER['REQUEST_METHOD'] ) );
	}

	function fs_get_action( $action_key = 'action' ) {
		if ( ! empty( $_REQUEST[ $action_key ] ) ) {
			return strtolower( $_REQUEST[ $action_key ] );
		}

		if ( 'action' == $action_key ) {
			$action_key = 'fs_action';

			if ( ! empty( $_REQUEST[ $action_key ] ) ) {
				return strtolower( $_REQUEST[ $action_key ] );
			}
		}

		return false;
	}

	function fs_request_is_action( $action, $action_key = 'action' ) {
		return ( strtolower( $action ) === fs_get_action( $action_key ) );
	}

	function fs_is_plugin_page( $menu_slug ) {
		return ( is_admin() && isset( $_REQUEST['page'] ) && $_REQUEST['page'] === $menu_slug );
	}

	/* Core UI.
	--------------------------------------------------------------------------------------------*/
	/**
	 * @param number      $module_id
	 * @param string      $page
	 * @param string      $action
	 * @param string      $title
	 * @param array       $params
	 * @param bool        $is_primary
	 * @param string|bool $icon_class   Optional class for an icon (since 1.1.7).
	 * @param string|bool $confirmation Optional confirmation message before submit (since 1.1.7).
	 * @param string      $method       Since 1.1.7
	 *
	 * @uses fs_ui_get_action_button()
	 */
	function fs_ui_action_button(
		$module_id,
		$page,
		$action,
		$title,
		$params = array(),
		$is_primary = true,
		$icon_class = false,
		$confirmation = false,
		$method = 'GET'
	) {
		echo fs_ui_get_action_button(
			$module_id,
			$page,
			$action,
			$title,
			$params,
			$is_primary,
			$icon_class,
			$confirmation,
			$method
		);
	}

	/**
	 * @author Vova Feldman (@svovaf)
	 * @since  1.1.7
	 *
	 * @param number      $module_id
	 * @param string      $page
	 * @param string      $action
	 * @param string      $title
	 * @param array       $params
	 * @param bool        $is_primary
	 * @param string|bool $icon_class   Optional class for an icon.
	 * @param string|bool $confirmation Optional confirmation message before submit.
	 * @param string      $method
	 *
	 * @return string
	 */
	function fs_ui_get_action_button(
		$module_id,
		$page,
		$action,
		$title,
		$params = array(),
		$is_primary = true,
		$icon_class = false,
		$confirmation = false,
		$method = 'GET'
	) {
		// Prepend icon (if set).
		$title = ( is_string( $icon_class ) ? '<i class="' . $icon_class . '"></i> ' : '' ) . $title;

		if ( is_string( $confirmation ) ) {
			return sprintf( '<form action="%s" method="%s"><input type="hidden" name="fs_action" value="%s">%s<a href="#" class="%s" onclick="if (confirm(\'%s\')) this.parentNode.submit(); return false;">%s</a></form>',
				freemius( $module_id )->_get_admin_page_url( $page, $params ),
				$method,
				$action,
				wp_nonce_field( $action, '_wpnonce', true, false ),
				'button' . ( $is_primary ? ' button-primary' : '' ),
				$confirmation,
				$title
			);
		} else if ( 'GET' !== strtoupper( $method ) ) {
			return sprintf( '<form action="%s" method="%s"><input type="hidden" name="fs_action" value="%s">%s<a href="#" class="%s" onclick="this.parentNode.submit(); return false;">%s</a></form>',
				freemius( $module_id )->_get_admin_page_url( $page, $params ),
				$method,
				$action,
				wp_nonce_field( $action, '_wpnonce', true, false ),
				'button' . ( $is_primary ? ' button-primary' : '' ),
				$title
			);
		} else {
			return sprintf( '<a href="%s" class="%s">%s</a></form>',
				wp_nonce_url( freemius( $module_id )->_get_admin_page_url( $page, array_merge( $params, array( 'fs_action' => $action ) ) ), $action ),
				'button' . ( $is_primary ? ' button-primary' : '' ),
				$title
			);
		}
	}

	function fs_ui_action_link( $module_id, $page, $action, $title, $params = array() ) {
		?><a class=""
		     href="<?php echo wp_nonce_url( freemius( $module_id )->_get_admin_page_url( $page, array_merge( $params, array( 'fs_action' => $action ) ) ), $action ) ?>"><?php echo $title ?></a><?php
	}

	/*function fs_error_handler($errno, $errstr, $errfile, $errline)
	{
		if (false === strpos($errfile, 'freemius/'))
		{
			// @todo Dump Freemius errors to local log.
		}

//		switch ($errno) {
//			case E_USER_ERROR:
//				break;
//			case E_WARNING:
//			case E_USER_WARNING:
//				break;
//			case E_NOTICE:
//			case E_USER_NOTICE:
//				break;
//			default:
//				break;
//		}
	}

	set_error_handler('fs_error_handler');*/

	function fs_nonce_url( $actionurl, $action = - 1, $name = '_wpnonce' ) {
//		$actionurl = str_replace( '&amp;', '&', $actionurl );
		return add_query_arg( $name, wp_create_nonce( $action ), $actionurl );
	}

	if ( ! function_exists( 'fs_starts_with' ) ) {
		/**
		 * Check if string starts with.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @param string $haystack
		 * @param string $needle
		 *
		 * @return bool
		 */
		function fs_starts_with( $haystack, $needle ) {
			$length = strlen( $needle );

			return ( substr( $haystack, 0, $length ) === $needle );
		}
	}

	#region Url Canonization ------------------------------------------------------------------

	if ( ! function_exists( 'fs_canonize_url' ) ) {
		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @param string $url
		 * @param bool   $omit_host
		 * @param array  $ignore_params
		 *
		 * @return string
		 */
		function fs_canonize_url( $url, $omit_host = false, $ignore_params = array() ) {
			$parsed_url = parse_url( strtolower( $url ) );

//		if ( ! isset( $parsed_url['host'] ) ) {
//			return $url;
//		}

			$canonical = ( ( $omit_host || ! isset( $parsed_url['host'] ) ) ? '' : $parsed_url['host'] ) . $parsed_url['path'];

			if ( isset( $parsed_url['query'] ) ) {
				parse_str( $parsed_url['query'], $queryString );
				$canonical .= '?' . fs_canonize_query_string( $queryString, $ignore_params );
			}

			return $canonical;
		}
	}

	if ( ! function_exists( 'fs_canonize_query_string' ) ) {
		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @param array $params
		 * @param array $ignore_params
		 * @param bool  $params_prefix
		 *
		 * @return string
		 */
		function fs_canonize_query_string( array $params, array &$ignore_params, $params_prefix = false ) {
			if ( ! is_array( $params ) || 0 === count( $params ) ) {
				return '';
			}

			// Url encode both keys and values
			$keys   = fs_urlencode_rfc3986( array_keys( $params ) );
			$values = fs_urlencode_rfc3986( array_values( $params ) );
			$params = array_combine( $keys, $values );

			// Parameters are sorted by name, using lexicographical byte value ordering.
			// Ref: Spec: 9.1.1 (1)
			uksort( $params, 'strcmp' );

			$pairs = array();
			foreach ( $params as $parameter => $value ) {
				$lower_param = strtolower( $parameter );

				// Skip ignore params.
				if ( in_array( $lower_param, $ignore_params ) ||
				     ( false !== $params_prefix && fs_starts_with( $lower_param, $params_prefix ) )
				) {
					continue;
				}

				if ( is_array( $value ) ) {
					// If two or more parameters share the same name, they are sorted by their value
					// Ref: Spec: 9.1.1 (1)
					natsort( $value );
					foreach ( $value as $duplicate_value ) {
						$pairs[] = $lower_param . '=' . $duplicate_value;
					}
				} else {
					$pairs[] = $lower_param . '=' . $value;
				}
			}

			if ( 0 === count( $pairs ) ) {
				return '';
			}

			return implode( "&", $pairs );
		}
	}

	if ( ! function_exists( 'fs_urlencode_rfc3986' ) ) {
		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @param string|string[] $input
		 *
		 * @return array|mixed|string
		 */
		function fs_urlencode_rfc3986( $input ) {
			if ( is_array( $input ) ) {
				return array_map( 'fs_urlencode_rfc3986', $input );
			} else if ( is_scalar( $input ) ) {
				return str_replace( '+', ' ', str_replace( '%7E', '~', rawurlencode( $input ) ) );
			}

			return '';
		}
	}

	#endregion Url Canonization ------------------------------------------------------------------

	/**
	 * @author Vova Feldman (@svovaf)
	 *
	 * @since 1.2.2 Changed to usage of WP_Filesystem_Direct.
	 *
	 * @param string $from URL
	 * @param string $to   File path.
	 */
	function fs_download_image( $from, $to ) {
		$dir = dirname( $to );

		if ( 'direct' !== get_filesystem_method( array(), $dir ) ) {
			return;
		}

		$fs      = new WP_Filesystem_Direct( '' );
		$tmpfile = download_url( $from );
		$fs->copy( $tmpfile, $to );
		$fs->delete( $tmpfile );
	}

	/* General Utilities
	--------------------------------------------------------------------------------------------*/

	/**
	 * Sorts an array by the value of the priority key.
	 *
	 * @author Daniel Iser (@danieliser)
	 * @since  1.1.7
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	function fs_sort_by_priority( $a, $b ) {

		// If b has a priority and a does not, b wins.
		if ( ! isset( $a['priority'] ) && isset( $b['priority'] ) ) {
			return 1;
		} // If b has a priority and a does not, b wins.
		elseif ( isset( $a['priority'] ) && ! isset( $b['priority'] ) ) {
			return - 1;
		} // If neither has a priority or both priorities are equal its a tie.
		elseif ( ( ! isset( $a['priority'] ) && ! isset( $b['priority'] ) ) || $a['priority'] === $b['priority'] ) {
			return 0;
		}

		// If both have priority return the winner.
		return ( $a['priority'] < $b['priority'] ) ? - 1 : 1;
	}

	/**
	 * VERY IMPORTANT ----------------------------------------------
	 *
	 * @todo IMPORTANT - After merging to main branch rename _efs() to fs_echo() and __fs() to fs_translate(). Otherwise, if a there's a plugin that runs version < 1.2.2 some of the translation in the plugin dialog will not be translated correctly.
	 *
	 * VERY IMPORTANT ----------------------------------------------
	 */
	if ( ! function_exists( '__fs' ) ) {
		global $fs_text_overrides;

		if ( ! isset( $fs_text_overrides ) ) {
			$fs_text_overrides = array();
		}

		/**
		 * Retrieve a translated text by key.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.4
		 *
		 * @param string $key
		 * @param string $slug
		 *
		 * @return string
		 *
		 * @global       $fs_text , $fs_text_overrides
		 */
		function __fs( $key, $slug = 'freemius' ) {
			global $fs_text, $fs_module_info_text, $fs_text_overrides;

			if ( isset( $fs_text_overrides[ $slug ] ) ) {
				if ( isset( $fs_text_overrides[ $slug ][ $key ] ) ) {
					return $fs_text_overrides[ $slug ][ $key ];
				}

				$lower_key = strtolower( $key );
				if ( isset( $fs_text_overrides[ $slug ][ $lower_key ] ) ) {
					return $fs_text_overrides[ $slug ][ $lower_key ];
				}
			}

			if ( ! isset( $fs_text ) ) {
				require_once( ( defined( 'WP_FS__DIR_INCLUDES' ) ? WP_FS__DIR_INCLUDES : dirname( __FILE__ ) ) . '/i18n.php' );
			}

			if ( isset( $fs_text[ $key ] ) ) {
				return $fs_text[ $key ];
			}

			if ( isset( $fs_module_info_text[ $key ] ) ) {
				return $fs_module_info_text[ $key ];
			}

			return $key;
		}

		/**
		 * Display a translated text by key.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.4
		 *
		 * @param string $key
		 * @param string $slug
		 */
		function _efs( $key, $slug = 'freemius' ) {
			echo __fs( $key, $slug );
		}
	}

	if ( ! function_exists( 'fs_override_i18n' ) ) {
		/**
		 * Override default i18n text phrases.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.6
		 *
		 * @param string[] $key_value
		 * @param string   $slug
		 *
		 * @global         $fs_text_overrides
		 */
		function fs_override_i18n( array $key_value, $slug = 'freemius' ) {
			global $fs_text_overrides;

			if ( ! isset( $fs_text_overrides[ $slug ] ) ) {
				$fs_text_overrides[ $slug ] = array();
			}

			foreach ( $key_value as $key => $value ) {
				$fs_text_overrides[ $slug ][ $key ] = $value;
			}
		}
	}