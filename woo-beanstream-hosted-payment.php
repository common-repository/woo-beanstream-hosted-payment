<?php
/*
Plugin Name: Woo Beanstream Hosted Payment Getway
Plugin URI: http://www.uniquesweb.co.in/demo/woocommerce
Description: WooCommerce Beanstream Hosted Paymen Getway
Version: 1.0.0
Author: Bhavik Patel
Author URI: http://www.uniquesweb.co.in/demo/woocommerce
License: GPLv2
Text Domain: wc_beanstream
*/

defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );

function Beanstream_gateway_class_function_load() {
	if(class_exists('WC_Gateway_Beanstream'))
			return;
	class WC_Gateway_Beanstream extends WC_Payment_Gateway {

		/** @var boolean Whether or not logging is enabled */
		public static $log_enabled = false;
		public static $log = false;
	    /**
	     * Constructor for the gateway.
	     *
	     * @return void
	     */
	    public function __construct() {
				$plugin_dir = plugin_dir_url(__FILE__);

	        global $woocommerce;

	        $this->id             = 'beanstream';
					$this->icon = apply_filters('woocommerce_twocheckoutpp_icon', ''.$plugin_dir.'beanstream.png');
					$this->has_fields = true;
	        $this->has_fields     = false;
	        $this->method_title   = __( 'Beanstream Payment Getway', 'wc_beanstream' );

	        // Load the form fields.
	        $this->init_form_fields();

	        // Load the settings.
	        $this->init_settings();

	        // Define user set variables.
	        $this->title          = $this->settings['title'];
	        $this->description    = $this->settings['description'];
					$this->instructions   = $this->get_option( 'instructions' );
					$this->merchant_id    = $this->settings['merchant_id'];
					$this->notify_url   = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Beanstream', home_url( '/' ) ) );
					$this->debug = $this->get_option('debug');

					  self::$log_enabled = $this->debug;

				//		self::$log_enabled    = $this->debug;

	        // Actions.
	        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) )
	            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
	        else
	            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );

						  add_action( 'woocommerce_api_wc_gateway_beanstream', array( $this, 'check_ipn_response' ) );


	    }


	    /* Admin Panel Options.*/
		function admin_options() {
			?>
			<h3><?php _e('Beanstream Payment Getway','wc_beanstream'); ?></h3>
			<p>
				WooCommerce Beanstream allows you to process payments The hosted payment form allows you to redirect your customers to a payment form hosted on the Beanstream servers. The customer completes their checkout and is then redirected back to your site.
			</p>
	    	<table class="form-table">
	    		<?php $this->generate_settings_html(); ?>
			</table> <?php
	    }


			public static function log( $message ) {
					if ( self::$log_enabled ) {
							if ( empty( self::$log ) ) {
									self::$log = new WC_Logger();
							}
							self::$log->add( 'beanstream', $message );
					}
			}


	    /* Initialise Gateway Settings Form Fields. */
	    public function init_form_fields() {
	    	global $woocommerce;



	    	if ( is_admin() )


	        $this->form_fields = array(
	            'enabled' => array(
	                'title' => __( 'Enable/Disable', 'wc_beanstream' ),
	                'type' => 'checkbox',
	                'label' => __( 'Enable Beanstream Payment Getway', 'wc_beanstream' ),
	                'default' => 'no'
	            ),
							'merchant_id' => array(
								'title' 		=> __( 'Merchant ID', 'wc_beanstream' ),
								'type' 			=> 'text',
								'default' 		=> '',
								'description' 	=> __( 'Merchant ID.', 'wc_beanstream' ),
							),
	            'title' => array(
	                'title' => __( 'Title', 'wc_beanstream' ),
	                'type' => 'text',
	                'description' => __( 'This controls the title which the user sees during checkout.', 'wc_beanstream' ),
	                'desc_tip' => true,
	                'default' => __( 'Beanstream Payment Getway', 'wc_beanstream' )
	            ),
	            'description' => array(
	                'title' => __( 'Description', 'wc_beanstream' ),
	                'type' => 'textarea',
	                'description' => __( 'This controls the description which the user sees during checkout.', 'wc_beanstream' ),
	                'default' => __( 'Desctiptions for Beanstream Payment Getway.', 'wc_beanstream' )
	            ),
							'debug' => array(
									'title'       => __( 'Debug Log', 'woocommerce' ),
									'type'        => 'checkbox',
									'label'       => __( 'Enable logging', 'woocommerce' ),
									'default'     => 'no',
									'description' => sprintf( __( 'Log Beanstream events', 'woocommerce' ), wc_get_log_file_path( 'beanstream ') )
							)


	        );

	    }




	    /* Process the payment and return the result. */
		function process_payment ($order_id) {

			//include_once( 'includes/beanstream-payment-getway-request.php');
			global $woocommerce;

			$order          = wc_get_order( $order_id );
		//	$beanstream_request = new WC_Gateway_Beanstream_Request( $this );
			

				$beanstream_request_arg = apply_filters( 'woocommerce_beanstream_args',
					array(
						'merchant_id'      => $this->merchant_id,
						'trnOrderNumber'   => $order->get_order_number(),
						'trnAmount'        => $order->get_total(),
						'ordName'          => $order->billing_first_name.'  '.$order->billing_last_name.'('.$order->billing_company.')',
						'ordAddress1'      => $order->billing_address_1,
						'ordAddress2'      => $order->billing_address_2,
						'ordCity'          => $order->billing_city,
						'ordProvince'      => $order->billing_state,
						'ordPhoneNumber'=>$order->billing_phone,
						'ordEmailAddress'=>$order->billing_email,
						'ordPostalCode'=>$order->billing_postcode,
						'trnCardOwner'=>	$order->billing_first_name.'  '.$order->billing_last_name,
						'errorPage'=>$this->notify_url,
						'approvedPage'=>$this->notify_url,
						'declinedPage'=>$this->notify_url,
					)
				);



				$beanstream_args = http_build_query( $beanstream_request_arg, '', '&' );

				$redirect = 'https://www.beanstream.com/scripts/payment/payment.asp?' . $beanstream_args;


			return array(
				'result'   => 'success',
				'redirect' => $redirect
			);

		}

		function check_ipn_response()
		{
			global $woocommerce;
				$wc_order_id    = $_REQUEST['trnOrderNumber'];
				$order   = new WC_Order( absint( $wc_order_id ) );
				// Custom holds post ID
					if ( 'yes' == $this->debug )
					{
						$this->log( 'beanstream', 'Found order #' . $order->id );
					}

					// Lowercase returned variables
					$_REQUEST['trnApproved'] 	= strtolower( $_REQUEST['trnApproved'] );
					$_REQUEST['trnType'] 		= strtolower( $_REQUEST['trnType'] );

					if ( 'yes' == $this->debug )
					{
							$this->log( 'beanstream', 'Payment status: ' . $_REQUEST['messageText'] );
					}

					// We are here so lets check status and do actions
					if( isset($_REQUEST['trnApproved']) && $_REQUEST['trnApproved'] ==1 )
					 {

							// Check order not already completed
							if ( $order->has_status( 'completed' ) ) {
								if ( 'yes' == $this->debug ) {
									$this->log( 'beanstream', 'Aborting, Order #' . $order->id . ' is already complete.' );
								}
								exit;
							}


							 // Store PP Details
							if ( ! empty( $_REQUEST['trnEmailAddress'] ) ) {
								update_post_meta( $order->id, 'Payer beanstream address', wc_clean( $_REQUEST['trnEmailAddress'] ) );
							}
							if ( ! empty( $_REQUEST['trnCustomerName'] ) ) {
								update_post_meta( $order->id, 'Payer first name', wc_clean( $_REQUEST['trnCustomerName'] ) );
							}
							if ( ! empty( $_REQUEST['trnType'] ) ) {
								update_post_meta( $order->id, 'Payment type', wc_clean( $_REQUEST['trnType'] ) );
							}

							if ( $_REQUEST['messageText'] == 'Approved' ) {
								$order->add_order_note( __( 'IPN payment completed', 'woocommerce' ) );
								$txn_id = ( ! empty( $_REQUEST['trnId'] ) ) ? wc_clean( $_REQUEST['trnId'] ) : '';
								$order->payment_complete( $txn_id );
							}
							else
							{
								$order->update_status( 'on-hold', sprintf( __( 'Payment pending: %s', 'woocommerce' ), $posted['pending_reason'] ) );
							}

							if ( 'yes' == $this->debug ) {
							$this->log( 'beanstream', 'Payment complete.' );
							}

							$woocommerce->cart->empty_cart();
							wp_redirect( $this->get_return_url( $order ) );
							exit;
				}
				elseif (isset($_REQUEST['trnApproved']) && $_REQUEST['trnApproved'] == 0)
        {
						// Order failed
							$order->update_status( 'failed', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), strtolower( $_REQUEST['trnApproved'] ) ) );
							wc_add_notice( __('Payment error:', 'woothemes') . $_REQUEST['messageText'], 'error' );

								if ( 'yes' == $this->debug )
								{
									$this->log( 'beanstream', 'Aborting, Order #' . $order->id .'---'. $_REQUEST['messageText']  );
								}
								wp_redirect( $order->get_cancel_order_url( ) );
								exit;
				}else {
							$this->log( 'Some thing Wrong #' . $order->id .'---'. $_REQUEST['messageText']  );

					}


		}



	    /* Output for the order received page.   */
		function thankyou() {
			echo $this->instructions != '' ? wpautop( $this->instructions ) : '';
		}



	}

}
add_action( 'plugins_loaded', 'Beanstream_gateway_class_function_load' );


function Beanstream_gateway_add_in_woocommerce( $methods ) {
	$methods[] = 'WC_Gateway_Beanstream';
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'Beanstream_gateway_add_in_woocommerce' );


?>
