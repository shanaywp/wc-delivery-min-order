<?php
/**
 * Plugin Name: WC Delivery & Min Order
 * Plugin URI: https://www.tidbitsolution.com/
 * Description: Woo Delivery is provide advanced Store Pickup/Delivery option. Enable/Disable Delivery, Delivery Fee, Minimum Order amount functionality.
 * Version: 1.0.0
 * Author: Shanay
 * Author URI: https://www.tidbitsolution.com/
 * Text Domain: wcdm
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define WC_PLUGIN_FILE.
if ( ! defined( 'WOODM_PLUGIN_FILE' ) ) {
	define( 'WOODM_PLUGIN_FILE', __FILE__ );
}

define( 'WOODM_VERSION', '1.0.0' );
define( 'WOODM_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/template/' );
define( 'WOODM_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WOODM_MAIN_FILE', __FILE__ );
define( 'WOODM_ABSPATH', dirname( __FILE__ ) . '/' );

function WOODM_Active() {

	// Require parent plugin
	if( ! is_plugin_active('woocommerce/woocommerce.php') && current_user_can('activate_plugins')) {

		// Stop activation redirect and show error
        wp_die('Sorry, but this plugin requires the Woocommerce Plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
	}
}
register_activation_hook( WOODM_PLUGIN_FILE , 'WOODM_Active');

// Include the main WooCommerce class.
if ( ! class_exists( 'WOODM_Main' ) ) {
	include_once dirname( __FILE__ ) . '/includes/woodm-delivery-pickup.php';
	include_once dirname( __FILE__ ) . '/includes/woodm-minimum-order.php';
	include_once dirname( __FILE__ ) . '/includes/woodm-delivery-fee.php';
	include_once dirname( __FILE__ ) . '/includes/woodm-main.php';
	new WOODM_Main;
}