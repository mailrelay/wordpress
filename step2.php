<?php 
if(isset($_REQUEST['step']) && ($_REQUEST['step']=='step2') )
{
	$jsonResult = get_option('jsonResult');
	$data=$jsonResult->data;
	$usrname = get_option('usrname');
	$pwd = get_option('pwd');
	$userhost = get_option('userhost');
	
?>

<div class="wrap">
<?php    echo "<h2>" . __( 'Web services Steps', 'webserve_trdom' ) . "</h2>"; ?>

<form name="webservices_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<input type="hidden" name="chk_hidden" value="Y" />
	<input type="hidden" name="step" value="step3" />
	<input type="hidden" name="usrname" value="<?php echo $usrname; ?>">
	<input type="hidden" name="pwd" value="<?php echo $pwd; ?>" />
	<input type="hidden" name="userhost" value="<?php echo $userhost; ?>">
	<?php    echo "<h4>" . __( 'Please Select Group', 'webserve_trdom' ) . "</h4>"; ?>
	<select multiple="multiple" name="group[]" size="5" style="height:auto;">
		<?php foreach($data as $x=>$value){ ?>
		<option value="<?php echo $data[$x]->id; ?>"><?php echo $data[$x]->name; ?></option>
		<?php } ?>
	</select>
	<p class="submit">
	<input type="button" onclick="return chk_form();" name="Submit" value="<?php _e('Submit', 'webserve_trdom' ) ?>" />
	</p>
</form>

</div>
<script type="text/javascript">
function chk_form()
{
	var chk=check();
	if(chk!=false)
	{
		document.webservices_form.submit();
		document.webservices_form.action="";
	}
}
     function check() {
		if(document.webservices_form["group[]"].value == "")
		{alert('Please select at least one Group.');
		return false; }
		return true;
	}
  </script>

<?php } ?>