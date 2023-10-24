<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

class Mailrelay_WPForms {

	public function __construct() {
		add_filter( 'wpforms_builder_settings_sections', array( $this, 'settings_section' ), 20, 2 );
		add_filter( 'wpforms_form_settings_panel_content', array( $this, 'settings_section_content' ), 20 );
		add_action( 'wpforms_process_complete', array( $this, 'send_data_to_mailrelay' ), 10, 4 );
	}

	public function settings_section( $sections ) {
		$sections['mailrelay'] = __( 'Mailrelay', 'mailrelay' );
		return $sections;
	}

	public function settings_section_content( $instance ) {
		$base_groups = mailrelay_get_groups();
		$groups = array();
		foreach ( $base_groups as $g ) {
			$groups[ $g['id'] ] = $g['name'];
		}

		$all_fields = array(
			'text',
			'textarea',
			'select',
			'radio',
			'checkbox',
			'email',
			'address',
			'url',
			'name',
			'hidden',
			'date-time',
			'phone',
			'number',
			'file-upload',
			'payment-single',
			'payment-multiple',
			'payment-select',
			'payment-total',
		); // TODO: make this list dynamic
		echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-mailrelay">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="wpforms-panel-content-section-title">' . __( 'Mailrelay', 'mailrelay' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		wpforms_panel_field(
			'select',
			'settings',
			'mailrelay_field_group_id',
			$instance->form_data,
			__( 'Group', 'mailrelay' ),
			array(
				'options'     => $groups,
				'placeholder' => __( '-- Select Group --', 'mailrelay' ),
			)
		);

		$mailrelay_fields = array(
			'email'     => 'Email Address',
			'name'      => 'Name',
			'address'   => 'Address',
			'city'      => 'City',
			'state'     => 'State',
			'country'   => 'Country',
			'birthday'  => 'Birthday',
			'website'   => 'Website',
			'time_zone' => 'Time Zone',
		); // TODO: save this list in plugin configuration

		foreach ( $mailrelay_fields as $id => $text ) {
			wpforms_panel_field(
				'select',
				'settings',
				'mailrelay_field_' . $id,
				$instance->form_data,
				$text,
				array(
					'field_map'   => $all_fields,
					'placeholder' => __( '-- Select Field --', 'mailrelay' ),
				)
			);
		}

		echo '</div>';
	}

	public function send_data_to_mailrelay( $fields, $entry, $form_data, $entry_id ) {

		$mailrelay_fields = array(
			'email'     => 'Email Address',
			'name'      => 'Name',
			'address'   => 'Address',
			'city'      => 'City',
			'state'     => 'State',
			'country'   => 'Country',
			'birthday'  => 'Birthday',
			'website'   => 'Website',
			'time_zone' => 'Time Zone',
		);

		$group_id = 0;
		if ( ! empty( $form_data['settings']['mailrelay_field_group_id'] ) ) {
			$group_id = intval( $form_data['settings']['mailrelay_field_group_id'] );
		}
		if ( $group_id < 1 ) {
			return;
		}

		$args = array();
		foreach ( $mailrelay_fields as $id => $text ) {
			$fid = $form_data['settings'][ 'mailrelay_field_' . $id ];
			$fvalue = $fields[ $fid ]['value'];

			if ( ! empty( $fvalue ) ) {
				$args[ $id ] = $fvalue;
			}
		}
		if ( ! array_key_exists( 'email', $args ) ) {
			return;
		}

		$return = mailrelay_sync_user( null, array( $group_id ), null, $args );

		if ( function_exists( 'wpforms_log' ) ) {
			wpforms_log(
				'Mailrelay Response',
				$request,
				array(
					'type'    => array( 'provider' ),
					'parent'  => $entry_id,
					'form_id' => $form_data['id'],
				)
			);
		}
	}
}

new Mailrelay_WPForms();
