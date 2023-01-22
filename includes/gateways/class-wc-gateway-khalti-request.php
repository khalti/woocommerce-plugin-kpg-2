<?php

/**
 * Generates requests to send to Khalti
 *
 * @package WooCommerce_Khalti\Classes\Payment
 */

defined('ABSPATH') || exit;

/**
 * WC_Gateway_Khalti_Request class.
 */
class WC_Gateway_Khalti_Request
{

    /**
     * Pointer to gateway making the request.
     *
     * @var WC_Gateway_Khalti
     */
    protected $gateway;

    /**
     * Endpoint for requests from Khalti.
     *
     * @var string
     */
    protected $notify_url;

    /**
     * Constructor.
     *
     * @param WC_Gateway_Khalti $gateway Gateway class.
     */
    public function __construct($gateway)
    {
        $this->gateway    = $gateway;
        $this->notify_url = WC()->api_request_url('WC_Gateway_Khalti');
    }

    /**
     * Get the Khalti request URL for an order.
     *
     * @param  WC_Order $order   Order object.
     * @return string
     */
    public function get_request_url($order)
    {
        $args = $this->get_khalti_args($order);
        $order_id = $order->get_order_number();

        WC_Gateway_Khalti::log(
            "Khalti Request Args for order : $order_id" . wc_print_r($args, true)
        );

        $response = wp_remote_post(
            $this->gateway->payment_request_api_endpoint,
            [
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Key ' . $this->gateway->merchant_secret
                ],
                'body' => json_encode($args)
            ]
        );

        if ($this->api_response_is_ok($order_id, $response)) {
            $response_body = wp_remote_retrieve_body($response);
            $response_body = json_decode($response_body, true);
            $redirect_to = $response_body['payment_url'];

            return $redirect_to;
        }

        return false;
    }

    /**
     * Get Khalti Args for passing to Khalti.
     *
     * @param  WC_Order $order Order object.
     * @return array
     */
    protected function get_khalti_args($order)
    {
        WC_Gateway_Khalti::log(
            'Generating payment form for order '
                . $order->get_order_number()
                . '. Notify URL: '
                . $this->notify_url
        );

        $args = [
            'return_url' => $this->notify_url,
            'website_url' => get_site_url(),
            'amount' => $order->get_total() * 100,
            'purchase_order_id' => $order->get_id(),
            'purchase_order_name' => $order->get_order_key(),
            'product_details' => $this->get_products_info($order)
        ];

        if ($this->gateway->send_customer_info) {
            $args['customer_info'] = $this->get_customer_info($order->data['billing']);
        }

        if ($this->gateway->send_amount_breakdown) {
            $args['amount_breakdown'] = $this->get_amount_breakdown($order);
        }

        return $args;
    }

    /**
     * Get customer information
     *
     * @param  $billing array.
     * @return array
     */
    private function get_customer_info($billing)
    {
        return array(
            "name" => $billing['first_name'] . ' ' . $billing['last_name'],
            "email" => $billing['email'],
            "phone" => $billing['phone']
        );
    }

    /**
     * Get order amount breakdown information
     *
     * @param  WC_ORDER $order object.
     * @return array
     */
    private function get_amount_breakdown($order)
    {
        $sub_total = ($order->data['total'] + $order->data['discount_total'])
            - ($order->data['shipping_total'] + $order->data['total_tax']);

        $amount_breakdown = [
            [
                "label" => "Mark Price",
                "amount" => $sub_total * 100
            ],
            [
                "label" => "Shipping Charge",
                "amount" => $order->data['shipping_total'] * 100
            ],
            [
                "label" => "VAT",
                "amount" => $order->data['total_tax'] * 100
            ]
        ];

        if ($order->data['discount_total'] > 0) {
            $amount_breakdown[] = [
                "label" => "Discount",
                "amount" => -$order->data['discount_total'] * 100
            ];
        }

        return $amount_breakdown;
    }

    /**
     * Get each product information in an order
     *
     * @param  WC_ORDER $order object.
     * @return array
     */
    private function get_products_info($order)
    {
        $product_info = array();
        $bundle_ids = array();
        $skipped_product_ids = array();

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $product_id = $product->get_id();

            if (function_exists('wc_pb_get_bundled_product_map')) {
                $product_id = $product->get_id();

                if ($product->is_type('bundle')) {
                    array_push($bundle_ids, $product_id);

                    $product_info[] = $this->get_product_details($item, $product);
                } elseif ($this->product_belongs_to_bundle_in_cart($bundle_ids, $skipped_product_ids, $product)) {
                    array_push($skipped_product_ids, $product_id);
                } else {
                    $product_info[] = $this->get_product_details($item, $product);
                }
            } else {
                $product_info[] = $this->get_product_details($item, $product);
            }
        }

        return $product_info;
    }

    /*
    *Checks if an item belongs to bundle item which is in the cart already
    *This is because even bundle items are treated as individual item in order loop
    */
    private function product_belongs_to_bundle_in_cart($bundle_ids, $skipped_product_ids, $product)
    {
        $skipped_count = array_count_values($skipped_product_ids);
        $product_id = $product->get_id();

        if ($product_bundle_ids = wc_pb_get_bundled_product_map($product)) {
            foreach ($product_bundle_ids as $bundle_id) {
                if (in_array($bundle_id, $bundle_ids) && ($skipped_count[$product_id] < count($product_bundle_ids))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get product detail of an item.
     *
     * @param  $item object.
     * @param  $prodcut object.
     * @return array
     */
    private function get_product_details($item, $product)
    {
        return array(
            "identity" => $product->id,
            "name" => $product->name,
            "quantity" => $item->get_quantity(),
            "unit_price" => round($item->get_total(), 2) / $item->get_quantity() * 100,
            "total_price" => round($item->get_total(), 2) * 100
        );
    }

    /**
     * Check if api response is ok or notify on fail.
     *
     * @param  $order_id id Order object.
     * @param  $response array|WP_Error object.
     * @return boolean
     */
    private function api_response_is_ok($order_id, $response)
    {
        $timezone = get_option('timezone_string');
        date_default_timezone_set($timezone ? $timezone : 'Asia/Kathmandu');

        if (is_wp_error($response)) {
            WC_Gateway_Khalti::log(
                'Khalti Request Error: ' . $response->get_error_message(),
                'error'
            );

            wp_mail(
                get_option('admin_email'),
                "Khalti API Request Error for order id: $order_id at " . date('Y-m-d H:i A'),
                $response->get_error_message()
            );

            return false;
        }

        if (wp_remote_retrieve_response_code($response) != 200) {
            WC_Gateway_Khalti::log(
                'Khalti Request Error: ' . wp_remote_retrieve_body($response),
                'error'
            );

            wp_mail(
                get_option('admin_email'),
                "Khalti API Request Error for order id: $order_id at " . date('Y-m-d H:i A'),
                wp_remote_retrieve_body($response)
            );

            return false;
        }

        return true;
    }
}
