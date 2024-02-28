<?php

namespace DonationManager\users;
use function DonationManager\templates\{render_template};

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

/**
 * Assign a Transportation Department to a user and send them a notification
 *
 * @param      int     $user_id    The User ID
 * @param      string  $role       The role
 * @param      array   $old_roles  The old roles
 */
function change_department_user_role( $user_id, $role, $old_roles ) {

  $user = get_userdata( $user_id );

  $new_role = ! empty( $role );

  // Retrieve the department ID from the user's metadata
  if ( ! empty( $role ) && $role == 'org' && in_array( 'org-inactive', $old_roles ) ) {
    $department_ids = get_user_meta( $user_id, 'department' );

    // Retrieve the organization post object
  	foreach ($department_ids as $department_id) {
      $department = get_post( $department_id );
  		if($department !== NULL){
  			wp_update_post( array(
  				'ID' => $department_id,
  				'post_author' => $user_id
  			) );
  		}
    }

    // Notify the user via email:
    $hbs_vars = [ 'year' => date('Y'), 'header_logo' => DONMAN_PLUGIN_URL . 'lib/images/pickupmydonation-logo-inverted_1200x144.png' ];
    $to = $user->user_email;
    $subject = 'Your Account Has Been Approved';

	  $key = get_password_reset_key( $user );
    $login = $user->user_email;
	  $url = network_site_url( "wp-login.php?action=rp&key=${key}&login=" . rawurlencode( $user->user_login ), 'login' );

    $site_title = get_bloginfo( 'title' );
    $hbs_vars['email_content'] = "Hi " . $user->display_name . ",<br><br>Your User Portal account has been created at ${site_title}. You may now edit various details associated with your account.<br><br><a href=\"${url}\">Click here</a> to generate your password so you can login.<br><br>Best Regards,<br>The ${site_title} Team";

    $headers = array(
      'Content-Type: text/html; charset=UTF-8',
      'From: ' . get_bloginfo( 'name' ) . ' <' . get_bloginfo( 'admin_email' ) . '>',
    );
    // Send the email
    $html = render_template( 'email.user-portal-notification', $hbs_vars );
    wp_mail( $to, $subject, $html, $headers );
  }
}

add_action( 'set_user_role', __NAMESPACE__ . '\\change_department_user_role', 10, 3 );

/**
 * Create a WordPress User for a Transportation Department and set the user's `department` and `organization` meta fields.
 *
 * @param      int  $trans_dept_id  The Transportation Department ID.
 *
 * @return     mixed  The User ID upon success or FALSE;
 */
function dept_to_user_account( $trans_dept_id ) {
	$user_id = false;
	$trans_dept = get_post( $trans_dept_id );
	$trans_dept_name = $trans_dept->post_title;
	$trans_dept_slug = $trans_dept->post_name;
	$trans_dept_email = trim( explode( ',', get_field( 'contact_email',  $trans_dept_id ) ) [0] );
	$org_id = get_post_meta( $trans_dept_id , 'organization', true);

	if ( ! email_exists( $trans_dept_email ) ) {
    $email_parts = explode( '@', $trans_dept_email );
		$user_data = array(
			'user_login' => $trans_dept_email,
			'user_pass' => wp_generate_password(12),
			'user_email' => $trans_dept_email,
			'display_name' => $email_parts[0],
			'role' => 'org-inactive',
		);

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			error_log( 'Error creating user: ' . $user_id->get_error_message() );
		}else{
			$user = new \WP_User( $user_id );
			add_user_meta( $user_id, 'department',  $trans_dept_id , false );
			add_user_meta( $user_id, 'organization', $org_id, true );
		}
		return $user_id;
	} else {
		return false;
	}

}

/**
 * Modify default WP login page styles
 * @return void
 */
function my_login_stylesheet() {
	wp_enqueue_style( 'custom-login', DONMAN_PLUGIN_URL . 'lib/css/login.css' );
  ?>
  <style>
    #login h1 a, .login h1 a{background-image: url(<?= DONMAN_PLUGIN_URL . 'lib/images/pickupmydonation-logo.800x96.png' ?>);}
  </style>
  <?php
}
add_action( 'login_enqueue_scripts',  __NAMESPACE__ .'\\my_login_stylesheet' );

/**
 * Modify default WP login page logo url
 * @return string
 */
function pumd_logo_url() {
	return home_url();
}
add_filter( 'login_headerurl',  __NAMESPACE__ . '\\pumd_logo_url' );

/**
 * Modify default WP login page logo title
 * @return string
 */
function pumd_logo_url_title() {
	return 'Pickup My Donation - Login Page';
}
add_filter( 'login_headertext', __NAMESPACE__ . '\\pumd_logo_url_title' );


