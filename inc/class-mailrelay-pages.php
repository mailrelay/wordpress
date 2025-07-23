<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

class MailrelayPages {
	protected $mailrelay_data_memo;
	protected $is_api_key_setup_and_valid_memo;

	public function setup_hooks() {
		add_action( 'admin_menu', array( $this, 'add_main_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	public function add_main_menu_page() {
		add_menu_page(
			'Mailrelay', // page_title
			'Mailrelay', // menu_title
			'manage_options', // capability
			'mailrelay', // menu_slug
			array( $this, 'render_admin_page' ), // function
			plugins_url( 'mailrelay/mailrelay.png' )
		);
	}

	public function admin_enqueue_scripts( $hook ) {
		if ( 'toplevel_page_mailrelay' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'mailrelay-admin-css', ( plugins_url( 'mailrelay/css/style.css' ) ), array(), MAILRELAY_PLUGIN_VERSION );
		wp_enqueue_script( 'mailrelay-admin-js', ( plugins_url( 'mailrelay/js/admin.js' ) ), array(), MAILRELAY_PLUGIN_VERSION, true );
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		load_plugin_textdomain( 'mailrelay', false, 'mailrelay/languages/' );

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		if ( isset($_SERVER['REQUEST_METHOD']) && 'POST' === $_SERVER['REQUEST_METHOD'] && ! wp_verify_nonce(sanitize_key($_POST['_mailrelay_nonce']), '_mailrelay_nonce') ) {
			wp_die(
				'Invalid Nonce',
				'Invalid Nonce',
				array(
					'back_link' => true,
				)
			);
		}

		if ( isset($_POST['action']) ) {
			if ( 'mailrelay_save_authentication_settings' === $_POST['action'] ) {
				$response = $this->process_save_connection_settings();
			} elseif ( 'mailrelay_save_settings' === $_POST['action'] ) {
				$response = $this->process_save_settings();
			} elseif ( 'mailrelay_manual_sync' === $_POST['action'] ) {
				$response = $this->process_manual_sync();
			} elseif ( 'mailrelay_save_woocommerce_sync_settings' === $_POST['action'] ) {
				$response = $this->process_save_woocommerce_sync_settings();
			} elseif ( 'mailrelay_manual_woocommerce_sync' === $_POST['action'] ) {
				$response = $this->process_manual_woocommerce_sync();
			} elseif ( 'mailrelay_connect_woocommerce_store' === $_POST['action'] ) {
				$response = $this->process_connect_woocommerce_store();
			}

			if ( isset( $response ) ) {
				if ( $response['valid'] ) {
					$success_message = $response['message'];
				} else {
					$error_message = $response['message'];
				}
			}
		}

		if ( $this->is_api_key_setup_and_valid() ) {
			$default_tab   = 'Settings';
			$authenticated = true;
			$disconnected  = false;
		} else {
			$default_tab   = 'Authentication';
			$disconnected  = true;
			$authenticated = false;
		}

		$display_woocommerce_tab = ! $disconnected && mailrelay_woo_commerce_installed();

		$tab = isset( $_GET['tab'] ) ? wp_unslash( $_GET['tab'] ) : $default_tab; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		?>

		<div class="wrap">

			<h1>Mailrelay</h1>

			<?php
			if ( ! empty( $error_message ) ) {
				?>
				<div class="error"><?php echo $error_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php
			} elseif ( ! empty( $success_message ) ) {
				?>
				<div class="updated"><?php echo $success_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php
			}
			?>

			<nav class="nav-tab-wrapper">
				<a href="?page=mailrelay&tab=Authentication" class="nav-tab <?php echo ( $authenticated && 'Authentication' !== $tab ) ? 'hidden' : ''; ?> <?php echo ( 'Authentication' === $tab ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Authentication', 'mailrelay' ); ?></a>
				<a href="?page=mailrelay&tab=Settings" class="nav-tab <?php echo ( $disconnected ? 'hidden' : '' ); ?> <?php echo ( 'Settings' === $tab ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Settings', 'mailrelay' ); ?></a>
				<a href="?page=mailrelay&tab=Manual" class="nav-tab <?php echo ( $disconnected ? 'hidden' : '' ); ?> <?php echo ( 'Manual' === $tab ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Manual Sync', 'mailrelay' ); ?></a>
				<?php if ( $display_woocommerce_tab ) : ?>
					<a href="?page=mailrelay&tab=WooCommerce" class="nav-tab <?php echo ( 'WooCommerce' === $tab ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'WooCommerce Sync', 'mailrelay' ); ?></a>
				<?php endif; ?>
			</nav>

			<div class="tab-content">
				<?php
				switch ( $tab ) :
					case 'Manual':
						$this->setup_manual_sync_fields();
						include_once __DIR__ . '/partials/tab-manual.php';
						break;

					case 'Authentication':
						$this->setup_authentication_page_fields();
						include_once __DIR__ . '/partials/tab-authentication.php';
						break;

					case 'Settings':
						$this->setup_settings_page_fields();
						$link = admin_url( '/admin.php?page=mailrelay&tab=Authentication' );
						include_once __DIR__ . '/partials/tab-settings.php';
						break;

					case 'WooCommerce':
						$this->setup_woocommerce_page_fields();
						include_once __DIR__ . '/partials/tab-woocommerce.php';
						break;
				endswitch;
				?>
			</div>
		</div>
		<?php
	}

	public function setup_authentication_page_fields() {
		add_settings_section(
			'mailrelay_page_setting_section', // id
			'', // title
			array( $this, 'mailrelay_page_section_info' ), // callback
			'mailrelay-authentication-page' // page
		);

		add_settings_field(
			'account', // id
			__( 'Account', 'mailrelay' ), // title
			array( $this, 'account_callback' ), // callback
			'mailrelay-authentication-page', // page
			'mailrelay_page_setting_section', // section
			array(
				'class' => 'mailrelay-account-field',
			)
		);

		add_settings_field(
			'api_key', // id
			__( 'API Key', 'mailrelay' ), // title
			array( $this, 'api_key_callback' ), // callback
			'mailrelay-authentication-page', // page
			'mailrelay_page_setting_section' // section
		);

		add_settings_field(
			'action', // id
			'', // title
			array( $this, 'action_authentication_page_callback' ), // callback
			'mailrelay-authentication-page', // page
			'mailrelay_page_setting_section', // section
			array(
				'class' => 'hidden',
			)
		);
	}

	public function mailrelay_page_section_info() { }

	public function setup_settings_page_fields() {
		add_settings_section(
			'settings_page_setting_section', // id
			'', // title
			array( $this, 'settings_page_section_info' ), // callback
			'mailrelay-settings-page' // page
		);

		add_settings_field(
			'auto_sync', // id
			__( 'Automatically sync new users with Mailrelay', 'mailrelay' ), // title
			array( $this, 'auto_sync_callback' ), // callback
			'mailrelay-settings-page', // page
			'settings_page_setting_section'// section
		);

		$link = 'javascript:window.location.href=window.location.href';
		add_settings_field(
			'groups', // id
			/* translators: %s link */
			sprintf( __( 'Groups that you want to automatically syncronize <br /><a href="%s">(refresh groups)</a>', 'mailrelay' ), $link ), // title
			array( $this, 'groups_callback' ), // callback
			'mailrelay-settings-page', // page
			'settings_page_setting_section', // section
			array(
				'class' => 'mailrelay-auto-sync-groups-field',
			)
		);

		add_settings_field(
			'action', // id
			null, // title
			array( $this, 'action_settings_page_callback' ), // callback
			'mailrelay-settings-page', // page
			'settings_page_setting_section', // section
			array(
				'class' => 'hidden',
			)
		);
	}

	public function settings_page_section_info() { }

	public function setup_manual_sync_fields() {
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
			array( $this, 'action_manual_sync_callback' ), // callback
			'manual-page-admin', // page
			'manual_page_setting_section', // section
			array(
				'class' => 'hidden',
			)
		);

		if ( mailrelay_woo_commerce_installed() ) {
			add_settings_field(
				'woo_commerce', // id
				__( 'WooCommerce options', 'mailrelay' ), // title
				array( $this, 'woocommerce_callback' ), // callback
				'manual-page-admin', // page
				'manual_page_setting_section' // section
			);
		}
	}

	public function manual_page_section_info() { }

	public function account_callback() {
		$data = $this->mailrelay_data();

		printf(
			'<input class="regular-text" type="text" name="mailrelay_host" id="host" required="required" value="%s"> <div class="input-addon">.%s</div>',
			esc_attr( $data ? $data['host'] : '' ),
			esc_html( MAILRELAY_BASE_DOMAIN )
		);
	}

	public function api_key_callback() {
		$data = $this->mailrelay_data();

		printf(
			'<input class="regular-text" type="text" name="mailrelay_api_key" id="api_key" required="required" value="%s">',
			esc_attr( $data ? $data['api_key'] : '' )
		);
	}

	public function action_authentication_page_callback() {
		printf(
			'<input type="hidden" name="action" value="mailrelay_save_authentication_settings" />'
		);
	}

	public function action_settings_page_callback() {
		printf(
			'<input type="hidden" name="action" value="mailrelay_save_settings" />'
		);
	}

	public function action_manual_sync_callback() {
		printf(
			'<input type="hidden" name="action" value="mailrelay_manual_sync" />'
		);
	}

	public function auto_sync_callback() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification happens at render_admin_page.
		$value = isset( $_POST['mailrelay_auto_sync'] ) ? filter_var( wp_unslash( $_POST['mailrelay_auto_sync'] ), FILTER_SANITIZE_NUMBER_INT ) : get_option( 'mailrelay_auto_sync' );
		?>
		<input type="checkbox" name="mailrelay_auto_sync" id="mailrelay_auto_sync" value="1" <?php checked( 1, $value ); ?>/>
		<?php
	}

