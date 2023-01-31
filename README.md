# Donation Manager #
**Contributors:** [thewebist](https://profiles.wordpress.org/thewebist/)  
**Tags:** donations, CPT  
**Requires at least:** 4.5  
**Tested up to:** 5.9.3  
**Requires PHP:** 7.2  
**Stable tag:** 3.0.2  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

A complete donation intake system for WordPress.

## Description ##

Long description goes here...

## Changelog ##

### 3.0.2 ###
* Updating `lib/fns/admin.php::custom_save_post()` to properly obtain the `$org_id`.

### 3.0.1 ###
* Adding `pickup_code` taxonomy to `lib/cpt/`.
* Adding Github URL to README.

### 3.0.0 ###
* Complete rewrite of PMD 2.0.
* Setting up CPTs and Taxonomies inside `lib/cpt/`.
* Storing ACF definitions in `lib/acf-json/`.
* Dismissable admin notifications.
