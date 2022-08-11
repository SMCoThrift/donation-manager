<?php

// check for plugin using plugin name
function donman_is_active_plugin( $plugin ){
  return ( in_array( $plugin, apply_filters( 'active_plugins', get_option('active_plugins') ) ) );
}

/**
 * Load Composer or display a notice if not loaded.
 */
if( file_exists( DONMAN_PLUGIN_PATH . 'vendor/autoload.php' ) ){
  require_once DONMAN_PLUGIN_PATH . 'vendor/autoload.php';
} else {
  add_action( 'admin_notices', function(){
    $class = 'notice notice-error';
    $message = __( 'Missing required Composer libraries. Please run `composer install` from the root directory of this plugin.', 'donation-manager' );
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
  } );
}

/**
 * Load "Persist Admin notice Dismissals" Composer plugin
 *
 * See: https://github.com/w3guy/persist-admin-notices-dismissal
 */
add_action( 'admin_init', array( 'PAnD', 'init' ) );

/**
 * Check for ACF
 */
if( ! class_exists( 'ACF' ) ){
  add_action( 'admin_notices', function(){
    $class = 'notice notice-error';
    $message = __( 'Missing <a href="https://www.advancedcustomfields.com" target="_blank">Advanced Custom Fields</a> plugin. Please install and activate.', 'donation-manager' );
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
  } );
}

/**
 * Check for (Custom Taxonomy Order)[https://wordpress.org/plugins/custom-taxonomy-order-ne/] plugin.
 */
if( ! donman_is_active_plugin( 'custom-taxonomy-order-ne/customtaxorder.php' ) ){
  add_action( 'admin_notices', function(){
    $class = 'notice notice-error';
    $message = __( 'Missing <a href="https://wordpress.org/plugins/custom-taxonomy-order-ne/" target="_blank">Custom Taxonomy Order</a> plugin. Please install and activate.', 'donation-manager' );
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
  } );
}

/**
 * Check for CPT UI
 */
if( ! donman_is_active_plugin( 'custom-post-type-ui/custom-post-type-ui.php' ) ){
  if( ! PAnD::is_admin_notice_active( 'notice-cptui-forever' ) ){
    add_action( 'admin_notices', function(){
      $class = 'notice notice-info is-dismissible';
      $message = __( 'Note: The <a href="https://wordpress.org/plugins/custom-post-type-ui/" target="_blank">CPT UI plugin</a> is not installed. Therefore we\'re using the CPT and Taxonomy definitions defined in the Donation Manager plugin\'s <code>lib/cpt/</code> directory.', 'donation-manager' );
      printf( '<div class="%1$s" data-dismissible="notice-cptui-forever"><p>%2$s</p></div>', esc_attr( $class ), $message );
    } );
  }
  $files = array_diff( scandir( DONMAN_PLUGIN_PATH . 'lib/cpt' ), [ '.', '..' ] );
  foreach( $files as $file ){
    require_once DONMAN_PLUGIN_PATH . 'lib/cpt/' . $file;
  }
}