	public function groups_callback() {
		$groups = mailrelay_get_groups();
		$auto_groups = get_option( 'mailrelay_auto_sync_groups' );
		$auto_groups = $auto_groups ? $auto_groups : array();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification happens at render_admin_page.
		$mailrelay_auto_sync_groups = isset( $_POST['mailrelay_auto_sync_groups'] ) ? (array) wp_unslash( $_POST['mailrelay_auto_sync_groups'] ) : $auto_groups;

		?>
		<select multiple name="mailrelay_auto_sync_groups[]" id="mailrelay_auto_sync_groups" class="form-select">
			<?php foreach ( $groups as $value ) { ?>
				<option value="<?php echo esc_attr( $value['id'] ); ?>" <?php echo in_array( $value['id'], $mailrelay_auto_sync_groups ) ? 'selected' : ''; // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict ?>><?php echo esc_html( $value['name'] ); ?></option>
			<?php } ?>
		</select>
		<?php
	}

	public function woocommerce_callback() {
		?>
		<select name="woo_commerce" id="woo_commmerce" class="form-select">
			<option value=""><?php esc_html_e( 'Sync all users and WooCommmerce customers', 'mailrelay' ); ?></option>
			<option value="only"><?php esc_html_e( 'Sync only WooCommerce customers', 'mailrelay' ); ?></option>
			<option value="except"><?php esc_html_e( 'Sync all users except WooCommerce customers', 'mailrelay' ); ?></option>
		</select>
		<?php
	}

