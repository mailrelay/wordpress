<?php  // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Mailrelay
 * Plugin URI: http://mailrelay.com
 * Description: Syncronize your WordPress users with Mailrelay
 * Version: 2.0
 * Author: Consultor-PC
 * Text Domain: Mailrelay
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

define( 'MAILRELAY_PLUGIN_VERSION', '2.0' );


function mailrelay_style_css() {
	wp_enqueue_style( 'mailrelay-admin-css', ( plugin_dir_url( __FILE__ ) . '/css/style.css' ), array(), MAILRELAY_PLUGIN_VERSION );
}
add_action( 'admin_enqueue_scripts', 'style_css' );

function mailrelay_init() {
	load_plugin_textdomain( 'mailrelay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'mailrelay_init' );

function mailrelay_global() {
	global $mailrelay_data;
	$mailrelay_data = array(
		'host'        => get_option( 'mailrelay_host' ),
		'api_key'     => get_option( 'mailrelay_api_key' ),
		'auto_sync'   => get_option( 'mailrelay_auto_sync' ),
		'groups_sync' => get_option( 'mailrelay_auto_sync_groups' ),
	);

}
add_action( 'add_global', 'mailrelay_global', 10 );
do_action( 'add_global' );

class MailrelayPage {

	private $mailrelay_page_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'mailrelay_page_add_plugin_page' ) );
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
			plugins_url( 'mailrelay/mailrelay.png' )
		);

	}

	public function mailrelay_page_create_admin_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $test_ping;
		global $message;
		global $mailrelay_data;

		if ( 204 === $test_ping ) {
			$default_tab   = 'Settings';
			$authenticated = true;
		} else {
			$default_tab  = 'Authentication';
			$disconnected = true;
		}

		$tab = isset( $_GET['tab'] ) ? wp_unslash( $_GET['tab'] ) : $default_tab; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		?>

		<div class="wrap">

			<h1>Mailrelay</h1>

			<?php
			if ( ! empty( $message ) ) {
				_e( $message, 'mailrelay' ); // phpcs:ignore WordPress
			}
			?>

			<nav class="nav-tab-wrapper">
				<a href="?page=mailrelay&tab=Authentication" class="nav-tab <?php echo ( $authenticated ? 'hidden' : '' ); ?> <?php echo ( 'Authentication' === $tab ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Authentication', 'mailrelay' ); ?></a>
				<a href="?page=mailrelay&tab=Settings" class="nav-tab <?php echo ( $disconnected ? 'hidden' : '' ); ?> <?php echo ( 'Settings' === $tab ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Settings', 'mailrelay' ); ?></a>
				<a href="?page=mailrelay&tab=Manual" class="nav-tab <?php echo ( $disconnected ? 'hidden' : '' ); ?> <?php echo ( 'Manual' === $tab ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Manual Sync', 'mailrelay' ); ?></a>
			</nav>

			<div class="tab-content">
				<?php
				switch ( $tab ) :
					case 'Manual':
						?>
						<div id="tab-manual">
							<h3><?php esc_html_e( 'Manual Sync', 'mailrelay' ); ?></h3>

							<?php
							settings_errors();
							?>

							<form name="webservices_form" method="post">
								<?php
									settings_fields( 'manual_page_option_group' );
									do_settings_sections( 'manual-page-admin' );
									$atributes   = array( 'onclick' => 'return check_form();' );
									$submit_text = __( 'Sync', 'mailrelay' );
									submit_button( $submit_text, 'primary', 'submit-manual', true, $atributes );
								?>
							</form>
						</div>
						<?php
						break;

					case 'Authentication':
						?>
						<div id="tab-authentication">
							<h3><?php esc_html_e( 'Authentication', 'mailrelay' ); ?></h3>
							<p><?php esc_html_e( 'Login using your account name and API Key.', 'mailrelay' ); ?></p>
							<p><?php esc_html_e( 'For example if your account name is demo.ipzmarketing.com write only "demo".', 'mailrelay' ); ?></p>
							<p><?php esc_html_e( 'Your API Key can be found or generated at your Mailrelay Account -> Settings -> API Access.', 'mailrelay' ); ?>
							<?php settings_errors(); ?>
							<form name="webservices_form" id="form_conection" method="post" action="<?php echo esc_attr( site_url( '/wp-admin/admin.php?page=mailrelay' ) ); ?>">
								<?php
								settings_fields( 'mailrelay_page_option_group' );
								do_settings_sections( 'mailrelay-page-admin' );
								$submit_text = __( 'Save', 'mailrelay' );
								submit_button( $submit_text, 'primary', 'submit-authentication' );
								?>
							</form>
						</div>
						<?php
						break;

					case 'Settings':
						$link = site_url( '/wp-admin/admin.php?page=mailrelay&tab=Authentication' );
						?>

						<div id="tab-settings">
							<h3><?php esc_html_e( 'Settings', 'mailrelay' ); ?></h3>
							<p>
								<?php
								/* translators: %1: host  %2: authentication link */
								echo sprintf( __( 'You are currently logged in as <strong>%1$s.ipzmarketing.com</strong> (<a href="%2$s">Change Account</a>)', 'mailrelay' ), esc_html( $mailrelay_data['host'] ), esc_url( $link ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							</p>
							<?php
							settings_errors();
							?>

							<form name="webservices_form" id="form_conection" method="post" action="">
								<?php
								settings_fields( 'settings_page_option_group' );
								do_settings_sections( 'settings-page-admin' );
								$atributes   = array( 'onclick' => 'return check_form();' );
								$submit_text = __( 'Save', 'mailrelay' );
								submit_button( $submit_text, 'primary', 'submit-settings', true, $atributes );
								?>
							</form>
						</div>
						<?php
						break;
				endswitch;
				?>
			</div>
		</div>
		<script type="text/javascript">
			function check_form() {
				var chk = check();
				if (chk) {
					document.webservices_form.submit();
				} else {
					return false;
				}
			}

			function check() {
				if(jQuery('#mailrelay_group').val() === '' || jQuery('#mailrelay_auto_sync_groups').val() === '') {
					alert("<?php echo wp_json_encode( __( 'Please select at least one Group.', 'mailrelay' ) ); ?>");
					return false;
				}
				return true;
			}
		</script>

		<?php
	}

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
			__( 'Account', 'mailrelay' ), // title
			array( $this, 'account_callback' ), // callback
			'mailrelay-page-admin', // page
			'mailrelay_page_setting_section' // section
		);

		add_settings_field(
			'api_key', // id
			__( 'API Key', 'mailrelay' ), // title
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
			array(
				'class' => 'hidden',
			)
		);

	}


	public function mailrelay_page_sanitize( $input ) {
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
			__( 'Automatically sync new users with Mailrelay', 'mailrelay' ), // title
			array( $this, 'auto_sync_callback' ), // callback
			'settings-page-admin', // page
			'settings_page_setting_section'// section
		);
		$link = 'javascript:window.location.href=window.location.href';
		add_settings_field(
			'groups', // id
			/* translators: %s link */
			sprintf( __( 'Groups that you want to automatically syncronize <br /><a href="%s">(refresh groups)</a>', 'mailrelay' ), $link ), // title
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
			array(
				'class' => 'hidden',
			)
		);

		add_settings_field(
			'account', // id
			'', // title
			array( $this, 'account_settings' ), // callback
			'settings-page-admin', // page
			'settings_page_setting_section', // section
			array(
				'class' => 'hidden',
			)
		);

		add_settings_field(
			'api_key', // id
			'', // title
			array( $this, 'api_key_settings' ), // callback
			'settings-page-admin', // page
			'settings_page_setting_section', // section
			array(
				'class' => 'hidden',
			)
		);
	}

	public function settings_page_section_info() { }

	public function settings_page_sanitize( $input ) {
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
			/* translators: %s link */
			sprintf( __( 'Please select Groups <br /><a href="%s">(refresh groups)</a>', 'mailrelay' ), $link ), // title
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
			array(
				'class' => 'hidden',
			)
		);

		add_settings_field(
			'account', // id
			'', // title
			array( $this, 'account_settings' ), // callback
			'manual-page-admin', // page
			'manual_page_setting_section', // section
			array(
				'class' => 'hidden',
			)
		);

		add_settings_field(
			'api_key', // id
			'', // title
			array( $this, 'api_key_settings' ), // callback
			'manual-page-admin', // page
			'manual_page_setting_section', // section
			array(
				'class' => 'hidden',
			)
		);

		if ( mailrelay_woo_commmerce_installed() ) {
			add_settings_field(
				'woo_commerce', // id
				__( 'WooCommerce options', 'mailrelay' ), // title
				array( $this, 'woocommerce_callback' ), // callback
				'manual-page-admin', // page
				'manual_page_setting_section' // section
			);
		}

	}

	public function manual_page_sanitize( $input ) {
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
		global $mailrelay_data;
		printf(
			'<input class="regular-text"  type="text" name="mailrelay_host" id="host" value="%s"> <div class="ipz">.ipzmarketing.com</div>',
			esc_attr( $mailrelay_data['host'] )
		);
	}

	public function account_settings() {
		global $mailrelay_data;
		printf(
			'<input class="regular-text"  type="hidden" name="mailrelay_host" id="host" value="%s">',
			esc_attr( $mailrelay_data['host'] )
		);
	}

	public function api_key_callback() {
		global $mailrelay_data;
		printf(
			'<input class="regular-text" type="text" name="mailrelay_api_key" id="api_key" value="%s">',
			esc_attr( $mailrelay_data['api_key'] )
		);
	}

	public function api_key_settings() {
		global $mailrelay_data;
		printf(
			'<input class="regular-text" type="hidden" name="mailrelay_api_key" id="api_key" value="%s">',
			esc_attr( $mailrelay_data['api_key'] )
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
		$mailrelay_data['auto_sync'] = get_option( 'mailrelay_auto_sync' );
		?>
			<input type="checkbox" name="mailrelay_auto_sync" id="mailrelay_auto_sync" <?php echo $mailrelay_data['auto_sync'] ? 'checked' : ''; ?>/>
		<?php
	}

	public function groups_callback() {
		global $mailrelay_data;
		if ( ! empty( $mailrelay_data['host'] ) && ! empty( $mailrelay_data['api_key'] ) ) {
			$groups = mailrelay_get_groups();
		}
		$mailrelay_data['groups_sync'] = get_option( 'mailrelay_auto_sync_groups' );

		?>
		<select multiple name="mailrelay_auto_sync_groups[]" id="mailrelay_auto_sync_groups" class="form-select">
			<?php foreach ( $groups as $value ) { ?>

				<option value="<?php echo esc_attr( $value['id'] ); ?>" <?php echo in_array( $value['id'], (array) $mailrelay_data['groups_sync'] ) ? 'selected' : ''; ?>><?php echo esc_html( $value['name'] ); ?></option>
			<?php } ?>
		</select>
		<?php
	}

	public function woocommerce_callback() {
		?>
		<select name="woo_commerce" id="woo_commmerce" class="form-select">
				<option value=""><?php printf( esc_html_e( 'Sync all users and WooCommmerce customers', 'mailrelay' ) ); ?></option>
				<option value="only"><?php printf( esc_html_e( 'Sync only WooCommerce customers', 'mailrelay' ) ); ?></option>
				<option value="except"><?php printf( esc_html_e( 'Sync all users except WooCommerce customers', 'mailrelay' ) ); ?></option>
		</select>
		<?php
	}

	public function manual_groups_callback() {
		global $mailrelay_data;
		if ( ! empty( $mailrelay_data['host'] ) && ! empty( $mailrelay_data['api_key'] ) ) {
			$groups = mailrelay_get_groups();
		}
		?>
		<select multiple name="group[]" id="mailrelay_group" class="form-select">
			<?php foreach ( $groups as $value ) { ?>
				<option value="<?php echo esc_attr( $value['id'] ); ?>" <?php selected( in_array( $value['id'], (array) $mailrelay_data['groups_sync'] ) ); ?>><?php echo esc_html( $value['name'] ); ?></option>
			<?php } ?>
		</select>
		<?php
	}

}

