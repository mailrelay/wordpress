<?php
if(!isset($_REQUEST['step']) || ($_REQUEST['step']=='step1') )
{ 
$usrname = get_option('usrname');
$pwd = get_option('pwd');
$userhost = get_option('userhost');
?>

<div class="wrap">
	<div class="updated">
		<p><?php _e("Please enter your Mailrelay connection data."); ?></p>
                <p><?php _e("Note that host must be filled without initial http://"); ?></p>
	</div>

<?php    echo "<h2>" . __( 'Web services Steps', 'webserve_trdom' ) . "</h2>"; ?>

<form name="webservices_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<input type="hidden" name="chk_hidden" value="Y">
	<input type="hidden" name="page" value="web2">
	<input type="hidden" name="step" value="step2">
	<?php    echo "<h4>" . __( 'Please Fill This Form', 'webserve_trdom' ) . "</h4>"; ?>
	<table width="400" border="0">
	  <tr>
		<td><?php _e("Username: " ); ?></td>
		<td><input type="text" name="usrname" value="<?php echo $usrname; ?>" size="20" /></td>
	  </tr>
	  <tr>
		<td><?php _e("Password: " ); ?></td>
		<td><input type="password" name="pwd" value="<?php echo $pwd; ?>" size="20" /></td>
	  </tr>
	  <tr>
		<td><?php _e("Host: " ); ?></td>
		<td><input type="text" name="userhost" value="<?php echo $userhost; ?>" size="20" /></td>
	  </tr>
	</table>

	<p class="submit">
	<input type="submit" name="Submit" value="<?php _e('Submit', 'webserve_trdom' ) ?>" />
	</p>
</form>
</div>
<?php } ?>
