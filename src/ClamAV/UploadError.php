<?php

namespace Wieczo\WordPress\Plugins\ClamAV;

enum UploadError {
	case VIRUS_FOUND;
	case CANNOT_READ;
	case CONNECTION_REFUSED;
	case FILE_NOT_FOUND;

	public function message( $fileName ): string {
		return match ( $this ) {
			/* translators: %s is replaced with the filename which contains a virus */
			UploadError::VIRUS_FOUND => sprintf( __( 'Die hochgeladene Datei "%s" ist mit einem Virus infiziert und wurde abgelehnt.', 'wieczos-virus-scanner' ), $fileName ),
			UploadError::CANNOT_READ => __( 'Konnte die hochgeladene Datei nicht lesen.', 'wieczos-virus-scanner' ),
			UploadError::CONNECTION_REFUSED => __( 'Konnte keine Verbindung zum Virenscanner herstellen.', 'wieczos-virus-scanner' ),
			UploadError::FILE_NOT_FOUND => __( 'Die hochgeladene Datei konnte nicht gefunden werden.', 'wieczos-virus-scanner' ),
		};
	}

	public static function mapNameToEnum(string $name): ?UploadError
	{
		return match($name) {
			self::VIRUS_FOUND->name => self::VIRUS_FOUND,
			self::CANNOT_READ->name => self::CANNOT_READ,
			self::CONNECTION_REFUSED->name => self::CONNECTION_REFUSED,
			self::FILE_NOT_FOUND->name => self::FILE_NOT_FOUND,
			default => null, // Optional: Rückgabe von `null` bei ungültigem Namen
		};
	}
}
