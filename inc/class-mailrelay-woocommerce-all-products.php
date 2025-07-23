<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

class MailrelayWoocommerceAllProducts {
	/**
	 * Batch size for processing products
	 */
	const BATCH_SIZE = 100;

	/**
	 * Sync products in batches to avoid memory issues
	 *
	 * @param int $page Current page number.
	 */
	public function sync( $page = 1 ) {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => self::BATCH_SIZE,
			'paged'          => $page,
			'fields'         => 'ids',
			'no_found_rows'  => false, // We need total count for pagination
		);

		$query = new WP_Query( $args );
		$products = $query->posts;

		// Queue individual product sync jobs
		foreach ( $products as $product_id ) {
			as_enqueue_async_action(
				'mailrelay_sync_product_background',
				array( $product_id ),
				'mailrelay'
			);
		}

		// If there are more pages, schedule the next batch
		if ( $page < $query->max_num_pages ) {
			as_enqueue_async_action(
				'mailrelay_sync_all_products_background',
				array( $page + 1 ),
				'mailrelay'
			);
		}

		// Clean up memory
		wp_reset_postdata();
	}
}