	public function manual_groups_callback() {
		$groups = mailrelay_get_groups();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification happens at render_admin_page.
		$selected_groups = isset( $_POST['group'] ) ? (array) wp_unslash( $_POST['group'] ) : array();

		?>
		<select multiple name="group[]" id="mailrelay_group" class="form-select">
			<?php foreach ( $groups as $value ) { ?>
				<option value="<?php echo esc_attr( $value['id'] ); ?>" <?php selected( in_array( $value['id'], $selected_groups ) );  // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict ?>><?php echo esc_html( $value['name'] ); ?></option>
			<?php } ?>
		</select>
		<?php
	}

	protected function is_api_key_setup_and_valid() {
		if ( ! is_null( $this->is_api_key_setup_and_valid_memo ) ) {
			return $this->is_api_key_setup_and_valid_memo;
		}

		$data = $this->mailrelay_data();
		if ( $data ) {
			$this->is_api_key_setup_and_valid_memo = 204 === mailrelay_ping( $data );
		} else {
			$this->is_api_key_setup_and_valid_memo = false;
		}

		return $this->is_api_key_setup_and_valid_memo;
	}

	protected function mailrelay_data() {
		if ( ! is_null( $this->mailrelay_data_memo ) ) {
			return $this->mailrelay_data_memo;
		}

		$this->mailrelay_data_memo = mailrelay_data();

		return $this->mailrelay_data_memo;
	}

