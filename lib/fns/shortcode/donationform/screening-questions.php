<?php

use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html};
//use function DonationManager\utilities\{get_alert};
use function DonationManager\organizations\{get_screening_questions};
use function DonationManager\realtors\{get_realtor_ads};

$screening_questions = get_screening_questions( $_SESSION['donor']['org_id'] );

$questions = [];
foreach( $screening_questions as $question ) {
  $question_id = $question['id'];
  $checked_yes = ( isset( $_POST['donor']['answers'][$question_id] ) &&  'Yes' == $_POST['donor']['answers'][$question_id] )? ' checked="checked"' : '';
  $checked_no = ( isset( $_POST['donor']['answers'][$question_id] ) &&  'No' == $_POST['donor']['answers'][$question_id] )? ' checked="checked"' : '';
  $questions[] = [
    'key' => $question['id'],
    'question' => $question['desc'],
    'question_esc_attr' => esc_attr( $question['desc'] ),
    'checked_yes' => $checked_yes,
    'checked_no' => $checked_no,
  ];
}

/**
 * Setup our Pick Up Settings:
 *
 * @type  bool   $provide_additional_details Does the organization allow donors to provide additional details when answering "Yes" to one of the screening questions?
 * @type  array  $user_photo_uploads {
 *   @type  bool $on        TRUE if User Photo Uploads are allowed.
 *   @type  bool $required  TRUE if User Photo Uploads are required.
 * }
 */
$pickup_settings = get_field( 'pickup_settings', $_SESSION['donor']['org_id'] );

$provide_additional_details = $pickup_settings['provide_additional_details'];
if( DMDEBUG_VERBOSE )
  uber_log( '🔔 $provide_additional_details = ' . $provide_additional_details );
$additional_details = null;
if( $provide_additional_details )
    $additional_details = ( isset( $_POST['donor']['additional_details'] ) )? esc_textarea( $_POST['donor']['additional_details'] ) : '' ;

$user_photo_uploads = [
  'on'        => $pickup_settings['allow_user_photo_uploads'],
  'required'  => $pickup_settings['user_photo_uploads_required'],
];
if( DMDEBUG_VERBOSE )
  uber_log( '🔔 $user_photo_uploads = ' . print_r( $user_photo_uploads, true ) );

$hbs_vars = [
  'questions' => $questions,
  'additional_details' => $additional_details,
  'nextpage' => $nextpage,
  'provide_additional_details' => $provide_additional_details,
  'user_photo_uploads_on' => $user_photo_uploads['on'],
  'user_photo_uploads_required' => $user_photo_uploads['required'],
];

/**
 * 08/11/2022 (09:19) - TODO: Get Cloudinary Photo Uploads working:
 */
// jQuery/Cloudinary Photo Upload
if( $user_photo_uploads['on'] )
{
  uber_log('🔔 allowing user photo uploads...');

  wp_enqueue_script( 'blueimp-jquery-ui-widget', DONMAN_PLUGIN_URL . 'lib/components/vendor/blueimp-file-upload/js/vendor/jquery.ui.widget.js', ['jquery'], '2.3.0' );
  wp_enqueue_script( 'blueimp-iframe-transport', DONMAN_PLUGIN_URL . 'lib/components/vendor/blueimp-file-upload/js/jquery.iframe-transport.js', ['jquery'], '2.3.0' );
  wp_enqueue_script( 'blueimp-file-upload', DONMAN_PLUGIN_URL . 'lib/components/vendor/blueimp-file-upload/js/jquery.fileupload.js', ['jquery'], '2.3.0' );
  wp_enqueue_script( 'cloudinary-file-upload', DONMAN_PLUGIN_URL . 'lib/components/vendor/cloudinary-jquery-file-upload/cloudinary-jquery-file-upload.js', ['jquery'], '2.3.0' );

  \Cloudinary::config([
      'cloud_name' => CLOUDINARY_CLOUD_NAME,
      'api_key' => CLOUDINARY_API_KEY,
      'api_secret' => CLOUDINARY_API_SECRET,
  ]);

  add_action( 'wp_footer', function(){
      $params = array();
      foreach (\Cloudinary::$JS_CONFIG_PARAMS as $param) {
          $value = \Cloudinary::config_get($param);
          if ($value) $params[$param] = $value;
      }
      $params = json_encode( $params );
      $script = str_replace( '{{params}}', $params, file_get_contents( DONMAN_PLUGIN_PATH . 'lib/js/cloudinary.js' ) );
      echo '<script type="text/javascript">' . $script . '</script>';

      $dm_styles = str_replace( '{{plugin_uri}}', DONMAN_PLUGIN_URL, file_get_contents( DONMAN_PLUGIN_PATH . 'lib/css/styles.css' ) );
      echo '<style type="text/css">' . $dm_styles . '</style>';
  }, 9999 );

  // Generate a signed file upload field
  // 08/12/2022 (09:18) - the file `cloudinary_cors.html` does
  // not exist anywhere that I've checked (production or dev).
  // Perhaps this function works by calling a pseudo URL?
  $file_upload_input = cl_image_upload_tag( 'user_photo_id[]', [
      'callback' => site_url() . '/cloudinary_cors.html',
      'html'  => [
          'multiple' => 'multiple',
      ],
  ]);

  $hbs_vars['file_upload_input'] = $file_upload_input;
}

if( empty( $template ) )
    $template = 'form3.screening-questions-form';
$html = render_template( $template, $hbs_vars );
add_html( $html );

// Add Realtor Ads to the bottom of the form.
$realtor_ads = get_realtor_ads([ $_SESSION['donor']['org_id'] ]);
if( $realtor_ads && 0 < count( $realtor_ads ) ){
    foreach( $realtor_ads as $ad ){
        add_html($ad);
    }
}