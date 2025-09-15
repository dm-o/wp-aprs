<?php
// Sicherheitsabfrage
if (!defined('ABSPATH')) {
    exit;
}

// Admin-Menü hinzufügen
function wp_aprs_admin_menu() {
    // Hauptmenüeintrag
    add_menu_page(
        'WP-APRS Einstellungen',
        'WP-APRS',
        'manage_options',
        'wp-aprs',
        'wp_aprs_settings_page',
        'dashicons-location-alt',
        80
    );
    
    // Untermenüeintrag für die gleiche Seite (optional)
    add_submenu_page(
        'wp-aprs',
        'WP-APRS Einstellungen',
        'Einstellungen',
        'manage_options',
        'wp-aprs',
        'wp_aprs_settings_page'
    );
    
    // Debug Seite
    add_submenu_page(
        'wp-aprs',
        'WP-APRS Debug',
        'Debug',
        'manage_options',
        'wp-aprs-debug',
        'wp_aprs_debug_page'
    );
}
add_action('admin_menu', 'wp_aprs_admin_menu');

// Einstellungsseite
function wp_aprs_settings_page() {
    // Berechtigungen prüfen
    if (!current_user_can('manage_options')) {
        wp_die('Sie haben keine Berechtigung für diese Seite.');
    }
    
    // Import/Export Handling
    if (isset($_POST['import_settings']) && check_admin_referer('wp_aprs_import_export_nonce')) {
        wp_aprs_handle_import();
    }
    
    if (isset($_GET['export_confirmed']) && $_GET['export_confirmed'] === '1') {
        if (check_admin_referer('wp_aprs_export_nonce', 'export_nonce')) {
            wp_aprs_handle_export();
        }
    }
    
    // Speichern der Einstellungen
    if (isset($_POST['submit']) && check_admin_referer('wp_aprs_settings_nonce')) {
        // API-Schlüssel speichern
        if (isset($_POST['api_key_1'])) {
            update_option('wp_aprs_api_key_1', sanitize_text_field($_POST['api_key_1']));
        }
        
        // Rufzeichen 1 speichern
        $callsigns_1 = array();
        for ($i = 1; $i <= 20; $i++) {
            $field_name = 'callsign_1_' . $i;
            if (!empty($_POST[$field_name])) {
                $callsigns_1[] = sanitize_text_field($_POST[$field_name]);
            }
        }
        update_option('wp_aprs_callsigns_1', $callsigns_1);
        
        // Mehr Rufzeichen Option
        $more_callsigns = isset($_POST['more_callsigns']) ? true : false;
        update_option('wp_aprs_more_callsigns', $more_callsigns);
        
        // Zweiter API-Schlüssel und Rufzeichen
        if ($more_callsigns) {
            if (isset($_POST['api_key_2'])) {
                update_option('wp_aprs_api_key_2', sanitize_text_field($_POST['api_key_2']));
            }
            
            $callsigns_2 = array();
            for ($i = 1; $i <= 20; $i++) {
                $field_name = 'callsign_2_' . $i;
                if (!empty($_POST[$field_name])) {
                    $callsigns_2[] = sanitize_text_field($_POST[$field_name]);
                }
            }
            update_option('wp_aprs_callsigns_2', $callsigns_2);
        } else {
            // Wenn "Mehr Rufzeichen" deaktiviert ist, leeren wir die zweiten Einstellungen
            update_option('wp_aprs_api_key_2', '');
            update_option('wp_aprs_callsigns_2', array());
        }
        
        // Kartenmittelpunkt
        if (isset($_POST['map_center'])) {
            update_option('wp_aprs_map_center', sanitize_text_field($_POST['map_center']));
        }
        
        // Kartengröße
        if (isset($_POST['map_size'])) {
            update_option('wp_aprs_map_size', sanitize_text_field($_POST['map_size']));
        }
        
        // Kartenstil
        if (isset($_POST['map_style'])) {
            update_option('wp_aprs_map_style', sanitize_text_field($_POST['map_style']));
        }
        
        echo '<div class="notice notice-success"><p>Einstellungen erfolgreich gespeichert.</p></div>';
    }
    
    // Export-Status anzeigen
    if (isset($_GET['export_status'])) {
        if ($_GET['export_status'] === 'success') {
            echo '<div class="notice notice-success"><p>✅ Export erfolgreich abgeschlossen.</p></div>';
        } elseif ($_GET['export_status'] === 'cancelled') {
            echo '<div class="notice notice-warning"><p>❌ Export abgebrochen.</p></div>';
        }
    }
    
    // Aktuelle Einstellungen laden
    $api_key_1 = get_option('wp_aprs_api_key_1', '');
    $callsigns_1 = get_option('wp_aprs_callsigns_1', array('DO6DAD-7', 'DO0RM-10'));
    $more_callsigns = get_option('wp_aprs_more_callsigns', false);
    $api_key_2 = get_option('wp_aprs_api_key_2', '');
    $callsigns_2 = get_option('wp_aprs_callsigns_2', array());
    $map_center = get_option('wp_aprs_map_center', 'JO63HH');
    $map_size = get_option('wp_aprs_map_size', '');
    $map_style = get_option('wp_aprs_map_style', 'topo');
    
    // Verfügbare Kartenstile
    $available_styles = array(
        'osm_standard' => 'OpenStreetMap Standard',
        'osm_de' => 'OpenStreetMap DE',
        'osm_hot' => 'Humanitarian OSM',
        'topo' => 'Topographic Map',
        'cyclosm' => 'Cycle OSM',
        'dark' => 'Dark Mode'
    );
    ?>
    <div class="wrap">
        <h1>WP-APRS Einstellungen</h1>
        
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('wp_aprs_settings_nonce'); ?>
            
            <h2>API-Einstellungen</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="api_key_1">APRS.fi API-Schlüssel</label></th>
                    <td>
                        <input type="text" name="api_key_1" id="api_key_1" 
                               value="<?php echo esc_attr($api_key_1); ?>" class="regular-text"
                               pattern="[a-zA-Z0-9]{20,50}" 
                               title="Ihr APRS.fi API-Schlüssel (20-50 alphanumerische Zeichen)">
                        <button type="button" class="button button-secondary test-api-key" style="margin-left: 10px;">
                            API-Schlüssel testen
                        </button>
                        <p class="description">
                            API-Schlüssel von <a href="https://aprs.fi/" target="_blank">aprs.fi</a><br>
                            <small>Format: 20-50 alphanumerische Zeichen (keine Sonderzeichen)</small>
                        </p>
                    </td>
                </tr>
            </table>
            
            <h2>Rufzeichen (1-20) <small style="font-weight: normal; color: #666;">Hinweis: pro API-Schlüssel können maximal 20 Rufzeichen abgerufen werden!</small></h2>
            <table class="form-table" id="callsigns-table-1">
                <?php for ($i = 1; $i <= 20; $i++): 
                    $callsign_value = isset($callsigns_1[$i-1]) ? $callsigns_1[$i-1] : '';
                ?>
                <tr>
                    <th scope="row"><label for="callsign_1_<?php echo $i; ?>">Rufzeichen <?php echo $i; ?></label></th>
                    <td>
                        <input type="text" name="callsign_1_<?php echo $i; ?>" id="callsign_1_<?php echo $i; ?>" 
                               value="<?php echo esc_attr($callsign_value); ?>" class="regular-text"
                               title="Hier bitte das Rufzeichen oder die Objekt-Bezeichnung inkl. Suffix eingeben (Bsp: DO6DAD-7).">
                    </td>
                </tr>
                <?php endfor; ?>
            </table>
            
            <h2>Weitere Rufzeichen</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Mehr Rufzeichen</th>
                    <td>
                        <label for="more_callsigns">
                            <input type="checkbox" name="more_callsigns" id="more_callsigns" value="1" 
                                <?php checked($more_callsigns, true); ?>>
                            Zusätzliche Rufzeichen aktivieren (bis zu 40 Rufzeichen)
                        </label>
                    </td>
                </tr>
            </table>
            
            <div id="more-callsigns-section" style="<?php echo $more_callsigns ? '' : 'display: none;'; ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="api_key_2">Zweiter API-Schlüssel</label></th>
                        <td>
                            <input type="text" name="api_key_2" id="api_key_2" 
                                   value="<?php echo esc_attr($api_key_2); ?>" class="regular-text"
                                   pattern="[a-zA-Z0-9]{20,50}" 
                                   title="Optionaler zweiter API-Schlüssel (20-50 alphanumerische Zeichen)">
                            <button type="button" class="button button-secondary test-api-key" style="margin-left: 10px;">
                                API-Schlüssel testen
                            </button>
                            <p class="description">Optionaler zweiter API-Schlüssel für zusätzliche Rufzeichen</p>
                        </td>
                    </tr>
                </table>
                
                <h2>Zusätzliche Rufzeichen (21-40) <small style="font-weight: normal; color: #666;">Hinweis: pro API-Schlüssel können maximal 20 Rufzeichen abgerufen werden!</small></h2>
                <table class="form-table" id="callsigns-table-2">
                    <?php for ($i = 1; $i <= 20; $i++): 
                        $callsign_value = isset($callsigns_2[$i-1]) ? $callsigns_2[$i-1] : '';
                    ?>
                    <tr>
                        <th scope="row"><label for="callsign_2_<?php echo $i; ?>">Rufzeichen <?php echo $i + 20; ?></label></th>
                        <td>
                            <input type="text" name="callsign_2_<?php echo $i; ?>" id="callsign_2_<?php echo $i; ?>" 
                                   value="<?php echo esc_attr($callsign_value); ?>" class="regular-text"
                                   title="Hier bitte das Rufzeichen oder die Objekt-Bezeichnung inkl. Suffix eingeben (Bsp: DO6DAD-7).">
                        </td>
                    </tr>
                    <?php endfor; ?>
                </table>
            </div>
            
            <h2>Karteneinstellungen</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="map_center">Kartenmittelpunkt</label></th>
                    <td>
                        <input type="text" name="map_center" id="map_center" 
                               value="<?php echo esc_attr($map_center); ?>" class="regular-text">
                        <p class="description">Locator (JO63HH), Koordinaten (52.5200,13.4050) oder Stadtname (Berlin)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="map_size">Kartengröße (optional)</label></th>
                    <td>
                        <input type="text" name="map_size" id="map_size" 
                               value="<?php echo esc_attr($map_size); ?>" class="regular-text">
                        <p class="description">Format: BreitexHöhe (z.B. 800x600) - leer lassen für automatische Größe</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="map_style">Kartenstil</label></th>
                    <td>
                        <select name="map_style" id="map_style" class="regular-text">
                            <?php foreach ($available_styles as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" 
                                <?php selected($map_style, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Wählen Sie den gewünschten Kartenstil aus</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Einstellungen speichern'); ?>
        </form>
        
        <hr style="margin: 40px 0;">
        
        <h2>Import/Export Einstellungen</h2>
        
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('wp_aprs_import_export_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="import_file">Einstellungen importieren</label></th>
                    <td>
                        <input type="file" name="import_file" id="import_file" accept=".cfg">
                        <p class="description">WP-APRS Konfigurationsdatei (.cfg) auswählen</p>
                        <?php submit_button('Importieren', 'secondary', 'import_settings', false); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Einstellungen exportieren</th>
                    <td>
                        <p class="description">Laden Sie Ihre aktuellen Einstellungen als Konfigurationsdatei herunter</p>
                        <button type="button" class="button button-secondary" id="start-export">Export starten</button>
                        <p class="description" style="color: #d63638; font-weight: bold;">
                            ⚠️ Enthält API-Schlüssel im Klartext!
                        </p>
                    </td>
                </tr>
            </table>
        </form>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Mehr Rufzeichen Checkbox Handler
        $('#more_callsigns').change(function() {
            if ($(this).is(':checked')) {
                $('#more-callsigns-section').slideDown();
            } else {
                $('#more-callsigns-section').slideUp();
            }
        });
        
        // Initialen Zustand setzen
        if ($('#more_callsigns').is(':checked')) {
            $('#more-callsigns-section').show();
        } else {
            $('#more-callsigns-section').hide();
        }
        
        // Tooltip für alle Rufzeichen-Felder
        $('input[id^="callsign_"]').each(function() {
            $(this).attr('title', 'Hier bitte das Rufzeichen oder die Objekt-Bezeichnung inkl. Suffix eingeben (Bsp: DO6DAD-7).');
        });
        
        // Export Popup Modal erstellen
        function createExportModal() {
            if ($('#wp-aprs-export-modal').length === 0) {
                var modalHTML = `
                <div id="wp-aprs-export-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; width: 500px; max-width: 90%;">
                        <h2 style="color: #d63638; margin-top: 0;">⚠️ Sicherheitswarnung</h2>
                        <p><strong>Achtung: Die Export-Datei enthält APRS.fi-API-Schlüssel im Klartext!</strong></p>
                        <p>Die Datei enthält sensible Zugangsdaten. Bewahren Sie sie sicher auf und teilen Sie sie nur mit vertrauenswürdigen Personen.</p>
                        <p><strong>Sind Sie sicher, dass Sie die Export-Datei herunterladen wollen?</strong></p>
                        <div style="margin-top: 25px; text-align: center;">
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wp-aprs&export_confirmed=1'), 'wp_aprs_export_nonce', 'export_nonce'); ?>" 
                               class="button button-primary" id="export-confirm-btn" style="background-color: #46b450; border-color: #46b450; color: white; font-weight: bold; padding: 10px 20px; margin-right: 15px;">
                                ✅ Ja, herunterladen
                            </a>
                            <button class="button button-secondary" id="export-cancel-btn" style="background-color: #dc3232; border-color: #dc3232; color: white; font-weight: bold; padding: 10px 20px;">
                                ❌ Nein - Abbruch!
                            </button>
                        </div>
                    </div>
                </div>`;
                $('body').append(modalHTML);
            }
        }
        
        // Export Start Handler
        $('#start-export').click(function(e) {
            e.preventDefault();
            createExportModal();
            $('#wp-aprs-export-modal').fadeIn();
        });
        
        // Export Abbruch Handler
        $(document).on('click', '#export-cancel-btn', function(e) {
            e.preventDefault();
            $('#wp-aprs-export-modal').fadeOut();
            // Redirect mit Abbruch-Status
            setTimeout(function() {
                window.location.href = '<?php echo admin_url('admin.php?page=wp-aprs&export_status=cancelled'); ?>';
            }, 300);
        });
        
        // Modal schließen bei Klick außerhalb
        $(document).on('click', '#wp-aprs-export-modal', function(e) {
            if (e.target.id === 'wp-aprs-export-modal') {
                $('#wp-aprs-export-modal').fadeOut();
                setTimeout(function() {
                    window.location.href = '<?php echo admin_url('admin.php?page=wp-aprs&export_status=cancelled'); ?>';
                }, 300);
            }
        });
        
        // ESC-Taste zum Schließen
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#wp-aprs-export-modal').is(':visible')) {
                $('#wp-aprs-export-modal').fadeOut();
                setTimeout(function() {
                    window.location.href = '<?php echo admin_url('admin.php?page=wp-aprs&export_status=cancelled'); ?>';
                }, 300);
            }
        });
    });
    </script>
    <?php
}

