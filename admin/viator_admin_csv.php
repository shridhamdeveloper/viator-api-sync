<?php

/**
 * This class is used to retrieve CSV file contents
 */
class Viator_Admin_Csv{

	// Retrieve CSV file contents
	function vas_get_csv_file_data(){
		$csv_file_details = get_option('vas_data');
		if(isset($csv_file_details) && !empty($csv_file_details)){
			if(isset($csv_file_details['vas_csv_path']) && file_exists($csv_file_details['vas_csv_path'])){
				echo '<pre>csv_file_details===== '; print_r($csv_file_details); die;
			}
		}
	}

	
}