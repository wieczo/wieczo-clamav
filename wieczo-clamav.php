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

define('CLAMAV_HOST', 'clamav'); // Hostname des ClamAV-Containers
define('CLAMAV_PORT', '3310');      // Port des ClamAV-Daemons
define('CLAMAV_TIMEOUT', 30);     // Timeout in Sekunden

$clamAV = new ClamAV(sanitize_text_field(get_option('clamav_host')), (int)get_option('clamav_port'), (int)get_option('clamav_timeout'));

add_filter('wp_handle_upload_prefilter', [$clamAV, 'scanFile']);

$settings = new Settings();