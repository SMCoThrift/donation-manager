<?php

namespace DonationManager\utilities;
use function DonationManager\templates\{render_template};

/**
 * Returns an HTML alert message
 *
 * @param      array  $atts {
 *   @type  string  $type         The alert type can be info, warning, success, or danger (defaults to `warning`).
 *   @type  string  $title        The title of the alert.
 *   @type  string  $description  The content of the alert.
 *   @type  string  $css_classes  Additional CSS classes to add to the alert parent <div>.
 *   @type  bool    $dismissable  Is the alert dismissable? (default FALSE)
 * }
 *
 * @return     html  The alert.
 */
function get_alert( $atts ){
  $args = shortcode_atts([
   'type'               => 'warning',
   'title'              => null,
   'description'        => 'Alert description goes here.',
   'css_classes'        => null,
   'dismissable'        => false,
  ], $atts );

  $args['dismissable'] = filter_var( $args['dismissable'], FILTER_VALIDATE_BOOLEAN );

  $data = [
    'description' => $args['description'],
    'title'       => $args['title'],
    'type'        => $args['type'],
    'css_classes' => $args['css_classes'],
    'dismissable' => $args['dismissable'],
  ];

  return render_template( 'alert', $data );
}

/**
 * Gets the boilerplate.
 *
 * @param      array  $atts   The atts
 *
 * @return     string  The boilerplate.
 */
function get_boilerplate( $atts ){
    extract( shortcode_atts( array(
        'title' => null,
    ), $atts ) );

    if( is_null( $title ) )
        return;

    switch( $title ){
        case 'about-pmd':
        case 'aboutpmd':
            $html = '<h3 style="clear: both; display: block; margin-top: 40px;">About PickUpMyDonation.com</h3>
Our mission is to connect you with organizations who will pick up your donation. Our donation process is quick and simple. Schedule your donation pick up with our online donation pick up form. Our system sends your donation directly to your chosen pick up provider. They will then contact you to finalize your selected pick up date.';
        break;
    }

    return $html;
}

/**
 * Returns first array value from $_SESSION[‘donor’][‘url_path’]
 *
 * @since 1.?.?
 *
 * @return string First value from $_SESSION[‘donor’][‘url_path’]
 */
function get_referer(){
    if(
        ! isset( $_SESSION['donor']['url_path'] )
        || ! is_array( $_SESSION['donor']['url_path'] )
        || ! isset( $_SESSION['donor']['url_path'][0] )
    )
        return null;

    $referer = $_SESSION['donor']['url_path'][0];
    return $referer;
}

/**
 * Gets a test donation.
 *
 * @return     array  The test donation.
 */
function get_test_donation( $use_different_pickup_address = 'no' ){
  $donor = [
    'url_path' => [
      0 => 'https://pmdthree.local/',
      1 => 'https://pmdthree.local/?dmdebug=true',
      2 => 'https://pmdthree.local/select-your-organization/?pcode=37922',
      3 => 'https://pmdthree.local/step-one/?oid=122&tid=124&priority=0',
      4 => 'https://pmdthree.local/step-two/',
      5 => 'https://pmdthree.local/step-three/',
    ],
    'pickup_code' => 37922,
    'rewrite_titles'  => null,
    'org_id'  => 122,
    'trans_dept_id' => 124,
    'priority'  => 0,
    'items' => [
      11 => 'Large Furniture',
    ],
    'description' => 'Couch and Love Seat',
    'screening_questions' => [
      27 => [
        'question'  => 'Is your donation in any way broken or damaged?',
        'answer'    => 'No',
      ],
      29 => [
        'question'  => 'Has your donation been in a smoking environment?',
        'answer'    => 'No',
      ],
      28 => [
        'question'  => 'Has your donation been in a pet environment - items used frequently by pets, covered in hair, have pet stains or pet odor?',
        'answer'    => 'No',
      ],
    ],
    'address' => [
      'name' => [
        'first' => 'Michael',
        'last'  => 'Wender',
      ],
      'company' => 'Test Co',
      'address' => '123 Any Street',
      'city'    => 'Knoxville',
      'state'   => 'TN',
      'zip'     => '37922',
    ],
    'different_pickup_address'  => 'No',
    'email' => 'michael@michaelwender.com',
    'phone' => '(865) 454-2121',
    'preferred_contact_method'  => 'Email',
    'preferred_code'  => 'Testing preferred_code...',
    'reason'  => 'Remodeling',
    'pickupdate1' => '11/21/2022',
    'pickuptime1' => '8:00AM - 11:00AM',
    'pickupdate2' => '11/23/2022',
    'pickuptime2' => '8:00AM - 11:00AM',
    'pickupdate3' => '11/25/2022',
    'pickuptime3' => '8:00AM - 11:00AM',
    'pickuplocation' => 'Outside/Garage',
  ];

  if( 'yes' == $use_different_pickup_address ){
    $donor['different_pickup_address'] = 'Yes';
    $donor['pickup_address'] = [
      'address' => '321 Other Street',
      'city'    => 'Knoxville',
      'state'   => 'TN',
      'zip'     => '37931',
    ];
  }

  // Set a unique email to avoid a duplicate donation error:
  $donor['email'] = current_time('U') . '@example.com';

  return $donor;
}

