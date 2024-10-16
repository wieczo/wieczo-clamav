<?php

namespace Wieczo\WordPress\Plugins\ClamAV;

enum ScanType {
	case WORDPRESS_SCAN;
	case UPLOAD_SCAN;

	public function message(): string {
		return match ( $this ) {
			ScanType::UPLOAD_SCAN => __('Upload', 'wieczos-virus-scanner' ),
			ScanType::WORDPRESS_SCAN => __('WordPress Scanner', 'wieczos-virus-scanner' ),
		};
	}
	public static function mapNameToEnum( string $name ): ?ScanType {
		return match ( $name ) {
			ScanType::UPLOAD_SCAN->name => ScanType::UPLOAD_SCAN,
			ScanType::WORDPRESS_SCAN->name => ScanType::WORDPRESS_SCAN,
			default => null,
		};
	}
}
