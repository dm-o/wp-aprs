<?php
// Sicherheitsabfrage
if (!defined('ABSPATH')) {
    exit;
}

// API-Key Validierung in PHP
function wp_aprs_validate_api_key($api_key) {
    if (empty($api_key)) {
        return false;
    }
    
    // Länge prüfen
    if (strlen($api_key) < 20 || strlen($api_key) > 50) {
        return false;
    }
    
    // Zeichen prüfen - erlaubt: alphanumerisch, Punkt, Unterstrich, Bindestrich
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $api_key)) {
        return false;
    }
    
    return true;
}

// APRS.fi API abfragen - Robustere Version
function wp_aprs_query_api($api_key, $callsigns) {
    if (empty($api_key) || empty($callsigns)) {
        return false;
    }
    
    // API-Key Validierung
    if (!wp_aprs_validate_api_key($api_key)) {
        error_log('WP-APRS: Ungültiger API-Key Format');
        return false;
    }
    
    // Cache prüfen
    $cache_key = 'aprs_data_' . md5(implode(',', $callsigns) . '_' . $api_key);
    $cached_data = wp_aprs_get_cache($cache_key);
    
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    // API-URL erstellen (max. 10 Callsigns per Request)
    $chunked_callsigns = array_chunk($callsigns, 10);
    $all_entries = array();
    
    foreach ($chunked_callsigns as $chunk) {
        $callsign_string = implode(',', array_map('urlencode', $chunk));
        $url = "https://api.aprs.fi/api/get?name={$callsign_string}&what=loc&apikey={$api_key}&format=json";
        
        // API abfragen mit besserem Error Handling
        $response = wp_remote_get($url, array(
            'timeout' => 20,
            'sslverify' => true,
            'headers' => array(
                'User-Agent' => 'WP-APRS-Plugin/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('WP-APRS API Fehler: ' . $response->get_error_message());
            continue;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log("WP-APRS API HTTP Fehler: {$response_code}");
            continue;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data && isset($data['result']) && $data['result'] === 'ok') {
            if (isset($data['entries']) && is_array($data['entries'])) {
                $all_entries = array_merge($all_entries, $data['entries']);
            }
        } else {
            // API Fehler logging
            $error_msg = isset($data['description']) ? $data['description'] : 'Unknown error';
            error_log("WP-APRS API Error: {$error_msg}");
        }
        
        // Kurze Pause zwischen Requests
        usleep(500000); // 0.5 seconds
    }
    
    if (!empty($all_entries)) {
        wp_aprs_set_cache($cache_key, $all_entries, WP_APRS_CACHE_TIME);
        return $all_entries;
    }
    
    return false;
}

// Cache-Funktionen
function wp_aprs_get_cache($key) {
    $cache = get_option('wp_aprs_cache', array());
    
    if (isset($cache[$key])) {
        $data = $cache[$key];
        
        // Prüfen ob Cache abgelaufen
        if ($data['expires'] > time()) {
            return $data['data'];
        } else {
            // Abgelaufenen Cache löschen
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

// Koordinaten aus verschiedenen Eingabeformaten parsen
function wp_aprs_parse_coordinates($input) {
    // Locator (z.B. JO63HH)
    if (preg_match('/^[A-R]{2}[0-9]{2}[A-X]{2}$/i', $input)) {
        return wp_aprs_locator_to_coordinates($input);
    }
    
    // Koordinaten (z.B. 52.5200,13.4050)
    if (preg_match('/^(-?\d+\.\d+),\s*(-?\d+\.\d+)$/', $input, $matches)) {
        return array(
            'lat' => floatval($matches[1]),
            'lng' => floatval($matches[2])
        );
    }
    
    // Stadtname (Geocoding)
    return wp_aprs_geocode_location($input);
}

// Locator zu Koordinaten konvertieren
function wp_aprs_locator_to_coordinates($locator) {
    $locator = strtoupper($locator);
    
    $longitude = -180.0;
    $latitude = -90.0;
    
    // Erste zwei Zeichen (Field)
    $longitude += (ord($locator[0]) - 65) * 20;
    $latitude += (ord($locator[1]) - 65) * 10;
    
    // Zwei Ziffern (Square)
    $longitude += intval($locator[2]) * 2;
    $latitude += intval($locator[3]) * 1;
    
    // Letzte zwei Zeichen (Subsquare)
    $longitude += (ord($locator[4]) - 65) * (5.0 / 60.0);
    $latitude += (ord($locator[5]) - 65) * (2.5 / 60.0);
    
    // Mittelpunkt des Subsquare
    $longitude += 2.5 / 60.0;
    $latitude += 1.25 / 60.0;
    
    return array(
        'lat' => $latitude,
        'lng' => $longitude
    );
}

// Geocoding für Städtenamen
function wp_aprs_geocode_location($location) {
    $cache_key = 'geocode_' . md5($location);
    $cached = wp_aprs_get_cache($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    // Nominatim OpenStreetMap API
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($location);
    
    $response = wp_remote_get($url, array(
        'timeout' => 15,
        'headers' => array(
            'User-Agent' => 'WP-APRS Plugin/1.0'
        )
    ));
    
    if (is_wp_error($response)) {
        return array(
            'lat' => 52.5200, // Berlin als Fallback
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
        
        wp_aprs_set_cache($cache_key, $result, 86400); // 24 Stunden cache
        return $result;
    }
    
    // Fallback zu Berlin
    return array(
        'lat' => 52.5200,
        'lng' => 13.4050
    );
}

// Alle Positionsdaten abrufen
function wp_aprs_get_all_positions() {
    $positions = array();
    
    // Erste Gruppe von Rufzeichen
    $api_key_1 = get_option('wp_aprs_api_key_1');
    $callsigns_1 = get_option('wp_aprs_callsigns_1', array('DO6DAD-7', 'DO0RM-10'));
    
    if (!empty($api_key_1) && !empty($callsigns_1)) {
        $data_1 = wp_aprs_query_api($api_key_1, $callsigns_1);
        if ($data_1) {
            $positions = array_merge($positions, $data_1);
        }
    }
    
    // Zweite Gruppe von Rufzeichen (falls aktiviert)
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

// AJAX Handler für API-Key Testing
function wp_aprs_ajax_test_api_key() {
    check_ajax_referer('wp_aprs_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
    
    if (empty($api_key)) {
        wp_send_json_error(array('message' => 'API-Schlüssel ist leer'));
    }
    
    // API-Key Validierung
    if (!wp_aprs_validate_api_key($api_key)) {
        wp_send_json_error(array('message' => 'Ungültiges Format (20-50 Zeichen, erlaubt: A-Z a-z 0-9 . _ -)'));
    }
    
    // Einfachen Test-Call machen
    $url = "https://api.aprs.fi/api/get?name=DO6DAD-7&what=loc&apikey={$api_key}&format=json";
    
    $response = wp_remote_get($url, array(
        'timeout' => 15,
        'sslverify' => true,
        'headers' => array(
            'User-Agent' => 'WP-APRS-Plugin/1.0'
        )
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Netzwerkfehler: ' . $response->get_error_message()));
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($response_code === 200) {
        if (isset($data['result']) && $data['result'] === 'ok') {
            wp_send_json_success(array('message' => 'API-Schlüssel ist gültig und funktioniert'));
        } else {
            $error = isset($data['description']) ? $data['description'] : 'Unbekannter Fehler';
            wp_send_json_error(array('message' => 'API-Fehler: ' . $error));
        }
    } else {
        wp_send_json_error(array('message' => "HTTP Fehler: {$response_code}"));
    }
}
add_action('wp_ajax_wp_aprs_test_api_key', 'wp_aprs_ajax_test_api_key');