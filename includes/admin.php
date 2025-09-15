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
    
    if (isset($_POST['export_settings']) && check_admin_referer('wp_aprs_import_export_nonce')) {
        wp_aprs_handle_export();
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
                               value="<?php echo esc_attr($api_key_1); ?>" class="regular-text">
                        <p class="description">API-Schlüssel von <a href="https://aprs.fi/" target="_blank">aprs.fi</a></p>
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
                                   value="<?php echo esc_attr($api_key_2); ?>" class="regular-text">
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
                        <?php submit_button('Exportieren', 'secondary', 'export_settings', false); ?>
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
    $settings = json_decode($file_content, true);
    
    if (!$settings || !is_array($settings)) {
        echo '<div class="notice notice-error"><p>Ungültige Konfigurationsdatei.</p></div>';
        return;
    }
    
    // Alle Einstellungen importieren
    foreach ($settings as $key => $value) {
        if (strpos($key, 'wp_aprs_') === 0) {
            update_option($key, $value);
        }
    }
    
    echo '<div class="notice notice-success"><p>Einstellungen erfolgreich importiert.</p></div>';
}

// Export-Funktion
function wp_aprs_handle_export() {
    $settings = array();
    $all_options = wp_load_alloptions();
    
    // Nur WP-APRS Einstellungen sammeln
    foreach ($all_options as $key => $value) {
        if (strpos($key, 'wp_aprs_') === 0) {
            $settings[$key] = maybe_unserialize($value);
        }
    }
    
    // JSON exportieren
    $json = json_encode($settings, JSON_PRETTY_PRINT);
    
    // Datei downloaden
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="wp-aprs-export.cfg"');
    header('Content-Length: ' . strlen($json));
    
    echo $json;
    exit;
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