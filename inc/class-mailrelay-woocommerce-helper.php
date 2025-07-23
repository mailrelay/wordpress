<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access Denied.' );
}

class MailrelayWoocommerceHelper {
	public static function transform_price( $price ) {
		return (int) round( floatval( $price ) * 100 );
	}
}
