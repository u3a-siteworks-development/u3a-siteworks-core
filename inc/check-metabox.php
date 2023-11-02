<?php
if (!defined('ABSPATH')) exit;

// Check that the metabox plugin is activated.  If not, exit with an admin message
if ( ! function_exists( 'rwmb_meta' ) ) {
    add_action( 'admin_notices', 'siteworks_missing_metabox' );
    return false;
}
else {
    return true;
}

function siteworks_missing_metabox() {
    print '<div class="notice notice-error is-dismissible"><p><strong>The u3a SiteWorks plugin can not function without the Meta Box plugin</strong>
    <br><br>Please ensure the Meta Box plugin is installed and activated.</p></div>';
}
