<?php

namespace Wieczo\WordPress\Plugins\ClamAV;
class ClamAV {
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
  public function scanFile( array $file ): array {
	  $filepath = $file['tmp_name'];
	  $filename = $file['name'];

	  // Open Socket to the ClamAV service
	  $socket = fsockopen( $this->host, $this->port, $errorCode, $errorMessage, $this->timeout );

	  if ( ! $socket ) {
		  $file['error'] = __( 'Konnte keine Verbindung zum Virenscanner herstellen.', 'wieczo-clamav' );

		  return $file;
	  }

	  // INSTREAM-Kommando senden
	  fwrite( $socket, "nINSTREAM\n" );

	  $handle = fopen( $filepath, "rb" );
	  if ( ! $handle ) {
		  fclose( $socket );
		  $file['error'] = __( 'Konnte die hochgeladene Datei nicht lesen.', 'wieczo-clamav' );

		  return $file;
	  }

	  while ( ! feof( $handle ) ) {
		  $chunk = fread( $handle, 8192 );
		  $size  = pack( 'N', strlen( $chunk ) );
		  fwrite( $socket, $size . $chunk );
	  }

	  // Null-Größe senden, um das Ende zu markieren
	  fwrite( $socket, pack( 'N', 0 ) );

	  // Antwort lesen
	  $response = fgets( $socket );

	  fclose( $handle );
	  fclose( $socket );

	  // Überprüfen, ob ein Virus gefunden wurde
	  if ( str_contains( $response, 'FOUND' ) ) {
		  $file['error'] = sprintf( __( 'Die hochgeladene Datei "%s" ist mit einem Virus infiziert und wurde abgelehnt.', 'wieczo-clamav' ), $filename );
	  }

	  return $file;
  }
}
