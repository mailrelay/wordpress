<?php

/*
 * Plugin Name: Mailrelay
 * Plugin URI: http://mailrelay.com
 * Description: Syncronize your wordpress users with Mailrelay
 * Version: 2.0
 * Author: Consultor-PC
 * Text Domain: Mailrelay
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    die('Access Denied.');
}

define('MAILRELAY_PLUGIN_VERSION', '2.0');


function style_css() {
    wp_enqueue_style( 'admin_css', (plugins_url() . '/mailrelay/includes/css/style.css') );
}
add_action('admin_enqueue_scripts', 'style_css');

add_action('init', 'mailrelay_init');
function mailrelay_init() {
    $result = load_plugin_textdomain('mailrelay', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

function mailrelay_global() {
    global $mailrelayData;
    $mailrelayData = array (
        'host'        => get_option('mailrelay_host'),
        'api_key'     => get_option('mailrelay_api_key'),
        'auto_sync'   => get_option('mailrelay_auto_sync'),
        'groups_sync' => get_option('mailrelay_auto_sync_groups'),
    );

}
add_action('add_global', 'mailrelay_global', 10);
do_action('add_global');

class MailrelayPage {

    private $mailrelay_page_options;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'mailrelay_page_add_plugin_page', ) );
        add_action( 'admin_init', array( $this, 'mailrelay_page_page_init' ) );
        add_action( 'admin_init', array( $this, 'settings_page_page_init' ) );
        add_action( 'admin_init', array( $this, 'manual_page_page_init' ) );
    }

    public function mailrelay_page_add_plugin_page() {
        add_menu_page(
            'Mailrelay', // page_title
            'Mailrelay', // menu_title
            'manage_options', // capability
            'mailrelay', // menu_slug
            array( $this, 'mailrelay_page_create_admin_page' ), // function
            plugins_url('mailrelay/mailrelay.png')
        );

    }
 
    public function mailrelay_page_create_admin_page() {
        
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        global $testPing;
        global $message;
        global $mailrelayData;

        if ($testPing == 204) {
            $default_tab = "Settings";
            $authenticated = 'hidden'; 
        }
        else {  
            $default_tab = "Authentication";
            $disconected = 'hidden';
        }

        $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
        ?>

        <div class="wrap">

            <h1>Mailrelay</h1>

            <?php
                if (!empty($message)) {
                            sprintf(_e($message, 'mailrelay'));
                        }
            ?>
            
            <nav class="nav-tab-wrapper">
              <a href="?page=mailrelay&tab=Authentication" class="nav-tab <?php if (isset($authenticated) ) { echo $authenticated; }  ?><?php if($tab==='Authentication'):?>nav-tab-active<?php endif; ?>"><?php _e('Authentication', 'mailrelay') ?></a>
              <a href="?page=mailrelay&tab=Settings" class="nav-tab <?php if (isset($disconected) ) { echo $disconected; }  ?> <?php if($tab==='Settings'):?>nav-tab-active<?php endif; ?>"><?php _e('Settings', 'mailrelay') ?></a>
              <a href="?page=mailrelay&tab=Manual" class="nav-tab <?php if (isset($disconected) ) { echo $disconected; }  ?> <?php if($tab==='Manual'):?>nav-tab-active<?php endif; ?>"><?php _e('Manual Sync', 'mailrelay') ?></a>
            </nav>
            
            <div class="tab-content">
            <?php switch($tab) :
                case 'Manual':
                ?>
                <div id="tab-manual">
                    <h3><?php sprintf(_e('Manual Sync', 'mailrelay')); ?></h3>        


                        <?php 
                        settings_errors(); 
                        ?>

                        <form name="webservices_form" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">
                            <?php
                                settings_fields( 'manual_page_option_group' );
                                do_settings_sections( 'manual-page-admin' );
                                $atributes = array('onclick' => 'return check_form();');
                                $submit_text = __('Sync', 'mailrelay');
                                submit_button($submit_text, 'primary', 'submit-manual', true, $atributes);
                            ?>
                        </form>
                </div>    
            </div>

                <?php
                break;

                
                case 'Authentication':

                $link = site_url('/wp-admin/admin.php?page=mailrelay');
                ?>
                <div id="tab-authentication">
                    <h3><?php sprintf(_e('Authentication', 'mailrelay')) ?></h3>
                    <p><?php sprintf(_e('Login using your account name and API Key.', 'mailrelay')) ?></p>
                    <p><?php sprintf(_e('For example if your account name is demo.ipzmarketing.com write only "demo".', 'mailrelay')) ?></p>
                    <p><?php sprintf(_e('Your API Key can be found or generated at your Mailrelay Account -> Settings -> API Access.', 'mailrelay')) ?> 
                    <?php 
                        
                        settings_errors(); 
                        ?>
                        <form name="webservices_form" id="form_conection <?php if (isset($testPing)) echo 'logged' ?>" method="post" action="<?php echo $link ?>">
                            <?php
                                settings_fields( 'mailrelay_page_option_group' );
                                do_settings_sections( 'mailrelay-page-admin' );
                                $submit_text = __('Save', 'mailrelay');
                                submit_button($submit_text, 'primary', 'submit-authentication');
                            ?>
                        </form>
                </div>
            </div>
                <?php
                break;

                
                case 'Settings':
                $link = site_url('/wp-admin/admin.php?page=mailrelay&tab=Authentication');
                ?>  
                <div id="tab-settings">
                    <h3><?php sprintf(_e('Settings', 'mailrelay')); ?></h3> 
                    <p><?php echo sprintf(__('You are currently logged in as <strong>%1$s.ipzmarketing.com</strong> (<a href="%2$s">Change Account</a>)', 'mailrelay'), $mailrelayData['host'], esc_url( $link)) ?></p>       
                    <?php 
         
                        
                        settings_errors(); 

                        ?>

                        <form name="webservices_form" id="form_conection" method="post" action="">
                            <?php
                                settings_fields( 'settings_page_option_group' );
                                do_settings_sections( 'settings-page-admin' );
                                $atributes = array('onclick' => 'return check_form();');
                                $submit_text = __('Save', 'mailrelay');
                                submit_button($submit_text, 'primary', 'submit-settings', true, $atributes);

                                
                            ?>
                        </form>
                </div>
            </div>
                <?php
                break;
            endswitch; ?>
            </div>
        </div>
        <script type="text/javascript">
                    
            function check_form() {
                var chk = check();
                if (chk != false) {
                    document.webservices_form.submit();
                } else {
                    return false;
                }
            }

            function check() {
                if(jQuery('#mailrelay_group').val() == '' || jQuery('#mailrelay_auto_sync_groups').val() == '') {
                    alert("<?php sprintf(_e('Please select at least one Group.', 'mailrelay')); ?>");
                    return false;
                }
                return true;
            }

        </script>
           
<?php }

    public function mailrelay_page_page_init() {
        register_setting(
            'mailrelay_page_option_group', // option_group
            'mailrelay_page_option_name', // option_name
            array( $this, 'mailrelay_page_sanitize' ) // sanitize_callback
        );

        add_settings_section(
            'mailrelay_page_setting_section', // id
            '', // title
            array( $this, 'mailrelay_page_section_info' ), // callback
            'mailrelay-page-admin' // page
        );

        add_settings_field(
            'account', // id
            __('Account', 'mailrelay'), // title
            array( $this, 'account_callback' ), // callback
            'mailrelay-page-admin', // page
            'mailrelay_page_setting_section' // section
        );

        add_settings_field(
            'api_key', // id
            __('API Key', 'mailrelay'), // title
            array( $this, 'api_key_callback' ), // callback
            'mailrelay-page-admin', // page
            'mailrelay_page_setting_section' // section
        );

        add_settings_field(
            'action', // id
            '', // title
            array( $this, 'action_callback' ), // callback
            'mailrelay-page-admin', // page
            'mailrelay_page_setting_section', // section
            [
                'class' => 'hidden'
            ]
        );

    }


    public function mailrelay_page_sanitize($input) {
        $sanitary_values = array();
        if ( isset( $input['account'] ) ) {
            $sanitary_values['account'] = sanitize_text_field( $input['account'] );
        }
        if ( isset( $input['api_key'] ) ) {
            $sanitary_values['api_key'] = sanitize_text_field( $input['api_key'] );
        }
        if ( isset( $input['action'] ) ) {
            $sanitary_values['action'] = sanitize_text_field( $input['action'] );
        }

        return $sanitary_values;
    }
   
    public function mailrelay_page_section_info() { }

    public function settings_page_page_init() {
        register_setting(
            'settings_page_option_group', // option_group
            'settings_page_option_name', // option_name
            array( $this, 'settings_page_sanitize' ) // sanitize_callback
        );

        add_settings_section(
            'settings_page_setting_section', // id
            '', // title
            array( $this, 'settings_page_section_info' ), // callback
            'settings-page-admin' // page
        );

        add_settings_field(
            'auto_sync', // id
            __('Automatically sync new users with Mailrelay', 'mailrelay'), // title
            array( $this, 'auto_sync_callback' ), // callback
            'settings-page-admin', // page
            'settings_page_setting_section'// section
        );
        $link = 'javascript:window.location.href=window.location.href';
        add_settings_field(
            'groups', // id
            sprintf(__('Groups that you want to automatically syncronize <br /><a href="%s">(refresh groups)</a>', 'mailrelay'), $link), // title
            array( $this, 'groups_callback' ), // callback
            'settings-page-admin', // page
            'settings_page_setting_section' // section
        );

        add_settings_field(
            'action', // id
            null, // title
            array( $this, 'action_callback' ), // callback
            'settings-page-admin', // page
            'settings_page_setting_section', // section
            [
                'class' => 'hidden'
            ]
        );

        add_settings_field(
            'account', // id
            '', // title
            array( $this, 'account_settings' ), // callback
            'settings-page-admin', // page
            'settings_page_setting_section', // section
            [
                'class' => 'hidden'
            ]
        );

        add_settings_field(
            'api_key', // id
            '', // title
            array( $this, 'api_key_settings' ), // callback
            'settings-page-admin', // page
            'settings_page_setting_section', // section
            [
                'class' => 'hidden'
            ]
        );
    }

    public function settings_page_section_info() { }

    public function settings_page_sanitize($input) {
        $sanitary_values = array();
        if ( isset( $input['groups'] ) ) {
            $sanitary_values['groups'] = $input['groups'];
        }
        if ( isset( $input['auto_sync'] ) ) {
            $sanitary_values['auto_sync'] = $input['auto_sync'];
        }
        if ( isset( $input['action'] ) ) {
            $sanitary_values['action'] = sanitize_text_field( $input['action'] );
        }
        if ( isset( $input['account'] ) ) {
            $sanitary_values['account'] = sanitize_text_field( $input['account'] );
        }
        if ( isset( $input['api_key'] ) ) {
            $sanitary_values['api_key'] = sanitize_text_field( $input['api_key'] );
        }
        return $sanitary_values;
    }

    public function manual_page_page_init() {
        register_setting(
            'manual_page_option_group', // option_group
            'manual_page_option_name', // option_name
            array( $this, 'manual_page_sanitize' ) // sanitize_callback
        );

        add_settings_section(
            'manual_page_setting_section', // id
            '', // title
            array( $this, 'manual_page_section_info' ), // callback
            'manual-page-admin' // page
        );

        $link = 'javascript:window.location.href=window.location.href';
        add_settings_field(
            'groups', // id
            sprintf(__('Please select Groups <br /><a href="%s">(refresh groups)</a>', 'mailrelay'), $link), // title
            array( $this, 'manual_groups_callback' ), // callback
            'manual-page-admin', // page
            'manual_page_setting_section' // section
        );

        add_settings_field(
            'action', // id
            '', // title
            array( $this, 'sync_callback' ), // callback
            'manual-page-admin', // page
            'manual_page_setting_section', // section
            [
                'class' => 'hidden'
            ]
        );

        add_settings_field(
            'account', // id
            '', // title
            array( $this, 'account_settings' ), // callback
            'manual-page-admin', // page
            'manual_page_setting_section', // section
            [
                'class' => 'hidden'
            ]
        );

        add_settings_field(
            'api_key', // id
            '', // title
            array( $this, 'api_key_settings' ), // callback
            'manual-page-admin', // page
            'manual_page_setting_section', // section
            [
                'class' => 'hidden'
            ]
        );

        if (mailrelay_woo_commmerce_installed()) {
            add_settings_field(
            'woo_commerce', // id
            __('WooCommerce options', 'mailrelay'), // title
            array( $this, 'woocommerce_callback' ), // callback
            'manual-page-admin', // page
            'manual_page_setting_section' // section
            );
        }

    }

    public function manual_page_sanitize($input) {
        $sanitary_values = array();
        if ( isset( $input['groups'] ) ) {
            $sanitary_values['groups'] = $input['groups'];
        }
        if ( isset( $input['auto_sync'] ) ) {
            $sanitary_values['auto_sync'] = $input['auto_sync'];
        }
        if ( isset( $input['action'] ) ) {
            $sanitary_values['action'] = sanitize_text_field( $input['action'] );
        }
        if ( isset( $input['account'] ) ) {
            $sanitary_values['account'] = sanitize_text_field( $input['account'] );
        }
        if ( isset( $input['api_key'] ) ) {
            $sanitary_values['api_key'] = sanitize_text_field( $input['api_key'] );
        }
        if ( isset( $input['woo_commerce'] ) ) {
            $sanitary_values['woo_commerce'] = $input['woo_commerce'];
        }
        return $sanitary_values;
    }

    public function manual_page_section_info() { }
    
    public function account_callback() {
        global $mailrelayData;
        printf(
            '<input class="regular-text"  type="text" name="mailrelay_host" id="host" value="%s"> <div class="ipz">.ipzmarketing.com</div>',
            $mailrelayData['host']
        );
    }

    public function account_settings() {
        global $mailrelayData;
        printf(
            '<input class="regular-text"  type="hidden" name="mailrelay_host" id="host" value="%s">',
            $mailrelayData['host']
        );
    }

    public function api_key_callback() {
        global $mailrelayData;
        printf(
            '<input class="regular-text" type="text" name="mailrelay_api_key" id="api_key" value="%s">',
            $mailrelayData['api_key']
        );
    }

    public function api_key_settings() {
        global $mailrelayData;
        printf(
            '<input class="regular-text" type="hidden" name="mailrelay_api_key" id="api_key" value="%s">',
            $mailrelayData['api_key']
        );
    }

    public function action_callback() {
        printf(
            '<input class="regular-text" type="hidden" name="action" value="mailrelay_save_connection_settings" />'
        );
    }

     public function sync_callback() {
        printf(
            '<input class="regular-text" type="hidden" name="action" value="mailrelay_sync_users_group" />'
        );
    }

     public function sync_users() {
        printf(
            '<input class="regular-text" type="hidden" name="action" value="mailrelay_sync_users" />'
        );
    }

    public function auto_sync_callback() {
        $mailrelayData['auto_sync'] = get_option('mailrelay_auto_sync');
        ?>
            <input type="checkbox" name="mailrelay_auto_sync" id="mailrelay_auto_sync" <?php echo $mailrelayData['auto_sync'] ? 'checked' : '' ?>/>
        <?php
    }

    public function groups_callback() {
        global $mailrelayData;
        if (!empty($mailrelayData['host']) && !empty($mailrelayData['api_key'])) {
            $groups = mailrelay_get_groups();
        }
        $mailrelayData['groups_sync'] = get_option('mailrelay_auto_sync_groups');

        ?> <select multiple name="mailrelay_auto_sync_groups[]" id="mailrelay_auto_sync_groups" class="form-select">
            <?php foreach($groups as $value) { ?>

                <option value="<?php echo $value['id']; ?>" <?php echo in_array($value['id'], (array) $mailrelayData['groups_sync']) ? 'selected' : '' ?>><?php echo esc_html($value['name']); ?></option>
            <?php } ?>
        </select> <?php
    }

    public function woocommerce_callback() {
        ?> <select name="woo_commerce" id="woo_commmerce" class="form-select">
                <option value=""><?php printf(__('Sync all users and WooCommmerce customers', 'mailrelay')) ?></option>
                <option value="only"><?php printf(__('Sync only WooCommerce customers', 'mailrelay')) ?></option>
                <option value="except"><?php printf(__('Sync all users except WooCommerce customers', 'mailrelay')) ?></option>
        </select> <?php
    }

    public function manual_groups_callback() {
        global $mailrelayData;
        if (!empty($mailrelayData['host']) && !empty($mailrelayData['api_key'])) {
            $groups = mailrelay_get_groups();
        }
        ?> <select multiple name="group[]" id="mailrelay_group" class="form-select">
            <?php foreach($groups as $value) { ?>
                <option value="<?php echo $value['id']; ?>" <?php echo in_array($value['id'], (array) $mailrelayData['groups_sync']) ? 'selected' : '' ?>><?php echo esc_html($value['name']); ?></option>
            <?php } ?>
        </select> <?php
    }

}

function mailrelay_ping() {
        
    global $mailrelayData;
    global $testPing;

    $url = 'https://' . $mailrelayData['host'] . '.ipzmarketing.com/api/v1/ping';
    $args = array(
        'method'            => 'GET',
        'timeout'           => 30,
        'redirection'       => 10,
        'httpversion'       => '1.1',
        'returntransfer'    => true,
        'sslverify'         => false,
        'headers'           => array(
            'x-auth-token'      => $mailrelayData['api_key'],
            'content-type'      => 'application/json'),
    );
    //Get the API response ping
    $response = wp_remote_request( $url, $args);
    //Get the response ping code
    $testPing = wp_remote_retrieve_response_code($response);
    
    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
    } else {
        if(isset($testPing)) {
            return $testPing;
        }
        else {
            return NULL;
        }  
    }  
}
add_action('ping', 'mailrelay_ping');
do_action('ping');

add_action('mailrelay_counter', 'mailrelay_count');

function mailrelay_sync_user($user, $groups) {

    global $mailrelayData;

    $url = 'https://' . $mailrelayData['host'] . '.ipzmarketing.com/api/v1/subscribers/sync';
    $full_name = $user->first_name . ' ' . $user->last_name;
    $group_ids = '['. implode(', ', $groups) .']';
    $data = array(
        'email'                 => $user->user_email,
        'name'                  => $full_name,
        'replace_groups'        => false,
        'restore_if_deleted'    => false,
        'status'                => 'active',
        'group_ids'            => $group_ids
    );
    $data = json_encode($data);
    //Remove de "" from the Groups ids
    $data = str_replace('"[','[', (string) $data);
    $data = str_replace(']"',']', (string) $data);
    $args = array(
        'method'            => 'POST',
        'timeout'           => 60,
        'redirection'       => 10,
        'httpversion'       => '1.1',
        'post_data'         => 'body',
        'body'              => $data,
        'headers'           => array(
            'x-auth-token'      => $mailrelayData['api_key'],
            'content-type'      => 'application/json'),
            'Expect'            => '',
    );
    //Post the API data
    $response = wp_remote_post( $url, $args );
    //Retrieve the headers response code
    $code = wp_remote_retrieve_response_code($response);
    
    if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            sprintf(_e('Something went wrong: %s', 'mailrelay'), $error_message);
    } else {
        if ($code == "200") {
            return array(
               'status' => 'updated'
            );
        } 
        elseif ($code == "201") {
            return array(
                'status' => 'created'
            );
        } 
        else {
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

    function mailrelay_woo_commmerce_installed() {
        return in_array('woocommerce/woocommerce.php', apply_filters('  ', get_option('active_plugins')));
    }

    function mailrelay_get_groups() {
        
        global $mailrelayData;

        $url = 'https://'. $mailrelayData['host'].'.ipzmarketing.com/api/v1/groups?page=1';
        
        $args = array(
            'method'            => 'GET',
            'timeout'           => 30,
            'redirection'       => 10,
            'httpversion'       => '1.1',
            'returntransfer'    => true,
            'sslverify'         => false,
            'headers'           => array(
                'x-auth-token'      => $mailrelayData['api_key']),
        );

        $response = wp_remote_get( $url, $args);
        $code = wp_remote_retrieve_response_code($response);
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            sprintf(_e('Something went wrong: %s', 'mailrelay'), $error_message);
        } else {
            if ($code == '200') {
                $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
                return $response_body;
            } else {
                sprintf(_e('Something went wrong: %s', 'mailrelay'), $code);
            }
        }
    }


    if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'mailrelay_sync_users_group')) {
            global $message;

            if ($_REQUEST['woo_commerce'] == 'only') {
                $users = get_users('role=customer');
            } elseif ($_REQUEST['woo_commerce'] == 'except') {
                $roles = wp_roles()->roles;
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
               
                if ($return['status'] == 'created') {
                    $added++;
                } elseif ($return['status'] == 'updated') {
                    $updated++;
                } elseif ($return['status'] == 'failed') {
                    $failed++;
                } else {
                    throw new Exception('Invalid return status.');
                }
            }
        

            $message  = '<div class="updated"><ul>';
            $message .= '<li>'. sprintf(  _n('%s was synced', '%s were synced', $added, 'mailrelay'), $added ) .'</li>';
            $message .= '<li>'. sprintf( _n('%s was updated', '%s were updated', $updated, 'mailrelay'), $updated ) .'</li>';
            $message .= '<li>'. sprintf(  _n('%s has failed', '%s has failed', $failed, 'mailrelay'), $failed ) .'</li>';
            $message .= '</ul></div>';
        }
 
    if (isset($_POST['action']) && ($_POST['action'] == 'mailrelay_save_connection_settings')) {

        $mailrelayData['host'] = $_POST['mailrelay_host'];
        if (strpos($mailrelayData['host'], 'http://') === 0 || strpos($mailrelayData['host'], 'https://') === 0 ) {
            $mailrelayData['host'] = parse_url($mailrelayData['host'], PHP_URL_HOST);
        }
        if (strpos($mailrelayData['host'], '.ipzmarketing.com') != false ) {
            $mailrelayData['host'] = str_replace('.ipzmarketing.com', '', $mailrelayData['host']);
        }
        $mailrelayData['api_key'] = $_POST['mailrelay_api_key'];
        $mailrelayData['mailrelay_auto_sync'] = $_POST['mailrelay_auto_sync'];
        $mailrelayData['mailrelay_auto_sync_groups'] = $_POST['mailrelay_auto_sync_groups'];

        $ping = mailrelay_ping();
        global $message;
        global $testPing;

        if(isset($ping)) {
            if ($ping == 401) {
                $message = '<div class="error"><p>'. sprintf(__('Invalid API KEY', 'mailrelay')) .'</p></div>';
            } elseif($ping == 404) {
                $message = '<div class="error"><p>'. sprintf(__('Invalid Account', 'mailrelay')) .'</p></div>';
            }
            elseif($ping == 204) {

                //Form data sent
                
                update_option('mailrelay_host', trim($mailrelayData['host']));
                update_option('mailrelay_api_key', trim($mailrelayData['api_key']));
                update_option('mailrelay_auto_sync',$mailrelayData['mailrelay_auto_sync']);
                update_option('mailrelay_auto_sync_groups', $mailrelayData['mailrelay_auto_sync_groups']);
                $message = '<div class="updated"><p>'. sprintf(__('Data saved successfully', 'mailrelay')) .'</p></div>';
            }
        }
        else {
            $message = '<div class="error"><p>'. sprintf(__('Account or API KEY missing', 'mailrelay')) .'</p></div>';
        }


    }
    $MailrelayPage = new MailrelayPage();
}
