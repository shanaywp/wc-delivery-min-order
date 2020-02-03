var ajax_url = woodm_front_data.ajax_url;
jQuery(document).ready(function($){
	//alert(ajax_url);

	jQuery(document).on('change', '#pickupp_or_delivery', function(){
 		
		if(jQuery(this).val() == 'pickup'){
			jQuery('.address_1').hide();
			jQuery('.store_address, .reday_in_time').show();
			jQuery('.reday_in_time.delivery-msg').hide();
			jQuery('.address_1').removeClass('validate-required');
			jQuery('.checkout-address').css({'background-color': woodm_front_data.store_pickup_bg});

			jQuery('#billing_address_1_field').fadeOut();
		    jQuery('#billing_address_1').val('n/a'); 
		    jQuery('#billing_address_2_field').fadeOut();
		    jQuery('#billing_address_2').val('n/a'); 
		    jQuery('#billing_city_field').fadeOut();
		    jQuery('#billing_city').val('n/a'); 
		    jQuery('#billing_postcode_field').fadeOut();
		    jQuery('#billing_postcode').val('90001');
		    jQuery('#billing_state_field').fadeOut();
			
		} else {
			jQuery('.address_1').show();
			jQuery('.store_address, .reday_in_time').hide();
			jQuery('.reday_in_time.delivery-msg').show();
			jQuery('.address_1').addClass('validate-required');
			jQuery('.checkout-address').css({'background-color': woodm_front_data.delivery_bg});

			jQuery('#billing_address_1_field').fadeIn();
		    jQuery('#billing_address_1').val(''); 
		    jQuery('#billing_address_2_field').fadeIn();
		    jQuery('#billing_address_2').val(''); 
		    jQuery('#billing_city_field').fadeIn();
		    jQuery('#billing_city').val(''); 
		    jQuery('#billing_postcode_field').fadeIn();
		    jQuery('#billing_postcode').val('90001');
		    jQuery('#billing_state_field').fadeIn();
			
		}
		jQuery('body').trigger( 'update_checkout' );
	});

 	if(jQuery(document).find('#pickupp_or_delivery').val() == 'pickup' || typeof woodm_front_data.delivery_mod !== "undefined" && woodm_front_data.delivery_mod === 'pickup'){
		
		jQuery('.address_1').hide();
		jQuery('.store_address, .reday_in_time').show();
		jQuery('.reday_in_time.delivery-msg').hide();
		jQuery('.address_1').removeClass('validate-required');
		jQuery('.checkout-address').css({'background-color': woodm_front_data.store_pickup_bg});

		jQuery('#billing_address_1_field').fadeOut();
	    jQuery('#billing_address_1').val('n/a'); 
	    jQuery('#billing_address_2_field').fadeOut();
	    jQuery('#billing_address_2').val('n/a'); 
	    jQuery('#billing_city_field').fadeOut();
	    jQuery('#billing_city').val('n/a'); 
	    jQuery('#billing_postcode_field').fadeOut();
	    jQuery('#billing_postcode').val('');
	    jQuery('#billing_state_field').fadeOut();
		
	} else {
		
		jQuery('.address_1').show();
		jQuery('.store_address, .reday_in_time').hide();
		jQuery('.reday_in_time.delivery-msg').show();
		jQuery('.address_1').addClass('validate-required');
		jQuery('.checkout-address').css({'background-color': woodm_front_data.delivery_bg});

		jQuery('#billing_address_1_field').fadeIn();
	    jQuery('#billing_address_1').val(''); 
	    jQuery('#billing_address_2_field').fadeIn();
	    jQuery('#billing_address_2').val(''); 
	    jQuery('#billing_city_field').fadeIn();
	    jQuery('#billing_city').val(''); 
	    jQuery('#billing_postcode_field').fadeIn();
	    jQuery('#billing_postcode').val('90001');
	    jQuery('#billing_state_field').fadeIn();
	}

	jQuery(document).on('change', '#billing_postcode', function(){
	    jQuery('body').trigger( 'update_checkout' );
	});
});