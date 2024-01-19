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
      'role' => '' // Set user role to "pending"
    ]);

    $user = new \WP_User( $user_id );

     // Add user meta data
    add_user_meta( $user_id, 'organization', $organization_id, true );
    add_user_meta( $user_id, 'phone', $fields['phone'], true );


    //\NCCAgent\userprofiles\create_user_message( $user_id );
    return true;

  } else {
    //ncc_error_log('🔔 A user with the email `' . $fields['email'] . '` or NPN `' . $fields['npn'] . '` already exists!' );
    return false;
  }
}
add_action( 'elementor_pro/forms/new_record', __NAMESPACE__ . '\\register_user', 10, 2 );


////STATUS ADMIN ROLE USERS DISPLAY COLUMN
//function custom_user_columns( $columns ) {
//   $columns['user_status'] = 'Status';
//   return $columns;
//}
//
//add_filter( 'manage_users_columns', __NAMESPACE__ . '\\custom_user_columns' );
//
////STATUS ADMIN ROLE USERS
//
//function custom_user_column_content( $value, $column_name, $user_id ) {
//   if ( 'user_status' === $column_name ) {
//      $status = get_user_meta( $user_id, 'user_status', true );
//      $user = get_userdata( $user_id );
//      $user_roles = $user->roles;
//      //need to fix this.
//     //error_log(print_r($user_roles[0]));
//    if ( empty( $user_roles ) ) {
//         $value = '<span style="color:orange;font-weight:bold;">Pending</span>';
//      }elseif($user_roles[0] === 'rejected' ) {
//        $value = '<span style="color:red;font-weight:bold;">Rejected</span>';
//      }else{
//        $value = '<span style="color:green;font-weight:bold;">Approved</span>';
//      }
//   }
//   return $value;
//}
//
//add_filter( 'manage_users_custom_column', __NAMESPACE__ . '\\custom_user_column_content', 10, 3 );



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


function send_role_change_email( $user_id, $old_user_data ) {
    $new_user_data = get_userdata( $user_id );
    // $reset_key = get_password_reset_key( $new_user_data );
    // error_log(print_r($reset_key));

    $new_role = ! empty( $new_user_data->roles ) ? $new_user_data->roles[0] : '';

    if ( in_array( $new_role, array( 'subscriber', 'administrator' ) ) ) {
        // Retrieve the organization ID from the user's metadata
        $organization_id = get_user_meta( $user_id, 'organization', true );

        // Retrieve the organization post object
        $organization = get_post( $organization_id );

        // Assign the user as the author of the organization post
        wp_update_post( array(
            'ID' => $organization_id,
            'post_author' => $user_id
        ) );

        // Send an email to the user to notify them that they have been approved
          // Define the recipient email address
          $to = $new_user_data->user_email;

          // Define the email subject
          $subject = 'Your Account Has Been Approved';


          $key = wp_generate_password( 20, false );
          $login = $new_user_data->user_email;
          $url = site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . urlencode($login).'&wp_lang=en_US' );

          // Define the email message
          $message = 'Hello ' . $new_user_data->display_name . ', your account has been approved. Please click the generated link to set your password : <a href="'.  $url .'">generate your password</a>';

          $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo( 'name' ) . ' <' . get_bloginfo( 'admin_email' ) . '>',
          );
          // Send the email using wp_mail()
          wp_mail( $to, $subject, $message, $headers );
    }
}


add_action( 'set_user_role', __NAMESPACE__ . '\\send_role_change_email', 10, 3 );

function org_to_user_account($id_org){

}

function dept_to_user_account($id_dept)
{
	$user_id = FALSE;
	$dept = get_post($id_dept);
	$dept_name = $dept->post_title;
	$dept_slug = $dept->post_name;
	$dept_email = trim(explode(',', get_field('contact_email', $id_dept))[0]);
	$id_org = get_post_meta($id_dept, 'organization', true);

	if (!email_exists($dept_email)) {
		$user_data = array(
			'user_login' => $dept_email,
			'user_pass' => wp_generate_password(12),
			'user_email' => $dept_email,
			'first_name' => $dept_name,
			'role' => 'org-inactive',
		);

		$user_id = wp_insert_user($user_data);

		if (is_wp_error($user_id)) {
			error_log('Error creating user: ' . $user_id->get_error_message());
		}else{
			$user = new \WP_User( $user_id );
			add_user_meta( $user_id, 'department', $id_dept, false );
			add_user_meta( $user_id, 'organization', $id_org, true );
		}
		return $user_id;
	}

}
