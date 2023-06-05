<?php

namespace DonationManager\callbacks;

/**
 * Hooks to `init`. Logs the donor's entire path through the system.
 *
 * @return void
 */
function track_url_path(){
    if( ! isset( $_SESSION['donor']['url_path'] ) || ! is_array( $_SESSION['donor']['url_path'] )  )
        $_SESSION['donor']['url_path'] = array();

    $site_host = str_replace( array( 'http://', 'https://' ), '', site_url() );

    $referer = ( isset( $_SERVER['HTTP_REFERER'] ) && ! empty( $_SERVER['HTTP_REFERER'] ) )? $_SERVER['HTTP_REFERER'] : '' ;
    $referer_url = parse_url( $referer );
    $referer_host = ( isset( $referer_url['host'] ) )? $referer_url['host'] : '';

    // Start a new array if our referer is not from this site
    if( $site_host != $referer_host )
        $_SESSION['donor']['url_path'] = array( $referer );

    $last_referer = end( $_SESSION['donor']['url_path'] );
    reset( $_SESSION['donor']['url_path'] );
    if( ! empty( $referer ) && $referer != $last_referer )
        $_SESSION['donor']['url_path'][] = $referer;
}
add_action( 'init', __NAMESPACE__ . '\\track_url_path', 100 );
