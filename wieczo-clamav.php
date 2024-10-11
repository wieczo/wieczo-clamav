<?php
/*
Plugin Name: Wieczo's Virus Scanner
Plugin URI: https://wieczo.net/wieczo-clamav
Description: Untersuche hochgeladene Dateien auf Viren und Malware mit ClamAV.
Version: 1.0.0
Author: Thomas Wieczorek
Author URI: https://wieczo.net
License: MIT License
License URI: https://github.com/wieczo/wieczo-clamav/blob/main/LICENSE
Text Domain: wieczo-clamav
Domain Path: /languages
*/

use Wieczo\WordPress\Plugins\ClamAV\ClamAV;
use Wieczo\WordPress\Plugins\ClamAV\Settings;

require plugin_dir_path(__FILE__) . 'src/autoloader.php';

// Initialize Scanner for handling uploaded files
$clamAV = new ClamAV();

add_filter('wp_handle_upload_prefilter', [$clamAV, 'scanFile']);

// Initialize settings pages
$settings = new Settings();

// Initialize translations
add_action('plugins_loaded', 'wieczo_clamav_load_textdomain');

function wieczo_clamav_load_textdomain() {
	load_plugin_textdomain('wieczo-clamav', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}