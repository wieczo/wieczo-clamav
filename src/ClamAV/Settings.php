<?php

namespace Wieczo\WordPress\Plugins\ClamAV;

class Settings {
	public function __construct() {
		add_action('admin_menu', [$this, 'addAdminMenu']);
		add_action('admin_init', [$this, 'initSettings']);
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
	}

	/**
	 * Displays the settings page for configuration the connection to ClamAV
	 * @return void
	 */
	public function showSettingsPage() {
		?>
        <div class="wrap">
            <h1><?php __( 'ClamAV Scanner Einstellungen', Config::LANGUAGE_DOMAIN ) ?></h1>
            <form method="post" action="options.php">
				<?php
				// Sicherheitsfelder und Einstellungen rendern
				settings_fields( 'wieczo_clamav_options_group' );
				do_settings_sections( 'wieczo_clamav_settings' );
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	public function initSettings() {
		// Define the settings
		register_setting( 'wieczo_clamav_options_group', 'clamav_host', [
			'type'        => 'string',
			'label'       => __( 'Hostname von ClamAV', Config::LANGUAGE_DOMAIN ),
			'description' => __( 'Wenn es sich um einen separaten Docker Container handelt, z.B. clamav. Wenn es lokal läuft, z.B. localhost', Config::LANGUAGE_DOMAIN ),
			'default'     => 'clamav',
		] );
		register_setting( 'wieczo_clamav_options_group', 'clamav_port', [
			'type'        => 'integer',
			'label'       => __( 'Port von ClamAV', Config::LANGUAGE_DOMAIN ),
			'description' => __( 'Der Standardwert ist 3310', Config::LANGUAGE_DOMAIN ),
			'default'     => 3310,
		] );
		register_setting( 'wieczo_clamav_options_group', 'clamav_timeout', [
			'type'        => 'integer',
			'label'       => __( 'Nach wie viel Sekunden soll die Verbindung abgebrochen werden', Config::LANGUAGE_DOMAIN ),
			'description' => __( 'Der Standartwert ist 30 Sekunden', Config::LANGUAGE_DOMAIN ),
			'default'     => 30,
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
}

