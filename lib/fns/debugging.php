<?php
/**
 * Sets $_COOKIE[‘dmdebug’] for debuging purposes.
 *
 * @since 1.?.?
 *
 * @return void
 */
function set_debug_cookie(){
    if( ! isset( $_GET['dmdebug'] ) )
        return;

    $debug = ( 'false' === strtolower( $_GET['dmdebug'] ) )? false : 'on';
    setcookie( 'dmdebug', $debug, time() + 3600, COOKIEPATH, COOKIE_DOMAIN );
}
add_action( 'init', 'set_debug_cookie', 98 );

/**
 * Enhanced logging.
 *
 * @param      mixed  $message  The log message, can be a string or an array.
 */
if( ! function_exists( 'uber_log' ) ){
  function uber_log( $message = null, $varname = '' ){
    static $counter = 1;

    $bt = debug_backtrace();
    $caller = array_shift( $bt );

    //date('h:i:sa', current_time('timestamp') )
    if( 1 == $counter )
      error_log( "\n\n" . str_repeat('-', 25 ) . ' STARTING DEBUG [' . current_time( 'h:i:sa' ) . '] ' . str_repeat('-', 25 ) . "\n\n" );

    if( is_array( $message ) || is_object( $message ) ){
      $message_str = "🔔 $varname is Array: \n";
      foreach ($message as $key => $value) {
        if( is_array( $value ) || is_object( $value ) )
          $value = print_r( $value, true );
        $message_str.= "-- 👉 " . $key . ' = ' . $value . "\n";
      }
      $message = $message_str;
    }

    error_log( "\n" . $counter . '. ' . basename( $caller['file'] ) . '::' . $caller['line'] . "\n" . $message . "\n---\n" );
    $counter++;
  }
}


if( ! function_exists( 'uber_log_table') ){
  /**
   * Logs out an array as a table.
   *
   * @param      array  $array               The array
   * @param      bool   $prefer_public_vars  If TRUE, will discard variables starting with an underscore.
   *
   * @return     string The array formatted as an ASCII table.
   */
  function uber_log_table( $array = [], $prefer_public_vars = false ){
    if( 0 == count( $array ) )
      return false;

    ksort( $array );
    $rows = [];
    foreach( $array as $key => $value ){
      if( $prefer_public_vars && '_' == substr( $key, 0, 1 ) )
        continue;

      $rows[] = [
        'key' => $key,
        'value' => $value,
      ];
    }

    ob_start();
    WP_CLI\Utils\format_items( 'table', $rows, array( 'key', 'value' ) );
    $table = ob_get_contents();
    ob_end_clean();
    error_log( "\n🔔 uber_log_table() 👇\n" . $table );
  }
}