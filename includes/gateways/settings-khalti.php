<?php

/**
 * Payment Gateway: Settings - Khalti.
 *
 * @package WooCommerce_Khalti\Classes\Payment
 */

defined('ABSPATH') || exit;

/**
 * Settings for Khalti Gateway.
 */
return array(
    'enabled' => array(
        'title'   => __('Enable/Disable', 'woocommerce-khalti'),
        'type'    => 'checkbox',
        'label'   => __('Enable Khalti Payment', 'woocommerce-khalti'),
        'default' => 'yes',
    ),
    'title' => array(
        'title'       => __('Title', 'woocommerce-khalti'),
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __(
            'This controls the title which the user sees during checkout.',
            'woocommerce-khalti'
        ),
        'default'     => __('Khalti', 'woocommerce-khalti'),
    ),
    'description' => array(
        'title'       => __('Description', 'woocommerce-khalti'),
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __(
            'This controls the description which the user sees during checkout.',
            'woocommerce-khalti'
        ),
        'default'     => __(
            'Pay via Khalti; you can pay with Khalti securely.',
            'woocommerce-khalti'
        ),
    ),
    'merchant_secret' => array(
        'title'       => __('Live merchant secret', 'woocommerce-khalti'),
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __(
            'Please enter your live Khalti merchant secret; this is needed in order to take payment.',
            'woocommerce-khalti'
        ),
        'default'     => '',
    ),
    'sandbox_merchant_secret' => array(
        'title'       => __('Test Merchant secret', 'woocommerce-khalti'),
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __(
            'Please enter your test Khalti merchant secret; this is needed inorder to test payment.',
            'woocommerce-khalti'
        ),
        'default'     => '',
    ),
    'advanced'             => array(
        'title'       => __('Advanced options', 'woocommerce-khalti'),
        'type'        => 'title',
        'description' => '',
    ),
    'testmode'             => array(
        'title'       => __('Sandbox mode', 'woocommerce-khalti'),
        'type'        => 'checkbox',
        'label'       => __('Enable Sandbox Mode', 'woocommerce-khalti'),
        'default'     => 'no',
        /* translators: %s: Khalti contact page */
        'description' => sprintf(
            __(
                'Enable Khalti sandbox to test payments. Please contact Khalti Merchant/Service Provider for a <a href="%s" target="_blank">developer account</a>.',
                'woocommerce-khalti'
            ),
            'https://www.khalti.com/contact'
        ),
    ),
    'send_customer_info'    => array(
        'title'       => __('Send customer info', 'woocommerce-khalti'),
        'type'        => 'checkbox',
        'label'       => __('Send customer info to Khalti', 'woocommerce-khalti'),
        'default'     => 'yes',
    ),
    'send_amount_breakdown'  => array(
        'title'       => __('Send amount breakdown', 'woocommerce-khalti'),
        'type'        => 'checkbox',
        'label'       => __(
            'Send total amount breakdown to Khalti',
            'woocommerce-khalti'
        ),
        'default'     => 'yes',
    ),
    'debug'                => array(
        'title'       => __('Debug log', 'woocommerce-khalti'),
        'type'        => 'checkbox',
        'label'       => __('Enable logging', 'woocommerce-khalti'),
        'default'     => 'no',
        /* translators: %s: Khalti log file path */
        'description' => sprintf(
            __(
                'Log Khalti events, such as IPN requests, inside <code>%s</code>',
                'woocommerce-khalti'
            ),
            wc_get_log_file_path('khalti')
        ),
    ),
    'ipn_notification'     => array(
        'title'       => __('IPN email notifications', 'woocommerce-khalti'),
        'type'        => 'checkbox',
        'label'       => __('Enable IPN email notifications', 'woocommerce-khalti'),
        'default'     => 'yes',
        'description' => __(
            'Send email when an IPN is received from Khalti for cancelled order.',
            'woocommerce-khalti'
        ),
    ),
);
