<?php
    /**
     * @var array $VARS
     * @var Freemius $fs
     */
    $fs = freemius( $VARS['id'] );

    $slug = $fs->get_slug();

    $sites               = $VARS['sites'];
    $require_license_key = $VARS['require_license_key'];
?>
<?php $separator      = '<td>|</td>' ?>
<?php $has_many_sites = ( count( $sites ) > 1 ) ?>
<div id="multisite_options_container" class="apply-on-all-sites">
    <table id="all_sites_options">
        <tbody>
        <tr>
            <td width="600">
                <label>
                    <?php
                        $apply_checkbox_label = $require_license_key ?
                        fs_text_inline( 'Activate license on all sites in the network.', 'activate-license-on-all-sites-in-the-network', $slug ) :
                        fs_text_inline( 'Apply on all sites in the network.', 'apply-on-all-sites-in-the-network', $slug );
                    ?>
                    <input id="apply_on_all_sites" type="checkbox" value="true" checked <?php disabled( true, ! $has_many_sites ) ?>><span><?php echo esc_html( $apply_checkbox_label ) ?></span>
                </label>
            </td>
            <?php if ( ! $require_license_key ) : ?>
                <td><a class="action action-allow" data-action-type="allow" href="#"><?php fs_esc_html_echo_inline( 'allow', 'allow', $slug ) ?></a></td>
                <?php echo $separator ?>
                <td><a class="action action-delegate" data-action-type="delegate" href="#"><?php fs_esc_html_echo_inline( 'delegate', 'delegate', $slug ) ?></a></td>
                <?php if ( $fs->is_enable_anonymous() ) : ?>
                    <?php echo $separator ?>
                    <td><a class="action action-skip" data-action-type="skip" href="#"><?php echo strtolower( fs_esc_html_inline( 'skip', 'skip', $slug ) ) ?></a></td>
                <?php endif ?>
            <?php endif ?>
        </tr>
        </tbody>
    </table>
    <?php if ( $has_many_sites ) : ?>
        <div id="sites_list_container">
            <table cellspacing="0">
                <tbody>
                <?php $site_props = array('uid', 'url', 'title', 'charset', 'language') ?>
                <?php foreach ( $sites as $site ) : ?>
                    <tr<?php if ( ! empty( $site['license_id'] ) ) {
                        echo ' data-license-id="' . $site['license_id'] . '"';
                    } ?>>
                        <?php if ( $require_license_key ) : ?>
                            <td><input type="checkbox" value="true" /></td>
                        <?php endif ?>
                        <td class="blog-id"><span><?php echo $site['blog_id'] ?></span>.</td>
                        <td width="600"><span><?php
                            $url = str_replace( 'http://', '', str_replace( 'https://', '', $site['url'] ) );
                            echo $url;
                            ?></span>
                            <?php foreach ($site_props as $prop) : ?>
                                <input class="<?php echo $prop ?>" type="hidden" value="<?php echo esc_attr($site[$prop]) ?>" />
                            <?php endforeach ?>
                        </td>
                        <?php if ( ! $require_license_key ) : ?>
                            <td><a class="action action-allow" data-action-type="allow" href="#"><?php fs_esc_html_echo_inline( 'allow', 'allow', $slug ) ?></a></td>
                            <?php echo $separator ?>
                            <td><a class="action action-delegate" data-action-type="delegate" href="#"><?php fs_esc_html_echo_inline( 'delegate', 'delegate', $slug ) ?></a></td>
                            <?php if ( $fs->is_enable_anonymous() ) : ?>
                                <?php echo $separator ?>
                                <td><a class="action action-skip" data-action-type="skip" href="#"><?php echo strtolower( fs_esc_html_inline( 'skip', 'skip', $slug ) ) ?></a></td>
                            <?php endif ?>
                        <?php endif ?>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>