/**
 * Enqueues styles and scripts.
 */
function enqueue_scripts(){
  wp_register_style( 'form', DONMAN_PLUGIN_URL . 'lib/css/form.css', null, filemtime( DONMAN_PLUGIN_PATH . 'lib/css/form.css' ) );
};
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );

/**
 * Gets the posted variable.
 *
 * Returns the following:
 *
 * - If $_POST[$varname] isset
 * - else if $_SESSION[$varname] isset
 * - else an empty string
 *
 * Check for a multi-level array value by using a
 * colon (i.e. `:`) between each level. Example:
 *
 * `get_posted_var( 'foo:bar' )` checks for $_POST['foo']['bar']
 *
 * @param      string  $varname  The varname
 *
 * @return     string  The value of the posted variable.
 */
function get_posted_var( $varname ){
  $varname = ( stristr( $varname, ':') )? explode( ':', $varname ) : [$varname];
  $value = '';
  //*
  switch( count( $varname ) ){
    case 4:
        if( isset( $_POST[$varname[0]][$varname[1]][$varname[2]][$varname[3]] ) ){
            $value = $_POST[$varname[0]][$varname[1]][$varname[2]][$varname[3]];
        } else if( isset( $_SESSION[$varname[0]][$varname[1]][$varname[2]][$varname[3]] ) ){
            $value = $_SESSION[$varname[0]][$varname[1]][$varname[2]][$varname[3]];
        }
    break;
    case 3:
        if( isset( $_POST[$varname[0]][$varname[1]][$varname[2]] ) ){
            $value = $_POST[$varname[0]][$varname[1]][$varname[2]];
        } else if( isset( $_SESSION[$varname[0]][$varname[1]][$varname[2]] ) ){
            $value = $_SESSION[$varname[0]][$varname[1]][$varname[2]];
        }
    break;
    case 2:
        if( isset( $_POST[$varname[0]][$varname[1]] ) ){
            $value = $_POST[$varname[0]][$varname[1]];
        } else if( isset( $_SESSION[$varname[0]][$varname[1]] ) ){
            $value = $_SESSION[$varname[0]][$varname[1]];
        }
    break;
    case 1:
        if( isset( $_POST[$varname[0]] ) ){
            $value = $_POST[$varname[0]];
        } else if( isset( $_SESSION[$varname[0]] ) ){
            $value = $_SESSION[$varname[0]];
        }
    break;
  }
  return $value;
}

/**
 * Saves a report CSV to the WordPress media library.
 *
 * @param      string  $filename  The name of the file
 * @param      string  $content   The content of the file
 *
 * @return     int       The attachment ID.
 */
function save_report_csv( $filename = null, $content = null ){
  $upload_dir = \wp_upload_dir();
  $reports_dir = \trailingslashit( $upload_dir['basedir'] . '/reports' . $upload_dir['subdir'] );

  $access_type = \get_filesystem_method();
  if( 'direct' === $access_type ){
    $creds = \request_filesystem_credentials( \site_url() . '/wp-admin/', '', false, false, array() );

    // break if we find any problems
    if( ! \WP_Filesystem( $creds ) )
      return new \WP_Error( 'nocredentials', __( 'Unable to get filesystem credentials.', 'donman' ) );

    global $wp_filesystem;

    // Create the directory for the report

    // Check/Create /uploads/reports/
    if( ! $wp_filesystem->is_dir( $upload_dir['basedir'] . '/reports' ) )
      $wp_filesystem->mkdir( $upload_dir['basedir'] . '/reports' );

    // Check/Create /uploads/reports/ subdirs
    if( ! $wp_filesystem->is_dir( $reports_dir ) ){
      $subdirs = explode( '/', $upload_dir['subdir'] );
      $chk_dir = $upload_dir['basedir'] . '/reports/';
      foreach( $subdirs as $dir ){
        $chk_dir.= $dir . '/';
        if( ! $wp_filesystem->is_dir( $chk_dir ) )
          $wp_filesystem->mkdir( $chk_dir );
      }
    }

    if( ! $wp_filesystem->is_dir( $reports_dir ) )
      return new \WP_Error( 'noreportsdir', __( 'Unable to create reports directory.', 'donman' ) );

    $filetype = \wp_check_filetype( $filename, null );

    $filepath = \trailingslashit( $reports_dir ) . $filename;
    if( ! $wp_filesystem->put_contents( $filepath, $content, FS_CHMOD_FILE ) )
      return new \WP_Error( 'filesaveerror', __( 'Error saving file.', 'donman' ) );

    $attachment = array(
      'guid' => \trailingslashit( $upload_dir['baseurl'] . '/reports' . $upload_dir['subdir'] ) . $filename,
      'post_mime_type' => $filetype['type'],
      'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
      'post_content'   => '',
      'post_status'    => 'inherit'
    );
    $attach_id = \wp_insert_attachment( $attachment, $filepath );

    // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    // Generate the metadata for the attachment, and update the database record.
    $attach_data = \wp_generate_attachment_metadata( $attach_id, $filename );
    \wp_update_attachment_metadata( $attach_id, $attach_data );

    return $attach_id;
  }
}
