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
			__( 'ClamAV Scanner Einstellungen', 'wieczo-clamav' ),
			__( 'ClamAV Einstellungen', 'wieczo-clamav' ),
			'manage_options',
			'wieczo-clamav',
			[ $this, 'showSettingsPage' ],
			'dashicons-shield'
		);

		add_submenu_page(
			'wieczo-clamav',    // slug of the main menu
			__( 'ClamAV Datei-Scanner', 'wieczo-clamav' ),
			__( 'ClamAV Scanner', 'wieczo-clamav' ),
			'manage_options',
			'wieczo-clamav-test',    // Slug of the submenu
			array( $this, 'showTestPage' ) // Callback for the page
		);

		add_submenu_page(
			'wieczo-clamav',    // slug of the main menu
			__( 'Logs', 'wieczo-clamav' ),
			__( 'Logs', 'wieczo-clamav' ),
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
			wp_die( esc_html( __( 'Du hast keine Berechtigung für diesen Vorgang.', 'wieczo-clamav' ) ) );
		}
		?>
        <div class="wrap">
            <h1><?php __( 'ClamAV Scanner Einstellungen', 'wieczo-clamav' ) ?></h1>
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
			'label'       => __( 'Hostname von ClamAV', 'wieczo-clamav' ),
			'description' => __( 'Wenn es sich um einen separaten Docker Container handelt, z.B. clamav. Wenn es lokal läuft, z.B. localhost', 'wieczo-clamav' ),
			'default'     => Config::DEFAULT_HOST,
		] );
		register_setting( 'wieczo_clamav_options_group', 'clamav_port', [
			'type'        => 'integer',
			'label'       => __( 'Port von ClamAV', 'wieczo-clamav' ),
			'description' => __( 'Der Standardwert ist 3310', 'wieczo-clamav' ),
			'default'     => Config::DEFAULT_PORT,
		] );
		register_setting( 'wieczo_clamav_options_group', 'clamav_timeout', [
			'type'        => 'integer',
			'label'       => __( 'Nach wie viel Sekunden soll die Verbindung abgebrochen werden', 'wieczo-clamav' ),
			'description' => __( 'Der Standartwert ist 30 Sekunden', 'wieczo-clamav' ),
			'default'     => Config::DEFAULT_TIMEOUT,
		] );

		add_settings_section(
			'wieczo_clamav_section',
			__( 'ClamAV Einstellungen', 'wieczo-clamav' ),
			[ $this, 'settingsCB' ],
			'wieczo_clamav_settings'
		);
		add_settings_field(
			'clamav_host',
			__( 'ClamAV Host', 'wieczo-clamav' ),
			[ $this, 'renderSettingHost' ],
			'wieczo_clamav_settings',
			'wieczo_clamav_section'
		);
		add_settings_field(
			'clamav_port',
			__( 'ClamAV Port', 'wieczo-clamav' ),
			[ $this, 'renderSettingPort' ],
			'wieczo_clamav_settings',
			'wieczo_clamav_section'
		);
		add_settings_field(
			'clamav_timeout',
			__( 'Timeout', 'wieczo-clamav' ),
			[ $this, 'renderSettingTimeout' ],
			'wieczo_clamav_settings',
			'wieczo_clamav_section'
		);
	}


	public function settingsCB() {
		echo esc_html( __( 'Hier findest du die ClamAV Verbindungsoptionen', 'wieczo-clamav' ) );
	}

	public function renderSettingHost() {
		$host = esc_attr( get_option( 'clamav_host' ) );
		?>
        <input type="text" name="clamav_host" value="<?php echo esc_attr( $host ); ?>"/>
        <p class="description"><?php esc_html( __( 'Wenn es sich um einen separaten Docker Container handelt, z.B. clamav. Wenn es lokal läuft, z.B. localhost', 'wieczo-clamav' ) ) ?></p>
		<?php
	}

	public function renderSettingPort() {
		$port = (int) get_option( 'clamav_port' );
		?>
        <input type="text" name="clamav_port" value="<?php echo esc_attr( $port ); ?>"/>
        <p class="description"><?php esc_html( __( 'Der Standardwert ist 3310', 'wieczo-clamav' ) ) ?></p>
		<?php
	}

	public function renderSettingTimeout() {
		$timeout = (int) get_option( 'clamav_timeout' );
		?>
        <input type="text" name="clamav_timeout" value="<?php echo esc_attr( $timeout ); ?>"/>
        <p class="description"><?php esc_html( __( 'Der Standartwert ist 30 Sekunden', 'wieczo-clamav' ) ) ?></p>
		<?php
	}

	public function showTestPage() {
		$scanResult = isset( $_GET['scan_result'] ) ? urldecode( sanitize_text_field( wp_unslash( $_GET['scan_result'] ) ) ) : null;
		// Check only the nonce when it is set, no need to test it on a normal page call.
		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'file_check_nonce' ) ) {
			wp_die( esc_html( __( 'Ungültiger Sicherheits-Token1', 'wieczo-clamav' ) ) );
		}
		if ( $scanResult ) {
			echo '<div class="notice notice-success"><p><strong>Scan Ergebnis:</strong> ' . esc_html( $scanResult ) . '</p></div>';
		}
		?>

        <div class="wrap">
            <h1><?php __( 'ClamAV Datei-Scanner', 'wieczo-clamav' ) ?></h1>
            <form method="post" enctype="multipart/form-data"
                  action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="wieczo_clamav_scan_file">
				<?php wp_nonce_field( 'clamav_scan_file_action', 'clamav_scan_file_nonce' ); ?>
                <label for="clamav-file-upload"><?php esc_html( __( 'Wähle eine Datei zum Scannen aus:', 'wieczo-clamav' ) ) ?></label>
                <input type="file" name="clamav_file" id="clamav-file-upload" required>

				<?php submit_button( esc_html( __( 'Datei scannen', 'wieczo-clamav' ) ) ); ?>
            </form>
        </div>
		<?php
	}

	public function scanUploadedFile() {
		// Check policies.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'Du hast keine Berechtigung für diesen Vorgang.', 'wieczo-clamav' ) ) );
		}
		if ( ! isset( $_POST['clamav_scan_file_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['clamav_scan_file_nonce'] ) ), 'clamav_scan_file_action' ) ) {
			wp_die( esc_html( __( 'Ungültiger Sicherheits-Token2', 'wieczo-clamav' ) ) );
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
				wp_die( esc_html( __( 'Fehler beim Hochladen der Datei: ', 'wieczo-clamav' ) . esc_html( $movefile['error'] ) ) );
			}
		} else {
			wp_die( esc_html( __( 'Keine Datei hochgeladen.', 'wieczo-clamav' ) ) );
		}
	}

	public function showLogsPage() {

		global $wpdb;

		$table_name = $wpdb->prefix . Config::TABLE_LOGS;

        // Entries per page
		$per_page = 10;

        // Current page
		$paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;

        // Calculate offset
		$offset = ( $paged - 1 ) * $per_page;

        // Total Count of entries
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

        // Select the limited data
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name LIMIT %d OFFSET %d",
			$per_page, $offset
		) );

        // Display the table
		if ( ! empty( $results ) ) {
			echo '<table class="wp-list-table widefat fixed striped tablesorter">';
			echo '<thead>
            <tr>
                <th>' . __( 'ID', 'wieczo-clamav' ) . '</th>
                <th>' . __( 'Benutzername', 'wieczo-clamav' ) . '</th>
                <th>' . __( 'Dateiname', 'wieczo-clamav' ) . '</th>
                <th>' . __( 'Erstellungsdatum', 'wieczo-clamav' ) . '</th>
            </tr>
          </thead>';
			echo '<tbody>';

			foreach ( $results as $row ) {
                // Get user object by login
				$user = get_user_by('login', $row->user_name);

				// Get the user admin link
				if ($user) {
					$userLink = get_edit_user_link($user->ID);
					$userNameWithLink = '<a href="' . esc_url($userLink) . '">' . esc_html($row->user_name) . '</a>';
				} else {
					// When the user's not found, just display the user_name
					$userNameWithLink = esc_html($row->user_name);
				}


				echo '<tr>';
				echo '<td>' . esc_html( $row->id ) . '</td>';
				echo '<td>' . $userNameWithLink . '</td>';
				echo '<td>' . esc_html( $row->filename ) . '</td>';
				echo '<td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $row->created_at ) ) ) . '</td>';
				echo '</tr>';
			}

			echo '</tbody>';
			echo '</table>';
		} else {
			echo '<p>' . __( 'Keine Daten gefunden.', 'wieczo-clamav' ) . '</p>';
		}

        // Count total pages
		$total_pages = ceil( $total_items / $per_page );

        // Show pagination links if there are more pages
		if ( $total_pages > 1 ) {
			echo paginate_links( [
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '?paged=%#%',
				'current'   => $paged,
				'total'     => $total_pages,
				'prev_text' => '« ' . __( 'Zurück', 'wieczo-clamav' ),
				'next_text' => __( 'Weiter', 'wieczo-clamav' ) . ' »',
			] );
		}
	}
}

