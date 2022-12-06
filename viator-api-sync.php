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

define('VIATORAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VIATORAS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VIATORAS_MAIN_PLUGIN_DIR', plugin_dir_url(__DIR__));
define('VIATORAS_VERSION', '1.0');

require_once VIATORAS_PLUGIN_DIR.'admin/settings.php';