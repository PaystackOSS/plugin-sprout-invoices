<?php

class sprout_paystack_plugin_tracker {
    var $public_key;
    var $plugin_name;
    function __construct($plugin, $pk){
        //configure plugin name
        //configure public key
        $this->plugin_name = $plugin;
        $this->public_key = $pk;
    }

   

    function log_transaction_success($trx_ref){
        //send reference to logger along with plugin name and public key
        $url = "https://plugin-tracker.paystackintegrations.com/log/charge_success";

        $fields = [
            'plugin_name'  => $this->plugin_name,
            'transaction_reference' => $trx_ref,
            'public_key' => $this->public_key
        ];

        $fields_string = http_build_query($fields);

        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 

        //execute post
        $result = curl_exec($ch);
        //  echo $result;
    }
}

class SI_Paystack extends SI_Credit_Card_Processors
{
    const MODE_TEST = 'test';
    const MODE_LIVE = 'live';
    const MODAL_JS_OPTION = 'si_use_paystack_js_modal';
    const DISABLE_JS_OPTION = 'si_use_paystack_js';
    const API_SECRET_KEY_OPTION = 'si_paystack_secret_key';
    const API_SECRET_KEY_TEST_OPTION = 'si_paystack_secret_key_test';
    const API_PUB_KEY_OPTION = 'si_paystack_pub_key';
    const API_PUB_KEY_TEST_OPTION = 'si_paystack_pub_key_test';

    const PAYSTACK_CUSTOMER_KEY_USER_META = 'si_paystack_customer_id_v1';
    const TOKEN_INPUT_NAME = 'paystack_charge_token';

    const API_MODE_OPTION = 'si_paystack_mode';
    const CURRENCY_CODE_OPTION = 'si_Paystack_currency';
    const PAYMENT_METHOD = 'Debit & Credit Card (Paystack)';
    const PAYMENT_SLUG = 'paystack';
    const TOKEN_KEY = 'si_token_key'; // Combine with $blog_id to get the actual meta key
    const PAYER_ID = 'si_payer_id'; // Combine with $blog_id to get the actual meta key


    const UPDATE = 'paystack_version_upgrade_v1';

    protected static $instance;
    protected static $api_mode = self::MODE_TEST;
    private static $payment_modal;
    private static $disable_paystack_js;
    private static $api_secret_key_test;
    private static $api_pub_key_test;
    private static $api_secret_key;
    private static $api_pub_key;
    private static $currency_code = 'NGN';

