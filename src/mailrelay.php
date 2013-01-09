<?php 
/*
Plugin Name: Mailrelay
Plugin URI: http://mailrelay.com
Description: Easily sync your Wordpress users with Mailrelay.
Author: Mailrelay.com
Version: 1.1.1
*/
add_action('admin_menu', array('MailRelay', 'addMenuPage'));


Class MailRelay
{
    
    const slug = 'mailrelay';
    const url  = 'http://%s/ccm/admin/api/version/2/&type=%s';
    
    private $step, $username, $password, $userhost, $apikey, $groups;

    public $message = null;
    
    public function getMessage()
    {
        return $this->message;
    }
    
    public function setMessage( $value )
    {
        return $this->message = $value;
    }
    
    public function getServiceUrl( $format = 'json')
    {
        return sprintf(self::url, $this->userhost, $format);
    }
    
    public function addMenuPage()
    {
        // Add the top-level admin menu
        $page_title = 'Mail Relay';
        $menu_title = 'Mail Relay';
        $capability = 'manage_options';
        $menu_slug  = self::slug;
        $function   = array(new MailRelay(), 'controller');
        $icon_url   = null;
        $position   = null;

        
        add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
        
    }
    
    public function controller()
    {
        $this->step = ( !isset($_REQUEST['step']) ) ? 'step1' : $_REQUEST['step'];
        switch($this->step)
        {
            case 'step2':
                $this->step2();
                break;
            case 'step3':
                $this->step3();
                break;
            default:
                $this->step1();
                break;
        }
       
    }
    
    public function step1()
    {
       include('step1.php');
    } 
    
    public function step2()
    {
        //Get the Values
        $this->username = (isset($_POST['usrname']))  ? $_POST['usrname']  : get_option('usrname');
        $this->password = (isset($_POST['pwd']))      ? $_POST['pwd']      : get_option('pwd');
        $this->userhost = (isset($_POST['userhost'])) ? $_POST['userhost'] : get_option('userhost');
        
        //Update the Values
        update_option('usrname', $this->username);
        update_option('pwd', $this->password);
        update_option('userhost', $this->userhost);

        
	// First thing, authenticate
	$jsonResult = $this->authenticate();

        if(!$jsonResult){
            $this->setMessage('Invalid host, username or password. Please Retry.');   
            $this->step1();
            return;
        }
        
        if($jsonResult->error == 'Your account does not have an API key.'){
            $this->setMessage ('Your account does not have an API key. Please, generate one in your Mailrelay\'s account: Settings -> API access -> Generate new API key.');
            $this->step1();
            return;
        }
        
        // error with API
        if(!$jsonResult->data){
            
            $this->setMessage ('Invalid host, username or password. Please Retry.');
            $this->step1();
            return;
        }
        
        //Set Api Key
        $this->apikey = $jsonResult->data;
        
        //Get Groups
        $this->groups = $this->getGroups();
        
        if(!$this->groups){
            $this->setMessage ('Gruoups not found. Please Retry.');
            $this->step1();
            return;
        }
            
        include('step2.php');
    } 
    
    public function step3()
    {
    
        //@TODO: implement Step 3
    }
    
    
    public function authenticate()
    {
        
        // First thing, authenticate
	$url  = $this->getServiceUrl();
	$curl = curl_init($url);

	$params = array(
		'function' => 'doAuthentication',
		'username' => $this->username,
		'password' => $this->password
	);

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

        $headers = array(
        	'X-Request-Origin: Wordpress|1.1.1|'.$wp_version 
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	// Call the page, it will return a json
	$result = curl_exec($curl);
	$jsonResult = json_decode($result);
       
        if( !$jsonResult || trim($jsonResult->status)!=1 )
            return false;
        else
            return $jsonResult;
    }
    
    public function getGroups()
    {
        
        // Call getGroups
	$params = array(
            'function'  => 'getGroups',
            'apiKey'    => $this->apikey,
            'sortField' => 'name',
            'sortOrder' => 'ASC'
        );
        
        $url = $this->getServiceUrl();
        $curl = curl_init( $url );
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

       	$headers = array(
                    'X-Request-Origin: Wordpress|1.1.1|'.$wp_version 
                   );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        
        $result = curl_exec($curl);
        $jsonResult = json_decode($result);
        
        if( $jsonResult && $jsonResult->status == 1 && isset($jsonResult->data)){
            update_option('jsonResult', $jsonResult);
            return $jsonResult->data;
        }else{
            return false;
        }
        
    }
}

?>