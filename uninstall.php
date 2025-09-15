<?php
/**
 * Uninstall WP-APRS Plugin
 *
 * @package WP-APRS
 */

// Sicherheitsabfrage
if (!defined('ABSPATH') || !defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Plugin-Daten löschen
delete_option('wp_aprs_api_key_1');
delete_option('wp_aprs_api_key_2');
delete_option('wp_aprs_callsigns_1');
delete_option('wp_aprs_callsigns_2');
delete_option('wp_aprs_more_callsigns');
delete_option('wp_aprs_map_center');
delete_option('wp_aprs_map_size');
delete_option('wp_aprs_cache');
