<?php
if (!defined('ABSPATH')) {
    exit;
}

function wp_aprs_register_shortcodes() {
    add_shortcode('WP-APRS-MAP', 'wp_aprs_map_shortcode');
    add_shortcode('WP-APRS-Callsigns', 'wp_aprs_callsigns_shortcode');
}

function wp_aprs_map_shortcode($atts) {
    $positions = wp_aprs_get_all_positions();
    
    if (empty($positions)) {
        return '<p>Keine APRS-Positionen verfügbar.</p>';
    }
    
    $map_center_input = get_option('wp_aprs_map_center', 'JO63HH');
    $center = wp_aprs_parse_coordinates($map_center_input);
    
    $map_size = get_option('wp_aprs_map_size');
    $size_style = '';
    if (!empty($map_size)) {
        $size_parts = explode('x', $map_size);
        if (count($size_parts) === 2) {
            $size_style = 'width: ' . intval($size_parts[0]) . 'px; height: ' . intval($size_parts[1]) . 'px;';
        }
    }
    
    $map_style = get_option('wp_aprs_map_style', 'osm_standard');
    
    static $map_count = 0;
    $map_count++;
    $map_id = 'wp-aprs-map-' . $map_count;
    
    $map_data = array(
        'id' => $map_id,
        'center' => $center,
        'positions' => $positions,
        'style' => $size_style,
        'map_style' => $map_style
    );
    
    wp_enqueue_style('leaflet', WP_APRS_PLUGIN_URL . 'vendor/leaflet/leaflet.css', array(), '1.7.1');
    wp_enqueue_script('leaflet', WP_APRS_PLUGIN_URL . 'vendor/leaflet/leaflet.js', array(), '1.7.1', true);
    wp_enqueue_script('wp-aprs-map', WP_APRS_PLUGIN_URL . 'assets/js/map.js', array('leaflet'), WP_APRS_VERSION, true);
    
    wp_localize_script('wp-aprs-map', 'wpAprsMapData_' . $map_count, $map_data);
    
    return '<div id="' . $map_id . '" class="wp-aprs-map" style="' . $size_style . '"></div>';
}

function wp_aprs_callsigns_shortcode($atts) {
    $callsigns = array();
    
    $callsigns_1 = get_option('wp_aprs_callsigns_1');
    if (!empty($callsigns_1)) {
        $callsigns = array_merge($callsigns, $callsigns_1);
    }
    
    $more_callsigns = get_option('wp_aprs_more_callsigns');
    if ($more_callsigns) {
        $callsigns_2 = get_option('wp_aprs_callsigns_2');
        if (!empty($callsigns_2)) {
            $callsigns = array_merge($callsigns, $callsigns_2);
        }
    }
    
    if (empty($callsigns)) {
        return '';
    }
    
    sort($callsigns, SORT_NATURAL | SORT_FLAG_CASE);
    $callsigns = array_filter($callsigns);
    
    return implode(', ', $callsigns);
}