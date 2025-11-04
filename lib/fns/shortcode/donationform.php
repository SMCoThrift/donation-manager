<?php

namespace DonationManager\shortcodes;
use function DonationManager\callbacks\{track_url_path};
use function DonationManager\templates\{template_exists};
use function DonationManager\utilities\{get_alert,donman_start_session};
use function DonationManager\globals\{add_html,get_html};

/**
 * Renders our donation form.
 *
 * @param      array  $atts {
 *    Optional. An array of arguments.
 *
 *    @type  string  $nextpage  Specify the permalink of the next page where
 *                              the donation form will $_POST its submission.
 *    @type  string  $template  Specify the Handlebars template used to render
 *                              the form.
 * }
 *
 * @return     string  Donation form HTML.
 */
function donationform( $atts ){
  $args = shortcode_atts([
    'nextpage'  => null,
    'template'  => null,
    'reset'     => false,
  ], $atts );
  $args['reset'] = filter_var( $args['reset'], FILTER_VALIDATE_BOOLEAN );

  // Show DEBUG info
  donationform_docs( $atts );

  // Determine nextpage URL
  $nextpage_attr = trim( (string) $args['nextpage'] );
  if ( $nextpage_attr === '' ) {
    $nextpage = get_permalink();
  } elseif ( filter_var( $nextpage_attr, FILTER_VALIDATE_URL ) ) {
    $nextpage = $nextpage_attr;
  } else {
    $nextpage = home_url( '/' . ltrim( $nextpage_attr, '/' ) );
  }

  /**
   * SESSION RESET LOGIC — hardened to avoid nuking donor mid-flow
   */
  global $wp;
  $current_url = home_url( add_query_arg( array(), $wp->request ) );
  $reset_session_donor = false;

  donman_start_session(); // ensure session before tests

  $in_flow = ! empty( $_SESSION['donor']['_in_flow'] );

  // If redirect flag present, clear it and bail (no reset)
  if ( isset( $_SESSION['donor']['_redirecting'] ) ) {
    unset( $_SESSION['donor']['_redirecting'] );
    session_write_close();
  } 
  else {

    $is_start_page = (
      is_front_page()
      || is_page('donate-now')
      || stristr( $current_url, 'city-pages' )
      || $args['reset']
    );

    // Only reset if: on a start page AND not already in flow
    if ( $is_start_page && ! $in_flow ) {

      $msg = '⚠️⚠️⚠️ RESETTING $_SESSION[donor]';
      $msg.= "\n" . ' - $args[reset] = ' . $args['reset'];
      $msg.= "\n" . ' - is_front_page() = ' . is_front_page();
      $msg.= "\n" . ' - is_page(\'donate-now\') = ' . is_page('donate-now');
      $msg.= "\n" . ' - stristr( ' . $current_url . ', \'city-pages\' ) = ' . stristr( $current_url, 'city-pages' );
      $msg.= "\n" . ' - REFERRER: ' . wp_get_referer();
      $msg.= "\n" . ' - Session 🆔: ' . session_id();

      uber_log( $msg );
      $reset_session_donor = true;
    }
  }

  if ( $reset_session_donor ) {
    $_SESSION['donor'] = array();
    // Mark fresh start of flow
    $_SESSION['donor']['_in_flow'] = true;
  }

  // If we're on the final "thank you" page, reset donor session
  if ( is_page('thank-you') ) {

      if ( DMDEBUG_VERBOSE ) {
          uber_log("🎯 Flow complete — resetting donor session on thank-you page.");
      }

      // Reset session to allow new donation flow
      //$_SESSION['donor'] = [];
      $_SESSION['donor']['_completed'] = true; // optional for reporting
      unset( $_SESSION['donor']['_in_flow'] );

      session_write_close();
  }  

  // Abort/reset flow ONLY if user truly navigates to homepage
  if (
      isset($_SESSION['donor']['_in_flow'])
      && ! isset($_SESSION['donor']['_redirecting'])
      && is_front_page()
      && empty($_GET) // no query parameters
      && ! wp_get_referer() // direct visit, not redirect bounce
  ) {
      if ( DMDEBUG_VERBOSE ) {
          uber_log("🏠 Clean home visit — resetting donor session (true abort).");
      }

      $_SESSION['donor'] = [];
      session_write_close();
  }

  track_url_path();

  /**
   * Determine form file to load based on state
   */
  $form = ( isset( $_SESSION['donor']['form'] ) )? $_SESSION['donor']['form'] : 'default';

  if( isset( $_REQUEST['pcode'] ) ){
    $form = 'select-your-organization';
  } elseif( isset( $_REQUEST['oid'] ) && isset( $_REQUEST['tid'] ) ){
    $form = 'describe-your-donation';
  }

  $user_photo_uploads = [
    'on'       => false,
    'required' => false,
  ];

  if( isset( $_SESSION['donor']['org_id'] ) ){
    $user_photo_uploads = [
      'on'        => get_field( 'pickup_settings_allow_user_photo_uploads', $_SESSION['donor']['org_id'] ),
      'required'  => get_field( 'pickup_settings_user_photo_uploads_required', $_SESSION['donor']['org_id'] ),
    ];
  }

  $form_filename = DONMAN_PLUGIN_PATH . 'lib/fns/shortcode/donationform/' . $form . '.php';

  if( ! file_exists( $form_filename ) ) {
    return get_alert(['description' => 'I could not find <code>lib/fns/shortcode/donationform/' . basename( $form_filename ) . '</code>.']);
  }

  static $donationform_rendered = false;
  if ( $donationform_rendered ) {
    return ''; // prevent duplicate screens
  }
  $donationform_rendered = true;  

  wp_enqueue_style( 'form' );
  require_once( $form_filename );

  return get_html();
}
add_shortcode( 'donationform', __NAMESPACE__ . '\\donationform' );

