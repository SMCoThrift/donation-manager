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







