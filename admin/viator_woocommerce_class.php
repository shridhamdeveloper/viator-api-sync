<?php

/**
 * Viator_Admin_Csv
 * @author 		Magazine3
 * This class is used to retrieve CSV file contents
 */

function vas_add_external_product($product_details)
{
    // echo "<pre>product_details===== "; print_r($product_details); die;
    $external_product = new WC_Product_External();
    $slug = 'vas-'.$product_details['productCode'];
    $external_product->set_name($product_details['title']);
    $external_product->set_slug($slug);
    $external_product->set_description($product_details['description']);
    $external_product->set_product_url($product_details['productUrl']);
    $external_product->set_button_text('Book On Viator');
    $external_product->set_category_ids($product_details['categoryId']);
    // $external_product->set_image_id("https://media.tacdn.com/media/attractions-splice-spp-674x446/07/47/d9/70.jpg");
    $external_product->save();
    if(isset($product_details['images']) && !empty($product_details['images'])){
        $product_code = $product_details['productCode'];
        vas_upload_product_images($external_product->id, $product_details['images'], $product_code);
    }
}

function vas_upload_product_images($id='', $images='', $product_code='')
{
    if(!empty($id) && !empty($images)){
        $attachment_id = array();
        if(is_array($images)){
            $image_cnt = 1;
            foreach ($images as $pi_key => $pi_value) {
                if(isset($pi_value['variants']) && is_array($pi_value['variants'])){
                    foreach ($pi_value['variants'] as $vari_key => $vari_value) {
                        if($vari_value['height'] == 446 && $vari_value['width'] == 674){
                            $image_url = ''; $image_name = ''; $post_name = '';
                            $image_url = $vari_value['url'];
                            $image_mime = wp_get_image_mime($image_url);
                            $mime_type = '';
                            if($image_mime){
                                $mime_type = explode('/', $image_mime)[1];
                            }
                            if($pi_value['isCover']){ 
                                $post_name =  'Product-'. $product_code .'-' . $image_cnt;
                                $image_name = $post_name.'.'.$mime_type; 
                            }else{
                                $post_name =  'Product-Gallery'. $product_code .'-' . $image_cnt;
                                $image_name = $post_name.'.'.$mime_type;  
                            }
                            $attachment_id[] = vas_save_image_to_wp_uploads($image_name, $image_url, $id, $post_name, $image_mime);
                        }
                    }
                    $image_cnt++;
                }
            }
        }
        if(count($attachment_id) > 0){
            set_post_thumbnail( $id, $attachment_id[0]);
            array_shift($attachment_id);
            update_post_meta($id, '_product_image_gallery', implode(',', $attachment_id));
        }
    }
}

function vas_save_image_to_wp_uploads($image_name='', $image_url='', $id='', $post_name='', $image_mime=''){
    if(!empty($image_name) && !empty($image_url)){
        require_once( ABSPATH . 'wp-admin/includes/file.php' );

        // download to temp dir
        $temp_file = download_url( $image_url );

        if( is_wp_error( $temp_file ) ) {
            return false;
        }

        // move the temp file into the uploads directory
        $file = array(
            'name'     => $image_name,
            'type'     => $image_mime,
            'tmp_name' => $temp_file,
            'size'     => filesize( $temp_file ),
        );
        $sideload = wp_handle_sideload(
            $file,
            array(
                'test_form'   => false // no needs to check 'action' parameter
            )
        );

        if( ! empty( $sideload[ 'error' ] ) ) {
            // you may return error message if you want
            return false;
        }

        // it is time to add our uploaded image into WordPress media library
        $attachment_id = wp_insert_attachment(
            array(
                'guid'           => $sideload[ 'url' ],
                'post_mime_type' => $sideload[ 'type' ],
                'post_title'     => $post_name,
                'post_content'   => '',
                'post_status'    => 'inherit',
                'post_type' => 'attachment',
            ),
            $sideload[ 'file' ], 
            $id
        );

        if( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            return false;
        }

        // update medatata, regenerate image sizes
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        wp_update_attachment_metadata(
            $attachment_id,
            wp_generate_attachment_metadata( $attachment_id, $sideload[ 'file' ] )
        );
        return $attachment_id;
    }

}