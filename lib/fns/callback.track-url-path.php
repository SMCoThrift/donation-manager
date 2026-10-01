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

/**
 * Builds the initial $_SESSION['donor']['url_path'] when the zip/donation code
 * form is submitted.
 *
 * The page the donor landed on is served from the page cache without a session,
 * so the referrer which brought them to the site can't be recorded server side.
 * lib/js/scripts.js records it in the browser and posts it with the form as
 * `donman_referrer` and `donman_landing`.
 *
 * The first value is always the external referrer (empty for direct visits) as
 * that is what get_referer() saves with the donation.
 *
 * @return array The external referrer followed by the landing page URL.
 */
function get_landing_url_path(){
    $site_host = parse_url( home_url(), PHP_URL_HOST );

    $posted_url = function( $key ){
        if( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) )
            return '';
        return esc_url_raw( substr( wp_unslash( $_POST[ $key ] ), 0, 2000 ), [ 'http', 'https' ] );
    };

    $referrer = $posted_url( 'donman_referrer' );
    if( $site_host == parse_url( $referrer, PHP_URL_HOST ) )
        $referrer = '';

    // Without JS, the page which posted the form is the best landing page we have.
    $landing = $posted_url( 'donman_landing' );
    if( empty( $landing ) && ! empty( $_SERVER['HTTP_REFERER'] ) )
        $landing = esc_url_raw( $_SERVER['HTTP_REFERER'], [ 'http', 'https' ] );
    if( $site_host != parse_url( $landing, PHP_URL_HOST ) )
        $landing = '';

    $url_path = [ $referrer ];
    if( ! empty( $landing ) )
        $url_path[] = $landing;

    return $url_path;
}
