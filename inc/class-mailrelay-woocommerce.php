<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

require_once __DIR__ . '/class-mailrelay-woocommerce-product.php';
require_once __DIR__ . '/class-mailrelay-woocommerce-all-products.php';
require_once __DIR__ . '/class-mailrelay-woocommerce-cart.php';

class MailrelayWoocommerce {
	/**
	 * The single instance of the class.
	 *
	 * @var MailrelayWoocommerce|null
	 */
	private static $instance = null;

	/**
	 * Main MailrelayWoocommerce Instance.
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @return MailrelayWoocommerce
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_store_id() {
		return get_option( 'mailrelay_woocommerce_store_id' );
	}

	public function auto_sync_enabled() {
		return get_option( 'mailrelay_woocommerce_auto_sync' ) === '1' && $this->get_store_id() && $this->mailrelay_configured();
	}

	public function mailrelay_configured() {
		return (bool) mailrelay_data();
	}

	public function setup_hooks() {
		if ( ! mailrelay_woo_commerce_installed() ) {
			return;
		}

		add_action( 'woocommerce_new_product', array( $this, 'handle_product_created' ), 10, 1 );
		add_action( 'woocommerce_update_product', array( $this, 'handle_product_updated' ), 10, 1 );

		// Cart hooks.
		add_action( 'woocommerce_add_to_cart', array( $this, 'handle_cart_event' ), 20, 99 );
		add_action( 'woocommerce_cart_updated', array( $this, 'handle_cart_event' ), 20, 99 );
		add_action( 'woocommerce_remove_cart_item', array( $this, 'handle_cart_event' ), 20, 99 );
		add_action( 'woocommerce_cart_emptied', array( $this, 'handle_cart_event' ), 20, 99 );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'capture_guest_email_from_checkout' ), 10, 1 );
		add_action( 'woocommerce_new_order', array( $this, 'handle_new_order' ), 10, 2 );

		// User session hooks.
		add_action( 'wp_login', array( $this, 'handle_user_login' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'handle_user_logout' ), 10, 1 );

		add_action(
			'mailrelay_sync_product_background',
			function ( int $post_ID ) {
				$service = new MailrelayWoocommerceProduct();
				$service->sync( $post_ID );
			},
			10,
			1
		);

		add_action(
			'mailrelay_sync_all_products_background',
			function ( $page = 1 ) {
				$service = new MailrelayWoocommerceAllProducts();
				$service->sync( $page );
			},
			10,
			1
		);

		add_action(
			'mailrelay_sync_cart_background',
			function ( $cart_data ) {
				$service = new MailrelayWoocommerceCart();
				$service->sync( $cart_data );
			},
			10,
			1
		);

		add_action(
			'mailrelay_sync_cart_completed_background',
			function ( $data ) {
				$service = new MailrelayWoocommerceCart();
				$service->set_completed( $data['cart_id'], $data['email'] );
			},
			10,
			1
		);
	}

	public function enqueue_sync_all_products() {
		as_enqueue_async_action(
			'mailrelay_sync_all_products_background',
			array(),
			'mailrelay',
			true
		);
	}

	/**
	 * Handle product save.
	 *
	 * @param int $post_ID Product ID.
	 */
	public function handle_product_created( int $post_ID ) {
		if ( 'product' !== get_post_type($post_ID) ) {
			return;
		}

		if ( ! $this->auto_sync_enabled() ) {
			return;
		}

		as_enqueue_async_action(
			'mailrelay_sync_product_background',
			array( $post_ID ),
			'mailrelay'
		);
	}

	/**
	 * Handle product update.
	 *
	 * @param int $post_ID Product ID.
	 */
	public function handle_product_updated( int $post_ID ) {
		if ( 'product' !== get_post_type($post_ID) ) {
			return;
		}

		if ( ! $this->auto_sync_enabled() ) {
			return;
		}

		if ( in_array(get_post_status($post_ID), array( 'trash', 'auto-draft' ), true) ) {
			return;
		}

		as_enqueue_async_action(
			'mailrelay_sync_product_background',
			array( $post_ID ),
			'mailrelay'
		);
	}

