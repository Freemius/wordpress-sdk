<?php
    /**
     * @package     Freemius
     * @copyright   Copyright (c) 2015, Freemius, Inc.
     * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since       1.2.4
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    /**
     * @var array $VARS
     */
    $available_license = $VARS['license'];
    $premium_plan      = $VARS['plan'];
    $slug              = $VARS['slug'];

    $activate_plan_text = fs_text_inline( 'Activate %s Plan', 'activate-x-plan', $slug );
?>
<form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>" method="POST">
    <input type="hidden" name="fs_action" value="activate_license">
    <?php wp_nonce_field( 'activate_license' ) ?>
    <input type="hidden" name="license_id" value="<?php echo $available_license->id ?>">
    <?php if ( ! empty( $VARS['install_id'] ) ) : ?>
        <input type="hidden" name="install_id" value="<?php echo $VARS['install_id'] ?>">
    <?php elseif ( ! empty( $VARS['blog_id'] ) ) : ?>
        <input type="hidden" name="blog_id" value="<?php echo $VARS['blog_id'] ?>">
    <?php endif ?>
    <input type="submit" class="button<?php echo ! empty( $VARS['class'] ) ? ' ' . $VARS['class'] : '' ?>"
           value="<?php echo esc_attr( sprintf(
               $activate_plan_text . '%s',
               $premium_plan->title,
               ( $VARS['is_localhost'] && $available_license->is_free_localhost ) ?
                   ' [' . fs_text_inline( 'Localhost', 'localhost', $slug ) . ']' :
                   ( $available_license->is_single_site() ?
                       '' :
                       ' [' . ( 1 < $available_license->left() ?
                           sprintf( fs_text_x_inline( '%s left', 'as 5 licenses left', 'x-left', $slug ), $available_license->left() ) :
                           strtolower( fs_text_inline( 'Last license', 'last-license', $slug ) ) ) . ']'
                   )
           ) ) ?> ">
</form>