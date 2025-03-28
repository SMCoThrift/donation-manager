=== Donation Manager ===
Contributors: TheWebist
Tags: donations, CPT
Requires at least: 6.5.0
Tested up to: 6.7.2
Requires PHP: 8.1
Stable tag: 5.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete donation intake system for WordPress.

== Changelog ==

= 5.4.0 =
* Supabase sync for Donation CPTs via `wp dm supabase sync donations`.
* Specify the limit for Supabase syncing via the `--limit=<limit>` parameter. Currently works for `donations`.

= 5.3.4 =
* Removing Dashicons for frontend users.

= 5.3.3 =
* Loading `user-dashboard.css` only when user is logged in.

= 5.3.2 =
* Removing enqueue for HTMX in favor of the plugin enqueuing.

= 5.3.1 =
* Loading HTMX locally.

= 5.3.0 =
* Supabase syncing for Organizations and Transportation Departments via WP CLI (`wp dm supabase`).
* Declare `orphaned_donations_hook` to fix PHP 8.2 warning.

= 5.2.0 =
* Updating social links in Donor Confirmation, Monthly Donor Report, and Transportation Department Notification emails.
* Adding `{{year}}` variable to email footers to allow for dynamic insertion of current year in the copyright notice.
* Adding `--force` and `--orgs` options to `$ wp dm report`.
* BUGFIX: Admin alerts for `invalid_link`, `missing_org_transdept_notification`, and `zipcode_mismatch` updated to utilize the "User Portal Notification" template. Previously they were set to use the legacy "Blank Template" which no longer exists resulting in an empty email which generated a fail over our transactional email API.
* BUGFIX: Correcting redirect for an `invalid_link` to return to the home page.

= 5.1.0 =
* Not adding alert styling to Step 1 Note when alert HTML is already present.
* Adjusting "Prices start as low" text from "$50" to "$100".
* Adding documentation for `[getalert/]` shortcode.

= 5.0.0 =
* Saving `api_post_timestamp`.

= 4.9.6 =
* BUGFIX: Correcting variable collision in `cli.resend.php`.

= 4.9.5 =
* BUGFIX: Correctly referencing variable for `$pickup_code` in `cli.resend.php`.

= 4.9.4 =
* BUGFIX: Adding namespacing for `get_organizations()` call in `cli.resend.php`.

= 4.9.3 =
* Setting "api" routing method when resending donations with PickUpMyDonation.com as the Org.

= 4.9.2 =
* Updating Communications Consent notice.

= 4.9.1 =
* Setting `api_response_code=pending` when attempting to resend a donation via `wp dm resend`.

= 4.9.0 =
* Adding `wp dm resend` for resending failed API posts.

= 4.8.5 =
* Correcting storage logic for cURL Gateway Timeouts.
* Removing "Reason" column from "Fails" table for `wp dm stats priority`.

= 4.8.4 =
* Update API Save Response Code to 408 when Response Message contains "cURL error 28: Operation timed out".

= 4.8.3 =
* BUGFIX: Preventing division by zero in `wp dm stats priority` when no donations have been recorded for an organization.

= 4.8.2 =
* BUGFIX: Updating variable naming in `wp dm stats priority`.

= 4.8.1 =
* BUGFIX: Correctly storing donation stats with `wp dm stats priority`.

= 4.8.0 =
* Adding `wp dm stats priority` for generating priority partner stats.

= 4.7.3 =
* Storing 1800GJ API Response->body->message in `api_response_message`.

= 4.7.2 =
* Adding 1800GJ API Stats widget.

= 4.7.1 =
* Adding "The Good Truck" to `wp dm fixallzips`.

= 4.7.0 =
* Saving `api_method` when posting to an API. This paves the way for a rewrite of our stats code.

= 4.6.0 =
* Now sending API submissions for Orphaned Donations.

= 4.5.6 =
* Updating post timeout from 10 to 15s for 1800GJ API.

= 4.5.5 =
* Fixing conditional statement so that emails are still sent when posting to 1800GJ API.
* Addressing unset variables during "Skip Pick Up Dates" donation flow.

= 4.5.4 =
* Compiling HBS templates with `sms_consent_note`.

= 4.5.3 =
* Adding SMS Consent note.

= 4.5.2 =
* Adding additional data sanitization for GOTJUNK API posts.

= 4.5.1 =
* Checking for and using `GOTJUNK_API_EP` when sending an API post for 1-800-Got-Junk.

= 4.5.0 =
* Adding API Routing for 1-800-GOT-JUNK.

