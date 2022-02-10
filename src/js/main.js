jQuery( document ).ready(function() {
    if ( jQuery( '#mailrelay_auto_sync' ).is( ':checked' ) == false ) {
        jQuery( '#mailrelay_groups' ).hide()
    }
    jQuery( '#mailrelay_auto_sync' ).on( 'change', function() {
        jQuery( '#mailrelay_groups' ).toggle();
        if (jQuery( '#mailrelay_auto_sync' ).is( ':checked' )) {
            jQuery( "#manual-form" ).submit(function() {
                if ( jQuery.trim( jQuery( '#mailrelay_auto_sync_groups' ).val()).length == 0) {
                    alert( 'Please select at least on group' );
                    return false;
                }
            });
        } else {
            return true;
        }
    });
});


