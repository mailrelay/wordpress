<?php // phpcs:ignore Generic.Commenting.DocComment.MissingShort Squiz.Commenting.FileComment.MissingPackageTag

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

if ( ! function_exists( 'mailrelay_woo_commerce_installed' ) ) {
	function mailrelay_woo_commerce_installed() {
		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}
}

if ( ! function_exists( 'mailrelay_api_request' ) ) {
	function mailrelay_api_request( $method, $url, $args = array(), $mailrelay_data = null ) {
		if ( is_null( $mailrelay_data ) ) {
			$mailrelay_data = mailrelay_data();
		}

		$url = 'https://' . $mailrelay_data['host'] . '.ipzmarketing.com/api/v1/' . $url;

		$args = array_merge_recursive(
			$args,
			array(
				'method'  => $method,
				'headers' => array(
					'x-auth-token'     => $mailrelay_data['api_key'],
					'x-request-origin' => 'Wordpress|' . MAILRELAY_PLUGIN_VERSION . '|' . get_bloginfo( 'version' ),
				),
			)
		);

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'wp_error'      => true,
				'error_message' => $response->get_error_message(),
				'response'      => $response,
			);
		}

		$code = wp_remote_retrieve_response_code( $response );

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return array(
			'wp_error' => false,
			'code'     => $code,
			'body'     => $body,
			'response' => $response,
		);
	}
}

if ( ! function_exists( 'mailrelay_get_groups' ) ) {
	function mailrelay_get_groups() {
		$response = mailrelay_api_request( 'GET', 'groups' );

		if ( $response['wp_error'] ) {
			/* translators: %s error message */
			wp_die( sprintf( esc_html__( 'Something went wrong: %s', 'mailrelay' ), esc_html( $response['error_message'] ) ) );
		}

		if ( 200 === $response['code'] ) {
			return $response['body'];
		} else {
			/* translators: %s error message */
			wp_die( sprintf( esc_html__( 'Something went wrong: %s', 'mailrelay' ), esc_html( $response['body'] ) ) );
		}
	}
}

if ( ! function_exists( 'mailrelay_sync_user' ) ) {
	function mailrelay_sync_user( $user, $groups, $mailrelay_data = null ) {
		if ( is_null( $mailrelay_data ) ) {
			$mailrelay_data = mailrelay_data();
		}

		$full_name = $user->first_name . ' ' . $user->last_name;
		if ( ' ' === $full_name ) {
			$full_name = $user->display_name;
		}
		$data = array(
			'email'              => $user->user_email,
			'name'               => $full_name,
			'replace_groups'     => false,
			'restore_if_deleted' => false,
			'status'             => 'active',
			'group_ids'          => (array) $groups,
		);
		$data = wp_json_encode( $data );

		$response = mailrelay_api_request(
			'POST',
			'subscribers/sync',
			array(
				'post_data' => 'body',
				'body'      => $data,
				'headers'   => array(
					'content-type' => 'application/json',
				),
			),
			$mailrelay_data
		);

		if ( $response['wp_error'] ) {
			/* translators: %s error message */
			wp_die( sprintf( esc_html__( 'Something went wrong: %s', 'mailrelay' ), esc_html( $response['error_message'] ) ) );
		}

		if ( 200 === $response['code'] ) {
			return array(
				'status' => 'updated',
			);
		} elseif ( 201 === $response['code'] ) {
			return array(
				'status' => 'created',
			);
		} else {
			return array(
				'status' => 'failed',
			);
		}
	}
}

if ( ! function_exists( 'mailrelay_ping' ) ) {
	function mailrelay_ping( $mailrelay_data ) {
		$response = mailrelay_api_request( 'GET', 'ping', array(), $mailrelay_data );

		if ( $response['wp_error'] ) {
			return false;
		}

		return $response['code'];
	}
}

if ( ! function_exists( 'mailrelay_data' ) ) {
	function mailrelay_data() {
		$api_key = get_option( 'mailrelay_api_key' );

		if ( $api_key ) {
			return array(
				'host'        => get_option( 'mailrelay_host' ),
				'api_key'     => $api_key,
				'auto_sync'   => get_option( 'mailrelay_auto_sync' ),
				'groups_sync' => get_option( 'mailrelay_auto_sync_groups' ),
			);
		}

		return false;
	}
}
