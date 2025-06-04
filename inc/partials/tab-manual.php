<?php
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Access Denied.' );
}
?>
<div id="tab-manual">
	<h3><?php esc_html_e( 'Manual Sync', 'mailrelay' ); ?></h3>
	<?php settings_errors(); ?>
	<form method="post">
		<?php
		wp_nonce_field('_mailrelay_nonce', '_mailrelay_nonce');
		echo '<table class="form-table">';
		do_settings_sections( 'manual-page-admin' );
		echo '</table>';
		$attributes   = array( 'onclick' => 'return check_form();' );
		$submit_text = __( 'Sync', 'mailrelay' );
		submit_button( $submit_text, 'primary', 'submit-manual', true, $attributes );
		?>
	</form>
</div>