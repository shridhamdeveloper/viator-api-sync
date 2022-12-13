<?php

/**
 * Viator_Admin_Csv
 * @author 		Magazine3
 * This class is used to retrieve CSV file contents
 */
class Viator_Woocommerce_Class{

    public function vas_add_external_product($product_details)
    {
        $external_product = new WC_Product_External();
        $slug = 'vas-'.$product_details['productCode'];
        $external_product->set_name($product_details['title']);
        $external_product->set_slug($slug);
        $external_product->set_description($product_details['description']);
        $external_product->set_product_url($product_details['productUrl']);
        $external_product->set_button_text('Book On Viator');
        $external_product->save();
    }
}