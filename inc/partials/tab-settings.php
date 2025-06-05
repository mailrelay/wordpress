<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}
?>
<div id="tab-settings">
	<h3><?php esc_html_e( 'Settings', 'mailrelay' ); ?></h3>
	<p>
		<?php
		/* translators: %1: host  %2: authentication link */
		printf( __( 'You are currently logged in as <strong>%1$s.ipzmarketing.com</strong> (<a href="%2$s">Change Account</a>)', 'mailrelay' ), esc_html( $this->mailrelay_data()['host'] ), esc_url( $link ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</p>
	<?php settings_errors(); ?>
	<form method="post">
		<?php
		wp_nonce_field('_mailrelay_nonce', '_mailrelay_nonce');
		echo '<table class="form-table">';
		do_settings_sections( 'mailrelay-settings-page' );
		echo '</table>';
		$attributes  = array( 'onclick' => 'return check_form();' );
		$submit_text = __( 'Save', 'mailrelay' );
		submit_button( $submit_text, 'primary', 'submit-settings', true, $attributes );
		?>
	</form>
</div>