function mailrelay_ping() {
	global $mailrelay_data;
	global $test_ping;

	$url  = 'https://' . $mailrelay_data['host'] . '.ipzmarketing.com/api/v1/ping';
	$args = array(
		'method'         => 'GET',
		'timeout'        => 30,
		'redirection'    => 10,
		'httpversion'    => '1.1',
		'returntransfer' => true,
		'sslverify'      => false,
		'headers'        => array(
			'x-auth-token' => $mailrelay_data['api_key'],
			'content-type' => 'application/json',
		),
	);
	// Get the API response ping
	$response = wp_remote_request( $url, $args );
	// Get the response ping code
	$test_ping = wp_remote_retrieve_response_code( $response );

	if ( is_wp_error( $response ) ) {
		$error_message = $response->get_error_message();
	} else {
		if ( isset( $test_ping ) ) {
			return $test_ping;
		} else {
			return null;
		}
	}
}
add_action( 'ping', 'mailrelay_ping' );
do_action( 'ping' );

add_action( 'mailrelay_counter', 'mailrelay_count' );

function mailrelay_sync_user( $user, $groups ) {

	global $mailrelay_data;

	$url       = 'https://' . $mailrelay_data['host'] . '.ipzmarketing.com/api/v1/subscribers/sync';
	$full_name = $user->first_name . ' ' . $user->last_name;
	$group_ids = '[' . implode( ', ', $groups ) . ']';
	$data      = array(
		'email'              => $user->user_email,
		'name'               => $full_name,
		'replace_groups'     => false,
		'restore_if_deleted' => false,
		'status'             => 'active',
		'group_ids'          => $group_ids,
	);
	$data      = wp_json_encode( $data );
	// Remove de "" from the Groups ids
	$data = str_replace( '"[', '[', (string) $data );
	$data = str_replace( ']"', ']', (string) $data );
	$args = array(
		'method'      => 'POST',
		'timeout'     => 60,
		'redirection' => 10,
		'httpversion' => '1.1',
		'post_data'   => 'body',
		'body'        => $data,
		'headers'     => array(
			'x-auth-token' => $mailrelay_data['api_key'],
			'content-type' => 'application/json',
		),
		'Expect'      => '',
	);
	// Post the API data
	$response = wp_remote_post( $url, $args );
	// Retrieve the headers response code
	$code = wp_remote_retrieve_response_code( $response );

	if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			/* translators: %s error message */
			sprintf( esc_html__( 'Something went wrong: %s', 'mailrelay' ), $error_message );
	} else {
		if ( 200 === $code ) {
			return array(
				'status' => 'updated',
			);
		} elseif ( 201 === $code ) {
			return array(
				'status' => 'created',
			);
		} else {
			return array(
				'status' => 'failed',
			);
		}
	}
}

