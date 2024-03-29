<?php
/**
* Description: Adds an extra field option for pick up or delivery. If pick up, address is removed from checkout
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOODM_Delivery_pickup {

	public function __construct() {

		/*
		* Create custom html field in checkout.
		*/
		add_filter( 'woocommerce_form_field', array($this, 'add_custom_checkout_field'), 10, 4 );

		/*
		* Checkout field validation.
		*/
  		add_filter( 'woocommerce_checkout_fields' , array($this, 'woodm_checkout_add_delivery_options') );
		
		add_action( 'woocommerce_admin_order_data_after_billing_address', array($this, 'woodm_delivery_option_display_admin_order_meta'), 10, 1 );

		add_filter('woocommerce_email_order_meta_keys', array($this, 'woodm_select_order_meta_keys'));
		add_action( 'woocommerce_checkout_update_order_review', array($this, 'woodm_checkout_is_delivery') );
		add_action( 'woocommerce_checkout_update_order_meta', array($this, 'woodm_save_extra_checkout_fields'), 10, 2 );
		add_action( 'woocommerce_email_customer_details', array($this, 'woodm_email_after_customer_details'), 30, 4);
		add_action( 'woocommerce_order_details_before_order_table', array($this, 'woodm_show_delivery_in_thanku'), 30, 4);

		/*
		* Add Tab in woocommerce settings
		*/
		add_filter( 'woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 30);
		add_action( 'woocommerce_settings_tabs_delivery_pickup', array( $this, 'delivery_tab_output_sections' ) );
		add_action( 'woocommerce_update_options_delivery_pickup', array( $this, 'woodm_update_settings' ) );

		add_action( 'woocommerce_admin_field_delivery_range', array($this, 'woodm_delivery_range_field') );

		add_action('init', array($this, 'woodm_set_default_pickup'));
  	}

  	public function woodm_set_default_pickup() {
  		if (get_option('woodm_enable_delivery') === 'no' && !is_admin()) {
	  		WC()->session->set( 'store_pickup', 'pickup' );
	  	}
  	}

  	public function woodm_checkout_is_delivery($posted) {

		parse_str($posted, $get_array);

		if (isset($get_array['pickupp_or_delivery']) && $get_array['pickupp_or_delivery'] == 'pickup') {
			//add_action('woocommerce_review_order_after_payment_method', array($this, 'pickup_add_error_message_to_checkout'));
			WC()->session->set( 'store_pickup', 'pickup' );

		}
		if (isset($get_array['pickupp_or_delivery']) && $get_array['pickupp_or_delivery'] == 'delivery') {
			//add_action('woocommerce_review_order_after_payment_method', array($this, 'delivery_add_error_message_to_checkout'));
			WC()->session->set( 'store_pickup', 'delivery' );
			$fee = new WOODM_Delivery_fee;
			add_action('woocommerce_cart_calculate_fees', array($fee, 'add_donation_to_cart'));
		}
	}

	public function woodm_save_extra_checkout_fields( $order_id, $posted ){
	    if( isset( $posted['pickupp_or_delivery'] ) ) {
	        update_post_meta( $order_id, 'pickupp_or_delivery', $posted['pickupp_or_delivery'] );
	    }
	}

  	public function woodm_show_delivery_in_thanku( $order_id ) {

		// Lets grab the order
		$order = wc_get_order( $order_id );
		$delivery = get_post_meta( $order->id, 'pickupp_or_delivery', true);
		$delivery = ($delivery == 'pickup') ? __('Store Pickup', '') : __('Delivery', '');
		echo "<h3>Delivery Option: " . $delivery  . "</h3>";
	}

	/*
	* Add Delivery option to checkout page
	*/
  	public function woodm_checkout_add_delivery_options( $fields ) {

		unset($fields['billing']['billing_country']);
		unset($fields['billing']['billing_state']);
		unset($fields['billing']['billing_address_2']);
		unset($fields['billing']['billing_company']);

		unset($fields['shipping']['shipping_country']);
		unset($fields['shipping']['shipping_state']);
		unset($fields['shipping']['shipping_address_2']);
		unset($fields['shipping']['shipping_company']);

		
		$fields['billing']['billing_address_1']['label'] = 'Delivery Address';
		$fields['billing']['billing_address_1']['type'] = 'textarea';

		$fields['billing']['billing_city']['class'] = array( 'form-row-first', 'address-field' );
		$fields['billing']['billing_postcode']['class'] = array( 'form-row-last', 'address-field' );

		$fields['shipping']['shipping_address_1']['label'] = 'Delivery Address';
		$fields['shipping']['shipping_address_1']['type'] = 'textarea';

		$fields['shipping']['shipping_city']['class'] = array( 'form-row-first', 'address-field' );
		$fields['shipping']['shipping_postcode']['class'] = array( 'form-row-last', 'address-field' );

		$fields['billing']['billing_phone']['class'] = array( 'form-row-first' );
		$fields['billing']['billing_email']['class'] = array( 'form-row-last' );

		$output = '';
		$output .= '<div class="checkout-address">';
			if (!empty(trim($this->get_default_store_address()))) {
				
				$output .= '<div class="store_address">';
					$output .= $this->get_default_store_address();
		       	$output .= '</div>';
			}
			if (!empty(trim(get_option('woodm_pickup_time')))) {
				
				$output .= '<div class="reday_in_time">';
		       		$output .= get_option('woodm_pickup_time');
		        $output .= '</div>';
			}
			if (!empty(trim(get_option('woodm_delivery_time')))) {
		        $output .= '<div class="reday_in_time delivery-msg" style="display:none;">';
		       		$output .= get_option('woodm_delivery_time');
		        $output .= '</div>';
		    }
    	$output .= '</div>';

    	$fields['billing']['pickupp_or_delivery'] = array(
							        'label' 		=> __('Select Store or Delivery?', 'woocommerce'),
							        'placeholder' 	=> _x('Select....', 'placeholder', 'woocommerce'),
							        'required' 		=> true,
							        'priority'		=> 38,
							        'clear' 		=> false,
							        'type' 			=> 'select',
							        'class' 		=> array('form-row-wide', 'pickupp-or-delivery'),
							        'options'       => array(
								    	'pickup'	=> __( 'Store Pickup', 'd' ),
								    ),
							        'default' 		=> WC()->session->get( 'store_pickup' ),
							);

    	if (get_option('woodm_enable_delivery') !== 'no') {
			$fields['billing']['pickupp_or_delivery']['options'] = array(
								    	'pickup'	=> __( 'Store Pickup', 'd' ),
								    	'delivery'	=> __( 'Local Delivery', 'd' ),
								    );
		}
		$fields['billing']['pickupp_or_delivery_html'] = array(
						        'required' 		=> false,
						        'clear' 		=> false,
						        'priority'		=> 38,
						        'type' 			=> 'html',
						        'class' 		=> array('pickupp-or-delivery-html', 'pickupp-or-delivery-address'),
						        'description' 	=> $output,
						);

		$fields['billing']['billing_phone']['priority'] = 31;
	    $fields['billing']['billing_email']['priority'] = 32;
	    $fields['billing']['billing_country']['class'][] = 'hide-element';
	    $fields['billing']['billing_address_1']['label'] = 'Delivery Address';
	    
	    if (isset($_POST['pickupp_or_delivery']) && $_POST['pickupp_or_delivery'] == 'pickup') {

	  		if (isset($fields['billing']['billing_country'])) {
	  			$fields['billing']['billing_country']['required'] = '';
	  		}
	  		if (isset($fields['billing']['billing_address_1'])) {
		    	$fields['billing']['billing_address_1']['required'] = '';
		    }
		    if (isset($fields['billing']['billing_address_2'])) {
		    	$fields['billing']['billing_address_2']['required'] = '';
		    }
		    if (isset($fields['billing']['billing_city'])) {
		    	$fields['billing']['billing_city']['required'] = '';
		    }
		    if (isset($fields['billing']['billing_state'])) {
		    	$fields['billing']['billing_state']['required'] = '';
		    }
		    if (isset($fields['billing']['billing_postcode'])) {
		    	$fields['billing']['billing_postcode']['required'] = '';
		    }
		}

		return $fields;
	}

	public function woodm_delivery_option_display_admin_order_meta($order){
		$delivery = get_post_meta( $order->id, 'pickupp_or_delivery', true);
		$delivery = ($delivery == 'pickup') ? __('Store Pickup', '') : __('Delivery', '');
		echo '<p><strong>'.__('Delivery option').':</strong> ' . $delivery . '</p>';
	}

	public function woodm_select_order_meta_keys( $keys ) {
		$keys['Delivery Option:'] = 'pickupp_or_delivery';
		return $keys;
	}

	public function woodm_email_after_customer_details($order, $sent_to_admin, $plain_text, $email) {

		$is_storepicup = get_post_meta($order->get_id(), 'pickupp_or_delivery', true);

		if ($is_storepicup == 'pickup') {
			?>
			<div class="checkout-address-thankyou" style="background-color: #f3f3f3;padding: 15px;    margin-bottom: 25px;">
				<p style="margin-bottom: 10px;margin-top: 0;font-size: 19px;font-weight: bold;">Store Pickup</p>
				<div class="store_address" style="margin-bottom: 4px;">
		       		<?php
		       			echo $this->get_default_store_address();
		       		?>
		       	</div>
		       	<div class="reday_in_time">
		       		<?php	
		       			echo get_option('woodm_pickup_time');
		       	    ?>
		        </div>
	    	</div>
			<?php 
		} else if ($is_storepicup == 'delivery') {

			?>
			<div class="checkout-address-thankyou" style="background-color: #f3f3f3;padding: 15px;    margin-bottom: 25px;">

		       	<div class="reday_in_time delivery-msg">
		       		<?php	
		       			echo get_option('woodm_delivery_time');
		       	    ?>
		        </div>
	    	</div>
			<?php
		}
	}

	public function add_custom_checkout_field( $field, $key, $args, $value ) {

		switch ( $args['type'] ) {
			case 'html':
				$field .= '<div class="form-row ' . esc_attr( $args['type'] ) .' '. esc_attr( implode( ' ', $args['class'] ) ) . '" id="' . esc_attr( $args['id'] ) . '">'.$args['description'].'</div>';

				break;
		}
		return $field;
	}

	public function add_settings_tab($settings_tabs) {

  		$settings_tabs['delivery_pickup'] = __( 'Delivery', 'wcdm' );
        return $settings_tabs;
  	}

  	public function delivery_tab_output_sections() {
  		$this->woodm_get_delivery_sub_tab();
  		$this->woocommerce_admin_fields( $this->get_delivery_tab_settings_fields() );
  	}

  	/**
	 * Output admin fields.
	 *
	 * Loops though the woocommerce options array and outputs each field.
	 *
	 * @param array $options Opens array to output.
	 */
	public function woocommerce_admin_fields( $options ) {
		
		if ( ! class_exists( 'WC_Admin_Settings', false ) ) {
			include WooCommerce::plugin_path() . '/includes/admin/class-wc-admin-settings.php';
		}
		
		self::output_fields( $options );
	}

  	public function get_delivery_tab_settings_fields() {
  		global $current_section;
  		
  		if ($current_section == 'min-order-amount') {
  			
  			$settings = array();

  			$settings['section_title'] = array(
	            'name'     => __( 'Minimum Order Amount', 'woocommerce-settings-tab-demo' ),
	            'type'     => 'title',
	            'desc'     => '',
	            'id'       => 'wc_settings_tab_min_order_amount_title'
	        );

  			$settings['woodm_enable_min_amount'] = array(
				'title'           => __( 'Enable Minimum Order Amount?', 'woocommerce' ),
				'desc'            => __( 'Minimum Order Amount Enable / Disable', 'woocommerce' ),
				'id'              => 'woodm_enable_min_amount',
				'default'         => 'no',
				'type'            => 'checkbox',
				'checkboxgroup'   => 'start',
				'show_if_checked' => 'option',
				/* Translators: %s Docs URL. */
				//'desc_tip'        => sprintf( __( 'Enable delivery option to checkout', 'woocommerce' ), '' ),
			);

  			$settings['woodm_pickup_min_amount'] = array(
	            'name' => __( 'Minimum Order Amount For "Store Pickup"', 'woocommerce-settings-tab-demo' ),
	            'type' => 'number',
	            'default' => '10',
	            'id'   => 'woodm_pickup_min_amount',
	            'desc_tip'        => sprintf( __( 'The minimum amount with which you can place your order.', 'woocommerce' ), '' ),
	        );

  			$settings['woodm_delivery_min_amount'] = array(
	            'name' => __( 'Minimum Order Amount for "Delivery"', 'woocommerce-settings-tab-demo' ),
	            'type' => 'number',
	            'default' => '10',
	            'id'   => 'woodm_delivery_min_amount',
	            'desc_tip'        => sprintf( __( 'The minimum amount with which you can place your order.', 'woocommerce' ), '' ),
	        );

	        $settings['woodm_pickup_min_amount_msg'] = array(
	            'name' => __( 'Minimum Order Message for "Store Pickup"*', 'woocommerce-settings-tab-demo' ),
	            'type' => 'textarea',
	            'default' => 'To place an order, you must have an order value of [minimum] minimum. Your current order value is [current].',
	            'id'   => 'woodm_pickup_min_amount_msg',
	            'desc_tip'        => sprintf( __( 'The notice message that appears if the minimum amount is not reached. Insert [minimum],[current] in the position where you want to show the minimum value and current cart value in the message.', 'woocommerce' ), '' ),
	        );

	        $settings['woodm_delivery_min_amount_msg'] = array(
	            'name' => __( 'Minimum Order Message for "Delivery"*', 'woocommerce-settings-tab-demo' ),
	            'type' => 'textarea',
	            'default' => 'To place an order, you must have an order value of [minimum] minimum. Your current order value is [current].',
	            'id'   => 'woodm_delivery_min_amount_msg',
	            'desc_tip'        => sprintf( __( 'The notice message that appears if the minimum amount is not reached. Insert [minimum],[current] in the position where you want to show the minimum value and current cart value in the message.', 'woocommerce' ), '' ),
	        );

	        $settings['section_end'] = array(
	             'type' => 'sectionend',
	             'id' => 'wc_settings_tab_min_order_amount_section_end'
	        );

  			return apply_filters( 'woodm_settings_min_order_amount', $settings );

  		} else if($current_section == 'delivery-fee') {

  			$settings = array();

	        $settings['section_title'] = array(
	            'name'     => __( 'Order Delivery', 'woocommerce-settings-tab-demo' ),
	            'type'     => 'title',
	            'desc'     => '',
	            'id'       => 'wc_settings_tab_demo_section_title'
	        );
	        $settings['woodm_enable_delivery_fee'] = array(
				'title'           => __( 'Delivery Fee Enable?', 'woocommerce' ),
				'id'              => 'woodm_enable_delivery_fee',
				'default'         => 'no',
				'type'            => 'checkbox',
				'checkboxgroup'   => 'start',
				'show_if_checked' => 'option',
			);
			$settings['woodm_enable_delivery_fee_taxable'] = array(
				'title'           => __( 'Delivery Fee is Taxable?', 'woocommerce' ),
				'id'              => 'woodm_enable_delivery_fee_taxable',
				'default'         => 'no',
				'type'            => 'checkbox',
				'checkboxgroup'   => 'start',
				'show_if_checked' => 'option',
			);
			$settings['woodm_delivery_api_key'] = array(
	            'name' => __( 'Enter Google Map API key', 'woocommerce-settings-tab-demo' ),
	            'type' => 'text',
	            'id'   => 'woodm_delivery_api_key',
	            'desc'     => sprintf( __( '<a href="%s" target="_blank">How to Get API Key?</a>.', 'woocommerce' ), 'https://cloud.google.com/maps-platform/#get-started' ),
	        );
	        $settings['woodm_enable_delivery_range'] = array(
	            'name' => __( 'Enter Miles', 'woocommerce-settings-tab-demo' ),
	            'type' => 'delivery_range',
	            'default' => 10,
	            'id'   => 'woodm_enable_delivery_range',
	        );
			/*$settings['woodm_enable_delivery_fee_field_on'] = array(
				'title'    => __( 'Display Tip/Donation Field On*', 'woocommerce' ),
				'desc'     => __( 'Select pages you want to display tip/donation field.', 'woocommerce' ),
				'id'       => 'woodm_enable_delivery_fee_field_on',
				'default'  => 'both',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'desc_tip' => true,
				'options'  => array(
					'cart'        => __( 'Cart page only', 'woocommerce' ),
					'checkout' => __( 'Checkout page only', 'woocommerce' ),
					'both'   => __( 'Both Cart and Checkout pages', 'woocommerce' ),
				),
			);*/
			$settings['section_end'] = array(
	             'type' => 'sectionend',
	             'id' => 'wc_settings_tab_delivery_fee_end'
	        );

			?>
			<script type="text/template" id="template-add-fee-field">
				<tr>
					<td>
						<input name="{{miles_from_name}}" type="number" value="" class="mile-field from-range" placeholder="<?php _e('Miles', ''); ?>" />
						<span> - </span>
						<input name="{{miles_to_name}}" type="number" value="" class="mile-field to-range" placeholder="<?php _e('Miles', ''); ?>" />
					</td>
					<td>
						<input name="{{fee_name}}" type="number" value="" class="mile-field" placeholder="<?php _e('Fee', ''); ?>" />
					</td>
					<td>
						<span class="delivery-fee-action">
							<a href="#" onclick="add_fee_to_grid(event, this)" data-fee_id="{{fee_id}}"><i alt="f132" class="dashicons dashicons-plus"></i></a>
							<a href="#" onclick="remove_fee_to_grid(event, this)"><i alt="f132" class="dashicons dashicons-minus"></i></a>
						</span>
					</td>
				</tr>
			</script>
			<?php
	        return apply_filters( 'woodm_settings_delivery_fee_tab_settings', $settings );

  		} else {

  			$settings = array();

	        $settings['section_title'] = array(
	            'name'     => __( 'Order Delivery', 'woocommerce-settings-tab-demo' ),
	            'type'     => 'title',
	            'desc'     => '',
	            'id'       => 'wc_settings_tab_demo_section_title'
	        );
	        $settings['woodm_enable_delivery'] = array(
				'title'           => __( 'Enable delivery?', 'woocommerce' ),
				'desc'            => __( 'Enable delivery option to checkout', 'woocommerce' ),
				'id'              => 'woodm_enable_delivery',
				'default'         => 'yes',
				'type'            => 'checkbox',
				'checkboxgroup'   => 'start',
				'show_if_checked' => 'option',
				/* Translators: %s Docs URL. */
				//'desc_tip'        => sprintf( __( 'Enable delivery option to checkout', 'woocommerce' ), '' ),
			);
			/*$settings['woodm_use_store_address'] = array(
				'title'           => __( 'Use default store address?', 'woocommerce' ),
				'id'              => 'woodm_use_store_address',
				'default'         => 'yes',
				'type'            => 'checkbox',
				'checkboxgroup'   => 'start',
				'show_if_checked' => 'option',
				'desc_tip'        => sprintf( __( 'Use woocommerce default store address or not. (<a href="%s" target="_blank">Go to store address</a>).', 'woocommerce' ), 'admin.php?page=wc-settings&tab=general' ),
			);
	        $settings['woodm_store_address'] = array(
	            'name' => __( 'Store Address', 'woocommerce-settings-tab-demo' ),
	            'type' => 'heading',
	            'id'   => 'woodm_store_address',
	        );
	        $settings['woodm_store_address_line_1'] = array(
				'title'    => __( 'Address line 1', 'woocommerce' ),
				'desc'     => __( 'The street address for your business location.', 'woocommerce' ),
				'id'       => 'woocommerce_store_address',
				'default'  => '',
				'type'     => 'text',
				'desc_tip' => true,
			);
			$settings['woodm_store_address_line_2'] = array(
				'title'    => __( 'Address line 2', 'woocommerce' ),
				'desc'     => __( 'An additional, optional address line for your business location.', 'woocommerce' ),
				'id'       => 'woodm_store_address_line_2',
				'default'  => '',
				'type'     => 'text',
				'desc_tip' => true,
			);
			$settings['woodm_store_address_city'] = array(
				'title'    => __( 'City', 'woocommerce' ),
				'desc'     => __( 'The city in which your business is located.', 'woocommerce' ),
				'id'       => 'woodm_store_address_city',
				'default'  => '',
				'type'     => 'text',
				'desc_tip' => true,
			);
			$settings['woodm_store_address_state'] = array(
				'title'    => __( 'Country / State', 'woocommerce' ),
				'desc'     => __( 'The country and state or province, if any, in which your business is located.', 'woocommerce' ),
				'id'       => 'woodm_store_address_state',
				'default'  => 'US',
				'css'      => 'max-width:400px!important;',
				'type'     => 'single_select_country',
				'desc_tip' => true,
			);
			$settings['woodm_store_address_postcode'] = array(
				'title'    => __( 'Postcode / ZIP', 'woocommerce' ),
				'desc'     => __( 'The postal code, if any, in which your business is located.', 'woocommerce' ),
				'id'       => 'woodm_store_address_postcode',
				'css'      => 'min-width:50px;',
				'default'  => '',
				'type'     => 'text',
				'desc_tip' => true,
			);*/
	        $settings['woodm_delivery_time'] = array(
	            'name' => __( 'Delivery Time Message.', 'woocommerce-settings-tab-demo' ),
	            'type' => 'textarea',
	            'default' => __('Allow 45 minutes for delivery.', ''),
	            'id'   => 'woodm_delivery_time',
	        );
	        $settings['woodm_delivery_bgcolor'] = array(
	            'name' => __( 'Delivery Message Background color.', 'woocommerce-settings-tab-demo' ),
	            'type' => 'color',
	            'id'   => 'woodm_delivery_bgcolor',
	            'default'   => '#eeeeee',
	            'placeholder'   => 'Select Color',
	            'css'      => 'max-width:365px;',
	        );
	        $settings['woodm_pickup_time'] = array(
	            'name' => __( 'Pickup Time Message.', 'woocommerce-settings-tab-demo' ),
	            'type' => 'textarea',
	            'default' => __('Pickup 25 Min. - Delivery 45 Min.', ''),
	            'id'   => 'woodm_pickup_time',
	        );
	        $settings['woodm_pickup_bgcolor'] = array(
	            'name' => __( 'Pickup Message Background color.', 'woocommerce-settings-tab-demo' ),
	            'type' => 'color',
	            'id'   => 'woodm_pickup_bgcolor',
	            'default'   => '#eeeeee',
	            'placeholder'   => 'Select Color',
	            'css'      => 'max-width:365px;',
	        );
	        $settings['section_end'] = array(
	             'type' => 'sectionend',
	             'id' => 'wc_settings_tab_demo_section_end'
	        );

	        return apply_filters( 'woodm_settings_delivery_tab_settings', $settings );
  		}
	}

	public function woodm_update_settings() {
		woocommerce_update_options( $this->get_delivery_tab_settings_fields() );
	}

	public function get_default_store_address() {

		$address = '';
		$address .= WC_Countries::get_base_address();
		$address .= ' ';
		$address .= WC_Countries::get_base_address_2();
		$address .= ' ';
		$address .= WC_Countries::get_base_city();
		$address .= ', ';
		$address .= WC_Countries::get_base_postcode();
		$address .= ' ';
		$address .= WC_Countries::get_base_state();
		$address .= ' ';
		$address .= WC_Countries::get_base_country();

		return $address;
	}

	public function woodm_delivery_range_field($value) {

		// Custom attribute handling.
		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		$option_value = $value['value'];
		$_count = count($option_value);

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<table class="delivery-fee-table">
					<thead>
						<tr>
							<th><?php _e('Miles', ''); ?></th>
							<th><?php _e('Fee', ''); ?></th>
							<th><?php _e('Action', ''); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if(!empty($option_value)) { 
							
							for ($i=1; $i <= $_count; $i++) { 
								$elm_value = isset($option_value[$i]) ? $option_value[$i] : 1;
								?>
								<tr>
									<td>
										<input
										name="<?php echo esc_attr( $value['id'] ); ?>[<?php echo $i; ?>][from_miles]"
										id="<?php echo esc_attr( $value['id'] ); ?>"
										type="number"
										value="<?php echo $elm_value['from_miles']; ?>"
										class="mile-field from-range"
										placeholder="<?php _e('Miles', ''); ?>" />
										<span> - </span>
										<input
										name="<?php echo esc_attr( $value['id'] ); ?>[<?php echo $i; ?>][to_miles]"
										id="<?php echo esc_attr( $value['id'] ); ?>"
										type="number"
										value="<?php echo $elm_value['to_miles']; ?>"
										class="mile-field to-range"
										placeholder="<?php _e('Miles', ''); ?>" />
									</td>
									<td>
										<input
										name="<?php echo esc_attr( $value['id'] ); ?>[<?php echo $i; ?>][miles_fee]"
										id="<?php echo esc_attr( $value['id'] ); ?>"
										type="number"
										value="<?php echo $elm_value['miles_fee']; ?>"
										class="mile-field"
										placeholder="<?php _e('Fee', ''); ?>" />
									</td>
									<td>
										<span class="delivery-fee-action">
											<a href="#" onclick="add_fee_to_grid(event, this)" data-fee_id="<?php echo esc_attr( $value['id'] ); ?>"><i alt="f132" class="dashicons dashicons-plus"></i></a>
											<?php if($i != 1) { ?>
												<a href="#" onclick="remove_fee_to_grid(event, this)"><i alt="f132" class="dashicons dashicons-minus"></i></a>
											<?php } ?>
										</span>
									</td>
								</tr>
								<?php 
							}
						} else { ?>
							<tr>
								<td>
									<input
									name="<?php echo esc_attr( $value['id'] ); ?>[1][from_miles]"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="number"
									value=""
									class="mile-field from-range"
									placeholder="<?php _e('Miles', ''); ?>" />
									<span> - </span>
									<input
									name="<?php echo esc_attr( $value['id'] ); ?>[1][to_miles]"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="number"
									value=""
									class="mile-field to-range"
									placeholder="<?php _e('Miles', ''); ?>" />
								</td>
								<td>
									<input
									name="<?php echo esc_attr( $value['id'] ); ?>[1][miles_fee]"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="number"
									value=""
									class="mile-field"
									placeholder="<?php _e('Fee', ''); ?>" />
								</td>
								<td>
									<span class="delivery-fee-action">
										<a href="#" onclick="add_fee_to_grid(event, this)" data-fee_id="<?php echo esc_attr( $value['id'] ); ?>"><i alt="f132" class="dashicons dashicons-plus"></i></a>
										<a href="#" onclick="remove_fee_to_grid(event, this)"><i alt="f132" class="dashicons dashicons-minus"></i></a>
									</span>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
	}

	public function get_store_address() {

	}

	public function woodm_get_delivery_sub_tab() {

		global $current_section;
		$sections = array();
		$sections[''] = __( 'Order Delivery', 'text-domain' );
		$sections['min-order-amount'] = __( 'Minimum Order Amount', 'text-domain' );
		$sections['delivery-fee'] = __( 'Delivery Fee', 'text-domain' );

		echo '<ul class="subsubsub">';

		$array_keys = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=delivery_pickup&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
		}

		echo '</ul><br class="clear" />';
	}

	/**
	 * Output admin fields.
	 *
	 * Loops though the woocommerce options array and outputs each field.
	 *
	 * @param array[] $options Opens array to output.
	 */
	public static function output_fields( $options ) {

		foreach ( $options as $value ) {
			if ( ! isset( $value['type'] ) ) {
				continue;
			}
			if ( ! isset( $value['id'] ) ) {
				$value['id'] = '';
			}
			if ( ! isset( $value['title'] ) ) {
				$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
			}
			if ( ! isset( $value['class'] ) ) {
				$value['class'] = '';
			}
			if ( ! isset( $value['css'] ) ) {
				$value['css'] = '';
			}
			if ( ! isset( $value['default'] ) ) {
				$value['default'] = '';
			}
			if ( ! isset( $value['desc'] ) ) {
				$value['desc'] = '';
			}
			if ( ! isset( $value['desc_tip'] ) ) {
				$value['desc_tip'] = false;
			}
			if ( ! isset( $value['placeholder'] ) ) {
				$value['placeholder'] = '';
			}
			if ( ! isset( $value['suffix'] ) ) {
				$value['suffix'] = '';
			}
			if ( ! isset( $value['value'] ) ) {
				$value['value'] = self::get_option( $value['id'], $value['default'] );
			}

			// Custom attribute handling.
			$custom_attributes = array();

			if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
				foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}

			// Description handling.
			$field_description = WC_Admin_Settings::get_field_description( $value );
			$description       = $field_description['description'];
			$tooltip_html      = $field_description['tooltip_html'];

			// Switch based on type.
			switch ( $value['type'] ) {

				// Section Titles.
				case 'title':
					if ( ! empty( $value['title'] ) ) {
						echo '<h2>' . esc_html( $value['title'] ) . '</h2>';
					}
					if ( ! empty( $value['desc'] ) ) {
						echo '<div id="' . esc_attr( sanitize_title( $value['id'] ) ) . '-description">';
						echo wp_kses_post( wpautop( wptexturize( $value['desc'] ) ) );
						echo '</div>';
					}
					echo '<table class="form-table">' . "\n\n";
					if ( ! empty( $value['id'] ) ) {
						do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) );
					}
					break;

				// Section Ends.
				case 'sectionend':
					if ( ! empty( $value['id'] ) ) {
						do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) . '_end' );
					}
					echo '</table>';
					if ( ! empty( $value['id'] ) ) {
						do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) . '_after' );
					}
					break;

				// Standard text inputs and subtypes like 'number'.
				case 'text':
				case 'password':
				case 'datetime':
				case 'datetime-local':
				case 'date':
				case 'month':
				case 'time':
				case 'week':
				case 'number':
				case 'email':
				case 'url':
				case 'tel':
					$option_value = $value['value'];

					?><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="<?php echo esc_attr( $value['type'] ); ?>"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								value="<?php echo esc_attr( $option_value ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
								<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
								/><?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; // WPCS: XSS ok. ?>
						</td>
					</tr>
					<?php
					break;

				// Color picker.
				case 'color':
					$option_value = $value['value'];

					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">&lrm;
							<span class="colorpickpreview" style="background: <?php echo esc_attr( $option_value ); ?>">&nbsp;</span>
							<input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="text"
								dir="ltr"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								value="<?php echo esc_attr( $option_value ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>colorpick"
								placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
								<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
								/>&lrm; <?php echo $description; // WPCS: XSS ok. ?>
								<div id="colorPickerDiv_<?php echo esc_attr( $value['id'] ); ?>" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>
						</td>
					</tr>
					<?php
					break;

				// Textarea.
				case 'textarea':
					$option_value = $value['value'];

					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<?php echo $description; // WPCS: XSS ok. ?>

							<textarea
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
								<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
								><?php echo esc_textarea( $option_value ); // WPCS: XSS ok. ?></textarea>
						</td>
					</tr>
					<?php
					break;

				// Select boxes.
				case 'select':
				case 'multiselect':
					$option_value = $value['value'];

					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<select
								name="<?php echo esc_attr( $value['id'] ); ?><?php echo ( 'multiselect' === $value['type'] ) ? '[]' : ''; ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
								<?php echo 'multiselect' === $value['type'] ? 'multiple="multiple"' : ''; ?>
								>
								<?php
								foreach ( $value['options'] as $key => $val ) {
									?>
									<option value="<?php echo esc_attr( $key ); ?>"
										<?php

										if ( is_array( $option_value ) ) {
											selected( in_array( (string) $key, $option_value, true ), true );
										} else {
											selected( $option_value, (string) $key );
										}

										?>
									><?php echo esc_html( $val ); ?></option>
									<?php
								}
								?>
							</select> <?php echo $description; // WPCS: XSS ok. ?>
						</td>
					</tr>
					<?php
					break;

				// Radio inputs.
				case 'radio':
					$option_value = $value['value'];

					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<fieldset>
								<?php echo $description; // WPCS: XSS ok. ?>
								<ul>
								<?php
								foreach ( $value['options'] as $key => $val ) {
									?>
									<li>
										<label><input
											name="<?php echo esc_attr( $value['id'] ); ?>"
											value="<?php echo esc_attr( $key ); ?>"
											type="radio"
											style="<?php echo esc_attr( $value['css'] ); ?>"
											class="<?php echo esc_attr( $value['class'] ); ?>"
											<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
											<?php checked( $key, $option_value ); ?>
											/> <?php echo esc_html( $val ); ?></label>
									</li>
									<?php
								}
								?>
								</ul>
							</fieldset>
						</td>
					</tr>
					<?php
					break;

				// Checkbox input.
				case 'checkbox':
					$option_value     = $value['value'];
					$visibility_class = array();

					if ( ! isset( $value['hide_if_checked'] ) ) {
						$value['hide_if_checked'] = false;
					}
					if ( ! isset( $value['show_if_checked'] ) ) {
						$value['show_if_checked'] = false;
					}
					if ( 'yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked'] ) {
						$visibility_class[] = 'hidden_option';
					}
					if ( 'option' === $value['hide_if_checked'] ) {
						$visibility_class[] = 'hide_options_if_checked';
					}
					if ( 'option' === $value['show_if_checked'] ) {
						$visibility_class[] = 'show_options_if_checked';
					}

					if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) {
						?>
							<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
								<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
								<td class="forminp forminp-checkbox">
									<fieldset>
						<?php
					} else {
						?>
							<fieldset class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
						<?php
					}

					if ( ! empty( $value['title'] ) ) {
						?>
							<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
						<?php
					}

					?>
						<label for="<?php echo esc_attr( $value['id'] ); ?>">
							<input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="checkbox"
								class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
								value="1"
								<?php checked( $option_value, 'yes' ); ?>
								<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
							/> <?php echo $description; // WPCS: XSS ok. ?>
						</label> <?php echo $tooltip_html; // WPCS: XSS ok. ?>
					<?php

					if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) {
						?>
									</fieldset>
								</td>
							</tr>
						<?php
					} else {
						?>
							</fieldset>
						<?php
					}
					break;

				// Image width settings. @todo deprecate and remove in 4.0. No longer needed by core.
				case 'image_width':
					$image_size       = str_replace( '_image_size', '', $value['id'] );
					$size             = wc_get_image_size( $image_size );
					$width            = isset( $size['width'] ) ? $size['width'] : $value['default']['width'];
					$height           = isset( $size['height'] ) ? $size['height'] : $value['default']['height'];
					$crop             = isset( $size['crop'] ) ? $size['crop'] : $value['default']['crop'];
					$disabled_attr    = '';
					$disabled_message = '';

					if ( has_filter( 'woocommerce_get_image_size_' . $image_size ) ) {
						$disabled_attr    = 'disabled="disabled"';
						$disabled_message = '<p><small>' . esc_html__( 'The settings of this image size have been disabled because its values are being overwritten by a filter.', 'woocommerce' ) . '</small></p>';
					}

					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
						<label><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html . $disabled_message; // WPCS: XSS ok. ?></label>
					</th>
						<td class="forminp image_width_settings">

							<input name="<?php echo esc_attr( $value['id'] ); ?>[width]" <?php echo $disabled_attr; // WPCS: XSS ok. ?> id="<?php echo esc_attr( $value['id'] ); ?>-width" type="text" size="3" value="<?php echo esc_attr( $width ); ?>" /> &times; <input name="<?php echo esc_attr( $value['id'] ); ?>[height]" <?php echo $disabled_attr; // WPCS: XSS ok. ?> id="<?php echo esc_attr( $value['id'] ); ?>-height" type="text" size="3" value="<?php echo esc_attr( $height ); ?>" />px

							<label><input name="<?php echo esc_attr( $value['id'] ); ?>[crop]" <?php echo $disabled_attr; // WPCS: XSS ok. ?> id="<?php echo esc_attr( $value['id'] ); ?>-crop" type="checkbox" value="1" <?php checked( 1, $crop ); ?> /> <?php esc_html_e( 'Hard crop?', 'woocommerce' ); ?></label>

							</td>
					</tr>
					<?php
					break;

				// Single page selects.
				case 'single_select_page':
					$args = array(
						'name'             => $value['id'],
						'id'               => $value['id'],
						'sort_column'      => 'menu_order',
						'sort_order'       => 'ASC',
						'show_option_none' => ' ',
						'class'            => $value['class'],
						'echo'             => false,
						'selected'         => absint( $value['value'] ),
						'post_status'      => 'publish,private,draft',
					);

					if ( isset( $value['args'] ) ) {
						$args = wp_parse_args( $value['args'], $args );
					}

					?>
					<tr valign="top" class="single_select_page">
						<th scope="row" class="titledesc">
							<label><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp">
							<?php echo str_replace( ' id=', " data-placeholder='" . esc_attr__( 'Select a page&hellip;', 'woocommerce' ) . "' style='" . $value['css'] . "' class='" . $value['class'] . "' id=", wp_dropdown_pages( $args ) ); // WPCS: XSS ok. ?> <?php echo $description; // WPCS: XSS ok. ?>
						</td>
					</tr>
					<?php
					break;

				// Single country selects.
				case 'single_select_country':
					$country_setting = (string) $value['value'];

					if ( strstr( $country_setting, ':' ) ) {
						$country_setting = explode( ':', $country_setting );
						$country         = current( $country_setting );
						$state           = end( $country_setting );
					} else {
						$country = $country_setting;
						$state   = '*';
					}
					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp"><select name="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" data-placeholder="<?php esc_attr_e( 'Choose a country&hellip;', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Country', 'woocommerce' ); ?>" class="wc-enhanced-select">
							<?php WC()->countries->country_dropdown_options( $country, $state ); ?>
						</select> <?php echo $description; // WPCS: XSS ok. ?>
						</td>
					</tr>
					<?php
					break;

				// Country multiselects.
				case 'multi_select_countries':
					$selections = (array) $value['value'];

					if ( ! empty( $value['options'] ) ) {
						$countries = $value['options'];
					} else {
						$countries = WC()->countries->countries;
					}

					asort( $countries );
					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp">
							<select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="width:350px" data-placeholder="<?php esc_attr_e( 'Choose countries&hellip;', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Country', 'woocommerce' ); ?>" class="wc-enhanced-select">
								<?php
								if ( ! empty( $countries ) ) {
									foreach ( $countries as $key => $val ) {
										echo '<option value="' . esc_attr( $key ) . '"' . wc_selected( $key, $selections ) . '>' . esc_html( $val ) . '</option>'; // WPCS: XSS ok.
									}
								}
								?>
							</select> <?php echo ( $description ) ? $description : ''; // WPCS: XSS ok. ?> <br /><a class="select_all button" href="#"><?php esc_html_e( 'Select all', 'woocommerce' ); ?></a> <a class="select_none button" href="#"><?php esc_html_e( 'Select none', 'woocommerce' ); ?></a>
						</td>
					</tr>
					<?php
					break;

				// Days/months/years selector.
				case 'relative_date_selector':
					$periods      = array(
						'days'   => __( 'Day(s)', 'woocommerce' ),
						'weeks'  => __( 'Week(s)', 'woocommerce' ),
						'months' => __( 'Month(s)', 'woocommerce' ),
						'years'  => __( 'Year(s)', 'woocommerce' ),
					);
					$option_value = wc_parse_relative_date_option( $value['value'] );
					?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp">
						<input
								name="<?php echo esc_attr( $value['id'] ); ?>[number]"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="number"
								style="width: 80px;"
								value="<?php echo esc_attr( $option_value['number'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
								step="1"
								min="1"
								<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
							/>&nbsp;
							<select name="<?php echo esc_attr( $value['id'] ); ?>[unit]" style="width: auto;">
								<?php
								foreach ( $periods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $option_value['unit'], $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select> <?php echo ( $description ) ? $description : ''; // WPCS: XSS ok. ?>
						</td>
					</tr>
					<?php
					break;

				// Default: run an action.
				default:
					do_action( 'woocommerce_admin_field_' . $value['type'], $value );
					break;
			}
		}
	}

	/**
	 * Get a setting from the settings API.
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $default     Default value.
	 * @return mixed
	 */
	public static function get_option( $option_name, $default = '' ) {
		if ( ! $option_name ) {
			return $default;
		}
		
		// Array value.
		if ( strstr( $option_name, '[' ) ) {

			parse_str( $option_name, $option_array );

			// Option name is first key.
			$option_name = current( array_keys( $option_array ) );

			// Get value.
			$option_values = get_option( $option_name, '' );

			$key = key( $option_array[ $option_name ] );

			if ( isset( $option_values[ $key ] ) ) {
				$option_value = $option_values[ $key ];
			} else {
				$option_value = null;
			}
		} else {
			// Single value.
			$option_value = get_option( $option_name, null );
		}

		if ( is_array( $option_value ) ) {
			$option_value = $option_value;
		} elseif ( ! is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		return ( null === $option_value ) ? $default : $option_value;
	}
}