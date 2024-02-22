<?php
namespace DonationManager\users;
use function DonationManager\utilities\{get_alert};
use function DonationManager\organizations\{get_org_transdepts, is_useredited, set_useredited};

function current_user_info_shortcode() {
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        ob_start();
        ?>
                <div class = "avatar"><?php echo get_avatar( $current_user->user_email ); ?>
                    <span><?=esc_html($current_user->user_email) ?> </span>
                    <ul class="nav-user">
                        <li><a href = "<?=home_url('/dashboard/profile') ?>">Profile</a></li>
                        <li><a href = "<?=wp_logout_url( home_url('/user-account/') ) ?>">Logout</a></li>
                    </ul>
                </div>
        <?php
        return ob_get_clean();
    } else {
        return '<span class="b-userportal-not-loggedin-info">Please <a href="/wp-login.php">log in</a> to view your user information.</span>';
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

/**
 * Send custom reset password email message to Department Account owner
 * @param $message
 * @param $key
 * @param $user_login
 * @param $user
 * @return mixed|string
 */
function pmd_user_password_reset_email_message($message, $key, $user_login, $user)
{
	if ($user->has_cap('org')) {
//		$reset_link = network_site_url('dashboard/login/?action=resetpass&key=' . $key . '&login=' . rawurlencode($user_login), 'login');
		$reset_link = network_site_url('wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode($user_login), 'login');

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

/**
 * Display message on the user dashboard
 * @return false|string
 */
function show_account_owner_stats()
{
	ob_start();
	$current_user = wp_get_current_user();
	$organization_id = get_user_meta( $current_user->ID, 'organization', true );
	$organization = get_post( $organization_id );

	?>
    <div hx-get="/wp-htmx/v1/userportal/dashboard" hx-trigger="load" hx-swap="outerHTML">
        Options
    </div>
	<?php


	return ob_get_clean();
}
add_shortcode( 'pumd_stats', __NAMESPACE__ . '\\show_account_owner_stats' );

/** Get default options for the organization
 * @param $field_name | string | the field name | 'pickup_location' | 'screening_question' | 'pickup_time'| 'donation_option'
 * @return array
 */
function get_organization_default_options($field_name) {
	$default_terms = get_field( "default_options_default_${field_name}s", 'option' );

	return $default_terms;
}


/** Get the organization options
 * @param $organization_id
 * @param $field_name 	| string | the field name | 'pickup_location' | 'screening_question' | 'pickup_time'| 'donation_option'
 * @return array|\WP_Error
 */
function get_ogranization_options($organization_id, $field_name) {

	$terms = wp_get_post_terms( $organization_id, $field_name );

	return $terms;
}

/**
 * @param $organization_id
 * @param $field_name | string | the field name | 'pickup_location' | 'screening_question' | 'pickup_time'| 'donation_option'
 * @return array
 */
function get_organization_additional_options($organization_id, $field_name) {
	$default_terms = get_organization_default_options($field_name);
	$terms = get_ogranization_options($organization_id, $field_name);

	$form_terms = [];
    if(!empty($default_terms)){
        $defaults_checked = false;
        if(count($terms) === 0 && !is_useredited($organization_id)){
            $defaults_checked = true;
        }
        foreach ($default_terms as $term) {
            $form_terms[$term->term_id] = [
                'term' => $term,
                'checked' => $defaults_checked
            ];
        }
    }

	foreach ($terms as $term) {
		$form_terms[$term->term_id] = [
			'term' => $term,
			'checked' => true
		];
	}
	return $form_terms;
}


/** Retrieve the form data for the additional options form
 * @return array
 */
function get_additional_options_form_data($user_id = null){
	$form_data = [];

    if($user_id === null) {
        $current_user = wp_get_current_user();
        $organization_id = get_user_meta($current_user->ID, 'organization', true);
        $organization = get_post($organization_id);
    }

    if($organization){
        $form_data['elements'] = [
            [
                'type' => 'checkbox',
                'name' => 'userportal_pickup_location',
                'label' => 'Pickup Location',
                'description' => 'Select the pickup location',
                'options' => get_organization_additional_options($organization->ID, 'pickup_location')
            ],
            [
                'type' => 'checkbox',
                'name' => 'userportal_screening_question',
                'label' => 'Screening Question',
                'description' => 'Select the screening question',
                'options' => get_organization_additional_options($organization->ID, 'screening_question')
            ],
            [
                'type' => 'checkbox',
                'name' => 'userportal_pickup_time',
                'label' => 'Pickup Time',
                'description' => 'Select the pickup time',
                'options' => get_organization_additional_options($organization->ID, 'pickup_time')
            ],
            [
                'type' => 'checkbox',
                'name' => 'userportal_donation_option',
                'label' => 'Donation Option',
                'description' => 'Select the donation option',
                'options' => get_organization_additional_options($organization->ID, 'donation_option')
            ]
        ];
    }


	return $form_data;

}

/**
 * Get list of ID's for default organization options and currently set organization specific options
 * @param $field_name
 * @return array
 */
function get_valid_organization_terms_ids($organization_id,$field_name) {
	$default_terms = get_field( "default_options_default_${field_name}s", 'option' );
	$user_terms = get_ogranization_options($organization_id, $field_name);
	$ids = wp_list_pluck($default_terms, 'term_id');
	$uids = wp_list_pluck($user_terms, 'term_id');
	$ids = array_merge($ids, $uids);
	return $ids;
}


/** Save user additional options
 * @param null $organization_id || int | the organization id
 * @return void
 */
function save_user_additional_options($organization_id = null) {
    $saved = FALSE;

    if($organization_id === null) {
        $current_user = wp_get_current_user();
        $organization_id = get_user_meta( $current_user->ID, 'organization', true );
    }

	$field_names = ['pickup_location', 'screening_question', 'pickup_time', 'donation_option'];

    $organization = get_post($organization_id);

    if($organization) {
        foreach ($field_names as $field_name) {
            if (isset($_POST['userportal_' . $field_name])) {
                $valid_ids = get_valid_organization_terms_ids($organization_id, $field_name);
                $options = $_POST['userportal_' . $field_name];
                $options = array_map('intval', $options);
                $options = array_filter($options, function ($option) use ($valid_ids) {
                    return in_array($option, $valid_ids);
                });

                $result = wp_set_object_terms($organization_id, $options, $field_name);
            } else {//user cleared all options for this field - remove all terms
                $result = wp_set_object_terms($organization_id, [], $field_name);
            }

            if (!is_wp_error($result)) {
                $saved = TRUE;
            }
        }
    }

    if($saved) {
        //mark organization options as user edited
        set_useredited($organization_id);
        set_userportal_notification('Organization options saved successfully', 'success', 'Options Saved');
    }

    return $saved;
}

/**
 * Set header content with userportal notification
 * @param $message | string | the message of the notification
 * @param null $type | string | the type of the notification | 'success' | 'error' | 'warning' | 'info'
 * @param null $title | string | the title of the notification
 * @return void
 */
function set_userportal_notification($message,$type=null ,$title = null) {
    $notification_data = [
        'message' => '',
        'type' => '',
        'title' => ''
    ];
    if($message !== null){
        $notification_data['message'] = $message;
    }
    if($type !== null){
        $notification_data['type'] = $type;
    }
    if($title !== null){
        $notification_data['title'] = $title;
    }

    if(!empty($notification_data['message'])){
        header( 'HX-Trigger-After-Settle: '.json_encode(['showUserportalNotification'=>$notification_data]));
    }
}

/**
 * Do not show the field in the dashboard for organization users
 * @param $field
 * @return false|mixed
 */
function exclude_form_field_from_dashboard( $field ) {
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        if($current_user->has_cap('org')){
            return false;
        }
    }
    return $field;
}

// Apply to fields named "example_field".
add_filter('acf/prepare_field/name=step_one_notice',  __NAMESPACE__ . '\\exclude_form_field_from_dashboard');
