<!--specific hide categories section start-->
<?php
$cate_data = new \includes\data_hcps\Shop_Data_Hcps();
$cate_data->save_data_hcps();
$hcps_array = get_option('hcps_data');

$cate_checked = '';
$pro_checked = '';
$hwidget_show = '';
$hwidget_hide = '';
$hpage_show = '';
$hpage_hide = '';

if(!empty($hcps_array)){
	
	if(array_key_exists('options',$hcps_array) && in_array("category", $hcps_array['options'])){
		$cate_checked = _x('checked', HCPS_TEXTDOMAIN);
	}
	if(array_key_exists('options',$hcps_array) && in_array("product", $hcps_array['options'])){
		$pro_checked = _x('checked', HCPS_TEXTDOMAIN);
	}
	if(array_key_exists('widget',$hcps_array) && $hcps_array['widget'] == "yes"){
		$hwidget_show = _x('checked', HCPS_TEXTDOMAIN);
	}
	if(array_key_exists('widget',$hcps_array) && $hcps_array['widget'] == "no"){
		$hwidget_hide = _x('checked', HCPS_TEXTDOMAIN);
	}
	if(array_key_exists('not_found',$hcps_array) && $hcps_array['not_found'] == "yes"){
		$hpage_show = _x('checked', HCPS_TEXTDOMAIN);
	}
	if(array_key_exists('not_found',$hcps_array) && $hcps_array['not_found'] == "no"){
		$hpage_hide = _x('checked', HCPS_TEXTDOMAIN);
	}
}
?>
<div class="wrap">
	<div class="hcps_title">
		<h1><?php _e('Specific Hide Categories Or Products', HCPS_TEXTDOMAIN); ?></h1>
	</div>
	
	<div class="hcps_form_category">
	<!--Form section start-->
	<form  method="post" name="hcps_form" class="hcpsform">
		<table>
			<tbody>
				<tr>
					<th>
						<label><?php _e('Select Category Name With Slug', HCPS_TEXTDOMAIN); ?></label>
					</th>
					<td>
						<select data-placeholder="Please select categories" multiple class="hcps_select" name="select_hcategory[]">
							<option value=""></option>
								<?php
								 	$list_category = $cate_data->show_category_hcps();
									
								 	foreach($list_category as $key => $show_category){
									 	
									 	$scategory_id = $show_category['id'];	
									 	$select_values = $cate_data->select_category_values($scategory_id);		

										if($scategory_id == $select_values){
										echo '<option value="'.$scategory_id.'" selected>'.$show_category['name'].' ('.$show_category['slug'].')</option>';
										}
										else{
											echo '<option value="'.$scategory_id.'">'.$show_category['name'].' ('.$show_category['slug'].')</option>';
										}
									}
								?>
						</select>
					</td>
				</tr>
				<tr>
					<!-- Hide opation section start -->
					<th>
						<label><?php _e('Options', HCPS_TEXTDOMAIN); ?></label>
					</th>
					<td colspan="2">
						<label class="hcps_che">
							<input type="checkbox" name="categ_hcps[]" id="categ_hcps" value="category" <?php echo $cate_checked;?>>
							<span><?php _e('Hide Category', HCPS_TEXTDOMAIN); ?></span>
						</label>
						<label class="hcps_che">
							<input type="checkbox" name="categ_hcps[]" id="prod_hcps" value="product" <?php echo $pro_checked;?>>
							<span><?php _e('Hide Product', HCPS_TEXTDOMAIN); ?></span>
						</label>
					</td>
					<!-- Hide opation section end -->
				</tr>
				<tr>
					<!-- Hide opation section start -->
					<th>
						<label><?php _e('Hide Category From Widget', HCPS_TEXTDOMAIN); ?></label>
					</th>
					<td colspan="2">
						<label class="hcps_che">
							<input type="radio" name="hide_widget" id="yhide_widget" value="yes" <?php echo $hwidget_show;?>>
							<span><?php _e('Yes', HCPS_TEXTDOMAIN); ?></span>
						</label>
					
						<label class="hcps_che">
							<input type="radio" name="hide_widget" id="nhide_widget" value="no" <?php echo $hwidget_hide;?>>
							<span><?php _e('No', HCPS_TEXTDOMAIN);?></span>
						</label>
					</td>
					<!-- Hide opation section end -->
				</tr>
				<tr>
					<!-- Hide opation section start -->
					<th>
						<label><?php _e('Hide Category/Product and Show The 404 Page', HCPS_TEXTDOMAIN); ?></label>
					</th>
					<td colspan="2">
						<label class="hcps_che">
							<input type="radio" name="page_redirect" id="page_redirect" value="yes" <?php echo $hpage_show;?>><span><?php _e('Yes', HCPS_TEXTDOMAIN);?></span>
						</label>
						<label class="hcps_che">
							<input type="radio" name="page_redirect" id="page_redirect" value="no" <?php echo $hpage_hide;?>><span><?php _e('No', HCPS_TEXTDOMAIN);?></span>
						</label>
					</td>
					<!-- Hide opation section end -->
					
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<input type="submit" name="hide_cate" class="button button-primary" value="Save">
		</p>
		<?php 
		wp_nonce_field( 'nonce_hcps_submit', 'nonce_hcps_data' );
		?>
	 </form>
	 <!--Form section end-->
</div>
</div>
<!--specific hide categories section end-->