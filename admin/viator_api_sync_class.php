<?php

/**
 * Viator_Admin_Csv
 * @author 		Magazine3
 * This class is used to retrieve CSV file contents
 */

define('VAS_API_END_POINT', 'https://api.viator.com/partner/'); // LIVE END POINT
define('VAS_API_KEY', 'fbdd5f54-a066-42fd-9bc0-9df61b72db79'); // LIVE API KEY
define('VAS_API_CONTENT_TYPE', 'application/json;version=2.0'); // LIVE API KEY

// define('VAS_API_END_POINT', 'https://api.sandbox.viator.com/partner/'); // TEST END POINT
// define('VAS_API_KEY', 'bcac8986-4c33-4fa0-ad3f-75409487026c'); // TEST API KEY

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
								vas_fetch_product_details($ccd_value['product_code']);
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
		$url = VAS_API_END_POINT.$method;
		$product_response = vas_wp_http_methods($url, 'GET');
		// $product_response = file_get_contents('D:/Shrikant/Magzine3-Technology/Office-material/viator/viator-product-details-response.json');
		$product_response = json_decode($product_response, true);
		if(isset($product_response['status']) && $product_response['status'] == 'ACTIVE'){
			$product_details = vas_format_product_response($product_response);
			vas_add_external_product($product_details);
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

function vas_wp_http_methods($url, $type='GET',$request_data='')
{
	$ch = curl_init();
	$headers = array(
		"exp-api-key: ".VAS_API_KEY,
		"Accept-Language: en-US",
		"Accept: ".VAS_API_CONTENT_TYPE,
		"Content-Type: ".VAS_API_CONTENT_TYPE,
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

	$response = curl_exec($ch);
	curl_close($ch);

	if(!empty($response)){
		$response = gzdecode($response);
	}
	return $response;
}

/** 
 * Format API response to one standard format
 * @arguments
 * product_details: Details received from API
 */

function vas_format_product_response($product_details)
{
	$formatted_response = array();
	$formatted_response['title'] = isset($product_details['title'])?$product_details['title']:'';
	$formatted_response['description'] = isset($product_details['description'])?$product_details['description']:'';
	$formatted_response['productCode'] = isset($product_details['productCode'])?$product_details['productCode']:'';
	$formatted_response['productUrl'] = isset($product_details['productUrl'])?$product_details['productUrl']:'';
	$formatted_response['images'] = isset($product_details['images'])?$product_details['images']:'';
	$formatted_response['destinationId'] = isset($product_details['destinations'][0])?$product_details['destinations'][0]['ref']:'';

	$product_categories = array(); $sub_term_details = array(); $parent_term_details = array();
	// Get destination details from Database Table
	if(isset($formatted_response['destinationId']) && !empty($formatted_response['destinationId'])){	
		$destination_details = vas_get_destination_details($formatted_response['destinationId']);
		if(empty($destination_details)){
			vas_fetch_viator_destination_details_from_api();
			$destination_details = vas_get_destination_details($formatted_response['destinationId']);
		}
		if(!empty($destination_details)){
			$sub_term_details = get_term_by('name',$destination_details['destinationName'],'product_cat');
			$parent_dest_details = vas_get_destination_parent_details($destination_details['parentId']);
			if(!empty($parent_dest_details)){
				$parent_term_details = get_term_by('name',$parent_dest_details['destinationName'],'product_cat');
			}


			if(!$parent_term_details){
				$category_slug = 'vas-parent-cat-'. strtolower($parent_dest_details['destinationName']);
				wp_insert_term( $parent_dest_details['destinationName'], 'product_cat', array(
				    'description' => 'Country', 
				    'parent' => 0,
				    'slug' => $category_slug
				) );

				$parent_term_details = get_term_by('name',$parent_dest_details['destinationName'],'product_cat');
			}

			if(!$sub_term_details){
				$category_slug = 'vas-sub-cat-'. strtolower($destination_details['destinationName']);
				wp_insert_term( $destination_details['destinationName'], 'product_cat', array(
				    'description' => 'City', 
				    'parent' => isset($parent_term_details->term_id)?$parent_term_details->term_id:0 ,
				    'slug' => $category_slug
				) );

				$sub_term_details = get_term_by('name',$destination_details['destinationName'],'product_cat');
			}	
		}	
	} 

	if(!empty($parent_term_details) && $sub_term_details){
		$product_categories[] = $parent_term_details->term_id; 
		$product_categories[] = $sub_term_details->term_id; 
	}else if(!empty($parent_term_details) && empty($sub_term_details)){
		$product_categories[] = $parent_term_details->term_id;
	}
	else if(empty($parent_term_details) && !empty($sub_term_details)){
		$product_categories[] = $sub_term_details->term_id;
	}
	$formatted_response['categoryId'] = $product_categories;
	return $formatted_response;	
}

/** 
 * Check for existing destional details, if destionation doesn't exist the create new table
 * @arguments
 * destionation_id: Destionation id to get the details
 */
function vas_get_destination_details($destination_id='')
{
	global $table_prefix; global $wpdb; $destination_details = array();
	if(!empty($destination_id)){
		$table_name = $table_prefix."vas_api_destinations";
		$query = "SELECT * FROM $table_name WHERE destinationId=$destination_id";
		$destination_results = $wpdb->get_results($query, ARRAY_A);
		if(!empty($destination_results)){
			$destination_details['parentId'] = $destination_results[0]['parentId'];
			$destination_details['destinationName'] = $destination_results[0]['destinationName'];
			$destination_details['destinationType'] = $destination_results[0]['destinationType'];
			$destination_details['destinationId'] = $destination_results[0]['destinationId'];
		}else{
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
				id INT(11) NOT NULL AUTO_INCREMENT , 
				sortOrder INT(11) NULL , 
				selectable TINYINT NOT NULL DEFAULT '0' , 
				destinationUrlName VARCHAR(255) NULL , 
				defaultCurrencyCode VARCHAR(255) NULL , 
				lookupId VARCHAR(255) NULL , 
				parentId INT(255) NOT NULL DEFAULT '0' , 
				timeZone VARCHAR(255) NULL , 
				destinationName VARCHAR(255) NOT NULL , 
				destinationId INT(11) NOT NULL DEFAULT '0' , 
				destinationType VARCHAR(255) NULL , 
				latitude VARCHAR(255) NULL , 
				longitude VARCHAR(255) NOT NULL , PRIMARY KEY (`id`), 
				INDEX vas_api_dest_name (destinationName), 
				INDEX vas_api_dest_id (destinationId), 
				INDEX vas_api_dest_type (destinationType)) ENGINE = InnoDB;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	    	dbDelta($sql);
	    }
	}
	return $destination_details;
}

function vas_fetch_viator_destination_details_from_api()
{
	global $wpdb; global $table_prefix;
	$method = 'v1/taxonomy/destinations';
	$url = VAS_API_END_POINT.$method;
	$destination_response = vas_wp_http_methods($url, 'GET');
	// $destination_response = file_get_contents('D:/Shrikant/Magzine3-Technology/Office-material/viator/viator-destination-response.json');
	$destination_response = json_decode($destination_response, true);
	$table_name = $table_prefix.'vas_api_destinations';
	if(isset($destination_response['data']) && isset($destination_response['data'][0])){
		if(is_array($destination_response['data'])){
			foreach ($destination_response['data'] as $dest_key => $dest_value) {
				$insert_data['sortOrder'] = $dest_value['sortOrder'];
				$insert_data['selectable'] = $dest_value['selectable'];
				$insert_data['destinationUrlName'] = $dest_value['destinationUrlName'];
				$insert_data['defaultCurrencyCode'] = $dest_value['defaultCurrencyCode'];
				$insert_data['lookupId'] = $dest_value['lookupId'];
				$insert_data['parentId'] = $dest_value['parentId'];
				$insert_data['timeZone'] = $dest_value['timeZone'];
				$insert_data['destinationName'] = strtoupper($dest_value['destinationName']);
				$insert_data['destinationId'] = $dest_value['destinationId'];
				$insert_data['destinationType'] = strtoupper($dest_value['destinationType']);
				$insert_data['latitude'] = $dest_value['latitude'];
				$insert_data['longitude'] = $dest_value['longitude'];
				$wpdb->insert($table_name, $insert_data);	
			}
		}
	}
}

function vas_get_destination_parent_details($parent_id='')
{
	global $wpdb; global $table_prefix; 
	$country_details = array();
	$table_name = $table_prefix.'vas_api_destinations';
	if(!empty($parent_id)){
		$destination_country_details = vas_get_destination_country_details($parent_id);
		if(isset($destination_country_details[0]) && !empty($destination_country_details)){
			if($destination_country_details[0]['destinationType'] == 'REGION'){
				$destination_country_details = vas_get_destination_country_details($destination_country_details[0]['parentId']);
				if(isset($destination_country_details[0]) && !empty($destination_country_details)){
					if($destination_country_details[0]['destinationType'] == 'COUNTRY'){
						$country_details = $destination_country_details[0];
					}
				}
			}else if($destination_country_details[0]['destinationType'] == 'COUNTRY'){
				$country_details = $destination_country_details[0];
			}
		}
	}
	return $country_details;
}

function vas_get_destination_country_details($parent_id='')
{
	global $wpdb; global $table_prefix;
	$destination_results = array();
	if(!empty($parent_id)){
		$table_name = $table_prefix."vas_api_destinations";
		$query = "SELECT * FROM $table_name WHERE destinationId=$parent_id";
		$destination_results = $wpdb->get_results($query, ARRAY_A);
	}
	return $destination_results;
}