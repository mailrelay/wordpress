<?php
if (!function_exists('is_admin') || !is_admin()) {
    die('Invalid access.');
}

global $message;

if (!empty($message)) {
    echo _e($message, 'mailrelay');
}

$mailrelay_host = get_option('mailrelay_host');
$mailrelay_api_key = get_option('mailrelay_api_key');

?>

<div class="wrap">

<?php
screen_icon('options-general');
echo '<h2 id="mailrelay_settings">';
echo _e('Sync Users', 'mailrelay') . '</h2>';
if ($mailrelay_host == '' || $mailrelay_api_key == '') {
    wp_die('Please fill first the connection settings.');
}

// First thing, init
$url = 'https://'. $mailrelay_host .'/ccm/admin/api/version/2/&type=json';
$curl = curl_init($url);

// Call getGroups
$params = array(
    'function' => 'getGroups',
    'apiKey' => $mailrelay_api_key,
    'sortField' => 'name',
    'sortOrder' => 'ASC'
);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$headers = array(
    'X-Request-Origin: Wordpress|'. MAILRELAY_PLUGIN_VERSION .'|'. get_bloginfo('version')
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($curl);
$jsonResult = json_decode($result);

if (!$jsonResult || trim($jsonResult->status) != 1) {
    global $message;
    if (is_object($jsonResult)) {
        if ($jsonResult->error != '') {
            $message = '<div class="error"><p>' . $jsonResult->error . '</p></div>';
        } else {
            $message = '<div class="error"><p>Your account does not have an API key. Please, generate one in your Mailrelay\'s account: Settings -> API access -> Generate new API key.</p></div>';
        }
    } else {
        $message = '<div class="error"><p>Invalid host. Please Retry.</p></div>';
    }
} else {
    $data = $jsonResult->data;
}
?>

<form name="webservices_form" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">
    <input type="hidden" name="action" value="mailrelay_sync_users_group" />

    <table class="form-table">
        <tr><th scope="row">
        <label for="group"><?php _e('Please Select Group', 'mailrelay'); ?></label></th>
        <td>
        <select multiple="multiple" name="group[]" size="5" style="height:auto;">
            <?php foreach($data as $key => $value) { ?>
            <option value="<?php echo $data[$key]->id; ?>"><?php echo esc_html($data[$key]->name); ?></option>
            <?php } ?>
        </select>
        <p><?php _e('All your Wordpress users will be synced with the groups you are choosing now.', 'mailrelay'); ?><br />
        <?php _e('To create new groups in Mailrelay, you must login into the control panel and click into the Mail Relay > Subscribers groups', 'mailrelay'); ?><br />
        <?php _e('Once there you can add a new group for your Wordpress users, or edit an existing one', 'mailrelay'); ?></p>
        </td></tr>

        <?php
        if (mailrelay_woo_commmerce_installed()) {
            ?>
            <tr>
                <th scope="row"><label for="woo_commerce"><?php _e('WooCommerce options') ?></label></th>
                <td>
                    <select name="woo_commerce" id="woo_commmerce">
                        <option value=""><?php _e('Sync all users and WooCommmerce customers') ?></option>
                        <option value="only"><?php _e('Sync only WooCommerce customers', 'mailrelay') ?></option>
                        <option value="except"><?php _e('Sync all users except WooCommerce customers', 'mailrelay') ?></option>
                    </select>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>

    <p class="submit">
    <input type="button" onclick="return chk_form();" name="Select groups" value="<?php _e('Start Sync', 'mailrelay') ?>" class="button-primary" />
    </p>
</form>

</div>
<script type="text/javascript">
function chk_form() {
    var chk = check();
    if (chk != false) {
        document.webservices_form.submit();
        document.webservices_form.action = '';
    }
}
function check() {
    if(document.webservices_form['group[]'].value == '') {
        alert("<?php echo _e('Please select at least one Group.', 'mailrelay'); ?>");
        return false;
    }
    return true;
}
</script>