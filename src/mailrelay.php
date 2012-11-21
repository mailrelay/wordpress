<?php 

/*
Plugin Name: Mailrelay
Plugin URI: http://mailrelay.com
Description: Easily sync your Wordpress users with Mailrelay.
Author: Mailrelay.com
Version: 1.1.1
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

if(!isset($_REQUEST['step']) || ($_REQUEST['step']=='step1') )
{
	add_action('admin_menu', 'web_admin_actions');
}
add_action('init', 'mailrelay_init');

if(isset($_REQUEST['step']) && ($_REQUEST['step']=='step2') )
{
	//Form data sent
	$usrname = $_POST['usrname'];
	update_option('usrname', $usrname);

	$pwd = $_POST['pwd'];
	update_option('pwd', $pwd);

	$userhost = $_POST['userhost'];
	update_option('userhost', $userhost);

	// These will be entered by user. 
	$username = get_option('usrname');
	$password = get_option('pwd');
	$hostname = get_option('userhost');

	// First thing, authenticate
	$url = 'http://'. $hostname .'/ccm/admin/api/version/2/&type=json';
	$curl = curl_init($url);

	$params = array(
		'function' => 'doAuthentication',
		'username' => $username,
		'password' => $password
	);

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

        $headers = array(
        	'X-Request-Origin' => 'Wordpress|1.1.1|'.$wp_version 
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	// Call the page, it will return a json
	$result = curl_exec($curl);
	$jsonResult = json_decode($result);

	if (!$jsonResult || trim($jsonResult->status)!=1)
	{
		global $message;
		add_action('admin_menu', 'web_admin_actions1');

		if ($jsonResult->error == 'Your account does not have an API key.') {
			$message = "Your account does not have an API key. Please, generate one in your Mailrelay's account: Settings -> API access -> Generate new API key.";
		} else {
			$message = 'Invalid host, username or password. Please Retry.';
		}
	}
	else
	{
		$apiKey = $jsonResult->data;
		if ($apiKey)
		{
			// Call getGroups
			$params = array(
				'function' => 'getGroups',
				'apiKey' => $apiKey,
				'sortField' => 'name',
				'sortOrder' => 'ASC'
			);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

       			$headers = array(
                		'X-Request-Origin' => 'Wordpress|1.1.1|'.$wp_version 
        		);
        		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($curl);
			$jsonResult = json_decode($result);
			update_option('jsonResult', $jsonResult);
			add_action('admin_menu', 'web_admin_actions2');
		}
		else
		{
			// error with API
			add_action('admin_menu', 'web_admin_actions1');
			$message = 'Invalid host, username or password. Please Retry.';
		}
	}
} 
else 
{
	$usrname = get_option('usrname');
	$pwd = get_option('pwd');
	$userhost = get_option('userhost');
}

if(isset($_REQUEST['step']) && ($_REQUEST['step']=='step3') )
{
	$querystr = "SELECT * FROM $wpdb->users";
	$users = $wpdb->get_results($querystr, OBJECT);
	$groups=$_POST['group'];

	//Form data sent
	$usrname = $_POST['usrname'];
	update_option('usrname', $usrname);
	
	$pwd = $_POST['pwd'];
	update_option('pwd', $pwd);
	
	$userhost = $_POST['userhost'];
	update_option('userhost', $userhost);
	update_option('step', 'step2');

	// These will be entered by user.
	$username = get_option('usrname');
	$password = get_option('pwd');
	$hostname = get_option('userhost');

	// First thing, authenticate
	$url = 'http://'. $hostname .'/ccm/admin/api/version/2/&type=json';
	$curl = curl_init($url);

	$params = array(
		'function' => 'doAuthentication',
		'username' => $username,
		'password' => $password
	);

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

        $headers = array(
                'X-Request-Origin' => 'Wordpress|1.1.1|'.$wp_version 
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	// Call the page, it will return a json
	$result = curl_exec($curl);
	$jsonResult = json_decode($result);

	if(trim($jsonResult->status)==1)
	{
		$added=0;
		$updated=0;
		$fail=0;

		if (!$jsonResult->status) 
		{
			throw new Exception('Authenticate failed. Please verify hostname, username and password.');
		}
		else
		{
			$apiKey = $jsonResult->data;
		}

		foreach($users as $user)
		{
			$user->user_email;
			// Call getSubscribers
			$params = array(
				'function' => 'getSubscribers',
				'apiKey' => $apiKey,
				'email'=>$user->user_email,
			);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

        		$headers = array(
                		'X-Request-Origin' => 'Wordpress|1.1.1|'.$wp_version 
        		);
        		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($curl);
			$jsonResult = json_decode($result);

			if(count($jsonResult->data)>0)
			{
				$params = array(
					'function' => 'updateSubscriber',
					'apiKey' => $apiKey,
					'id'=>$jsonResult->data[0]->id,
					'email'=>$user->user_email,
					'name'=>$user->display_name,
					'groups'=>$groups
				);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

        			$headers = array(
                			'X-Request-Origin' => 'Wordpress|1.1.1|'.$wp_version 
        			);
        			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

				$result = curl_exec($curl);
				$jsonResult = json_decode($result);

				if($jsonResult->status==1)
				{
					$updated++;
				}
				else
				{
					$fail++;
				}
			}
			else
			{
				$params = array(
					'function' => 'addSubscriber',
					'apiKey' => $apiKey,
					'email'=>$user->user_email,
					'name'=>$user->display_name,
					'groups'=>$groups
				);

				curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

        			$headers = array(
                			'X-Request-Origin' => 'Wordpress|1.1.1|'.$wp_version 
        			);
        			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

				$result = curl_exec($curl);
				$jsonResult = json_decode($result);

				if($jsonResult->status==1)
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
	}
	add_action('admin_menu', 'web_admin_actions3');
} 
else 
{
	$usrname = get_option('usrname');
	$pwd = get_option('pwd');
	$userhost = get_option('userhost');
}
?>
