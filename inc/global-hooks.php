<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

function mailrelay_user_register_hook( $user_id ) {
	if ( '1' !== get_option( 'mailrelay_auto_sync' ) ) {
		// Autosync isn't enabled
		return;
	}

	// Schedule the sync to run in the background
	as_enqueue_async_action(
		'mailrelay_sync_user_background',
		array( $user_id ),
		'mailrelay',
		true
	);
}
add_action( 'user_register', 'mailrelay_user_register_hook' );

/**
 * Background handler for syncing users with Mailrelay
 *
 * @param int $user_id The WordPress user ID to sync.
 */
function mailrelay_sync_user_background( $user_id ) {
	$user = new WP_User( $user_id );
	$groups = get_option( 'mailrelay_auto_sync_groups' );

	if ( ! empty( $groups ) ) {
		try {
			mailrelay_sync_user( $user, $groups );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Ignore if something goes wrong to avoid showing errors to user
		}
	}
}
add_action( 'mailrelay_sync_user_background', 'mailrelay_sync_user_background', 10, 1 );
