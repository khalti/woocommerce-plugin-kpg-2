<?php

/**
 * Plugin Name: Integrate Khalti in WooCommerce
 * Plugin URI: https://github.com/act360/khalti-for-woocommerce
 * Description: Integrate Khalti in WooCommerce is a plugin that enables payment via Khalti in WooCommerce shop in Nepal.
 * Version: 1.0
 * Author: ACT360
 * Author URI: https://www.act360.com.np
 * Text Domain: woocommerce-khalti
 * Domain Path: /languages
 *
 * WC requires at least: 4.5.0
 * WC tested up to: 6.0.1
 *
 * @package Khalti-WooCommerce
 */

defined('ABSPATH') || exit;

// Define WC_KHALTI_PLUGIN_FILE.
if (!defined('WC_KHALTI_PLUGIN_FILE')) {
    define('WC_KHALTI_PLUGIN_FILE', __FILE__);
}

// Include the main WooCommerce Khalti class.
if (!class_exists('WooCommerce_Khalti')) {
    include_once dirname(__FILE__) . '/includes/class-woocommerce-khalti.php';
    include_once dirname(__FILE__) . '/includes/class-woocommerce-khalti-data.php';
}

// Initialize the plugin.
add_action('plugins_loaded', array('WooCommerce_Khalti', 'get_instance'));
add_action('plugins_loaded', array('WooCommerce_Khalti_Data', 'get_instance'));
