(function($) {
    'use strict';
    
    // Warte bis DOM geladen ist
    $(document).ready(function() {
        // Finde alle Script-Tags mit Map-Daten
        $('script').each(function() {
            var scriptContent = $(this).html();
            if (scriptContent.indexOf('wpAprsMapData_') !== -1) {
                try {
                    // Extrahiere den Variablennamen
                    var varNameMatch = scriptContent.match(/var\s+(\w+)\s*=/);
                    if (varNameMatch && varNameMatch[1]) {
                        var mapData = window[varNameMatch[1]];
                        
                        if (mapData && mapData.id) {
                            initMap(mapData);
                        }
                    }
                } catch (e) {
                    console.error('Fehler beim Verarbeiten der Map-Daten:', e);
                }
            }
        });
    });
    
    function initMap(mapData) {
        var map = L.map(mapData.id).setView([mapData.center.lat, mapData.center.lng], 8);
        
        // Verfügbare Kartenlayer
        var availableLayers = {
            'osm_standard': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
                name: 'OpenStreetMap Standard'
            }),
            
            'osm_de': L.tileLayer('https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18,
                name: 'OpenStreetMap DE'
            }),
            
            'osm_hot': L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Tiles style by <a href="https://www.hotosm.org/" target="_blank">Humanitarian OpenStreetMap Team</a>',
                maxZoom: 19,
                name: 'Humanitarian OSM'
            }),
            
            'topo': L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="https://opentopomap.org">OpenTopoMap</a>',
                maxZoom: 17,
                name: 'Topographic Map'
            }),
            
            'cyclosm': L.tileLayer('https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="https://github.com/cyclosm/cyclosm-cartocss-style/releases">CyclOSM</a>',
                maxZoom: 20,
                name: 'Cycle OSM'
            }),
            
            'dark': L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="https://carto.com/attributions">CARTO</a>',
                maxZoom: 20,
                name: 'Dark Mode'
            })
        };
        
        // Standard-Layer aus Admin-Einstellung
        var defaultStyle = mapData.map_style || 'osm_standard';
        var currentLayer = availableLayers[defaultStyle] || availableLayers['osm_standard'];
        
        // Layer zur Karte hinzufügen
        currentLayer.addTo(map);
        
        // Layer-Control für Benutzer
        var layerControl = L.control.layers(availableLayers).addTo(map);
        
        // Marker für jede Position hinzufügen
        var bounds = L.latLngBounds();
        var hasValidPositions = false;
        
        if (mapData.positions && Array.isArray(mapData.positions)) {
            mapData.positions.forEach(function(position) {
                if (position.lat && position.lng) {
                    var marker = L.marker([position.lat, position.lng]).addTo(map);
                    
                    // Popup mit Informationen
                    var popupContent = '<div class="wp-aprs-popup">';
                    popupContent += '<strong>' + (position.name || position.callsign || 'Unbekannt') + '</strong>';
                    
                    if (position.lasttime) {
                        var date = new Date(position.lasttime * 1000);
                        popupContent += '<br>Letzte Position: ' + date.toLocaleString();
                    }
                    
                    if (position.altitude) {
                        popupContent += '<br>Höhe: ' + position.altitude + 'm';
                    }
                    
                    if (position.speed) {
                        popupContent += '<br>Geschwindigkeit: ' + position.speed + 'km/h';
                    }
                    
                    if (position.comment) {
                        popupContent += '<br>Kommentar: ' + position.comment;
                    }
                    
                    popupContent += '</div>';
                    
                    marker.bindPopup(popupContent);
                    bounds.extend([position.lat, position.lng]);
                    hasValidPositions = true;
                }
            });
        }
        
        // Karte an alle Marker anpassen, wenn Positionen vorhanden sind
        if (hasValidPositions) {
            map.fitBounds(bounds, {padding: [20, 20]});
        } else {
            // Fallback: Auf Mittelpunkt zoomen
            map.setView([mapData.center.lat, mapData.center.lng], 8);
        }
        
        // Skalierungs-Control hinzufügen
        L.control.scale({metric: true, imperial: false}).addTo(map);
    }
})(jQuery);