<?php

/**
 * WooCommerce Khalti setup
 *
 * @package WooCommerce_Khalti
 */

defined('ABSPATH') || exit;

/**
 * Main WooCommerce Khalti Class.
 *
 * @class WooCommerce_Khalti
 */
final class WooCommerce_Khalti
{

    /**
     * Plugin version.
     *
     * @var string
     */
    const VERSION = '1.0';

    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Initialize the plugin.
     */
    private function __construct()
    {
        // Load plugin text domain.
        add_action('init', array($this, 'load_plugin_textdomain'));

        // Checks with WooCommerce is installed.
        if (defined('WC_VERSION') && version_compare(WC_VERSION, '3.6', '>=')) {
            $this->includes();

            // Hooks.
            add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
            add_filter(
                'plugin_action_links_' . plugin_basename(WC_KHALTI_PLUGIN_FILE),
                array($this, 'plugin_action_links')
            );

            // add_action('add_meta_boxes', array($this, 'add_custom_meta_boxes'));
        } else {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
        }
    }

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance()
    {
        // If the single instance hasn't been set, set it now.
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present.
     *
     * Locales found in:
     *      - WP_LANG_DIR/integrate-Khalti-in-woocommerce/woocommerce-Khalti-LOCALE.mo
     *      - WP_LANG_DIR/plugins/woocommerce-khalti-LOCALE.mo
     */
    public function load_plugin_textdomain()
    {
        $locale = apply_filters('plugin_locale', get_locale(), 'woocommerce-khalti');

        load_textdomain(
            'woocommerce-khalti',
            WP_LANG_DIR . '/integrate-khalti-in-woocommerce/woocommerce-khalti-' . $locale . '.mo'
        );
        load_plugin_textdomain(
            'woocommerce-khalti',
            false,
            plugin_basename(dirname(WC_KHALTI_PLUGIN_FILE)) . '/languages'
        );
    }

    /**
     * Includes.
     */
    private function includes()
    {
        include_once dirname(WC_KHALTI_PLUGIN_FILE) . '/includes/class-wc-gateway-khalti.php';
    }

    /**
     * Add the gateway to WooCommerce.
     *
     * @param  array $methods WooCommerce payment methods.
     * @return array Payment methods with Khalti.
     */
    public function add_gateway($methods)
    {
        $methods[] = 'WC_Gateway_Khalti';

        return $methods;
    }

    /**
     * Display action links in the Plugins list table.
     *
     * @param  array $actions Plugin Action links.
     * @return array
     */
    public function plugin_action_links($actions)
    {
        $new_actions = array(
            'settings' => '<a href="'
                . admin_url('admin.php?page=wc-settings&tab=checkout&section=khalti')
                . '" aria-label="'
                . esc_attr(__('View WooCommerce Khalti settings', 'woocommerce-khalti')) . '">'
                . __('Settings', 'woocommerce-khalti') . '</a>',
        );

        return array_merge($new_actions, $actions);
    }

    /**
     * WooCommerce fallback notice.
     */
    public function woocommerce_missing_notice()
    {
        /* translators: %s: woocommerce version */
        echo '<div class="error notice is-dismissible"><p>'
            . sprintf(
                esc_html__(
                    'Integrate Khalti in WooCommerce depends on the version of %s or later to work!',
                    'woocommerce-khalti'
                ),
                '<a href="http://www.woothemes.com/woocommerce/" target="_blank">'
                    . esc_html__('WooCommerce 3.6', 'woocommerce-khalti') . '</a>'
            ) . '</p></div>';
    }
}
