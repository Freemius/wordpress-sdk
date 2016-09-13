<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.1.4
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 */
	$slug = $VARS['slug'];
	$fs   = freemius( $slug );

	/**
	 * @since 1.1.7.5
	 */
	$local_path = $fs->apply_filters( 'plugin_icon', false );

	if ( is_string( $local_path ) ) {
		$icons = array( $local_path );
	} else {
		global $fs_active_plugins;

		$img_dir = WP_FS__DIR_IMG;

		if ( 1 < count( $fs_active_plugins->plugins ) ) {
			foreach ( $fs_active_plugins->plugins as $sdk_path => &$data ) {
				if ( $data->plugin_path == $fs->get_plugin_basename() ) {
					$img_dir = WP_PLUGIN_DIR . '/' . $sdk_path . '/assets/img';
					break;
				}
			}
		}

		$icons = glob( fs_normalize_path( $img_dir . '/' . $slug . '.*' ) );
		if ( ! is_array( $icons ) || 0 === count( $icons ) ) {
			$icon_found             = false;
			$local_path             = fs_normalize_path( $img_dir . '/' . $slug . '.png' );
			$have_write_permissions = is_writable( fs_normalize_path( $img_dir ) );

			if ( WP_FS__IS_LOCALHOST && $fs->is_org_repo_compliant() && $have_write_permissions ) {
				/**
				 * IMPORTANT: THIS CODE WILL NEVER RUN AFTER THE PLUGIN IS IN THE REPO.
				 *
				 * This code will only be executed once during the testing
				 * of the plugin in a local environment. The plugin icon file WILL
				 * already exist in the assets folder when the plugin is deployed to
				 * the repository.
				 */
				$suffixes = array(
					'-128x128.png',
					'-128x128.jpg',
					'-256x256.png',
					'-256x256.jpg',
					'.svg',
				);

				$base_url = 'https://plugins.svn.wordpress.org/' . $slug . '/assets/icon';

				foreach ( $suffixes as $s ) {
					$headers = get_headers( $base_url . $s );
					if ( strpos( $headers[0], '200' ) ) {
						$local_path = fs_normalize_path( $img_dir . '/' . $slug . '.' . substr( $s, strpos( $s, '.' ) + 1 ) );
						fs_download_image( $base_url . $s, $local_path );
						$icon_found = true;
						break;
					}
				}
			}

			if ( ! $icon_found ) {
				// No icons found, fallback to default icon.
				if ( $have_write_permissions ) {
					// If have write permissions, copy default icon.
					copy( fs_normalize_path( $img_dir . '/plugin-icon.png' ), $local_path );
				} else {
					// If doesn't have write permissions, use default icon path.
					$local_path = fs_normalize_path( $img_dir . '/plugin-icon.png' );
				}
			}

			$icons = array( $local_path );
		}
	}

	$icon_dir     = dirname( $icons[0] );
	$relative_url = fs_img_url( substr( $icons[0], strlen( $icon_dir ) ), $icon_dir );
?>
<div class="fs-plugin-icon">
	<img src="<?php echo $relative_url ?>" width="80" height="80" />
</div>