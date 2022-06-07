<?php

function cptui_register_my_taxes_screening_question() {

  /**
   * Taxonomy: Screening Questions.
   */

  $labels = [
    "name" => __( "Screening Questions", "donation-manager" ),
    "singular_name" => __( "Screening Question", "donation-manager" ),
    "menu_name" => __( "Screening Questions", "donation-manager" ),
    "all_items" => __( "All Screening Questions", "donation-manager" ),
    "edit_item" => __( "Edit Screening Question", "donation-manager" ),
    "view_item" => __( "View Screening Question", "donation-manager" ),
    "update_item" => __( "Update Screening Question name", "donation-manager" ),
    "add_new_item" => __( "Add new Screening Question", "donation-manager" ),
    "new_item_name" => __( "New Screening Question name", "donation-manager" ),
    "parent_item" => __( "Parent Screening Question", "donation-manager" ),
    "parent_item_colon" => __( "Parent Screening Question:", "donation-manager" ),
    "search_items" => __( "Search Screening Questions", "donation-manager" ),
    "popular_items" => __( "Popular Screening Questions", "donation-manager" ),
    "separate_items_with_commas" => __( "Separate Screening Questions with commas", "donation-manager" ),
    "add_or_remove_items" => __( "Add or remove Screening Questions", "donation-manager" ),
    "choose_from_most_used" => __( "Choose from the most used Screening Questions", "donation-manager" ),
    "not_found" => __( "No Screening Questions found", "donation-manager" ),
    "no_terms" => __( "No Screening Questions", "donation-manager" ),
    "items_list_navigation" => __( "Screening Questions list navigation", "donation-manager" ),
    "items_list" => __( "Screening Questions list", "donation-manager" ),
    "back_to_items" => __( "Back to Screening Questions", "donation-manager" ),
    "name_field_description" => __( "The name is how it appears on your site.", "donation-manager" ),
    "parent_field_description" => __( "Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.", "donation-manager" ),
    "slug_field_description" => __( "The slug is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.", "donation-manager" ),
    "desc_field_description" => __( "The description is not prominent by default; however, some themes may show it.", "donation-manager" ),
  ];

  
  $args = [
    "label" => __( "Screening Questions", "donation-manager" ),
    "labels" => $labels,
    "public" => false,
    "publicly_queryable" => true,
    "hierarchical" => false,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => [ 'slug' => 'screening_question', 'with_front' => true, ],
    "show_admin_column" => false,
    "show_in_rest" => true,
    "show_tagcloud" => false,
    "rest_base" => "screening_question",
    "rest_controller_class" => "WP_REST_Terms_Controller",
    "rest_namespace" => "wp/v2",
    "show_in_quick_edit" => false,
    "sort" => false,
    "show_in_graphql" => false,
  ];
  register_taxonomy( "screening_question", [ "organization" ], $args );
}
add_action( 'init', 'cptui_register_my_taxes_screening_question' );

