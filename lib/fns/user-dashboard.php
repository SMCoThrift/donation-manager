<?php 
namespace DonationManager\users;
use function DonationManager\utilities\{get_alert};
use function DonationManager\organizations\{get_org_transdepts};

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
    $post = ['ID' => $post_id, 'post_status' => 'publish',];
    wp_update_post( $post );
}
add_action( 'acf/save_post', __NAMESPACE__ . '\\my_publish_organization', 20 );


function is_pickup_code_available_callback() {
  /**
   * Do this check for each pickup_code:
   *
   * 1. Is the "pickup_code" assigned to *any* trans_dept? If "no", "code is available".
   * 2. If "yes", if the trans_dept the child of a priority org? If "yes", code is available.
   * 3. If "no", code is not available.
   */
  $codes = explode(',', $_POST['codes']);
  $available_codes = array();

  // $organization_id = get_user_meta( $current_user->ID, 'organization', true );
  // $priority_settings_account = get_field('pickup_settings', $organization_id);


  foreach ($codes as $code) {
    $term = get_term_by('name', trim($code), 'pickup_code');
    $assigned_trans_depts = get_posts([
      'post_type' => 'trans_dept',
      'tax_query' => [
        [
          'taxonomy' => 'pickup_code',
          'field' => 'term_id',
          'terms' => $term->term_id
        ]
      ]
    ]);
    if ( empty( $assigned_trans_depts ) ) {
      // Check if the Pickup Code is assigned to any Trans. Dept. If "NO", then it's available.
      $available_codes[] = $code;
    } else {
      foreach( $assigned_trans_depts as $trans_dept ){
        $parent_org = get_field( 'organization', $trans_dept->ID );
      //  $priority = get_field( 'pickup_settings_priority_pickup', $parent_org->ID);
        $priority = get_field( 'pickup_settings_priority_pickup', $parent_org );
      //2. If "yes", if the trans_dept the child of a priority org? If "yes", code is available.
        if( $priority ){
             $available_codes[] = $code;
        }


      }
    }
  }
  if (!empty($available_codes)) {
  
    echo 'The following pickup codes are available: ' . implode(', ', $available_codes);
  } else {
    echo implode(', ', $codes);
  }
  wp_die();
}
add_action('wp_ajax_check_pickup_code', __NAMESPACE__ . '\\is_pickup_code_available_callback');
add_action('wp_ajax_nopriv_check_pickup_code', __NAMESPACE__ . '\\is_pickup_code_available_callback');



function pickup_form_shortcode() {
  ob_start();
?>
  <form id="pickup-form">
    <label for="pickup-code">Pickup Codes</label>
     <p id="indicator"></p>
    <p>Enter your five digit pickup codes (i.e.zip codes) to check their availability within our system or separated with comma example 11154,88785,87835.</p>
<!--     <input type="text" id="pickup-code"  name="pickup-code" /> -->

<input id="pickup-code"  name="pickup-code" class="zipcodes-data"  autocomplete="off" maxlength="5" placeholder="example 30031,30034, 303433">
    <button type="submit" class = "pickupcode-btn">Check Availability</button>
  </form>

      <p><span id="invalid-pickup-count"></p>
   <ul id="pickup-code-result">
  </ul>
<?php
  return ob_get_clean();
}
//usage [pickup_form]
add_shortcode('pickup_form', __NAMESPACE__ . '\\pickup_form_shortcode');

function add_pickup_code() {
    $pickupcode = $_POST['pickupcode'];
    $term_exists = term_exists($pickupcode, 'pickup_code');
  
    $current_user_id = get_current_user_id();
    $organization_id = get_user_meta( $current_user_id, 'organization', true );
     if( $organization_id ){ 

        error_log(print_r('exist choi'));
          $trans_depts = get_org_transdepts( $organization_id );
          $args = [ 'author' => $current_user_id,
                    'post_type' => 'trans_dept',
                    'posts_per_page' => 1 ];
          $posts = get_posts($args);
          $post_terms = wp_get_post_terms($trans_depts[0], 'pickup_code', ['fields' => 'slugs']);
          if (in_array($pickupcode, $post_terms)) {
              echo 'Pickup Code ' . $pickupcode . ' already exists in the current transport department.';
              wp_die();
          }

          // If the term doesn't exist in the 'pickup_code' taxonomy, insert it
          if (!$term_exists) {
              $term = wp_insert_term($pickupcode, 'pickup_code');
              if (is_wp_error($term)) {
                  echo 'Error inserting Pickup Code ' . $pickupcode . ': ' . $term->get_error_message();
                  wp_die();
              }
          }

          // Assign the term to the current post's 'pickup_code' taxonomy
          $term_taxonomy_id = wp_set_object_terms($trans_depts[0], $pickupcode, 'pickup_code', true);
          if (is_wp_error($term_taxonomy_id)) {
            error_log(print_r('wala ma assign'));
              echo 'Error assigning Pickup Code ' . $pickupcode . ' to current transport department: ' . $term_taxonomy_id->get_error_message();
              wp_die();
          }
          echo 'Pickup Code ' . $pickupcode . ' added successfully to current transport department!';
          wp_die();
      }
 }
add_action('wp_ajax_add_pickup_code', __NAMESPACE__ . '\\add_pickup_code');
add_action('wp_ajax_nopriv_add_pickup_code', __NAMESPACE__ . '\\add_pickup_code');


function user_pickup_codes() {
  $output = '';
  $current_user_id = get_current_user_id();
  $organization_id = get_user_meta( $current_user_id, 'organization', true );
  if( $organization_id ){  
    $trans_depts = get_org_transdepts( $organization_id );
    if(!empty($trans_depts)) {
        $terms = wp_get_post_terms($trans_depts[0], 'pickup_code', ['fields' => 'slugs']);
        $output .= '<h3>Your Pickup Codes</h3>';
        $output .= '<p>You are picking up donation for these pickup codes:</p>';
        $output .= '<ul class = "user-pickup-code">';
          foreach ($terms as $term) {
            $output .='<li> <a class="removed-pickupcode" value="'.$term.'" href = "#"> '.$term.' <i class="fa fa-times" aria-hidden="true"></i></a></li>';
          }
       $output .= '</ul>';
    }else {
       $output .= '<p>No pickup codes available.</p>';
    }
  }
  return $output;
}
//[your_pickup_codes]
add_shortcode('your_pickup_codes', __NAMESPACE__ . '\\user_pickup_codes'); 

function remove_pickup_code() {
  $value = $_POST['value'];
  $taxonomy = $_POST['taxonomy'];
  $postType = $_POST['post_type'];
  $term = get_term_by('name', $value, $taxonomy);
  $posts = get_posts(array(
    'post_type' => $postType,
    'tax_query' => array(
      array(
        'taxonomy' => $taxonomy,
        'field' => 'term_id',
        'terms' => $term->term_id,
      ),
    ),
  ));
  foreach ($posts as $post) {
    wp_remove_object_terms($post->ID, $term->term_id, $taxonomy);
  }
  wp_send_json_success('Successfully removed pickup code!');
}
add_action('wp_ajax_remove_pickup_code', __NAMESPACE__ . '\\remove_pickup_code');
add_action('wp_ajax_nopriv_remove_pickup_code', __NAMESPACE__ . '\\remove_pickup_code');
?>