<?php
if (!function_exists('is_admin') || !is_admin()) {
    die('Invalid access.');
}

global $message;

if (!empty($message)) {
    echo _e($message, 'mailrelay');
}

wp_enqueue_script('mailrelay_main', '/wp-content/plugins/mailrelay/assets/js/main.js');

$mailrelay_host = get_option('mailrelay_host');
$mailrelay_api_key = get_option('mailrelay_api_key');
$mailrelay_auto_sync = get_option('mailrelay_auto_sync');
$mailrelay_auto_sync_groups = get_option('mailrelay_auto_sync_groups');

if (!empty($mailrelay_host) && !empty($mailrelay_api_key)) {
    $groups = mailrelay_get_groups();
}

?>

<div class="wrap">

<?php
screen_icon('options-general');
echo '<h2 id="mailrelay_connection_settings">';
echo _e('Connection Settings', 'mailrelay') . '</h2>';
?>

<form name="webservices_form" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">
    <input type="hidden" name="action" value="mailrelay_save_connection_settings" />
    <table class="form-table">
        <tr><th scope="row"><label for="mailrelay_host"><?php _e('Host:', 'mailrelay'); ?></label></th>
        <td><input type="text" name="mailrelay_host" value="<?php echo $mailrelay_host; ?>" size="40" /><p>
        <?php _e('Please enter the host that you have in your Mairelay welcome email. Please enter it without the initial http:// (for example demo.ip-zone.com)', 'mailrelay'); ?>
        </p></td></tr>
        <tr><th scope="row"><label for="mailrelay_api_key"><?php _e('API Key: ', 'mailrelay'); ?></label></th>
        <td><input type="text" name="mailrelay_api_key" value="<?php echo $mailrelay_api_key; ?>" size="50" autocomplete="off" /><p>
        <?php _e('Please enter your API key. You can generate your API key on your Mailrelay panel, Configuration -> API access -> Generate API key',  'mailrelay'); ?>
        </p>
        </td>
        </tr>

        <?php
        if (!empty($mailrelay_host) && !empty($mailrelay_api_key)) {
            ?>
            <tr>
                <th scope="row">
                    <label for="mailrelay_auto_sync"><?php _e('Sync new users automatically', 'mailrelay'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="mailrelay_auto_sync" id="mailrelay_auto_sync" <?php echo $mailrelay_auto_sync ? 'checked' : '' ?> />
                </td>
            </tr>

            <tr id="mailrelay_auto_sync_groups_wrapper">
                <th scope="row">
                    <label for="mailrelay_auto_sync_groups"><?php _e('Groups', 'mailrelay'); ?></label>
                </th>
                <td>
                    <select multiple="multiple" name="mailrelay_auto_sync_groups[]" id="mailrelay_auto_sync_groups" size="5" style="height:auto;">
                        <?php foreach($groups->data as $value) { ?>
                        <option value="<?php echo $value->id; ?>" <?php echo in_array($value->id, (array) $mailrelay_auto_sync_groups) ? 'selected' : '' ?>><?php echo esc_html($value->name); ?></option>
                        <?php } ?>
                    </select>
                    <p><?php _e('Groups that new users will be automatically synced to.', 'mailrelay') ?></p>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>
<?php
$submit_text = __('Save Changes', 'webserve_trdom');
submit_button($submit_text, 'primary', 'options');
?>
</form>
</div>