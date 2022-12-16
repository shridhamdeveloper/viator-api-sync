<?php

add_action( 'woocommerce_single_product_summary', 'custom_button_by_categories', 36 ,0 );
function custom_button_by_categories(){
    global $product;
    $product_code = strtoupper($product->slug);
    echo '<div id="vas-product-price" data-product-code="'.esc_attr($product_code).'" class="vas-loading"><h3>Fetching Price Please Wait</h3></div>';
}

add_action('wp_enqueue_scripts', 'vas_front_css_and_js');
function vas_front_css_and_js()
{
	if(is_product()){

		wp_enqueue_style('vas-front-custom-css', VIATORAS_PLUGIN_URL . 'assets/frontend/css/vas-front.css', array(), VIATORAS_VERSION , true );

		$local = array(     		   
			'ajax_url'                     => admin_url( 'admin-ajax.php' ),            
			'vas_security_nonce'           => wp_create_nonce('vas_ajax_check_nonce'),
			'post_id'                      => get_the_ID()
		); 
		wp_register_script('vas-front-custom-js', VIATORAS_PLUGIN_URL . 'assets/frontend/js/vas-front.js', array('jquery'), VIATORAS_VERSION , true );                        
		wp_localize_script('vas-front-custom-js', 'vas_localize_front_data', $local );        
		wp_enqueue_script('vas-front-custom-js');
	}
}

add_action( 'wp_ajax_nopriv_vas_get_product_price_from_api_ajax', 'vas_get_product_price_from_api');  
add_action( 'wp_ajax_vas_get_product_price_from_api_ajax', 'vas_get_product_price_from_api') ; 
function vas_get_product_price_from_api()
{
	$response['status'] = false;
	$response = array();
	 if (isset($_POST['vas_security_nonce']) &&  wp_verify_nonce( $_POST['vas_security_nonce'], 'vas_ajax_check_nonce' ) ){
	 	if(isset($_POST['product_id']) && !empty($_POST['product_id'])){
		 	$product_code = sanitize_text_field($_POST['product_id']);
			$method = 'availability/schedules/'.$product_code;
			$url = VAS_API_END_POINT.$method;
			$product_avail_resp = vas_wp_http_methods($url, 'GET');
			if(!empty($product_avail_resp)){
				$product_avail_resp = json_decode($product_avail_resp, true);
				if(isset($product_avail_resp['currency']) && !empty($product_avail_resp['currency'])){
					$response['currency'] = isset($product_avail_resp['currency'])?$product_avail_resp['currency']:'';
					$response['status'] = true;
				}

				if(isset($product_avail_resp['summary']) && !empty($product_avail_resp['summary'])){
					$response['price'] = isset($product_avail_resp['summary']['fromPrice'])?$product_avail_resp['summary']['fromPrice']:0;
					$response['status'] = true;
				}
			}
		}
	 }
	 echo json_encode($response);
	wp_die();
}