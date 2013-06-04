<?php
if (isset($_REQUEST['step']) && ($_REQUEST['step'] == 'step2'))
{
    $jsonResult = get_option('jsonResult');
    $data = $jsonResult->data;
    $userhost = get_option('userhost');
    $mailrelay_api_key = get_option('mailrelay_api_key');
?>

<div class="wrap">
<?php
        screen_icon('options-general');
        echo "<h2 id='mailrelay_settings'>";
        echo _e('Mairelay Step 2 - Choose groups', 'mailrelay') . "</h2>";
?>

<form name="webservices_form" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">
    <input type="hidden" name="chk_hidden" value="Y" />
    <input type="hidden" name="step" value="step3" />
    <input type="hidden" name="userhost" value="<?php echo $userhost; ?>">
    <input type="hidden" name="mailrelay_api_key" value="<?php echo $mailrelay_api_key; ?>">

    <table class="form-table">
        <tr><th scope="row">
        <label for="group"><?php _e('Please Select Group', 'mailrelay'); ?></label></th>
        <td>
        <select multiple="multiple" name="group[]" size="5" style="height:auto;">
            <?php foreach($data as $x=>$value){ ?>
            <option value="<?php echo $data[$x]->id; ?>"><?php echo $data[$x]->name; ?></option>
            <?php } ?>
        </select>
        <p><?php _e("All your Wordpress users will be synced with the groups you are choosing now.", "mailrelay"); ?><br />
        <?php _e("To create new groups in Mailrelay, you must login into the control panel and click into the Mail Relay > Subscribers groups", "mailrelay"); ?><br />
        <?php _e("Once there you can add a new group for your Wordpress users, or edit an existing one", "mailrelay"); ?></p>
        </td></tr>
    </table>

    <p class="submit">
    <input type="button" onclick="return chk_form();" name="Select groups" value="<?php _e('Select groups', 'mailrelay') ?>" class="button-primary" />
    </p>
</form>

</div>
<script type="text/javascript">
function chk_form()
{
    var chk=check();
    if (chk != false)
    {
        document.webservices_form.submit();
        document.webservices_form.action = "";
    }
}
function check() {
    if(document.webservices_form["group[]"].value == "")
    {
        alert("<?php echo _e('Please select at least one Group.', 'mailrelay'); ?>");
        return false;
    }
    return true;
}
 </script>

<?php } ?>