	public function process_save_connection_settings() {
		$mailrelay_data = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification happens at render_admin_page.
		$mailrelay_data['host'] = isset( $_POST['mailrelay_host'] ) ? sanitize_text_field( wp_unslash( $_POST['mailrelay_host'] ) ) : '';
		if ( strpos( $mailrelay_data['host'], 'http://' ) === 0 || strpos( $mailrelay_data['host'], 'https://' ) === 0 ) {
			$mailrelay_data['host'] = wp_parse_url( $mailrelay_data['host'], PHP_URL_HOST );
		}
		if ( strpos( $mailrelay_data['host'], '.' . MAILRELAY_BASE_DOMAIN ) !== false ) {
			$mailrelay_data['host'] = str_replace( '.' . MAILRELAY_BASE_DOMAIN, '', $mailrelay_data['host'] );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification happens at render_admin_page.
		$mailrelay_data['api_key'] = ( isset( $_POST['mailrelay_api_key'] ) ) ? sanitize_text_field( wp_unslash( $_POST['mailrelay_api_key'] ) ) : '';

		$ping_response_code = mailrelay_ping( $mailrelay_data );

		// Memoize data
		$this->mailrelay_data_memo = $mailrelay_data;

		if ( 204 === $ping_response_code ) {
			$this->is_api_key_setup_and_valid_memo = true;

			// Save valid response
			update_option( 'mailrelay_host', trim( $mailrelay_data['host'] ) );
			update_option( 'mailrelay_api_key', trim( $mailrelay_data['api_key'] ) );

			return array(
				'valid'   => true,
				'message' => esc_html__( 'Data saved successfully', 'mailrelay' ),
			);
		}

		$this->is_api_key_setup_and_valid_memo = false;

		if ( 401 === $ping_response_code ) {
			return array(
				'valid'   => false,
				'message' => esc_html__( 'Invalid API KEY', 'mailrelay' ),
			);
		} elseif ( 404 === $ping_response_code ) {
			return array(
				'valid'   => false,
				'message' => esc_html__( 'Invalid Account', 'mailrelay' ),
			);
		} else {
			return array(
				'valid'   => false,
				'message' => esc_html__( 'Account or API KEY missing', 'mailrelay' ),
			);
		}
	}

	public function process_save_settings() {
		$mailrelay_data = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification happens at render_admin_page.
		$mailrelay_data['mailrelay_auto_sync'] = ( isset( $_POST['mailrelay_auto_sync'] ) ) ? filter_var( wp_unslash( $_POST['mailrelay_auto_sync'] ), FILTER_SANITIZE_NUMBER_INT ) : false;

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification happens at render_admin_page.
		$mailrelay_data['mailrelay_auto_sync_groups'] = ( isset( $_POST['mailrelay_auto_sync_groups'] ) ) ? wp_unslash( $_POST['mailrelay_auto_sync_groups'] ) : array();

		update_option( 'mailrelay_auto_sync', $mailrelay_data['mailrelay_auto_sync'] );
		update_option( 'mailrelay_auto_sync_groups', $mailrelay_data['mailrelay_auto_sync_groups'] );

		return array(
			'valid'   => true,
			'message' => __( 'Data saved successfully', 'mailrelay' ),
		);
	}

	public function process_manual_sync() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: Nonce verification happens at render_admin_page.
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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: Nonce verification happens at render_admin_page.
		$groups  = isset( $_REQUEST['group'] ) ? array_map( 'intval', wp_unslash( $_REQUEST['group'] ) ) : array();
		$added   = 0;
		$updated = 0;
		$failed  = 0;

		foreach ( $users as $user ) {
			$return = mailrelay_sync_user( $user, $groups, $this->mailrelay_data() );

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

		$message = '<ul>';
		/* translators: %s: number */
		$message .= '<li>' . sprintf( _n( '%s was synced', '%s were synced', $added, 'mailrelay' ), $added ) . '</li>';
		/* translators: %s: number */
		$message .= '<li>' . sprintf( _n( '%s was updated', '%s were updated', $updated, 'mailrelay' ), $updated ) . '</li>';
		/* translators: %s: number */
		$message .= '<li>' . sprintf( _n( '%s has failed', '%s has failed', $failed, 'mailrelay' ), $failed ) . '</li>';
		$message .= '</ul>';

		return array(
			'valid'   => true,
			'message' => $message,
		);
	}

	public function setup_woocommerce_page_fields() {
		add_settings_section(
			'mailrelay_woocommerce_sync_section', // id
			'', // title
			function () { }, // callback
			'mailrelay-woocommerce-sync-page' // page
		);

		$store_id = MailrelayWoocommerce::instance()->get_store_id();

		if ( $store_id ) {
			add_settings_field(
				'woocommerce_auto_sync', // id
				__( 'Automatically sync WooCommerce with Mailrelay', 'mailrelay' ), // title
				array( $this, 'woocommerce_auto_sync_callback' ), // callback
				'mailrelay-woocommerce-sync-page', // page
				'mailrelay_woocommerce_sync_section'// section
			);

			add_settings_field(
				'action', // id
				'', // title
				array( $this, 'action_woocommerce_settings_callback' ), // callback
				'mailrelay-woocommerce-sync-page', // page
				'mailrelay_woocommerce_sync_section', // section
				array(
					'class' => 'hidden',
				)
			);
		}
	}

	public function action_woocommerce_settings_callback() {
		printf(
			'<input type="hidden" name="action" value="mailrelay_save_woocommerce_sync_settings" />'
		);
	}

	public function woocommerce_auto_sync_callback() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification happens at render_admin_page.
		$value = isset( $_POST['mailrelay_woocommerce_auto_sync'] ) ? filter_var( wp_unslash( $_POST['mailrelay_woocommerce_auto_sync'] ), FILTER_SANITIZE_NUMBER_INT ) : get_option( 'mailrelay_woocommerce_auto_sync' );
		?>
		<input type="checkbox" name="mailrelay_woocommerce_auto_sync" id="mailrelay_woocommerce_auto_sync" value="1" <?php checked( 1, $value ); ?>/>
		<p class="description"><?php esc_html_e( 'When enabled, products, orders and carts will be automatically synced to Mailrelay.', 'mailrelay' ); ?></p>
		<?php
	}

