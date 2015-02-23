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
screen_icon('options-posts');
echo '<h2 id="mailrelay_posts_settings">';
echo _e('Posts Settings', 'mailrelay') . '</h2>';
if ($mailrelay_host == '' || $mailrelay_api_key == '') {
    wp_die('Please fill first the connection settings.');
}
$mailrelay_newsletter_post = get_option('mailrelay_newsletter_post');
$mailrelay_newsletter_from = get_option('mailrelay_newsletter_from');
$mailrelay_newsletter_reply_to = get_option('mailrelay_newsletter_reply_to');
$mailrelay_newsletter_report_to = get_option('mailrelay_newsletter_report_to');
$mailrelay_newsletter_unsubscribe = get_option('mailrelay_newsletter_unsubscribe');

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
    <input type="hidden" name="action" value="mailrelay_save_posts_settings" />
    <table class="form-table">
        <tr><th scope="row"><label for="mailrelay_newsletter_post"><?php _e('Posts like newsletters:', 'mailrelay'); ?></label></th>
        <td><input type="checkbox" name="mailrelay_newsletter_post" id="mailrelay_newsletter_post" onclick="" value="1" <?php echo $mailrelay_newsletter_post == '1' ? ' checked="checked"' : ''; ?>" /><p>
        <?php _e('Enable posts like newsletters', 'mailrelay'); ?>
        </p></td></tr>
        <tr id="mailrelay_newsletter_from_tr"><th scope="row"><label for="mailrelay_newsletter_from"><?php _e('Newsletter From:', 'mailrelay'); ?></label></th>
        <td>
        <select name="mailrelay_newsletter_from" id="mailrelay_newsletter_from">
        <?php
        foreach ($mailboxes as $id => $mail) {
        ?>
        <option value="<?php echo $id; ?>"<?php echo $id == $mailrelay_newsletter_from ? ' selected="selected"' : ''; ?>><?php echo $mail; ?></option>
        <?php
        }
        ?>
        </select><p>
        <?php _e('Use this field like Newsletter From', 'mailrelay'); ?>
        </p></td></tr>
        <tr id="mailrelay_newsletter_reply_to_tr"><th scope="row"><label for="mailrelay_newsletter_reply_to"><?php _e('Newsletter Reply To:', 'mailrelay'); ?></label></th>
        <td>
        <select name="mailrelay_newsletter_reply_to" id="mailrelay_newsletter_reply_to">
        <?php
        foreach ($mailboxes as $id => $mail) {
        ?>
        <option value="<?php echo $id; ?>"<?php echo $id == $mailrelay_newsletter_reply_to ? ' selected="selected"' : ''; ?>><?php echo $mail; ?></option>
        <?php
        }
        ?>
        </select><p>
        <?php _e('Use this field like Newsletter Reply To', 'mailrelay'); ?>
        </p></td></tr>
        <tr id="mailrelay_newsletter_report_to_tr"><th scope="row"><label for="mailrelay_newsletter_report_to"><?php _e('Newsletter Report To:', 'mailrelay'); ?></label></th>
        <td>
        <select name="mailrelay_newsletter_report_to" id="mailrelay_newsletter_report_to">
        <?php
        foreach ($mailboxes as $id => $mail) {
        ?>
        <option value="<?php echo $id; ?>"<?php echo $id == $mailrelay_newsletter_report_to ? ' selected="selected"' : ''; ?>><?php echo $mail; ?></option>
        <?php
        }
        ?>
        </select><p>
        <?php _e('Use this field like Newsletter Report To', 'mailrelay'); ?>
        </p></td></tr>
        <tr><th scope="row"><label for="mailrelay_newsletter_unsubscribe"><?php _e('Allow users to unsubscribe:', 'mailrelay'); ?></label></th>
        <td><input type="checkbox" name="mailrelay_newsletter_unsubscribe" id="mailrelay_newsletter_unsubscribe" onclick="" value="1" <?php echo $mailrelay_newsletter_unsubscribe == '1' ? ' checked="checked"' : ''; ?>" /><p>
        <?php _e('Enable users to unsubscribe from the newsletters', 'mailrelay'); ?>
        </p></td></tr>
    </table>
<?php
$submit_text = __('Save Changes', 'webserve_trdom');
submit_button($submit_text, 'primary', 'options');
?>
</form>
</div>