<?php

namespace Wieczo\WordPress\Plugins\ClamAV;
class Scanner {
	private string $host;
	private int $port;
	private int $timeout;

	public function __construct() {
		$this->host    = sanitize_text_field( get_option( 'clamav_host', Config::DEFAULT_HOST ) );
		$this->port    = (int) get_option( 'clamav_port', Config::DEFAULT_PORT );
		$this->timeout = (int) get_option( 'clamav_timeout', Config::DEFAULT_TIMEOUT );
	}

	/**
	 * @param array $file Contains the following keys ['tmp_name', 'name'] mapping the upload path and the filename.
	 *
	 * @return array Returns the input $file array with the key 'error' when a virus was found.
	 */
	public function scanUpload( array $file ): array {
		global $wp_filesystem;

		// Initialisiere WP_Filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		$filepath = $file['tmp_name'];

		if ( $this->scanFile( $filepath, $errorMessage ) ) {
			$file['error'] = $errorMessage;
		}

		return $file;
	}

	/**
	 * @param $filePath      Path to the file to scan
	 * @param null $errorMessage Error Message which occurred when trying to open a socket, read the file or scan it for a virus
	 *
	 * @return bool|null Returns true if a virus was found or if an error occurred.
	 */
	public function scanFile( $filePath, &$errorMessage = null ): ?bool {
		global $wp_filesystem;

		// Initialisiere WP_Filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		$path     = $filePath;
		$filename = basename( $filePath );

		// Check if the file exists
		if ( ! $wp_filesystem->exists( $path ) ) {
			$errorMessage = __( 'Die hochgeladene Datei konnte nicht gefunden werden.', 'wieczos-virus-scanner' );

			return null;
		}

		// Connect to the ClamAV service
		// phpcs:ignore
		$socket = fsockopen( $this->host, $this->port, $errorCode, $errorMessage, $this->timeout );

		if ( ! $socket ) {
			$errorMessage = __( 'Konnte keine Verbindung zum Virenscanner herstellen.', 'wieczos-virus-scanner' );

			return null;
		}

		// Send the INSTREAM command to ClamAV
		// phpcs:ignore
		fwrite( $socket, "nINSTREAM\n" );

		// Read the file with WP_Filesystem
		$handle = $wp_filesystem->get_contents( $path );
		if ( ! $handle ) {
			// phpcs:ignore
			fclose( $socket );
			$errorMessage = __( 'Konnte die hochgeladene Datei nicht lesen.', 'wieczos-virus-scanner' );

			return null;
		}

		// Send the file in blocks 8192 Bytes to ClamAV
		$file_size = strlen( $handle );
		$position  = 0;

		while ( $position < $file_size ) {
			$chunk = substr( $handle, $position, 8192 );
			$size  = pack( 'N', strlen( $chunk ) );
			// phpcs:ignore
			fwrite( $socket, $size . $chunk );
			$position += 8192;
		}

		// Send a NULL value to terminate the stream
		// phpcs:ignore
		fwrite( $socket, pack( 'N', 0 ) );

		// Read the ClamAV response
		$response = fgets( $socket );

		// Close the socket
		// phpcs:ignore
		fclose( $socket );

		// Überprüfen, ob ein Virus gefunden wurde
		if ( str_contains( $response, 'FOUND' ) ) {
			$this->logVirus( $filePath );
			/* translators: %s is replaced with the filename which contains a virus */
			$errorMessage = sprintf( __( 'Die hochgeladene Datei "%s" ist mit einem Virus infiziert und wurde abgelehnt.', 'wieczos-virus-scanner' ), $filename );

			return true;
		}

		return false;
	}

	/**
	 * Logs the file as having a virus in the database.
	 *
	 * @param $filename File name of the scanned file which contains a virus
	 *
	 * @return void
	 */
	private function logVirus( string $filename ): void {
		global $wpdb;

		// Table name
		$tableName = $wpdb->prefix . Config::TABLE_LOGS;

		// Fetch the user name of the currently logged in user
		$current_user = wp_get_current_user();
		$username     = $current_user->user_login;

		// Data to insert
		$data = [
			'user_name'  => sanitize_text_field( $username ),
			'filename'   => sanitize_text_field( $filename ),
			'created_at' => current_time( 'mysql' ) // Aktuelles Datum im MySQL-Format
		];

		// Formats for the inserted rows
		$formats = [ '%s', '%s', '%s' ];

		// Insert data into the table
		// phpcs:ignore
		$wpdb->insert( $tableName, $data, $formats );
	}

	/**
	 * Scans a directory recursively for files.
	 *
	 * @param string $folder Directory to scan recursively
	 * @param array $results The array to store files in the recursion
	 *
	 * @return array         The files found in the given folder
	 */
	public static function collectAllFiles( string $folder, array &$results = array() ) {
		$files = scandir( $folder );

		foreach ( $files as $value ) {
			$path = realpath( $folder . DIRECTORY_SEPARATOR . $value );
			if ( ! is_dir( $path ) && is_readable( $path ) ) {
				$results[] = $path;
			} else if ( $value != "." && $value != ".." ) {
				self::collectAllFiles( $path, $results );
			}
		}

		return $results;
	}
}
