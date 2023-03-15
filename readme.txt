=== Donation Manager ===
Contributors: TheWebist
Tags: donations, CPT
Requires at least: 6.0.0
Tested up to: 6.1.1
Requires PHP: 8.0
Stable tag: 3.6.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete donation intake system for WordPress.

== Description ==

Long description goes here...

== Changelog ==

= 3.6.2.1 =
* Bugfix: Addressing correct namespace in `[unsubscribe-orphaned-contact]` shortcode.

= 3.6.2 =
* Updating `wp dm fixzips` to work with new PMD 3.0 data structures.

= 3.6.1.1 =
* Updating admin column width for Pickup Codes on Transportation Department CPT listing.

= 3.6.1 =
* Updating CHHJ API Response to show plaintext errors stored in `api_response` meta field.

= 3.6.0 =
* API Response monitoring via new "API Response" column in the Donation CPT admin listing.

= 3.5.1.1 =
* Removing call to `print_r()` before saving CHHJ API Response.

= 3.5.1 =
* Saving CHHJ API Response as serialized array.
* Adding admin CSS for Donation listing and API Response column.

= 3.5.0 =
* Updating `wp dm report --type=organizations` to correctly pull data from donations.
* Updating `wp dm report` to work without needing to enter a YYYY-MM.

= 3.4.2.2 =
* BUGFIX: Removing orphaned donation note from Exclusive Partners emails.

= 3.4.2.1 =
* BUGFIX: Checking if `$contacts` is null before using `count()` in `get_priority_organizations()`.
* Including note in Trans Dept notifications in markets without any contacts.

= 3.4.2 =
* Listing orphaned donation notifications in the Donations admin post listing.

= 3.4.1 =
* Updating email address in Transportation Department note for Orphaned Donation emails.
* Removing social sharing note from Donor Confirmation email.

= 3.4.0 =
* New Feature: `[click_to_claim]` shortcode for processing "Click to Claim" links.
* Removing "social sharing" note on donation receipt.
* Adding `get_contact()` for retrieving contact details of Network Providers.

= 3.3.9.7 =
* BUGFIX: Using `DONMAN_DEV_ENV` instead of `WP_DEBUG` to accomodate SpinupWP default settings in production.

= 3.3.9.6 =
* BUGFIX: PHP 8 compatiblity: Removing optional parameter appearing before required parameter in `DonationRouter::save_api_response()`.
* Adding WP CLI test for `get_donation_routing_method()`.

= 3.3.9.5 =
* BUGFIX: Fixing `get_donations_by_area()` so that the zip code for a donation is returned to be used in grabbing the coordinates for a donation.
* Adding `get_donation_zip_code()` for retrieving a donation's zip code given a Donation ID.

= 3.3.9.4 =
* BUGFIX: Updating `custom_save_post()` to handle integer (i.e. Org ID) returned from `organization` field.

= 3.3.9.3 =
* Correctly calling `CHHJDonationRouter` from the parent namespace inside `send_api_post()`.
* Checking for `$_SESSION['donor']` in `send_email()` before setting variables.

= 3.3.9.2 =
* Setting `publicly_queryable` and `show_in_rest` to `false` for Donations CPT.
* Setting `exclude_from_search` to `true` for Donations CPT.

= 3.3.9.1 =
* BUGFIX: Adding PMD 2.0 images.

= 3.3.9 =
* Styling/Layout for `[donors_in_your_area/]`.
* Correcting namespace function calls in `api.rest.php` for use with `[donors_in_your_area/]`.
* Adding `stat` attribute to `[donation-stats]` for calling "donations-last-month" and "donations-last-month-value".

= 3.3.8 =
* Adding `orphaned_donation_exists()`.

= 3.3.7.2 =
* BUGFIX: Refactoring screening questions to work with Taxonomy Order plugin.

= 3.3.7.1 =
* BUGFIX: Checking if variable isset() inside `get_screening_questions()` rather than true/false.

= 3.3.7 =
* Setting `DMDEBUG` constant.
* DEBUG output for donor emails in `send_email()`.

= 3.3.6 =
* BUGFIX: Manually setting ACF Display Rules for City Page Options to include the Page ID for "City Pages" on the production server. By doing this, any time we update the plugin on production, the City Page Options should be properly displayed for child pages of the "City Pages" page.
* BUGFIX: Checking for object before attempting to retrieve object property in `custom_save_post()`.

= 3.3.5 =
* BUGFIX: Converting variable assigments to work with ACF true/false fields when working with Organization Pickup Settings.

= 3.3.4 =
* BUGFIX: Checking for array in `get_realtor_ads()`.

= 3.3.3 =
* BUGFIX: Moving `$template` variable assigment inside `lib/fns/shortcode/donationform/default.php` to allow all other steps to properly set their own form templates.

= 3.3.2 =
* Setting default initial donation form for `[donationform/]`.
* Showing available `template=""` options for initial `[donationform/]` option.

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
