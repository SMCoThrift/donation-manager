=== Donation Manager ===
Contributors: TheWebist
Tags: donations, CPT
Requires at least: 4.5
Tested up to: 6.1.1
Requires PHP: 7.2
Stable tag: 3.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete donation intake system for WordPress.

== Description ==

Long description goes here...

== Changelog ==

= 3.3.1 =
* Adding `sort_column` option to `[list_pages/]`.
* Updating `list_pages()` to use `get_posts()` instead of `get_pages()`.

= 3.3.0 =
* Adding "Alternate Title" to City Page options.
* Adding `[list_pages/]` shortcode for listing the children of a page.

= 3.2.1 =
* BUGFIX: Checking for numeric value before attempting `+=` operation in `get_archived_donations()`.

= 3.2.0 =
* Adding City Pages sidebar shortcode (i.e. `[city_page_sidebar/]`).
* Adding City Pages realtor description shortcode (i.e. `[city_page_realtor_description/]`).
* Adding `[donationform template="form0.city-page" /]` template for City Page.
* Adding "template" attribute for `[donationform/]` shortcode.

= 3.1.0 =
* Adding City Pages ACF fields.

= 3.0.2 =
* Updating `lib/fns/admin.php::custom_save_post()` to properly obtain the `$org_id`.

= 3.0.1 =
* Adding `pickup_code` taxonomy to `lib/cpt/`.
* Adding Github URL to README.

= 3.0.0 =
* Complete rewrite of PMD 2.0.
* Setting up CPTs and Taxonomies inside `lib/cpt/`.
* Storing ACF definitions in `lib/acf-json/`.
* Dismissable admin notifications.
