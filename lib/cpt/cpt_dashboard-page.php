<?php
function cptui_register_my_cpts() {

	/**
	 * Post Type: Dashboard Pages.
	 */

	$labels = [
		"name" => esc_html__( "Dashboard Pages", "hello-elementor" ),
		"singular_name" => esc_html__( "Dashboard Page", "hello-elementor" ),
		"menu_name" => esc_html__( "My dashboard page", "hello-elementor" ),
		"all_items" => esc_html__( "All dashboard page", "hello-elementor" ),
		"add_new" => esc_html__( "Add new", "hello-elementor" ),
		"add_new_item" => esc_html__( "Add new dashboard_page", "hello-elementor" ),
		"edit_item" => esc_html__( "Edit dashboard_page", "hello-elementor" ),
		"new_item" => esc_html__( "New dashboard_page", "hello-elementor" ),
		"view_item" => esc_html__( "View dashboard_page", "hello-elementor" ),
		"view_items" => esc_html__( "View dashboard_page", "hello-elementor" ),
		"search_items" => esc_html__( "Search dashboard_page", "hello-elementor" ),
		"not_found" => esc_html__( "No dashboard page found", "hello-elementor" ),
		"not_found_in_trash" => esc_html__( "No dashboard page found in trash", "hello-elementor" ),
		"parent" => esc_html__( "Parent dashboard page:", "hello-elementor" ),
		"featured_image" => esc_html__( "Featured image for this dashboard_page", "hello-elementor" ),
		"set_featured_image" => esc_html__( "Set featured image for this dashboard_page", "hello-elementor" ),
		"remove_featured_image" => esc_html__( "Remove featured image for this dashboard_page", "hello-elementor" ),
		"use_featured_image" => esc_html__( "Use as featured image for this dashboard_page", "hello-elementor" ),
		"archives" => esc_html__( "dashboard_page archives", "hello-elementor" ),
		"insert_into_item" => esc_html__( "Insert into dashboard_page", "hello-elementor" ),
		"uploaded_to_this_item" => esc_html__( "Upload to this dashboard_page", "hello-elementor" ),
		"filter_items_list" => esc_html__( "Filter dashboard_page list", "hello-elementor" ),
		"items_list_navigation" => esc_html__( "dashboard_page list navigation", "hello-elementor" ),
		"items_list" => esc_html__( "dashboard_page list", "hello-elementor" ),
		"attributes" => esc_html__( "dashboard_page attributes", "hello-elementor" ),
		"name_admin_bar" => esc_html__( "dashboard_page", "hello-elementor" ),
		"item_published" => esc_html__( "dashboard_page published", "hello-elementor" ),
		"item_published_privately" => esc_html__( "dashboard_page published privately.", "hello-elementor" ),
		"item_reverted_to_draft" => esc_html__( "dashboard_page reverted to draft.", "hello-elementor" ),
		"item_scheduled" => esc_html__( "dashboard_page scheduled", "hello-elementor" ),
		"item_updated" => esc_html__( "dashboard_page updated.", "hello-elementor" ),
		"parent_item_colon" => esc_html__( "Parent dashboard_page:", "hello-elementor" ),
	];

	$args = [
		"label" => esc_html__( "Dashboard Pages", "hello-elementor" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => false,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => true,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => true,
		"can_export" => false,
		"rewrite" => [ "slug" => "dashboard_page", "with_front" => true ],
		"query_var" => "dashboard",
		"menu_icon" => "dashicons-networking",
		"supports" => [ "title", "editor" ],
		"show_in_graphql" => false,
	];

	register_post_type( "dashboard_page", $args );
}

add_action( 'init', 'cptui_register_my_cpts' );
