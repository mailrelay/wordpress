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
    
    const addSubscriber     = 'addSubscriber';
    const updateSubscriber  = 'updateSubscriber';
    const getSubscribers    = 'getSubscribers';
    const getGroups         = 'getGroups';
    const doAuthentication  = 'doAuthentication';
    
    private $step, $username, $password, $userhost, $apikey, $groups, $headers;

    private $message = null;
    
    public function getMessage()
    {
        return $this->message;
    }
    
    public function setMessage( $value )
    {
        return $this->message = $value;
    }
    
    public function addMenuPage()
    {
        global $wp_version;
        
        $mailRelay = new MailRelay();
        
        //Get the plugin values
        $mailRelay->username = get_option('usrname');
        $mailRelay->password = get_option('pwd');
        $mailRelay->userhost = get_option('userhost');
        $mailRelay->apikey   = get_option('apikey');
        $mailRelay->headers  = array('X-Request-Origin: Wordpress|1.1.1|'.$wp_version);
        
        
        // Add the top-level admin menu
        $page_title = 'Mail Relay';
        $menu_title = 'Mail Relay';
        $capability = 'manage_options';
        $menu_slug  = self::slug;
        $function   = array($mailRelay, 'controller');
        $icon_url   = null;
        $position   = null;

        add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
        
    }
    
    
    private function getServiceUrl( $format = 'json')
    {
        return sprintf(self::url, $this->userhost, $format);
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
        //Include view
       include('step1.php');
    } 
    
    public function step2()
    {
        //Get the Values
        $this->username = (isset($_POST['usrname']))  ? $_POST['usrname']  : $this->username;
        $this->password = (isset($_POST['pwd']))      ? $_POST['pwd']      : $this->password;
        $this->userhost = (isset($_POST['userhost'])) ? $_POST['userhost'] : $this->userhost;
        
        //Update the Values
        update_option('usrname',    $this->username);
        update_option('pwd',        $this->password);
        update_option('userhost',   $this->userhost);

        
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
        update_option('apikey',  $this->apikey);
        
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
        
        $added   = 0;
        $updated = 0;
        $fail    = 0;
       
        //Get the selected Group.
        $group = (isset($_POST['group'])) ? $_POST['group'] : null;
        if(is_null($group)){
            $this->setMessage('Gruoups not selected. Please Retry.');
            $this->step1();
            return;
        }
        
        
        
        //Get the Users
        $users = $this->getUsers();
        foreach($users as $user){
            
            //Check user exists
            $params = array(
                        'function'  => self::getSubscribers,
			'apiKey'    => $this->apikey,
			'email'     => $user->user_email
                    );
            
            $result = $this->curlRequest($params);
            $jsonResult = json_decode($result);
            
            if(!$jsonResult){
                $this->setMessage('Error on getSubscribers');
                $this->step1();
                return;
            }
                
            
            $params = array(
                            'email'     => $user->email,
                            'name'      => $user->name,
                            'groups'    => $group
                          );
            
            
            //Update
            if(count($jsonResult->data)>0){
                
                $params['id'] = $jsonResult->data[0]->id;
                $params['function'] = self::updateSubscriber;
                if($this->saveSubscriber($params))
                    $updated++;
                else
                    $fail++;
                
                
            }else{
                
                $params['function'] = self::addSubscriber;
                if($this->saveSubscriber($params))
                   $saved++;
                else
                   $fail++;
            }
				
	    	
		
        }//endforeach;
        
        include('step3.php');
        
    }
    
    private function getUsers()
    {
        global $wpdb;
        
        $users = array();
        
        $query = sprintf('SELECT user_email, display_name FROM %s', $wpdb->users );
	$temp = $wpdb->get_results($query, OBJECT);
        
        if($temp){
            foreach($temp as $t){
                $user = new stdClass();
                $user->name  = $t->display_name;
                $user->email = $t->user_email;
                array_push($users, $user);
            }
        }
        return $users;
    }
    
    private function authenticate()
    {
        $params = array(
		'function' => self::doAuthentication,
		'username' => $this->username,
		'password' => $this->password
	);

        $result = $this->curlRequest($params);
	$jsonResult = json_decode($result);
       
        if( !$jsonResult || trim($jsonResult->status)!=1 )
            return false;
        else
            return $jsonResult;
    }
    
    private function getGroups()
    {
 	$params = array(
            'function'  => self::getGroups,
            'apiKey'    => $this->apikey,
            'sortField' => 'name',
            'sortOrder' => 'ASC'
        );
        
        $result = $this->curlRequest($params);
	$jsonResult = json_decode($result);
        
        if( $jsonResult && $jsonResult->status == 1 && isset($jsonResult->data)){
            return $jsonResult->data;
        }else{
            return false;
        }
        
    }
    
    private function saveSubscriber( array $params)
    {
        $defaults = array(
                    'apiKey'    => $this->apikey,
                    'function'  => null,
                    'email'     => null,
                    'name'      => null,
                    'groups'    => null
                );
        
        $params = array_merge($defaults, $params);
        
        //check the values
        foreach($params as $key=>$value){
            if(is_null($value))
                throw new Exception(sprintf('To save the Subscriver, "%s" cannot be null (%s - %s)', $key, __FILE__, __LINE__));
        }
        
        $result = $this->curlRequest($params);
	$jsonResult = json_decode($result);
        return ($jsonResult->status == 1);
    
    }
    
    private function curlRequest($params, $url = null, $headers = null)
    {
        $url     = (is_null($url))     ? $this->getServiceUrl() : $url;
        $headers = (is_null($headers)) ? $this->headers         : $headers;
        
	$curl = curl_init($url);

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        
	return curl_exec($curl);
    }
}

?>