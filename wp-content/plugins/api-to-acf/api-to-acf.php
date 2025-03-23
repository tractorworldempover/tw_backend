<?php
/*
Plugin Name: Inventory to ACF
Description: Fetches data from an API and stores it in ACF fields for custom post types on Live Inventory.
Version: 1.0
Author: G SAI KUMAR
*/

// Schedule the API fetch event if not already scheduled
if (!wp_next_scheduled('fetch_tractor_data_event')) {
    wp_schedule_event(time(), 'daily', 'fetch_tractor_data_event');
    error_log('Scheduled event "fetch_tractor_data_event" registered.');
}

// Hook our function to the scheduled event
add_action('fetch_tractor_data_event', 'fetch_and_insert_tractors');

// Fetch data from API and insert/update posts
function fetch_and_insert_tractors() {
    error_log('Starting fetch_and_insert_tractors function.');

    $response = wp_remote_get('https://used-tractor-backend.azurewebsites.net/inventory/web/v2/tractor/', array('timeout' => 20));

    if (is_wp_error($response)) {
        error_log('Error fetching data from API: ' . $response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    error_log('API response body: ' . $body);

    $response_data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        return;
    }

    if (empty($response_data['data'])) {
        error_log('No data returned from API or data is empty');
        return;
    }

    $data = $response_data['data'];

    // Ensure Polylang is active
    if (!function_exists('pll_set_post_language')) {
        error_log('Polylang is not active');
        return;
    } else {
        error_log('Polylang is active');
    }

    $insert_count = 0;
    foreach ($data as $item) {
        error_log('Insert record the limit of ' . $insert_count++ . '/1000 records.');
        if ($insert_count >= 1000) {
            error_log('Reached the limit of 1000 records.');
            break;
        }

        // Check if the required keys exist in the item
        if (!isset($item['brand']) || !isset($item['model']) || !isset($item['tractor_id'])) {
            error_log('Missing required fields in item: ' . json_encode($item));
            continue;
        }

        // Create categories based on state and user_location
        $state = isset($item['state']) ? $item['state'] : 'Unknown State';
        $location = isset($item['user_location']) ? $item['user_location'] : 'Unknown Location';

        $state_cat_id = wp_create_category($state);
        $location_cat_id = wp_create_category($location, $state_cat_id);

        // Check for duplicate posts by brand and model
        $existing_post_id = get_existing_post_id_by_title($item['brand'], $item['model']);
        if ($existing_post_id) {
            error_log('Duplicate post found for brand: ' . $item['brand'] . ', model: ' . $item['model']);
            continue;
        }

        // Insert or update the post
        $post_id = $existing_post_id ? $existing_post_id : wp_insert_post(array(
            'post_title' => $item['brand'] . ' ' . $item['model'],
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'live-inventory',
        ));

        if ($post_id) {
            error_log(($existing_post_id ? 'Post updated' : 'Post created') . ' with ID: ' . $post_id);

            // Assign categories to the post
            wp_set_post_categories($post_id, array($state_cat_id, $location_cat_id));
            
            // Determine the language of the post
            $language = 'en'; // Default language
            if (isset($item['language'])) {
                $language = $item['language']; // Assuming 'language' is a key in your API response
            }

            // Set post language
            if (!pll_set_post_language($post_id, $language)) {
                error_log('Failed to set language for post ID: ' . $post_id . ' to ' . $language);
            }

            // Update ACF fields
            update_field('tractor_id', $item['tractor_id'], $post_id);
            update_field('tractor_version_id', isset($item['tractor_version_id']) ? $item['tractor_version_id'] : '', $post_id);
            update_field('status', isset($item['status']) ? $item['status'] : '', $post_id);
            update_field('additional_data_available', isset($item['additional_data_available']) ? $item['additional_data_available'] : false, $post_id);
            update_field('product_link', isset($item['product_link']) ? $item['product_link'] : '', $post_id);
            update_field('created_at', isset($item['created_at']) ? $item['created_at'] : '', $post_id);
            update_field('updated_at', isset($item['updated_at']) ? $item['updated_at'] : '', $post_id);
            update_field('inventory_type', isset($item['inventory_type']) ? $item['inventory_type'] : '', $post_id);

            // Handle expense_data
            $expense_data = isset($item['expense_data']) ? $item['expense_data'] : array();
            update_field('expense_data', array(
                'expense_last_updated_at' => isset($expense_data['expense_last_updated_at']) ? $expense_data['expense_last_updated_at'] : '',
                'sourcing' => isset($expense_data['sourcing']) ? $expense_data['sourcing'] : false,
                'liquidation' => isset($expense_data['liquidation']) ? $expense_data['liquidation'] : false,
                'operation' => isset($expense_data['operation']) ? $expense_data['operation'] : false,
            ), $post_id);

            update_field('is_verified', isset($item['is_verified']) ? $item['is_verified'] : '', $post_id);
            update_field('pricing_data', isset($item['pricing_data']) ? $item['pricing_data'] : '', $post_id);
            update_field('user_id', isset($item['user_id']) ? $item['user_id'] : '', $post_id);
            update_field('user_location', isset($item['user_location']) ? $item['user_location'] : '', $post_id);
            update_field('state', isset($item['state']) ? $item['state'] : '', $post_id);
            update_field('district', isset($item['district']) ? $item['district'] : '', $post_id);

            // Ensure you have the correct post ID
            error_log('Updating post ID: ' . $post_id);

            // Extract and log the additional_featureInfo array
            $additional_featureInfo = isset($item['additional_featureInfo']) ? $item['additional_featureInfo'] : array();
            $normalized_additional_featureInfo = array();
            foreach ($additional_featureInfo as $key => $value) {
                $normalized_key = preg_replace('/\s+/', ' ', trim($key));
                $normalized_additional_featureInfo[$normalized_key] = $value;
                error_log('Normalized Key: ' . $normalized_key . ' Value: ' . $value);
            }

            error_log('Brake condition: ' . $normalized_additional_featureInfo['Brake condition']);

            // Update ACF fields
            $update_result = update_field('additional_featureInfo', array(
                'ignition' => isset($normalized_additional_featureInfo['Ignition']) ? $normalized_additional_featureInfo['Ignition'] : '',
                'clutch_type' => isset($normalized_additional_featureInfo['Clutch type']) ? $normalized_additional_featureInfo['Clutch type'] : '',
                'steering_type' => isset($normalized_additional_featureInfo['Steering type']) ? $normalized_additional_featureInfo['Steering type'] : '',
                'seat_condition' => isset($normalized_additional_featureInfo['Seat condition']) ? $normalized_additional_featureInfo['Seat condition'] : '',
                'hydraulic_hitch' => isset($normalized_additional_featureInfo['Hydraulic Hitch']) ? $normalized_additional_featureInfo['Hydraulic Hitch'] : '',
                'brake_condition' => isset($normalized_additional_featureInfo['Brake condition']) ? $normalized_additional_featureInfo['Brake condition'] : '',
                'drawbar_available' => isset($normalized_additional_featureInfo['Drawbar available']) ? $normalized_additional_featureInfo['Drawbar available'] : '',
                'bumper_available' => isset($normalized_additional_featureInfo['Bumper available']) ? $normalized_additional_featureInfo['Bumper available'] : '',
                'insurance_validity' => isset($normalized_additional_featureInfo['Insurance validity']) ? $normalized_additional_featureInfo['Insurance validity'] : '',
                'hydraulic_condition' => isset($normalized_additional_featureInfo['Hydraulic condition']) ? $normalized_additional_featureInfo['Hydraulic condition'] : '',
                'hypothecation_status' => isset($normalized_additional_featureInfo['Hypothecation Status']) ? $normalized_additional_featureInfo['Hypothecation Status'] : '',
                'financing_eligibility' => isset($normalized_additional_featureInfo['Financing eligibility']) ? $normalized_additional_featureInfo['Financing eligibility'] : '',
                'registration_rto_no' => isset($normalized_additional_featureInfo['Registration (RTO) No.']) ? $normalized_additional_featureInfo['Registration (RTO) No.'] : '',
                'trolley_hook_available' => isset($normalized_additional_featureInfo['Trolley Hook available']) ? $normalized_additional_featureInfo['Trolley Hook available'] : '',
                'buyer_kyc_form_2930' => isset($normalized_additional_featureInfo['Buyer  KYC (Form 29,30)']) ? $normalized_additional_featureInfo['Buyer  KYC (Form 29,30)'] : '',
                'documentation_rc_available' => isset($normalized_additional_featureInfo['Documentation/RC available']) ? $normalized_additional_featureInfo['Documentation/RC available'] : '',
                'tractor_trailer_available' => isset($normalized_additional_featureInfo['Tractor Trailer available']) ? $normalized_additional_featureInfo['Tractor Trailer available'] : '',
                'tractor_rotavator_available' => isset($normalized_additional_featureInfo['Tractor Rotavator available']) ? $normalized_additional_featureInfo['Tractor Rotavator available'] : '',
            ), $post_id);

            if ($update_result) {
                error_log('ACF fields updated successfully for post ID: ' . $post_id);
            } else {
                error_log('Failed to update ACF fields for post ID: ' . $post_id);
            }

            if (!empty($item['images'])) {
                update_field('images', json_encode($item['images']), $post_id);
            }

            if (!empty($item['processed_images'])) {
                update_field('processed_images', json_encode($item['processed_images']), $post_id);
            }
            if (!empty($item['image_links'])) {
                update_field('image_links', json_encode($item['image_links']), $post_id);
            }

            update_field('year', isset($item['year']) ? $item['year'] : '', $post_id);
            update_field('max_price', isset($item['max_price']) ? $item['max_price'] : '', $post_id);
            update_field('min_price', isset($item['min_price']) ? $item['min_price'] : '', $post_id);
            update_field('brand', isset($item['brand']) ? $item['brand'] : '', $post_id);
            update_field('model', isset($item['model']) ? $item['model'] : '', $post_id);
            update_field('owner', isset($item['owner']) ? $item['owner'] : '', $post_id);
            update_field('reg_no', isset($item['reg_no']) ? $item['reg_no'] : '', $post_id);
            update_field('source', isset($item['source']) ? $item['source'] : '', $post_id);
            update_field('battery', isset($item['battery']) ? $item['battery'] : false, $post_id);
            update_field('comment', isset($item['comment']) ? $item['comment'] : '', $post_id);
            update_field('finance', isset($item['finance']) ? $item['finance'] : '', $post_id);
            update_field('video_url', isset($item['video_url']) ? $item['video_url'] : '', $post_id);
            update_field('drive_type', isset($item['drive_type']) ? $item['drive_type'] : '', $post_id);

            if (!empty($item['inv_images'])) {
                update_field('inv_images', json_encode($item['inv_images']), $post_id);
            }

            update_field('tyre_state', isset($item['tyre_state']) ? $item['tyre_state'] : '', $post_id);
            update_field('buying_year', isset($item['buying_year']) ? $item['buying_year'] : '', $post_id);
            update_field('engine_hours', isset($item['engine_hours']) ? $item['engine_hours'] : '', $post_id);
            update_field('engine_power', isset($item['engine_power']) ? $item['engine_power'] : '', $post_id);
            update_field('chassis_number', isset($item['chassis_number']) ? $item['chassis_number'] : '', $post_id);
            update_field('tyre_condition', isset($item['tyre_condition']) ? $item['tyre_condition'] : '', $post_id);
            update_field('engine_condition', isset($item['engine_condition']) ? $item['engine_condition'] : '', $post_id);
            update_field('is_tyre_brand_mrf', isset($item['is_tyre_brand_mrf']) ? $item['is_tyre_brand_mrf'] : false, $post_id);
            update_field('is_battery_branded', isset($item['is_battery_branded']) ? $item['is_battery_branded'] : false, $post_id);
            update_field('rating', isset($item['rating']) ? $item['rating'] : 0, $post_id);
            update_field('rating_scores', isset($item['rating_scores']) ? json_encode($item['rating_scores']) : '', $post_id);
        }
    }
}

// Utility function to get existing post ID by brand and model
function get_existing_post_id_by_title($brand, $model) {
    $title = $brand . ' ' . $model;
    $args = array(
        'post_type' => 'live-inventory',
        'post_status' => 'publish',
        'title' => $title,
        'fields' => 'ids'
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) {
        return $query->posts[0];
    }
    return false;
}

// Clear scheduled event upon plugin deactivation
register_deactivation_hook(__FILE__, 'deactivate_plugin');
function deactivate_plugin() {
    wp_clear_scheduled_hook('fetch_tractor_data_event');
    error_log('Scheduled event "fetch_tractor_data_event" cleared.');
}

add_action('admin_init', 'manual_fetch_and_insert_tractors');

function manual_fetch_and_insert_tractors() {
    if (current_user_can('administrator')) {
        fetch_and_insert_tractors();
        error_log('Manual fetch_and_insert_tractors triggered.');
        
        // Remove the action hook after inserting 10 records
        remove_action('admin_init', 'manual_fetch_and_insert_tractors');
    }
}  
