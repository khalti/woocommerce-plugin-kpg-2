<?php

/**
 * Handles responses from Khalti IPN.
 *
 * @package WooCommerce_Khalti\Classes\Payment
 */

defined('ABSPATH') || exit;

require_once dirname(__FILE__) . '/class-wc-gateway-khalti-response.php';

/**
 * WC_Gateway_Khalti_IPN_Handler class.
 */
class WC_Gateway_Khalti_IPN_Handler extends WC_Gateway_Khalti_Response
{
    /**
     * Constructor.
     *
     * @param WC_Gateway_Khalti $gateway Gateway class.
     */
    public function __construct($gateway)
    {
        add_action(
            'woocommerce_api_wc_gateway_khalti',
            array($this, 'check_response')
        );
        add_action(
            'valid_khalti_standard_ipn_request',
            array($this, 'valid_response')
        );

        $this->gateway = $gateway;
    }

    /**
     * Check for Khalti IPN Response.
     */
    public function check_response()
    {
        $required_params = ['pidx', 'amount', 'mobile', 'purchase_order_id', 'purchase_order_name'];
        $missing_params = [];

        array_push(
            $required_params,
            isset($_REQUEST['transaction_id']) ? 'transaction_id' : 'idx'
        );

        foreach ($required_params as $param) {
            if (!isset($_REQUEST[$param])) {
                array_push($missing_params, $param);
            }
        }

        if (count($missing_params) == 0) { // WPCS: CSRF ok.
            WC_Gateway_Khalti::log('IPN Response: ' . wc_print_r($_REQUEST, true));

            $requested = wp_unslash($_REQUEST); // WPCS: CSRF ok, input var ok.

            if ($verification_response = $this->validate_payment($requested)) {
                do_action(
                    'valid_khalti_standard_ipn_request',
                    $verification_response
                );

                exit;
            }

            wp_die(
                'Khalti Payment Verification Failure',
                'Khalti IPN',
                array('response' => 500)
            );
        }

        WC_Gateway_Khalti::log(
            'Parameter ' . implode(', ', $missing_params) . ' missing in response'
        );

        wp_die(
            'Khalti Request Failure',
            'Khalti IPN',
            array('response' => 500)
        );
    }

    /**
     * Check Khalti response validity.
     */
    public function validate_payment($requested)
    {
        WC_Gateway_Khalti::log('Validating IPN response');

        $order = $this->get_khalti_order($requested['purchase_order_name']);

        if (!$order) {
            return false;
        }

        $response = wp_remote_post(
            $this->gateway->payment_lookup_api_endpoint,
            [
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Key ' . $this->gateway->merchant_secret
                ],
                'body' => json_encode(['pidx' => $requested['pidx']])
            ]
        );

        if (is_wp_error($response)) {
            WC_Gateway_Khalti::log(
                'Khalti Payment Verification Error: ' . $response->get_error_message(),
                'error'
            );

            return false;
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        if (isset($response_body['status']) && $response_body['status'] == 'Completed') {
            WC_Gateway_Khalti::log('Payment Verification: Successful');

            $response_body['order_key'] = $requested['purchase_order_name'];

            return $response_body;
        }

        WC_Gateway_Khalti::log('Payment Verification: Failed');

        return false;
    }

    /**
     * There was a valid response.
     *
     * @param array    $response 	Verification response data
     */
    public function valid_response($response)
    {
        $order = $this->get_khalti_order($response['order_key']);

        if ($order) {
            WC_Gateway_Khalti::log('Found order #' . $order->get_id());

            // Lowercase returned variables.
            $payment_status = strtolower($response['status']);
            $payment_status =  in_array(
                $payment_status,
                ['completed', 'failed', 'cancelled', 'refunded']
            ) ? $payment_status : 'failed';

            WC_Gateway_Khalti::log('Payment status: ' . $payment_status);

            if (method_exists($this, "payment_status_$payment_status")) {
                call_user_func(
                    array($this, "payment_status_$payment_status"),
                    $order,
                    $response
                );

                wp_safe_redirect(
                    esc_url_raw(add_query_arg(
                        'utm_nooverride',
                        '1',
                        $this->gateway->get_return_url($order)
                    ))
                );

                exit;
            }
        }
    }

