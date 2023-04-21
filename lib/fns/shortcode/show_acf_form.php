<?php

namespace DonationManager\shortcodes;
use function DonationManager\utilities\{get_alert};


/**
 * Shows the ACF organization form.
 *
 * @return     string  HTML for displaying the form
 */
function show_acf_organization_form(){
  $current_user = wp_get_current_user();
  $organization_id = get_user_meta( $current_user->ID, 'organization', true );

  if( $organization_id ){
    ob_start();
    acf_form(
      [
        'post_id' => $organization_id,
        'post_title' => false,
        'fields' => ['monthly_report_emails', 'website', 'pickup_settings' ],
        'submit_value' => 'Save',
        'updated_message' => get_alert( ['description' => 'Your information has been saved.', 'type' => 'info'] ),
      ]
    );
    return ob_get_clean();
  } else {
    return get_alert([ 'description' => 'Error: No Organization assigned to user.' ]);
  }
}
add_shortcode( 'show_acf_organization_form', __NAMESPACE__ . '\\show_acf_organization_form' );


function show_acf_transdept_form(){
    $current_user = wp_get_current_user();
    $organization_id = get_user_meta( $current_user->ID, 'organization', true );

    if( $organization_id ){
        $post_id = 'new_post';
        $field_values = array();

        // Check if the form has been submitted
        if( isset( $_POST['acf'] ) && wp_verify_nonce( $_POST['acf']['_acf_nonce'], 'new_post' ) ) {
            // Save the form data
            $post_id = acf_form_save_post( 'new_post' );
            
            // Automatically assign the organization of the user to the organization field
            if( $post_id ) {
                update_field( 'organization', $organization_id, $post_id );
            }
        } else {
            // Retrieve the saved field values if the form hasn't been submitted
            $args = array(
                'post_type' => 'trans_dept',
                'post_status' => 'publish',
                'author' => $current_user->ID,
                'posts_per_page' => 1
            );
            $trans_depts = get_posts( $args );
            if( $trans_depts ) {
                $post_id = $trans_depts[0]->ID;
                $field_values['organization'] = get_field('organization', $post_id);
                $field_values['contact_title'] = get_field('contact_title', $post_id);
                $field_values['contact_name'] = get_field('contact_name', $post_id);
                $field_values['contact_email'] = get_field('contact_email', $post_id);
                $field_values['phone'] = get_field('phone', $post_id);
                $field_values['pickup_codes'] = get_field('pickup_codes', $post_id);
                
            }
        }

        ob_start();
        acf_form(array(
            'post_id' => $post_id, // Pass the post ID to the form
            'post_title' => true,
            'post_type' => 'trans_dept',
            'new_post' => array(
                'post_type' => 'trans_dept',
                'post_status' => 'publish'
            ),
            'fields' => ['organization','contact_title', 'contact_name', 'contact_email', 'phone','pickup_codes'],
            'submit_value' => 'Update',
            'field_values' => $field_values // Pass the field values to the form
        ));

        return ob_get_clean();
    } else {
        return get_alert([ 'description' => 'Error: No Organization assigned to user.' ]);
    }
}

add_shortcode( 'show_acf_transdept_form', __NAMESPACE__ . '\\show_acf_transdept_form' );


// ==============

// function validate_and_save_zipcode( $valid, $value, $field, $input ){

//     // bail early if value is already invalid
//     if( !$valid ) {
//         return $valid;
//     }

//     // load data
//     $repeater_field = isset($_POST['acf']['field_64241976176e9']) ? $_POST['acf']['field_64241976176e9'] : ''; // repeater parent key
//     $invalid_zips = array(); // initialize array to hold invalid zip codes
//     $valid_zips = array(); // initialize array to hold valid zip codes
//     foreach ($repeater_field as $row) {
//         $zip_code_fields = $row['field_642419a0176ea']; // specific field

//         // check if the zipcode is already assigned to a term in the pickup_code taxonomy
//         $args = array(
//             'post_type' => 'trans_dept',
//             'posts_per_page' => -1,
//             'tax_query' => array(
//                 array(
//                     'taxonomy' => 'pickup_code',
//                     'field' => 'name',
//                     'terms' => $zip_code_fields,
//                 ),
//             ),
//         );
//         $existing_posts = get_posts($args);
//         if (!empty($existing_posts)) {
//             $invalid_zips[] = $zip_code_fields;
//         } else {
//             $valid_zips[] = $zip_code_fields;
//         }
//     }

//     // If there are invalid zip codes, construct the validation message
//     if (!empty($invalid_zips)) {
//         $invalid_zip_message = 'This Zipcode ' . implode(',', $invalid_zips) . ' is already assigned to other Transportation Department.';
//         $valid = $invalid_zip_message;
//     } else {
//         $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : '';
//         wp_set_post_terms($post_id, $valid_zips, 'pickup_code', true);

