<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * WooCommerce Khalti.
 *
 * @class   WC_Gateway_Khalti_Boilerplate
 * @extends WC_Payment_Gateway
 * @version 1.0.0
 * @package Khalti Payment Gateway/Includes
 * @author  Bibek M Acharya
 */
class WC_Gateway_Khalti_Payment_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->id            = 'khalti';
		$this->icon          = apply_filters( 'khalti_icon', plugins_url( '/assets/images/payment.jpg', dirname( __FILE__ ) ) );
		$this->has_fields    = false;
		$this->credit_fields = false;

		$this->order_button_text = __( 'Pay with Khalti', 'khalti' );

		$this->method_title       = __( 'Khalti', 'khalti' );
		$this->method_description = __( 'Payments Via Khalti.', 'khalti' );

		$this->notify_url = WC()->api_request_url( 'Khalti' );

		$this->api_endpoint = '';

		$this->supports = array(
			'subscriptions',
			'products',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_amount_changes',
			'subscription_payment_method_change',
			'subscription_date_changes',
			'default_credit_card_form',
			'refunds',
			'pre-orders'
		);

		$this->view_transaction_url = '';

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values.
		$this->enabled = $this->get_option( 'enabled' );

		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		$this->sandbox     = $this->get_option( 'sandbox' );
		$this->private_key = $this->sandbox == 'no' ? $this->get_option( 'live_secret_key' ) : $this->get_option( 'test_secret_key' );
		$this->public_key  = $this->sandbox == 'no' ? $this->get_option( 'live_public_key' ) : $this->get_option( 'test_public_key' );

		$this->debug = $this->get_option( 'debug' );

		$this->final_url = null;

		// Logs.
		if ( $this->debug == 'yes' ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $woocommerce->logger();
			}
		}

		$this->init_gateway_sdk();

		// Hooks.
		if ( is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
			add_action( 'admin_notices', array( $this, 'checks' ) );

			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

			add_action( "woocommerce_cart_contents", "get_cart" );
		}

		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

	}

	/**
	 * Init Payment Gateway SDK.
	 *
	 * @access protected
	 * @return void
	 */
	protected function init_gateway_sdk() {
		// $this->admin_options();
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
		$transaction_id = @$_GET['transaction_id'];
		$transaction    = array();
		$getTransaction = $this->getTransaction()['Response'];
		$getTransaction = json_decode( $getTransaction );

		if ( ! empty( $getTransaction->records ) ) {
			foreach ( $getTransaction->records as $t ) {
				array_push( $transaction, array(
					'idx'      => $t->idx,
					'source'   => $t->user->name,
					'amount'   => $t->amount / 100,
					'fee'      => $t->fee_amount / 100,
					'date'     => date( "Y/m/d H:m:s", strtotime( $t->created_on ) ),
					'type'     => $t->type->name,
					'state'    => $t->refunded == true ? "Refunded" : $t->state->name,
					'refunded' => $t->refunded
				) );
			}
		}

		if ( $transaction_id ) {
			if ( @$_GET['refund'] == 'true' ) {
				$refund      = $this->khaltiRefund( $transaction_id );
				$status_code = $refund['StatusCode'];
				$detail      = json_decode( $refund['Response'] );
				$detail      = $detail->detail;
				if ( $status_code == 200 ) {
					$success = true;
					$message = $detail;
				} else {
					$error   = true;
					$message = $detail;
				}
			}
			$transaction_detail = json_decode( $this->getTransactionDetail( $transaction_id )['Response'] );
			//
			$transaction_detail_array = array(
				"idx"        => $transaction_detail->idx,
				"source"     => $transaction_detail->user->name,
				"mobile"     => $transaction_detail->user->mobile,
				"amount"     => $transaction_detail->amount / 100,
				"fee_amount" => $transaction_detail->fee_amount / 100,
				"date"       => date( "Y/m/d H:m:s", strtotime( $transaction_detail->created_on ) ),
				"state"      => $transaction_detail->refunded == true ? "Refunded" : $transaction_detail->state->name,
				"refunded"   => $transaction_detail->refunded,
			);
			include_once( Khalti()->plugin_path() . '/includes/admin/views/transaction-detail.php' );
		} else {
			include_once( Khalti()->plugin_path() . '/includes/admin/views/admin-options.php' );
		}
	}

	public function getTransaction() {
		$url = "https://khalti.com/api/merchant-transaction/";

		$headers = array(
			'headers' => array(
				'Authorization' => 'Key ' . $this->private_key
			)
		);

		# Make the call using API.
		# Modified to suit with WordPress coding standard
		# @modified by Deepen
		$response    = wp_remote_request( $url, $headers );
		$status_code = wp_remote_retrieve_response_code( $response );

		if( !is_wp_error( $response )){
			return [
				"Response"   => $response['body'],
				"StatusCode" => $status_code
			];
		}
	}

	public function getTransactionDetail( $idx ) {
		$url = "https://khalti.com/api/merchant-transaction/{$idx}/";

		$headers = array(
			'headers' => array(
				'Authorization' => 'Key ' . $this->private_key
			)
		);

		# Make the call using API.
		# Modified to suit with WordPress coding standard
		# @modified by Deepen
		$response    = wp_remote_request( $url, $headers );
		$status_code = wp_remote_retrieve_response_code( $response );

		if( !is_wp_error($response)){
			return [
				"Response"   => $response['body'],
				"StatusCode" => $status_code
			];
		}
	}

	public function khaltiRefund( $idx ) {
		$url = "https://khalti.com/api/merchant-transaction/{$idx}/refund/";

		$headers = array(
			'headers' => array(
				'Authorization' => 'Key ' . $this->private_key
			)
		);

		# Make the call using API.
		# Modified to suit with WordPress coding standard
		# @modified by Deepen
		$response    = wp_remote_request( $url, $headers );
		$status_code = wp_remote_retrieve_response_code( $response );

		return array(
			"Response"   => $response['body'],
			"StatusCode" => $status_code
		);
	}

	/**
	 * Check if SSL is enabled and notify the user.
	 *
	 * @access public
	 */
	public function checks() {
		if ( $this->enabled == 'no' ) {
			return;
		}

		// PHP Version.
		if ( version_compare( phpversion(), '5.3', '<' ) ) {
			echo '<div class="error"><p>' . sprintf( __( 'Khalti Error: Khalti requires PHP 5.3 and above. You are using version %s.', 'khalti' ), phpversion() ) . '</p></div>';
		} // Check required fields.
		else if ( ! $this->public_key || ! $this->private_key ) {
			echo '<div class="error"><p>' . __( 'Khalti Error: Please enter your public and private keys', 'khalti' ) . '</p></div>';
		} // Show message if enabled and FORCE SSL is disabled and WordPress HTTPS plugin is not detected.
		else if ( 'no' == get_option( 'woocommerce_force_ssl_checkout' ) && ! class_exists( 'WordPressHTTPS' ) ) {
			echo '<div class="error"><p>' . sprintf( __( 'Khalti is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - Gateway Name will only work in sandbox mode.', 'khalti' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '</p></div>';
		}
	}

	/**
	 * Check if this gateway is enabled.
	 *
	 * @access public
	 */
	public function is_available() {
		if ( $this->enabled == 'no' ) {
			return false;
		}

		if(get_woocommerce_currency() !== 'NPR'){
			return false;
		}

		if ( ! is_ssl() && 'yes' != $this->sandbox ) {
			return false;
		}

		if ( ! $this->public_key || ! $this->private_key ) {
			return false;
		}

		return true;
	}

	/**
	 * Initialise Khalti Settings Form Fields
	 *
	 * The standard gateway options have already been applied.
	 * Change the fields to match what the payment gateway your building requires.
	 *
	 * @access public
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'         => array(
				'title'       => __( 'Enable/Disable', 'Khalti' ),
				'label'       => __( 'Enable Khalti', 'Khalti' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title'           => array(
				'title'       => __( 'Title', 'Khalti' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'khalti' ),
				'default'     => __( 'Khalti', 'khalti' ),
				'desc_tip'    => true
			),
			'description'     => array(
				'title'       => __( 'Description', 'Khalti' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'Khalti' ),
				'default'     => 'Pay with Khalti',
				'desc_tip'    => true
			),
			'sandbox'         => array(
				'title'       => __( 'Khalti Mode', 'Khalti' ),
				'label'       => __( 'Enable Test Mode', 'Khalti' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the Khalti Payment Gateway in test mode before doing live transactions', 'Khalti' ),
				'default'     => 'yes'
			),
			'test_secret_key' => array(
				'title'       => __( 'Test Secret Key', 'Khalti' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Khalti Merchant Panel.', 'Khalti' ),
				'default'     => '',
				'desc_tip'    => true
			),
			'test_public_key' => array(
				'title'       => __( 'Test Public Key', 'Khalti' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Khalti Merchant Panel.', 'Khalti' ),
				'default'     => '',
				'desc_tip'    => true
			),
			'live_secret_key' => array(
				'title'       => __( 'Live Secret Key', 'Khalti' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Khalti Merchant Panel.', 'Khalti' ),
				'default'     => '',
				'desc_tip'    => true
			),
			'live_public_key' => array(
				'title'       => __( 'Live Public Key', 'Khalti' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Gateway Name account.', 'Khalti' ),
				'default'     => '',
				'desc_tip'    => true
			),
		);
	}

	/**
	 * Output for the order received page.
	 *
	 * @access public
	 * @return void
	 */
	public function receipt_page( $order ) {
		echo '<p>' . __( 'Thank you - your order is now pending payment.', 'Khalti' ) . '</p>';

	}

	/**
	 * Payment form on checkout page.
	 *
	 * @access public
	 */
	public function payment_fields() {
		$description = $this->get_description();
		
		if ( $this->sandbox == 'yes' ) {
			$description .= ' ' . __( 'TEST MODE ENABLED.' );
		}

		if ( ! empty( $description ) ) {
			echo wpautop( wptexturize( trim( $description ) ) );
		}

		// If credit fields are enabled, then the credit card fields are provided automatically.
		if ( $this->credit_fields ) {
			$this->credit_card_form( array(
				'fields_have_names' => false
			) );
		}

		$basetot = WC()->cart->total;
		$tot     = $basetot * 100;
		// This includes your custom payment fields.
		if ( @$_GET['khalti'] == 'pay' && @$_GET['order_id'] != null ) {
			$order_id = @$_GET['order_id'];
			include_once( Khalti()->plugin_path() . '/includes/views/html-payment-fields.php' );
		}

		if ( @$_GET['token'] != null && @$_GET['amount'] != null && @$_GET['order_id'] != null ) {
			$order_id = (int) strip_tags( $_GET['order_id'] );
			$token    = strip_tags( $_GET['token'] );
			$amount   = (int) strip_tags( $_GET['amount'] );
			$this->complete_payment( $order_id, $amount, $token );
		}
	}

	/**
	 * Outputs scripts used for the payment gateway.
	 *
	 * @access public
	 */
	public function payment_scripts() {
		if ( ! is_checkout() || ! $this->is_available() ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'khalti', 'https://khalti.com/static/khalti-checkout.js', '', '3.0', false );

		wp_enqueue_script( 'khalti' );
	}

	/**
	 * Output for the order received page.
	 *
	 * @access public
	 */
	public function thankyou_page( $order_id ) {
		if ( ! empty( $this->instructions ) ) {
			echo wpautop( wptexturize( wp_kses_post( $this->instructions ) ) );
		}

		$this->extra_details( $order_id );
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 *
	 * @param  WC_Order $order
	 * @param  bool $sent_to_admin
	 * @param  bool $plain_text
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
			if ( ! empty( $this->instructions ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}

			$this->extra_details( $order->id );
		}
	}

	/**
	 * Gets the extra details you set here to be
	 * displayed on the 'Thank you' page.
	 *
	 * @access private
	 */
	private function extra_details( $order_id = '' ) {
		echo '<h2>' . __( 'Extra Details', 'Khalti' ) . '</h2>' . PHP_EOL;

	}

	public function process_payment( $order_id ) {
		$order   = new WC_Order( $order_id );
		$basetot = WC()->cart->total;
		$tot     = $basetot * 100;
		// This includes your custom payment fields.
		include_once( Khalti()->plugin_path() . '/includes/views/html-payment-fields.php' );

		$this->final_url = $this->get_return_url( $order );

		$redirectUrl =  add_query_arg(['khalti' => 'pay', 'order_id' => $order_id], wc_get_checkout_url());

		// Return thank you page redirect.
		return array(
			'result'   => 'success',
			'redirect' => $redirectUrl
		);
	}

	/**
	 * Process the payment and return the result.
	 * @access public
	 *
	 * @param  int $order_id
	 *
	 * @return array
	 */
	public function complete_payment( $order_id, $amount, $token ) {
		$validate    = $this->khalti_validate( $token, $amount );
		$status_code = $validate['status_code'];
		$idx         = $validate['idx'];
		$amount      = $amount / 100;
		$orderTotal  = WC()->cart->total;

		$order = new WC_Order( $order_id );

		if ( $orderTotal == $amount && $idx != null && $status_code == 200 ) {
			// Payment complete.
			$order->payment_complete();

			// Store the transaction ID for WC 2.2 or later.
			add_post_meta( $order->id, '_transaction_id', $idx, true );

			// Add order note.
			$order->add_order_note( sprintf( __( 'Gateway Name payment approved (ID: %s)', 'Khalti' ), $idx ) );

			if ( $this->debug == 'yes' ) {
				$this->log->add( $this->id, 'Gateway Name payment approved (ID: ' . $idx . ')' );
			}

			// Reduce stock levels.
			// Changed to support latest WooCommerce
			wc_reduce_stock_levels( $order_id );

			if ( $this->debug == 'yes' ) {
				$this->log->add( $this->id, 'Stocked reduced.' );
			}

			// Remove items from cart.
			WC()->cart->empty_cart();

			if ( $this->debug == 'yes' ) {
				$this->log->add( $this->id, 'Cart emptied.' );
			}

			$redirect_url = $order->get_checkout_order_received_url();
			//redirect to success page
			wp_redirect( $redirect_url );
			exit();
		} else {
			// Add order note.
			$order->add_order_note( __( 'Gateway Name payment declined', 'Khalti' ) );
			$order->update_status( 'failed' );
			$redirect_url = $order->get_checkout_order_received_url();

			wp_redirect( $redirect_url );
			exit();
		}
	}

	public function khalti_validate( $token, $amount ) {
		$url = "https://khalti.com/api/payment/verify/";

		# Make the call using API.
		$headers = array(
			'headers' => array(
				'Authorization' => 'Key ' . $this->private_key
			),
			'method'  => 'POST',
			'body'    => array(
				'token'  => $token,
				'amount' => $amount
			)
		);

		$response    = wp_remote_request( $url, $headers );
		$status_code = wp_remote_retrieve_response_code( $response );

		// Response
		$response = json_decode( $response['body'] );
		$idx      = @$response->idx;
		$data     = array(
			"idx"         => $idx,
			"status_code" => $status_code,
			"response"    => $response
		);

		return $data;
	}

	public function khalti_transaction( $idx ) {
		//is this even being called ?
		$url = "https://khalti.com/api/merchant-transaction/$idx/";

		# Make the call using API.
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		//        $headers = ['Authorization: Key '.$this->private_key];
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

		// Response
		$response    = curl_exec( $ch );
		$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		return $response;
	}

	/**
	 * Process refunds.
	 * WooCommerce 2.2 or later
	 *
	 * @access public
	 *
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 *
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		$payment_id = get_post_meta( $order_id, '_transaction_id', true );
		$response   = '';

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 'APPROVED' == $refund['status'] ) {

			// Mark order as refunded
			$order->update_status( 'refunded', __( 'Payment refunded via Gateway Name.', 'Khalti' ) );

			$order->add_order_note( sprintf( __( 'Refunded %s - Refund ID: %s', 'Khalti' ), $refunded_cost, $refund_transaction_id ) );

			if ( $this->debug == 'yes' ) {
				$this->log->add( $this->id, 'Gateway Name order #' . $order_id . ' refunded successfully!' );
			}

			return true;
		} else {

			$order->add_order_note( __( 'Error in refunding the order.', 'Khalti' ) );

			if ( $this->debug == 'yes' ) {
				$this->log->add( $this->id, 'Error in refunding the order #' . $order_id . '. Gateway Name response: ' . print_r( $response, true ) );
			}

			return true;
		}

	}

} // end class.
