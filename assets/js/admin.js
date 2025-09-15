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
    
    // Erweiterte Tooltips für Rufzeichen-Felder
    $('input[id^="callsign_"]').each(function() {
        var $field = $(this);
        
        // Tooltip für alle Felder
        $field.attr('title', 'Hier bitte das Rufzeichen oder die Objekt-Bezeichnung inkl. Suffix eingeben (Bsp: DO6DAD-7).');
        
        // Placeholder für leere Felder
        if (!$field.val()) {
            $field.attr('placeholder', 'z.B. DO6DAD-7');
        }
        
        // Live-Validierung
        $field.on('blur', function() {
            var value = $(this).val().trim();
            if (value && !isValidCallsign(value)) {
                alert('Bitte geben Sie ein gültiges Rufzeichen ein (z.B. DO6DAD-7).');
            }
        });
    });
    
    // Rufzeichen-Validierung
    function isValidCallsign(callsign) {
        // Einfache Validierung: Mindestens 3 Zeichen, darf Buchstaben, Zahlen, Bindestrich enthalten
        return callsign.length >= 3 && /^[A-Z0-9-]+$/i.test(callsign);
    }
    
    // Import/Export Bestätigungen
    $('form').on('submit', function(e) {
        var $form = $(this);
        
        // Import Bestätigung
        if ($form.find('input[name="import_settings"]').length) {
            if (!confirm('Sind Sie sicher, dass Sie die Einstellungen importieren möchten? Bestehende Einstellungen werden überschrieben.')) {
                e.preventDefault();
                return false;
            }
        }
        
        // Export Bestätigung
        if ($form.find('input[name="export_settings"]').length) {
            // Keine Bestätigung nötig für Export
            return true;
        }
    });
});