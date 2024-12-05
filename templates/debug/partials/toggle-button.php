<?php
    /**
     * @package   Freemius
     * @copyright Copyright (c) 2015, Freemius, Inc.
     * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since     2.10.1
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    /**
     * @var array $VARS
     * @var bool  $is_open
     */
    $is_open = $VARS['is_open'];
?>
<button class="fs-debug-table-toggle-button" aria-expanded="<?php echo $is_open ? 'true' : 'false' ?>">
    <span class="fs-debug-table-toggle-icon"><?php echo $is_open ? '▼' : '▶' ?></span>
</button>