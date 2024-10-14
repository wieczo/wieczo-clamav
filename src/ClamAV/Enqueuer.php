<?php

namespace Wieczo\WordPress\Plugins\ClamAV;

class Enqueuer {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );

		add_action( 'admin_footer', [ $this, 'addTableSortingJS' ] );
	}

	public function enqueue() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'tablesorter', plugins_url( 'assets/js/jquery.tablesorter.min.js', \WIECZOS_VIRUS_SCANNER_PLUGIN_DIR ), [ 'jquery' ], "2.32.0", true );

		wp_enqueue_style( 'tablesorter-css', plugins_url( 'assets/css/jquery.tablesorter.min.css', \WIECZOS_VIRUS_SCANNER_PLUGIN_DIR ), [], "2.32.0" );
	}

	function addTableSortingJS() {
		wp_add_inline_script('init-tablesorter', "
		<script type='text/javascript'>
            jQuery(document).ready(function ($) {
                $('.tablesorter').tablesorter();
            });
        </script>
        ");
	}
}