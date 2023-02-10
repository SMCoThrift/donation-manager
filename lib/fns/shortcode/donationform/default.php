<?php
use function DonationManager\templates\{render_template,template_exists};
use function DonationManager\globals\{add_html};

$template = 'form0.enter-your-zipcode';
if( ! empty( $args['template'] ) && template_exists( $args['template'] ) )
  $template = $args['template'];

$html = render_template( $template, [ 'nextpage' => $nextpage ] );
add_html( $html );
if( current_user_can( 'activate_plugins') && ! isset( $_COOKIE['dmdebug'] ) ){
  add_html( '<div style="text-align: center; font-size: 12px; margin-top: -20px;"><a href="./?dmdebug=true">Start Debug Mode</a></div>' );
} else if( current_user_can( 'activate_plugins') && isset( $_COOKIE['dmdebug'] ) && 'on' == $_COOKIE['dmdebug'] ){
  add_html( '<div style="text-align: center; font-size: 12px; margin-top: -20px; color: #999;">Debug Mode is ON.</div>' );
  $available_templates = glob( trailingslashit( DONMAN_PLUGIN_PATH ) . 'lib/templates/form0.*.hbs' );
  if( is_array( $available_templates ) ){
    $templates = [];
    foreach ($available_templates as $available_template ) {
      $templates[] = str_replace( '.hbs', '', basename( $available_template ) );
    }
    add_html( '<div style="color: #999;">Selected: <code>' . $template . '</code><br>Available: <code>' . implode( ', ', $templates ) . '</code></div>' );
  }
}
