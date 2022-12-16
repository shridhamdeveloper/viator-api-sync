<?php 
/** 
 * Function to add menus to wordpress dashboard
 * **/
require_once VIATORAS_PLUGIN_DIR.'admin/viator_admin_subpages.php';
require_once VIATORAS_PLUGIN_DIR.'admin/viator_admin_api.php';
require_once VIATORAS_PLUGIN_DIR.'admin/viator_admin_woocommerce.php';

add_action('admin_enqueue_scripts', 'vas_admin_script_and_styles');
function vas_admin_script_and_styles()
{
	wp_register_style('viator-admin-css', VIATORAS_PLUGIN_URL.'assets/admin/css/viator-admin.css', array(), VIATORAS_VERSION);
	wp_enqueue_style('viator-admin-css');
}

add_action('admin_menu', 'vas_option_menu', 1);

function vas_option_menu() {
	add_menu_page('Viator API Sync Options', 'Viator API Sync', 'manage_options', 'viator-api-sync', 'vas_options_page');
}

if(!function_exists('vas_options_page')){
	function vas_options_page(){
		$vas_class_obj = new Vas_Admin_Subpages();
		$vas_class_obj->add_subpage('Settings',  'vas_settings', 'vas_options_setting');
		$vas_class_obj->display();
	}
}

if(!function_exists('vas_options_setting')){
	function vas_options_setting()
	{
		if (isset($_POST['update_options'])) {
			check_admin_referer('viator-api-sync-settings');
			if(isset($_FILES['vas_csv_file'])){
				$uploadedfile = $_FILES['vas_csv_file'];
				$file_mime_type = wp_check_filetype($uploadedfile['name']);
				if(isset($file_mime_type['ext']) && !empty($file_mime_type['ext'])){
					if($file_mime_type['ext'] == 'csv'){
		    			$upload_overrides = array( 'test_form' => false );
						$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
					    if ( $movefile && ! isset( $movefile['error'] ) ) {
					    	$vas_data['vas_csv_path'] = $movefile['file'];
					    	$vas_data['vas_csv_url'] = $movefile['url'];
					    	$vas_data['vas_csv_type'] = $movefile['type'];
		       				update_option('vas_data', $vas_data);
							echo '<div class="notice notice-success is-dismissible"><p>' . __('<b>File uploaded successfully</b>', 'viator_api_sync') . '</p></div>';

							vas_get_csv_file_data();
					    }
					}else{
						echo '<div class="notice notice-error is-dismissible"><p>' . __('<b>Kindly upload CSV file only.</b>', 'viator_api_sync') . '</p></div>';
					}
				}
			}
		}

		global $wpdb;
		$total_product = 0;
		$table_name = $wpdb->prefix."vas_uploaded_products";
		$query = "SELECT count(id) as product_count FROM $table_name";
		$query_result = $wpdb->get_results($query, ARRAY_A);
		if(isset($query_result[0]) && isset($query_result[0]['product_count'])){
			$total_product = $query_result[0]['product_count'];
		}

		$total_imported_product = 0;
		$table_name = $wpdb->prefix."vas_uploaded_products";
		$query = "SELECT count(id) as imported_product FROM $table_name WHERE flag=1";
		$query_result = $wpdb->get_results($query, ARRAY_A);
		if(isset($query_result[0]) && isset($query_result[0]['imported_product'])){
			$total_imported_product = $query_result[0]['imported_product'];
		}
	?>
		<div class="wrap vas-tab-content">
			<?php 
			$style_green = "color: #fff !important; width: 100%; display: inline-block; text-align: center; background-color: #2271b1 !important; height: 30px; display: inline-block;";
			if($total_product != 0){
				if($total_imported_product != 0){
					$progress_bar_width = $total_imported_product / $total_product;
					$progress_bar_width = ceil($progress_bar_width * 100);
					$progress_bar_width = esc_attr(strval($progress_bar_width));

					$style_green = "color: #fff !important; background-color: #4caf50 !important; width: $progress_bar_width"."%; height: 30px; display: inline-block; text-align: center";
				} 
			?>
				<div style="color: #000 !important; background-color: #f1f1f1 !important;height: 30px;">
				  <div style="<?php echo $style_green; ?>"><?= $total_imported_product ?> Products Imported Out Of <?= $total_product ?></div>
				</div><br>
			<?php 
			}
			?>
			<form method="post" action="" enctype='multipart/form-data'>
				<div class="vas_settings_div">
					<h3><?php echo esc_html__('Upload CSV', 'viator_api_sync') ?></h3>
					<ul>
						<li>
							<div>
		                        <input type="file" name="vas_csv_file" >                     
		                        <p><?php echo esc_html__('Click here to download sample CSV Format', 'viator_api_sync') ?></p>
		                    </div>
						</li>
					</ul>
				</div>
				<div class="submit"><input type="submit" class="button button-primary" name="update_options" value="<?php echo esc_html__('Save Settings', 'viator_api_sync') ?>" /></div>
				<?php if (function_exists('wp_nonce_field')) wp_nonce_field('viator-api-sync-settings'); ?>
			</form>
		</div>	
	<?php
	}
}