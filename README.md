# WP-APRS WordPress Plugin

WordPress Plugin für APRS Position Tracking mit Kartenansicht. Zeigt Positionen von APRS-Stationen auf einer interaktiven Karte an.

## 🚀 Features

- **APRS.fi API Integration** - Unterstützung für bis zu 40 Rufzeichen
- **Multiple Kartenlayer** - 6 verschiedene OpenStreetMap-basierte Stile
- **Responsive Design** - Optimiert für Desktop und Mobile
- **Import/Export** - Einstellungen zwischen Websites transferieren
- **DSGVO-konform** - Keine Tracking-Cookies, keine Nutzerdaten-Übermittlung
- **Caching-System** - Performance-Optimierung durch Zwischenspeicherung

## 📦 Installation

1. **Plugin herunterladen** von GitHub
2. **In WordPress hochladen**: Plugins → Installieren → Hochladen
3. **Plugin aktivieren**
4. **Einstellungen konfigurieren**: WP-APRS → Einstellungen

## ⚙️ Konfiguration

### API-Schlüssel
- APRS.fi API-Schlüssel in den Einstellungen eintragen
- Optional: Zweiter API-Schlüssel für bis zu 40 Rufzeichen

### Rufzeichen
- Standardmäßig vorbelegt: `DO6DAD-7` und `DO0RM-10`
- Pro API-Schlüssel maximal 20 Rufzeichen
- Unterstützt Rufzeichen mit Suffix (z.B. DO6DAD-7, DO0RM-10)

### Karten-Einstellungen
- **Mittelpunkt**: Locator (JO63HH), Koordinaten oder Stadtname
- **Größe**: Optional festlegen oder automatisch berechnen lassen
- **Stil**: 6 verschiedene Kartenlayer verfügbar

## 🗺️ Verfügbare Kartenlayer

1. **OpenStreetMap Standard** - Standard OSM Karte
2. **OpenStreetMap DE** - Deutsche OSM Variante
3. **Humanitarian OSM** - H.O.T. Style für humanitäre Einsätze
4. **Topographic Map** - Topografische Karte (Standard)
5. **Cycle OSM** - Optimiert für Radfahrer
6. **Dark Mode** - Dunkler Modus für nächtliches Betrachten

## 🔧 Shortcodes

### Karte anzeigen
Einfach an beliebiger Stelle in einer Wordpress-Seite folgende Shortcodes nutzen:
[WP-APRS-MAP]
Zeigt eine interaktive Karte mit allen konfigurierten Rufzeichen.

[WP-APRS-Callsigns]
Listet alle konfigurierten Rufzeichen alphabetisch sortiert auf.

## 📤 Import/Export
### Einstellungen exportieren
In den Einstellungen auf "Exportieren" klicken

Datei wp-aprs-export.cfg wird heruntergeladen

Enthält alle Plugin-Einstellungen im JSON-Format

### Einstellungen importieren
In den Einstellungen Datei auswählen

Auf "Importieren" klicken

Alle Einstellungen werden übernommen

Hinweis: Beim Import werden bestehende Einstellungen überschrieben!

## CSS Anpassungen
.wp-aprs-map {
    /* Eigene Stile für die Karte */
    border: 2px solid #0073aa;
    border-radius: 8px;
}

.leaflet-popup-content {
    /* Stile für die Popups */
    font-size: 14px;
}

## Filter und Actions
Das Plugin bietet verschiedene Filter und Actions für Entwickler:

// Eigene Kartenlayer hinzufügen
add_filter('wp_aprs_available_map_styles', function($styles) {
    $styles['custom_layer'] = 'Mein eigener Layer';
    return $styles;
});

// Eigene API-URL hinzufügen
add_filter('wp_aprs_tile_layer_url', function($url, $style) {
    if ($style === 'custom_layer') {
        return 'https://mein-tile-server/{z}/{x}/{y}.png';
    }
    return $url;
}, 10, 2);

## 📋 Systemvoraussetzungen
WordPress: 5.0 oder höher

PHP: 7.4 oder höher

Browser: Moderner Browser mit JavaScript-Unterstützung

## 🐛 Fehler melden
Bitte melden Sie Fehler oder Feature Requests auf GitHub.

## 🔄 Changelog
1.1.0 - [Datum]
✅ Import/Export Funktion hinzugefügt

✅ Standard-Rufzeichen vorbelegt (DO6DAD-7, DO0RM-10)

✅ Topographic Map als Standard-Kartenstil

✅ Tooltips für Rufzeichen-Eingabefelder

✅ Karte wird immer angezeigt (auch ohne Rufzeichen)

✅ Erweiterte Admin-Oberfläche

1.0.0 - [Datum]
🎉 Initiale Version

✅ APRS.fi API Integration

✅ OpenStreetMap Unterstützung

✅ Bis zu 40 Rufzeichen

✅ Shortcodes für Karte und Liste

✅ Admin-Einstellungsseite

## 📄 Lizenz
GPL v2 or later - Lizenz ansehen

## 👥 Beitragende
Steffan Jeschek (DO6DAD.de) - Entwicklung und Wartung

OpenStreetMap Contributors - Kartenlayer

Leaflet.js Team - Kartenbibliothek

## 🔗 Links
GitHub Repository

APRS.fi - APRS API Service

OpenStreetMap - Kartendaten

Hinweis: Dieses Plugin benötigt einen API-Schlüssel von aprs.fi. Die Nutzung unterliegt den Terms of Service von APRS.fi.

