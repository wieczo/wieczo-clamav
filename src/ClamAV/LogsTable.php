<?php

namespace Wieczo\WordPress\Plugins\ClamAV;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class LogsTable extends \WP_List_Table {

	public function __construct() {
		parent::__construct( [
			'singular' => __( 'Log', 'wieczos-virus-scanner' ),
			'plural'   => __( 'Logs', 'wieczos-virus-scanner' ),
			'ajax'     => false
		] );
	}

	public function get_columns() {
		return [
			'cb'         => '<input type="checkbox" />', // Checkbox für Massenaktionen
			'id'         => __( 'ID', 'wieczos-virus-scanner' ),
			'user_name'  => __( 'Benutzername', 'wieczos-virus-scanner' ),
			'filename'   => __( 'Dateiname', 'wieczos-virus-scanner' ),
			'error_type' => __( 'Fehlertyp', 'wieczos-virus-scanner' ),
			'source'     => __( 'Scan-Typ', 'wieczos-virus-scanner' ),
			'created_at' => __( 'Erstellungsdatum', 'wieczos-virus-scanner' ),
			'actions'    => __( 'Aktionen', 'wieczos-virus-scanner' ),
		];
	}

	public function get_sortable_columns() {
		return [
			'id'         => [ 'id', false ],
			'user_name'  => [ 'user_name', false ],
			'created_at' => [ 'created_at', true ],
		];
	}

