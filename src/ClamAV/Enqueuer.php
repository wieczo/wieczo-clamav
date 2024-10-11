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
		wp_enqueue_script( 'tablesorter', plugins_url( 'assets/js/jquery.tablesorter.min.js', \WIECZO_CLAMAV_PLUGIN_DIR ), [ 'jquery' ], null, true );

		wp_enqueue_style( 'tablesorter-css', plugins_url( 'assets/css/jquery.tablesorter.min.css', \WIECZO_CLAMAV_PLUGIN_DIR ) );
	}

	function addTableSortingJS() {
		?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $(".tablesorter").tablesorter();
            });
        </script>
		<?php
	}
}