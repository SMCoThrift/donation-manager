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

      // Create organization post
    $org_data = array(
      'post_title' => $fields['organization'],
      'post_type' => 'organization',
      'post_status' => 'draft'
    );
    $organization_id = wp_insert_post( $org_data );


    $user_id = wp_insert_user([
      'user_pass' => wp_generate_password( 8, false ),
      'user_login' => $fields['email'],
      'user_email' => $fields['email'],
      'display_name' => $fields['firstname'],
      'first_name' => $fields['firstname'],
      'last_name' => $fields['lastname'],
      'role' => 'pending' // Set user role to "pending"
    ]);

    // Set the user's status to "pending"
    $user = new WP_User( $user_id );
    $user->set_role( 'pending' );
    
     // Add user meta data
    add_user_meta( $user_id, 'organization', $organization_id, true );
    add_user_meta( $user_id, 'phone', $fields['phone'], true );
    
    // Add organization meta data
    add_post_meta( $organization_id, 'organization', $fields['organization'], true );

    //\NCCAgent\userprofiles\create_user_message( $user_id );
    //
        $to = get_option('admin_email');
        $subject = 'New Organization Registration';
        $message = 'A new organization has been registered and is waiting for approval. Please log in to the website to review and approve the organization.';
    return true;
  } else {
    //ncc_error_log('🔔 A user with the email `' . $fields['email'] . '` or NPN `' . $fields['npn'] . '` already exists!' );
    return false;
  }
}
add_action( 'elementor_pro/forms/new_record', __NAMESPACE__ . '\\register_user', 10, 2 );

//STATUS ADMIN ROLE USERS DISPLAY COLUMN
function custom_user_columns( $columns ) {
   $columns['user_status'] = 'Status';
   return $columns;
}

add_filter( 'manage_users_columns', __NAMESPACE__ . '\\custom_user_columns' );

//STATUS ADMIN ROLE USERS

function custom_user_column_content( $value, $column_name, $user_id ) {
   if ( 'user_status' === $column_name ) {
      $status = get_user_meta( $user_id, 'user_status', true );
      $user = get_userdata( $user_id );
      $user_roles = $user->roles;

    if ( empty( $user_roles ) ) {
         $value = '<span style="color:orange;font-weight:bold;">Pending</span>';
      }elseif($user_roles[0] === 'rejected' ) {
        $value = '<span style="color:red;font-weight:bold;">Rejected</span>';
      }else{
        $value = '<span style="color:green;font-weight:bold;">Approved</span>';
      }
   }
   return $value;
}

add_filter( 'manage_users_custom_column', __NAMESPACE__ . '\\custom_user_column_content', 10, 3 );



//ADD ROLE REJECTED
function add_rejected_role() {
    add_role(
        'rejected',
        __( 'Rejected', 'pickupmydonation' ),
        array(
            'read'         => true,
            'edit_posts'   => false,
            'delete_posts' => false,
        )
    );
}
add_action( 'init', __NAMESPACE__ . '\\add_rejected_role' );


//IF CHANGE THE ROLE OF THE USERS OR TO REVIEW THE APPLICATION THIS IS NOT YET DONE
function send_user_role_email( $user_id, $old_role, $new_role ) {
    $user = get_userdata( $user_id );
    $email = $user->user_email;
    $subject = 'Your role has been changed';
    $message = 'Your role has been changed to ' . $new_role . '.';

    if ( $new_role == 'subscriber' ) {
        // Send email to user with password reset link
        $key = get_password_reset_key( $user );
        $reset_link = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );
        $message .= ' Please use this link to set your password: ' . $reset_link;
        
        // Set the user's custom post type status to "publish"
        update_user_meta( $user_id, 'organization_status', 'publish' );
    } elseif ( $new_role == 'rejected' ) {
        // Send email to user with rejection message
        $message .= ' Unfortunately, your application has been rejected.';
    }

    wp_mail( $email, $subject, $message );
}
add_action( 'set_user_role', __NAMESPACE__ . '\\send_user_role_email', 10, 4 );