= 4.4.6 =
* Major update to `get_organizations()`: We are removing leading "zeros" from pickup codes. So, if a donor searches on `01420`, `get_organizations( "01420" )` will search for Transportation Departments which have "1420" as their assigned zip code.
* Updating `wp dm fixzips` to remove leading "zeros" during import of zip codes from a CSV.
* Adding `wp dm fixallzips` to process all CSVs found in the Franchisee Maps directory.
* Adding `--hidefranchiseelist` option to `wp dm fixzips`.
* Adding `--hidetable` option to `wp dm fixzips`.
* Fixing: "Using ${var} in strings is deprecated, use {$var} instead"

= 4.4.5.2 =
* BUGFIX: Updating check for building `$edit_url` in `get_organizations()` so that edit link for Priority Orgs will show for logged in admins.

= 4.4.5.1 =
* BUGFIX: Newer versions of Bower require `name` to be lowercase with no spaces. Changed `"name": "Donation Manager WordPress Plugin"` to `"name": "donation-manager-wordpress-plugin"` in bower.json.

= 4.4.5 =
* Custom handling display of Pickup Codes in admin listing for Trans Depts. Now departments with greater than 201 pick up codes show a number indicating how many are mapped to the department rather than listing them all in the admin table listing.
* Adding --reset switch for `wp cli fixzips`
* Checking for `DONMAN_DEV_ENV` when sending Transportation Department notification. This allows for easy localhost testing. If "Development Environment", then we are sending the notification email even if the provider receives donations via their API.

= 4.4.4.6 =
* BUGFIX: Setting `permission_callback` for zip code search to `__return_true`.

= 4.4.4.5 =
* BUGFIX: Setting correct relative path in `donation-reports.php` to allow for downloading of "Combined Reports".

= 4.4.4.4 =
* BUGFIX: Updating `$provide_additional_details` to support ACF Field definition for `true/false` field in `lib/fns/shortcode/donationform/screening-questions.php`.

= 4.4.4.3 =
* BUGFIX: Updating `site_url()` to `home_url()` in instances where we're building URL refs from the site's Home URL.

= 4.4.4.2 =
* Updating copyright year in email footers.

= 4.4.4.1 =
* BUGFIX: Additional image path corrections in email templates.

= 4.4.4 =
* BUGFIX: Correcting image paths in email templates.

= 4.4.3 =
* BUGFIX: Hard-coding `from:support@pickupmydonation.com` for  Sender Authentication in New Account Email.

= 4.4.2 =
* BUGFIX: Checking for array and array key before checking variable.

= 4.4.1 =
* BUGFIX: Checking if variable is an array before running `array_key_exists()` in `/lib/fns/callback/05.validate-contact-details.php`.

= 4.4.0 =
* Adding `org`, `org-inactive`, and `rejected` user roles.
* Login/logout redirects for `org` users.

= 4.3.1 =
* Adding `[get_additional_options_form]` for loading Additional Organization Options form in the User Portal.

= 4.3.0 =
* Adding User Portal.

= 4.2.4 =
* Hiding "Preferred Contact Method" field from donor form, setting Preferred Contact Method to always be "Phone".

= 4.2.3 =
* Updating for Composer install compatiblity.

= 4.2.2 =
* Minor edit to "Fee-Based Pickup Service" note on "Select Your Organization" view.

= 4.2.1 =
* Updating "No Damaged Items Message" by adding `{store_signature}` as an available token and documenting available tokens for the ACF field.

