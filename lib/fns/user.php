<?php

namespace DonationManager\users;

/**
 * Registers a new user.
 *
 * @param      object  $record   The form submission object
 * @param      object  $handler  The form handler
 *
 * @return     bool    Return TRUE when a user is created.
 */
function register_user( $record, $handler ){
  // Only process the form named `wordpress_and_campaign_registration`:
  $form_name = $record->get_form_settings( 'form_name' );
  if( 'user_registration' != $form_name )
    return;

  // Get our form field values
  $raw_fields = $record->get( 'fields' );
  $fields = [];
  foreach( $raw_fields as $id => $field ){
    switch( $id ){
      default:
        $fields[$id] = $field['value'];
    }

  }

  // Add the user to WordPress
  if( ! email_exists( $fields['email'] ) && ! username_exists( $fields['email'] ) ){
    $user_id = wp_insert_user([
      'user_pass' => wp_generate_password( 8, false ),
      'user_login' => $fields['email'],
      'user_email' => $fields['email'],
      'display_name' => $fields['firstname'],
      'first_name' => $fields['firstname'],
      'last_name' => $fields['lastname'],
    ]);
    add_user_meta( $user_id, 'organization', $fields['organization'], true );
    //\NCCAgent\userprofiles\create_user_message( $user_id );
    return true;
  } else {
    //ncc_error_log('🔔 A user with the email `' . $fields['email'] . '` or NPN `' . $fields['npn'] . '` already exists!' );
    return false;
  }
}
add_action( 'elementor_pro/forms/new_record', __NAMESPACE__ . '\\register_user', 10, 2 );