jQuery(document).ready(function() {
    jQuery('#mailrelay_auto_sync').on('change', function() {
        if (jQuery(this).attr('checked')) {
            jQuery('#mailrelay_auto_sync_groups_wrapper').show()
        } else {
            jQuery('#mailrelay_auto_sync_groups_wrapper').hide()
        }
    })

    jQuery('#mailrelay_auto_sync').trigger('change')
})