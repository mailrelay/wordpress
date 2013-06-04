<?php

/*
Plugin Name: Mailrelay
Plugin URI: http://mailrelay.com
Description: Easily sync your Wordpress users with Mailrelay.
Author: Mailrelay.com
Version: 1.2.0
*/

//*************** Admin function ***************
function step1()
{
    include('step1.php');
}

function step2()
{
    include('step2.php');
}

function step3()
{
    include('step3.php');
}

function web_admin_actions()
{
    add_options_page("Mailrelay", "Mailrelay", 1, "Mailrelay", "step1");
}

function web_admin_actions1()
{
    $_REQUEST["step"] = "step1";
    add_options_page("Mailrelay", "Mailrelay", 1, "Mailrelay", "step1");
}

function web_admin_actions2()
{
    add_options_page("Mailrelay", "Mailrelay", 1, "Mailrelay", "step2");
}

function web_admin_actions3()
{
    add_options_page("Mailrelay", "Mailrelay", 1, "Mailrelay", "step3");
}

function mailrelay_init()
{
    $result = load_plugin_textdomain('mailrelay', false, dirname(plugin_basename(__FILE__)).'/languages');
}

if(!isset($_REQUEST['step']) || ($_REQUEST['step'] == 'step1'))
{
    add_action('admin_menu', 'web_admin_actions');
}
add_action('init', 'mailrelay_init');

if(isset($_REQUEST['step']) && ($_REQUEST['step'] == 'step2'))
{
    //Form data sent
    $userhost = $_POST['userhost'];
    update_option('userhost', trim($userhost));

    $mailrelay_api_key = $_POST['mailrelay_api_key'];
    update_option('mailrelay_api_key', trim($mailrelay_api_key));

    // These will be entered by user.
    $hostname = get_option('userhost');
    $mailrelay_api_key = get_option('mailrelay_api_key');

    // First thing, authenticate
    $url = 'http://'. $hostname .'/ccm/admin/api/version/2/&type=json';
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

    $headers = array(
        'X-Request-Origin: Wordpress|1.2.0|'.$wp_version
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($curl);
    $jsonResult = json_decode($result);

    if (!$jsonResult || trim($jsonResult->status) != 1)
    {
        global $message;
        add_action('admin_menu', 'web_admin_actions1');

        if (is_object($jsonResult)) {
            if ($jsonResult->error != '') {
                $message = $jsonResult->error;
            } else {
                $message = "Your account does not have an API key. Please, generate one in your Mailrelay's account: Settings -> API access -> Generate new API key.";
            }
        } else {
            $message = 'Invalid host. Please Retry.';
        }
    } else {
        update_option('jsonResult', $jsonResult);
        add_action('admin_menu', 'web_admin_actions2');
    }
}
else
{
    $userhost = get_option('userhost');
    $mailrelay_api_key = get_option('mailrelay_api_key');
}

if(isset($_REQUEST['step']) && ($_REQUEST['step'] == 'step3'))
{
    $querystr = "SELECT * FROM $wpdb->users";
    $users = $wpdb->get_results($querystr, OBJECT);
    $groups = $_POST['group'];

    //Form data sent
    $userhost = $_POST['userhost'];
    $mailrelay_api_key = $_POST['mailrelay_api_key'];
    update_option('userhost', $userhost);
    update_option('mailrelay_api_key', $mailrelay_api_key);
    update_option('step', 'step2');

    // These will be entered by user.
    $hostname = get_option('userhost');
    $mailrelay_api_key = get_option('mailrelay_api_key');

    // First thing, authenticate
    $url = 'http://'. $hostname .'/ccm/admin/api/version/2/&type=json';
    $curl = curl_init($url);

    $added = 0;
    $updated = 0;
    $fail = 0;

    foreach($users as $user)
    {
        $user->user_email;
        // Call getSubscribers
        $params = array(
            'function' => 'getSubscribers',
            'apiKey' => $mailrelay_api_key,
            'email' => $user->user_email,
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

        $headers = array(
            'X-Request-Origin: Wordpress|1.2.0|'.$wp_version
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($curl);
        $jsonResult = json_decode($result);

        if(count($jsonResult->data) > 0)
        {
            $params = array(
                'function' => 'updateSubscriber',
                'apiKey' => $mailrelay_api_key,
                'id' => $jsonResult->data[0]->id,
                'email' => $user->user_email,
                'name' => $user->display_name,
                'groups' => $groups
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

            $headers = array(
                'X-Request-Origin: Wordpress|1.2.0|'.$wp_version
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($curl);
            $jsonResult = json_decode($result);

            if($jsonResult->status == 1)
            {
                $updated++;
            }
            else
            {
                $fail++;
            }
        } else {
            $params = array(
                'function' => 'addSubscriber',
                'apiKey' => $mailrelay_api_key,
                'email' => $user->user_email,
                'name' => $user->display_name,
                'groups' => $groups
            );

            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

            $headers = array(
                'X-Request-Origin: Wordpress|1.2.0|'.$wp_version
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($curl);
            $jsonResult = json_decode($result);

            if($jsonResult->status == 1)
            {
                $added++;
            }
            else
            {
                $fail++;
            }
        }
    }
    update_option('added', $added);
    update_option('updated', $updated);
    update_option('fail', $fail);

    add_action('admin_menu', 'web_admin_actions3');
}
else
{
    $userhost = get_option('userhost');
    $mailrelay_api_key = get_option('mailrelay_api_key');
}
?>