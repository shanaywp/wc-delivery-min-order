<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WOODM_Minimum_Order class.
 */
class WOODM_Minimum_Order {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	 	 
	public function __construct() {
		
		if(get_option('woodm_enable_min_amount') == 'yes') {

			add_action( 'woocommerce_widget_shopping_cart_before_buttons', array(&$this,'add_message_widget_shopping_cart'), 90 );	
			//add_action( 'woocommerce_check_cart_items', array(&$this, 'add_notice') );
			add_action( 'woocommerce_after_checkout_validation', array( $this, 'check_is_delivery' ), 1, 2 );
			add_action( 'woocommerce_checkout_update_order_review', array($this, 'checkout_is_delivery') );
		}
	}

	public function checkout_is_delivery($posted) {

		parse_str($posted, $get_array);
		if (isset($get_array['pickupp_or_delivery']) && $get_array['pickupp_or_delivery'] == 'pickup') {
			add_action('woocommerce_review_order_after_payment_method', array($this, 'pickup_add_error_message_to_checkout'));
			WC()->session->set( 'store_pickup', 'pickup' );
		}
		if (isset($get_array['pickupp_or_delivery']) && $get_array['pickupp_or_delivery'] == 'delivery' || !isset($get_array['pickupp_or_delivery']) && get_option('woodm_delivery_min_amount') != '') {
			add_action('woocommerce_review_order_after_payment_method', array($this, 'delivery_add_error_message_to_checkout'));
			WC()->session->set( 'store_pickup', 'delivery' );
		}
	}

	public function pickup_add_error_message_to_checkout() {
		$message = $this->get_store_pickup_message();
		if($message != ""){
			echo "<div class='widget_min_order_message'>".sprintf( __( '<strong>Store Pickup Minimum Order: </strong> %s', 'woocommerce' ), $message )."</div>";		
		}
	}

	public function delivery_add_error_message_to_checkout() {
		$message = $this->get_delivery_message();
		if($message != ""){
			echo "<div class='widget_min_order_message'>".sprintf( __( '<strong>Delivery Minimum Order: </strong> %s', 'woocommerce' ), $message )."</div>";	
		}
	}

	public function check_is_delivery($posted, $errors) {

		if (isset($posted['pickupp_or_delivery']) && $posted['pickupp_or_delivery'] == 'delivery' || !isset($get_array['pickupp_or_delivery']) && get_option('woodm_delivery_min_amount') != '') {
			if (!empty($this->get_delivery_message())) {
				$errors->add( 'delivery', sprintf( __( '<strong>Delivery Minimum Order: </strong> %s', 'woocommerce' ), $this->get_delivery_message() ) );
			}
		}
		if (isset($posted['pickupp_or_delivery']) && $posted['pickupp_or_delivery'] == 'pickup') {
			if (!empty($this->get_store_pickup_message())) {
				
				$errors->add( 'store_pickup', sprintf( __( '<strong>Store Pickup Minimum Order: </strong> %s', 'woocommerce' ), $this->get_store_pickup_message() ) );
			}
		}
	}
	
	/**
	* dicplay minimum order amount message on widget
	*
	**/
	public function add_message_widget_shopping_cart(){
		$message = $this->get_store_pickup_message();
		if($message != ""){
			echo "<div class='widget_min_order_message'>".sprintf( __( '<strong>Store Pickup Minimum Order: </strong> %s', 'woocommerce' ), $message )."</div>";
		}
		/*$delivery_message = $this->get_delivery_message();
		if($delivery_message != ""){
			echo "<div class='widget_min_order_message'>".sprintf( __( '<strong>Delivery Minimum Order: </strong> %s', 'woocommerce' ), $delivery_message )."</div>";
		}*/
	}
	
	public function add_notice(){
		if( is_cart() || is_checkout() ) {
			$message = $this->get_store_pickup_message();
			if($message != ""){
				$message="<strong>".$message."</strong>";
				wc_add_notice($message,'error');	
			}
		}
	}
	
	/**
	* returns minimum order message 
	*/
	public function get_store_pickup_message(){
		global $xc_woo_restaurant;
		$return = "";
		$min = get_option('woodm_pickup_min_amount');
		$message = get_option('woodm_pickup_min_amount_msg');	
		$cart_total = round(WC()->cart->subtotal, 2);
		if( $cart_total < $min  ) {
			$message = str_replace("[minimum]",wc_price($min),$message);
			$message = str_replace("[current]",wc_price($cart_total),$message);
			$return = $message;
			
		}
		return apply_filters('woodm_pickup_minimum_order_message',$return,$min,$cart_total);
	}

	/**
	* returns minimum order message 
	*/
	public function get_delivery_message(){
		global $xc_woo_restaurant;
		$return = "";
		$min = get_option('woodm_delivery_min_amount');
		$message = get_option('woodm_delivery_min_amount_msg');	
		$cart_total = round(WC()->cart->subtotal, 2);
		if( $cart_total < $min  ) {
			$message = str_replace("[minimum]",wc_price($min),$message);
			$message = str_replace("[current]",wc_price($cart_total),$message);
			$return = $message;
			
		}
		return apply_filters('woodm_delivery_minimum_order_message',$return,$min,$cart_total);
	}
	
}