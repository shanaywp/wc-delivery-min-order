<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 *  Plugin main class
 */
class WOODM_Main {
	
	public function __construct() {

		add_action('wp_enqueue_scripts', array($this, 'woodm_enqueue_script'));
		add_action('admin_enqueue_scripts', array($this, 'woodm_enqueue_admin_script'));
		new WOODM_Delivery_pickup;
		new WOODM_Minimum_Order;
		new WOODM_Delivery_fee;
	}

	public function woodm_enqueue_script() {

		wp_enqueue_style( 'woodm-front-style', WOODM_PLUGIN_URL . '/css/woodm-front.css' );
		wp_enqueue_script( 'woodm-front-script', WOODM_PLUGIN_URL . '/js/woodm-front.js', array('jquery'), TRUE );
	    wp_localize_script( 
            'woodm-front-script',
            'woodm_front_data', 
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'ajax_nonce' => wp_create_nonce('post-woodm-data'),
                'delivery_bg' => get_option('woodm_delivery_bgcolor'),
                'store_pickup_bg' => get_option('woodm_pickup_bgcolor'),
                'delivery_mod' => WC()->session->get( 'store_pickup' ),
            )
        );
	}

	public function woodm_enqueue_admin_script() {
		wp_enqueue_style( 'woodm-front-style', WOODM_PLUGIN_URL . '/css/woodm-admin.css' );
		wp_enqueue_script( 'woodm-front-script', WOODM_PLUGIN_URL . '/js/woodm-admin.js', array('jquery'), TRUE );
	}
}