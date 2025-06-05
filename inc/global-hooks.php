<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

function mailrelay_new_user_auto_sync( $user_id ) {
	if ( '1' !== get_option( 'mailrelay_auto_sync' ) ) {
		// Autosync isn't enabled
		return;
	}

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
add_action( 'user_register', 'mailrelay_new_user_auto_sync' );
