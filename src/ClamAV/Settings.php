<?php

namespace Wieczo\WordPress\Plugins\ClamAV;

class Settings {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'addAdminMenu' ] );
		add_action( 'admin_init', [ $this, 'initSettings' ] );
		add_action( 'admin_post_wieczo_clamav_scan_file', array( $this, 'scanUploadedFile' ) );
	}

	/**
	 * Adds the menu entry with the corresponding icon to the WordPress Dashboard
	 *
	 * @return void
	 */
	public function addAdminMenu() {
		// Adds settings page
		add_menu_page(
			__( 'ClamAV Scanner Einstellungen', 'wieczos-virus-scanner' ),
			__( 'ClamAV Einstellungen', 'wieczos-virus-scanner' ),
			'manage_options',
			'wieczos-virus-scanner',
			[ $this, 'showSettingsPage' ],
			'dashicons-shield'
		);

		add_submenu_page(
			'wieczos-virus-scanner',    // slug of the main menu
			__( 'ClamAV Datei-Scanner', 'wieczos-virus-scanner' ),
			__( 'ClamAV Scanner', 'wieczos-virus-scanner' ),
			'manage_options',
			'wieczo-clamav-test',    // Slug of the submenu
			array( $this, 'showTestPage' ) // Callback for the page
		);

		add_submenu_page(
			'wieczos-virus-scanner',    // slug of the main menu
			__( 'Logs', 'wieczos-virus-scanner' ),
			__( 'Logs', 'wieczos-virus-scanner' ),
			'manage_options',
			'wieczo-clamav-logs',    // Slug of the submenu
			array( $this, 'showLogsPage' ) // Callback for the page
		);
	}

	/**
	 * Displays the settings page for configuration the connection to ClamAV
	 * @return void
	 */
	public function showSettingsPage() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'Du hast keine Berechtigung für diesen Vorgang.', 'wieczos-virus-scanner' ) ) );
		}
		?>
        <div class="wrap">
            <h1><?php __( 'ClamAV Scanner Einstellungen', 'wieczos-virus-scanner' ) ?></h1>
            <form method="post" action="options.php">
				<?php
				// Render settings fields
				settings_fields( 'wieczo_clamav_options_group' );
				do_settings_sections( 'wieczo_clamav_settings' );
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	/**
	 * Initializes the connection settings for the ClamAV service.
	 *
	 * @return void
	 */
	public function initSettings() {
		// Define the settings
		register_setting( 'wieczo_clamav_options_group', 'clamav_host', [
			'type'        => 'string',
			'label'       => __( 'Hostname von ClamAV', 'wieczos-virus-scanner' ),
			'description' => __( 'Wenn es sich um einen separaten Docker Container handelt, z.B. clamav. Wenn es lokal läuft, z.B. localhost', 'wieczos-virus-scanner' ),
			'default'     => Config::DEFAULT_HOST,
		] );
		register_setting( 'wieczo_clamav_options_group', 'clamav_port', [
			'type'        => 'integer',
			'label'       => __( 'Port von ClamAV', 'wieczos-virus-scanner' ),
			'description' => __( 'Der Standardwert ist 3310', 'wieczos-virus-scanner' ),
			'default'     => Config::DEFAULT_PORT,
		] );
		register_setting( 'wieczo_clamav_options_group', 'clamav_timeout', [
			'type'        => 'integer',
			'label'       => __( 'Nach wie viel Sekunden soll die Verbindung abgebrochen werden', 'wieczos-virus-scanner' ),
			'description' => __( 'Der Standartwert ist 30 Sekunden', 'wieczos-virus-scanner' ),
			'default'     => Config::DEFAULT_TIMEOUT,
		] );

		add_settings_section(
			'wieczo_clamav_section',
			__( 'ClamAV Einstellungen', 'wieczos-virus-scanner' ),
			[ $this, 'settingsCB' ],
			'wieczo_clamav_settings'
		);
		add_settings_field(
			'clamav_host',
			__( 'ClamAV Host', 'wieczos-virus-scanner' ),
			[ $this, 'renderSettingHost' ],
			'wieczo_clamav_settings',
			'wieczo_clamav_section'
		);
		add_settings_field(
			'clamav_port',
			__( 'ClamAV Port', 'wieczos-virus-scanner' ),
			[ $this, 'renderSettingPort' ],
			'wieczo_clamav_settings',
			'wieczo_clamav_section'
		);
		add_settings_field(
			'clamav_timeout',
			__( 'Timeout', 'wieczos-virus-scanner' ),
			[ $this, 'renderSettingTimeout' ],
			'wieczo_clamav_settings',
			'wieczo_clamav_section'
		);
	}


	public function settingsCB() {
		echo esc_html( __( 'Hier findest du die ClamAV Verbindungsoptionen', 'wieczos-virus-scanner' ) );
	}

	public function renderSettingHost() {
		$host = esc_attr( get_option( 'clamav_host' ) );
		?>
        <input type="text" name="clamav_host" value="<?php echo esc_attr( $host ); ?>"/>
        <p class="description"><?php esc_html( __( 'Wenn es sich um einen separaten Docker Container handelt, z.B. clamav. Wenn es lokal läuft, z.B. localhost', 'wieczos-virus-scanner' ) ) ?></p>
		<?php
	}

	public function renderSettingPort() {
		$port = (int) get_option( 'clamav_port' );
		?>
        <input type="text" name="clamav_port" value="<?php echo esc_attr( $port ); ?>"/>
        <p class="description"><?php esc_html( __( 'Der Standardwert ist 3310', 'wieczos-virus-scanner' ) ) ?></p>
		<?php
	}

	public function renderSettingTimeout() {
		$timeout = (int) get_option( 'clamav_timeout' );
		?>
        <input type="text" name="clamav_timeout" value="<?php echo esc_attr( $timeout ); ?>"/>
        <p class="description"><?php esc_html( __( 'Der Standartwert ist 30 Sekunden', 'wieczos-virus-scanner' ) ) ?></p>
		<?php
	}

	public function showTestPage() {
		$scanResult = isset( $_GET['scan_result'] ) ? urldecode( sanitize_text_field( wp_unslash( $_GET['scan_result'] ) ) ) : null;
		// Check only the nonce when it is set, no need to test it on a normal page call.
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'file_check_nonce' ) ) {
			wp_die( esc_html( __( 'Ungültiger Sicherheits-Token1', 'wieczos-virus-scanner' ) ) );
		}
		if ( $scanResult ) {
			echo '<div class="notice notice-success"><p><strong>Scan Ergebnis:</strong> ' . esc_html( $scanResult ) . '</p></div>';
		}
		?>

        <div class="wrap">
            <h1><?php __( 'ClamAV Datei-Scanner', 'wieczos-virus-scanner' ) ?></h1>
            <form method="post" enctype="multipart/form-data"
                  action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="wieczo_clamav_scan_file">
				<?php wp_nonce_field( 'clamav_scan_file_action', 'clamav_scan_file_nonce' ); ?>
                <label for="clamav-file-upload"><?php esc_html( __( 'Wähle eine Datei zum Scannen aus:', 'wieczos-virus-scanner' ) ) ?></label>
                <input type="file" name="clamav_file" id="clamav-file-upload" required>

				<?php submit_button( esc_html( __( 'Datei scannen', 'wieczos-virus-scanner' ) ) ); ?>
            </form>
        </div>
		<?php
	}

	public function scanUploadedFile() {
		// Check policies.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'Du hast keine Berechtigung für diesen Vorgang.', 'wieczos-virus-scanner' ) ) );
		}
		if ( ! isset( $_POST['clamav_scan_file_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['clamav_scan_file_nonce'] ) ), 'clamav_scan_file_action' ) ) {
			wp_die( esc_html( __( 'Ungültiger Sicherheits-Token2', 'wieczos-virus-scanner' ) ) );
		}
		// Check if a file was uploaded
		if ( isset( $_FILES['clamav_file'] ) && isset( $_FILES['clamav_file']['size'] ) && $_FILES['clamav_file']['size'] > 0 ) {
			// phpcs:ignore
			$uploaded_file = $_FILES['clamav_file'];

			// Validate and save file
			$upload_overrides = array( 'test_form' => false );
			$movefile         = wp_handle_upload( $uploaded_file, $upload_overrides );

			if ( $movefile && ! isset( $movefile['error'] ) ) {
				// ClamAV Scanner Check
				$fileArray = [
					'tmp_name' => $movefile['file'],
					'name'     => basename( $movefile['file'] ),
				];
				$scanner   = new ClamAV();
				$scanner->scanFile( $fileArray );

				// Show scan results
				$nonce           = wp_create_nonce( 'file_check_nonce' );
				$scan_result_url = add_query_arg( [
					'scan_result' => urlencode( urlencode( $fileArray['error'] ?? 'OK' ) ),
					'_wpnonce'    => $nonce,
				], admin_url( 'admin.php?page=wieczo-clamav-test' ) );
				wp_redirect( $scan_result_url );
				exit;
			} else {
				// Error while uploading
				wp_die( esc_html( __( 'Fehler beim Hochladen der Datei: ', 'wieczos-virus-scanner' ) . esc_html( $movefile['error'] ) ) );
			}
		} else {
			wp_die( esc_html( __( 'Keine Datei hochgeladen.', 'wieczos-virus-scanner' ) ) );
		}
	}

	public function showLogsPage() {

		global $wpdb;

		$tableName = $wpdb->prefix . Config::TABLE_LOGS;

		// Entries per page
		$entriesPerPage = 10;

		// Current page
		// Check if the nonce is set and correct
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'paginate_nonce_action' ) ) {
			wp_die( esc_html( __( 'Ungültige Paginierungsanfrage.', 'wieczos-virus-scanner' ) ) );
		}
		$paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;

		// Calculate offset
		$offset = ( $paged - 1 ) * $entriesPerPage;

		// Total Count of entries
        $cacheKeyTotalItems = 'wieczo-clamav-scan-total-items-' . $entriesPerPage;
        $totalItems = wp_cache_get($cacheKeyTotalItems);
        if ( false === $totalItems) {
            // phpcs:ignore
	        $totalItems = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %s", $tableName ) );

            // Cache for a minute
            wp_cache_set($cacheKeyTotalItems, $totalItems, '', 60);
        }

		// Select the limited data
        $cacheKeyResults = 'wieczo-clamav-scan-results-' . $entriesPerPage . '-' . $offset;
        $results = wp_cache_get($cacheKeyResults);
        if ( false === $results ) {
	        // phpcs:ignore
	        $results = $wpdb->get_results( $wpdb->prepare(
		        "SELECT * FROM %s LIMIT %d OFFSET %d",
		        $tableName, $entriesPerPage, $offset
	        ) );

            // Cache for a minute
            wp_cache_set($cacheKeyResults, $results, '', 60);
        }

		// Display the table
		if ( ! empty( $results ) ) {
			echo '<table class="wp-list-table widefat fixed striped tablesorter">';
			echo '<thead>
            <tr>
                <th>' . esc_html( __( 'ID', 'wieczos-virus-scanner' ) ) . '</th>
                <th>' . esc_html( __( 'Benutzername', 'wieczos-virus-scanner' ) ) . '</th>
                <th>' . esc_html( __( 'Dateiname', 'wieczos-virus-scanner' ) ) . '</th>
                <th>' . esc_html( __( 'Erstellungsdatum', 'wieczos-virus-scanner' ) ) . '</th>
            </tr>
          </thead>';
			echo '<tbody>';

			foreach ( $results as $row ) {
				// Get user object by login
				$user = get_user_by( 'login', $row->user_name );

				// Get the user admin link
				if ( $user ) {
					$userLink         = get_edit_user_link( $user->ID );
					$userNameWithLink = '<a href="' . esc_url( $userLink ) . '">' . esc_html( $row->user_name ) . '</a>';
				} else {
					// When the user's not found, just display the user_name
					$userNameWithLink = esc_html( $row->user_name );
				}


				echo '<tr>';
				echo '<td>' . esc_html( $row->id ) . '</td>';
				// phpcs:ignore
				echo '<td>' . $userNameWithLink . '</td>';
				echo '<td>' . esc_html( $row->filename ) . '</td>';
				echo '<td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $row->created_at ) ) ) . '</td>';
				echo '</tr>';
			}

			echo '</tbody>';
			echo '</table>';
		} else {
			echo '<p>' . esc_html( __( 'Keine Daten gefunden.', 'wieczos-virus-scanner' ) ) . '</p>';
		}

		// Count total pages
		$total_pages = ceil( $totalItems / $entriesPerPage );

		// Show pagination links if there are more pages
		if ( $total_pages > 1 ) {
			$nonce = wp_create_nonce( 'paginate_nonce_action' );
			// phpcs:ignore
			echo paginate_links( [
				'base'      => add_query_arg( [ 'paged', '%#%', '_wpnonce' => $nonce ] ),
				'format'    => '?paged=%#%',
				'current'   => $paged,
				'total'     => $total_pages,
				'prev_text' => '« ' . escape_html( __( 'Zurück', 'wieczos-virus-scanner' ) ),
				'next_text' => escape_html( __( 'Weiter', 'wieczos-virus-scanner' ) ) . ' »',
			] );
		}
	}
}

