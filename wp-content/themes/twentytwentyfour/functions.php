<?php

// Hook into the admin menu setup
add_action( 'admin_menu', 'remove_default_menus' );

function remove_default_menus() {
    // Remove the comments menu item
    remove_menu_page( 'edit-comments.php' );

    // Remove the pages menu item
    remove_menu_page( 'edit.php?post_type=page' );

    // Remove the posts menu item
    remove_menu_page( 'edit.php' );
    
    remove_menu_page( 'themes.php' );
}

// Disable core updates
add_filter( 'pre_site_transient_update_core', '__return_null' );
add_filter( 'pre_site_transient_update_plugins', '__return_null' );
add_filter( 'pre_site_transient_update_themes', '__return_null' );

// Disable plugin updates
add_filter( 'site_transient_update_plugins', '__return_empty_array' );

// Disable theme updates
add_filter( 'site_transient_update_themes', '__return_empty_array' );

// Disable automatic updates (including core, plugins, and themes)
define( 'AUTOMATIC_UPDATER_DISABLED', true ); 
 
//states and towns 
function fetch_states_and_towns() {
    
   // error_log('API calling for states and towns');
    
    $response = wp_remote_get('https://used-tractor-backend.azurewebsites.net/user/web/user-location-details/');
    
    if (is_wp_error($response)) {
      //  error_log('API call error: ' . $response->get_error_message());
        return;
    }
    
    $data = wp_remote_retrieve_body($response);
    
   // error_log('API raw response body for state and towns: ' . $data);
    
    $respdata = json_decode($data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
         error_log('JSON decode error: ' . json_last_error_msg());
        return;
    }
    
   // error_log('API response body for state and towns: ' . print_r($respdata, true));
    
    return $respdata;
}
 
function save_states_and_towns_to_acf() {
    $data = fetch_states_and_towns();
    if (!$data) {
        return;
    }

    foreach ($data['data'] as $town_name => $town_info) {
        $state_name = $town_info['stores__geo_data__state'];
        $state_id = null;

        // Find or create a post for the state
        $states = get_posts([
            'post_type' => 'states_towns',
            'meta_key' => 'state',
            'meta_value' => $state_name,
            'posts_per_page' => 1,
        ]);

        if ($states) {
            $state_id = $states[0]->ID;
        } else {
            // Create a new state post if not found
            $state_id = wp_insert_post([
                'post_title' => $state_name,
                'post_type' => 'states_towns',
                'post_status' => 'publish',
            ]);
            update_field('state', $state_name, $state_id);
        }

        // Add town to the state
        if ($state_id) {
            $towns_list = get_field('towns_list', $state_id);
            if (!$towns_list) {
                $towns_list = [];
            } else {
                $towns_list = explode(', ', $towns_list);
            }

            // Add the new town
            $towns_list[] = $town_name;
            $towns_list = array_unique($towns_list); // Remove duplicates
            update_field('towns_list', implode(', ', $towns_list), $state_id);
            
        }
    }
}
add_action('init', 'save_states_and_towns_to_acf');

 
function add_custom_columns($columns) {
    $columns['state'] = __('State');
    $columns['towns'] = __('Towns');
    return $columns;
}
add_filter('manage_states_towns_posts_columns', 'add_custom_columns');

// Populate the custom columns with the ACF data
function custom_column_content($column, $post_id) {
    if ($column == 'state') {
        $state = get_field('state', $post_id);
        error_log('State for post ID ' . $post_id . ': ' . $state);
        echo esc_html($state ? $state : 'No state');
    }

    if ($column == 'towns') {
        $towns_list = get_field('towns_list', $post_id);
        
        // Ensure $towns_list is a string and not empty
        if ($towns_list) {
            // Split the comma-separated list into an array
            $towns = explode(', ', $towns_list);
            // Display the towns as a comma-separated list
            echo esc_html(implode(', ', $towns));
        } else {
            error_log('No towns found for post ID ' . $post_id);
            echo 'No towns found';
        }
    }
}
add_action('manage_states_towns_posts_custom_column', 'custom_column_content', 10, 2);


//brands models
// 
function fetch_brands_and_models() {
    
   // error_log('API calling for brands and models');
    
    $response = wp_remote_get('https://used-tractor-backend.azurewebsites.net/inventory/v1/brand_and_model');
    
    if (is_wp_error($response)) {
      //  error_log('API call error: ' . $response->get_error_message());
        return;
    }
    
    $data = wp_remote_retrieve_body($response);
    
   // error_log('API raw response body for brands and models: ' . $data);
    
    $respdata = json_decode($data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
         error_log('JSON decode error: ' . json_last_error_msg());
        return;
    }
    
   // error_log('API response body for brands and models: ' . print_r($respdata, true));
    
    return $respdata;
}
// 
function save_brands_and_models_to_acf() {
    $data = fetch_brands_and_models(); // Ensure this function returns your brand data
    if (!$data) {
        return;
    }

    foreach ($data['data'] as $brand_name => $brand_info) {
        
       $brand_id = null; 
        // Find or create a post for the brand
        $brands = get_posts([
            'post_type' => 'brandmodel',
            'meta_query' => [
                [
                    'key' => 'brand',
                    'value' => $brand_name,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1
        ]);

        if ($brands) {
            $brand_id = $brands[0]->ID;
        } else {
            // Create a new brand post if not found
            $brand_id = wp_insert_post([
                'post_title' => $brand_name,
                'post_type' => 'brandmodel',
                'post_status' => 'publish',
            ]);
        }

        // Update ACF fields
        if ($brand_id) {
            update_field('brand', $brand_name, $brand_id);
            update_field('brand_logo', $brand_info['logo'], $brand_id);
            update_field('models', implode(', ', $brand_info['models']), $brand_id);
        }
}
}
add_action('init', 'save_brands_and_models_to_acf');
 
// Populate the custom columns with the ACF data
function add_custom_columns_for_brands_models($columns) {
    $columns['brand'] = __('Brand');
    $columns['logo'] = __('Logo');
    $columns['models'] = __('Models');
    return $columns;
}
add_filter('manage_brandmodel_posts_columns', 'add_custom_columns_for_brands_models');
 
function custom_column_content_brands_models($column, $post_id) {
     if ($column == 'brand') {
        $brand = get_field('brand', $post_id);
        if($brand){
            echo esc_html($brand);
        } 
      }
     if ($column == 'logo') {
        $logo = get_field('brand_logo', $post_id);
        if ($logo) {
            echo '<img src="' . esc_url($logo) . '" style="max-width: 100px; height: auto;" />';
        } else {
            echo 'No logo';
        }
    }

    if ($column == 'models') {
        $models = get_field('models', $post_id);
        if ($models) {
            echo esc_html($models);
        } else {
            echo 'No models';
        }
    }
}
add_action('manage_brandmodel_posts_custom_column', 'custom_column_content_brands_models', 10, 2);

//filters for state in graphql
function register_state_filter_to_graphql( $allowed_filters ) {
  $allowed_filters[] = 'state'; // Add 'state' as a filter
  return $allowed_filters;
}
add_filter( 'graphql_allowed_query_input_fields', 'register_state_filter_to_graphql' ); 
?>