/**
 * Outputs the docs for the `[donationform]` shortcode.
 */
function donationform_docs( $atts = [] ){
  if( current_user_can( 'activate_plugins') && isset( $_COOKIE['dmdebug'] ) && 'on' == $_COOKIE['dmdebug'] ){
    add_action( 'wp_footer', function() use ( $atts ){
      echo '<style>.docs code{color: #900; background: #eee; padding: 1px 3px; font-size: .8em;} .docs h3{font-size: 1em; font-weight: bold; margin-bottom: .25em;}</style>';
      echo '<div class="docs" style="padding: 1em; margin: 1em; background-color: #f8f8f8; border-radius: 3px;">';
      echo get_alert([
        'description' => '<strong>NOTE:</strong> This note and the following array output is only visible to logged in PMD Admins.',
        'type'        => 'info',
      ]);
      echo '<div style="display: flex;" class="flex-columns">';
      $nextpage = ( is_array( $atts ) && array_key_exists( 'nextpage', $atts ) )? $atts['nextpage'] : '';
      echo '<div style="width: 50%;"><pre style="text-align: left; font-size: 12px;">Shortcode: [donationform nextpage="' . $nextpage . '" /] (👈 The shortcode as it is used on this 👆 page.)<br/><br/>$_SESSION[\'donor\'] = ' . print_r( $_SESSION['donor'], true ) . '<br/>$_COOKIE[\'dmdebug\'] = ' . $_COOKIE['dmdebug'] . '<br/>Constant: DMDEBUG = ' . DMDEBUG . '</pre></div>';
      echo '<div style="width: 50%;">';
      echo file_get_contents( DONMAN_PLUGIN_PATH . 'lib/docs/shortcode.donationform.html' );
      echo file_get_contents( DONMAN_PLUGIN_PATH . 'lib/docs/shortcode.get_alert.html' );
      echo '</div>';
      echo '</div><!-- .flex-columns -->';
      echo '</div>';
    });
  }
}