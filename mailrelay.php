<?php  // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Mailrelay
 * Plugin URI: https://mailrelay.com
 * Description: Synchronize your WordPress users with Mailrelay
 * Version: 2.1.0-beta
 * Author: Mailrelay
 * Text Domain: Mailrelay
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

define( 'MAILRELAY_PLUGIN_VERSION', '2.1.0-beta' );

require_once __DIR__ . '/inc/common.php';
require_once __DIR__ . '/inc/block-widgets.php';
require_once __DIR__ . '/inc/global-hooks.php';

if ( function_exists( 'is_admin' ) && is_admin() ) {

	require_once __DIR__ . '/inc/class-mailrelaypages.php';

	$mailrelay_pages = new MailrelayPages();
	$mailrelay_pages->setup_hooks();

}

function mailrelay_wpforms() {

	require_once __DIR__ . '/inc/class-mailrelay-wpforms.php';

}
add_action( 'wpforms_loaded', 'mailrelay_wpforms' );
