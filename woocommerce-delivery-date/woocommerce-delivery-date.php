<?php
/**
 * Plugin Name: WooCommerce Subscriptions Delivery Date Feature
 * Description: Create a simple plugin that adds a “Delivery Date” field to both simple and variable subscription products..
 * Version: 1.0.0
 * Author: Umair Iqbal
 * Text Domain: woocommerce-delivery-date
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-delivery-date.php';


add_action( 'plugins_loaded', 'wc_delivery_date_init' );
function wc_delivery_date_init() {
    WC_Delivery_Date::instance();
}
