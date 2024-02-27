<?php

namespace DonationManager\shortcodes;
use function DonationManager\utilities\{get_alert};
use function DonationManager\organizations\{get_org_transdepts};

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
        'updated_message' => get_alert( ['description' => 'Your Organization information has been saved.', 'type' => 'success'] ),
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
       // error_log( print_r( $organization_id, true ));

    if( $organization_id ){
      $trans_depts = get_org_transdepts( $organization_id , true);
          //error_log( print_r( get_org_transdepts($organization_id), true ));

      $post_id = 'new_post';
      $field_values = array();

      // Check if the form has been submitted
      if( isset( $_POST['acf'] ) && wp_verify_nonce( $_POST['acf']['_acf_nonce'], 'new_post' ) ) {
        // If no Transportation Department exists for the current user,
        // save our form data as a new Transportation Department.

        // Save the form data
        $post_id = acf_form_save_post( 'new_post' );

        // Automatically assign the organization of the user to the organization field
        if( $post_id ) {
          update_field( 'organization', $organization_id, $post_id );
        }
      } else {
          // Retrieve the saved field values if the form hasn't been submitted
          if( 1 === count( $trans_depts ) || isset($_GET['did'])) {

            	$trans_dept_id = $trans_depts[0]->ID;
			  if(isset($_GET['did']) && in_array($_GET['did'], wp_list_pluck($trans_depts, 'ID'))){
				  $trans_dept_id = $_GET['did'];
			  }

            if( $trans_dept_id ):
              $field_values['organization'] = get_field( 'organization', $trans_dept_id );
              $field_values['contact_title'] = get_field( 'contact_title', $trans_dept_id );
              $field_values['contact_name'] = get_field( 'contact_name', $trans_dept_id );
              $field_values['contact_email'] = get_field( 'contact_email', $trans_dept_id );
              $field_values['phone'] = get_field( 'phone', $trans_dept_id );

              $pickup_codes = wp_get_post_terms( $trans_dept_id, 'pickup_code', [ 'fields' => 'names' ] );
              uber_log('🔔 $pickup_codes = ' . print_r( $pickup_codes, true ) );

              $field_values['pickup_codes'] = $pickup_codes;
            endif;
            ob_start();
            acf_form( array(
              'post_id' => $trans_dept_id, // Pass the post ID to the form
              'post_title' => true,
              'post_type' => 'trans_dept',
              'new_post' => array(
                  'post_type' => 'trans_dept',
                  'post_status' => 'publish'
              ),
              'fields' => [ 'contact_title', 'contact_name', 'contact_email', 'phone' ],
              'submit_value' => 'Update',
              'field_values' => $field_values,
              'updated_message' => get_alert([ 'description' => 'Your Transportation Department details have been updated.', 'type' => 'success' ]),
            ) );
            return ob_get_clean();

          } else if( 1 < count( $trans_depts ) ) {
			  ob_start();
				  echo "<h3>Your Transportation Departments</h3>
				  <p>Click transportation department name to edit</p>";
			  foreach ($trans_depts as $trans_dept) {
				  ?>
					<ul>
						<li><a href="?did=<?php echo( $trans_dept->ID ); ?>"><?php echo esc_html($trans_dept->post_title) ?></a></li>
					</ul>
				  <?php
			  }
			  return ob_get_clean();
          }
      }

    } else {
        return get_alert([ 'description' => 'Error: No Organization assigned to user.' ]);
    }
}

add_shortcode( 'show_acf_transdept_form', __NAMESPACE__ . '\\show_acf_transdept_form' );

function validate_and_save_zipcode( $valid, $value, $field, $input ){

    // bail early if value is already invalid
    if( !$valid ) {
        return $valid;
    }

    // load data
    $repeater_field = isset( $_POST['acf']['field_64241976176e9'] ) ? $_POST['acf']['field_64241976176e9'] : ''; // repeater parent key
    $invalid_zips = []; // initialize array to hold invalid zip codes
    $valid_zips = []; // initialize array to hold valid zip codes
    foreach ( $repeater_field as $row ) {
        $zip_code = $row['field_642419a0176ea']; // specific field
        uber_log( $zip_code, '$zip_code' );

        // check if the zipcode is already assigned to a term in the pickup_code taxonomy
        $args = [
          'post_type' => 'trans_dept',
          'posts_per_page' => -1,
          'exclude' => null,
          'tax_query' => [
            [
              'taxonomy' => 'pickup_code',
              'field' => 'name',
              'terms' => $zip_code,
            ],
          ],
        ];
        $existing_transdepts = get_posts( $args );
        if ( ! empty( $existing_transdepts ) ) {
            $invalid_zips[] = $zip_code;
        } else {
            $valid_zips[] = $zip_code;
        }
    }

    // If there are invalid zip codes, construct the validation message
    if ( ! empty( $invalid_zips ) ) {
        $invalid_zip_message = 'This Zipcode ' . implode( ',', $invalid_zips ) . ' is already assigned to other Transportation Department.';
        $valid = $invalid_zip_message;
    }
    uber_log( $valid, '$valid' );

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

  if(!empty($post) && (
    has_shortcode( $post->post_content, 'show_acf_organization_form' )
    || has_shortcode( $post->post_content, 'show_acf_transdept_form' ))
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




