<?php
namespace DonationManager\callbacks;

/**
 * Used to redirect the page when we are skipping the screening questions.
 *
 * From the WordPress Codex: This action hook executes just before
 * WordPress determines which template page to load. It is a good
 * hook to use if you need to do a redirect with full knowledge of
 * the content that has been queried.
 *
 * @link http://codex.wordpress.org/Plugin_API/Action_Reference/template_redirect WordPress Codex > `template_redirect`.
 * @global obj $post Global post object.
 *
 * @since 1.0.0
 *
 * @return void
 */
function callback_template_redirect() {
  if( isset( $_SESSION['donor']['skipquestions'] ) && true == $_SESSION['donor']['skipquestions'] && 'screening-questions' == $_SESSION['donor']['form'] ) {
    global $post;
    unset( $_SESSION['donor']['skipquestions'] );
    if( has_shortcode( $post->post_content, 'donationform' ) ) {
      $_SESSION['donor']['form'] = 'contact-details';
      preg_match( '/nextpage="(.*)"/U', $post->post_content, $matches );
      if( $matches[1] ){
        uber_log( '🔔 Skipping screening questions. Redirecting to `' . $matches[1] . '`' );
        session_write_close();
        header( 'Location: ' . $matches[1] );
        die();
      }
    }
  }
}
add_action( 'template_redirect', __NAMESPACE__ . '\\callback_template_redirect' );