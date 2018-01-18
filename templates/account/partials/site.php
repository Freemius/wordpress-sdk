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
     * @var array             $VARS
     * @var Freemius          $fs
     * @var FS_Plugin_License $main_license
     */
    $fs            = $VARS['freemius'];
    $slug          = $fs->get_slug();
    $site          = $VARS['site'];
    $main_license  = $VARS['license'];
    $has_paid_plan = $fs->has_paid_plan();
    $is_premium    = $fs->is_premium();
    $main_user     = $fs->get_user();
    $blog_id       = $site['blog_id'];

    $install       = $VARS['install'];
    $is_registered = ! empty( $install );
    $license       = null;
    $trial_plan    = $fs->get_trial_plan();
    $free_text     = fs_text_inline( 'Free', 'free', $slug );
?>
    <tr class="fs-site-details" data-blog-id="<?php echo $blog_id ?>"<?php if ( $is_registered ) : ?> data-install-id="<?php echo $install->id ?>"<?php endif ?>>
        <!-- Install ID or Opt-in option -->
        <td><?php if ( $is_registered ) : ?>
                <?php echo $install->id ?>
            <?php else : ?>
                <?php $action = 'opt_in' ?>
                <form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>" method="POST">
                    <input type="hidden" name="fs_action" value="<?php echo $action ?>">
                    <?php wp_nonce_field( trim( "{$action}:{$blog_id}", ':' ) ) ?>
                    <input type="hidden" name="blog_id" value="<?php echo $blog_id ?>">
                    <button class="fs-opt-in button button-small"><?php fs_esc_html_echo_inline( 'Opt In', 'opt-in', $slug ) ?></button>
                </form>
            <?php endif ?>
        </td>
        <!--/ Install ID or Opt-in option -->

        <!-- Site URL -->
        <td class="fs-field-url fs-main-column"><?php echo fs_strip_url_protocol( $site['url'] ) ?></td>
        <!--/ Site URL -->

        <!-- License Activation / Deactivation -->
        <td><?php if ( $has_paid_plan && $is_premium ) {
                $view_params = array(
                    'freemius' => $fs,
                    'slug'     => $slug,
                    'blog_id'  => $blog_id,
                    'class'    => 'button-small',
                );

                $license = null;
                if ( $is_registered ) {
                    $view_params['install_id']   = $install->id;
                    $view_params['is_localhost'] = $install->is_localhost();

                    $has_license = FS_Plugin_License::is_valid_id( $install->license_id );
                    $license     = $has_license ?
                        $fs->_get_license_by_id( $install->license_id ) :
                        null;
                } else {
                    $view_params['is_localhost'] = FS_Site::is_localhost_by_address( $site['url'] );
                }

                if ( is_object( $license ) ) {
                    $view_params['license'] = $license;

                    // Show license deactivation button.
                    fs_require_template( 'account/partials/deactivate-license-button.php', $view_params );
                } else {
                    if ( is_object( $main_license ) && $main_license->can_activate( $view_params['is_localhost'] ) ) {
                        // Main license is available for activation.
                        $available_license = $main_license;
                    } else {
                        // Try to find any available license for activation.
                        $available_license = $fs->_get_available_premium_license( $view_params['is_localhost'] );
                    }

                    if ( is_object( $available_license ) ) {
                        $premium_plan = $fs->_get_plan_by_id( $available_license->plan_id );

                        $view_params['license'] = $available_license;
                        $view_params['class'] .= ' button-primary';
                        $view_params['plan'] = $premium_plan;

                        fs_require_template( 'account/partials/activate-license-button.php', $view_params );
                    }
                }
            } ?></td>
        <!--/ License Activation / Deactivation -->

        <!-- Plan -->
        <td><?php if ( $is_registered ) : ?>
                <?php
                if ( ! $has_paid_plan ) {
                    $plan_title = $free_text;
                } else {
                    if ( $install->is_trial() ) {
                        if ( $trial_plan->id == $install->trial_plan_id ) {
                            $plan_title = is_string( $trial_plan->name ) ?
                                strtoupper( $trial_plan->title ) :
                                fs_text_inline( 'Trial', 'trial', $slug );
                        } else {
                            $plan_title = fs_text_inline( 'Trial', 'trial', $slug );
                        }
                    } else {
                        $plan       = $fs->_get_plan_by_id( $install->plan_id );
                        $plan_title = strtoupper( is_string( $plan->title ) ?
                            $plan->title :
                            strtoupper( $free_text )
                        );
                    }
                }
                ?>
                <code><?php echo $plan_title ?></code>
            <?php endif ?></td>
        <!--/ Plan -->

        <!-- More details button -->
        <td><?php if ( $is_registered ) : ?>
                <button class="fs-show-install-details button button-small">More details <i
                        class="dashicons dashicons-arrow-right-alt2"></i>
                </button><?php endif ?></td>
        <!--/ More details button -->
    </tr>