// Import-Funktion
function wp_aprs_handle_import() {
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="notice notice-error"><p>Fehler beim Hochladen der Datei.</p></div>';
        return;
    }
    
    $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
    $data = json_decode($file_content, true);
    
    if (!$data || !isset($data['settings']) || !is_array($data['settings'])) {
        echo '<div class="notice notice-error"><p>Ungültige Konfigurationsdatei.</p></div>';
        return;
    }
    
    $settings = $data['settings'];
    
    // Nur WP-APRS Einstellungen importieren
    $allowed_settings = array(
        'wp_aprs_api_key_1',
        'wp_aprs_api_key_2',
        'wp_aprs_callsigns_1',
        'wp_aprs_callsigns_2',
        'wp_aprs_more_callsigns',
        'wp_aprs_map_center',
        'wp_aprs_map_size',
        'wp_aprs_map_style'
    );
    
    foreach ($settings as $key => $value) {
        if (in_array($key, $allowed_settings)) {
            update_option($key, $value);
        }
    }
    
    echo '<div class="notice notice-success"><p>Einstellungen erfolgreich importiert.</p></div>';
}

// Export-Funktion
function wp_aprs_handle_export() {
    // Nur Konfigurationseinstellungen exportieren (keine API-Daten)
    $export_settings = array(
        'wp_aprs_api_key_1' => get_option('wp_aprs_api_key_1', ''),
        'wp_aprs_api_key_2' => get_option('wp_aprs_api_key_2', ''),
        'wp_aprs_callsigns_1' => get_option('wp_aprs_callsigns_1', array('DO6DAD-7', 'DO0RM-10')),
        'wp_aprs_callsigns_2' => get_option('wp_aprs_callsigns_2', array()),
        'wp_aprs_more_callsigns' => get_option('wp_aprs_more_callsigns', false),
        'wp_aprs_map_center' => get_option('wp_aprs_map_center', 'JO63HH'),
        'wp_aprs_map_size' => get_option('wp_aprs_map_size', ''),
        'wp_aprs_map_style' => get_option('wp_aprs_map_style', 'topo')
    );
    
    // Meta-Informationen hinzufügen
    $export_data = array(
        'version' => WP_APRS_VERSION,
        'export_date' => current_time('mysql'),
        'site_url' => get_site_url(),
        'settings' => $export_settings
    );
    
    // JSON exportieren
    $json = json_encode($export_data, JSON_PRETTY_PRINT);
    
    // Temporäre Datei erstellen
    $temp_file = wp_tempnam('wp-aprs-export');
    file_put_contents($temp_file, $json);
    
    // Datei downloaden
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="wp-aprs-export.cfg"');
    header('Content-Length: ' . filesize($temp_file));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($temp_file);
    
    // Temporäre Datei löschen
    unlink($temp_file);
    
    exit;
}

