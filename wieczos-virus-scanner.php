<?php
/*
Plugin Name: Wieczo's Virus Scanner
Plugin URI: https://github.com/wieczo/wieczos-virus-scanner
Description: Untersuche hochgeladene Dateien auf Viren und Malware mit ClamAV.
Version: 1.2.1
Author: Thomas Wieczorek
Author URI: https://wieczo.net
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wieczos-virus-scanner
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access
}

use Wieczo\WordPress\Plugins\ClamAV\Scanner;
use Wieczo\WordPress\Plugins\ClamAV\Enqueuer;
use Wieczo\WordPress\Plugins\ClamAV\Settings;
use Wieczo\WordPress\Plugins\ClamAV\Table;

define( 'WIECZOS_VIRUS_SCANNER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) . '/wieczos-virus-scanner/' );
require plugin_dir_path( __FILE__ ) . 'src/autoloader.php';
// Initialize tables
$tableUpdates = new Table();
register_activation_hook( __FILE__, [ $tableUpdates, 'defineTable' ] );
register_uninstall_hook( __FILE__, [ $tableUpdates, 'dropTable' ] );
// Initialize Scanner for handling uploaded files
$clamAV = new Scanner();

add_filter( 'wp_handle_upload_prefilter', [ $clamAV, 'scanUpload' ] );

// Initialize settings pages
$settings = new Settings();

// Initialize translations
add_action( 'plugins_loaded', 'wieczo_clamav_load_textdomain' );

function wieczo_clamav_load_textdomain() {
	load_plugin_textdomain( 'wieczos-virus-scanner', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wieczo_clamav_settings_link' );
function wieczo_clamav_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'options-general.php?page=wieczos-virus-scanner' ) . '">' . __( 'Einstellungen', 'wieczos-virus-scanner' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}


new Enqueuer();
