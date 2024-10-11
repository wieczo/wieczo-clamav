<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access
}

spl_autoload_register( function ( $class ) {
	$namespace = 'Wieczo\WordPress\Plugins\ClamAV\\';
	if ( str_starts_with( $class, $namespace ) ) {
		$classFile = str_replace( $namespace, '', $class );
		$file      = plugin_dir_path( __FILE__ ) . 'ClamAV/' . $classFile . '.php';
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
} );
