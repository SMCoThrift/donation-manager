<?php

namespace DonationManager\templates;
use function DonationManager\utilities\{get_alert};

/**
 * Retrieves template from /lib/html/
 */

/**
 * Retrieves a template from /lib/html/.
 *
 * @param      string  $filename              The filename
 * @param      array   $search_replace_array  The search replace array
 *
 * @return     string  The template part.
 */
function get_template_part( $filename = '', $search_replace_array = array() ) {
  if( empty( $filename ) )
    return get_alert(['type' => 'danger', 'description' => '<strong>ERROR:</strong> No filename!']);

  $file = DONMAN_PLUGIN_PATH . 'lib/html/' . $filename . '.html';

  if( ! file_exists( $file ) )
    return get_alert(['type' => 'danger', 'description' => '<strong>ERROR:</strong> File not found! (<em>' . basename( $file ) . '</em>)']);

  $template = file_get_contents( $file );

  if( is_array( $search_replace_array ) && 0 < count( $search_replace_array ) ) {
    $search_array = [];
    $replace_array = [];
    foreach( $search_replace_array as $search => $replace ) {
      $search_array[] = '{' . $search . '}';
      $replace_array[] = $replace;
    }
    $template = str_replace( $search_array, $replace_array, $template );
  }

  return $template;
}

/**
 * Renders a Handlebars template.
 *
 * Requires `zordius/lightncandy` Composer library for
 * PHP Handlebars template processing.
 *
 * @param      string  $filename  The filename
 * @param      array   $data      The data passed to the handlebars template.
 *
 * @return     string    The rendered template.
 */
function render_template( $filename = '', $data = [] ){
  if( empty( $filename ) )
    return false;

  // Remove file extension
  $extensions = ['.hbs', '.htm', '.html'];
  $filename = str_replace( $extensions, '', $filename );

  $compile = 'false';

  $theme_path = \get_stylesheet_directory();
  $theme_template = \trailingslashit( $theme_path ) . 'donation-manager-templates/' . $filename . '.hbs';
  $theme_template_compiled = \trailingslashit( $theme_path ) . 'donation-manager-templates/compiled/' . $filename . '.php';

  $plugin_template = trailingslashit( DONMAN_PLUGIN_PATH ) . 'lib/templates/' . $filename . '.hbs';
  $plugin_template_compiled = \trailingslashit( DONMAN_PLUGIN_PATH ) . 'lib/templates/compiled/' . $filename . '.php';

  if( file_exists( $theme_template ) ){
    if( ! file_exists( $theme_template_compiled ) ){
      $compile = 'true';
    } else if( filemtime( $theme_template ) > filemtime( $theme_template_compiled ) ){
      $compile = 'true';
    }

    $template = $theme_template;
    $template_compiled = $theme_template_compiled;
  } else if( file_exists( $plugin_template ) ){
    if( ! file_exists( $plugin_template_compiled ) ){
      $compile = 'true';
    } else if( filemtime( $plugin_template ) > filemtime( $plugin_template_compiled ) ){
      $compile = 'true';
    }

    $template = $plugin_template;
    $template_compiled = $plugin_template_compiled;
  } else if( ! file_exists( $plugin_template ) ){
    return false;
  }

  $template = [
    'filename' => $template,
    'filename_compiled' => $template_compiled,
    'compile' => $compile,
  ];

  if( ! file_exists( dirname( $template['filename_compiled'] ) ) )
    \wp_mkdir_p( dirname( $template['filename_compiled'] ) );

  if( 'true' == $template['compile'] ){
    $hbs_template = file_get_contents( $template['filename'] );
    $phpStr = \LightnCandy\LightnCandy::compile( $hbs_template, [
      'flags' => \LightnCandy\LightnCandy::FLAG_SPVARS | \LightnCandy\LightnCandy::FLAG_PARENT | \LightnCandy\LightnCandy::FLAG_ELSE
    ] );
    if ( ! is_writable( dirname( $template['filename_compiled'] ) ) )
      \wp_die( 'I can not write to the directory.' );
    file_put_contents( $template['filename_compiled'], '<?php' . "\n" . $phpStr . "\n" . '?>' );
  }

  if( ! file_exists( $template['filename_compiled'] ) )
    return false;

  $renderer = include( $template['filename_compiled'] );

  return $renderer( $data );
}

/**
 * Checks if template file exists.
 *
 * @since 1.4.6
 *
 * @param string $filename Filename of the template to check for.
 * @return bool Returns TRUE if template file exists.
 */
function template_exists( $filename = '' ){
  if( empty( $filename ) )
  return false;

  // Remove file extension
  $extensions = ['.hbs', '.htm', '.html'];
  $filename = str_replace( $extensions, '', $filename );

  $theme_path = \get_stylesheet_directory();
  $theme_template = \trailingslashit( $theme_path ) . 'donation-manager-templates/' . $filename . '.hbs';

  $plugin_template = trailingslashit( DONMAN_PLUGIN_PATH ) . 'lib/templates/' . $filename . '.hbs';

  if( file_exists( $theme_template ) ){
    return true;
  } else if( file_exists( $plugin_template ) ){
    return true;
  } else if( ! file_exists( $plugin_template ) ){
    return false;
  }
}