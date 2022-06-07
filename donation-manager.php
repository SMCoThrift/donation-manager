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

/**
 * Setup constants.
 *
 * @var        string  $css_dir Either `css` or `dist` depending on Site URL.
 */
$css_dir = ( stristr( site_url(), '.local' ) || SCRIPT_DEBUG )? 'css' : 'dist' ;
define( 'DONMAN_CSS_DIR', $css_dir );
define( 'DONMAN_DEV_ENV', stristr( site_url(), '.local' ) );
define( 'DONMAN_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'DONMAN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load required files
 */
require_once( DONMAN_PLUGIN_PATH . 'lib/fns/acf.php' );
require_once( DONMAN_PLUGIN_PATH . 'lib/fns/debugging.php' );

/**
 * Load required libraries and check for required plugins.
 */
require_once DONMAN_PLUGIN_PATH . 'lib/required-checks.php';
