<?php
add_filter( 'cron_schedules', 'isa_add_every_thirty_seconds' );
function isa_add_every_thirty_seconds( $schedules ) {
    $schedules['every_thirty_seconds'] = array(
            'interval'  => 15,
            'display'   => __( 'Every 30 Seconds', 'textdomain' )
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'isa_add_every_thirty_seconds' ) ) {
    wp_schedule_event( time(), 'every_thirty_seconds', 'isa_add_every_thirty_seconds' );
}

// Hook into that action that'll fire every thirty seconds
add_action( 'isa_add_every_thirty_seconds', 'every_thirty_seconds_event_func' );
function every_thirty_seconds_event_func() {
	global $wpdb;
	$table_name = $wpdb->prefix."vas_uploaded_products";
	$query = "SELECT * FROM $table_name WHERE flag=0 LIMIT 5";
	$product_details = $wpdb->get_results($query, ARRAY_A);
	if(!empty($product_details)){
		if(is_array($product_details)){
			foreach ($product_details as $pro_key => $pro_value) {
				if(isset($pro_value['product_code']) && !empty($pro_value['product_code'])){
					vas_fetch_product_details($pro_value['product_code']);
				}
			}
		}
	}
}