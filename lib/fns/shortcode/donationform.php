<?php

namespace DonationManager\shortcodes;
use function DonationManager\callbacks\{track_url_path};
use function DonationManager\templates\{template_exists};
use function DonationManager\utilities\{get_alert};
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
  ], $atts );

  /**
   * Show DEBUG info when $_COOKIE['dmdebug'] is TRUE:
   */
  donationform_docs( $atts );

  /**
   *  NEXT PAGE - WHERE DOES OUR FORM REDIRECT?
   *
   *  The form's redirect is defined by the `nextpage` shortcode
   *  attribute. We allow this redirect to be user defined so
   *  the user can control the path through the site. This in
   *  turn allows for adding the various pages as steps in an
   *  analytics tracking funnel (e.g. Google Analytics).
   */
  $nextpage = ( is_null( $args['nextpage'] ) || empty( $args['nextpage'] ) )? get_permalink() : site_url( $args['nextpage'] ) ;

  /**
   *  RESET $_SESSION['donor'] ON HOME PAGE
   *
   *  We're assuming that the donation process begins on the site
   *  homepage. Therefore, we always reset the donor array so that
   *  we can begin the donation process and show the proper form
   *  by making sure that $_SESSION['donor']['form'] is unset.
   *
   *  In the event that we ever want to change this behavior, we
   *  could add some settings to the Donation Settings page we
   *  create with the ACF plugin. These settings would define
   *  which form displays on which page. Otherwise, we setup
   *  $_SESSION['donor']['form'] inside callback_init().
   */
  global $wp;
  $current_url = home_url( add_query_arg( array(), $wp->request ) );
  if( is_front_page() || is_page('donate-now') || stristr( $current_url, 'city-pages' ) )
      $_SESSION['donor'] = array();

  track_url_path();

  /**
   * The form we're displaying.
   *
   * @var        string
   */
  $form = ( isset( $_SESSION['donor']['form'] ) )? $_SESSION['donor']['form'] : 'default';
  if( isset( $_REQUEST['pcode'] ) ){
      $form = 'select-your-organization';
  } else if( isset( $_REQUEST['oid'] ) && isset( $_REQUEST['tid'] ) ){
      $form = 'describe-your-donation';
  }

  if( isset( $_SESSION['donor']['org_id'] ) ){
    /**
     * Used to switch on interface elements for User Photo Uploads.
     *
     * Used in the following files included by require_once():
     * - lib/fns/shortcode/donationform/contact-details.php
     *
     * @var        array
     */
    $user_photo_uploads = [
      'on'        => get_field( 'pickup_settings_allow_user_photo_uploads', $_SESSION['donor']['org_id'] ),
      'required'  => get_field( 'pickup_settings_user_photo_uploads_required', $_SESSION['donor']['org_id'] ),
    ];
  }

  $form_filename = DONMAN_PLUGIN_PATH . 'lib/fns/shortcode/donationform/' . $form . '.php';
  /**
   * If $form_filename does not exist, "bail" and show alert:
   */
  if( ! file_exists( $form_filename ) )
    return get_alert(['description' => 'I could not find <code>lib/fns/shortcode/donationform/' . basename( $form_filename ) . '</code>. Please create this file with any neccessary pre-processing for this form.']);

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