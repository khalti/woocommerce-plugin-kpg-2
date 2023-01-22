<?php

/**
 * Abstract class for handling Khalti IPN responses.
 *
 * @package WooCommerce_Khalti\Abstracts
 */

defined('ABSPATH') || exit;

/**
 * WC_Gateway_Khalti_Response class.
 */
abstract class WC_Gateway_Khalti_Response
{

    /**
     * Sandbox mode.
     *
     * @var boolean
     */
    protected $sandbox = false;

    /**
     * Get the order from the Khalti Order ID and Key variable.
     *
     * @param  string $order_key Order key.
     * @return bool|WC_Order object
     */
    protected function get_khalti_order($order_key)
    {
        if ($order_id = wc_get_order_id_by_order_key($order_key)) {

            if ($order = wc_get_order($order_id)) {
                return $order;
            }

            WC_Gateway_Khalti::log(
                "Order not found for order id: $order_id.",
                'error'
            );

            return false;
        }

        WC_Gateway_Khalti::log(
            "Order id not found for order key: $order_key.",
            'error'
        );

        return false;
    }

    /**
     * Complete order, add transaction ID and note.
     *
     * @param WC_Order $order Order object.
     * @param string   $txn_id Transaction ID.
     * @param string   $note Payment note.
     */
    protected function payment_complete($order, $txn_id = '', $note = '')
    {
        $order->update_status('processing', $note, 'success');
        $order->payment_complete($txn_id);

        WC()->cart->empty_cart();
    }

    /**
     * Hold order and add note.
     *
     * @param WC_Order $order Order object.
     * @param string   $reason Reason why the payment is on hold.
     */
    protected function payment_on_hold($order, $reason = '')
    {
        $order->update_status('on-hold', $reason);
        $order->reduce_order_stock();

        WC()->cart->empty_cart();
    }
}
