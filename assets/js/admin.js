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
    
    // Export Bestätigung
    $('#start-export').click(function(e) {
        e.preventDefault();
        $('#export-warning').slideDown();
    });
    
    $('#export-cancel').click(function(e) {
        e.preventDefault();
        $('#export-warning').slideUp();
        // Redirect mit Abbruch-Status
        window.location.href = ajaxurl.replace('admin-ajax.php', 'admin.php') + '?page=wp-aprs&export_status=cancelled';
    });
    
    // Import Bestätigung
    $('form').on('submit', function(e) {
        var $form = $(this);
        
        // Import Bestätigung
        if ($form.find('input[name="import_settings"]').length) {
            if (!confirm('Sind Sie sicher, dass Sie die Einstellungen importieren möchten? Bestehende Einstellungen werden überschrieben.')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // CSS für Export-Buttons
    $('#export-confirm').css({
        'background-color': '#46b450',
        'border-color': '#46b450',
        'color': 'white',
        'font-weight': 'bold'
    });
    
    $('#export-cancel').css({
        'background-color': '#dc3232',
        'border-color': '#dc3232',
        'color': 'white',
        'font-weight': 'bold'
    });
    
    // Hover-Effekte für Buttons
    $('#export-confirm').hover(
        function() {
            $(this).css('background-color', '#3a9e43');
        },
        function() {
            $(this).css('background-color', '#46b450');
        }
    );
    
    $('#export-cancel').hover(
        function() {
            $(this).css('background-color', '#c32222');
        },
        function() {
            $(this).css('background-color', '#dc3232');
        }
    );
    
    // Auto-Focus auf erstes leeres Rufzeichen-Feld
    var $emptyCallsign = $('input[id^="callsign_"]').filter(function() {
        return $(this).val() === '';
    }).first();
    
    if ($emptyCallsign.length) {
        $emptyCallsign.focus();
    }
    
    // Quick-Save mit Strg+S
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            $('input[name="submit"]').click();
        }
    });
    
    // Responsive Anpassungen
    function handleResponsive() {
        if ($(window).width() < 782) {
            $('.form-table th').css('width', '100%');
            $('.form-table td').css('width', '100%');
            $('.form-table input.regular-text').css('width', '100%');
        } else {
            $('.form-table th').css('width', '200px');
            $('.form-table td').css('width', '');
            $('.form-table input.regular-text').css('width', '');
        }
    }
    
    // Initial und bei Resize
    handleResponsive();
    $(window).resize(handleResponsive);
    
    // AJAX Status-Checker für API-Keys
    $('input[name="api_key_1"], input[name="api_key_2"]').on('blur', function() {
        var $input = $(this);
        var apiKey = $input.val().trim();
        
        if (apiKey.length > 0) {
            $input.css('border-color', '#ffb900');
            
            // Simulierter API-Check (kann später erweitert werden)
            setTimeout(function() {
                if (apiKey.length === 32) {
                    $input.css('border-color', '#46b450');
                } else {
                    $input.css('border-color', '#dc3232');
                }
            }, 500);
        } else {
            $input.css('border-color', '');
        }
    });
});