# Wieczo's Virus Scanner

**Wieczo's Virus Scanner** is a WordPress plugin that allows users to upload files from the WordPress admin area and scan them for viruses and malware by sending them to a ClamAV service.

Additionally, the plugin sends every upload to ClamAV first. If a virus is found, the upload is aborted.

The plugin connects to a ClamAV service using the **Host** and **Port** variables configured in the plugin settings.

## Features

- **File Upload**: Allows file uploads through a user-friendly admin page.
- **Upload Scan**: Scans all uploaded files for viruses and malware.
- **ClamAV Integration**: Sends files via a socket connection to a ClamAV service for virus scanning.
- **Easy Configuration**: Configure the host and port of the ClamAV service in the plugin settings.

## Installation

### Manual Installation:

1. Upload the `wieczo-clamav` directory to your WordPress installation's `/wp-content/plugins/` directory.
2. Activate the plugin in the WordPress Admin area under "Plugins."
3. Configure the ClamAV service under **Settings -> ClamAV Settings** (enter the host and port of the ClamAV service).

## Configuration

After activating the plugin, you can configure the connection information for your ClamAV service as follows:

1. Go to **Settings -> ClamAV Scanner**.
2. Enter the **Host** and **Port** of your ClamAV service, for example:
    - **Host**: `127.0.0.1` (if the service is running locally)
    - **Port**: `3310` (default port for ClamAV)

3. Optionally, you can set a **Timeout** to define the maximum wait time for the connection to the ClamAV service.

## Usage

### 1. Upload Scan

Once the plugin is activated, it automatically scans all uploads for viruses.

### 2. Manually Upload and Scan Files

1. Navigate to **ClamAV Scanner -> File Scanner** in the WordPress admin menu.
2. Select a file you want to scan for viruses and click "Scan File."
3. The plugin sends the file via a socket to the ClamAV service, and you will receive the scan result directly in the admin dashboard.

## How It Works

- The plugin uses the PHP `socket` functionality to establish a connection with a ClamAV service.
- Files uploaded through the WordPress admin interface are temporarily stored on the server.
- The plugin then opens a socket connection to the configured ClamAV host and port and sends the uploaded file for analysis.
- After the scan, the plugin displays the scan results on the admin page.

## Requirements

- WordPress 5.0 or higher
- A running **ClamAV service** that is accessible via a network socket (default port: 3310).
- PHP sockets must be enabled on the server.

## Development

### Local Development

1. Clone this repository into the `/wp-content/plugins/` directory of your local WordPress project:
   ```bash
   git clone https://github.com/wieczo/wieczo-clamav.git
