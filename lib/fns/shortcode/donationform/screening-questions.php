<?php

use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html};
//use function DonationManager\utilities\{get_alert};
use function DonationManager\organizations\{get_screening_questions};
use function DonationManager\realtors\{get_realtor_ads};

$screening_questions = get_screening_questions( $_SESSION['donor']['org_id'] );

$questions = array();
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
 * 08/11/2022 (09:18) - TODO: Get Additional Details working...
 */
$provide_additional_details = get_post_meta( $_SESSION['donor']['org_id'], 'provide_additional_details', true );
$additional_details = ( isset( $_POST['donor']['additional_details'] ) )? esc_textarea( $_POST['donor']['additional_details'] ) : '';

$hbs_vars = [
    'questions' => $questions,
    'additional_details' => $additional_details,
    'nextpage' => $nextpage,
    'provide_additional_details' => $provide_additional_details,
];

/**
 * 08/11/2022 (09:19) - TODO: Get Cloudinary Photo Uploads working:
 */
// jQuery/Cloudinary Photo Upload
if( $allow_user_photo_uploads )
{
    wp_enqueue_script( 'blueimp-jquery-ui-widget', plugin_dir_url( __FILE__ ) . 'lib/components/vendor/blueimp-file-upload/js/vendor/jquery.ui.widget.js', ['jquery'], '2.3.0' );
    wp_enqueue_script( 'blueimp-iframe-transport', plugin_dir_url( __FILE__ ) . 'lib/components/vendor/blueimp-file-upload/js/jquery.iframe-transport.js', ['jquery'], '2.3.0' );
    wp_enqueue_script( 'blueimp-file-upload', plugin_dir_url( __FILE__ ) . 'lib/components/vendor/blueimp-file-upload/js/jquery.fileupload.js', ['jquery'], '2.3.0' );
    wp_enqueue_script( 'cloudinary-file-upload', plugin_dir_url( __FILE__ ) . 'lib/components/vendor/cloudinary-jquery-file-upload/cloudinary-jquery-file-upload.js', ['jquery'], '2.3.0' );

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
        $script = str_replace( '{{params}}', $params, file_get_contents( trailingslashit( DONMAN_DIR ) . 'lib/js/cloudinary.js' ) );
        echo '<script type="text/javascript">' . $script . '</script>';

        $dm_styles = str_replace( '{{plugin_uri}}', DONMAN_URL, file_get_contents( trailingslashit( DONMAN_DIR ) . 'lib/css/styles.css' ) );
        echo '<style type="text/css">' . $dm_styles . '</style>';
    }, 9999 );

    // Generate a signed file upload field
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