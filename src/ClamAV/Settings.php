<?php

namespace Wieczo\WordPress\Plugins\ClamAV;

class Settings {
	private int $batchSize = 1_000;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'addAdminMenu' ] );
		add_action( 'admin_init', [ $this, 'initSettings' ] );
		add_action( 'admin_post_wieczo_clamav_scan_file', [ $this, 'scanUploadedFile' ] );

		// Scripts for the batch scan
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );

		// AJAX-Handler for the batch scan
		add_action( 'wp_ajax_batchScan', array( $this, 'handleAjaxBatchScan' ) );
	}

	/**
	 * Adds the menu entry with the corresponding icon to the WordPress Dashboard
	 *
	 * @return void
	 */
	public function addAdminMenu() {
		// Adds settings page
		add_menu_page(
			__( 'Scanner Einstellungen', 'wieczos-virus-scanner' ),
			__( 'Virus Scanner', 'wieczos-virus-scanner' ),
			'manage_options',
			'wieczos-virus-scanner',
			[ $this, 'showSettingsPage' ],
			'dashicons-shield'
		);

		add_submenu_page(
			'wieczos-virus-scanner',    // slug of the main menu
			__( 'Einzeldatei-Scanner', 'wieczos-virus-scanner' ),
			__( 'Datei Scanner', 'wieczos-virus-scanner' ),
			'manage_options',
			'wieczos-virus-scanner-test',    // Slug of the submenu
			array( $this, 'showTestPage' ) // Callback for the page
		);

		add_submenu_page(
			'wieczos-virus-scanner',    // slug of the main menu
			__( 'WordPress Scanner', 'wieczos-virus-scanner' ),
			__( 'WordPress Scanner', 'wieczos-virus-scanner' ),
			'manage_options',
			'full-scan',    // Slug of the submenu
			array( $this, 'showFullScanPage' ) // Callback for the page
		);

		add_submenu_page(
			'wieczos-virus-scanner',    // slug of the main menu
			__( 'Logs', 'wieczos-virus-scanner' ),
			__( 'Logs', 'wieczos-virus-scanner' ),
			'manage_options',
			'wieczos-virus-scanner-logs',    // Slug of the submenu
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
			wp_die( esc_html( __( 'Ungültiger Sicherheits-Token', 'wieczos-virus-scanner' ) ) );
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
		if ( ! isset( $_POST['clamav_scan_file_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['clamav_scan_file_nonce'] ) ), 'clamav_scan_file_action' ) ) {
			wp_die( esc_html( __( 'Ungültiger Sicherheits-Token', 'wieczos-virus-scanner' ) ) );
		}
		// Check if a file was uploaded
		if ( isset( $_FILES['clamav_file'] ) && isset( $_FILES['clamav_file']['size'] ) && $_FILES['clamav_file']['size'] > 0 ) {
			$uploaded_file = wp_unslash( $_FILES['clamav_file'] );

			// Validate file upload status
			if ( isset( $_FILES['clamav_file']['error'] ) && $_FILES['clamav_file']['error'] !== UPLOAD_ERR_OK ) {
				wp_die( esc_html( __( 'Fehler beim Hochladen der Datei.', 'wieczos-virus-scanner' ) ) );
			}
			// Sanitize the filename
			$uploaded_file['name'] = sanitize_file_name( $uploaded_file['name'] );
			// Validate and save file
			$upload_overrides = array( 'test_form' => false );
			$movefile         = wp_handle_upload( $uploaded_file, $upload_overrides );

			if ( $movefile && ! isset( $movefile['error'] ) ) {
				// ClamAV Scanner Check
				$fileArray = [
					'tmp_name' => $movefile['file'],
					'name'     => basename( $movefile['file'] ),
				];
				$scanner   = new Scanner();
				$scanner->scanUpload( $fileArray );

				// Show scan results
				$nonce           = wp_create_nonce( 'file_check_nonce' );
				$scan_result_url = add_query_arg( [
					'scan_result' => urlencode( $fileArray['error'] ?? 'OK' ),
					'_wpnonce'    => $nonce,
				], admin_url( 'admin.php?page=wieczos-virus-scanner-test' ) );
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

		$tableName = sanitize_key( $wpdb->prefix . Config::TABLE_LOGS );

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
		// phpcs:ignore
		$totalItems = $wpdb->get_var( "SELECT COUNT(*) FROM {$tableName}" );

		// Select the limited data
		// phpcs:ignore
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$tableName} LIMIT %d OFFSET %d",
			$entriesPerPage, $offset
		) );
		// Display the table
		if ( ! empty( $results ) ) {
			echo '<table class="wp-list-table widefat fixed striped tablesorter">';
			echo '<thead>
            <tr>
                <th>' . esc_html( __( 'ID', 'wieczos-virus-scanner' ) ) . '</th>
                <th>' . esc_html( __( 'Benutzername', 'wieczos-virus-scanner' ) ) . '</th>
                <th>' . esc_html( __( 'Dateiname', 'wieczos-virus-scanner' ) ) . '</th>
                <th>' . esc_html( __( 'Fehlertyp', 'wieczos-virus-scanner' ) ) . '</th>
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
				echo '<td>' . esc_html( UploadError::mapNameToEnum( $row->error_type )?->message( $row->filename ) ) . '</td>';
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
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '?paged=%#%',
				'current'   => $paged,
				'total'     => $total_pages,
				'prev_text' => '« ' . esc_html( __( 'Zurück', 'wieczos-virus-scanner' ) ),
				'next_text' => esc_html( __( 'Weiter', 'wieczos-virus-scanner' ) ) . ' »',
				'add_args'  => [ '_wpnonce' => $nonce ] // Füge den Nonce zu den Links hinzu
			] );
		}
	}

	public function showFullScanPage() {
		$files = Scanner::collectAllFiles( \ABSPATH );
		?>
        <div class="wrap">
            <h1><?php echo esc_html( __( 'Voller WordPress Scan', 'wieczos-virus-scanner' ) ) ?></h1>
            <p>
				<?php echo esc_html( __( 'Es werden alle WordPress Dateien nach Viren durchsucht.', 'wieczos-virus-scanner' ) ) ?>
            </p>
            <div id="progress-container"
                 style="width: 100%; background-color: #f3f3f3; border: 1px solid #ddd; padding: 5px;">
                <div id="progress-bar"
                     style="width: 0%; height: 30px; background-color: #4caf50; text-align: center; line-height: 30px; color: white;">
                    0%
                </div>
            </div>
            <p id="progress-text"> <?php
				/* translators: %1$s is replaced with the count of the files which have been already scanned,
				   %2$s is replaced with the total count of all files
				*/
				echo esc_html( sprintf( __( 'Es wurden %1$s von %2$s Dateien gescannt.', 'wieczos-virus-scanner' ), 0, count( $files ) ) )
				?></p>
            <button id="start-scan"
                    class="button button-primary"><?php echo esc_html( __( 'Scan starten', 'wieczos-virus-scanner' ) ) ?></button>
        </div>
		<?php
	}

	public function enqueueScripts( $hook ) {
		// Woher kommt  virus-scanner_page_full-scan statt wieczos-virus-scanner_page_full-scan
		if ( $hook !== 'virus-scanner_page_full-scan' ) {
			return;
		}

		// JavaScript for AJAX Batching
		wp_enqueue_script( 'full-scan-script', plugins_url( 'assets/js/batch-scan.js', \WIECZOS_VIRUS_SCANNER_PLUGIN_DIR ), [ 'jquery' ], '0.1', true );

		// Localized data for the JS
		$files = Scanner::collectAllFiles( \ABSPATH );
		wp_localize_script( 'full-scan-script', 'batchScanData', [
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'wieczos-virus-scanner-batch_scan_nonce' ),
			'totalFiles'       => count( $files ), // Gesamtzahl der zu scannenden Dateien
			'localizedStrings' => [
				/* translators: %1$s is replaced with the count of the files which have been already scanned,
				   %2$s is replaced with the total count of all files
				*/
				'filesScanned' => __( 'Es wurden %1$s von %2$s Dateien gescannt.', 'wieczos-virus-scanner' ),
				/* translators: %1$s stand for the total of all infected files. */
				'scanFinished' => __( 'Alle Dateien wurden erfolgreich gescannt! %1$s infizierte Dateien gefunden. Infizierte Dateien sind im Log zu finden.', 'wieczos-virus-scanner' ),
				'scanError'    => __( 'Es ist ein Fehler beim Scannen aufgetreten!', 'wieczos-virus-scanner' ),
			]
		] );
	}

	public function handleAjaxBatchScan() {
		check_ajax_referer( 'wieczos-virus-scanner-batch_scan_nonce', 'security' );

		// Pick the offset from the request (how many files have been handled)
		$offset        = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
		$infectedFiles = $_POST['infectedFiles'] ? array_map( 'sanitize_text_field', wp_unslash( $_POST['infectedFiles'] ) ) : [];
		$scanner       = new Scanner();
		$wordpressRoot = \ABSPATH;
		$files         = $scanner->collectAllFiles( $wordpressRoot );
		$totalFiles    = count( $files );

		$filesToScan = array_slice( $files, $offset, $this->batchSize );
		foreach ( $filesToScan as $file ) {

			if ( $scanner->scanFile( $file, $error ) === true ) {
				$infectedFiles[] = $file;
			};
		}
		// Work on the next batch
		$processedFiles = min( $offset + $this->batchSize, $totalFiles );

		// When all files have been scanned, send the finished statt
		if ( $processedFiles >= $totalFiles ) {
			wp_send_json_success( array(
				'finished'       => true,
				'processedFiles' => $processedFiles,
				'infectedFiles'  => $infectedFiles
			) );
		} else {
			wp_send_json_success( array(
				'finished'       => false,
				'offset'         => $processedFiles,
				'processedFiles' => $processedFiles,
				'infectedFiles'  => $infectedFiles,
			) );
		}

		wp_die();
	}
}

