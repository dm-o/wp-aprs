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
                        <a href="${ajaxurl.replace('admin-ajax.php', 'admin.php')}?page=wp-aprs&export_confirmed=1&export_nonce=${wpAprsAdmin.nonce}" 
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
        
        // Focus auf den Abbruch-Button für bessere Accessibility
        setTimeout(function() {
            $('#export-cancel-btn').focus();
        }, 100);
    });
    
    // Export Abbruch Handler
    $(document).on('click', '#export-cancel-btn', function(e) {
        e.preventDefault();
        $('#wp-aprs-export-modal').fadeOut();
        // Redirect mit Abbruch-Status
        setTimeout(function() {
            window.location.href = ajaxurl.replace('admin-ajax.php', 'admin.php') + '?page=wp-aprs&export_status=cancelled';
        }, 300);
    });
    
    // Modal schließen bei Klick außerhalb
    $(document).on('click', '#wp-aprs-export-modal', function(e) {
        if (e.target.id === 'wp-aprs-export-modal') {
            $('#wp-aprs-export-modal').fadeOut();
            setTimeout(function() {
                window.location.href = ajaxurl.replace('admin-ajax.php', 'admin.php') + '?page=wp-aprs&export_status=cancelled';
            }, 300);
        }
    });
    
    // ESC-Taste zum Schließen
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#wp-aprs-export-modal').is(':visible')) {
            $('#wp-aprs-export-modal').fadeOut();
            setTimeout(function() {
                window.location.href = ajaxurl.replace('admin-ajax.php', 'admin.php') + '?page=wp-aprs&export_status=cancelled';
            }, 300);
        }
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
    
    // Hover-Effekte für Buttons
    $(document).on('mouseenter', '#export-confirm-btn', function() {
        $(this).css('background-color', '#3a9e43');
    }).on('mouseleave', '#export-confirm-btn', function() {
        $(this).css('background-color', '#46b450');
    });
    
    $(document).on('mouseenter', '#export-cancel-btn', function() {
        $(this).css('background-color', '#c32222');
    }).on('mouseleave', '#export-cancel-btn', function() {
        $(this).css('background-color', '#dc3232');
    });
    
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
            $('.form-table select.regular-text').css('width', '100%');
        } else {
            $('.form-table th').css('width', '200px');
            $('.form-table td').css('width', '');
            $('.form-table input.regular-text').css('width', '');
            $('.form-table select.regular-text').css('width', '');
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
    
    // Section Toggle für bessere UX
    $('h2').each(function() {
        var $header = $(this);
        if ($header.text().includes('Rufzeichen') || $header.text().includes('Karteneinstellungen')) {
            $header.css('cursor', 'pointer')
                  .attr('title', 'Klicken zum Ein-/Ausblenden')
                  .on('click', function() {
                      $(this).nextUntil('h2').slideToggle();
                  });
        }
    });
    
    // Bulk Actions für Rufzeichen
    $('#bulk-add-callsigns').on('click', function(e) {
        e.preventDefault();
        var callsigns = prompt('Geben Sie mehrere Rufzeichen ein (durch Komma getrennt):');
        if (callsigns) {
            var callsignArray = callsigns.split(',').map(function(item) {
                return item.trim();
            }).filter(function(item) {
                return item.length > 0;
            });
            
            if (callsignArray.length > 0) {
                $('input[id^="callsign_"]').each(function(index) {
                    if (index < callsignArray.length) {
                        $(this).val(callsignArray[index]);
                    }
                });
                alert(callsignArray.length + ' Rufzeichen wurden eingetragen.');
            }
        }
    });
    
    // Clear All Button
    $('#clear-all-callsigns').on('click', function(e) {
        e.preventDefault();
        if (confirm('Sind Sie sicher, dass Sie alle Rufzeichen löschen möchten?')) {
            $('input[id^="callsign_"]').val('');
            alert('Alle Rufzeichen wurden gelöscht.');
        }
    });
    
    // Duplicate Checker
    $('input[id^="callsign_"]').on('blur', function() {
        var currentValue = $(this).val().trim();
        if (currentValue) {
            var duplicates = [];
            $('input[id^="callsign_"]').not(this).each(function() {
                if ($(this).val().trim() === currentValue) {
                    duplicates.push($(this).attr('id'));
                }
            });
            
            if (duplicates.length > 0) {
                alert('Warnung: Das Rufzeichen "' + currentValue + '" wurde bereits in anderen Feldern eingetragen.');
            }
        }
    });
    
    // Auto-Tab zwischen Feldern
    $('input[id^="callsign_"]').on('input', function(e) {
        var maxLength = 10; // Maximale Länge für Rufzeichen
        var currentValue = $(this).val();
        
        if (currentValue.length >= maxLength) {
            var nextField = $(this).closest('tr').next().find('input[id^="callsign_"]');
            if (nextField.length) {
                nextField.focus();
            }
        }
    });
    
    // Real-time Character Counter
    $('input[id^="callsign_"]').on('input', function() {
        var $counter = $(this).next('.char-counter');
        if (!$counter.length) {
            $(this).after('<span class="char-counter" style="font-size: 11px; color: #666; display: block;"></span>');
            $counter = $(this).next('.char-counter');
        }
        
        var length = $(this).val().length;
        $counter.text(length + ' Zeichen');
        
        if (length > 15) {
            $counter.css('color', '#dc3232');
        } else if (length > 10) {
            $counter.css('color', '#ffb900');
        } else {
            $counter.css('color', '#666');
        }
    });
    
    // Preset Examples
    $('#load-example-callsigns').on('click', function(e) {
        e.preventDefault();
        if (confirm('Beispiel-Rufzeichen laden? Dies überschreibt vorhandene Einträge.')) {
            var examples = [
                'DO6DAD-7', 'DO0RM-10', 'DL1ABC', 'DB0ABC-1', 
                'DO1XYZ', 'DL2DEF-5', 'DM3GHI', 'DN4JKL-2',
                'DO5MNO', 'DP6PQR-9', 'DQ7STU', 'DR8VWX-3'
            ];
            
            $('input[id^="callsign_1_"]').each(function(index) {
                if (index < examples.length) {
                    $(this).val(examples[index]);
                }
            });
            
            alert('12 Beispiel-Rufzeichen wurden geladen.');
        }
    });
    
    // Keyboard Navigation
    $('input, select').on('keydown', function(e) {
        // Tab durch Formular mit Enter
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            var $next = $(this).closest('tr').next().find('input, select').first();
            if ($next.length) {
                $next.focus();
            } else {
                $(this).closest('table').next().find('input, select').first().focus();
            }
        }
    });
    
    // Auto-save Indicator
    var isSaving = false;
    $('form').on('submit', function() {
        if (!isSaving) {
            isSaving = true;
            var $submitBtn = $('input[name="submit"]');
            var originalText = $submitBtn.val();
            
            $submitBtn.val('Speichern...').prop('disabled', true);
            
            setTimeout(function() {
                $submitBtn.val(originalText).prop('disabled', false);
                isSaving = false;
            }, 1500);
        }
    });
    
    // Help Tooltips
    $('.dashicons-editor-help').on('click', function() {
        var helpText = $(this).data('help');
        if (helpText) {
            alert(helpText);
        }
    });
    
    // Color-coded API Key validation
    function validateApiKey($input) {
        var value = $input.val().trim();
        if (value.length === 0) {
            $input.css('border-color', '');
            $input.next('.api-key-status').remove();
        } else if (value.length === 32 && /^[a-zA-Z0-9]+$/.test(value)) {
            $input.css('border-color', '#46b450');
            updateApiKeyStatus($input, '✅ Gültiger API-Schlüssel', 'success');
        } else {
            $input.css('border-color', '#dc3232');
            updateApiKeyStatus($input, '❌ Ungültiges Format', 'error');
        }
    }
    
    function updateApiKeyStatus($input, message, type) {
        var $status = $input.next('.api-key-status');
        if (!$status.length) {
            $input.after('<span class="api-key-status" style="display: block; font-size: 12px; margin-top: 5px;"></span>');
            $status = $input.next('.api-key-status');
        }
        
        $status.text(message).css('color', type === 'success' ? '#46b450' : '#dc3232');
    }
    
    // Initial API Key validation
    $('input[name="api_key_1"], input[name="api_key_2"]').each(function() {
        validateApiKey($(this));
    });
    
    // Live API Key validation
    $('input[name="api_key_1"], input[name="api_key_2"]').on('input', function() {
        validateApiKey($(this));
    });
});