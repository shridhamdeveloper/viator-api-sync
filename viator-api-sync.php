<?php
/** 
 * Plugin Name: Viator API Sync
 * Plugin URI: https://wordpress.org/plugins/viator-api-sync/
 * Description: Viation Sync - Sync API data of viator to Woocommerce Product
 * Version: 1.0
 * Author: Magazine3
 * **/

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

// ini_set('display_errors', true);
// error_reporting(E_ALL);
define('VIATORAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VIATORAS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VIATORAS_MAIN_PLUGIN_DIR', plugin_dir_url(__DIR__));
define('VIATORAS_VERSION', '1.0');

require_once VIATORAS_PLUGIN_DIR.'frontend/viator_frontend_shop.php';

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'vas_add_plugin_action_links', 10, 5);
function vas_add_plugin_action_links($actions){
    $mylinks = array(
        '<a href="' . admin_url( 'admin.php?page=viator-api-sync' ) . '">'.esc_html__('Settings', 'viator_api_sync').'</a>',
    );
    $actions = array_merge( $actions, $mylinks );
    return $actions;
}
require_once VIATORAS_PLUGIN_DIR.'admin/settings.php';