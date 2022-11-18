<?php
/**
 * Categories and products option save
 */
namespace includes\data_hcps;

/*
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}
/*
 *Create a class called "Shop_Data_Hcps" if it doesn't already exist
 */
if ( !class_exists( 'Shop_Data_Hcps' ) ) {
	
	class Shop_Data_Hcps{
		
		public function __construct() {
			add_action('admin_save_hcps',array($this,'save_data_hcps'));
		}
		public function show_category_hcps(): array {
			
			$category_args = array(
				 'taxonomy'     => 'product_cat',
				 'orderby'      => 'name',
				 'show_count'   => 0,
				 'pad_counts'   => 0,
				 'hierarchical' => 1,
				 'title_li'     => '',
				 'hide_empty'   => 0
			);
			
			$all_categories = get_categories($category_args);
			foreach($all_categories as $key=> $category_data){
				$array_category[$key]['name'] =  $category_data->name;
				$array_category[$key]['id'] =  $category_data->term_id;
				$array_category[$key]['slug'] =  $category_data->slug;
			}

			return $array_category;
		}
		
		/*
		 *Save the category name.
		 */
		public function save_data_hcps(){
			if(isset($_POST['hide_cate'])){
				
				if(isset($_POST['nonce_hcps_data']) || wp_verify_nonce($_POST['nonce_hcps_data'])){
					
					if(!empty($_POST['select_hcategory'])){
						$hcps_data_option['category'] = sanitize_meta('select_category',$_POST['select_hcategory'],'category' );
					}
					if(!empty($_POST['categ_hcps'])){
						$hcps_data_option['options'] = sanitize_meta('select_option',$_POST['categ_hcps'],'option');
					}
					
					if(!empty($_POST['hide_widget'])){
						$hcps_data_option['widget'] = sanitize_meta('select_widget',$_POST['hide_widget'],'widget');
					}
					
					if(!empty($_POST['page_redirect'])){
						$hcps_data_option['not_found'] = sanitize_meta('select_page',$_POST['page_redirect'],'not_found');
					}
					//check empty or not
					if(!empty($hcps_data_option)){
						update_option( 'hcps_data', $hcps_data_option);
					}
					else{
						update_option( 'hcps_data', '');
					}
					
				}
			}
		}

		/*
		 * @return selected option data 
         */
		public function select_category_values($id){

			$select_values = get_option('hcps_data');
			
			if(!empty($select_values['category'])){
				foreach ($select_values['category'] as $key => $value) {
					if($id == $value){
						$select_values = $value;
					}					
				}
			}
			return $select_values;
		}
	}
}
/*
 * Created new object of the Shop_Data_Hcps.
 */
new \includes\data_hcps\Shop_Data_Hcps();