	public function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-action[]" value="%s" />',
			$item->id
		);
	}

	public function column_error_type( $item ) {
		return UploadError::mapNameToEnum( $item->error_type )?->message( $item->filename );
	}

	public function column_source( $item ) {
		return ScanType::mapNameToEnum( $item->source )?->message();
	}

	public function column_actions( $item ) {
		$delete_nonce = wp_create_nonce( 'delete_log_' . $item->id );
		$delete_url   = add_query_arg( [
			'action'        => 'delete',
			'log'           => $item->id,
			'_delete_nonce' => $delete_nonce,
		], admin_url( 'admin.php?page=wieczos-virus-scanner-logs' ) );

		return sprintf(
			'<a href="%s" onclick="return confirm(\'%s\')">%s</a>',
			esc_url( $delete_url ),
			esc_html__( 'Sind Sie sicher, dass Sie diesen Log-Eintrag löschen möchten?', 'wieczos-virus-scanner' ),
			esc_html__( 'Löschen', 'wieczos-virus-scanner' )
		);
	}

	public function get_bulk_actions() {
		return [
			'delete' => __( 'Löschen', 'wieczos-virus-scanner' ),
		];
	}

	public function process_bulk_action() {
		global $wpdb;
		$tableName = sanitize_key( $wpdb->prefix . Config::TABLE_LOGS );


		// Process bulk delete action
		if ( 'delete' === $this->current_action() && ! empty( $_REQUEST['bulk-action'] ) ) {
			// Verify nonce for filter security
			if ( isset( $_REQUEST['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'filter_logs_nonce' ) ) {
				wp_die( esc_html( __( 'Ungültige Anfrage.', 'wieczos-virus-scanner' ) ) );
			}

			$ids = array_map( 'intval', $_REQUEST['bulk-action'] );
			if ( ! empty( $ids ) ) {
				// Delete items by IDs
				$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				foreach ( $ids as $id ) {
					$wpdb->delete( $tableName, [ 'id' => $id ], [ '%d' ] );
				}
			}
		}

		// Process single delete action
		if ( 'delete' === $this->current_action() && isset( $_GET['log'] ) ) {
			$logId = intval( $_GET['log'] );
			$nonce = sanitize_text_field( wp_unslash( $_GET['_delete_nonce'] ) );

			if ( ! wp_verify_nonce( $nonce, 'delete_log_' . $logId ) ) {
				wp_die( esc_html( __( 'Ungültige Anfrage.', 'wieczos-virus-scanner' ) ) );
			}

			// Delete the item
			$wpdb->delete( $tableName, [ 'id' => $logId ], [ '%d' ] );
		}
	}

	// Methode zum Hinzufügen von Filter-UI-Elementen
	protected function extra_tablenav( $which ) {
		if ( $which == 'top' ) {
			echo '<div class="tablenav top">';
			echo '<div class="alignleft actions">';

			// Add nonce field for security
			wp_nonce_field( 'filter_logs_nonce', '_wpnonce' );

			// Verify nonce for filter security
			if ( isset( $_REQUEST['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'filter_logs_nonce' ) ) {
				wp_die( esc_html( __( 'Ungültige Anfrage.', 'wieczos-virus-scanner' ) ) );
			}

			// Hidden fields for pagination and page identification
			echo '<input type="hidden" name="page" value="wieczos-virus-scanner-logs" />';
			echo '<input type="hidden" name="paged" value="1" />'; // Ensure it resets to page 1 on filter

			// Error type filter dropdown
			echo '<label for="error_type_filter" class="screen-reader-text">' . esc_html( __( 'Fehlertyp filtern:', 'wieczos-virus-scanner' ) ) . '</label>';
			echo '<select id="error_type_filter" name="error_type" class="ewc-filter-select">';
			echo '<option value="">' . esc_html( __( 'Alle Fehlertypen', 'wieczos-virus-scanner' ) ) . '</option>';
			foreach (
				[
					UploadError::VIRUS_FOUND,
					UploadError::CANNOT_READ,
					UploadError::FILE_NOT_FOUND,
					UploadError::CONNECTION_REFUSED
				] as $uploadError
			) {
				echo '<option value="' . esc_attr( $uploadError->name ) . '" ' .
				     selected( esc_attr( sanitize_text_field( wp_unslash( $_GET['error_type'] ?? '' ) ) ), $uploadError->name, false ) .
				     '>' . esc_html( $uploadError->message( 'Dateiname' ) ) . '</option>';
			}
			echo '</select>';

			// Source filter dropdown
			echo '<label for="source_filter" class="screen-reader-text">' . esc_html( __( 'Quelle filtern:', 'wieczos-virus-scanner' ) ) . '</label>';
			echo '<select id="source_filter" name="source" class="ewc-filter-select">';
			echo '<option value="">' . esc_html( __( 'Alle Quellen', 'wieczos-virus-scanner' ) ) . '</option>';
			foreach (
				[
					ScanType::UPLOAD_SCAN,
					ScanType::WORDPRESS_SCAN,
				] as $scanSource
			) {
				echo '<option value="' . esc_attr( $scanSource->name ) . '" ' .
				     selected( esc_attr( sanitize_text_field( wp_unslash( $_GET['source'] ?? '' ) ) ), $scanSource->name, false ) .
				     '>' . esc_html( $scanSource->message() ) . '</option>';
			}
			echo '</select>';

			// Submit button with WordPress styling
			submit_button( __( 'Filtern', 'wieczos-virus-scanner' ), 'button', false, false, [ 'id' => 'post-query-submit' ] );

			echo '</div>'; // .alignleft.actions
			echo '</div>'; // .tablenav.top
		}
	}

	public function prepare_items() {
		global $wpdb;
		$table_name = sanitize_key( $wpdb->prefix . Config::TABLE_LOGS );

		// Process bulk actions before fetching items
		$this->process_bulk_action();

		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Verify nonce for filter security
		if ( isset( $_REQUEST['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'filter_logs_nonce' ) ) {
			wp_die( esc_html( __( 'Ungültige Anfrage.', 'wieczos-virus-scanner' ) ) );
		}

		// Apply filters
		$where_clauses = [];
		if ( ! empty( $_REQUEST['error_type'] ) ) {
			$where_clauses[] = $wpdb->prepare( "error_type = %s", sanitize_text_field( wp_unslash( $_REQUEST['error_type'] ) ) );
		}
		if ( ! empty( $_REQUEST['source'] ) ) {
			$where_clauses[] = $wpdb->prepare( "source = %s", sanitize_text_field( wp_unslash( $_REQUEST['source'] ) ) );
		}

		$where = '';
		if ( ! empty( $where_clauses ) ) {
			$where = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Pick order by from the request
		$orderby = ! empty( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
		$order   = ! empty( $_REQUEST['order'] ) && strtolower( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) === 'asc' ? 'ASC' : 'DESC';

		// Set the items for the table
		$sql         = "SELECT * FROM {$table_name} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$this->items = $wpdb->get_results( $wpdb->prepare( $sql, $per_page, $offset ) );

		// Total elements
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} {$where}" );

		// Set column captions
		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

		// Pagination settings
		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		] );
	}
}
