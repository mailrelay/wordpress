<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

function mailrelay_wpforms_init() {

	wp_register_script( 'mailrelay-wpforms-block', plugin_dir_url( __FILE__ ) . '../js/mailrelay-wpforms-block.js', array( 'wp-blocks', 'wp-i18n' ), true, false );

	$all_forms['forms'] = mailrelay_get_signup_forms();

	wp_localize_script( 'mailrelay-wpforms-block', 'mailrelay_wpforms_forms', $all_forms );
	register_block_type(
		'mailrelay/mailrelay-wpforms',
		array(
			'editor_script'   => 'mailrelay-wpforms-block',
			'render_callback' => 'mailrelay_wpforms_callback',
		)
	);
}
add_action( 'init', 'mailrelay_wpforms_init' );

function mailrelay_wpforms_callback( $attributes, $content, $block_instance ) {

	$form_id = ! empty( $attributes['form_id'] ) ? $attributes['form_id'] : '';
	ob_start();
	?>
	<div <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
		<?php
		if ( isset( $form_id ) && (int) $form_id > 0 ) {
			$response = mailrelay_get_signup_forms( $form_id );
			if ( ! empty( $response ) && count( $response ) > 0 ) {
				echo $response[0]['embedded_form_code']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		?>
	</div>
	<?php
	return ob_get_clean();
}

function mailrelay_api_init() {

	register_rest_route(
		'mailrelay-wpforms',
		'/get-signup-forms',
		array(
			'methods'             => 'GET',
			'callback'            => 'mailrelay_get_signup_forms',
			'permission_callback' => function () {
				return true;
			},
		)
	);
}

add_action( 'rest_api_init', 'mailrelay_api_init' );
