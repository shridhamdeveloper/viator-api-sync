jQuery(document).ready(function($){
	let productId = $('#vas-product-price').attr('data-product-code');
	$.ajax({
		type: 'POST',
		url: vas_localize_front_data.ajax_url,
		data: {action:"vas_get_product_price_from_api_ajax", product_id:productId,vas_security_nonce:vas_localize_front_data.vas_security_nonce},
		success: function(response){
			response = JSON.parse(response);
			let livePrice = '';
			if(typeof response.currency !== 'undefined'){
				livePrice = response.currency;
			}
			if(typeof response.price !== 'undefined'){
				livePrice = livePrice + ' ' + response.price;
			}
			if(livePrice.length === ""){
				livePrice = "Price Not Available";
			}
			$('#vas-product-price').html('<h3>' + livePrice + '</h3>');
		},
		error: function(error_response){

		}
	});
});