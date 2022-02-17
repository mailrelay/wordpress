jQuery( document ).ready(function() {
    if ( false == jQuery( '#mailrelay_auto_sync' ).is( ':checked' ) ) {
        jQuery( '#mailrelay_groups' ).hide()
    }

    jQuery( '#mailrelay_auto_sync' ).on('change', function() {
        jQuery( '#mailrelay_groups' ).toggle();
    });

    jQuery( "#manual-form" ).submit(function() {
        if (jQuery( '#mailrelay_auto_sync' ).is( ':checked' )) {
            
            if ( 0 == jQuery.trim( jQuery( '#mailrelay_auto_sync_groups' ).val()).length ) {
                alert( 'Please select at least on group' );
                return false;
            }
        }
        else {
            return true;
        }
    });

    jQuery( "#manual-sync" ).submit(function() {
            
        if ( 0 == jQuery.trim( jQuery( '#mailrelay_group' ).val()).length ) {
            alert( 'Please select at least on group' );
            return false;
        }

        else {
            return true;
        }
    });

});