// Debug Seite
function wp_aprs_debug_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $api_key_1 = get_option('wp_aprs_api_key_1', '');
    $api_key_2 = get_option('wp_aprs_api_key_2', '');
    $test_result = null;
    
    if (isset($_POST['test_api_key'])) {
        $api_key_to_test = sanitize_text_field($_POST['api_key_to_test']);
        $test_result = wp_aprs_test_api_key($api_key_to_test);
    }
    ?>
    <div class="wrap">
        <h1>WP-APRS Debug</h1>
        
        <h2>API-Schlüssel Test</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="api_key_to_test">API-Schlüssel testen</label></th>
                    <td>
                        <input type="text" name="api_key_to_test" id="api_key_to_test" class="regular-text" 
                               placeholder="Geben Sie einen API-Schlüssel zum Testen ein">
                        <?php submit_button('API-Schlüssel testen', 'primary', 'test_api_key', false); ?>
                    </td>
                </tr>
            </table>
        </form>
        
        <?php if ($test_result): ?>
        <div class="notice notice-<?php echo $test_result['success'] ? 'success' : 'error'; ?>">
            <p><strong>Test Ergebnis:</strong> <?php echo esc_html($test_result['message']); ?></p>
        </div>
        <?php endif; ?>
        
        <h2>Gespeicherte Einstellungen</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Einstellung</th>
                    <th>Wert</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>API-Schlüssel 1</td>
                    <td><?php echo esc_html($api_key_1 ? '***' . substr($api_key_1, -4) : 'Nicht gesetzt'); ?></td>
                </tr>
                <tr>
                    <td>API-Schlüssel 2</td>
                    <td><?php echo esc_html($api_key_2 ? '***' . substr($api_key_2, -4) : 'Nicht gesetzt'); ?></td>
                </tr>
                <tr>
                    <td>Rufzeichen 1</td>
                    <td><?php echo esc_html(implode(', ', get_option('wp_aprs_callsigns_1', array()))); ?></td>
                </tr>
                <tr>
                    <td>Rufzeichen 2</td>
                    <td><?php echo esc_html(implode(', ', get_option('wp_aprs_callsigns_2', array()))); ?></td>
                </tr>
                <tr>
                    <td>Cache Einträge</td>
                    <td><?php echo count(get_option('wp_aprs_cache', array())); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h2>Systeminformationen</h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <td>PHP Version</td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td>WordPress Version</td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td>cURL Unterstützung</td>
                    <td><?php echo function_exists('curl_init') ? 'Ja' : 'Nein'; ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}

