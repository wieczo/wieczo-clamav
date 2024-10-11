<?php
/**
 * Uninstall-Prozedur
 */

require plugin_dir_path(__FILE__) . 'src/autoloader.php';

use Wieczo\WordPress\Plugins\ClamAV\Config;

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit; // prevent direct access
}

// Delete options and tables
$options = [
	'host', 'port', 'timeout'
];
foreach ($options as $option) {
	delete_option('clamav_' . $option);
}
