<?php
/*
 * Plugin Name:       Khalti Payment Gateway
 * Plugin URI:        
 * Description:       Payment Gateway Module for Khalti. Start accepting payments from Khalti. Check all transactions and refund payments right from the dashboard
 * Version:           1.0.0
 * Author:            Bibek M Acharya
 * Author URI:        https://github.com/manibibek
 * Requires at least: 4.0
 * Tested up to:      4.0
 * Text Domain:       khalti
 * Domain Path:       languages
 * Network:           false
 * GitHub Plugin URI: https://github.com/khalti/khalti-woocommerce
 *
 * @package  Khalti
 * @author   Bibek M Acharya / 4 Walls Innovations
 * @category Core
 */

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Required functions
 */
require_once('woo-includes/woo-functions.php');

if( !class_exists( 'Khalti' ) ) {

  /**
   * WooCommerce Khalti main class.
   *
   * @class   Khalti
   * @version 1.0.0
   */
  final class Khalti {

    /**
     * Instance of this class.
     *
     * @access protected
     * @access static
     * @var object
     */
    protected static $instance = null;

    /**
     * Slug
     *
     * @access public
     * @var    string
     */
     public $gateway_slug = 'khalti';

    /**
     * Text Domain
     *
     * @access public
     * @var    string
     */
    public $text_domain = 'khalti';

    /**
     * The Gateway Name.
     *
     * @access public
     * @var    string
     */
     public $name = "Khalti Payment Gateway";

    /**
     * Gateway version.
     *
     * @access public
     * @var    string
     */
    public $version = '1.0.0';

    /**
     * The Gateway URL.
     *
     * @access public
     * @var    string
     */
     public $web_url = "";

    /**
     * The Gateway documentation URL.
     *
     * @TODO   Replace the url
     * @access public
     * @var    string
     */
     public $doc_url = "https://github.com/khalti/khalti-woocommerce";

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {
      // If the single instance hasn't been set, set it now.
      if( null == self::$instance ) {
        self::$instance = new self;
      }

      return self::$instance;
    }

    /**
     * Throw error on object clone
     *
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
     public function __clone() {
       // Cloning instances of the class is forbidden
       _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'khalti' ), $this->version );
     }

    /**
     * Disable unserializing of the class
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
     public function __wakeup() {
       // Unserializing instances of the class is forbidden
       _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'khalti' ), $this->version );
     }

    /**
     * Initialize the plugin public actions.
     *
     * @access private
     */
    private function __construct() {
      // Hooks.
      add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
      add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
      add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

      // Is WooCommerce activated?
      if( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        add_action('admin_notices', array( $this, 'woocommerce_missing_notice' ) );
        return false;
      }
      else{
        // Check we have the minimum version of WooCommerce required before loading the gateway.
        if( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '>=' ) ) {
          if( class_exists( 'WC_Payment_Gateway' ) ) {

            $this->includes();

            add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
            add_filter( 'woocommerce_currencies', array( $this, 'add_currency' ) );
            add_filter( 'woocommerce_currency_symbol', array( $this, 'add_currency_symbol' ), 10, 2 );
          }
        }
        else {
          add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
          return false;
        }
      }
    }

    /**
     * Plugin action links.
     *
     * @access public
     * @param  mixed $links
     * @return void
     */
     public function action_links( $links ) {
       if( current_user_can( 'manage_woocommerce' ) ) {
         $plugin_links = array(
           '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=khalti') . '">' . __( 'Payment Settings', 'woocommerce-payment-gateway-khalti' ) . '</a>',
         );
         return array_merge( $plugin_links, $links );
       }

       return $links;
     }

    /**
     * Plugin row meta links
     *
     * @access public
     * @param  array $input already defined meta links
     * @param  string $file plugin file path and name being processed
     * @return array $input
     */
     public function plugin_row_meta( $input, $file ) {
       if( plugin_basename( __FILE__ ) !== $file ) {
         return $input;
       }

       $links = array(
         '<a href="' . esc_url( $this->doc_url ) . '">' . __( 'Documentation', 'khalti' ) . '</a>',
       );

       $input = array_merge( $input, $links );

       return $input;
     }

    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any 
     * following ones if the same translation is present.
     *
     * @access public
     * @return void
     */
    public function load_plugin_textdomain() {
      // Set filter for plugin's languages directory
      $lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
      $lang_dir = apply_filters( 'woocommerce_' . $this->gateway_slug . '_languages_directory', $lang_dir );

      // Traditional WordPress plugin locale filter
      $locale = apply_filters( 'plugin_locale',  get_locale(), $this->text_domain );
      $mofile = sprintf( '%1$s-%2$s.mo', $this->text_domain, $locale );

      // Setup paths to current locale file
      $mofile_local  = $lang_dir . $mofile;
      $mofile_global = WP_LANG_DIR . '/' . $this->text_domain . '/' . $mofile;

      if( file_exists( $mofile_global ) ) {
        // Look in global /wp-content/languages/plugin-name/ folder
        load_textdomain( $this->text_domain, $mofile_global );
      }
      else if( file_exists( $mofile_local ) ) {
        // Look in local /wp-content/plugins/plugin-name/languages/ folder
        load_textdomain( $this->text_domain, $mofile_local );
      }
      else {
        // Load the default language files
        load_plugin_textdomain( $this->text_domain, false, $lang_dir );
      }
    }

    /**
     * Include files.
     *
     * @access private
     * @return void
     */
    private function includes() {
      include_once( 'includes/class-wc-gateway-' . str_replace( '_', '-', $this->gateway_slug ) . '.php' );

      // This supports the plugin extensions 'WooCommerce Subscriptions' and 'WooCommerce Pre-orders'.
      if( class_exists( 'WC_Subscriptions_Order' ) || class_exists( 'WC_Pre_Orders_Order' ) ) {
        include_once( 'includes/class-wc-gateway-' . str_replace( '_', '-', $this->gateway_slug ) . '-add-ons.php' );
      }
    }

    /**
     * This filters the gateway to only supported countries.
     *
     * @TODO   List the country codes the payment gateway your building supports.
     * @access public
     */
    public function gateway_country_base() {
      return apply_filters( 'woocommerce_gateway_country_base', array( 'NP' ) );
    }

    /**
     * Add the gateway.
     *
     * @access public
     * @param  array $methods WooCommerce payment methods.
     * @return array WooCommerce Khalti gateway.
     */
    public function add_gateway( $methods ) {
      // This checks if the gateway is supported for your country.
      // if( in_array( WC()->countries->get_base_country(), $this->gateway_country_base() ) ) {

        if( class_exists( 'WC_Subscriptions_Order' ) ) {
          $methods[] = 'WC_Gateway_' . str_replace( ' ', '_', $this->name ) . '_Subscription';
        }
        else {
          $methods[] = 'WC_Gateway_' . str_replace( ' ', '_', $this->name );
        }

      // }

      return $methods;
    }

    /**
     * Add the currency.
     *
     * @TODO   Use this function only if you are adding a new currency. 
     *   
     * @access public
     * @return array
     */
    public function add_currency( $currencies ) {
      $currencies['NPR'] = __( 'Currency Name', 'khalti' );
      return $currencies;
    }

    /**
     * Add the currency symbol.
     *
     * @TODO   Use this function only when using the function 'add_currency'. 
     *         If currency has no symbol, leave $currency_symbol blank.
     * @access public
     * @return string
     */
    public function add_currency_symbol( $currency_symbol, $currency ) {
      switch( $currency ) {
        case 'NPR':
          $currency_symbol = 'NPR';
        break;
      }
      return $currency_symbol;
    }

    /**
     * WooCommerce Fallback Notice.
     *
     * @access public
     * @return string
     */
    public function woocommerce_missing_notice() {
      echo '<div class="error woocommerce-message wc-connect"><p>' . sprintf( __( 'Sorry, <strong>WooCommerce %s</strong> requires WooCommerce to be installed and activated first. Please install <a href="%s">WooCommerce</a> first.', $this->text_domain), $this->name, admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce' ) ) . '</p></div>';
    }

    /**
     * WooCommerce Payment Gateway Upgrade Notice.
     *
     * @access public
     * @return string
     */
    public function upgrade_notice() {
      echo '<div class="updated woocommerce-message wc-connect"><p>' . sprintf( __( 'WooCommerce %s depends on version 2.2 and up of WooCommerce for this gateway to work! Please upgrade before activating.', 'payment-gateway-boilerplate' ), $this->name ) . '</p></div>';
    }

    /** Helper functions ******************************************************/

    /**
     * Get the plugin url.
     *
     * @access public
     * @return string
     */
    public function plugin_url() {
      return untrailingslashit( plugins_url( '/', __FILE__ ) );
    }

    /**
     * Get the plugin path.
     *
     * @access public
     * @return string
     */
    public function plugin_path() {
      return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

  } // end if class

  add_action( 'plugins_loaded', array( 'Khalti', 'get_instance' ), 0 );

} // end if class exists.

/**
 * Returns the main instance of WC_Gateway_Name to prevent the need to use globals.
 *
 * @return WooCommerce Gateway Name
 */
function Khalti() {
	return Khalti::get_instance();
}

?>