<?php

namespace Wieczo\WordPress\Plugins\ClamAV;

use const WIECZOS_VIRUS_SCANNER_PLUGIN_DIR;

class Enqueuer {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function enqueue(): void {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-sortable' );
	}
}