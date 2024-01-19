<?php
/**
 * Plugin Name:     Donation Manager
 * Plugin URI:      https://github.com/SMCoThrift/donation-manager
 * Description:     Online donation manager built for SMCoThrift and PickUpMyDonation.com. This plugin displays the donation form and handles donation submissions.
 * Author:          Michael Wender
 * Author URI:      https://mwender.com
 * Text Domain:     donation-manager
 * Domain Path:     /languages
 * Version:         4.2.3
 *
 * @package         DonationManager
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
//define( 'ORPHANED_PICKUP_RADIUS', 15 ); // radius in miles for zipcode search
define( 'AVERAGE_DONATION_VALUE', 230 ); // average value of a donation is $230
define( 'DONATION_TIMEOUT', 3 * MINUTE_IN_SECONDS );
define( 'GENERIC_DOMAINS', ['gmail.com', 'hotmail.com', 'verizon.net', 'comcast.net', 'sbcglobal.net', 'yahoo.com', 'att.net', 'chilitech.net', 'aol.com', 'yahoo.co', 'earthlink.net', 'pacbell.net' ] ); // Used to filter out generic email providers

$dmdebug = ( isset( $_COOKIE['dmdebug'] ) && 'on' == $_COOKIE['dmdebug'] )? true : false ;
define( 'DMDEBUG', $dmdebug );
$dmdebug_verbose = ( isset( $_COOKIE['dmdebug_verbose'] ) && 'on' == $_COOKIE['dmdebug_verbose'] )? true : false ;
define( 'DMDEBUG_VERBOSE', $dmdebug_verbose );

/**
 * Start our session
 */
if( ! defined( 'WP_CLI' ) && ! headers_sent() )
  session_start();

/**
 * Load required files
 */
$required_files = array_diff( scandir( DONMAN_PLUGIN_PATH . 'lib/fns' ), [ '.', '..', 'shortcode', 'cli', 'callback', 'callback-donation-report.organizations.php', 'callback-donation-report.donors.php' ] );
foreach( $required_files as $file ){
  require_once DONMAN_PLUGIN_PATH . 'lib/fns/' . $file;
}

/**
 * Load required libraries and check for required plugins.
 */
require_once DONMAN_PLUGIN_PATH . 'lib/required-checks.php';

// Include class files
require_once DONMAN_PLUGIN_PATH . 'lib/classes/cli.php';
require_once DONMAN_PLUGIN_PATH . 'lib/classes/cli.fixzips.php';
require_once DONMAN_PLUGIN_PATH . 'lib/classes/network-member.php';
require_once DONMAN_PLUGIN_PATH . 'lib/classes/organization.php';
require_once DONMAN_PLUGIN_PATH . 'lib/classes/background-processes.php';
$BackgroundDonationCountProcess = new DM_Donation_Count_Process();

// Initialize background process for deleteing/archiving donations:
require_once DONMAN_PLUGIN_PATH . 'lib/classes/background-delete-donation-process.php';
$GLOBALS['BackgroundDeleteDonationProcess'] = new DM_Delete_Donation_Process(); // We must set this as an explicit global in order for it to be available inside WPCLI

// Include our Orphaned Donations Class
require_once DONMAN_PLUGIN_PATH . 'lib/classes/orphaned-donations.php';
$DMOrphanedDonations = DMOrphanedDonations::get_instance();

// Include our Reporting Class
require_once DONMAN_PLUGIN_PATH . 'lib/classes/donation-reports.php';
$DMReports = DMReports::get_instance();
register_activation_hook( __FILE__, array( $DMReports, 'flush_rewrites' ) );
register_deactivation_hook( __FILE__, array( $DMReports, 'flush_rewrites' ) );