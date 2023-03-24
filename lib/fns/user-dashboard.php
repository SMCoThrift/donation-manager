<?php 
namespace DonationManager\users;
use function DonationManager\utilities\{get_alert};

function current_user_info_shortcode() {
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();

         $menu_name = 'dashboard-menu';
                $menu = wp_get_nav_menu_object( $menu_name );
                if ( $menu ) {
                    // Menu exists, get the menu items
                    $menu_items = wp_get_nav_menu_items( $menu->term_id );
                    if ( $menu_items ) {
                        // Loop through the menu items and output them
                 echo '<div class = "avatar">'.get_avatar( $current_user->user_email ).'<span>'.$current_user->first_name.' '.$current_user->last_name .' </span>';
                    echo '<ul class = "nav-user">';
                        foreach ( $menu_items as $menu_item ) {
                            // Output the menu item
                            echo '<li><a href = "' . $menu_item->url . '">' .$menu_item->title. '</a></li>';
                        }
                    }
                }
                        echo '<li><a href = "'.wp_logout_url( home_url('/user-account/') ).'">Logout</a></li>';
                     echo   '</ul>';
                  echo '</div>';

    } else {
        return 'Please log in to view your user information.';
    }
}
add_shortcode( 'current_user_info', __NAMESPACE__ . '\\current_user_info_shortcode' );

function my_organization_form_shortcode() {
  $current_user = wp_get_current_user();
  $organization_id = get_user_meta( $current_user->ID, 'organization', true );
  if ( $organization_id ){
    ob_start();
    acf_form_head();
    acf_form(
      [
        'post_id' => $organization_id,
        'post_title' => false,
        'fields' => ['monthly_report_emails', 'website', 'pickup_settings_skip_pickup_dates', 'pickup_settings_pickup_dates', 'pickup_settings_minimum_scheduling_interval', 'pickup_settings_step_one_notice', 'pickup_settings_provide_additional_details', 'pickup_settings_allow_user_photo_uploads', 'pickup_settings_pause_pickups' ],
        'submit_value' => 'Save',
        'updated_message' => 'Your information has been saved.',
            // we can add custom url after saving
        //'return' => add_query_arg( '', '', get_permalink( $organization_id ) )
      ]
    );
    return ob_get_clean();
  } else {
    return get_alert([ 'description' => 'Error: No Organization assigned to user.' ]);
  }
}
// shortcode usage [my_organization_form]
add_shortcode( 'my_organization_form', __NAMESPACE__ . '\\my_organization_form_shortcode' );



// CHANGE THE STATUS OF THE USER ORGANIZATION TO PUBLISH AFTER HE SAVED
function my_publish_organization( $post_id ) {
    // Check if this is an Organization post type
    if ( get_post_type( $post_id ) !== 'organization' ) {
        return;
    }

    // Check if the post is already published
    if ( get_post_status( $post_id ) === 'publish' ) {
        return;
    }

    // Set the post status to "publish"
    $post = array(
        'ID' => $post_id,
        'post_status' => 'publish',
    );
    wp_update_post( $post );
}
add_action( 'acf/save_post', __NAMESPACE__ . '\\my_publish_organization', 20 );







