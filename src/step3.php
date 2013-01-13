<div class="wrap">
<?php
        screen_icon('options-general'); 
        echo "<h2 id='mailrelay_settings'>";
        echo _e( 'Mailrelay Step 3 - Sync finished', 'mailrelay' ) . "</h2>"; 
?>
<div class="wrap">
<h3><?php _e("The Mailrelay sync has finished successfully. Next you can check the results of the sync:", "mailrelay"); ?></h3>
<ul>
	<li><?php _e("New users synced", "mailrelay"); ?>:&nbsp;<?php echo $added; ?></li>
	<li><?php _e("Updated users", "mailrelay"); ?>:&nbsp;<?php echo $updated; ?></li>
	<li><?php _e("Failed users", "mailrelay"); ?>:&nbsp;<?php echo $fail; ?></li>
</ul>
<p><?php printf(__("To retry the Mailrelay sync process please click <a href='%s'>here</a>.", 'mailrelay' ), esc_url( admin_url( '?page=mailrelay' ) ) ) ; ?></p>
</div>
