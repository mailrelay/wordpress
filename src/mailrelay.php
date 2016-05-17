<?php
/*
Plugin Name: Mailrelay
Plugin URI: http://mailrelay.com
Description: Easily sync your Wordpress users with Mailrelay.
Version: 1.8.0
Author: Mailrelay.com
*/

if (!defined('ABSPATH')) {
    die('Invalid access.');
}

if (!defined('MAILRELAY_PLUGIN_VERSION')) {
    define('MAILRELAY_PLUGIN_VERSION', '1.8.0');
}

function mailrelay_sync_user($user, $groups) {
    $mailrelay_host = get_option('mailrelay_host');
    $mailrelay_api_key = get_option('mailrelay_api_key');

    $url = 'https://' . $mailrelay_host . '/ccm/admin/api/version/2/&type=json';
    $curl = curl_init($url);

    // Call getSubscribers
    $params = array(
        'function' => 'getSubscribers',
        'apiKey' => $mailrelay_api_key,
        'email' => $user->user_email,
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

    if (count($jsonResult->data) > 0) {
        $params = array(
            'function' => 'updateSubscriber',
            'apiKey' => $mailrelay_api_key,
            'id' => $jsonResult->data[0]->id,
            'email' => $user->user_email,
            'name' => $user->display_name,
            'groups' => $groups
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));

        $headers = array(
            'X-Request-Origin: Wordpress|'. MAILRELAY_PLUGIN_VERSION .'|'. get_bloginfo('version')
       );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($curl);
        $jsonResult = json_decode($result);

        if ($jsonResult->status == 1) {
            return array(
                'status' => 'updated'
            );
        } else {
            return array(
                'status' => 'failed'
            );
        }
    } else {
        $params = array(
            'function' => 'addSubscriber',
            'apiKey' => $mailrelay_api_key,
            'email' => $user->user_email,
            'name' => $user->display_name,
            'groups' => $groups
        );

        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));

        $headers = array(
            'X-Request-Origin: Wordpress|'. MAILRELAY_PLUGIN_VERSION .'|'. get_bloginfo('version')
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($curl);
        $jsonResult = json_decode($result);

        if ($jsonResult->status == 1) {
            return array(
                'status' => 'added'
            );
        } else {
            return array(
                'status' => 'failed'
            );
        }
    }
}

function mailrelay_new_user_registration($user_id) {
    $user = new WP_User($user_id);

    $groups = get_option('mailrelay_auto_sync_groups');

    if (!empty($groups)) {
        try {
            mailrelay_sync_user($user, $groups);
        } catch(Exception $e) {
            // Ignore if something goes wrong to avoid showing errors to user
        }
    }
}

if (get_option('mailrelay_auto_sync')) {
    add_action('user_register', 'mailrelay_new_user_registration');
}

