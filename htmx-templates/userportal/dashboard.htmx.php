<?php
// No direct access.
defined('ABSPATH') || exit('Direct access not allowed.');
include_once 'init.php';
if (!isset($_SERVER['HTTP_X_WP_NONCE']) || !wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'], 'hxwp_nonce')) {
	hxwp_die('Insufficient permissions.');
}
?>
    <div>
        Dashboard content
    </div>
<?php
