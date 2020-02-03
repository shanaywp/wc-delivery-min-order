<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WOODM_Delivery_fee class.
 */
class WOODM_Delivery_fee {
	
	private $session = "woodm_delivery_fee_session";

	private $_fee_label = 'Delivery Fee';

	private $_api_key = '';

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	 
	 	 
	public function __construct() {
		$this->_api_key = get_option('woodm_delivery_api_key');
		add_action('init', array(&$this, 'woodm_fee_init'));
	}

	public function woodm_fee_init() {
		add_action( 'woocommerce_checkout_update_order_review', array($this, 'checkout_is_delivery') );
		if (get_option('woodm_enable_delivery_fee') === 'no' || !is_admin() && WC()->session->get( 'store_pickup' ) === 'pickup') {
			return;
		}
		add_action('woocommerce_cart_calculate_fees', array(&$this, 'add_donation_to_cart'));
	}

	public static function add_donation_to_cart(){
		global $xc_woo_restaurant;
		$donation_amount = $this->get_donation_amount();
		if ($donation_amount && is_numeric($donation_amount) && $donation_amount > 0):
			$taxable = (get_option('woodm_enable_delivery_fee_taxable') === 'yes') ? true : false;
			WC()->cart->add_fee(__($this->_fee_label, ''), $donation_amount, $taxable);
		endif;
	}
	
	public function get_donation_amount(){

		$amount = $this->get_range_amount();
		if ($amount && is_numeric($amount) && WC()->session->get( 'store_pickup' ) === 'delivery') {
			return $amount;
		}
		return "0";
	}

	public function get_range_amount() {
		if (empty(WC_Countries::get_base_postcode()) || !isset($_POST['postcode']) || isset($_POST['postcode']) && empty($_POST['postcode']) || get_option('woodm_enable_delivery_fee') === 'no') {
			return '0';
		}
		$miles = $this->get_distance_miles(WC_Countries::get_base_postcode(), $_POST['postcode']);
		$amount = '0';
		$arr_amount = get_option('woodm_enable_delivery_range');
		if (!empty($arr_amount)) {
			foreach ($arr_amount as $key => $value) {
				$miles_fee = (isset($value['miles_fee']) && !empty($value['miles_fee'])) ? $value['miles_fee'] : '0';
				$from_miles = (isset($value['from_miles']) && !empty($value['from_miles'])) ? $value['from_miles'] : '0';
				$to_miles = (isset($value['to_miles']) && !empty($value['to_miles'])) ? $value['to_miles'] : '0';
				
				if((float) $miles >= (float) $from_miles && (float) $miles <= (float) $to_miles) {
					
					return $miles_fee;
				}
			}
		}
	}
	
	public function woocommerce_thankyou(){
		WC()->session->set($this->session, 0);	
	}
	
	public function get_distance_miles($origins, $destinations) {
		$get_store_data = $this->get_distance_obj($origins, $destinations);
		$_miles = 0;
		if ($get_store_data->status === 'OK') {
			
			foreach ($get_store_data->rows as $key => $rows) {
				$_miles = $this->get_miles($rows->elements[0]->distance->value);
			}
		}
		return $_miles;
	}

	public function get_distance_obj($origins, $destinations) {
		
		$url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&sensor=false&origins='".$origins."'&destinations='".$destinations."'&key=".$this->_api_key;
		
		$ch = curl_init($url);

	    curl_setopt($ch, CURLOPT_POST, true);

	    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
	    
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    
	    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	    
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    
	    curl_setopt($ch, CURLOPT_HEADER, false);

	    $response = curl_exec($ch);
	    
	    curl_close($ch);

	    return json_decode($response);
	}

	public function get_miles($metres) {
		$miles = $metres * 0.000621371192;
	    return number_format((float)$miles, 2, '.', '');
	}
}