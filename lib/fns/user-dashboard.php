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
                 echo '<div class = "avatar">'.get_avatar( $current_user->user_email ).'<span>'.esc_html($current_user->user_email).' </span>';
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
        return 'Please <a href="/wp-login.php">log in</a> to view your user information.';
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

add_filter( 'show_admin_bar' ,  __NAMESPACE__ . '\\disable_admin_bar_for_orgs');
function disable_admin_bar_for_orgs($show_admin_bar) {
	return ( current_user_can( 'administrator' ) ) ? $show_admin_bar : false;
}

function resetpassform($rp_key,$user_login,$errors=[]){
	$rp_url = add_query_arg('action', 'setpass');
	$rp_ulogin = esc_attr($user_login);
	$rp_ukey = esc_attr($rp_key);
	if($errors){
		$rform = '<div class="alert alert-danger" role="alert">';
		foreach($errors as $error){
			$rform .= '<p>'.$error.'</p>';
		}
		$rform .= '</div>';
	}
	$rform .= <<<EOD

<form name="resetpassform" id="resetpassform" action="$rp_url" method="post" autocomplete="off">
						<input type="hidden" id="user_login" name="rp_login" value="$rp_ulogin" autocomplete="off" />
						<input type="hidden" name="rp_key" value="$rp_ukey" />

						<p>
							<label for="pass1">New password</label>
							<input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
						</p>
						<p>
							<label for="pass2">Confirm new password</label>
							<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" />
						</p>

						<p class="description">Hint: The password should be at least twelve characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ & ).</p>

						<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Reset Password" /></p>
					</form>
EOD;

	return $rform;
}

add_shortcode( 'pmd_loginform', function (){

	$passform_error ="";
	if ( is_user_logged_in() ) {
		echo 'You are already logged in.';
	} else {
		$action = isset($_GET['action']) ? $_GET['action'] : '';

		if ('POST' == $_SERVER['REQUEST_METHOD'] && $action === 'setpass') {
			$rp_key = $_POST['rp_key'];
			$rp_login = $_POST['rp_login'];

			$user = check_password_reset_key($rp_key, $rp_login);

			if (!is_wp_error($user)) {
				// Check for a valid password
				if (isset($_POST['pass1']) && !empty($_POST['pass1']) && $_POST['pass1'] == $_POST['pass2']) {
					// Reset the user's password
					reset_password($user, $_POST['pass1']);
					echo "Password changed successfully. You can now <a href='/dashboard/login/'>login</a>.";
				} else {
					$passform_error = 'Passwords do not match or are empty.';
					echo resetpassform($rp_key, $rp_login, $passform_error);
				}
			} else {
				// Invalid key or user
				$passform_error = 'Invalid key or user.';
				echo resetpassform($rp_key, $rp_login, $passform_error);
			}
		}

		if ($action === 'resetpass') {
			// Display the password reset form
			if (isset($_GET['key']) && isset($_GET['login'])) {
				// Verify the key and login are valid
				$user = check_password_reset_key($_GET['key'], $_GET['login']);
				if (is_wp_error($user)) {
					echo 'Invalid key or the reset time has expired. Please try resetting your password again if needed.';
				} else {
					echo resetpassform($_GET['key'], $user->user_login);
				}
			} else {
				echo 'Invalid request.';
			}
		} else if ($action === 'forgot') {
			if ( 'POST' == $_SERVER['REQUEST_METHOD']  ) {
				$errors = retrieve_password();
				if ( ! is_wp_error( $errors ) ) {
					echo 'Check your email for a link to reset your password.';
				}
			}

			if ( isset( $_GET['error'] ) ) {
				if ( 'invalidkey' === $_GET['error'] ) {
					$errors->add( 'invalidkey', __( '<strong>Error:</strong> Your password reset link appears to be invalid. Please request a new link below.' ) );
				} elseif ( 'expiredkey' === $_GET['error'] ) {
					$errors->add( 'expiredkey', __( '<strong>Error:</strong> Your password reset link has expired. Please request a new link below.' ) );
				}
			}

			$user_login = '';

			if ( isset( $_POST['user_login'] ) && is_string( $_POST['user_login'] ) ) {
				$user_login = wp_unslash( $_POST['user_login'] );
			}

			?>
			<form name="lostpasswordform" id="lostpasswordform" action="<?php echo esc_url( network_site_url( 'dashboard/login/?action=forgot', 'login_post' ) ); ?>" method="post">
				<p>
					<label for="user_login"><?php _e( 'Username or Email Address' ); ?></label>
					<input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr( $user_login ); ?>" size="20" autocapitalize="off" autocomplete="username" required="required" />
				</p>
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
				<p class="submit">
					<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Get New Password' ); ?>" />
				</p>
			</form>
<?php

		} else {
			$args = array(
				'echo' => false,
				'redirect_to' => site_url('/dashboard/organization/'),
				'remember' => true,
				'value_remember' => true,
			);
			$loginform = wp_login_form($args);

			//custom wp login url with parameters
			$wp_login_url = add_query_arg('action', 'forgot');
			$reset_link = '<a href="' . esc_url($wp_login_url) . '">Lost your password?</a>';
			$register_link = '<a href="' . site_url('dashboard/register/') . '">Register</a>';

			$login_options = '<div class="login-options">' . $reset_link . ' | ' . $register_link . '</div>';
			return $loginform . $login_options;
		}
	}


} );


function pmd_user_password_reset_email_message($message, $key, $user_login, $user)
{
	if ($user->has_cap('org')) {
		$reset_link = network_site_url('dashboard/login/?action=resetpass&key=' . $key . '&login=' . rawurlencode($user_login), 'login');

		$site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$message = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";
		$message .= sprintf(__('Site Name: %s'), $site_name) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
		$message .= __('If this was a mistake, ignore this email and nothing will happen.') . "\r\n\r\n";
		$message .= $reset_link . "\r\n\r\n";
		return $message;
	}

	return $message;
}

add_filter('retrieve_password_message', __NAMESPACE__ . '\\pmd_user_password_reset_email_message', 10, 4);

