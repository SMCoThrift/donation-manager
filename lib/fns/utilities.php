<?php

namespace DonationManager\utilities;
use function DonationManager\templates\{render_template};

/**
 * Returns an HTML alert message
 *
 * @param      array  $atts {
 *   @type  string  $type         The alert type can be info, warning, success, or danger (defaults to `warning`).
 *   @type  string  $title        The title of the alert.
 *   @type  string  $description  The content of the alert.
 *   @type  string  $css_classes  Additional CSS classes to add to the alert parent <div>.
 *   @type  bool    $dismissable  Is the alert dismissable? (default FALSE)
 * }
 *
 * @return     html  The alert.
 */
function get_alert( $atts ){
  $args = shortcode_atts([
   'type'               => 'warning',
   'title'              => null,
   'description'        => 'Alert description goes here.',
   'css_classes'        => null,
   'dismissable'        => false,
  ], $atts );

  $args['dismissable'] = filter_var( $args['dismissable'], FILTER_VALIDATE_BOOLEAN );

  $data = [
    'description' => $args['description'],
    'title'       => $args['title'],
    'type'        => $args['type'],
    'css_classes' => $args['css_classes'],
    'dismissable' => $args['dismissable'],
  ];

  return render_template( 'alert', $data );
}

/**
 * Returns first array value from $_SESSION[‘donor’][‘url_path’]
 *
 * @since 1.?.?
 *
 * @return string First value from $_SESSION[‘donor’][‘url_path’]
 */
function get_referer(){
    if(
        ! isset( $_SESSION['donor']['url_path'] )
        || ! is_array( $_SESSION['donor']['url_path'] )
        || ! isset( $_SESSION['donor']['url_path'][0] )
    )
        return null;

    $referer = $_SESSION['donor']['url_path'][0];
    return $referer;
}

/**
 * Enqueues styles and scripts.
 */
function enqueue_scripts(){
  wp_register_style( 'form', DONMAN_PLUGIN_URL . 'lib/css/form.css', null, filemtime( DONMAN_PLUGIN_PATH . 'lib/css/form.css' ) );
};
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );

/**
 * Gets the posted variable.
 *
 * Returns the following:
 *
 * - If $_POST[$varname] isset
 * - else if $_SESSION[$varname] isset
 * - else an empty string
 *
 * Check for a multi-level array value by using a
 * colon (i.e. `:`) between each level. Example:
 *
 * `get_posted_var( 'foo:bar' )` checks for $_POST['foo']['bar']
 *
 * @param      string  $varname  The varname
 *
 * @return     string  The value of the posted variable.
 */
function get_posted_var( $varname ){
  $varname = ( stristr( $varname, ':') )? explode( ':', $varname ) : [$varname];
  $value = '';
  //*
  switch( count( $varname ) ){
    case 4:
        if( isset( $_POST[$varname[0]][$varname[1]][$varname[2]][$varname[3]] ) ){
            $value = $_POST[$varname[0]][$varname[1]][$varname[2]][$varname[3]];
        } else if( isset( $_SESSION[$varname[0]][$varname[1]][$varname[2]][$varname[3]] ) ){
            $value = $_SESSION[$varname[0]][$varname[1]][$varname[2]][$varname[3]];
        }
    break;
    case 3:
        if( isset( $_POST[$varname[0]][$varname[1]][$varname[2]] ) ){
            $value = $_POST[$varname[0]][$varname[1]][$varname[2]];
        } else if( isset( $_SESSION[$varname[0]][$varname[1]][$varname[2]] ) ){
            $value = $_SESSION[$varname[0]][$varname[1]][$varname[2]];
        }
    break;
    case 2:
        if( isset( $_POST[$varname[0]][$varname[1]] ) ){
            $value = $_POST[$varname[0]][$varname[1]];
        } else if( isset( $_SESSION[$varname[0]][$varname[1]] ) ){
            $value = $_SESSION[$varname[0]][$varname[1]];
        }
    break;
    case 1:
        if( isset( $_POST[$varname[0]] ) ){
            $value = $_POST[$varname[0]];
        } else if( isset( $_SESSION[$varname[0]] ) ){
            $value = $_SESSION[$varname[0]];
        }
    break;
  }
  return $value;
}
