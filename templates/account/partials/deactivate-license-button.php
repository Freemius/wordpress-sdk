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
    $slug = $VARS['slug'];
?>
<form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>" method="POST">
    <input type="hidden" name="fs_action" value="deactivate_license">
    <?php wp_nonce_field( 'deactivate_license' ) ?>
    <?php if ( ! empty( $VARS['install_id'] ) && FS_Site::is_valid_id( $VARS['install_id'] ) ) : ?>
        <input type="hidden" name="install_id" value="<?php echo $VARS['install_id'] ?>">
    <?php endif ?>
    <button type="button"
            class="fs-deactivate-license button<?php echo ! empty( $VARS['class'] ) ? ' ' . $VARS['class'] : '' ?>"><?php fs_echo_inline( 'Deactivate License', 'deactivate-license', $slug ) ?></button>
</form>