<?php
// Sicherheitsabfrage
if (!defined('ABSPATH')) {
    exit;
}

// Frontend initialisieren
function wp_aprs_frontend_init() {
    // Frontend-Skripte und Styles
    add_action('wp_enqueue_scripts', 'wp_aprs_frontend_scripts');
}

// Frontend-Skripte und Styles
function wp_aprs_frontend_scripts() {
    // Nur laden wenn Shortcode auf der Seite ist
    global $post;
    
    if (is_a($post, 'WP_Post') && 
        (has_shortcode($post->post_content, 'WP-APRS-MAP') || 
         has_shortcode($post->post_content, 'WP-APRS-Callsigns'))) {
        
        wp_enqueue_style('wp-aprs-frontend', WP_APRS_PLUGIN_URL . 'assets/css/frontend.css', array(), WP_APRS_VERSION);
    }
}