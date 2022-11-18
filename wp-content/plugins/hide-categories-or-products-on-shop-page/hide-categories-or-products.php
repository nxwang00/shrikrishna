<?php
/**
 * Plugin Name:       Hide Categories Or Products On Shop Page
 * Plugin URI:        #
 * Description:       Hide the categories or products from shop page woocommerce.
 * Version:           1.0.3
 * Author:            Kaushik Nakrani 
 * Author URI:        https://profiles.wordpress.org/kaushikankrani/
 * License: GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hcps
 * Tested up to:      5.6.2
 * Domain Path:       /languages
 */

/*
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Defines a named
 */
define('HCPS_TEXTDOMAIN', 'hcps');
define('HCPS_DIR', plugin_dir_url( __FILE__ ));

/*
 *Create a class called "Hide_Categories_Or_Products" if it doesn't already exist
 */
if ( !class_exists( 'Hide_Categories_Or_Products' ) ) {

	Class Hide_Categories_Or_Products{
		
		public function __construct() {

			add_action('init',[$this,'admin_notice_hcps']);
			add_action('admin_enqueue_scripts',[$this,'admin_scripts_hcps']);
  			add_action('init', [ $this,'plugins_loaded_hcps']);
			add_action( 'template_redirect', [$this,'redirect_page_404']);
		}
		
		//Fire notice for admin side
		public function admin_notice_hcps() {

			if (!defined('WC_VERSION')) {
				add_action('admin_notices', array($this, 'wc_load_hcps'));
			}
			else{
				add_action( 'admin_menu', array($this, 'admin_menu_hcps'),100);
			}			
		}
		//Fire error message
		public function wc_load_hcps() {
		
            $buy_now_url = esc_url('https://woocommerce.com/');

            $message = '<p><strong>' . __('Hide Categories Or Products On Shop Page', HCPS_TEXTDOMAIN) . '</strong>' . __(' plugin not working because you need to install the WooCommerce plugin', HCPS_TEXTDOMAIN) . '</p>';
            $message .= '<p>' . sprintf('<a href="%s" class="button-primary" target="_blank">%s</a>', $buy_now_url, __('Get WooCommerce', HCPS_TEXTDOMAIN)) . '</p>';
        
			echo '<div class="error"><p>' . $message . '</p></div>';
		}
		
		//Add menu in admin side
		public function admin_menu_hcps(){
			
			add_submenu_page( 'woocommerce',  __( 'WooCommerce scategory hide', HCPS_TEXTDOMAIN), 'Hide Categories Or Products', 'manage_woocommerce','wc-hcps', 'show_menu_hcps');
			
			/**
			 * @return html Display
			 */
			function show_menu_hcps(){
				include_once('includes/hcps-admin-function.php');
				include_once('includes/hcps-html.php');
			}
		}
		
		/*
		 *@return style and script in admin site
		 */
		public function admin_scripts_hcps(){
			wp_enqueue_script('hcps_category_js', HCPS_DIR. '/assets/admin/js/hcps_category_option.js',array());
			wp_enqueue_style('hcps_category_css', HCPS_DIR. '/assets/admin/css/hcps_category_option.css',array());
			wp_enqueue_style('hcps_admin_css', HCPS_DIR. '/assets/admin/css/hcps_admin.css',array());
			wp_enqueue_script('hcps_plugin', HCPS_DIR. '/assets/admin/js/hcps_admin.js',array());
		}

		/**
		 *Hide category and product
		 */
		public function plugins_loaded_hcps() {

			$select_values = get_option('hcps_data');
			
			if(!empty($select_values)){
	
				if(array_key_exists("widget", $select_values)){
					if ($select_values['widget'] == 'yes') {
						add_filter("get_terms",[ $this,"hide_category_widget"],10,3);
					}
				}
				
				//check option
				if(array_key_exists("category", $select_values) && array_key_exists("options", $select_values)){
					$option_check = $select_values['options'];
					if(is_array($option_check)){
						if(in_array('category',$option_check)){
							add_filter('get_terms',[ $this,'category_hide_hcps'],10,3);
						}
						if(in_array('product',$option_check)){
							add_action('woocommerce_product_query',[$this,'product_hide_hcps']);
						}
						if(in_array('category',$option_check) && in_array('product',$option_check)){
							add_filter('get_terms',[ $this,'category_hide_hcps'],10,3);
							add_action('woocommerce_product_query',[$this,'product_hide_hcps']);
						}
					}
				} 
			}			 
		}
		/**
		 * Hide category on widget
		 *
		 */
		public function hide_category_widget( $terms, $taxonomies, $args ) : array {
			
			$new_terms 	= array();
			$hide_category 	= get_option('hcps_data'); 
		 	if(!empty($hide_category['category'])){
				
				if(!empty($hide_category['widget'])){
					if($hide_category['widget'] == 'yes'){
						 if ( in_array( 'product_cat', $taxonomies ) && !is_admin() && !empty($args['class'])) {
								foreach ( $terms as $key => $term ) {
									if ( ! in_array( $term->term_id, $hide_category['category'] ) ) { 
										$new_terms[] = $term;
									}
								}
							$terms = $new_terms;
						 }
					}
				}
			}
			return $terms;
		}
		
		/**
		 * Hide product on shop page
		 *
		 * @return object if admin will select the hide product  
		 */
		public function product_hide_hcps($data) : void {
			$hcps_datas = get_option('hcps_data');
			$tax_query = (array) $data->get( 'tax_query' );
			$select_values = $hcps_datas['category'];
			$tax_query[] = array(
				   'taxonomy' => 'product_cat',
				   'field' => 'term_id',
				   'terms' => $select_values,
				   'operator' => 'NOT IN'
			);
			if (!is_admin()) {
				$data->set( 'tax_query', $tax_query );
			}			
		}

		/**
		 * Hide product on shop page
		 *
		 * @return array if admin will select the hide category  
		 */
		public function category_hide_hcps( $terms, $taxonomies, $args ) : array {
			
			$new_terms 	= array();
			$hide_category 	= get_option('hcps_data'); 
		 	if(!empty($hide_category['category'])){
				
				if ( in_array( 'product_cat', $taxonomies ) && !is_admin() && empty($args['class'])) {
						foreach ( $terms as $key => $term ) {
							if(!empty($term->term_id)){
								if ( ! in_array( $term->term_id, $hide_category['category'] ) ) { 
										$new_terms[] = $term;
								}
							}
						}
					$terms = $new_terms;
				} 
			}
			return $terms;
		}
		/**
		 * redirect page on 404
		 *
		 */
		function redirect_page_404() {
			
			global $wp_query;
			$select_values = get_option('hcps_data');
			$curent_page_id = $wp_query->get_queried_object_id();
			$product_id = get_the_id();
			
			if(!empty($select_values['not_found'])){
				if($select_values['not_found'] == 'yes'){
					if(array_key_exists("options", $select_values)){
						if ( is_product() ){
							if(!empty($product_id)){
								$result_return = $this->exit_post_id($product_id,$select_values['category']);
								if($result_return == 1){
									$wp_query->set_404();
									status_header( 404 );
									get_template_part( 404 );
									exit();
								}
							}
						}
						if(in_array($curent_page_id,$select_values['category'])){
							
							$wp_query->set_404();
							status_header( 404 );
							get_template_part( 404 );
							exit(); 
						}
					}
				}
			}
		}
		
		public function exit_post_id($product_id,$category_id){
			$return_id ='';
			$objects = get_objects_in_term($category_id, 'product_cat'); 
			if(!empty($objects)){
				if(in_array($product_id,$objects)){
					$return_id = true;	
				}		
			}
			return $return_id;
		}
	}	
}
/*
 * Created new object of the Hide_Categories_Or_Products.
 */
new Hide_Categories_Or_Products();