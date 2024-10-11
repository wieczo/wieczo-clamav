<?php

namespace Wieczo\WordPress\Plugins\ClamAV;

class Table {
	public function defineTable() {
		global $wpdb;

		$tableName      = $wpdb->prefix . Config::TABLE_LOGS;
		$charsetCollate = $wpdb->get_charset_collate();
		$ddl            = <<<SQL
CREATE TABLE $tableName (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_name VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY  (id)
) $charsetCollate;
SQL;

		// Required WordPress methods to create the table
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Create or modify table
		dbDelta( $ddl );
	}

	public function dropTable() {
		global $wpdb;

		// Tabellenname definieren
		$tableName = $wpdb->prefix . Config::TABLE_LOGS;

		// Tabelle löschen
		// phpcs:ignore
		$wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $tableName ) );
	}
}