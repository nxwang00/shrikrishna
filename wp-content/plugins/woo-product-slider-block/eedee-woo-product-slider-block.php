<?php
/**
 * Plugin Name: Product Slider Block for WooCommerce
 * Plugin URI: https://eedee.net/woo-slider-block
 * Description: Powerful Product Slider and Carousel for WooCommerce with different styles and dynamic add to cart button.
 * Author: eedee
 * Author URI: https://eedee.net/
 * Version: 1.0.0
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package eedee_woo_product_slider_block
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block Initializer.
 */
require_once plugin_dir_path( __FILE__ ) . 'src/init.php';