	public function handle_new_order( $order_id, $order ) {
		if ( ! $this->auto_sync_enabled() ) {
			return;
		}

		if ( ! $order ) {
			return;
		}

		$email = $order->get_billing_email();
		// The cart_id is the user session.
		$cart_id = WC()->session->get_customer_id();

		if ( empty( $email ) || empty( $cart_id ) ) {
			return;
		}

		as_enqueue_async_action(
			'mailrelay_sync_cart_completed_background',
			array(
				'cart_id' => $cart_id,
				'email'   => $email,
			),
			'mailrelay'
		);
	}

	public function capture_guest_email_from_checkout() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! is_user_logged_in() && isset( $_POST['billing_email'] ) && is_email( wp_unslash( $_POST['billing_email'] ) ) ) {
			$email = sanitize_email( wp_unslash( $_POST['billing_email'] ) );
			if ( WC()->session ) {
				WC()->session->set( 'mailrelay_cart_tracking_email', $email );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	private function get_tracking_email() {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			return $user->user_email;
		}

		if ( WC()->customer && WC()->customer->get_billing_email() ) {
			return WC()->customer->get_billing_email();
		}

		return null;
	}

	public function handle_cart_event() {
		if ( ! $this->auto_sync_enabled() ) {
			return;
		}

		$email = $this->get_tracking_email();

		if ( empty( $email ) ) {
			return;
		}

		$cart = WC()->cart;

		if ( is_null( $cart ) || $cart->is_empty() ) {
			return;
		}

		// Prevent duplicate jobs using a transient based on cart hash.
		$transient_name = 'mailrelay_cart_sync_' . $cart->get_cart_hash();
		if ( get_transient( $transient_name ) ) {
			return;
		}
		set_transient( $transient_name, true, MINUTE_IN_SECONDS * 5 );

		$cart_contents_for_queue = array();
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];
			if ( ! $product ) {
				continue;
			}

			$cart_contents_for_queue[ $cart_item_key ] = array(
				'product_id'    => $cart_item['product_id'],
				'variation_id'  => $cart_item['variation_id'],
				'quantity'      => $cart_item['quantity'],
				'product_price' => $product->get_price(),
			);
		}

		$cart_data = array(
			'email'          => $email,
			'user_id'        => get_current_user_id(),
			'cart_id'        => WC()->session->get_customer_id(),
			'cart_total'     => $cart->get_total( 'edit' ),
			'cart_tax_total' => $cart->get_total_tax(),
			'cart_items'     => $cart_contents_for_queue,
			'checkout_url'   => wc_get_checkout_url(),
			'is_empty'       => $cart->is_empty(),
		);

		as_enqueue_async_action(
			'mailrelay_sync_cart_background',
			array( 'cart_data' => $cart_data ),
			'mailrelay'
		);
	}

	public function handle_user_login( $user_login, $user ) {
		if ( WC()->session ) {
			WC()->session->set( 'mailrelay_cart_tracking_email', $user->user_email );
		}
		$this->handle_cart_event();
	}

	public function handle_user_logout() {
		if ( WC()->session ) {
			WC()->session->set( 'mailrelay_cart_tracking_email', null );
		}
	}

	public function get_synced_products_count() {
		$store_id = $this->get_store_id();

		if ( ! $store_id ) {
			return 0;
		}

		// Check cache first
		$cache_key = 'mailrelay_synced_products_count_' . $store_id;
		$cached_count = get_transient( $cache_key );

		if ( false !== $cached_count ) {
			return (int) $cached_count;
		}

		$response = mailrelay_api_request( 'GET', "ecommerce/stores/{$store_id}/products?" . http_build_query( array( 'per_page' => 1 ) ) );

		if ( $response['wp_error'] || 200 !== $response['code'] ) {
			return 0;
		}

		$count = (int) $response['headers']['total'];

		// Cache for 5 minutes
		set_transient( $cache_key, $count, 5 * MINUTE_IN_SECONDS );

		return $count;
	}

	/**
	 * Clear the synced products count cache
	 */
	public function clear_synced_products_count_cache() {
		$store_id = $this->get_store_id();
		if ( $store_id ) {
			$cache_key = 'mailrelay_synced_products_count_' . $store_id;
			delete_transient( $cache_key );
		}
	}
}
