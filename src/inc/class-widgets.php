<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

class MailrelayWidgets extends WP_Widget {

	public function __construct() {

		parent::__construct(
			'mailrelaywidgets',
			__( 'MailRelay Widget', 'mailrelay' ),
			array( 'description' => __( 'Add signup forms', 'mailrelay' ) )
		);
	}

	public function form( $instance ) {

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$form_id = ! empty( $instance['form_id'] ) ? $instance['form_id'] : '';

		$response = mailrelay_get_signup_forms();
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Title:</label>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>" /></p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'form_id' ) ); ?>">Select a form:</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'form_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'form_id' ) ); ?>">
			<option></option>
		<?php
		foreach ( $response as $res ) {
			$active = ( $form_id === $res['id'] ) ? 'selected' : '';
			echo '<option value=' . esc_attr( $res['id'] ) . ' ' . esc_attr( $active ) . '>' . esc_html( $res['name'] ) . '</option>';
		}
		?>
			</select>
		</p>

		<?php
	}

	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title'] = wp_strip_all_tags( $new_instance['title'] );
		$instance['form_id'] = wp_strip_all_tags( $new_instance['form_id'] );
		return $instance;

	}

	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );
		$form_id = $instance['form_id'];

		$response = mailrelay_get_signup_forms( $form_id );

		echo esc_html( $args['before_widget'] );
		?>

		<div class="mailrelay-widget-form">
		<?php
		if ( ! empty( $response ) && count( $response ) > 0 ) {
			echo $response[0]['embedded_form_code']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		</div>

		<?php
		echo esc_html( $args['after_widget'] );

	}

}

function mailrelay_register_widgets() {

	register_widget( 'MailrelayWidgets' );

}

add_action( 'widgets_init', 'mailrelay_register_widgets' );
