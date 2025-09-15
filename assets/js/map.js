(function($) {
    'use strict';
    
    $(document).ready(function() {
        $('script').each(function() {
            var scriptContent = $(this).html();
            if (scriptContent.indexOf('wpAprsMapData_') !== -1) {
                try {
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
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 18
        }).addTo(map);
        
        var bounds = L.latLngBounds();
        var hasValidPositions = false;
        
        mapData.positions.forEach(function(position) {
            if (position.lat && position.lng) {
                var marker = L.marker([position.lat, position.lng]).addTo(map);
                
                var popupContent = '<strong>' + (position.name || position.callsign) + '</strong>';
                if (position.lasttime) {
                    popupContent += '<br>Letzte Position: ' + new Date(position.lasttime * 1000).toLocaleString();
                }
                if (position.comment) {
                    popupContent += '<br>' + position.comment;
                }
                
                marker.bindPopup(popupContent);
                bounds.extend([position.lat, position.lng]);
                hasValidPositions = true;
            }
        });
        
        if (hasValidPositions) {
            map.fitBounds(bounds, {padding: [20, 20]});
        }
    }
})(jQuery);