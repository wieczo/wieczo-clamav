<?php

namespace Wieczo\WordPress\Plugins\ClamAV;

class Config {
	public const DEFAULT_HOST = 'clamav';
	public const DEFAULT_PORT = 3310;
	public const DEFAULT_TIMEOUT = 30;
	public const TABLE_LOGS = 'wieczo_clamav_virus_log';
}