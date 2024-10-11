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
			__( 'ClamAV Scanner Einstellungen', Config::LANGUAGE_DOMAIN ),
			__( 'ClamAV Einstellungen', Config::LANGUAGE_DOMAIN ),
			'manage_options',
			'wieczo-clamav',
			[ $this, 'showSettingsPage' ],
			'dashicons-shield'
		);

		add_submenu_page(
			'wieczo-clamav',    // slug of the main menu
            __( 'ClamAV Datei-Scanner', Config::LANGUAGE_DOMAIN ),
			__( 'ClamAV Scanner', Config::LANGUAGE_DOMAIN ),
			'manage_options',
			'wieczo-clamav-test',    // Slug of the submenu
			array($this, 'showTestPage') // Callback for the page
		);
	}

	/**
	 * Displays the settings page for configuration the connection to ClamAV
	 * @return void
	 */
	public function showSettingsPage() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __('Du hast keine Berechtigung für diesen Vorgang.', Config::LANGUAGE_DOMAIN) );
		}
		?>
        <div class="wrap">
            <h1><?php __( 'ClamAV Scanner Einstellungen', Config::LANGUAGE_DOMAIN ) ?></h1>
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
			'label'       => __( 'Hostname von ClamAV', Config::LANGUAGE_DOMAIN ),
			'description' => __( 'Wenn es sich um einen separaten Docker Container handelt, z.B. clamav. Wenn es lokal läuft, z.B. localhost', Config::LANGUAGE_DOMAIN ),
			'default'     => Config::DEFAULT_HOST,
		] );
		register_setting( 'wieczo_clamav_options_group', 'clamav_port', [
			'type'        => 'integer',
			'label'       => __( 'Port von ClamAV', Config::LANGUAGE_DOMAIN ),
			'description' => __( 'Der Standardwert ist 3310', Config::LANGUAGE_DOMAIN ),
			'default'     => Config::DEFAULT_PORT,
		] );
		register_setting( 'wieczo_clamav_options_group', 'clamav_timeout', [
			'type'        => 'integer',
			'label'       => __( 'Nach wie viel Sekunden soll die Verbindung abgebrochen werden', Config::LANGUAGE_DOMAIN ),
			'description' => __( 'Der Standartwert ist 30 Sekunden', Config::LANGUAGE_DOMAIN ),
			'default'     => Config::DEFAULT_TIMEOUT,
		] );

		add_settings_section(
			'wieczo_clamav_section',
			__( 'ClamAV Einstellungen', Config::LANGUAGE_DOMAIN ),
			[ $this, 'settingsCB' ],
			'wieczo_clamav_settings'
		);
		add_settings_field(
			'clamav_host',
			__( 'ClamAV Host', Config::LANGUAGE_DOMAIN ),
			[ $this, 'renderSettingHost' ],
			'wieczo_clamav_settings',
			'wieczo_clamav_section'
		);
		add_settings_field(
			'clamav_port',
			__( 'ClamAV Port', Config::LANGUAGE_DOMAIN ),
			[ $this, 'renderSettingPort' ],
			'wieczo_clamav_settings',
			'wieczo_clamav_section'
		);
		add_settings_field(
			'clamav_timeout',
			__( 'Timeout', Config::LANGUAGE_DOMAIN ),
			[ $this, 'renderSettingTimeout' ],
			'wieczo_clamav_settings',
			'wieczo_clamav_section'
		);
	}


	public function settingsCB() {
		echo __( 'Hier findest du die ClamAV Verbindungsoptionen', Config::LANGUAGE_DOMAIN );
	}

	public function renderSettingHost() {
		$host = esc_attr( get_option( 'clamav_host' ) );
		?>
        <input type="text" name="clamav_host" value="<?php echo $host; ?>"/>
        <p class="description"><?php __( 'Wenn es sich um einen separaten Docker Container handelt, z.B. clamav. Wenn es lokal läuft, z.B. localhost', Config::LANGUAGE_DOMAIN ) ?></p>
		<?php
	}

	public function renderSettingPort() {
		$port = (int) get_option( 'clamav_port' );
		?>
        <input type="text" name="clamav_port" value="<?php echo $port; ?>"/>
        <p class="description"><?php __( 'Der Standardwert ist 3310', Config::LANGUAGE_DOMAIN ) ?></p>
		<?php
	}

	public function renderSettingTimeout() {
		$timeout = (int) get_option( 'clamav_timeout' );
		?>
        <input type="text" name="clamav_timeout" value="<?php echo $timeout; ?>"/>
        <p class="description"><?php __( 'Der Standartwert ist 30 Sekunden', Config::LANGUAGE_DOMAIN ) ?></p>
		<?php
	}

	public function showTestPage() {
        $scanResult = isset($_GET['scan_result']) ? urldecode($_GET['scan_result']) : null;
        if ($scanResult) {
	        echo '<div class="notice notice-success"><p><strong>Scan Ergebnis:</strong> ' . esc_html($scanResult) . '</p></div>';
        }
		?>

        <div class="wrap">
            <h1><?php __( 'ClamAV Datei-Scanner', Config::LANGUAGE_DOMAIN ) ?></h1>
            <form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <input type="hidden" name="action" value="wieczo_clamav_scan_file">
	            <?php wp_nonce_field('clamav_scan_file_action', 'clamav_scan_file_nonce'); ?>
                <label for="clamav-file-upload"><?php __( 'Wähle eine Datei zum Scannen aus:', Config::LANGUAGE_DOMAIN ) ?></label>
                <input type="file" name="clamav_file" id="clamav-file-upload" required>

				<?php submit_button( __( 'Datei scannen', Config::LANGUAGE_DOMAIN ) ); ?>
            </form>
        </div>
		<?php
	}

	public function scanUploadedFile() {
		// Check policies.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __('Du hast keine Berechtigung für diesen Vorgang.', Config::LANGUAGE_DOMAIN) );
		}
		if (!isset($_POST['clamav_scan_file_nonce']) || !wp_verify_nonce($_POST['clamav_scan_file_nonce'], 'clamav_scan_file_action')) {
			wp_die( __('Ungültiger Sicherheits-Token', Config::LANGUAGE_DOMAIN) );
		}
		// Prüfen, ob eine Datei hochgeladen wurde
		if ( isset( $_FILES['clamav_file'] ) && $_FILES['clamav_file']['size'] > 0 ) {
			$uploaded_file = $_FILES['clamav_file'];

			// Validieren und Datei speichern
			$upload_overrides = array( 'test_form' => false );
			$movefile         = wp_handle_upload( $uploaded_file, $upload_overrides );

			if ( $movefile && ! isset( $movefile['error'] ) ) {
				// Datei erfolgreich hochgeladen
				$file_path = $movefile['file'];

				// Hier die ClamAV-Integration vornehmen
                $fileArray = [
	                'tmp_name' => $movefile['file'],
	                'name'     => basename($movefile['file']),
                ];
				$scanner = new ClamAV();
                $scanner->scanFile($fileArray);

				// Zeige Scan-Ergebnis
				wp_redirect( admin_url( 'admin.php?page=wieczo-clamav-test&scan_result=' . urlencode( $fileArray['error'] ?? 'Alles in Ordnung' ) ) );
				exit;
			} else {
				// Fehler beim Hochladen
				wp_die( __('Fehler beim Hochladen der Datei: ', Config::LANGUAGE_DOMAIN) . esc_html( $movefile['error'] ) );
			}
		} else {
			wp_die( __('Keine Datei hochgeladen.', Config::LANGUAGE_DOMAIN ) );
		}
	}
}

