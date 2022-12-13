<?php

/**
 * Viator_Admin_Csv
 * @author 		Magazine3
 * This class is used to retrieve CSV file contents
 */
class Viator_Api_Sync_Class{

	function __construct()
	{
		// $this->endpoint = 'https://api.sandbox.viator.com/partner/'; // Test API Endpoint
		$this->endpoint = 'https://api.viator.com/partner/'; // Live API Endpoint
		$this->api_key = 'fbdd5f54-a066-42fd-9bc0-9df61b72db79'; // Live API Key
		// $this->api_key = 'bcac8986-4c33-4fa0-ad3f-75409487026c'; // Test API Key
		$this->content_type = 'application/json;version=2.0';
	}

	/** 
	 * Fetches value from uploaded CSV file 
	 */
	function vas_get_csv_file_data(){
		$response['status'] = true;
		$response['message'] = 'Success';
		$csv_file_details = get_option('vas_data');
		if(isset($csv_file_details) && !empty($csv_file_details)){
			if(isset($csv_file_details['vas_csv_path']) && file_exists($csv_file_details['vas_csv_path'])){
				$cols = array(); $client_csv_data = array();
				$handle_csv = fopen($csv_file_details['vas_csv_path'], "r");
				if(!empty($handle_csv)){
					$i = 0; $col_head_count = 0;
  					while(($line = fgetcsv($handle_csv)) !== FALSE) {
  						if($i == 0) {
				          	$c = 0;
				          	if(!empty($line)){
				          		$col_head_count = count($line);
					          	foreach($line as $col) {
					              $cols[$c] = trim(strtolower(strtoupper($col)));
					              $c++;
					          	}
				      		}
				      	} else if($i > 0) {
				          	$c = 0;
				          	if(!empty($line)){
					          	if(count($line) == $col_head_count){
						          	foreach($line as $col) {
						            	$client_csv_data[$i][$cols[$c]] = $col;
						            	$c++;
						          	}
					          	}
					        }
				      	}
				      	$i++;
  					}	
  					if(!empty($client_csv_data)){
  						$api_result = array(); $product_codes = array();
  						foreach ($client_csv_data as $ccd_key => $ccd_value) {
  							if(!empty($ccd_value['product_code'])){
  								// $product_codes[] = trim($ccd_value['product_code']);
  								$this->vas_fetch_product_details($ccd_value['product_code']);
  							}	
  						}
  					}
				}else{
					$response['status'] = false;
					$response['message'] = 'Error opening CSV file, please check';
				}
			}else{
				$response['status'] = false;
				$response['message'] = 'CSV file missing. kindly upload and try again';
			}
		}
		else{
			$response['status'] = false;
			$response['message'] = 'CSV file missing. kindly upload and try again';
		}
	}

	function vas_fetch_product_details($product_code)
	{
		if(!empty($product_code)){
			$method = 'products/'.$product_code;
			$url = $this->endpoint.$method;
			$product_response = $this->vas_wp_http_methods($url, 'GET');
			$product_response = json_decode($product_response, true);
			if(isset($product_response['status']) && $product_response['status'] == 'ACTIVE'){
				$product_details = $this->vas_format_product_response($product_response);
				$vas_woo_obj = new Viator_Woocommerce_Class();
				$vas_woo_obj->vas_add_external_product($product_details);
				// echo "<pre>product_details===== "; print_r($product_details); die;
			}
		}
	}

	/** 
	 *  PHP Curl API request method
	 * @arguments
	 * url : Endpoint URL for API method
	 * type: API request method (GET, POST etc)
	 * request_data: Request data for API method if any
	 */

	public function vas_wp_http_methods($url, $type='GET',$request_data='')
	{
		$ch = curl_init();
		$headers = array(
			"exp-api-key: $this->api_key",
			"Accept-Language: en-US",
			"Accept: $this->content_type",
			"Content-Type: $this->content_type",
			"Accept-Encoding: gzip"
		);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if($type == 'POST'){
			curl_setopt($ch, CURLOPT_POST, true);
		}
		if(!empty($request_data)){
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		// $response = curl_exec($ch);
		curl_close($ch);

		if(empty($response)){
			// $response = gzdecode($response);
			$response = file_get_contents('D:/Shrikant/Magzine3-Technology/Office-material/viator/viator-product-details-response.json');
		}
		return $response;
	}

	/** 
	 * Format API response to one standard format
	 * @arguments
	 * product_details: Details received from API
	 */

	public function vas_format_product_response($product_details)
	{
		$formatted_response = array();
		$formatted_response['title'] = isset($product_details['title'])?$product_details['title']:'';
		$formatted_response['description'] = isset($product_details['description'])?$product_details['description']:'';
		$formatted_response['productCode'] = isset($product_details['productCode'])?$product_details['productCode']:'';
		$formatted_response['productUrl'] = isset($product_details['productUrl'])?$product_details['productUrl']:'';
		return $formatted_response;
		// echo "<pre>formatted_response===== "; print_r($formatted_response); die;	
	}
}