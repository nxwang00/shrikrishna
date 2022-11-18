<?php
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.0.0
 * @package eedee_woo_product_slider_block
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side rendering of the `core/latest-posts` block.
 *
 * @package WordPress
 */

/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * Assets enqueued:
 * 1. blocks.style.build.css - Frontend + Backend.
 * 2. blocks.build.js - Backend.
 * 3. blocks.editor.build.css - Backend.
 *
 * @uses {wp-blocks} for block type registration & related functions.
 * @uses {wp-element} for WP Element abstraction — structure of blocks.
 * @uses {wp-i18n} to internationalize the block's text.
 * @uses {wp-editor} for WP editor styles.
 * @since 1.0.0
 */
function eedee_woo_product_slider_block_assets() { // phpcs:ignore
	// Register block styles for both frontend + backend.
	wp_register_style(
		'eedee-woo-product-slider-style-css', // Handle.
		plugins_url( 'dist/blocks.style.build.css', dirname( __FILE__ ) ), // Block style CSS.
		array( 'wp-editor' ), // Dependency to include the CSS after it.
		filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.style.build.css' ) // Version: File modification time.
	);

	// Register block editor script for backend.
	wp_register_script(
		'eedee-woo-product-slider-block-js', // Handle.
		plugins_url( '/dist/blocks.build.js', dirname( __FILE__ ) ), // Block.build.js: We register the block here. Built with Webpack.
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ), // Dependencies, defined above.
		filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: filemtime — Gets file modification time.
		true // Enqueue the script in the footer.
	);

	// Register block editor styles for backend.
	wp_register_style(
		'eedee-woo-product-slider-block-editor-css', // Handle.
		plugins_url( 'dist/blocks.editor.build.css', dirname( __FILE__ ) ), // Block editor CSS.
		array( 'wp-edit-blocks' ), // Dependency to include the CSS after it.
		filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.editor.build.css' ) // Version: File modification time.
	);

	/**
	 * Register Gutenberg block on server-side.
	 *
	 * Register the block on server-side to ensure that the block
	 * scripts and styles for both frontend and backend are
	 * enqueued when the editor loads.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type#enqueuing-block-scripts
	 * @since 1.16.0
	 */
	register_block_type(
		'eedee/woo-product-slider-block',
		array(
			// Enqueue blocks.style.build.css on both frontend & backend.
			'style'         => 'eedee-woo-product-slider-style-css',
			// Enqueue blocks.build.js in the editor only.
			'editor_script' => 'eedee-woo-product-slider-block-js',
			// Enqueue blocks.editor.build.css in the editor only.
			'editor_style'  => 'eedee-woo-product-slider-block-editor-css',
		)
	);

	$eedee_woo_slider_block_variables = array(
		'adminPluginsUrl' => network_admin_url( 'plugin-install.php' ),
		'isWooActive'     => in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ),
	);
	wp_localize_script( 'eedee-woo-product-slider-block-js', 'eedeeWooProductSlider', $eedee_woo_slider_block_variables );
}

/**
 * Register Frontend Assets for Woo Product Slider
 */
function eedee_woo_product_slider_frontend_assets() {
	// Register block editor script for frontend.
	wp_register_script(
		'eedee-woo-product-slider-block-js-front', // Handle.
		plugins_url( '/dist/blocks.frontend.build.js', dirname( __FILE__ ) ), // Block.build.js: We register the block here. Built with Webpack.
		// wp-elemnt for react, wp-api-fetch.
		array( 'wp-element', 'wp-api-fetch' ), // Dependencies, defined above.
		filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: filemtime — Gets file modification time.
		true // Enqueue the script in the footer.
	);

	wp_enqueue_script( 'eedee-woo-product-slider-block-js-front' );
}

// Hook: Block assets.
add_action( 'init', 'eedee_woo_product_slider_block_assets' );

add_action( 'wp_enqueue_scripts', 'eedee_woo_product_slider_frontend_assets' );
