<?php

function cptui_register_my_taxes_pickup_code() {

  /**
   * Taxonomy: Pickup Codes.
   */

  $labels = [
    "name" => esc_html__( "Pickup Codes", "hello-elementor" ),
    "singular_name" => esc_html__( "Pickup Code", "hello-elementor" ),
    "menu_name" => esc_html__( "Pickup Codes", "hello-elementor" ),
    "all_items" => esc_html__( "All Pickup Codes", "hello-elementor" ),
    "edit_item" => esc_html__( "Edit Pickup Code", "hello-elementor" ),
    "view_item" => esc_html__( "View Pickup Code", "hello-elementor" ),
    "update_item" => esc_html__( "Update Pickup Code name", "hello-elementor" ),
    "add_new_item" => esc_html__( "Add new Pickup Code", "hello-elementor" ),
    "new_item_name" => esc_html__( "New Pickup Code name", "hello-elementor" ),
    "parent_item" => esc_html__( "Parent Pickup Code", "hello-elementor" ),
    "parent_item_colon" => esc_html__( "Parent Pickup Code:", "hello-elementor" ),
    "search_items" => esc_html__( "Search Pickup Codes", "hello-elementor" ),
    "popular_items" => esc_html__( "Popular Pickup Codes", "hello-elementor" ),
    "separate_items_with_commas" => esc_html__( "Separate Pickup Codes with commas", "hello-elementor" ),
    "add_or_remove_items" => esc_html__( "Add or remove Pickup Codes", "hello-elementor" ),
    "choose_from_most_used" => esc_html__( "Choose from the most used Pickup Codes", "hello-elementor" ),
    "not_found" => esc_html__( "No Pickup Codes found", "hello-elementor" ),
    "no_terms" => esc_html__( "No Pickup Codes", "hello-elementor" ),
    "items_list_navigation" => esc_html__( "Pickup Codes list navigation", "hello-elementor" ),
    "items_list" => esc_html__( "Pickup Codes list", "hello-elementor" ),
    "back_to_items" => esc_html__( "Back to Pickup Codes", "hello-elementor" ),
    "name_field_description" => esc_html__( "The name is how it appears on your site.", "hello-elementor" ),
    "parent_field_description" => esc_html__( "Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.", "hello-elementor" ),
    "slug_field_description" => esc_html__( "The slug is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.", "hello-elementor" ),
    "desc_field_description" => esc_html__( "The description is not prominent by default; however, some themes may show it.", "hello-elementor" ),
  ];

  
  $args = [
    "label" => esc_html__( "Pickup Codes", "hello-elementor" ),
    "labels" => $labels,
    "public" => true,
    "publicly_queryable" => true,
    "hierarchical" => false,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => [ 'slug' => 'pickup_code', 'with_front' => true, ],
    "show_admin_column" => false,
    "show_in_rest" => true,
    "show_tagcloud" => false,
    "rest_base" => "pickup_code",
    "rest_controller_class" => "WP_REST_Terms_Controller",
    "rest_namespace" => "wp/v2",
    "show_in_quick_edit" => false,
    "sort" => false,
    "show_in_graphql" => false,
  ];
  register_taxonomy( "pickup_code", [ "trans_dept", "donation" ], $args );
}
add_action( 'init', 'cptui_register_my_taxes_pickup_code' );