	public function process_save_woocommerce_sync_settings() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce verification happens at render_admin_page.
		$auto_sync = isset( $_POST['mailrelay_woocommerce_auto_sync'] ) ? filter_var( wp_unslash( $_POST['mailrelay_woocommerce_auto_sync'] ), FILTER_SANITIZE_NUMBER_INT ) : 0;
		update_option( 'mailrelay_woocommerce_auto_sync', $auto_sync );

		if ( $auto_sync ) {
			MailrelayWoocommerce::instance()->enqueue_sync_all_products();
		}

		return array(
			'valid'   => true,
			'message' => __( 'WooCommerce Sync settings saved successfully.', 'mailrelay' ),
		);
	}

	public function process_connect_woocommerce_store() {
		// First, try to find a store with the same URL as the current site
		$site_url = home_url();

		$query_parameters = array(
			'q' => array(
				'url_eq' => $site_url,
			),
		);

		$response = mailrelay_api_request( 'GET', 'ecommerce/stores?' . http_build_query( $query_parameters ) );

		if ( $response['wp_error'] || 200 !== $response['code'] ) {
			return array(
				'valid'   => false,
				'message' => __( 'Failed to connect to Mailrelay API. Please try again.', 'mailrelay' ),
			);
		}

		$existing_store = $response['body'][0];

		if ( $existing_store ) {
			// Store found, save the ID
			update_option( 'mailrelay_woocommerce_store_id', $existing_store['id'] );
			return array(
				'valid'   => true,
				'message' => __( 'Successfully connected to existing store in Mailrelay.', 'mailrelay' ),
			);
		} else {
			// Create a new store
			$store_data = array(
				'name'     => get_bloginfo( 'name' ) . ' - WooCommerce',
				'url'      => $site_url,
				'currency' => get_woocommerce_currency(),
			);

			$response = mailrelay_api_request(
				'POST',
				'ecommerce/stores',
				array(
					'body'    => wp_json_encode( $store_data ),
					'headers' => array( 'content-type' => 'application/json' ),
				)
			);

			if ( $response['wp_error'] || ( 200 !== $response['code'] && 201 !== $response['code'] ) ) {
				return array(
					'valid'   => false,
					'message' => __( 'Failed to create store in Mailrelay. Please try again.', 'mailrelay' ),
				);
			}

			update_option( 'mailrelay_woocommerce_store_id', $response['body']['id'] );
			update_option( 'mailrelay_woocommerce_auto_sync', 1 );

			MailrelayWoocommerce::instance()->enqueue_sync_all_products();

			return array(
				'valid'   => true,
				'message' => __( 'Successfully created and connected to a new store in Mailrelay.', 'mailrelay' ),
			);
		}
	}

	public function process_manual_woocommerce_sync() {
		MailrelayWoocommerce::instance()->enqueue_sync_all_products();

		return array(
			'valid'   => true,
			'message' => __( 'Products are being synced to Mailrelay. It may take a few minutes to complete.', 'mailrelay' ),
		);
	}
}
