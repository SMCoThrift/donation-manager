<?php
// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');

if(!wp_get_current_user()->has_cap('org')){
    hxwp_die('Invalid action.');
}

