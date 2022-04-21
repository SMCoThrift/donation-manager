<?php
/**
 * Plugin Name:     Donation Manager
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Online donation manager built for SMCoThrift and PickUpMyDonation.com. This plugin displays the donation form and handles donation submissions.
 * Author:          Michael Wender
 * Author URI:      https://mwender.com
 * Text Domain:     donation-manager
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Donation_Manager
 */

// Your code starts here.
$css_dir = ( stristr( site_url(), '.local' ) || SCRIPT_DEBUG )? 'css' : 'dist' ;
define( 'DONMAN_CSS_DIR', $css_dir );
define( 'DONMAN_DEV_ENV', stristr( site_url(), '.local' ) );
define( 'DONMAN_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'DONMAN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load required files
 */
require_once( DONMAN_PLUGIN_PATH . 'lib/fns/acf.php' );

/**
 * Load Composer or display a notice if not loaded.
 */
if( file_exists( DONMAN_PLUGIN_PATH . 'vendor/autoload.php' ) ){
  require_once DONMAN_PLUGIN_PATH . 'vendor/autoload.php';
} else {
  add_action( 'admin_notices', function(){
    $class = 'notice notice-error';
    $message = __( 'Missing required Composer libraries. Please run `composer install` from the root directory of this plugin.', 'donman' );
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
  } );
}

/**
 * Check for ACF
 */
if( ! class_exists( 'ACF' ) ){
  add_action( 'admin_notices', function(){
    $class = 'notice notice-error';
    $message = __( 'Missing <a href="https://www.advancedcustomfields.com" target="_blank">Advanced Custom Fields</a> plugin. Please install and activate.', 'donman' );
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
  } );
}

/**
 * Enhanced logging.
 *
 * @param      string  $message  The log message
 */
function uber_log( $message = null ){
  static $counter = 1;

  $bt = debug_backtrace();
  $caller = array_shift( $bt );

  if( 1 == $counter )
    error_log( "\n\n" . str_repeat('-', 25 ) . ' STARTING DEBUG [' . date('h:i:sa', current_time('timestamp') ) . '] ' . str_repeat('-', 25 ) . "\n\n" );
  error_log( "\n" . $counter . '. ' . basename( $caller['file'] ) . '::' . $caller['line'] . "\n" . $message . "\n---\n" );
  $counter++;
}