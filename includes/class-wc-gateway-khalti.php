<?php

/**
 * Payment gateway - Khalti
 *
 * Provides an Khalti Payment Gateway.
 *
 * @package WooCommerce_Khalti\Classes\Payment
 */

defined('ABSPATH') || exit;

/**
 * WC_Gateway_Khalti Class.
 */
class WC_Gateway_Khalti extends WC_Payment_Gateway
{
    const LIVE_URL = 'https://khalti.com/api/v2/epayment';
    const TEST_URL = 'https://a.khalti.com/api/v2/epayment';

    /**
     * Whether or not logging is enabled.
     *
     * @var boolean
     */
    public static $log_enabled = false;

    /**
     * A log object returned by wc_get_logger().
     *
     * @var boolean
     */
    public static $log = false;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id = 'khalti';

        add_action(
            'admin_enqueue_scripts',
            array($this, 'admin_scripts')
        );
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array($this, 'process_admin_options')
        );

        $this->icon = apply_filters(
            'woocommerce_khalti_icon',
            plugins_url('assets/images/khalti.png', WC_KHALTI_PLUGIN_FILE)
        );
        $this->has_fields = false;
        $this->order_button_text = __('Proceed to Khalti', 'woocommerce-khalti');
        $this->method_title = __('Khalti', 'woocommerce-khalti');
        $this->method_description = __(
            'Take payments via Khalti - sends customers to Khalti portal to enter their payment information.',
            'woocommerce-khalti'
        );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->testmode = 'yes' === $this->get_option('testmode', 'no');
        $this->debug = 'yes' === $this->get_option('debug', 'no');
        $this->merchant_secret = $this->get_option('merchant_secret');

        // Optional params
        $this->send_customer_info = 'yes' === $this->get_option('send_customer_info', 'no');
        $this->send_amount_breakdown = 'yes' === $this->get_option('send_amount_breakdown', 'no');

        //API endpoints
        $this->payment_request_api_endpoint = self::LIVE_URL . '/initiate';
        $this->payment_lookup_api_endpoint = self::LIVE_URL . '/lookup';

        // Enable logging for events.
        self::$log_enabled = $this->debug;

        if ($this->testmode) {
            $this->description .= ' ' . __('SANDBOX ENABLED. You can use testing accounts only.', 'woocommerce-khalti');
            $this->description  = trim($this->description);
            $this->merchant_secret  = $this->get_option('sandbox_merchant_secret');
            $this->payment_request_api_endpoint = self::TEST_URL . '/initiate';
            $this->payment_lookup_api_endpoint = self::TEST_URL . '/lookup';
        }

        if (!$this->is_valid_for_use()) {
            $this->enabled = 'no';
        } elseif ($this->merchant_secret) {
            include_once dirname(__FILE__) . '/gateways/class-wc-gateway-khalti-ipn-handler.php';

            new WC_Gateway_KHALTI_IPN_Handler($this);
        }
    }

    /**
     * Return whether or not this gateway still requires setup to function.
     *
     * When this gateway is toggled on via AJAX, if this returns true a
     * redirect will occur to the settings page instead.
     *
     * @return bool
     */
    public function needs_setup()
    {
        return empty($this->merchant_secret);
    }

    /**
     * Logging method.
     *
     * @param string $message Log message.
     * @param string $level Optional, defaults to info, valid levels:
     *                      emergency|alert|critical|error|warning|notice|info|debug.
     */
    public static function log($message, $level = 'info')
    {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }

            self::$log->log($level, $message, array('source' => 'khalti'));
        }
    }

    /**
     * Processes and saves options.
     * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
     *
     * @return bool was anything saved?
     */
    public function process_admin_options()
    {
        $saved = parent::process_admin_options();

        // Maybe clear logs.
        if ('yes' !== $this->get_option('debug', 'no')) {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }

            self::$log->clear('Khalti');
        }

        return $saved;
    }

    /**
     * Check if this gateway is enabled and available in the user's country.
     *
     * @return bool
     */
    public function is_valid_for_use()
    {
        return in_array(
            get_woocommerce_currency(),
            apply_filters('woocommerce_khalti_supported_currencies', array('NPR')),
            true
        );
    }

    /**
     * Admin Panel Options.
     * - Options for bits like 'title' and availability on a country-by-country basis.
     *
     */
    public function admin_options()
    {
        if ($this->is_valid_for_use()) {
            parent::admin_options();
        } else {
            $error_notice = '<div class="inline error"><p>';
            $error_notice .= '<strong>' . esc_html_e('Gateway Disabled', 'woocommerce-khalti') . ':</strong>';
            $error_notice .= esc_html_e('Khalti does not support your store currency.', 'woocommerce-khalti');
            $error_notice = '</p></div>';

            echo $error_notice;
        }
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = include 'gateways/settings-khalti.php';
    }

    /**
     * Process the payment and return the result.
     *
     * @param  int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
        include_once dirname(__FILE__) . '/gateways/class-wc-gateway-khalti-request.php';

        $order = wc_get_order($order_id);
        $khalti_request = new WC_Gateway_Khalti_Request($this);

        return array(
            'result' => 'success',
            'redirect' => $khalti_request->get_request_url($order),
        );
    }

    /**
     * Load admin scripts.
     *
     */
    public function admin_scripts()
    {
        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if ('woocommerce_page_wc-settings' !== $screen_id) {
            return;
        }

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_script(
            'woocommerce_khalti_admin',
            plugins_url('/assets/js/khalti-admin' . $suffix . '.js', WC_KHALTI_PLUGIN_FILE),
            array(),
            WC_VERSION,
            true
        );
    }
}
