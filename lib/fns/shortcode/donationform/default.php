<?php
use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html};

$html = render_template( $args['template'], [ 'nextpage' => $nextpage ] );
add_html( $html );
if( current_user_can( 'activate_plugins') && ! isset( $_COOKIE['dmdebug'] ) ){
  add_html( '<div style="text-align: center; font-size: 12px; margin-top: -20px;"><a href="./?dmdebug=true">Start Debug Mode</a></div>' );
} else if( current_user_can( 'activate_plugins') && isset( $_COOKIE['dmdebug'] ) && 'on' == $_COOKIE['dmdebug'] ){
  add_html( '<div style="text-align: center; font-size: 12px; margin-top: -20px; color: #fff;">Debug Mode is ON.</div>' );
}
