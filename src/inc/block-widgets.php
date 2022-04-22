<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

function mailrelay_wpforms_init() {
	$js_file = '../js/mailrelay-wpforms-block.js';
	wp_register_script( 'mailrelay-wpforms-block', plugin_dir_url( __FILE__ ) . $js_file, array( 'wp-blocks', 'wp-i18n' ), filemtime( plugin_dir_path( __FILE__ ) . $js_file ), false );

	$all_forms['forms'] = mailrelay_get_signup_forms();

	wp_localize_script( 'mailrelay-wpforms-block', 'mailrelay_wpforms_forms', $all_forms );
	register_block_type(
		'mailrelay/mailrelay-wpforms',
		array(
			'editor_script' => 'mailrelay-wpforms-block',
		)
	);
}
add_action( 'init', 'mailrelay_wpforms_init' );