// API-Key Test Funktion
function wp_aprs_test_api_key($api_key) {
    if (empty($api_key)) {
        return array('success' => false, 'message' => 'API-Schlüssel ist leer');
    }
    
    // Validierung des Formats
    if (strlen($api_key) < 20 || strlen($api_key) > 50 || !preg_match('/^[a-zA-Z0-9]+$/', $api_key)) {
        return array('success' => false, 'message' => 'Ungültiges Format (20-50 alphanumerische Zeichen erforderlich)');
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
        return array('success' => false, 'message' => 'Netzwerkfehler: ' . $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($response_code === 200) {
        if (isset($data['result']) && $data['result'] === 'ok') {
            return array('success' => true, 'message' => 'API-Schlüssel ist gültig und funktioniert');
        } else {
            $error = isset($data['description']) ? $data['description'] : 'Unbekannter Fehler';
            return array('success' => false, 'message' => 'API-Fehler: ' . $error);
        }
    } else {
        return array('success' => false, 'message' => "HTTP Fehler: {$response_code}");
    }
}

// Admin-Skripte und Styles
function wp_aprs_admin_scripts($hook) {
    // Nur auf unserer Settings-Seite laden
    if (strpos($hook, 'wp-aprs') === false) {
        return;
    }
    
    wp_enqueue_style('wp-aprs-admin', WP_APRS_PLUGIN_URL . 'assets/css/admin.css', array(), WP_APRS_VERSION);
    wp_enqueue_script('wp-aprs-admin', WP_APRS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WP_APRS_VERSION, true);
    
    // Localize script für AJAX etc.
    wp_localize_script('wp-aprs-admin', 'wpAprsAdmin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_aprs_admin_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'wp_aprs_admin_scripts');

// Plugin-Link zu den Einstellungen hinzufügen
function wp_aprs_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wp-aprs') . '">Einstellungen</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_wp-aprs/wp-aprs.php', 'wp_aprs_plugin_action_links');

// Admin initialisieren
function wp_aprs_admin_init() {
    // Hier können weitere Admin-Initialisierungen erfolgen
}
add_action('admin_init', 'wp_aprs_admin_init');