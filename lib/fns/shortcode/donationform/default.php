<?php
use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html};

$html = render_template( $template, [ 'nextpage' => $nextpage ] );
add_html( $html );
if( current_user_can( 'activate_plugins') && ! isset( $_COOKIE['dmdebug'] ) ){
  add_html( '<div style="text-align: center; font-size: 12px; margin-top: -20px;"><a href="./?dmdebug=true">Start Debug Mode</a></div>' );
} else if( current_user_can( 'activate_plugins') && isset( $_COOKIE['dmdebug'] ) && 'on' == $_COOKIE['dmdebug'] ){
  add_html( '<div style="text-align: center; font-size: 12px; margin-top: -20px; color: #999;">Debug Mode is ON.</div>' );
  $available_templates = glob( trailingslashit( DONMAN_PLUGIN_PATH ) . 'lib/templates/form0.*.hbs' );
  if( is_array( $available_templates ) ){
    $templates = [];
    foreach ($available_templates as $template ) {
      $templates[] = str_replace( '.hbs', '', basename( $template ) );
    }
    add_html( '<div>Available templates: <code>' . implode( ', ', $templates ) . '</code></div>' );
  }
}
