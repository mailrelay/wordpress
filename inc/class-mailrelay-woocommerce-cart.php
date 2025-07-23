<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

require_once __DIR__ . '/class-mailrelay-woocommerce-helper.php';
require_once __DIR__ . '/class-mailrelay-api-request-exception.php';

class MailrelayWoocommerceCart {
	public function sync( array $cart_data ) {
		if ( empty( $cart_data['email'] ) ) {
			return;
		}

		$data = $this->transform( $cart_data );

		$store_id = MailrelayWoocommerce::instance()->get_store_id();
		$endpoint = "ecommerce/stores/{$store_id}/carts/sync";

		$response = mailrelay_api_request(
			'POST',
			$endpoint,
			array(
				'body'    => wp_json_encode( $data ),
				'headers' => array( 'content-type' => 'application/json' ),
			)
		);

		if ( $response['wp_error'] || ( isset( $response['code'] ) && $response['code'] >= 500 ) ) {
			throw new MailrelayApiRequestException( 'Mailrelay API request failed, will retry.' );
		}

		return $response;
	}

	public function set_completed( $cart_id, $email ) {
		$store_id = MailrelayWoocommerce::instance()->get_store_id();
		$endpoint = "ecommerce/stores/{$store_id}/carts/{$cart_id}";

		$data = array(
			'email'  => $email,
			'status' => 'completed',
		);

		$response = mailrelay_api_request(
			'PATCH',
			$endpoint,
			array(
				'body'    => wp_json_encode( $data ),
				'headers' => array( 'content-type' => 'application/json' ),
			)
		);

		if ( $response['wp_error'] || ( isset( $response['code'] ) && $response['code'] >= 500 ) ) {
			throw new MailrelayApiRequestException( 'Mailrelay API request failed, will retry.' );
		}

		return $response;
	}

	public function transform( array $cart_data ) {
		$cart_items_attributes = array();
		if ( ! empty( $cart_data['cart_items'] ) ) {
			foreach ( $cart_data['cart_items'] as $cart_item_key => $cart_item ) {
				$cart_items_attributes[] = array(
					'cart_item_id'       => $cart_item_key,
					'product_variant_id' => (string) ( ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'] ),
					'quantity'           => $cart_item['quantity'],
					'price'              => MailrelayWoocommerceHelper::transform_price( $cart_item['product_price'] ),
				);
			}
		}

		return array(
			'cart_id'               => $cart_data['cart_id'],
			'status'                => 'pending',
			'checkout_url'          => $cart_data['checkout_url'],
			'total'                 => MailrelayWoocommerceHelper::transform_price( $cart_data['cart_total'] ),
			'tax_total'             => MailrelayWoocommerceHelper::transform_price( $cart_data['cart_tax_total'] ),
			'email'                 => $cart_data['email'],
			'cart_items_attributes' => $cart_items_attributes,
		);
	}
}
