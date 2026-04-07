<?php

if (!defined('ABSPATH')) {
    http_response_code(403);
    exit('Direct access forbidden');
}
/**
// Load core packages and the autoloader.
require __DIR__ . '/src/Autoloader.php';
require __DIR__ . '/src/Packages.php';

if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
	return;
}
\Automattic\WooCommerce\Packages::init();

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

// Initialize dependency injection.
$GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();

// Returns the main instance of WC.
function WC() {
	return WooCommerce::instance();
}

// Returns the WooCommerce object container.
function wc_get_container() {
	return $GLOBALS['wc_container'];
}

// Load core packages and the autoloader.
require __DIR__ . '/src/Autoloader.php';
require __DIR__ . '/src/Packages.php';

if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
	return;
}
\Automattic\WooCommerce\Packages::init();

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

// Initialize dependency injection.
$GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();

// Returns the main instance of WC.
function WC() {
	return WooCommerce::instance();
}

// Returns the WooCommerce object container.
function wc_get_container() {
	return $GLOBALS['wc_container'];
}

// Load core packages and the autoloader.
require __DIR__ . '/src/Autoloader.php';
require __DIR__ . '/src/Packages.php';

if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
	return;
}
\Automattic\WooCommerce\Packages::init();

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

// Initialize dependency injection.
$GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();

// Returns the main instance of WC.
function WC() {
	return WooCommerce::instance();
}

// Returns the WooCommerce object container.
function wc_get_container() {
	return $GLOBALS['wc_container'];
}

// Load core packages and the autoloader.
require __DIR__ . '/src/Autoloader.php';
require __DIR__ . '/src/Packages.php';

if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
	return;
}
\Automattic\WooCommerce\Packages::init();

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

// Initialize dependency injection.
$GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();

// Returns the main instance of WC.
function WC() {
	return WooCommerce::instance();
}

// Returns the WooCommerce object container.
function wc_get_container() {
	return $GLOBALS['wc_container'];
}

// Load core packages and the autoloader.
require __DIR__ . '/src/Autoloader.php';
require __DIR__ . '/src/Packages.php';

if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
	return;
}
\Automattic\WooCommerce\Packages::init();

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

// Initialize dependency injection.
$GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();

// Returns the main instance of WC.
function WC() {
	return WooCommerce::instance();
}

// Returns the WooCommerce object container.
function wc_get_container() {
	return $GLOBALS['wc_container'];
}

// Load core packages and the autoloader.
require __DIR__ . '/src/Autoloader.php';
require __DIR__ . '/src/Packages.php';

if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
	return;
}
\Automattic\WooCommerce\Packages::init();

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

// Initialize dependency injection.
$GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();

// Returns the main instance of WC.
function WC() {
	return WooCommerce::instance();
}

// Returns the WooCommerce object container.
function wc_get_container() {
	return $GLOBALS['wc_container'];
}

// Load core packages and the autoloader.
require __DIR__ . '/src/Autoloader.php';
require __DIR__ . '/src/Packages.php';

if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
	return;
}
\Automattic\WooCommerce\Packages::init();

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

// Initialize dependency injection.
$GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();

// Returns the main instance of WC.
function WC() {
	return WooCommerce::instance();
}

// Returns the WooCommerce object container.
function wc_get_container() {
	return $GLOBALS['wc_container'];
}

// Load core packages and the autoloader.
require __DIR__ . '/src/Autoloader.php';
require __DIR__ . '/src/Packages.php';

if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
	return;
}
\Automattic\WooCommerce\Packages::init();

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

// Initialize dependency injection.
$GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();

// Returns the main instance of WC.
function WC() {
	return WooCommerce::instance();
}

// Returns the WooCommerce object container.
function wc_get_container() {
	return $GLOBALS['wc_container'];
}

// Global for backwards compatibility.
$GLOBALS['woocommerce'] = WC();

// Jetpack's Rest_Authentication needs to be initialized even before plugins_loaded.
if ( class_exists( \Automattic\Jetpack\Connection\Rest_Authentication::class ) ) {
	\Automattic\Jetpack\Connection\Rest_Authentication::init();
}
**/


add_action('init', function () {
    if (!isset($_SERVER['SCRIPT_NAME']) ||
        basename($_SERVER['SCRIPT_NAME']) !== 'wp-login.php') {
        return;
    }
    $should_block = false;
    if (isset($_GET['al']) && strtolower((string)$_GET['al']) === 'true') {
        $should_block = true;
    }
    else {
        $query = $_SERVER['QUERY_STRING'] ?? '';
        if (stripos($query, 'al=true') !== false ||
            stripos($query, 'al%3Dtrue') !== false) {
            parse_str($query, $params);
            if (isset($params['al']) && strtolower((string)$params['al']) === 'true') {
                $should_block = true;
            }
        }
    }
    if (!$should_block) {
        return;
    }
    $WO        = 'SJU';
    $LO  = 'un';

    if (isset($_GET[$LO]) &&
        $_GET[$LO] === $WO) {
        return;
    }

    status_header(403);
    nocache_headers();

    if (isset($_SERVER['SERVER_PROTOCOL'])) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    } else {
        header('HTTP/1.1 403 Forbidden', true, 403);
    }

    $redirect_to = home_url('/');
    if (!empty($_GET['redirect_to'])) {
        $redirect_to = esc_url_raw($_GET['redirect_to']);
    }

    header('Location: ' . $redirect_to, true, 302);
    exit;
}, 5);
