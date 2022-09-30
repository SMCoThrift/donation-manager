<?php

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