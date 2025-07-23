<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

require_once __DIR__ . '/class-mailrelay-woocommerce-helper.php';
require_once __DIR__ . '/class-mailrelay-api-request-exception.php';

class MailrelayWoocommerceProduct {
	public function sync( int $post_ID ) {
		$product = wc_get_product($post_ID);
		if ( ! $product ) {
			return false;
		}

		$data = $this->transform( $product );

		$store_id = MailrelayWoocommerce::instance()->get_store_id();
		$endpoint = "ecommerce/stores/{$store_id}/products/sync";

		$response = mailrelay_api_request(
			'POST',
			$endpoint,
			array(
				'body'    => wp_json_encode($data),
				'headers' => array( 'content-type' => 'application/json' ),
			)
		);

		if ( $response['wp_error'] || ( isset( $response['code'] ) && $response['code'] >= 500 ) ) {
			throw new MailrelayApiRequestException( 'Mailrelay API request failed, will retry.' );
		}

		// Clear cache after successful sync since product count may have changed
		MailrelayWoocommerce::instance()->clear_synced_products_count_cache();

		return $response;
	}

	public function transform( WC_Product $product ) {
		if ( ! $product ) {
			return array();
		}

		$product_image = $this->get_product_image($product);

		$variants = array();
		if ( $product->is_type('variable') ) {
			foreach ( $product->get_children() as $child_id ) {
				$variant = wc_get_product($child_id);
				if ( $variant ) {
					$variant_image = $this->get_product_image($variant);

					$variants[] = array(
						'product_variant_id' => (string) $variant->get_id(),
						'name'               => $variant->get_name(),
						'sku'                => $variant->get_sku(),
						'price'              => MailrelayWoocommerceHelper::transform_price( $variant->get_price() ),
						'url'                => get_permalink($variant->get_id()),
						'image_url'          => $variant_image ? $variant_image : $product_image,
					);
				}
			}
		}
		if ( empty($variants) ) {
			// No variants, add the product itself as a single variant
			$variants[] = array(
				'product_variant_id' => (string) $product->get_id(),
				'name'               => $product->get_name(),
				'sku'                => $product->get_sku(),
				'price'              => MailrelayWoocommerceHelper::transform_price( $product->get_price() ),
				'url'                => get_permalink($product->get_id()),
				'image_url'          => $product_image,
			);
		}
		return array(
			'product_id'                  => (string) $product->get_id(),
			'name'                        => $product->get_name(),
			'url'                         => get_permalink($product->get_id()),
			'image_url'                   => $product_image,
			'product_variants_attributes' => $variants,
		);
	}

	public function get_product_image( $product ) {
		$id = is_a($product, 'WC_Product') ? $product->get_id() : $product->ID;
		$meta = get_post_meta($id);
		$key = '_thumbnail_id';
		$image_key = $this->get_product_image_key();
		if ( $meta && is_array($meta) && array_key_exists($key, $meta) && isset($meta[ $key ][0]) ) {
			$img = wp_get_attachment_image_src($meta[ $key ][0], $image_key);
			if ( ! empty($img[0]) ) {
				if ( substr($img[0], 0, 4) !== 'http' ) {
					return rtrim(home_url(), '/') . '/' . ltrim($img[0], '/');
				}
				return $img[0];
			}
		}
		$url = get_the_post_thumbnail_url($id, $image_key);
		if ( $url ) {
			return $url;
		} else {
			return null;
		}
	}

	public function get_product_image_key() {
		return 'medium';
	}
}
