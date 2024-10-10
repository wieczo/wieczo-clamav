<?php
namespace Wieczo\WordPress\Plugins\ClamAV;
class ClamAV {
  public function __construct(private string $host = 'clamav', private int $port = 3300, private int $timeout = 30) {}

  /**
   * @param array $file Contains the following keys ['tmp_name', 'name'] mapping the upload path and the filename.
   * @return array Returns the input $file array with the key 'error' when a virus was found.
   */
  public function scanFile(array $file): array {
    $filepath = $file['tmp_name'];
    $filename = $file['name'];

    // Open Socket to the ClamAV service
    $socket = fsockopen($this->host, $this->port, $errorCode, $errorMessage, $this->timeout);

    if (!$socket) {
      $file['error'] = __('Konnte keine Verbindung zum Virenscanner herstellen.', Config::LANGUAGE_DOMAIN);
      return $file;
    }

    // INSTREAM-Kommando senden
    fwrite($socket, "nINSTREAM\n");

    $handle = fopen($filepath, "rb");
    if (!$handle) {
      fclose($socket);
      $file['error'] = __('Konnte die hochgeladene Datei nicht lesen.', Config::LANGUAGE_DOMAIN);
      return $file;
    }

    while (!feof($handle)) {
      $chunk = fread($handle, 8192);
      $size = pack('N', strlen($chunk));
      fwrite($socket, $size . $chunk);
    }

    // Null-Größe senden, um das Ende zu markieren
    fwrite($socket, pack('N', 0));

    // Antwort lesen
    $response = fgets($socket);

    fclose($handle);
    fclose($socket);

    // Überprüfen, ob ein Virus gefunden wurde
    if ( str_contains( $response, 'FOUND' ) ) {
      $file['error'] = sprintf(__('Die hochgeladene Datei "%s" ist mit einem Virus infiziert und wurde abgelehnt.', Config::LANGUAGE_DOMAIN), $filename);
    }

    return $file;
  }
}
