<?php
/*
Plugin Name: Sprout Invoices Add-on - Paystack Payments
Plugin URI: https://paystack.com/
Description: Accept Payments with Paystack for Sprout Invoices.
Author: KendysonD
Version: 1.0
Author URI: https://paystack.com
*/

/**
 * Plugin File
 */
define( 'SI_ADDON_PAYSTACK_VERSION', '3.1' );
define( 'SI_ADDON_PAYSTACK_DOWNLOAD_ID', 141 );
define( 'SI_ADDON_PAYSTACK_FILE', __FILE__ );
define( 'SI_ADDON_PAYSTACK_NAME', 'Sprout Invoices Paystack Payments' );
define( 'SI_ADDON_PAYSTACK_URL', plugins_url( '', __FILE__ ) );


// Load up the processor before updates
add_action( 'si_payment_processors_loaded', 'si_load_paystack' );
function si_load_paystack() {
	require_once( 'SI_Paystack.php' );
}

// Load up the updater after si is completely loaded
add_action( 'sprout_invoices_loaded', 'si_load_paystack_updates' );
function si_load_paystack_updates() {
	if ( class_exists( 'SI_Updates' ) ) {
		require_once( 'SI_Updates.php' );
	}
}