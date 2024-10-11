<?php
/*
Plugin Name: Wieczo's ClamAV Scanner
Plugin URI: https://wieczo.net/wieczo-clamav
Description: Scans uploaded file with ClamAV for viruses.
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

$clamAV = new ClamAV();

add_filter('wp_handle_upload_prefilter', [$clamAV, 'scanFile']);

$settings = new Settings();