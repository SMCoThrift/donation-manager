<?php
namespace DonationManager\passwordreset;
use function DonationManager\templates\{render_template};

function custom_password_reset_email_content_type() {
    return 'text/html';
}

function custom_password_reset_email( $message, $key, $user_login, $user_data ) {
    // Switch content type to HTML
    add_filter( 'wp_mail_content_type', __NAMESPACE__ . '\\custom_password_reset_email_content_type' );

    // Customize your email content here
    $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    $reset_link = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' );



    $message = '<h1>Password Reset Request</h1>';
    $message .= '<p>Hello ' . $user_login . ',</p>';
    $message .= '<p>A request to reset your password was made. If you did not make this request, please ignore this email.</p>';
    $message .= '<p>To reset your password, visit the following address: <a href="' . $reset_link . '">' . $reset_link . '</a></p>';
    $message .= '<p>Thanks,</p>';
    $message .= '<p>' . $site_name . '</p>';

    $hbs_vars = [ 'year' => date('Y'), 'header_logo' => DONMAN_PLUGIN_URL . 'lib/images/pickupmydonation-logo-inverted_1200x144.png', 'email_content' => $message ];
    $html = render_template( 'email.user-portal-notification', $hbs_vars );
    // Reset content type to avoid conflicts
    //remove_filter( 'wp_mail_content_type', __NAMESPACE__ . '\\custom_password_reset_email_content_type' );

    return $html;
}

// Add the filters
add_filter( 'retrieve_password_message', __NAMESPACE__ . '\\custom_password_reset_email', 10, 4 );
