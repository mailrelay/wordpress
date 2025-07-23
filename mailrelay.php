<?php  // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Mailrelay
 * Plugin URI: https://mailrelay.com
 * Description: Synchronize your WordPress users with Mailrelay
 * Version: 2.1.3
 * Author: Mailrelay
 * Text Domain: Mailrelay
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

define( 'MAILRELAY_PLUGIN_VERSION', '2.1.3' );

if ( ! defined( 'MAILRELAY_BASE_DOMAIN' ) ) {
	define( 'MAILRELAY_BASE_DOMAIN', 'ipzmarketing.com' );
}

require_once __DIR__ . '/libraries/action-scheduler/action-scheduler.php';

require_once __DIR__ . '/inc/common.php';
require_once __DIR__ . '/inc/block-widgets.php';
require_once __DIR__ . '/inc/global-hooks.php';

if ( function_exists( 'is_admin' ) && is_admin() ) {

	require_once __DIR__ . '/inc/class-mailrelay-pages.php';

	$mailrelay_pages = new MailrelayPages();
	$mailrelay_pages->setup_hooks();

}


add_action(
	'wpforms_loaded',
	function () {
		require_once __DIR__ . '/inc/class-mailrelay-wpforms.php';
	}
);

require_once __DIR__ . '/inc/class-mailrelay-woocommerce.php';
add_action(
	'plugins_loaded',
	function () {
		MailrelayWoocommerce::instance()->setup_hooks();
	}
);
