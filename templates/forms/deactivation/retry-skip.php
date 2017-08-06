<?php
    /**
     * @package     Freemius
     * @copyright   Copyright (c) 2015, Freemius, Inc.
     * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since       1.2.0
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    /**
     * @var array $VARS
     */
    $fs   = freemius( $VARS['id'] );
    $slug = $fs->get_slug();

    $skip_url  = fs_nonce_url( $fs->_get_admin_page_url( '', array( 'fs_action' => $fs->get_unique_affix() . '_skip_activation' ) ), $fs->get_unique_affix() . '_skip_activation' );
    $skip_text = strtolower( fs_text( 'skip', $slug ) );
    $use_plugin_anonymously_text = fs_text( 'click-here-to-use-plugin-anonymously', $slug );

    echo sprintf( fs_text( 'dont-have-to-share-any-data', $slug ), "<a href='{$skip_url}'>{$skip_text}</a>" )
            . " <a href='{$skip_url}' class='button button-small button-secondary'>{$use_plugin_anonymously_text}</a>";