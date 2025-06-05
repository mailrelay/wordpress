<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}
?>
<div id="tab-authentication">
	<h3><?php esc_html_e( 'Authentication', 'mailrelay' ); ?></h3>
	<p><?php esc_html_e( 'Login using your account name and API Key.', 'mailrelay' ); ?></p>
	<p><?php esc_html_e( 'For example if your account name is demo.ipzmarketing.com write only "demo".', 'mailrelay' ); ?></p>
	<p><?php esc_html_e( 'Your API Key can be found or generated at your Mailrelay Account -> Settings -> API Access.', 'mailrelay' ); ?></p>
	<?php settings_errors(); ?>
	<form method="post">
		<?php
		wp_nonce_field('_mailrelay_nonce', '_mailrelay_nonce');
		echo '<table class="form-table">';
		do_settings_sections( 'mailrelay-authentication-page' );
		echo '</table>';
		$submit_text = __( 'Save', 'mailrelay' );
		submit_button( $submit_text, 'primary', 'submit-authentication' );
		?>
	</form>
</div>