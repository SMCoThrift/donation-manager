<?php

/**
 * Load required files
 */
$required_files = array_diff( scandir( DONMAN_PLUGIN_PATH . 'lib/fns/shortcode' ), [ '.', '..', 'donationform' ] );
foreach( $required_files as $file ){
  require_once DONMAN_PLUGIN_PATH . 'lib/fns/shortcode/' . $file;
}