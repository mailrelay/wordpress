<?php 
if(isset($_REQUEST['step']) && ($_REQUEST['step']=='step3') )
{

	$added = get_option('added');
	$updated = get_option('updated');
	$fail = get_option('fail');
	
?>

<div class="wrap">
<?php    echo "<h2>" . __( 'Synced Users', 'webserve_trdom' ) . "</h2>"; ?>

New users synced: <?php echo $added; ?><br />
Updated users:  <?php echo $updated; ?><br />
Failed users:  <?php echo $fail; ?><br />

<?php } ?>