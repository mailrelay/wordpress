<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

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
				<a href="?page=mailrelay&tab=Authentication" class="nav-tab <?php echo ( $authenticated ? 'hidden' : '' ); ?> <?php echo ( 'Authentication' === $tab ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Authentication', 'mailrelay' ); ?></a>
				<a href="?page=mailrelay&tab=Settings" class="nav-tab <?php echo ( $disconnected ? 'hidden' : '' ); ?> <?php echo ( 'Settings' === $tab ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Settings', 'mailrelay' ); ?></a>
				<a href="?page=mailrelay&tab=Manual" class="nav-tab <?php echo ( $disconnected ? 'hidden' : '' ); ?> <?php echo ( 'Manual' === $tab ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Manual Sync', 'mailrelay' ); ?></a>
			</nav>

			<div class="tab-content">
				<?php
				switch ( $tab ) :
					case 'Manual':
						$this->setup_manual_sync_fields();

						?>
						<div id="tab-manual">
							<h3><?php esc_html_e( 'Manual Sync', 'mailrelay' ); ?></h3>

							<?php
							settings_errors();
							?>

							<form method="post">
								<?php
								wp_nonce_field('_mailrelay_nonce', '_mailrelay_nonce');
								do_settings_sections( 'manual-page-admin' );
								$attributes   = array( 'onclick' => 'return check_form();' );
								$submit_text = __( 'Sync', 'mailrelay' );
								submit_button( $submit_text, 'primary', 'submit-manual', true, $attributes );
								?>
							</form>
						</div>
						<?php
						break;

					case 'Authentication':
						$this->setup_authentication_page_fields();

						?>
						<div id="tab-authentication">
							<h3><?php esc_html_e( 'Authentication', 'mailrelay' ); ?></h3>
							<p><?php esc_html_e( 'Login using your account name and API Key.', 'mailrelay' ); ?></p>
							<p><?php esc_html_e( 'For example if your account name is demo.ipzmarketing.com write only "demo".', 'mailrelay' ); ?></p>
							<p><?php esc_html_e( 'Your API Key can be found or generated at your Mailrelay Account -> Settings -> API Access.', 'mailrelay' ); ?>
							<?php settings_errors(); ?>
							<form method="post">
								<?php
								wp_nonce_field('_mailrelay_nonce', '_mailrelay_nonce');
								do_settings_sections( 'mailrelay-authentication-page' );
								$submit_text = __( 'Save', 'mailrelay' );
								submit_button( $submit_text, 'primary', 'submit-authentication' );
								?>
							</form>
						</div>
						<?php
						break;

					case 'Settings':
						$this->setup_settings_page_fields();
						$link = admin_url( '/admin.php?page=mailrelay&tab=Authentication' );
						?>

						<div id="tab-settings">
							<h3><?php esc_html_e( 'Settings', 'mailrelay' ); ?></h3>
							<p>
								<?php
								/* translators: %1: host  %2: authentication link */
								printf( __( 'You are currently logged in as <strong>%1$s.ipzmarketing.com</strong> (<a href="%2$s">Change Account</a>)', 'mailrelay' ), esc_html( $this->mailrelay_data()['host'] ), esc_url( $link ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							</p>
							<?php
							settings_errors();
							?>

							<form method="post">
								<?php
								wp_nonce_field('_mailrelay_nonce', '_mailrelay_nonce');
								do_settings_sections( 'mailrelay-settings-page' );
								$attributes  = array( 'onclick' => 'return check_form();' );
								$submit_text = __( 'Save', 'mailrelay' );
								submit_button( $submit_text, 'primary', 'submit-settings', true, $attributes );
								?>
							</form>
						</div>
						<?php
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
			'mailrelay_page_setting_section' // section
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
			'settings_page_setting_section' // section
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
			'<input class="regular-text" type="text" name="mailrelay_host" id="host" required="required" value="%s"> <div class="ipz">.ipzmarketing.com</div>',
			esc_attr( $data ? $data['host'] : '' )
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
		if ( strpos( $mailrelay_data['host'], '.ipzmarketing.com' ) !== false ) {
			$mailrelay_data['host'] = str_replace( '.ipzmarketing.com', '', $mailrelay_data['host'] );
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
}