if (function_exists('is_admin') && is_admin()) {
    function mailrelay_init() {
        $result = load_plugin_textdomain('mailrelay', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    function mailrelay_menu() {
        add_menu_page('Mailrelay', 'Mailrelay', 'manage_options', 'mailrelay', null, plugins_url('mailrelay/mailrelay.png'));
        add_submenu_page('mailrelay', __('Connection Settings', 'mailrelay'), __('Connection Settings', 'mailrelay'), 'manage_options', 'mailrelay_connection_settings', 'mailrelay_connection_settings');
        add_submenu_page('mailrelay', __('Posts Settings', 'mailrelay'), __('Posts Settings', 'mailrelay'), 'manage_options', 'mailrelay_posts_settings', 'mailrelay_posts_settings');
        add_submenu_page('mailrelay', __('Feeds Settings', 'mailrelay'), __('Feeds Settings', 'mailrelay'), 'manage_options', 'mailrelay_feeds_settings', 'mailrelay_feeds_settings');
        add_submenu_page('mailrelay', __('Sync Users', 'mailrelay'), __('Sync Users', 'mailrelay'), 'manage_options', 'mailrelay_sync_users', 'mailrelay_sync_users');
        remove_submenu_page('mailrelay', 'mailrelay');
    }

    function mailrelay_connection_settings() {
        require 'connection_settings.php';
    }

    function mailrelay_posts_settings() {
        require 'posts_settings.php';
    }

    function mailrelay_feeds_settings() {
        require 'feeds_settings.php';
    }

    function mailrelay_sync_users() {
        global $message;

        if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'mailrelay_sync_users_group')) {
            if ($_REQUEST['woo_commerce'] == 'only') {
                $users = get_users('role=customer');
            } elseif ($_REQUEST['woo_commerce'] == 'except') {
                $roles = get_editable_roles();
                unset($roles['customer']);

                $users = array();
                foreach($roles as $role => $values) {
                    $users = array_merge($users, get_users('role='. $role));
                }
            } else {
                $users = get_users();
            }
            
            $groups = $_REQUEST['group'];

            $added = 0;
            $updated = 0;
            $failed = 0;

            foreach ($users as $user) {
                $return = mailrelay_sync_user($user, $groups);

                if ($return['status'] == 'added') {
                    $added++;
                } elseif ($return['status'] == 'updated') {
                    $updated++;
                } elseif ($return['status'] == 'failed') {
                    $failed++;
                } else {
                    throw new Exception('Invalid return status.');
                }
            }

            $message  = '<div class="updated"><p>The Mailrelay sync has finished successfully. Next you can check the results of the sync:<ul>';
            $message .= '<li>New users synced:&nbsp;' . $added . '</li>';
            $message .= '<li>Updated users:&nbsp;' . $updated . '</li>';
            $message .= '<li>Failed users:&nbsp;' . $failed . '</li>';
            $message .= '</ul></p></div>';
        }

        require 'sync_users.php';
    }

    function mailrelay_woo_commmerce_installed() {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }

    function mailrelay_get_groups() {
        // Initialize variables
        $mailrelay_host = get_option('mailrelay_host');
        $mailrelay_api_key = get_option('mailrelay_api_key');

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
        return json_decode($result);
    }

    function mailrelay_create_post_meta_box() {
        add_meta_box('mailrelay-meta-box', 'Mailrelay', 'mailrelay_post_meta_box', 'post', 'normal', 'high');
    }

    function mailrelay_post_meta_box($object, $box) {
        $mailrelay_group = get_post_meta($object->ID, 'mailrelay-group', true);
    ?>
    <p>
        <label for="mailrelay-group"><?php echo _e('Please select a group to send this post', 'mailrelay'); ?></label>
        <br /><br />
        <select name="mailrelay-group" id="mailrelay-group" onchange="if (this.value != '') {document.getElementById('spanMailrelayTitle').style.display = '';} else {document.getElementById('spanMailrelayTitle').style.display = 'none';}">
        <option value=""><?php echo _e("- Don't send -", 'mailrelay'); ?></option>
        <?php
        $result = mailrelay_get_groups();
        if ($result && $result->status == 1) {
            if (is_object($result)) {
                $data = $result->data;
                if (is_array($data)) {
                    foreach($data as $key => $value) {
                    ?>
                    <option value="<?php echo $data[$key]->id; ?>"<?php echo $mailrelay_group == $data[$key]->id ? ' selected="selected"' : ''; ?>><?php echo $data[$key]->name; ?></option>
                    <?php
                    }
                }
            }
        }
        ?>
        </select>
        <span id="spanMailrelayTitle" style="<?php echo $mailrelay_group == '' ? 'display:none;' : '' ?>">
        <br /><br />
        <label for="mailrelay-title"><?php echo _e('Alternative Title', 'mailrelay'); ?></label>
        <br /><br />
        <input type="text" name="mailrelay-title" value="<?php echo get_post_meta($object->ID, 'mailrelay-title', true); ?>" size="70" placeholder="<?php echo _e('If you enter a value in this field it will be used instead of post title', 'mailrelay'); ?>" />
        </span>
        <input type="hidden" name="mailrelay_meta_box_nonce" value="<?php echo wp_create_nonce(plugin_basename(__FILE__)); ?>" />
    </p>
    <?php
    }

    function mailrelay_save_post_meta_box($post_id) {
        // AJAX? Not used here
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Return if it's a post revision
        if (false !== wp_is_post_revision($post_id)) {
            return;
        }
        // Sanitizing the alternative title
        $_REQUEST['mailrelay-title'] = trim($_REQUEST['mailrelay-title']);

        if ($_REQUEST['mailrelay-group'] == '')
        {
            delete_post_meta($post_id, 'mailrelay-group');
            delete_post_meta($post_id, 'mailrelay-title');
        }
        else
        {
            $post_meta = get_post_meta($post_id);
            if (empty($post_meta))
            {
                add_post_meta($post_id, 'mailrelay-group', $_REQUEST['mailrelay-group'], true);
                add_post_meta($post_id, 'mailrelay-title', $_REQUEST['mailrelay-title'], true);
            }
            else
            {
                update_post_meta($post_id, 'mailrelay-group', $_REQUEST['mailrelay-group']);
                update_post_meta($post_id, 'mailrelay-title', $_REQUEST['mailrelay-title']);
            }
        }
    }

    function mailrelay_publish_post($post_id, $post) {
        // Get custom field value
        $mailrelay_group = (int)stripslashes(get_post_meta($post_id, 'mailrelay-group', true));
        if ($mailrelay_group == 0) {
            return;
        }
        $mailrelay_title = get_post_meta($post_id, 'mailrelay-title', true);

        // These will be entered by user.
        $mailrelay_host = get_option('mailrelay_host');
        $mailrelay_api_key = get_option('mailrelay_api_key');
        $mailrelay_unsubscribe = get_option('mailrelay_newsletter_unsubscribe');

        // First thing, ger config
        $url = 'https://'. $mailrelay_host .'/ccm/admin/api/version/2/&type=json';
        $curl = curl_init($url);

        // Post title
        $post_title = $mailrelay_title != '' ? $mailrelay_title : get_the_title();

        // Post content
        $post_content = apply_filters('the_content', $post->post_content);
        $post_content = trim(str_replace(']]>', ']]&gt;', $post_content));

        // Call addCampaign
        $params = array(
            'function' => 'addCampaign',
            'apiKey' => $mailrelay_api_key,
            'subject' => $post_title,
            'mailboxFromId' => (int)get_option('mailrelay_newsletter_from'),
            'mailboxReplyId' => (int)get_option('mailrelay_newsletter_reply_to'),
            'mailboxReportId' => (int)get_option('mailrelay_newsletter_report_to'),
            'emailReport' => true,
            'groups' => $mailrelay_group,
            'text' => null,
            'html' => mailrelay_build_template($post_id, $post_title, $post_content, $mailrelay_unsubscribe),
            'packageId' => 6,
            'campaignFolderId' => 1
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

        if (is_object($jsonResult)) {
            if (isset($jsonResult->data) && $jsonResult->data != '') {
                // Call sendCampaign
                $params = array(
                    'function' => 'sendCampaign',
                    'apiKey' => $mailrelay_api_key,
                    'id' => $jsonResult->data
                );
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                $headers = array(
                    'X-Request-Origin: Wordpress|'. MAILRELAY_PLUGIN_VERSION .'|'. get_bloginfo('version')
                );
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($curl);
            }
        }
    }

    function mailrelay_publish_feed($post_id, $post) {
        // These will be entered by user.
        $mailrelay_host = get_option('mailrelay_host');
        $mailrelay_api_key = get_option('mailrelay_api_key');
        $mailrelay_feeds_group = (int)get_option('mailrelay_feeds_group');
        $mailrelay_unsubscribe = get_option('mailrelay_feed_unsubscribe');
        if ($mailrelay_feeds_group == 0) {
            return;
        }

        // First thing, ger config
        $url = 'https://'. $mailrelay_host .'/ccm/admin/api/version/2/&type=json';
        $curl = curl_init($url);

        // Post title
        $post_title = get_the_title();

        // Post content
        $post_content = apply_filters('the_content', $post->post_content);
        $post_content = trim(str_replace(']]>', ']]&gt;', $post_content));

        // Call addCampaign
        $params = array(
            'function' => 'addCampaign',
            'apiKey' => $mailrelay_api_key,
            'subject' => $post_title,
            'mailboxFromId' => (int)get_option('mailrelay_feed_from'),
            'mailboxReplyId' => (int)get_option('mailrelay_feed_reply_to'),
            'mailboxReportId' => (int)get_option('mailrelay_feed_report_to'),
            'emailReport' => true,
            'groups' => $mailrelay_feeds_group,
            'text' => null,
            'html' => mailrelay_build_template($post_id, $post_title, $post_content, $mailrelay_unsubscribe),
            'packageId' => 6,
            'campaignFolderId' => 1
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

        if (is_object($jsonResult)) {
            if (isset($jsonResult->data) && $jsonResult->data != '') {
                // Call sendCampaign
                $params = array(
                    'function' => 'sendCampaign',
                    'apiKey' => $mailrelay_api_key,
                    'id' => $jsonResult->data
                );
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                $headers = array(
                    'X-Request-Origin: Wordpress|'. MAILRELAY_PLUGIN_VERSION .'|'. get_bloginfo('version')
                );
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($curl);
            }
        }
    }

    function mailrelay_do_feed($post_id, $post) {
        $post_meta = get_post_meta($post_id);
        if (!empty($post_meta)) {
            if (get_post_meta($post_id, 'mailrelay-feed-processed', true) == '1') {
                $is_new = false;
            } else {
                $is_new = true;
            }
        } else {
            add_post_meta($post_id, 'mailrelay-feed-processed', '0', true);
            $is_new = true;
        }
        if ($is_new) {
            mailrelay_publish_feed($post_id, $post);
            update_post_meta($post_id, 'mailrelay-feed-processed', '1');
        }
    }

    function mailrelay_build_template($post_id, $post_title, $post_content, $mailrelay_unsubscribe) {
        $post_content = substrhtml(nl2br($post_content), 0, 300) . '...';
        $post_link = get_permalink($post_id);
        if (strpos($post_link, '?') === false)
        {
            $post_link .= '?';
        }
        else
        {
            $post_link .= '&';
        }
        $post_link .= 'utm_source=newsletter&utm_medium=articulo&utm_campaign=blog';

        if ($mailrelay_unsubscribe == '1') {
            $unsubscribe_link = '<a href="[unsubscribe_url_direct]">' . __('Unsubscribe', 'mailrelay') . '</a>';
        } else {
            $unsubscribe_link = '';
        }
        
        $more = '' . __('View more', 'mailrelay') . '';
        $date = '' . __('Posted: ', 'mailrelay') . date('Y-m-d H:i:s');

        $aux     = pathinfo(__FILE__);
        $content = file_get_contents($aux['dirname'] . '/newletter.html');
        $content = str_replace('{$title}', $post_title, $content);
        $content = str_replace('{$date}', $date, $content);
        $content = str_replace('{$content}', $post_content, $content);
        $content = str_replace('{$link}', $post_link, $content);
        $content = str_replace('{$more}', $more, $content);
        $content = str_replace('{$unsubscribe}', $unsubscribe_link, $content);

        return $content;
    }

    function substrhtml($str, $start, $len) {
        $str_clean = substr(strip_tags($str),$start,$len);
        $pos = strrpos($str_clean, ' ');
        if ($pos === false)
        {
            $str_clean = substr(strip_tags($str),$start,$len);
        }
        else
        {
            $str_clean = substr(strip_tags($str),$start,$pos);
        }

        if (preg_match_all('/\<[^>]+>/is',$str,$matches,PREG_OFFSET_CAPTURE))
        {
            for($i=0;$i<count($matches[0]);$i++)
            {
                if($matches[0][$i][1] < $len)
                {
                    $str_clean = substr($str_clean,0,$matches[0][$i][1]) . $matches[0][$i][0] . substr($str_clean,$matches[0][$i][1]);
                }
                else if(preg_match('/\<[^>]+>$/is',$matches[0][$i][0]))
                {
                    $str_clean = substr($str_clean,0,$matches[0][$i][1]) . $matches[0][$i][0] . substr($str_clean,$matches[0][$i][1]);
                    break;
                }
            }
            return $str_clean;
        }
        else
        {
            $string = substr($str,$start,$len);
            $pos = strrpos($string, ' ');
            if ($pos === false)
            {
                return substr($str,$start,$len);
            }
            return substr($str,$start,$pos);
        }
    }

    add_action('init', 'mailrelay_init');
    add_action('admin_menu', 'mailrelay_menu');

    if (get_option('mailrelay_newsletter_post') == '1') {
        add_action('admin_menu', 'mailrelay_create_post_meta_box');
        add_action('save_post', 'mailrelay_save_post_meta_box', 10, 1);
        add_action('publish_post', 'mailrelay_publish_post', 10, 2);
    }

    if (get_option('mailrelay_newsletter_feed') == '1') {
        add_action('publish_post', 'mailrelay_do_feed', 10, 2);
    }

    if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'mailrelay_save_connection_settings')) {
        //Form data sent
        $mailrelay_host = $_REQUEST['mailrelay_host'];
        update_option('mailrelay_host', trim($mailrelay_host));

        $mailrelay_api_key = $_REQUEST['mailrelay_api_key'];
        update_option('mailrelay_api_key', trim($mailrelay_api_key));

        $mailrelay_auto_sync = $_REQUEST['mailrelay_auto_sync'] == 'on';
        update_option('mailrelay_auto_sync', $mailrelay_auto_sync);

        if (!empty($_REQUEST['mailrelay_auto_sync_groups'])) {
            $mailrelay_auto_sync_groups = $_REQUEST['mailrelay_auto_sync_groups'];
            update_option('mailrelay_auto_sync_groups', $mailrelay_auto_sync_groups);
        }

        // These will be entered by user.
        $mailrelay_host = get_option('mailrelay_host');
        $mailrelay_api_key = get_option('mailrelay_api_key');

        // First thing, init
        $url = 'https://' . $mailrelay_host . '/ccm/admin/api/version/2/&type=json';
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
            $message = '<div class="updated"><p>Data saved succesfully.</p></div>';
        }
    }

    if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'mailrelay_save_posts_settings')) {
        //Form data sent
        $mailrelay_newsletter_post = isset($_REQUEST['mailrelay_newsletter_post']) ? $_REQUEST['mailrelay_newsletter_post'] : '';
        update_option('mailrelay_newsletter_post', $mailrelay_newsletter_post);

        $mailrelay_newsletter_from = isset($_REQUEST['mailrelay_newsletter_from']) ? $_REQUEST['mailrelay_newsletter_from'] : '';
        update_option('mailrelay_newsletter_from', $mailrelay_newsletter_from);

        $mailrelay_newsletter_reply_to = isset($_REQUEST['mailrelay_newsletter_reply_to']) ? $_REQUEST['mailrelay_newsletter_reply_to'] : '';
        update_option('mailrelay_newsletter_reply_to', $mailrelay_newsletter_reply_to);

        $mailrelay_newsletter_report_to = isset($_REQUEST['mailrelay_newsletter_report_to']) ? $_REQUEST['mailrelay_newsletter_report_to'] : '';
        update_option('mailrelay_newsletter_report_to', $mailrelay_newsletter_report_to);

        $mailrelay_newsletter_unsubscribe = isset($_REQUEST['mailrelay_newsletter_unsubscribe']) ? $_REQUEST['mailrelay_newsletter_unsubscribe'] : '';
        update_option('mailrelay_newsletter_unsubscribe', $mailrelay_newsletter_unsubscribe);

        $message = '<div class="updated"><p>Data saved succesfully.</p></div>';
    }

    if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'mailrelay_save_feeds_settings')) {
        //Form data sent
        $mailrelay_newsletter_feed = isset($_REQUEST['mailrelay_newsletter_feed']) ? $_REQUEST['mailrelay_newsletter_feed'] : '';
        update_option('mailrelay_newsletter_feed', $mailrelay_newsletter_feed);

        $mailrelay_feeds_group = isset($_REQUEST['mailrelay_feeds_group']) ? $_REQUEST['mailrelay_feeds_group'] : '';
        update_option('mailrelay_feeds_group', $mailrelay_feeds_group);

        $mailrelay_feed_from = isset($_REQUEST['mailrelay_feed_from']) ? $_REQUEST['mailrelay_feed_from'] : '';
        update_option('mailrelay_feed_from', $mailrelay_feed_from);

        $mailrelay_feed_reply_to = isset($_REQUEST['mailrelay_feed_reply_to']) ? $_REQUEST['mailrelay_feed_reply_to'] : '';
        update_option('mailrelay_feed_reply_to', $mailrelay_feed_reply_to);

        $mailrelay_feed_report_to = isset($_REQUEST['mailrelay_feed_report_to']) ? $_REQUEST['mailrelay_feed_report_to'] : '';
        update_option('mailrelay_feed_report_to', $mailrelay_feed_report_to);

        $mailrelay_feed_unsubscribe = isset($_REQUEST['mailrelay_feed_unsubscribe']) ? $_REQUEST['mailrelay_feed_unsubscribe'] : '';
        update_option('mailrelay_feed_unsubscribe', $mailrelay_feed_unsubscribe);

        $message = '<div class="updated"><p>Data saved succesfully.</p></div>';
    }
}