    public static function get_instance()
    {
        if (! (isset(self::$instance) && is_a(self::$instance, __CLASS__))) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function is_test()
    {
        return self::MODE_TEST === self::$api_mode;
    }

    public function get_payment_method()
    {
        return self::PAYMENT_METHOD;
    }

    public function get_slug()
    {
        return self::PAYMENT_SLUG;
    }

    public static function register()
    {

        // Register processor
        self::add_payment_processor(__CLASS__, __('Paystack', 'sprout-invoices'));

        // Enqueue Scripts
        if (apply_filters('si_remove_scripts_styles_on_doc_pages', '__return_true')) {
            // enqueue after enqueue is filtered
            add_action('si_doc_enqueue_filtered', array( __CLASS__, 'enqueue' ));
        } else { // enqueue normal
            add_action('wp_enqueue_scripts', array( __CLASS__, 'enqueue' ));
        }
    }

    public static function public_name()
    {
        return __('Debit & Credit Card', 'sprout-invoices');
    }

    public static function checkout_options()
    {
        $option = array(
            'icons' => array(
                SI_URL . '/resources/front-end/img/visa.png',
                SI_URL . '/resources/front-end/img/mastercard.png',
                SI_URL . '/resources/front-end/img/amex.png',
                SI_URL . '/resources/front-end/img/discover.png',
                ),
            'label' => __('Debit & Credit Card', 'sprout-invoices'),
            'accepted_cards' => array(
                'visa',
                'mastercard',
                'amex',
                'verve',
                'discover',
                // 'jcb',
                // 'maestro'
                ),
            );
        if (self::$payment_modal) {
            $option['purchase_button_callback'] = array( __CLASS__, 'payment_button' );
        }
        return $option;
    }

    protected function __construct()
    {
        parent::__construct();
        self::$api_mode = get_option(self::API_MODE_OPTION, self::MODE_TEST);
        self::$payment_modal = get_option(self::MODAL_JS_OPTION, true);
        self::$disable_paystack_js = get_option(self::DISABLE_JS_OPTION, false);
        self::$currency_code = get_option(self::CURRENCY_CODE_OPTION, 'NGN');

        self::$api_secret_key = get_option(self::API_SECRET_KEY_OPTION, '');
        self::$api_pub_key = get_option(self::API_PUB_KEY_OPTION, '');
        self::$api_secret_key_test = get_option(self::API_SECRET_KEY_TEST_OPTION, '');
        self::$api_pub_key_test = get_option(self::API_PUB_KEY_TEST_OPTION, '');

        // Remove pages
        add_filter('si_checkout_pages', array( $this, 'remove_checkout_pages' ));

        if (! self::$disable_paystack_js) {
            add_filter('si_valid_process_payment_page_fields', '__return_false');
        }
    }

    /**
     * The review page is unnecessary
     *
     * @param  array $pages
     * @return array
     */
    public function remove_checkout_pages($pages)
    {
        unset($pages[ SI_Checkouts::REVIEW_PAGE ]);
        return $pages;
    }

    /**
     * Hooked on init add the settings page and options.
     */
    public static function register_settings()
    {

        // Settings
        $settings['payments'] = array(
            'si_paystack_settings' => array(
                'title' => __('Paystack Settings', 'sprout-invoices'),
                'weight' => 200,
                'settings' => array(
                    self::API_MODE_OPTION => array(
                        'label' => __('Mode', 'sprout-invoices'),
                        'option' => array(
                            'type' => 'radios',
                            'options' => array(
                                self::MODE_LIVE => __('Live', 'sprout-invoices'),
                                self::MODE_TEST => __('Test', 'sprout-invoices'),
                                ),
                            'default' => self::$api_mode,
                            ),
                        ),
                    self::API_SECRET_KEY_OPTION => array(
                        'label' => __('Live Secret Key', 'sprout-invoices'),
                        'option' => array(
                            'type' => 'text',
                            'default' => self::$api_secret_key,
                            ),
                        ),
                    self::API_PUB_KEY_OPTION => array(
                        'label' => __('Live Public Key', 'sprout-invoices'),
                        'option' => array(
                            'type' => 'text',
                            'default' => self::$api_pub_key,
                            ),
                        ),
                    self::API_SECRET_KEY_TEST_OPTION => array(
                        'label' => __('Test Secret Key', 'sprout-invoices'),
                        'option' => array(
                            'type' => 'text',
                            'default' => self::$api_secret_key_test,
                            ),
                        ),
                    self::API_PUB_KEY_TEST_OPTION => array(
                        'label' => __('Test Public Key', 'sprout-invoices'),
                        'option' => array(
                            'type' => 'text',
                            'default' => self::$api_pub_key_test,
                            ),
                        ),
                    self::CURRENCY_CODE_OPTION => array(
                        'label' => __('Currency Code', 'sprout-invoices'),
                        'option' => array(
                            'type' => 'text',
                            'default' => self::$currency_code,
                            'attributes' => array( 'class' => 'small-text' ),
                            ),
                        ),
                    ),
                ),
            );
        return $settings;
    }

    ///////////////////
    // Payment Modal //
    ///////////////////

    public static function payment_button($invoice_id = 0)
    {
        if (! $invoice_id) {
            $invoice_id = get_the_id();
        }
        $invoice = SI_Invoice::get_instance($invoice_id);

        // print_r($invoice);
        $user = si_who_is_paying($invoice);
        $user_email = ($user) ? $user->user_email : '' ;

        $key = (self::$api_mode === self::MODE_TEST) ? self::$api_pub_key_test : self::$api_pub_key ;
        $payment_amount = (si_has_invoice_deposit($invoice->get_id())) ? $invoice->get_deposit() : $invoice->get_balance();

        $data_attributes = array(
                'key' => $key,
                'email' => $user_email,
                'currency' => self::get_currency_code($invoice_id),
                'amount' => self::convert_money_to_cents($payment_amount),
                'ref' => $invoice_id.'_'.time(),
                'metadata' => json_encode(array('custom_fields' => array(
                    array(
                        "display_name"=>"Plugin",
                        "variable_name"=>"plugin",
                        "value"=>"sprout-invoice"
                    )
                )) )

            );

        $data_attributes = apply_filters('si_paystack_js_data_attributes', $data_attributes, $invoice_id); ?>

            <form action="<?php echo add_query_arg(array( SI_Checkouts::CHECKOUT_ACTION => SI_Checkouts::PAYMENT_PAGE ), si_get_credit_card_checkout_form_action()) ?>" method="POST" class="button" id="paystack_pop_form">
                <input type="hidden" name="<?php echo SI_Checkouts::CHECKOUT_ACTION ?>" value="<?php echo SI_Checkouts::PAYMENT_PAGE ?>" />
                <script
                    src="https://js.paystack.co/v1/inline.js" 
                    <?php foreach ($data_attributes as $attribute => $value) : ?>
                        data-<?php echo esc_js($attribute) ?>="<?php echo esc_js($value) ?>"
                    <?php endforeach ?>
                ></script>
            </form>
            <style type="text/css">
                #payment_selection.dropdown {
                    z-index: 9999;
                }
                #paystack_pop_form.button {
                    float: right;
                    padding: 0;
                    margin-top: -6px;
                    margin-left: 10px;
                    background-color: transparent;
                }
                #payment_selection.dropdown #paystack_pop_form.button {
                    float: none;
                    padding: inherit;
                    margin-top: 0px;
                    margin-right: 15px;
                    margin-bottom: 15px;
                    text-align: right;
                }
            </style>
        <?php
    }


    public function process_payment(SI_Checkouts $checkout, SI_Invoice $invoice)
    {

        // print_r($_POST);
        $reference = $_POST['reference'];
        $key = (self::$api_mode === self::MODE_TEST) ? self::$api_secret_key_test : self::$api_secret_key ;
        $paystack_url = 'https://api.paystack.co/transaction/verify/' . $reference;
        $headers = array(
            'Authorization' => 'Bearer ' . $key,
        );
        $args = array(
            'headers'    => $headers,
            'timeout'    => 60,
        );
        $request = wp_remote_get($paystack_url, $args);
        if (! is_wp_error($request) && 200 == wp_remote_retrieve_response_code($request)) {
            $paystack_response = json_decode(wp_remote_retrieve_body($request));
            if ('success' == $paystack_response->data->status) {
                // $payment_amount = ( si_has_invoice_deposit( $invoice->get_id() ) ) ? $invoice->get_deposit() : $invoice->get_balance();
                
                
                //PSTK Logger
                $pk = (self::$api_mode === self::MODE_TEST) ? self::$api_pub_key_test : self::$api_pub_key ;
                $pstk_logger = new sprout_paystack_plugin_tracker('sprout-invoice',$pk);
                $pstk_logger->log_transaction_success($reference);
               
                //----------------------------------------------------------------
                $amount_paid    = $paystack_response->data->amount / 100;

                // create new payment
                $payment_id = SI_Payment::new_payment(
                    array(
                    'payment_method' => self::get_payment_method(),
                    'invoice' => $invoice->get_id(),
                    'amount' => $amount_paid,
                    'data' => array(
                        'Status' => 'Successful',
                        'Transaction Reference' => $reference,
                     ),
                    ),
                    SI_Payment::STATUS_AUTHORIZED
                );
                if (! $payment_id) {
                    return false;
                }
                $payment = SI_Payment::get_instance($payment_id);
                do_action('payment_authorized', $payment);
                $payment->set_status(SI_Payment::STATUS_COMPLETE);
                do_action('payment_complete', $payment);

                return $payment;
            }
        }

        // $this->maybe_create_recurring_payment_profiles( $invoice, $payment );
    }

    public static function set_token($token)
    {
        global $blog_id;
        update_user_meta(get_current_user_id(), $blog_id.'_'.self::TOKEN_KEY, $token);
    }

    public static function unset_token()
    {
        global $blog_id;
        delete_user_meta(get_current_user_id(), $blog_id.'_'.self::TOKEN_KEY);
    }

    public static function get_token()
    {
        if (isset($_REQUEST['token']) && $_REQUEST['token']) {
            return $_REQUEST['token'];
        }
        global $blog_id;
        return get_user_meta(get_current_user_id(), $blog_id.'_'.self::TOKEN_KEY, true);
    }




    private static function get_currency_code($invoice_id)
    {
        return apply_filters('si_currency_code', self::$currency_code, $invoice_id, self::PAYMENT_METHOD);
    }


    //////////////
    // Utility //
    //////////////

    private static function convert_money_to_cents($value)
    {

        // strip out commas
        $value = preg_replace('/\,/i', '', $value);
        // strip out all but numbers, dash, and dot
        $value = preg_replace('/([^0-9\.\-])/i', '', $value);
        // make sure we are dealing with a proper number now, no +.4393 or 3...304 or 76.5895,94
        if (! is_numeric($value)) {
            return 0.00;
        }
        // convert to a float explicitly
        $value = (float) $value;
        return round($value, 2) * 100;
    }

    private static function convert_cents_to_money($value)
    {

        // strip out commas
        $value = preg_replace('/\,/i', '', $value);
        // strip out all but numbers, dash, and dot
        $value = preg_replace('/([^0-9\.\-])/i', '', $value);
        // make sure we are dealing with a proper number now, no +.4393 or 3...304 or 76.5895,94
        if (! is_numeric($value)) {
            return 0.00;
        }
        // convert to a float explicitly
        return number_format(floatval($value / 100), 2);
    }

    /**
     * Grabs error messages from a Paystack response and displays them to the user
     *
     * @param  array $response
     * @param  bool  $display
     * @return void
     */
    private function set_error_messages($message, $display = true)
    {
        if ($display) {
            self::set_message($message, self::MESSAGE_STATUS_ERROR);
        } else {
            do_action('si_error', __CLASS__ . '::' . __FUNCTION__ . ' - error message from paystack', $message);
        }
    }
    /**
     * Process Webhook
     */
    public function process_webhooks()
    {
        if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') || ! array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER)) {
            exit;
        }

        $json = file_get_contents('php://input');

        // validate event do all at once to avoid timing attack
        if ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $json, $this->secret_key)) {
            exit;
        }

        $event = json_decode($json);

        if ('charge.success' == $event->event) {
            http_response_code(200);

            $order_details         = explode('_', $event->data->reference);

            $order_id             = (int) $order_details[0];

            $order                 = wc_get_order($order_id);

            $paystack_txn_ref     = get_post_meta($order_id, '_paystack_txn_ref', true);

            if ($event->data->reference != $paystack_txn_ref) {
                exit;
            }

            if (in_array($order->get_status(), array( 'processing', 'completed', 'on-hold' ))) {
                exit;
            }

            $order_total    = $order->get_total();

            $amount_paid    = $event->data->amount / 100;

            $paystack_ref     = $event->data->reference;

             //PSTK Logger
             $pk = (self::$api_mode === self::MODE_TEST) ? self::$api_pub_key_test : self::$api_pub_key ;
             $pstk_logger_ = new sprout_paystack_plugin_tracker('sprout-invoice',$pk);
             $pstk_logger_->log_transaction_success($paystack_ref);
            
             //----------------------------------------------------------------
            // check if the amount paid is equal to the order amount.
            if ($order_total != $amount_paid) {
                $order->update_status('on-hold', '');

                add_post_meta($order_id, '_transaction_id', $paystack_ref, true);

                $notice = 'Thank you for shopping with us.<br />Your payment transaction was successful, but the amount paid is not the same as the total order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
                $notice_type = 'notice';

                // Add Customer Order Note
                $order->add_order_note($notice, 1);

                // Add Admin Order Note
                $order->add_order_note('<strong>Look into this order</strong><br />This order is currently on hold.<br />Reason: Amount paid is less than the total order amount.<br />Amount Paid was <strong>&#8358;'.$amount_paid.'</strong> while the total order amount is <strong>&#8358;'.$order_total.'</strong><br />Paystack Transaction Reference: '.$paystack_ref);

                $order->reduce_order_stock();

                wc_add_notice($notice, $notice_type);

                wc_empty_cart();
            } else {
                $order->payment_complete($paystack_ref);

                $order->add_order_note(sprintf('Payment via Paystack successful (Transaction Reference: %s)', $paystack_ref));

                wc_empty_cart();
            }

            $this->save_card_details($event, $order->get_user_id(), $order_id);

            exit;
        }

        exit;
    }
}
SI_Paystack::register();
