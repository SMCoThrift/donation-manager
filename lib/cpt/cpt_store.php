<?php

function cptui_register_my_cpts_store() {

  /**
   * Post Type: Stores.
   */

  $labels = [
    "name" => __( "Stores", "hello-elementor" ),
    "singular_name" => __( "Store", "hello-elementor" ),
    "menu_name" => __( "My Stores", "hello-elementor" ),
    "all_items" => __( "All Stores", "hello-elementor" ),
    "add_new" => __( "Add new", "hello-elementor" ),
    "add_new_item" => __( "Add new Store", "hello-elementor" ),
    "edit_item" => __( "Edit Store", "hello-elementor" ),
    "new_item" => __( "New Store", "hello-elementor" ),
    "view_item" => __( "View Store", "hello-elementor" ),
    "view_items" => __( "View Stores", "hello-elementor" ),
    "search_items" => __( "Search Stores", "hello-elementor" ),
    "not_found" => __( "No Stores found", "hello-elementor" ),
    "not_found_in_trash" => __( "No Stores found in trash", "hello-elementor" ),
    "parent" => __( "Parent Store:", "hello-elementor" ),
    "featured_image" => __( "Featured image for this Store", "hello-elementor" ),
    "set_featured_image" => __( "Set featured image for this Store", "hello-elementor" ),
    "remove_featured_image" => __( "Remove featured image for this Store", "hello-elementor" ),
    "use_featured_image" => __( "Use as featured image for this Store", "hello-elementor" ),
    "archives" => __( "Store archives", "hello-elementor" ),
    "insert_into_item" => __( "Insert into Store", "hello-elementor" ),
    "uploaded_to_this_item" => __( "Upload to this Store", "hello-elementor" ),
    "filter_items_list" => __( "Filter Stores list", "hello-elementor" ),
    "items_list_navigation" => __( "Stores list navigation", "hello-elementor" ),
    "items_list" => __( "Stores list", "hello-elementor" ),
    "attributes" => __( "Stores attributes", "hello-elementor" ),
    "name_admin_bar" => __( "Store", "hello-elementor" ),
    "item_published" => __( "Store published", "hello-elementor" ),
    "item_published_privately" => __( "Store published privately.", "hello-elementor" ),
    "item_reverted_to_draft" => __( "Store reverted to draft.", "hello-elementor" ),
    "item_scheduled" => __( "Store scheduled", "hello-elementor" ),
    "item_updated" => __( "Store updated.", "hello-elementor" ),
    "parent_item_colon" => __( "Parent Store:", "hello-elementor" ),
  ];

  $args = [
    "label" => __( "Stores", "hello-elementor" ),
    "labels" => $labels,
    "description" => "",
    "public" => true,
    "publicly_queryable" => true,
    "show_ui" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "rest_controller_class" => "WP_REST_Posts_Controller",
    "rest_namespace" => "wp/v2",
    "has_archive" => false,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "delete_with_user" => false,
    "exclude_from_search" => false,
    "capability_type" => "post",
    "map_meta_cap" => true,
    "hierarchical" => false,
    "can_export" => false,
    "rewrite" => [ "slug" => "store", "with_front" => true ],
    "query_var" => true,
    "menu_position" => 7,
    "menu_icon" => "dashicons-store",
    "supports" => [ "title" ],
    "show_in_graphql" => false,
  ];

  register_post_type( "store", $args );
}

add_action( 'init', 'cptui_register_my_cpts_store' );

