<?php  // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Mailrelay
 * Plugin URI: http://mailrelay.com
 * Description: Syncronize your WordPress users with Mailrelay
 * Version: 2.0
 * Author: Consultor-PC
 * Text Domain: Mailrelay
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

define( 'MAILRELAY_PLUGIN_VERSION', '2.0' );

require_once __DIR__ . '/inc/common.php';
require_once __DIR__ . '/inc/global-hooks.php';

if ( function_exists( 'is_admin' ) && is_admin() ) {

	require_once __DIR__ . '/inc/class-mailrelaypages.php';

	$mailrelay_pages = new MailrelayPages();
	$mailrelay_pages->setup_hooks();
}