function mailrelay_new_user_registration( $user_id ) {
	$user = new WP_User( $user_id );

	$groups = get_option( 'mailrelay_auto_sync_groups' );

	if ( ! empty( $groups ) ) {
		try {
			mailrelay_sync_user( $user, $groups );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Ignore if something goes wrong to avoid showing errors to user
		}
	}
}

if ( get_option( 'mailrelay_auto_sync' ) ) {
	add_action( 'user_register', 'mailrelay_new_user_registration' );
}

if ( function_exists( 'is_admin' ) && is_admin() ) {

	function mailrelay_woo_commmerce_installed() {
		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}

	function mailrelay_get_groups() {

		global $mailrelay_data;

		$url = 'https://' . $mailrelay_data['host'] . '.ipzmarketing.com/api/v1/groups?page=1';

		$args = array(
			'method'         => 'GET',
			'timeout'        => 30,
			'redirection'    => 10,
			'httpversion'    => '1.1',
			'returntransfer' => true,
			'sslverify'      => false,
			'headers'        => array(
				'x-auth-token' => $mailrelay_data['api_key'],
			),
		);

		$response = wp_remote_get( $url, $args );
		$code     = wp_remote_retrieve_response_code( $response );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			/* translators: %s error message */
			sprintf( __( 'Something went wrong: %s', 'mailrelay' ), $error_message );
		} else {
			if ( 200 === $code ) {
				$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
				return $response_body;
			} else {
				/* translators: %s error message */
				sprintf( __( 'Something went wrong: %s', 'mailrelay' ), $code );
			}
		}
	}


	if ( isset( $_REQUEST['action'] ) && ( 'mailrelay_sync_users_group' === $_REQUEST['action'] ) ) {
		global $message;

		$woo_commerce_option = ( isset( $_REQUEST['woo_commerce'] ) ) ? sanitize_key( wp_unslash( $_REQUEST['woo_commerce'] ) ) : '';

		if ( 'only' === $woo_commerce_option ) {
			$users = get_users( 'role=customer' );
		} elseif ( 'except' === $woo_commerce_option ) {
			$roles = wp_roles()->roles;
			unset( $roles['customer'] );

			$users = array();
			foreach ( $roles as $wp_role => $values ) {
				$users = array_merge( $users, get_users( 'role=' . $wp_role ) );
			}
		} else {
			$users = get_users();
		}

		$groups  = isset( $_REQUEST['group'] ) ? array_map( 'intval', wp_unslash( $_REQUEST['group'] ) ) : array();
		$added   = 0;
		$updated = 0;
		$failed  = 0;

		foreach ( $users as $user ) {
			$return = mailrelay_sync_user( $user, $groups );

			if ( 'created' === $return['status'] ) {
				$added++;
			} elseif ( 'updated' === $return['status'] ) {
				$updated++;
			} elseif ( 'failed' === $return['status'] ) {
				$failed++;
			} else {
				throw new Exception( 'Invalid return status.' );
			}
		}


		$message = '<div class="updated"><ul>';
		/* translators: %s: number */
		$message .= '<li>' . sprintf( _n( '%s was synced', '%s were synced', $added, 'mailrelay' ), $added ) . '</li>';
		/* translators: %s: number */
		$message .= '<li>' . sprintf( _n( '%s was updated', '%s were updated', $updated, 'mailrelay' ), $updated ) . '</li>';
		/* translators: %s: number */
		$message .= '<li>' . sprintf( _n( '%s has failed', '%s has failed', $failed, 'mailrelay' ), $failed ) . '</li>';
		$message .= '</ul></div>';
	}

	if ( isset( $_POST['action'] ) && ( 'mailrelay_save_connection_settings' === $_POST['action'] ) ) {

		$mailrelay_data['host'] = isset( $_POST['mailrelay_host'] ) ? wp_unslash( $_POST['mailrelay_host'] ) : '';
		if ( strpos( $mailrelay_data['host'], 'http://' ) === 0 || strpos( $mailrelay_data['host'], 'https://' ) === 0 ) {
			$mailrelay_data['host'] = wp_parse_url( $mailrelay_data['host'], PHP_URL_HOST );
		}
		if ( strpos( $mailrelay_data['host'], '.ipzmarketing.com' ) !== false ) {
			$mailrelay_data['host'] = str_replace( '.ipzmarketing.com', '', $mailrelay_data['host'] );
		}
		$mailrelay_data['api_key']                    = ( isset( $_POST['mailrelay_api_key'] ) ) ? wp_unslash( $_POST['mailrelay_api_key'] ) : '';
		$mailrelay_data['mailrelay_auto_sync']        = ( isset( $_POST['mailrelay_auto_sync'] ) ) ? wp_unslash( $_POST['mailrelay_auto_sync'] ) : '';
		$mailrelay_data['mailrelay_auto_sync_groups'] = ( isset( $_POST['mailrelay_auto_sync_groups'] ) ) ? wp_unslash( $_POST['mailrelay_auto_sync_groups'] ) : array();

		$ping = mailrelay_ping();
		global $message;
		global $test_ping;

		if ( isset( $ping ) ) {
			if ( 401 === $ping ) {
				$message = '<div class="error"><p>' . __( 'Invalid API KEY', 'mailrelay' ) . '</p></div>';
			} elseif ( 404 === $ping ) {
				$message = '<div class="error"><p>' . __( 'Invalid Account', 'mailrelay' ) . '</p></div>';
			} elseif ( 204 === $ping ) {

				// Form data sent

				update_option( 'mailrelay_host', trim( $mailrelay_data['host'] ) );
				update_option( 'mailrelay_api_key', trim( $mailrelay_data['api_key'] ) );
				update_option( 'mailrelay_auto_sync', $mailrelay_data['mailrelay_auto_sync'] );
				update_option( 'mailrelay_auto_sync_groups', $mailrelay_data['mailrelay_auto_sync_groups'] );
				$message = '<div class="updated"><p>' . __( 'Data saved successfully', 'mailrelay' ) . '</p></div>';
			}
		} else {
			$message = '<div class="error"><p>' . __( 'Account or API KEY missing', 'mailrelay' ) . '</p></div>';
		}
	}
	$mailrelay_page = new MailrelayPage();
}