    /**
     * Handle a completed payment.
     *
     * @param WC_Order $order     	Order object.
     * @param array    $response 	Verification response data.
     */
    protected function payment_status_completed($order, $response)
    {
        if ($order->has_status(wc_get_is_paid_statuses())) {
            WC_Gateway_Khalti::log('Aborting, Order #' . $order->get_id() . ' is already complete.');

            wp_die(
                'Order #' . $order->get_id() . ' is already complete.',
                'Khalti IPN',
                array('response' => 500)
            );
        }

        if ($order->has_status('cancelled')) {
            $this->payment_status_paid_cancelled_order($order);
        }

        $txn_id = isset($response['transaction_id'])
            ? wc_clean($response['transaction_id'])
            : wc_clean($response['idx']);

        $this->payment_complete(
            $order,
            $txn_id,
            __('IPN payment completed', 'woocommerce-khalti')
        );

        // Log Khalti Reference Code.
        if ($txn_id) {
            update_post_meta(
                $order->get_id(),
                '_khalti_pidx',
                wc_clean($response['pidx'])
            );

            update_post_meta(
                $order->get_id(),
                '_khalti_txn_id',
                $txn_id
            );
        }
    }

    /**
     * Handle a failed payment.
     *
     * @param WC_Order $order     	Order object.
     * @param array    $response 	Verification response data.
     */
    protected function payment_status_failed($order, $response)
    {
        /* translators: %s: payment status */
        $order->update_status(
            'failed',
            sprintf(__('Payment %s via IPN.', 'woocommerce-khalti'), 'failed')
        );
    }

    /**
     * Handle a refunded payment.
     *
     * @param WC_Order $order     	Order object.
     * @param array    $response 	Verification response data.
     */
    protected function payment_status_refunded($order, $response)
    {
        /* translators: %s: payment status */
        $order->update_status(
            'refunded',
            sprintf(__('Payment %s via IPN.', 'woocommerce-khalti'), 'refunded')
        );
    }

    /**
     * When a user cancelled order is marked paid.
     *
     * @param WC_Order $order 	Order object.
     */
    protected function payment_status_paid_cancelled_order($order)
    {
        if (version_compare(WC_VERSION, '3.3.0', '>')) {
            $this->send_ipn_email_notification(
                /* translators: %s: order link. */
                sprintf(
                    __(
                        'Payment for cancelled order %s received',
                        'woocommerce-khalti'
                    ),
                    '<a class="link" href="' . esc_url($order->get_edit_order_url()) . '">' . $order->get_order_number() . '</a>'
                ),
                /* translators: %s: order ID. */
                sprintf(
                    __(
                        'Order #%s has been marked paid by Khalti IPN, but was previously cancelled. Admin handling required.',
                        'woocommerce-khalti'
                    ),
                    $order->get_order_number()
                )
            );
        }
    }

    /**
     * Send a notification to the user handling orders.
     *
     * @param string $subject Email subject.
     * @param string $message Email message.
     */
    protected function send_ipn_email_notification($subject, $message)
    {
        $new_order_settings = get_option('woocommerce_new_order_settings', array());
        $mailer = WC()->mailer();
        $message = $mailer->wrap_message($subject, $message);
        $woocommerce_khalti_settings = get_option('woocommerce_khalti_settings');

        if (
            !empty($woocommerce_khalti_settings['ipn_notification'])
            && 'no' === $woocommerce_khalti_settings['ipn_notification']
        ) {
            return;
        }

        $mailer->send(
            !empty($new_order_settings['recipient'])
                ? $new_order_settings['recipient']
                : get_option('admin_email'),
            strip_tags($subject),
            $message
        );
    }
}
