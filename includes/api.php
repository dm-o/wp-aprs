<?php
if (!defined('ABSPATH')) {
    exit;
}

function wp_aprs_query_api($api_key, $callsigns) {
    if (empty($api_key) || empty($callsigns)) {
        return false;
    }
    
    $cache_key = 'aprs_data_' . md5(implode(',', $callsigns));
    $cached_data = wp_aprs_get_cache($cache_key);
    
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    $callsign_string = implode(',', array_map('urlencode', $callsigns));
    $url = "https://api.aprs.fi/api/get?name={$callsign_string}&what=loc&apikey={$api_key}&format=json";
    
    $response = wp_remote_get($url, array(
        'timeout' => 15,
        'sslverify' => false
    ));
    
    if (is_wp_error($response)) {
        error_log('WP-APRS API Fehler: ' . $response->get_error_message());
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data && isset($data['found']) && $data['found'] > 0) {
        wp_aprs_set_cache($cache_key, $data['entries'], WP_APRS_CACHE_TIME);
        return $data['entries'];
    }
    
    return false;
}

function wp_aprs_get_cache($key) {
    $cache = get_option('wp_aprs_cache', array());
    
    if (isset($cache[$key])) {
        $data = $cache[$key];
        if ($data['expires'] > time()) {
            return $data['data'];
        } else {
            unset($cache[$key]);
            update_option('wp_aprs_cache', $cache);
        }
    }
    
    return false;
}

function wp_aprs_set_cache($key, $data, $expires = 300) {
    $cache = get_option('wp_aprs_cache', array());
    
    $cache[$key] = array(
        'data' => $data,
        'expires' => time() + $expires
    );
    
    update_option('wp_aprs_cache', $cache);
    return true;
}

function wp_aprs_parse_coordinates($input) {
    if (preg_match('/^[A-R]{2}[0-9]{2}[A-X]{2}$/i', $input)) {
        return wp_aprs_locator_to_coordinates($input);
    }
    
    if (preg_match('/^(-?\d+\.\d+),\s*(-?\d+\.\d+)$/', $input, $matches)) {
        return array(
            'lat' => floatval($matches[1]),
            'lng' => floatval($matches[2])
        );
    }
    
    return wp_aprs_geocode_location($input);
}

function wp_aprs_locator_to_coordinates($locator) {
    $locator = strtoupper($locator);
    $longitude = -180.0;
    $latitude = -90.0;
    
    $longitude += (ord($locator[0]) - 65) * 20;
    $latitude += (ord($locator[1]) - 65) * 10;
    
    $longitude += intval($locator[2]) * 2;
    $latitude += intval($locator[3]) * 1;
    
    $longitude += (ord($locator[4]) - 65) * (5.0 / 60.0);
    $latitude += (ord($locator[5]) - 65) * (2.5 / 60.0);
    
    $longitude += 2.5 / 60.0;
    $latitude += 1.25 / 60.0;
    
    return array(
        'lat' => $latitude,
        'lng' => $longitude
    );
}

function wp_aprs_geocode_location($location) {
    $cache_key = 'geocode_' . md5($location);
    $cached = wp_aprs_get_cache($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($location);
    
    $response = wp_remote_get($url, array(
        'timeout' => 15,
        'headers' => array(
            'User-Agent' => 'WP-APRS Plugin/1.0'
        )
    ));
    
    if (is_wp_error($response)) {
        return array(
            'lat' => 52.5200,
            'lng' => 13.4050
        );
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
        $result = array(
            'lat' => floatval($data[0]['lat']),
            'lng' => floatval($data[0]['lon'])
        );
        
        wp_aprs_set_cache($cache_key, $result, 86400);
        return $result;
    }
    
    return array(
        'lat' => 52.5200,
        'lng' => 13.4050
    );
}

function wp_aprs_get_all_positions() {
    $positions = array();
    
    $api_key_1 = get_option('wp_aprs_api_key_1');
    $callsigns_1 = get_option('wp_aprs_callsigns_1');
    
    if (!empty($api_key_1) && !empty($callsigns_1)) {
        $data_1 = wp_aprs_query_api($api_key_1, $callsigns_1);
        if ($data_1) {
            $positions = array_merge($positions, $data_1);
        }
    }
    
    $more_callsigns = get_option('wp_aprs_more_callsigns');
    if ($more_callsigns) {
        $api_key_2 = get_option('wp_aprs_api_key_2');
        $callsigns_2 = get_option('wp_aprs_callsigns_2');
        
        if (!empty($api_key_2) && !empty($callsigns_2)) {
            $data_2 = wp_aprs_query_api($api_key_2, $callsigns_2);
            if ($data_2) {
                $positions = array_merge($positions, $data_2);
            }
        }
    }
    
    return $positions;
}