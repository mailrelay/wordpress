<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

$store_id = MailrelayWoocommerce::instance()->get_store_id();
?>

<?php if ( $store_id ) : ?>
	<?php
	$synced_products_count = MailrelayWoocommerce::instance()->get_synced_products_count();
	?>

	<h3><?php esc_html_e( 'WooCommerce Sync Status', 'mailrelay' ); ?></h3>

	<div class="mailrelay-sync-status">
		<div class="mailrelay-sync-box">
			<div class="mailrelay-sync-count"><?php echo esc_html( $synced_products_count ); ?></div>
			<div class="mailrelay-sync-label"><?php esc_html_e( 'Products Synced', 'mailrelay' ); ?></div>
		</div>
	</div>

	<hr />

	<h3><?php esc_html_e( 'WooCommerce Settings', 'mailrelay' ); ?></h3>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=mailrelay&tab=WooCommerce' ) ); ?>">
		<?php
		wp_nonce_field( '_mailrelay_nonce', '_mailrelay_nonce' );
		do_settings_sections( 'mailrelay-woocommerce-sync-page' );
		submit_button( __( 'Save WooCommerce Sync Settings', 'mailrelay' ) );
		?>
	</form>

	<hr />

	<h3><?php esc_html_e( 'Manual Synchronization', 'mailrelay' ); ?></h3>
	<p><?php esc_html_e( 'Manually synchronize all your WooCommerce products with Mailrelay. This may take some time depending on the amount of data.', 'mailrelay' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=mailrelay&tab=WooCommerce' ) ); ?>">
		<?php
		wp_nonce_field( '_mailrelay_nonce', '_mailrelay_nonce' );
		?>
		<input type="hidden" name="action" value="mailrelay_manual_woocommerce_sync" />
		<?php
		submit_button( __( 'Manually Sync All WooCommerce Data', 'mailrelay' ), 'secondary', 'submit', true );
		?>
	</form>
<?php else : ?>
	<h3><?php esc_html_e( 'WooCommerce Settings', 'mailrelay' ); ?></h3>

	<p><?php esc_html_e( 'Before you can sync your WooCommerce products with Mailrelay, you need to connect your store.', 'mailrelay' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=mailrelay&tab=WooCommerce' ) ); ?>">
		<?php
		wp_nonce_field( '_mailrelay_nonce', '_mailrelay_nonce' );
		?>
		<input type="hidden" name="action" value="mailrelay_connect_woocommerce_store" />
		<?php
		submit_button( __( 'Connect Store', 'mailrelay' ), 'primary', 'submit', false );
		?>
	</form>
<?php endif; ?>