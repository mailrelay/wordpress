<?php
if (!function_exists('is_admin') || !is_admin()) {
    die('Invalid access.');
}

global $message;

$mailrelay_host = get_option('mailrelay_host');
$mailrelay_api_key = get_option('mailrelay_api_key');

?>

<div class="wrap">

<?php
screen_icon('options-feeds');
echo '<h2 id="mailrelay_feeds_settings">';
echo _e('Feeds Settings', 'mailrelay') . '</h2>';
if ($mailrelay_host == '' || $mailrelay_api_key == '') {
    wp_die('Please fill first the connection settings.');
}
$mailrelay_newsletter_feed = get_option('mailrelay_newsletter_feed');
$mailrelay_feeds_group = get_option('mailrelay_feeds_group');
$mailrelay_feed_from = get_option('mailrelay_feed_from');
$mailrelay_feed_reply_to = get_option('mailrelay_feed_reply_to');
$mailrelay_feed_report_to = get_option('mailrelay_feed_report_to');
$mailrelay_feed_unsubscribe = get_option('mailrelay_feed_unsubscribe');

// First thing, init
$url = 'https://'. $mailrelay_host .'/ccm/admin/api/version/2/&type=json';
$curl = curl_init($url);

// Call getMailboxes
$params = array(
    'function' => 'getMailboxes',
    'apiKey' => $mailrelay_api_key,
    'sortField' => 'id',
    'sortOrder' => 'ASC'
);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_SSLVERSION, 3);

$headers = array(
    'X-Request-Origin: Wordpress|1.4.0|'.$wp_version
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
    $mailboxes = array();
    if ($jsonResult->data) {
        foreach ($jsonResult->data as $mailbox) {
            $mailboxes[$mailbox->id] = $mailbox->name . ' &lt;' . $mailbox->email . '&gt;';
        }
    }
}

if (!empty($message)) {
    echo _e($message, 'mailrelay');
}

?>
<form name="webservices_form" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">
    <input type="hidden" name="action" value="mailrelay_save_feeds_settings" />
    <table class="form-table">
        <tr><th scope="row"><label for="mailrelay_newsletter_feed"><?php _e('Feeds like newsletters:', 'mailrelay'); ?></label></th>
        <td><input type="checkbox" name="mailrelay_newsletter_feed" id="mailrelay_newsletter_feed" onclick="" value="1" <?php echo $mailrelay_newsletter_feed == '1' ? ' checked="checked"' : ''; ?>" /><p>
        <?php _e('To send feeds like newsletters', 'mailrelay'); ?>
        </p></td></tr>
        <tr><th><label for="mailrelay_feeds_group"><?php echo _e('Please select a group to send the feeds', 'mailrelay'); ?></label></th>
        <td><select name="mailrelay_feeds_group" id="mailrelay_feeds_group">
        <?php
        $result = mailrelay_get_groups();
        if ($result && $result->status == 1) {
            if (is_object($result)) {
                $data = $result->data;
                if (is_array($data)) {
                    foreach($data as $key => $value) {
                    ?>
                    <option value="<?php echo $data[$key]->id; ?>"<?php echo $mailrelay_feeds_group == $data[$key]->id ? ' selected="selected"' : ''; ?>><?php echo $data[$key]->name; ?></option>
                    <?php
                    }
                }
            }
        }
        ?>
        </select><p>
        <?php _e('To send feeds like newsletters', 'mailrelay'); ?>
        </p></td></tr>
        <tr id="mailrelay_feed_from_tr"><th scope="row"><label for="mailrelay_feed_from"><?php _e('Newsletter From:', 'mailrelay'); ?></label></th>
        <td>
        <select name="mailrelay_feed_from" id="mailrelay_feed_from">
        <?php
        foreach ($mailboxes as $id => $mail) {
        ?>
        <option value="<?php echo $id; ?>"<?php echo $id == $mailrelay_feed_from ? ' selected="selected"' : ''; ?>><?php echo $mail; ?></option>
        <?php
        }
        ?>
        </select><p>
        <?php _e('Use this field like Newsletter From', 'mailrelay'); ?>
        </p></td></tr>
        <tr id="mailrelay_feed_reply_to_tr"><th scope="row"><label for="mailrelay_feed_reply_to"><?php _e('Newsletter Reply To:', 'mailrelay'); ?></label></th>
        <td>
        <select name="mailrelay_feed_reply_to" id="mailrelay_feed_reply_to">
        <?php
        foreach ($mailboxes as $id => $mail) {
        ?>
        <option value="<?php echo $id; ?>"<?php echo $id == $mailrelay_feed_reply_to ? ' selected="selected"' : ''; ?>><?php echo $mail; ?></option>
        <?php
        }
        ?>
        </select><p>
        <?php _e('Use this field like Newsletter Reply To', 'mailrelay'); ?>
        </p></td></tr>
        <tr id="mailrelay_feed_report_to_tr"><th scope="row"><label for="mailrelay_feed_report_to"><?php _e('Newsletter Report To:', 'mailrelay'); ?></label></th>
        <td>
        <select name="mailrelay_feed_report_to" id="mailrelay_feed_report_to">
        <?php
        foreach ($mailboxes as $id => $mail) {
        ?>
        <option value="<?php echo $id; ?>"<?php echo $id == $mailrelay_feed_report_to ? ' selected="selected"' : ''; ?>><?php echo $mail; ?></option>
        <?php
        }
        ?>
        </select><p>
        <?php _e('Use this field like Newsletter Report To', 'mailrelay'); ?>
        </p></td></tr>
        <tr><th scope="row"><label for="mailrelay_feed_unsubscribe"><?php _e('Allow users to unsubscribe:', 'mailrelay'); ?></label></th>
        <td><input type="checkbox" name="mailrelay_feed_unsubscribe" id="mailrelay_feed_unsubscribe" onclick="" value="1" <?php echo $mailrelay_feed_unsubscribe == '1' ? ' checked="checked"' : ''; ?>" /><p>
        <?php _e('Enable users to unsubscribe from the feeds', 'mailrelay'); ?>
        </p></td></tr>
    </table>
<?php
$submit_text = __('Save Changes', 'webserve_trdom');
submit_button($submit_text, 'primary', 'options');
?>
</form>
</div>