<?php
namespace DonationManager\shortcodes;

/**
 * Lists pages in an HTML list.
 *
 * @param      array  $atts {
 *   @type    string   $tag HTML tag to use for the list parent (i.e. ul or ol).
 *   @type    integer  $parent Parent ID of the page we're listing the children of.
 *   @type    string   $title The title we're using for the link text (default: post_title).
 * }
 *
 * @return     string  HTML list of pages.
 */
function list_pages( $atts ){
  $args = shortcode_atts([
    'tag'     => 'ul',
    'parent'  => null,
    'title'   => 'post_title',
  ], $atts );

  $get_pages_args = [
    'parent'  => $args['parent'],
  ];

  $html = [];
  $pages = get_pages( $get_pages_args );
  if( $pages ){

    $html[] = '<' . $args['tag'] . '>';
    foreach ( $pages as $key => $page ) {
      switch( $args['title'] ){
        case 'alt_title':
          $title = get_post_meta( $page->ID, 'alt_title', true );
          if( empty( $title ) )
            $title = get_post_meta( $page->ID, 'city', true );
          if( empty( $title ) )
            $title = $page->post_title;
          break;

        default:
          $title = $page->post_title;
          break;
      }

      $html[] = '<li><a href="' . get_page_link( $page->ID ) . '">' . $title . '</a></li>';
    }
    $html[] = '</' . $args['tag'] . '>';
  }

  return implode( '', $html );
}
add_shortcode( 'list_pages', __NAMESPACE__ . '\\list_pages' );