//         // save the validated zip code fields to the repeater
//         $repeater_key = 'field_64241976176e9';
//         $new_rows = array();
//         foreach ($repeater_field as $row) {
//             $zip_code_fields = $row['field_642419a0176ea']; // specific field
//             if (in_array($zip_code_fields, $valid_zips)) {
//                 $new_rows[] = $row; // add row to new array if zip code is valid
//             }
//         }
//         $_POST['acf'][$repeater_key] = $new_rows;
//     }

//     // return
//     return $valid;
// }

// add_filter('acf/validate_value/key=field_64241976176e9', __NAMESPACE__ . '\\validate_and_save_zipcode', 10, 4);

function validate_and_save_zipcode( $valid, $value, $field, $input ){

    // bail early if value is already invalid
    if( !$valid ) {
        return $valid;
    }

    // load data
    $repeater_field = isset($_POST['acf']['field_64241976176e9']) ? $_POST['acf']['field_64241976176e9'] : ''; // repeater parent key
    $invalid_zips = array(); // initialize array to hold invalid zip codes
    $valid_zips = array(); // initialize array to hold valid zip codes
    foreach ($repeater_field as $row) {
        $zip_code_fields = $row['field_642419a0176ea']; // specific field

        // check if the zipcode is already assigned to a term in the pickup_code taxonomy
        $args = array(
            'post_type' => 'trans_dept',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'pickup_code',
                    'field' => 'name',
                    'terms' => $zip_code_fields,
                ),
            ),
        );
        $existing_posts = get_posts($args);
        if (!empty($existing_posts)) {
            $invalid_zips[] = $zip_code_fields;
        } else {
            $valid_zips[] = $zip_code_fields;
        }
    }

    // If there are invalid zip codes, construct the validation message
    if (!empty($invalid_zips)) {
        $invalid_zip_message = 'This Zipcode ' . implode(',', $invalid_zips) . ' is already assigned to other Transportation Department.';
        $valid = $invalid_zip_message;
    }
        error_log(print_r($valid, true));
    // save the validated zip code fields to the repeater
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : '';
    wp_set_post_terms($post_id, $valid_zips, 'pickup_code', true);

    $repeater_key = 'field_64241976176e9';
    $new_rows = array();
    foreach ($repeater_field as $row) {
        $zip_code_fields = $row['field_642419a0176ea']; // specific field
        if (in_array($zip_code_fields, $valid_zips)) {
            $new_rows[] = $row; // add row to new array if zip code is valid
        }
    }
    $_POST['acf'][$repeater_key] = $new_rows;

    // return
    return $valid;
}

add_filter('acf/validate_value/key=field_64241976176e9', __NAMESPACE__ . '\\validate_and_save_zipcode', 10, 4);






// ========================



// auto populate the organization fields from the organization postype

function auto_populate_select_organization( $value, $post_id, $field ) {
    // Get the current user ID
    $user_id = get_current_user_id();
    
    // Get all posts of the 'organization' post type for the current user
    $args = array(
        'post_type' => 'organization',
        'author' => $user_id,
        'posts_per_page' => -1
    );
    $posts = get_posts( $args );
    
    // Create an array of post IDs
    $post_ids = array();
    foreach ( $posts as $post ) {
        $post_ids[] = $post->ID;
    }
    
    // If the value is empty and the current user has organizations, set the value to the first organization ID
    if ( empty( $value ) && ! empty( $post_ids ) ) {
        $value = $post_ids[0];
    }
    
    // Return the value
    return $value;
}
add_filter( 'acf/load_value/name=organization', __NAMESPACE__ . '\\auto_populate_select_organization', 10, 3 );




/**
 * Checks the post content for `[show_acf_form]`. If found, loads `acf_form_head()`.
 *
 * In addition, we also:
 * - Add .acf-form-front to <body>
 * - Remove the fields we don't want to display to our end users
 */
function enqueue_acf_form_head(){
    if( is_admin() )
      return;

  global $post;
  if(
    has_shortcode( $post->post_content, 'show_acf_organization_form' )
    || has_shortcode( $post->post_content, 'show_acf_transdept_form' )
  ){
    acf_form_head();
    add_filter( 'body_class', function( $classes ){
      $classes[] = 'acf-form-front';
      return $classes;
    });

    $hidden_fields = [
      'priority_pickup' => 'field_6255be63b3b7a',
      'donation_routing' => 'field_6255beb5b3b7b',
      'realtor_ad_standard_banner' => 'field_62615ff13f2b5',
      'realtor_ad_medium_banner' => 'field_6261605b3f2b6',
      'realtor_ad_link' => 'field_626160923f2b7',
      'realtor_description' => 'field_626160ae3f2b8',
    ];
    foreach ($hidden_fields as $array_key => $acf_key ) {
      add_filter('acf/prepare_field/key=' . $acf_key, function( $acf_key ){
        if( ! is_admin() )
          return false;

        return $acf_key;
      });
    }
  }

}
add_action( 'wp', __NAMESPACE__ . '\\enqueue_acf_form_head', 15 );