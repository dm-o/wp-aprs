<?php
if (!defined('ABSPATH')) {
    exit;
}

function wp_aprs_admin_menu() {
    add_options_page(
        'WP-APRS Einstellungen',
        'WP-APRS',
        'manage_options',
        'wp-aprs',
        'wp_aprs_settings_page'
    );
}
add_action('admin_menu', 'wp_aprs_admin_menu');

function wp_aprs_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['submit'])) {
        check_admin_referer('wp_aprs_settings');
        
        update_option('wp_aprs_api_key_1', sanitize_text_field($_POST['api_key_1']));
        
        $callsigns_1 = array();
        for ($i = 1; $i <= 20; $i++) {
            if (!empty($_POST['callsign_1_' . $i])) {
                $callsigns_1[] = sanitize_text_field($_POST['callsign_1_' . $i]);
            }
        }
        update_option('wp_aprs_callsigns_1', $callsigns_1);
        
        $more_callsigns = isset($_POST['more_callsigns']) ? true : false;
        update_option('wp_aprs_more_callsigns', $more_callsigns);
        
        if ($more_callsigns) {
            update_option('wp_aprs_api_key_2', sanitize_text_field($_POST['api_key_2']));
            
            $callsigns_2 = array();
            for ($i = 1; $i <= 20; $i++) {
                if (!empty($_POST['callsign_2_' . $i])) {
                    $callsigns_2[] = sanitize_text_field($_POST['callsign_2_' . $i]);
                }
            }
            update_option('wp_aprs_callsigns_2', $callsigns_2);
        }
        
        update_option('wp_aprs_map_center', sanitize_text_field($_POST['map_center']));
        update_option('wp_aprs_map_size', sanitize_text_field($_POST['map_size']));
        
        echo '<div class="notice notice-success"><p>Einstellungen gespeichert.</p></div>';
    }
    
    $api_key_1 = get_option('wp_aprs_api_key_1');
    $callsigns_1 = get_option('wp_aprs_callsigns_1');
    $more_callsigns = get_option('wp_aprs_more_callsigns');
    $api_key_2 = get_option('wp_aprs_api_key_2');
    $callsigns_2 = get_option('wp_aprs_callsigns_2');
    $map_center = get_option('wp_aprs_map_center');
    $map_size = get_option('wp_aprs_map_size');
    ?>
    <div class="wrap">
        <h1>WP-APRS Einstellungen</h1>
        <form method="post">
            <?php wp_nonce_field('wp_aprs_settings'); ?>
            <h2>API-Einstellungen</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="api_key_1">APRS.fi API-Schlüssel</label></th>
                    <td>
                        <input type="text" name="api_key_1" id="api_key_1" value="<?php echo esc_attr($api_key_1); ?>" class="regular-text">
                        <p class="description">API-Schlüssel von <a href="https://aprs.fi/" target="_blank">aprs.fi</a></p>
                    </td>
                </tr>
            </table>
            
            <h2>Rufzeichen (1-20)</h2>
            <table class="form-table" id="callsigns-table-1">
                <?php for ($i = 1; $i <= 20; $i++): ?>
                <tr>
                    <th scope="row"><label for="callsign_1_<?php echo $i; ?>">Rufzeichen <?php echo $i; ?></label></th>
                    <td>
                        <input type="text" name="callsign_1_<?php echo $i; ?>" id="callsign_1_<?php echo $i; ?>" 
                               value="<?php echo isset($callsigns_1[$i-1]) ? esc_attr($callsigns_1[$i-1]) : ''; ?>" class="regular-text">
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
                            <input type="checkbox" name="more_callsigns" id="more_callsigns" value="1" <?php checked($more_callsigns, true); ?>>
                            Zusätzliche Rufzeichen aktivieren
                        </label>
                    </td>
                </tr>
            </table>
            
            <div id="more-callsigns-section" style="<?php echo $more_callsigns ? '' : 'display: none;'; ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="api_key_2">Zweiter API-Schlüssel</label></th>
                        <td>
                            <input type="text" name="api_key_2" id="api_key_2" value="<?php echo esc_attr($api_key_2); ?>" class="regular-text">
                            <p class="description">Optionaler zweiter API-Schlüssel</p>
                        </td>
                    </tr>
                </table>
                
                <h2>Zusätzliche Rufzeichen (21-40)</h2>
                <table class="form-table" id="callsigns-table-2">
                    <?php for ($i = 1; $i <= 20; $i++): ?>
                    <tr>
                        <th scope="row"><label for="callsign_2_<?php echo $i; ?>">Rufzeichen <?php echo $i + 20; ?></label></th>
                        <td>
                            <input type="text" name="callsign_2_<?php echo $i; ?>" id="callsign_2_<?php echo $i; ?>" 
                                   value="<?php echo isset($callsigns_2[$i-1]) ? esc_attr($callsigns_2[$i-1]) : ''; ?>" class="regular-text">
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
                        <input type="text" name="map_center" id="map_center" value="<?php echo esc_attr($map_center); ?>" class="regular-text">
                        <p class="description">Locator (JO63HH), Koordinaten (52.5200,13.4050) oder Stadtname (Berlin)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="map_size">Kartengröße (optional)</label></th>
                    <td>
                        <input type="text" name="map_size" id="map_size" value="<?php echo esc_attr($map_size); ?>" class="regular-text">
                        <p class="description">Format: BreitexHöhe (z.B. 800x600)</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#more_callsigns').change(function() {
            if ($(this).is(':checked')) {
                $('#more-callsigns-section').show();
            } else {
                $('#more-callsigns-section').hide();
            }
        });
    });
    </script>
    <?php
}

function wp_aprs_admin_scripts() {
    wp_enqueue_style('wp-aprs-admin', WP_APRS_PLUGIN_URL . 'assets/css/admin.css', array(), WP_APRS_VERSION);
    wp_enqueue_script('wp-aprs-admin', WP_APRS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WP_APRS_VERSION, true);
}
add_action('admin_enqueue_scripts', 'wp_aprs_admin_scripts');

function wp_aprs_admin_init() {
}