<?php

namespace Wieczo\WordPress\Plugins\ClamAV;

use const ABSPATH;
use const WIECZOS_VIRUS_SCANNER_PLUGIN_DIR;

class Settings {
	private int $batchSize = 200;

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
	public function addAdminMenu(): void {
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
	public function showSettingsPage(): void {
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
	public function initSettings(): void {
		// Define the settings
		register_setting( 'wieczo_clamav_options_group', 'clamav_host', [
			'type'              => 'string',
			'label'             => __( 'Hostname von ClamAV', 'wieczos-virus-scanner' ),
			'description'       => __( 'Wenn es sich um einen separaten Docker Container handelt, z.B. clamav. Wenn es lokal läuft, z.B. localhost', 'wieczos-virus-scanner' ),
			'default'           => Config::DEFAULT_HOST,
			'sanitize_callback' => 'sanitize_text_field',
		] );
		register_setting( 'wieczo_clamav_options_group', 'clamav_port', [
			'type'              => 'integer',
			'label'             => __( 'Port von ClamAV', 'wieczos-virus-scanner' ),
			'description'       => __( 'Der Standardwert ist 3310', 'wieczos-virus-scanner' ),
			'default'           => Config::DEFAULT_PORT,
			'sanitize_callback' => 'intval',
		] );
		register_setting( 'wieczo_clamav_options_group', 'clamav_timeout', [
			'type'              => 'integer',
			'label'             => __( 'Nach wie viel Sekunden soll die Verbindung abgebrochen werden', 'wieczos-virus-scanner' ),
			'description'       => __( 'Der Standartwert ist 30 Sekunden', 'wieczos-virus-scanner' ),
			'default'           => Config::DEFAULT_TIMEOUT,
			'sanitize_callback' => 'intval',
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


	public function settingsCB(): void {
		echo esc_html( __( 'Hier findest du die ClamAV Verbindungsoptionen', 'wieczos-virus-scanner' ) );
	}

	public function renderSettingHost(): void {
		$host = esc_attr( get_option( 'clamav_host' ) );
		?>
        <input type="text" name="clamav_host" value="<?php echo esc_attr( $host ); ?>"/>
        <p class="description"><?php esc_html( __( 'Wenn es sich um einen separaten Docker Container handelt, z.B. clamav. Wenn es lokal läuft, z.B. localhost', 'wieczos-virus-scanner' ) ) ?></p>
		<?php
	}

	public function renderSettingPort(): void {
		$port = (int) get_option( 'clamav_port' );
		?>
        <input type="text" name="clamav_port" value="<?php echo esc_attr( $port ); ?>"/>
        <p class="description"><?php esc_html( __( 'Der Standardwert ist 3310', 'wieczos-virus-scanner' ) ) ?></p>
		<?php
	}

	public function renderSettingTimeout(): void {
		$timeout = (int) get_option( 'clamav_timeout' );
		?>
        <input type="text" name="clamav_timeout" value="<?php echo esc_attr( $timeout ); ?>"/>
        <p class="description"><?php esc_html( __( 'Der Standartwert ist 30 Sekunden', 'wieczos-virus-scanner' ) ) ?></p>
		<?php
	}

	public function showTestPage(): void {
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

	public function scanUploadedFile(): void {
		// Check policies.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'Du hast keine Berechtigung für diesen Vorgang.', 'wieczos-virus-scanner' ) ) );
		}
		if ( ! isset( $_POST['clamav_scan_file_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['clamav_scan_file_nonce'] ) ), 'clamav_scan_file_action' ) ) {
			wp_die( esc_html( __( 'Ungültiger Sicherheits-Token', 'wieczos-virus-scanner' ) ) );
		}
		// Check if a file was uploaded
		if ( isset( $_FILES['clamav_file']['size'] ) && $_FILES['clamav_file']['size'] > 0 ) {
			// phpcs:ignore Can't escape $_FILES. wp_handle_upload takes care of it.
			$uploaded_file = $_FILES['clamav_file'];

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

	public function showLogsPage(): void {
		$table = new LogsTable();
		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html( __( 'Logs', 'wieczos-virus-scanner' ) ) . '</h1>';
		$table->prepare_items();
		// Filterformular
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="wieczos-virus-scanner-logs" />';
		$table->display();
		echo '</form>';

		echo '</div>';
	}

	public function showFullScanPage(): void {
		$files = Scanner::collectAllFiles( ABSPATH );
		?>
        <div class="wrap">
            <h1><?php echo esc_html( __( 'Voller WordPress Scan', 'wieczos-virus-scanner' ) ) ?></h1>
            <p>
				<?php echo esc_html( __( 'Es werden alle WordPress Dateien nach Viren durchsucht.', 'wieczos-virus-scanner' ) ) ?>
            </p>
            <div id="progress-container"
                 style="width: 100%; background-color: #f3f3f3; border: 1px solid #ddd; padding: 5px;">
                <div id="progress-bar"
                     style="width: 0; height: 30px; background-color: #4caf50; text-align: center; line-height: 30px; color: white;">
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

	public function enqueueScripts( $hook ): void {
		// Woher kommt  virus-scanner_page_full-scan statt wieczos-virus-scanner_page_full-scan
		if ( $hook !== 'virus-scanner_page_full-scan' ) {
			return;
		}

		// JavaScript for AJAX Batching
		wp_enqueue_script( 'full-scan-script', plugins_url( 'assets/js/batch-scan.js', WIECZOS_VIRUS_SCANNER_PLUGIN_DIR ), [ 'jquery' ], '0.1', true );

		// Localized data for the JS
		$files = Scanner::collectAllFiles( ABSPATH );
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

	public function handleAjaxBatchScan(): void {
		check_ajax_referer( 'wieczos-virus-scanner-batch_scan_nonce', 'security' );

		// Pick the offset from the request (how many files have been handled)
		$offset        = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
		$infectedFiles = isset( $_POST['infectedFiles'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['infectedFiles'] ) ) : [];
		$scanner       = new Scanner();
		$wordpressRoot = ABSPATH;
		$files         = $scanner->collectAllFiles( $wordpressRoot );
		$totalFiles    = count( $files );

		$filesToScan = array_slice( $files, $offset, $this->batchSize );
		foreach ( $filesToScan as $file ) {

			if ( $scanner->scanFile( $file, ScanType::WORDPRESS_SCAN, $error ) === true ) {
				$infectedFiles[] = $file;
			}
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

