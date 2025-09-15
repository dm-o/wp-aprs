<?php
/**
 * Plugin Name: WP-APRS
 * Plugin URI: https://github.com/dm-o/wp-aprs
 * Description: APRS Position Tracking mit Kartenansicht
 * Version: 1.0.0
 * Author: Steffan Jeschek (DO6DAD.de)
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-aprs
 */

// Sicherheitsabfrage
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
define('WP_APRS_VERSION', '1.0.0');
define('WP_APRS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_APRS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_APRS_CACHE_TIME', 300);

// Internationalisierung laden
load_plugin_textdomain('wp-aprs', false, dirname(plugin_basename(__FILE__)) . '/languages');

// Einbinden der notwendigen Dateien
require_once WP_APRS_PLUGIN_DIR . 'includes/admin.php';
require_once WP_APRS_PLUGIN_DIR . 'includes/frontend.php';
require_once WP_APRS_PLUGIN_DIR . 'includes/api.php';
require_once WP_APRS_PLUGIN_DIR . 'includes/shortcodes.php';

// Plugin aktivieren
register_activation_hook(__FILE__, 'wp_aprs_activate');
function wp_aprs_activate() {
    add_option('wp_aprs_api_key_1', '');
    add_option('wp_aprs_api_key_2', '');
    add_option('wp_aprs_callsigns_1', array());
    add_option('wp_aprs_callsigns_2', array());
    add_option('wp_aprs_more_callsigns', false);
    add_option('wp_aprs_map_center', 'JO63HH');
    add_option('wp_aprs_map_size', '');
    add_option('wp_aprs_cache', array());
}

// Plugin deaktivieren
register_deactivation_hook(__FILE__, 'wp_aprs_deactivate');
function wp_aprs_deactivate() {
    delete_option('wp_aprs_cache');
}

// Plugin deinstallieren
register_uninstall_hook(__FILE__, 'wp_aprs_uninstall');
function wp_aprs_uninstall() {
    delete_option('wp_aprs_api_key_1');
    delete_option('wp_aprs_api_key_2');
    delete_option('wp_aprs_callsigns_1');
    delete_option('wp_aprs_callsigns_2');
    delete_option('wp_aprs_more_callsigns');
    delete_option('wp_aprs_map_center');
    delete_option('wp_aprs_map_size');
    delete_option('wp_aprs_cache');
}

// Plugin initialisieren
function wp_aprs_init() {
    if (is_admin()) {
        wp_aprs_admin_init();
    }
    wp_aprs_frontend_init();
    wp_aprs_register_shortcodes();
}
add_action('plugins_loaded', 'wp_aprs_init');