= 4.2.0 =
* Updating `[donors_in_your_area]` to pull KML from [https://zipcodes.pickupmydonation.com](https://zipcodes.pickupmydonation.com).
* Deactivating "Click to Claim".

= 4.1.0 =
* Removing "Fee-Based/Priority" option from the "Select Your Organization" screen.

= 4.0.0 =
* Updating "Fee-Based" option to utilize generic verbiage during the "Select Your Organization" step.

= 3.9.4 =
* Adding "Prices start as low as..." call out to Fee-Based note on Step 4.

= 3.9.3 =
* Adding "PriorityPickup" column to CSV export in "My Donations > Donation Reports > Combined Donations" report.

= 3.9.2 =
* Adding "Fails" column to "College Hunks API Stats" widget.
* Adding "Note" to the bottom of the "College Hunks API Stats" widget explaining "Fails".

= 3.9.1.1 =
* BUGFIX: Setting required constants to `null` when not set to avoid fatal error upon setup.

= 3.9.1 =
* Updating output for `wp dm archive` to correctly show the `$donation_stats` that would be written to the database.
* Correcting examples in documentation for `wp dm archive`.

= 3.9.0 =
* Adding "Fee-Based" option on Step 4 allowing donors to choose whether or not we send their donation to fee-based providers.

= 3.8.0.1 =
* BUGFIX: Accounting for "Pick Up Days of the Week" values stored as "strings" from PMD 2.0 Org imports. Now we set the available pick up days to the default (Mon-Sat) when this happens.

= 3.8.0 =
* Now orphaned donations are sent to priority partners using the Email delivery method. Previously, orhpans would only get sent using the API method which meant that only College Hunks would receive orphaned donations.

= 3.7.5 =
* Using `usort()` to sort Pick Up Times returned by `get_pickuptimes()`.

= 3.7.4 =
* Adding "Store Relations" and "Org Page Options" to `lib/acf-json/`.
* Correctly retrieving values for Pick Up Days of the Week and Min. Scheduling Interval.
* Updating holiday/restricted pick up dates for the Step 4 screen.

= 3.7.3 =
* Adding option to use the Transportation Department's name when displaying to users in the "Select Your Organization" list. This allows us to use one parent Organization for multiple Transportation Departments.
* `send_email()` now always returns before sending the `trans_dept_notification` if the `routing_method` is not `email`.
* Setting defaults for Transportation Contact details in the `donor_confirmation` email.
* Removing dependency on `get_submit_button()` WP helper function as this function can only be used in an admin context ( see [get_submit_button user contributed notes](https://developer.wordpress.org/reference/functions/get_submit_button/#comment-3641)). Was throwing an error when calling via the WP REST API.

= 3.7.2.2 =
* BUGFIX: Correcting variable name to `$is_chhj_pickupcode` in `send_api_post()`.

= 3.7.2.1 =
* Adding `wp dm test` for `is_valid_pickupcode()`.

= 3.7.2 =
* Updatin `send_api_post()` to only post to an external API if the pickup code is valid for the organization.
* Adding `is_valid_pickupcode()` for determining if a pickup code is valid given a search string to compare against the returned organizations for the pickup code.
* Updating "API Response" column to list available organizations for a given pickup code when no `api_post` meta field value exists.

= 3.7.1 =
* Adding `ksort()` to stats displayed by `chhj_stats_dashboard_widget()`.
* Adding "Success Rate" column to `chhj_stats_dashboard_widget()`.

= 3.7.0.3 =
* BUGFIX: Adjusting switch statement in `lib/fns/apirouting.php` to route donations to the CHHJ API when `$routing_method` is also equal to `chhj_api`. This is in addition to accepting `api-chhj`. This fixes the issue where although the `trans_dept_notification` switch in `lib/fns/emails.php` was calling `send_api_post()` if `if( 'email' != $donor['routing_method'] )`, the actual `send_api_post()` function did not have a switch statement to handle the PMD 3.0 `$routing_method` value of `chhj_api` as defined in the ACF Field under each organization's "Pickup Settings".

= 3.7.0.2 =
* Allowing HTML in "Customer Description" field inside `email.donation-receipt.hbs`.

= 3.7.0.1 =
* Accepting `api-chhj` in addition to `chhj_api` as valid matching conditions for "Routing Method" column for Organization admin listing.

= 3.7.0 =
* Adding "Routing Method" column to Organization admin listing.

= 3.6.6 =
* Adding "College Hunks API Stats" dashboard widget.

= 3.6.5.4 =
* Updating "Click to Claim" link text in compiled file.

= 3.6.5.3 =
* Updating link in "Click To Claim" emails from "Click To Claim This Donation" to "View This Donation".

= 3.6.5.2 =
* BUGFIX: Correctly spliting multiple organization emails when sending monthly reports.

= 3.6.5.1 =
* Adding post_type=page as additional display param for City Pages ACF Field Group.

= 3.6.5 =
* Updating User Photo Uploads to include an ACF Option for making them required. Now, by default, User Photo Uploads are "optional".
* Removed `get_socialshare_copy()` as it is no longer in use.

= 3.6.4.3 =
* Better response code handling for API Response column in Donation CPT admin listings.

= 3.6.4.2 =
* Adding `custom_column_api_response_content()` to handle display of HTML in the API Response column in admin Donation CPT listings.

= 3.6.4.1 =
* Saving API response code and message as separate fields.
* Better handling of API response data.

= 3.6.4 =
* Restoring "Skip Pick Up Dates" functionality.

= 3.6.3.3 =
* BUGFIXES: Checking for variables before using in code.
* Adding option to turn on Debug Mode with Verbose set to ON.

= 3.6.3.2 =
* Checking for variables existence.

= 3.6.3.1 =
* BUGFIX: Checking if variable is_array() before running array_key_exists() in `04.validate-screening-questions.php`.
* Checking if array key exists in `describe-your-donation.php`.

= 3.6.3 =
* Introducing `DMDEBUG_VERBOSE` constant for "verbose mode" debugging.
* Moved several `uber_log()` called into "verbose mode" during the `[donationform]` process.

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