<?php if ( $is_registered ) : ?>
    <!-- More details -->
    <tr class="fs-install-details" data-install-id="<?php echo $install->id ?>" style="display: none">
        <td colspan="5">
            <table class="widefat fs-key-value-table">
                <tbody>
                <?php $row_index = 0 ?>
                <!-- Blog ID -->
                <tr <?php if ( 1 == $row_index % 2 ) {
                    echo ' class="alternate"';
                } ?>>
                    <td>
                        <nobr><?php fs_esc_html_echo_inline( 'Blog ID', 'blog-id', $slug ) ?>:</nobr>
                    </td>
                    <td><code><?php echo $blog_id ?></code></td>
                    <?php if ( ! FS_Plugin_License::is_valid_id( $install->license_id ) ) : ?>

                        <!-- Toggle Usage Tracking -->
                        <td>
                            <?php $action = 'toggle_tracking' ?>
                            <form action="<?php echo $fs->_get_admin_page_url( 'account' ) ?>" method="POST">
                                <input type="hidden" name="fs_action" value="<?php echo $action ?>">
                                <?php wp_nonce_field( trim( "{$action}:{$blog_id}:{$install->id}", ':' ) ) ?>
                                <input type="hidden" name="install_id" value="<?php echo $install->id ?>">
                                <input type="hidden" name="blog_id" value="<?php echo $blog_id ?>">
                                <button class="fs-toggle-tracking button button-small<?php if ( $install->is_disconnected ) {
                                    echo ' button-primary';
                                } ?>" data-is-disconnected="<?php echo $install->is_disconnected ? 'true' : 'false' ?>"><?php $install->is_disconnected ? fs_esc_html_echo_inline( 'Opt In', 'opt-in', $slug ) : fs_esc_html_echo_inline( 'Opt Out', 'opt-out', $slug ) ?></button>
                            </form>
                        </td>
                        <!--/ Toggle Usage Tracking -->

                    <?php endif ?>
                </tr>
                <?php $row_index ++ ?>
                <!--/ Blog ID -->

                <?php if ( $is_registered && $install->user_id != $main_user->id ) : ?>
                    <?php
                    /**
                     * @var FS_User $user
                     */
                    $user = Freemius::_get_user_by_id( $install->user_id ) ?>
                    <?php if ( is_object( $user ) ) : ?>
                        <!-- User Name -->
                        <tr <?php if ( 1 == $row_index % 2 ) {
                            echo ' class="alternate"';
                        } ?>>
                            <td>
                                <nobr><?php fs_esc_html_echo_inline( 'Owner Name', 'owner-name', $slug ) ?>:</nobr>
                            </td>
                            <td><code><?php echo htmlspecialchars( $user->get_name() ) ?></code></td>
                        </tr>
                        <?php $row_index ++ ?>
                        <!--/ User Name -->

                        <!-- User Email -->
                        <tr <?php if ( 1 == $row_index % 2 ) {
                            echo ' class="alternate"';
                        } ?>>
                            <td>
                                <nobr><?php fs_esc_html_echo_inline( 'Owner Email', 'owner-email', $slug ) ?>:</nobr>
                            </td>
                            <td><code><?php echo htmlspecialchars( $user->email ) ?></code></td>
                        </tr>
                        <?php $row_index ++ ?>
                        <!--/ User Email -->

                        <!-- User ID -->
                        <tr <?php if ( 1 == $row_index % 2 ) {
                            echo ' class="alternate"';
                        } ?>>
                            <td>
                                <nobr><?php fs_esc_html_echo_inline( 'Owner ID', 'owner-id', $slug ) ?>:</nobr>
                            </td>
                            <td><code><?php echo $user->id ?></code></td>
                        </tr>
                        <?php $row_index ++ ?>
                        <!--/ User ID -->
                    <?php endif ?>
                <?php endif ?>

                <!-- Public Key -->
                <tr <?php if ( 1 == $row_index % 2 ) {
                    echo ' class="alternate"';
                } ?>>
                    <td>
                        <nobr><?php fs_esc_html_echo_inline( 'Public Key', 'public-key', $slug ) ?>:</nobr>
                    </td>
                    <td><code><?php echo htmlspecialchars( $install->public_key ) ?></code></td>
                </tr>
                <?php $row_index ++ ?>
                <!--/ Public Key -->

                <!-- Secret Key -->
                <tr <?php if ( 1 == $row_index % 2 ) {
                    echo ' class="alternate"';
                } ?>>
                    <td>
                        <nobr><?php fs_esc_html_echo_inline( 'Secret Key', 'secret-key', $slug ) ?>:</nobr>
                    </td>
                    <td>
                        <code><?php echo htmlspecialchars( substr( $install->secret_key, 0, 6 ) ) . str_pad( '', 23 * 6, '&bull;' ) . htmlspecialchars( substr( $install->secret_key, - 3 ) ) ?></code>
                        <input type="text" value="<?php echo htmlspecialchars( $install->secret_key ) ?>"
                               style="display: none" readonly/></td>
                    <td><button class="button button-small fs-toggle-visibility"><?php fs_esc_html_echo_x_inline( 'Show', 'verb', 'show', $slug ) ?></button></td>
                </tr>
                <?php $row_index ++ ?>
                <!--/ Secret Key -->

                <?php if ( is_object( $license ) && ( ! is_object( $main_license ) || $license->id != $main_license->id ) ) : ?>
                    <!-- License Key -->
                    <tr <?php if ( 1 == $row_index % 2 ) {
                        echo ' class="alternate"';
                    } ?>>
                        <td>
                            <nobr><?php fs_esc_html_echo_inline( 'License Key', 'license-key', $slug ) ?>:</nobr>
                        </td>
                        <td>
                            <code><?php echo htmlspecialchars( substr( $license->secret_key, 0, 6 ) ) . str_pad( '', 23 * 6, '&bull;' ) . htmlspecialchars( substr( $license->secret_key, - 3 ) ) ?></code>
                            <input type="text" value="<?php echo htmlspecialchars( $license->secret_key ) ?>"
                                   style="display: none" readonly/></td>
                        <td><button class="button button-small fs-toggle-visibility"><?php fs_esc_html_echo_x_inline( 'Show', 'verb', 'show', $slug ) ?></button></td>
                    </tr>
                    <?php $row_index ++ ?>
                    <!--/ License Key -->
                <?php endif ?>

                </tbody>
            </table>
        </td>
    </tr>
    <!--/ More details -->
<?php endif ?>