<?php if( $this->step === 'step1' ) : ?>
<?php

    $usrname    = get_option('usrname');   
    $pwd        = get_option('pwd');
    $userhost   = get_option('userhost');

?>

<?php endif; ?>


<?php if( $this->message ) : ?>
    <div class="error"><p><?php echo _e($this->message, $this->slug); ?></p></div>
<?php endif; ?>

<div class="wrap">
<?php
	screen_icon('options-general'); 
	echo "<h2 id='mailrelay_settings'>";
	echo _e( 'Mailrelay Step 1 - Sync settings', 'mailrelay' ) . "</h2>"; 
?>

<form name="webservices_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<input type="hidden" name="chk_hidden" value="Y">
	<input type="hidden" name="page" value="web2">
	<input type="hidden" name="step" value="step2">

	<table class="form-table">
		<tr><th scope="row"><label for="usrname"><?php _e("Username: ", "mailrelay"); ?></label></th>
		<td><input type="text" name="usrname" value="<?php echo $usrname; ?>" size="20" /><p><?php 
			_e("Please enter the username the you have in your Mailrelay welcome email.",  "mailrelay"); ?></p>
		</td></tr>

		<tr><th scope="row"><label for="password"><?php _e("Password: ", "mailrelay" ); ?></label></th>
		<td><input type="password" name="pwd" value="<?php echo $pwd; ?>" size="20" /><p>
		<?php _e("Please enter the password that you have in your Mailrelay welcome email.", "mailrelay"); ?></p></td></tr>

		<tr><th scope="row"><label for="userhost"><?php _e("Host:", "mailrelay"); ?></label></th>
		<td><input type="text" name="userhost" value="<?php echo $userhost; ?>" size="20" /><p>
		<?php _e("Please enter the host that you have in your Mairelay welcome email. Please enter it without the initial http:// (for example demo.ip-zone.com)", "mailrelay"); ?>
		</p></td></tr>
	</table>
	<?php 
	$submit_text = __( 'Save Changes', 'webserve_trdom' );
	submit_button($submit_text, 'primary', 'options');
	?>
</form>
</div>
<?php // } ?>
