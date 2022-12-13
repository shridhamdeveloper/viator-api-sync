<?php
/** 
 * Uninstall Viator API Sync Plugin 
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

delete_option('vas_data');