jQuery(document).ready(function($) {
    $('#more_callsigns').change(function() {
        if ($(this).is(':checked')) {
            $('#more-callsigns-section').show();
        } else {
            $('#more-callsigns-section').hide();
        }
    });
});