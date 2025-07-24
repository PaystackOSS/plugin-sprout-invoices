<?php
/*
Plugin Name: Sprout Invoices Add-on - Paystack Payments
Plugin URI: https://paystack.com/
Description: Accept Payments with Paystack for Sprout Invoices.
Author: Paystack
Version: 2.1.4
Author URI: https://paystack.com
*/

/**
 * Plugin File
 */
define('SI_ADDON_PAYSTACK_VERSION', '3.1');
define('SI_ADDON_PAYSTACK_DOWNLOAD_ID', 141);
define('SI_ADDON_PAYSTACK_FILE', __FILE__);
define('SI_ADDON_PAYSTACK_NAME', 'Sprout Invoices Paystack Payments');
define('SI_ADDON_PAYSTACK_URL', plugins_url('', __FILE__));


// Load up the processor before updates
add_action('si_payment_processors_loaded', 'si_load_paystack');
function si_load_paystack()
{
    include_once 'SI_Paystack.php';
}
