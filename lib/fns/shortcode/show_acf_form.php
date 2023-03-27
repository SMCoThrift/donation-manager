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