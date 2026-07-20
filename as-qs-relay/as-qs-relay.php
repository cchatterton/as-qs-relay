<?php
/**
 * Plugin Name: AS QS Relay
 * Plugin URI: https://github.com/cchatterton/as-qs-relay/releases/latest
 * Description: Maintains a first-party JSON cookie of tracked query-string touchpoints over time.
 * Version: 0.1.3
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Update URI: https://github.com/cchatterton/as-qs-relay
 * Author: AlphaSys
 * Author URI: https://alphasys.com.au
 * Text Domain: as-qs-relay
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ASQR_VERSION', '0.1.3');
define('ASQR_COOKIE_NAME', 'as_qs_relay');
define('ASQR_COOKIE_TTL', 90 * DAY_IN_SECONDS);
define('ASQR_MAX_TOUCHES', 20);
define('ASQR_OPTION_NAME', 'asqr_options');
define('ASQR_PLUGIN_FILE', __FILE__);
define('ASQR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASQR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASQR_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once ASQR_PLUGIN_DIR . 'functions/helpers.php';
require_once ASQR_PLUGIN_DIR . 'functions/setup.php';
require_once ASQR_PLUGIN_DIR . 'functions/assets.php';
require_once ASQR_PLUGIN_DIR . 'functions/admin.php';
require_once ASQR_PLUGIN_DIR . 'functions/rest.php';
require_once ASQR_PLUGIN_DIR . 'functions/updater.php';

add_action('plugins_loaded', 'asqr_boot');
