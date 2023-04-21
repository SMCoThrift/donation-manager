<?php

function cptui_register_my_cpts_trans_dept() {

  /**
   * Post Type: Transportation Departments.
   */

  $labels = [
    "name" => __( "Transportation Departments", "donation-manager" ),
    "singular_name" => __( "Transportation Department", "donation-manager" ),
    "menu_name" => __( "My Transportation Departments", "donation-manager" ),
    "all_items" => __( "All Transportation Departments", "donation-manager" ),
    "add_new" => __( "Add new", "donation-manager" ),
    "add_new_item" => __( "Add new Transportation Department", "donation-manager" ),
    "edit_item" => __( "Edit Transportation Department", "donation-manager" ),
    "new_item" => __( "New Transportation Department", "donation-manager" ),
    "view_item" => __( "View Transportation Department", "donation-manager" ),
    "view_items" => __( "View Transportation Departments", "donation-manager" ),
    "search_items" => __( "Search Transportation Departments", "donation-manager" ),
    "not_found" => __( "No Transportation Departments found", "donation-manager" ),
    "not_found_in_trash" => __( "No Transportation Departments found in trash", "donation-manager" ),
    "parent" => __( "Parent Transportation Department:", "donation-manager" ),
    "featured_image" => __( "Featured image for this Transportation Department", "donation-manager" ),
    "set_featured_image" => __( "Set featured image for this Transportation Department", "donation-manager" ),
    "remove_featured_image" => __( "Remove featured image for this Transportation Department", "donation-manager" ),
    "use_featured_image" => __( "Use as featured image for this Transportation Department", "donation-manager" ),
    "archives" => __( "Transportation Department archives", "donation-manager" ),
    "insert_into_item" => __( "Insert into Transportation Department", "donation-manager" ),
    "uploaded_to_this_item" => __( "Upload to this Transportation Department", "donation-manager" ),
    "filter_items_list" => __( "Filter Transportation Departments list", "donation-manager" ),
    "items_list_navigation" => __( "Transportation Departments list navigation", "donation-manager" ),
    "items_list" => __( "Transportation Departments list", "donation-manager" ),
    "attributes" => __( "Transportation Departments attributes", "donation-manager" ),
    "name_admin_bar" => __( "Transportation Department", "donation-manager" ),
    "item_published" => __( "Transportation Department published", "donation-manager" ),
    "item_published_privately" => __( "Transportation Department published privately.", "donation-manager" ),
    "item_reverted_to_draft" => __( "Transportation Department reverted to draft.", "donation-manager" ),
    "item_scheduled" => __( "Transportation Department scheduled", "donation-manager" ),
    "item_updated" => __( "Transportation Department updated.", "donation-manager" ),
    "parent_item_colon" => __( "Parent Transportation Department:", "donation-manager" ),
  ];

  $args = [
    "label" => __( "Transportation Departments", "donation-manager" ),
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
    "rewrite" => [ "slug" => "trans_dept", "with_front" => true ],
    "query_var" => true,
    "menu_position" => 6,
    "menu_icon" => "dashicons-networking",
    "supports" => [ "title","author" ],
    "show_in_graphql" => false,
  ];

  register_post_type( "trans_dept", $args );
}

add_action( 'init', 'cptui_register_my_cpts_trans_dept' );

