<?php

function cptui_register_my_cpts_dashboard_page() {

	/**
	 * Post Type: Dashboard Pages.
	 */

	$labels = [
		"name" => esc_html__( "Dashboard Pages", "hello-elementor" ),
		"singular_name" => esc_html__( "Dashboard Page", "hello-elementor" ),
		"menu_name" => esc_html__( "My Dashboard Pages", "hello-elementor" ),
		"all_items" => esc_html__( "All Dashboard Pages", "hello-elementor" ),
		"add_new" => esc_html__( "Add new", "hello-elementor" ),
		"add_new_item" => esc_html__( "Add new Dashboard Page", "hello-elementor" ),
		"edit_item" => esc_html__( "Edit Dashboard Page", "hello-elementor" ),
		"new_item" => esc_html__( "New Dashboard Page", "hello-elementor" ),
		"view_item" => esc_html__( "View Dashboard Page", "hello-elementor" ),
		"view_items" => esc_html__( "View Dashboard Pages", "hello-elementor" ),
		"search_items" => esc_html__( "Search Dashboard Pages", "hello-elementor" ),
		"not_found" => esc_html__( "No Dashboard Pages found", "hello-elementor" ),
		"not_found_in_trash" => esc_html__( "No Dashboard Pages found in trash", "hello-elementor" ),
		"parent" => esc_html__( "Parent Dashboard Page:", "hello-elementor" ),
		"featured_image" => esc_html__( "Featured image for this Dashboard Page", "hello-elementor" ),
		"set_featured_image" => esc_html__( "Set featured image for this Dashboard Page", "hello-elementor" ),
		"remove_featured_image" => esc_html__( "Remove featured image for this Dashboard Page", "hello-elementor" ),
		"use_featured_image" => esc_html__( "Use as featured image for this Dashboard Page", "hello-elementor" ),
		"archives" => esc_html__( "Dashboard Page archives", "hello-elementor" ),
		"insert_into_item" => esc_html__( "Insert into Dashboard Page", "hello-elementor" ),
		"uploaded_to_this_item" => esc_html__( "Upload to this Dashboard Page", "hello-elementor" ),
		"filter_items_list" => esc_html__( "Filter Dashboard Pages list", "hello-elementor" ),
		"items_list_navigation" => esc_html__( "Dashboard Pages list navigation", "hello-elementor" ),
		"items_list" => esc_html__( "Dashboard Pages list", "hello-elementor" ),
		"attributes" => esc_html__( "Dashboard Pages attributes", "hello-elementor" ),
		"name_admin_bar" => esc_html__( "Dashboard Page", "hello-elementor" ),
		"item_published" => esc_html__( "Dashboard Page published", "hello-elementor" ),
		"item_published_privately" => esc_html__( "Dashboard Page published privately.", "hello-elementor" ),
		"item_reverted_to_draft" => esc_html__( "Dashboard Page reverted to draft.", "hello-elementor" ),
"item_scheduled" => esc_html__( "Dashboard Page scheduled", "hello-elementor" ),
		"item_updated" => esc_html__( "Dashboard Page updated.", "hello-elementor" ),
		"parent_item_colon" => esc_html__( "Parent Dashboard Page:", "hello-elementor" ),
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
		"can_export" => true,
		"rewrite" => [ "slug" => "dashboard", "with_front" => true ],
		"query_var" => true,
		"menu_icon" => "dashicons-networking",
		"supports" => [ "title", "editor", "page-attributes" ],
		"show_in_graphql" => false,
	];

	register_post_type( "dashboard_page", $args );
}

add_action( 'init', 'cptui_register_my_cpts_dashboard_page' );

