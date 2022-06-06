<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

function mailrelay_wpforms_init() {
	$js_file = '../js/mailrelay-wpforms-block.js';
	wp_register_script( 'mailrelay-wpforms-block', plugin_dir_url( __FILE__ ) . $js_file, array( 'wp-blocks', 'wp-i18n' ), filemtime( plugin_dir_path( __FILE__ ) . $js_file ), false );

	wp_localize_script(
		'mailrelay-wpforms-block',
		'mailrelay_wpforms_forms',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'mailrelay_wpforms_block' ),
		)
	);
	register_block_type(
		'mailrelay/mailrelay-wpforms',
		array(
			'editor_script' => 'mailrelay-wpforms-block',
		)
	);
}
add_action( 'init', 'mailrelay_wpforms_init' );

function mailrelay_get_signup_forms_ajax() {
	echo wp_json_encode( mailrelay_get_signup_forms() );
	wp_die();
}
add_action( 'wp_ajax_mailrelay_get_signup_forms', 'mailrelay_get_signup_forms_ajax' );
