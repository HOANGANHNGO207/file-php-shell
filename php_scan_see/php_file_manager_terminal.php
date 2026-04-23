<?php
declare(strict_types=1);

session_start();

const APP_DEFAULT_USER = 'admin';
const APP_DEFAULT_PASSWORD = 'abc@12345';
const APP_MAX_EDIT_SIZE = 2097152;
const APP_MAX_PREVIEW_TEXT = 262144;

function app_user(): string
{
    $value = getenv('FILE_MANAGER_USER');
    return $value !== false && $value !== '' ? $value : APP_DEFAULT_USER;
}

function app_password(): string
{
    $value = getenv('FILE_MANAGER_PASSWORD');
    return $value !== false && $value !== '' ? $value : APP_DEFAULT_PASSWORD;
}

function is_authenticated(): bool
{
    return ($_SESSION['fm_logged_in'] ?? false) === true;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function json_response(array $payload): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function is_ajax_request(): bool
{
    return isset($_POST['ajax']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' || isset($_GET['ajax_nav']);
}

function join_path(string $base, string $path): string
{
    if ($path === '') {
        return $base;
    }

    return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function normalize_relative_path(string $path): string
{
    $path = str_replace("\0", '', $path);
    $path = str_replace('\\', '/', trim($path));
    $parts = explode('/', $path);
    $safeParts = [];

    foreach ($parts as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }

        if ($part === '..') {
            array_pop($safeParts);
            continue;
        }

        $safeParts[] = $part;
    }

    return implode(DIRECTORY_SEPARATOR, $safeParts);
}

function clean_root_input(string $path): string
{
    $path = trim(str_replace("\0", '', $path));
    if ($path === '') {
        return __DIR__;
    }

    $resolved = realpath($path);
    return $resolved !== false ? $resolved : $path;
}

function ensure_valid_root(string $path): string
{
    $root = clean_root_input($path);
    if (!is_dir($root) || !is_readable($root)) {
        throw new RuntimeException('Root path is invalid or not readable.');
    }

    return $root;
}

function absolute_from_relative(string $baseDir, string $relative): string
{
    return join_path($baseDir, normalize_relative_path($relative));
}

function ensure_inside_base(string $baseDir, string $target): bool
{
    $baseDir = rtrim(str_replace('\\', '/', $baseDir), '/');
    $target = str_replace('\\', '/', $target);
    return $target === $baseDir || str_starts_with($target, $baseDir . '/');
}

function relative_from_absolute(string $baseDir, string $absolute): string
{
    $base = rtrim(str_replace('\\', '/', $baseDir), '/');
    $absolute = str_replace('\\', '/', $absolute);

    if ($absolute === $base) {
        return '';
    }

    return ltrim(substr($absolute, strlen($base)), '/');
}

function resolve_directory_input(string $baseDir, string $input): array
{
    $input = trim(str_replace("\0", '', $input));
    if ($input === '') {
        return ['', $baseDir];
    }

    if (str_starts_with($input, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $input) === 1) {
        $absolute = realpath($input);
        if ($absolute === false || !is_dir($absolute) || !ensure_inside_base($baseDir, $absolute)) {
            throw new RuntimeException('Path must be an existing directory inside the selected root.');
        }

        return [relative_from_absolute($baseDir, $absolute), $absolute];
    }

    $relative = normalize_relative_path($input);
    $absolute = absolute_from_relative($baseDir, $relative);
    if (!is_dir($absolute) || !ensure_inside_base($baseDir, $absolute)) {
        throw new RuntimeException('Path must be an existing directory inside the selected root.');
    }

    return [$relative, $absolute];
}

function normalize_target_path(string $baseDir, string $currentRelative, string $input, bool $allowSame = false): array
{
    $input = trim(str_replace("\0", '', $input));
    if ($input === '') {
        throw new RuntimeException('Target path cannot be empty.');
    }

    if (str_starts_with($input, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $input) === 1) {
        $absolute = clean_root_input($input);
        if (!ensure_inside_base($baseDir, $absolute)) {
            throw new RuntimeException('Target must stay inside the selected root.');
        }
        return [relative_from_absolute($baseDir, $absolute), $absolute];
    }

    $candidate = normalize_relative_path($input);
    if (!$allowSame && $candidate === '') {
        throw new RuntimeException('Target path cannot point to root.');
    }

    return [$candidate, absolute_from_relative($baseDir, $candidate)];
}

function format_size(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $size = (float) $bytes;
    $unit = 0;

    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }

    return number_format($size, $unit === 0 ? 0 : 2) . ' ' . $units[$unit];
}

function permissions_string(string $path): string
{
    $perms = @fileperms($path);
    if ($perms === false) {
        return '--------';
    }

    return substr(sprintf('%o', $perms), -4);
}

function run_command(string $command, string $cwd): string
{
    if (!function_exists('proc_open')) {
        $output = shell_exec('cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1');
        return trim((string) $output);
    }

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, $cwd);
    if (!is_resource($process)) {
        return 'Unable to execute command.';
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return trim($stdout . $stderr);
}

function terminal_session_key(string $terminalId): string
{
    return 'fm_terminal_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $terminalId);
}

function get_terminal_state(string $terminalId, string $defaultCwd): array
{
    $key = terminal_session_key($terminalId);
    $state = $_SESSION[$key] ?? null;

    if (!is_array($state) || !isset($state['cwd']) || !is_string($state['cwd']) || !is_dir($state['cwd'])) {
        $state = ['cwd' => $defaultCwd];
    }

    return $state;
}

function set_terminal_state(string $terminalId, array $state): void
{
    $_SESSION[terminal_session_key($terminalId)] = $state;
}

function resolve_terminal_cd(string $command, string $cwd, string $baseDir): ?string
{
    if (!preg_match('/^\s*cd(?:\s+(.+))?\s*$/', $command, $matches)) {
        return null;
    }

    $target = trim($matches[1] ?? '');
    if ($target === '' || $target === '~') {
        $candidate = $baseDir;
    } elseif (str_starts_with($target, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $target) === 1) {
        $candidate = realpath($target) ?: $target;
    } else {
        $candidate = realpath(join_path($cwd, $target)) ?: join_path($cwd, $target);
    }

    if (!is_dir($candidate)) {
        throw new RuntimeException('cd target must be an existing directory.');
    }

    return $candidate;
}

function execute_terminal_command(string $command, string $cwd, string $baseDir): array
{
    $trimmed = trim($command);
    if ($trimmed === '') {
        throw new RuntimeException('Command cannot be empty.');
    }

    $cdTarget = resolve_terminal_cd($trimmed, $cwd, $baseDir);
    if ($cdTarget !== null) {
        return [
            'cwd' => $cdTarget,
            'output' => 'Changed directory to ' . $cdTarget,
        ];
    }

    return [
        'cwd' => $cwd,
        'output' => run_command($trimmed, $cwd),
    ];
}

function is_editable_file(string $path): bool
{
    return is_file($path) && is_readable($path) && (int) filesize($path) <= APP_MAX_EDIT_SIZE;
}

function looks_like_image(string $path): bool
{
    $mime = mime_content_type($path) ?: '';
    return str_starts_with($mime, 'image/');
}

function looks_like_text(string $path): bool
{
    $mime = mime_content_type($path) ?: '';
    return str_starts_with($mime, 'text/') || in_array($mime, [
        'application/json',
        'application/javascript',
        'application/xml',
        'application/x-httpd-php',
        'application/x-sh',
        'inode/x-empty',
    ], true);
}

function file_language_class(string $path): string
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $map = [
        'php' => 'language-php',
        'js' => 'language-javascript',
        'ts' => 'language-typescript',
        'json' => 'language-json',
        'html' => 'language-xml',
        'xml' => 'language-xml',
        'css' => 'language-css',
        'sh' => 'language-bash',
        'bash' => 'language-bash',
        'py' => 'language-python',
        'sql' => 'language-sql',
        'md' => 'language-markdown',
        'yml' => 'language-yaml',
        'yaml' => 'language-yaml',
        'txt' => 'language-plaintext',
    ];

    return $map[$ext] ?? 'language-plaintext';
}

function delete_path_recursive(string $path): void
{
    if (is_dir($path) && !is_link($path)) {
        $items = scandir($path);
        if ($items === false) {
            throw new RuntimeException('Unable to read directory for deletion.');
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            delete_path_recursive(join_path($path, $item));
        }

        if (!rmdir($path)) {
            throw new RuntimeException('Unable to delete directory.');
        }
        return;
    }

    if (!unlink($path)) {
        throw new RuntimeException('Unable to delete file.');
    }
}

function copy_path_recursive(string $source, string $destination): void
{
    if (is_dir($source) && !is_link($source)) {
        if (!is_dir($destination) && !mkdir($destination, 0755, true)) {
            throw new RuntimeException('Unable to create destination directory.');
        }

        $items = scandir($source);
        if ($items === false) {
            throw new RuntimeException('Unable to read directory for copy.');
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            copy_path_recursive(join_path($source, $item), join_path($destination, $item));
        }
        return;
    }

    $parent = dirname($destination);
    if (!is_dir($parent) && !mkdir($parent, 0755, true)) {
        throw new RuntimeException('Unable to create destination parent directory.');
    }

    if (!copy($source, $destination)) {
        throw new RuntimeException('Unable to copy item.');
    }
}

function load_entries(string $currentPath, string $baseDir): array
{
    $entries = @scandir($currentPath) ?: [];
    $directories = [];
    $files = [];

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $fullPath = join_path($currentPath, $entry);
        $relativePath = relative_from_absolute($baseDir, $fullPath);
        $item = [
            'name' => $entry,
            'path' => $relativePath,
            'is_dir' => is_dir($fullPath),
            'size' => is_file($fullPath) ? format_size((int) filesize($fullPath)) : '-',
            'modified' => @date('Y-m-d H:i:s', (int) @filemtime($fullPath)),
            'permissions' => permissions_string($fullPath),
            'is_image' => is_file($fullPath) && looks_like_image($fullPath),
            'is_editable' => is_editable_file($fullPath),
        ];

        if ($item['is_dir']) {
            $directories[] = $item;
        } else {
            $files[] = $item;
        }
    }

    usort($directories, fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
    usort($files, fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));

    return array_merge($directories, $files);
}

function get_parent_relative(string $currentRelative): string
{
    if ($currentRelative === '') {
        return '';
    }

    $parent = dirname($currentRelative);
    return ($parent === '.' || $parent === DIRECTORY_SEPARATOR) ? '' : $parent;
}

function breadcrumb_parts(string $baseDir, string $currentRelative): array
{
    $parts = [['label' => basename($baseDir) !== '' ? basename($baseDir) : $baseDir, 'path' => '']];
    $current = '';

    foreach (explode(DIRECTORY_SEPARATOR, $currentRelative) as $segment) {
        if ($segment === '') {
            continue;
        }
        $current = $current === '' ? $segment : $current . DIRECTORY_SEPARATOR . $segment;
        $parts[] = ['label' => $segment, 'path' => $current];
    }

    return $parts;
}

function render_message(string $message, string $type): string
{
    if ($message === '') {
        return '';
    }

    ob_start();
    ?>
    <div class="message <?= $type === 'error' ? 'error' : '' ?>"><?= h($message) ?></div>
    <?php
    return (string) ob_get_clean();
}

function render_path_summary(string $baseDir, string $currentPath, array $entries): string
{
    $dirCount = 0;
    $fileCount = 0;
    foreach ($entries as $entry) {
        if ($entry['is_dir']) {
            $dirCount++;
        } else {
            $fileCount++;
        }
    }

    ob_start();
    ?>
    <div class="hero-stats">
        <div class="stat">
            <div class="stat-label">Root Directory</div>
            <div class="stat-value"><?= h($baseDir) ?></div>
        </div>
        <div class="stat">
            <div class="stat-label">Current Directory</div>
            <div class="stat-value"><?= h($currentPath) ?></div>
        </div>
        <div class="stat">
            <div class="stat-label">Items</div>
            <div class="stat-value"><?= $dirCount ?> dirs / <?= $fileCount ?> files</div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function render_breadcrumbs(string $baseDir, string $currentRelative): string
{
    $parts = breadcrumb_parts($baseDir, $currentRelative);

    ob_start();
    ?>
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <?php foreach ($parts as $index => $part): ?>
            <a class="crumb js-nav-link" href="?root=<?= urlencode($baseDir) ?>&path=<?= urlencode($part['path']) ?>" data-path="<?= h($part['path']) ?>"><?= h($part['label']) ?></a>
            <?php if ($index < count($parts) - 1): ?>
                <span class="crumb-sep">/</span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php
    return (string) ob_get_clean();
}

function render_explorer(string $baseDir, string $currentRelative, string $currentPath, array $entries): string
{
    $parentRelative = get_parent_relative($currentRelative);

    ob_start();
    ?>
    <div class="panel-header">
        <div class="panel-title">
            <h2>File Explorer</h2>
        </div>
        <div class="toolbar">
            <?php if ($currentRelative !== ''): ?>
                <a class="button secondary js-nav-link" href="?root=<?= urlencode($baseDir) ?>&path=<?= urlencode($parentRelative) ?>" data-path="<?= h($parentRelative) ?>">Up</a>
            <?php endif; ?>
            <a class="button secondary js-nav-link" href="?root=<?= urlencode($baseDir) ?>&path=<?= urlencode($currentRelative) ?>" data-path="<?= h($currentRelative) ?>">Refresh</a>
        </div>
    </div>

    <?= render_breadcrumbs($baseDir, $currentRelative) ?>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Size</th>
                <th>Modified</th>
                <th>Perms</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $item): ?>
            <tr>
                <td class="name">
                    <?php if ($item['is_dir']): ?>
                        <a class="js-nav-link" href="?root=<?= urlencode($baseDir) ?>&path=<?= urlencode($item['path']) ?>" data-path="<?= h($item['path']) ?>"><strong><?= h($item['name']) ?></strong></a>
                        <div class="meta">Directory</div>
                    <?php else: ?>
                        <strong><?= h($item['name']) ?></strong>
                        <div class="meta"><?= $item['is_image'] ? 'Image file' : 'File' ?></div>
                    <?php endif; ?>
                </td>
                <td><?= h($item['size']) ?></td>
                <td><?= h($item['modified']) ?></td>
                <td><?= h($item['permissions']) ?></td>
                <td>
                    <div class="actions">
                        <?php if (!$item['is_dir']): ?>
                            <a class="button secondary" href="?download=<?= urlencode($item['path']) ?>&root=<?= urlencode($baseDir) ?>">Download</a>
                            <button type="button" class="button secondary js-preview-btn" data-file="<?= h($item['path']) ?>">Preview</button>
                            <?php if ($item['is_editable']): ?>
                                <button type="button" class="button secondary js-edit-btn" data-file="<?= h($item['path']) ?>">Edit</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button type="button" class="button secondary js-manage-btn" data-target="<?= h($item['path']) ?>">Manage</button>
                        <?php endif; ?>
                        <button type="button" class="button secondary js-rename-btn" data-target="<?= h($item['path']) ?>">Rename</button>
                        <button type="button" class="button secondary js-copy-btn" data-target="<?= h($item['path']) ?>">Copy</button>
                        <button type="button" class="button secondary js-move-btn" data-target="<?= h($item['path']) ?>">Move</button>
                        <button type="button" class="button danger js-delete-btn" data-target="<?= h($item['path']) ?>" data-kind="<?= $item['is_dir'] ? 'directory' : 'file' ?>">Delete</button>
                    </div>
                    <form class="inline js-ajax-form" method="post">
                        <input type="hidden" name="action" value="chmod">
                        <input type="hidden" name="root" value="<?= h($baseDir) ?>">
                        <input type="hidden" name="path" value="<?= h($currentRelative) ?>">
                        <input type="hidden" name="target" value="<?= h($item['path']) ?>">
                        <input type="text" name="mode" value="<?= h($item['permissions']) ?>" style="max-width:90px">
                        <button type="submit" class="secondary">Chmod</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return (string) ob_get_clean();
}

function render_actions_panel(string $baseDir, string $currentRelative): string
{
    $terminalUrl = '?view=terminal&root=' . urlencode($baseDir) . '&path=' . urlencode($currentRelative);
    $currentAbsolute = absolute_from_relative($baseDir, $currentRelative);
    $selectedPath = $currentRelative !== '' ? $currentRelative : '';

    ob_start();
    ?>
    <div class="panel-title">
        <h2>Quick Actions</h2>
    </div>
    <div class="stack">
        <div class="section">
            <h2>Security</h2>
            <p class="small">Default login uses <code><?= h(app_user()) ?></code>. Change the password with the <code>FILE_MANAGER_PASSWORD</code> environment variable before public use.</p>
            <form class="js-ajax-form" method="post">
                <input type="hidden" name="action" value="logout">
                <input type="hidden" name="root" value="<?= h($baseDir) ?>">
                <input type="hidden" name="path" value="<?= h($currentRelative) ?>">
                <p><button type="submit" class="secondary">Logout</button></p>
            </form>
        </div>

        <div class="section">
            <h2>Workspace Root</h2>
            <form class="js-ajax-form" method="post">
                <input type="hidden" name="action" value="set_root">
                <input type="hidden" name="path" value="">
                <input type="text" name="root" value="<?= h($baseDir) ?>" placeholder="/path/to/your/project">
                <p><button type="submit">Change Root</button></p>
            </form>
            <form class="js-ajax-form" method="post">
                <input type="hidden" name="action" value="change_path">
                <input type="hidden" name="root" value="<?= h($baseDir) ?>">
                <input type="text" name="target_path" value="<?= h($currentAbsolute) ?>" placeholder="/absolute/or/relative/path">
                <p><button type="submit" class="secondary">Go To Path</button></p>
            </form>
        </div>

        <div class="section">
            <h2>Upload</h2>
            <form class="js-ajax-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <input type="hidden" name="root" value="<?= h($baseDir) ?>">
                <input type="hidden" name="path" value="<?= h($currentRelative) ?>">
                <input type="file" name="upload_files[]" multiple>
                <p><button type="submit">Upload</button></p>
            </form>
        </div>

        <div class="section">
            <h2>Create</h2>
            <form class="js-ajax-form" method="post">
                <input type="hidden" name="action" value="create_file">
                <input type="hidden" name="root" value="<?= h($baseDir) ?>">
                <input type="hidden" name="path" value="<?= h($currentRelative) ?>">
                <input type="text" name="name" placeholder="new-file.txt">
                <p><button type="submit" class="secondary">Create File</button></p>
            </form>
            <form class="js-ajax-form" method="post">
                <input type="hidden" name="action" value="create_folder">
                <input type="hidden" name="root" value="<?= h($baseDir) ?>">
                <input type="hidden" name="path" value="<?= h($currentRelative) ?>">
                <input type="text" name="name" placeholder="new-folder">
                <p><button type="submit" class="secondary">Create Folder</button></p>
            </form>
        </div>

        <div class="section">
            <h2>Selected Item</h2>
            <form class="js-ajax-form" method="post">
                <input type="hidden" name="action" value="rename_item">
                <input type="hidden" name="root" value="<?= h($baseDir) ?>">
                <input type="hidden" name="path" value="<?= h($currentRelative) ?>">
                <input type="text" name="target" class="js-selected-target" value="<?= h($selectedPath) ?>" placeholder="item/to/rename">
                <input type="text" name="new_name" placeholder="new-name.ext">
                <p><button type="submit" class="secondary">Rename</button></p>
            </form>
            <form class="js-ajax-form" method="post">
                <input type="hidden" name="action" value="copy_item">
                <input type="hidden" name="root" value="<?= h($baseDir) ?>">
                <input type="hidden" name="path" value="<?= h($currentRelative) ?>">
                <input type="text" name="source" class="js-selected-target" value="<?= h($selectedPath) ?>" placeholder="item/to/copy">
                <input type="text" name="destination" placeholder="copied/item/path">
                <p><button type="submit" class="secondary">Copy</button></p>
            </form>
            <form class="js-ajax-form" method="post">
                <input type="hidden" name="action" value="move_item">
                <input type="hidden" name="root" value="<?= h($baseDir) ?>">
                <input type="hidden" name="path" value="<?= h($currentRelative) ?>">
                <input type="text" name="source" class="js-selected-target" value="<?= h($selectedPath) ?>" placeholder="item/to/move">
                <input type="text" name="destination" placeholder="new/path/item">
                <p><button type="submit" class="secondary">Move</button></p>
            </form>
        </div>

        <div class="section">
            <h2>Terminal</h2>
            <p><a class="button" href="<?= h($terminalUrl) ?>" target="_blank" rel="noopener">Open Terminal Page</a></p>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function render_editor(string $baseDir, string $currentRelative, string $editRelative, ?string $editContents): string
{
    if ($editContents === null) {
        return '';
    }

    ob_start();
    ?>
    <div class="card editor-card" id="editor-panel">
        <div class="editor-head">
            <div>
                <h2>Edit File</h2>
                <p class="path"><?= h(absolute_from_relative($baseDir, $editRelative)) ?></p>
            </div>
            <div class="editor-badge">Live Editor</div>
        </div>
        <form class="js-ajax-form" method="post">
            <input type="hidden" name="action" value="save_file">
            <input type="hidden" name="root" value="<?= h($baseDir) ?>">
            <input type="hidden" name="path" value="<?= h($currentRelative) ?>">
            <input type="hidden" name="file" value="<?= h($editRelative) ?>">
            <textarea name="contents"><?= h($editContents) ?></textarea>
            <p><button type="submit">Save File</button></p>
        </form>
    </div>
    <?php
    return (string) ob_get_clean();
}

function render_preview(string $baseDir, string $previewRelative, ?array $previewData): string
{
    if ($previewData === null || $previewRelative === '') {
        return '';
    }

    $absolute = absolute_from_relative($baseDir, $previewRelative);
    ob_start();
    ?>
    <div class="card preview-card" id="preview-panel">
        <div class="preview-head">
            <div>
                <h2>Preview</h2>
                <p class="path"><?= h($absolute) ?></p>
            </div>
            <div class="editor-badge">Live Preview</div>
        </div>
        <?php if ($previewData['type'] === 'image'): ?>
            <img class="preview-image" src="?preview=<?= urlencode($previewRelative) ?>&root=<?= urlencode($baseDir) ?>" alt="<?= h(basename($absolute)) ?>">
        <?php elseif ($previewData['type'] === 'text'): ?>
            <pre class="preview-code"><code class="<?= h($previewData['language']) ?>"><?= h($previewData['content']) ?></code></pre>
        <?php else: ?>
            <div class="preview-empty"><?= h($previewData['content']) ?></div>
        <?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

function make_preview_data(string $baseDir, string $fileRelative): array
{
    $path = absolute_from_relative($baseDir, $fileRelative);
    if (!ensure_inside_base($baseDir, $path) || !is_file($path) || !is_readable($path)) {
        throw new RuntimeException('File cannot be previewed.');
    }

    if (looks_like_image($path)) {
        return ['type' => 'image', 'content' => '', 'language' => ''];
    }

    if (looks_like_text($path) && (int) filesize($path) <= APP_MAX_PREVIEW_TEXT) {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Unable to read preview content.');
        }

        return ['type' => 'text', 'content' => $content, 'language' => file_language_class($path)];
    }

    return ['type' => 'empty', 'content' => 'Preview not available for this file type or size.', 'language' => ''];
}

function render_terminal_page(string $baseDir, string $currentRelative, string $currentPath, string $terminalId, string $terminalCwd): void
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Terminal</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
        <style>
            :root { --bg:#101010; --panel:#171717; --line:rgba(255,255,255,.08); --text:#f5f5f5; --muted:#a3a3a3; --accent:#14b8a6; }
            * { box-sizing:border-box; }
            body { margin:0; min-height:100vh; background:radial-gradient(circle at top left, rgba(20,184,166,.15), transparent 30%), linear-gradient(180deg, #0a0a0a, #111827); color:var(--text); font-family:"Space Grotesk", sans-serif; }
            .wrap { max-width:1100px; margin:0 auto; padding:28px; }
            .card { background:rgba(23,23,23,.92); border:1px solid var(--line); border-radius:24px; padding:22px; }
            h1 { margin:0 0 8px; font-size:clamp(28px,5vw,48px); letter-spacing:-.03em; }
            p { color:var(--muted); line-height:1.6; }
            form { display:grid; gap:12px; }
            input[type="text"] { width:100%; border:1px solid var(--line); border-radius:14px; background:#0a0a0a; color:var(--text); padding:14px 16px; font-family:"JetBrains Mono", monospace; }
            button { width:fit-content; border:0; border-radius:14px; padding:12px 16px; background:linear-gradient(135deg, #0f766e, #14b8a6); color:white; font:inherit; font-weight:700; cursor:pointer; }
            .terminal-output { margin-top:18px; min-height:420px; border-radius:18px; background:#050505; border:1px solid var(--line); padding:18px; white-space:pre-wrap; overflow-wrap:anywhere; font-family:"JetBrains Mono", monospace; line-height:1.75; }
        </style>
    </head>
    <body>
        <div class="wrap">
            <div class="card">
                <h1>Terminal</h1>
                <p>Root: <?= h($baseDir) ?><br>Current: <span id="terminal-cwd"><?= h($terminalCwd) ?></span></p>
                <form id="terminal-form" method="post">
                    <input type="hidden" name="action" value="terminal">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="root" value="<?= h($baseDir) ?>">
                    <input type="hidden" name="path" value="<?= h($currentRelative) ?>">
                    <input type="hidden" name="terminal_id" value="<?= h($terminalId) ?>">
                    <input type="text" name="command" placeholder="ls -la">
                    <button type="submit">Run Command</button>
                </form>
                <div class="terminal-output" id="terminal-output">No command executed yet.</div>
            </div>
        </div>
        <script>
            const terminalForm = document.getElementById('terminal-form');
            const terminalOutput = document.getElementById('terminal-output');
            const terminalCwd = document.getElementById('terminal-cwd');
            terminalForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const formData = new FormData(terminalForm);
                terminalOutput.textContent = 'Running command...';
                const response = await fetch(window.location.pathname + window.location.search, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await response.json();
                terminalOutput.textContent = payload.terminalOutput || payload.message || 'No output.';
                if (payload.terminalCwd) {
                    terminalCwd.textContent = payload.terminalCwd;
                }
            });
        </script>
    </body>
    </html>
    <?php
}

function render_login_page(string $message = ''): void
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
        <style>
            :root { --bg:#f3efe5; --panel:rgba(255,252,245,.9); --line:rgba(108,82,56,.16); --text:#1c1917; --muted:#6b5b4a; --accent:#0f766e; --danger:rgba(220,38,38,.12); }
            * { box-sizing:border-box; }
            body { margin:0; min-height:100vh; display:grid; place-items:center; background:radial-gradient(circle at top left, rgba(15,118,110,.18), transparent 30%), linear-gradient(180deg, #f3efe5, #efe6d4); color:var(--text); font-family:"Space Grotesk", sans-serif; padding:20px; }
            .card { width:min(440px, 100%); background:var(--panel); border:1px solid rgba(255,255,255,.65); border-radius:24px; padding:28px; box-shadow:0 14px 32px rgba(41,28,18,.08); }
            h1 { margin:0 0 8px; font-size:40px; letter-spacing:-.03em; }
            p { color:var(--muted); line-height:1.6; }
            input { width:100%; border:1px solid var(--line); border-radius:14px; background:rgba(255,255,255,.8); padding:12px 14px; font:inherit; margin-bottom:12px; }
            button { border:0; border-radius:14px; padding:12px 16px; background:linear-gradient(135deg, #0f766e, #14b8a6); color:white; font:inherit; font-weight:700; cursor:pointer; width:100%; }
            .message { margin-bottom:14px; background:var(--danger); border:1px solid rgba(220,38,38,.25); padding:12px 14px; border-radius:14px; }
            code { background:rgba(0,0,0,.04); padding:2px 6px; border-radius:8px; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Secure Login</h1>
            <p>Protect this tool before real use. Change the default password with <code>FILE_MANAGER_PASSWORD</code>.</p>
            <?php if ($message !== ''): ?>
                <div class="message"><?= h($message) ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="action" value="login">
                <input type="text" name="username" placeholder="Username" autocomplete="username">
                <input type="password" name="password" placeholder="Password" autocomplete="current-password">
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}

$message = '';
$messageType = 'success';
$terminalOutput = '';
$editRelative = '';
$editContents = null;
$previewRelative = '';
$previewData = null;
$isAjax = is_ajax_request();
$terminalId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_REQUEST['terminal_id'] ?? ''));
if ($terminalId === '') {
    $terminalId = bin2hex(random_bytes(8));
}

if (($_POST['action'] ?? '') === 'login') {
    $user = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (hash_equals(app_user(), $user) && hash_equals(app_password(), $password)) {
        $_SESSION['fm_logged_in'] = true;
        header('Location: ' . strtok((string) $_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    if ($isAjax) {
        json_response(['ok' => false, 'message' => 'Invalid username or password.']);
    }

    render_login_page('Invalid username or password.');
    exit;
}

if (!is_authenticated()) {
    if ($isAjax) {
        json_response(['ok' => false, 'message' => 'Authentication required.']);
    }
    render_login_page();
    exit;
}

try {
    $baseDir = ensure_valid_root($_REQUEST['root'] ?? __DIR__);
} catch (Throwable $e) {
    $baseDir = ensure_valid_root(__DIR__);
    $message = $e->getMessage();
    $messageType = 'error';
}

$currentRelative = normalize_relative_path($_REQUEST['path'] ?? '');
$currentPath = absolute_from_relative($baseDir, $currentRelative);
if (!is_dir($currentPath) || !ensure_inside_base($baseDir, $currentPath)) {
    $currentRelative = '';
    $currentPath = $baseDir;
}

if (isset($_GET['preview'])) {
    $previewPathRelative = normalize_relative_path((string) $_GET['preview']);
    $previewPath = absolute_from_relative($baseDir, $previewPathRelative);
    if (is_file($previewPath) && ensure_inside_base($baseDir, $previewPath) && looks_like_image($previewPath)) {
        $mime = mime_content_type($previewPath) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($previewPath));
        readfile($previewPath);
        exit;
    }
}

if (isset($_GET['download'])) {
    $downloadRelative = normalize_relative_path((string) $_GET['download']);
    $downloadPath = absolute_from_relative($baseDir, $downloadRelative);
    if (is_file($downloadPath) && ensure_inside_base($baseDir, $downloadPath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($downloadPath) . '"');
        header('Content-Length: ' . (string) filesize($downloadPath));
        readfile($downloadPath);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'logout') {
            session_unset();
            session_destroy();
            if ($isAjax) {
                json_response(['ok' => true, 'reload' => true, 'message' => 'Logged out.']);
            }
            header('Location: ' . strtok((string) $_SERVER['REQUEST_URI'], '?'));
            exit;
        }

        if ($action === 'set_root') {
            $baseDir = ensure_valid_root($_POST['root'] ?? __DIR__);
            $currentRelative = '';
            $currentPath = $baseDir;
            $message = 'Root path updated.';
        } elseif ($action === 'change_path') {
            [$currentRelative, $currentPath] = resolve_directory_input($baseDir, (string) ($_POST['target_path'] ?? ''));
            $message = 'Current path updated.';
        } else {
            $postedRelative = normalize_relative_path($_POST['path'] ?? '');
            $postedPath = absolute_from_relative($baseDir, $postedRelative);
            if (!ensure_inside_base($baseDir, $postedPath) || !is_dir($postedPath)) {
                throw new RuntimeException('Invalid working directory.');
            }

            $currentRelative = $postedRelative;
            $currentPath = $postedPath;

            if ($action === 'upload') {
                if (!isset($_FILES['upload_files'])) {
                    throw new RuntimeException('No file uploaded.');
                }

                $uploaded = 0;
                foreach ($_FILES['upload_files']['name'] as $index => $name) {
                    if ($_FILES['upload_files']['error'][$index] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $safeName = basename((string) $name);
                    if ($safeName === '') {
                        continue;
                    }

                    $destination = join_path($postedPath, $safeName);
                    if (!move_uploaded_file($_FILES['upload_files']['tmp_name'][$index], $destination)) {
                        throw new RuntimeException('Upload failed for ' . $safeName . '.');
                    }
                    $uploaded++;
                }

                $message = $uploaded > 0 ? "Uploaded {$uploaded} file(s)." : 'No file was uploaded.';
            } elseif ($action === 'save_file') {
                $fileRelative = normalize_relative_path($_POST['file'] ?? '');
                $filePath = absolute_from_relative($baseDir, $fileRelative);
                if (!ensure_inside_base($baseDir, $filePath) || !is_file($filePath)) {
                    throw new RuntimeException('Invalid file.');
                }

                $contents = (string) ($_POST['contents'] ?? '');
                if (file_put_contents($filePath, $contents) === false) {
                    throw new RuntimeException('Unable to save file.');
                }

                $editRelative = $fileRelative;
                $editContents = $contents;
                $message = 'File saved successfully.';
            } elseif ($action === 'chmod') {
                $targetRelative = normalize_relative_path($_POST['target'] ?? '');
                $targetPath = absolute_from_relative($baseDir, $targetRelative);
                if (!ensure_inside_base($baseDir, $targetPath) || !file_exists($targetPath)) {
                    throw new RuntimeException('Invalid target.');
                }

                $mode = trim((string) ($_POST['mode'] ?? ''));
                if (!preg_match('/^[0-7]{3,4}$/', $mode)) {
                    throw new RuntimeException('Permission must be octal, for example 755 or 0644.');
                }

                if (!chmod($targetPath, octdec($mode))) {
                    throw new RuntimeException('chmod failed.');
                }

                $message = 'Permissions updated.';
            } elseif ($action === 'terminal') {
                $command = trim((string) ($_POST['command'] ?? ''));
                $terminalState = get_terminal_state($terminalId, $postedPath);
                $result = execute_terminal_command($command, $terminalState['cwd'], $baseDir);
                $terminalState['cwd'] = $result['cwd'];
                set_terminal_state($terminalId, $terminalState);
                $terminalOutput = $result['output'];
                $currentPath = $terminalState['cwd'];
                if (ensure_inside_base($baseDir, $currentPath)) {
                    $currentRelative = relative_from_absolute($baseDir, $currentPath);
                } else {
                    $currentPath = $postedPath;
                    $currentRelative = $postedRelative;
                }
                $message = 'Command executed.';
            } elseif ($action === 'create_file') {
                $name = basename(trim((string) ($_POST['name'] ?? '')));
                if ($name === '') {
                    throw new RuntimeException('File name cannot be empty.');
                }

                $newPath = join_path($postedPath, $name);
                if (file_exists($newPath)) {
                    throw new RuntimeException('Target already exists.');
                }

                if (file_put_contents($newPath, '') === false) {
                    throw new RuntimeException('Unable to create file.');
                }

                $message = 'New file created.';
            } elseif ($action === 'create_folder') {
                $name = basename(trim((string) ($_POST['name'] ?? '')));
                if ($name === '') {
                    throw new RuntimeException('Folder name cannot be empty.');
                }

                $newPath = join_path($postedPath, $name);
                if (file_exists($newPath)) {
                    throw new RuntimeException('Target already exists.');
                }

                if (!mkdir($newPath, 0755, false)) {
                    throw new RuntimeException('Unable to create folder.');
                }

                $message = 'New folder created.';
            } elseif ($action === 'load_editor') {
                $fileRelative = normalize_relative_path($_POST['file'] ?? '');
                $filePath = absolute_from_relative($baseDir, $fileRelative);
                if (!ensure_inside_base($baseDir, $filePath) || !is_editable_file($filePath)) {
                    throw new RuntimeException('File cannot be edited.');
                }

                $loaded = file_get_contents($filePath);
                if ($loaded === false) {
                    throw new RuntimeException('Unable to read file.');
                }

                $editRelative = $fileRelative;
                $editContents = $loaded;
                $message = 'Editor loaded.';
            } elseif ($action === 'load_preview') {
                $fileRelative = normalize_relative_path($_POST['file'] ?? '');
                $previewData = make_preview_data($baseDir, $fileRelative);
                $previewRelative = $fileRelative;
                $message = 'Preview loaded.';
            } elseif ($action === 'delete_item') {
                $targetRelative = normalize_relative_path($_POST['target'] ?? '');
                $targetPath = absolute_from_relative($baseDir, $targetRelative);
                if ($targetRelative === '' || !ensure_inside_base($baseDir, $targetPath) || !file_exists($targetPath)) {
                    throw new RuntimeException('Invalid delete target.');
                }

                delete_path_recursive($targetPath);
                $message = 'Item deleted.';
            } elseif ($action === 'rename_item') {
                $targetRelative = normalize_relative_path($_POST['target'] ?? '');
                $targetPath = absolute_from_relative($baseDir, $targetRelative);
                $newName = basename(trim((string) ($_POST['new_name'] ?? '')));
                if ($newName === '' || $targetRelative === '' || !file_exists($targetPath) || !ensure_inside_base($baseDir, $targetPath)) {
                    throw new RuntimeException('Invalid rename request.');
                }

                $destinationPath = join_path(dirname($targetPath), $newName);
                if (!ensure_inside_base($baseDir, $destinationPath) || file_exists($destinationPath)) {
                    throw new RuntimeException('Rename destination already exists or is invalid.');
                }

                if (!rename($targetPath, $destinationPath)) {
                    throw new RuntimeException('Rename failed.');
                }

                $message = 'Item renamed.';
            } elseif ($action === 'copy_item') {
                $sourceRelative = normalize_relative_path($_POST['source'] ?? '');
                $sourcePath = absolute_from_relative($baseDir, $sourceRelative);
                [$destinationRelative, $destinationPath] = normalize_target_path($baseDir, $currentRelative, (string) ($_POST['destination'] ?? ''));

                if ($sourceRelative === '' || !file_exists($sourcePath) || !ensure_inside_base($baseDir, $sourcePath)) {
                    throw new RuntimeException('Invalid copy source.');
                }
                if (file_exists($destinationPath)) {
                    throw new RuntimeException('Copy destination already exists.');
                }
                if (str_starts_with(str_replace('\\', '/', $destinationPath), str_replace('\\', '/', $sourcePath) . '/')) {
                    throw new RuntimeException('Cannot copy into a nested path of the source.');
                }

                copy_path_recursive($sourcePath, $destinationPath);
                $message = 'Item copied to ' . $destinationRelative . '.';
            } elseif ($action === 'move_item') {
                $sourceRelative = normalize_relative_path($_POST['source'] ?? '');
                $sourcePath = absolute_from_relative($baseDir, $sourceRelative);
                [$destinationRelative, $destinationPath] = normalize_target_path($baseDir, $currentRelative, (string) ($_POST['destination'] ?? ''));

                if ($sourceRelative === '' || !file_exists($sourcePath) || !ensure_inside_base($baseDir, $sourcePath)) {
                    throw new RuntimeException('Invalid move source.');
                }
                if (file_exists($destinationPath)) {
                    throw new RuntimeException('Move destination already exists.');
                }
                if (str_starts_with(str_replace('\\', '/', $destinationPath), str_replace('\\', '/', $sourcePath) . '/')) {
                    throw new RuntimeException('Cannot move into a nested path of the source.');
                }

                $parent = dirname($destinationPath);
                if (!is_dir($parent) && !mkdir($parent, 0755, true)) {
                    throw new RuntimeException('Unable to create destination parent directory.');
                }
                if (!rename($sourcePath, $destinationPath)) {
                    throw new RuntimeException('Move failed.');
                }

                $message = 'Item moved to ' . $destinationRelative . '.';
            } else {
                throw new RuntimeException('Unsupported action.');
            }
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

if (isset($_GET['edit']) && $editContents === null) {
    $editRelative = normalize_relative_path((string) $_GET['edit']);
    $editPath = absolute_from_relative($baseDir, $editRelative);
    if (is_editable_file($editPath) && ensure_inside_base($baseDir, $editPath)) {
        $loaded = file_get_contents($editPath);
        if ($loaded !== false) {
            $editContents = $loaded;
        }
    }
}

if (isset($_GET['preview_file']) && $previewData === null) {
    $previewRelative = normalize_relative_path((string) $_GET['preview_file']);
    try {
        $previewData = make_preview_data($baseDir, $previewRelative);
    } catch (Throwable $e) {
        $previewRelative = '';
    }
}

$entries = load_entries($currentPath, $baseDir);

if (isset($_GET['ajax_nav'])) {
    json_response([
        'ok' => true,
        'message' => '',
        'messageHtml' => '',
        'summaryHtml' => render_path_summary($baseDir, $currentPath, $entries),
        'explorerHtml' => render_explorer($baseDir, $currentRelative, $currentPath, $entries),
        'actionsHtml' => render_actions_panel($baseDir, $currentRelative),
        'editorHtml' => '',
        'previewHtml' => '',
        'terminalOutput' => '',
        'terminalCwd' => '',
        'root' => $baseDir,
        'path' => $currentRelative,
    ]);
}

if (($_GET['view'] ?? '') === 'terminal' && !$isAjax) {
    $terminalState = get_terminal_state($terminalId, $currentPath);
    render_terminal_page($baseDir, $currentRelative, $currentPath, $terminalId, $terminalState['cwd']);
    exit;
}

if ($isAjax) {
    json_response([
        'ok' => $messageType !== 'error',
        'message' => $message,
        'messageHtml' => render_message($message, $messageType),
        'summaryHtml' => render_path_summary($baseDir, $currentPath, $entries),
        'explorerHtml' => render_explorer($baseDir, $currentRelative, $currentPath, $entries),
        'actionsHtml' => render_actions_panel($baseDir, $currentRelative),
        'editorHtml' => render_editor($baseDir, $currentRelative, $editRelative, $editContents),
        'previewHtml' => render_preview($baseDir, $previewRelative, $previewData),
        'terminalOutput' => $terminalOutput,
        'terminalCwd' => isset($terminalState) ? $terminalState['cwd'] : '',
        'root' => $baseDir,
        'path' => $currentRelative,
        'reload' => false,
    ]);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP File Manager + Terminal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github-dark.min.css">
    <style>
        :root {
            --bg: #f3efe5;
            --panel: rgba(255, 252, 245, 0.84);
            --line: rgba(108, 82, 56, 0.16);
            --line-strong: rgba(108, 82, 56, 0.28);
            --text: #1c1917;
            --muted: #6b5b4a;
            --accent: #0f766e;
            --accent-strong: #115e59;
            --accent-soft: #d5f1eb;
            --danger: #b91c1c;
            --danger-soft: rgba(220, 38, 38, 0.1);
            --success-soft: rgba(15, 118, 110, 0.12);
            --shadow-md: 0 14px 32px rgba(41, 28, 18, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(15, 118, 110, 0.18), transparent 30%),
                radial-gradient(circle at top right, rgba(251, 191, 36, 0.18), transparent 24%),
                linear-gradient(180deg, #f3efe5, #efe6d4 45%, #efe8db 100%);
            color: var(--text);
            font-family: "Space Grotesk", sans-serif;
        }
        .container { max-width: 1440px; margin: 0 auto; padding: 28px; }
        .grid { display: grid; gap: 24px; grid-template-columns: minmax(0, 1.9fr) minmax(340px, 0.95fr); }
        .card { background: var(--panel); backdrop-filter: blur(18px); border: 1px solid rgba(255,255,255,.55); box-shadow: var(--shadow-md); border-radius: 24px; padding: 22px; overflow: hidden; }
        h1, h2, h3 { margin-top: 0; letter-spacing: -.03em; }
        h1 { margin-bottom: 10px; font-size: clamp(32px, 5vw, 56px); line-height: .95; }
        h2 { margin-bottom: 10px; font-size: 20px; }
        .hero { display:flex; justify-content:space-between; gap:20px; align-items:end; margin-bottom:22px; }
        .hero-copy { max-width: 760px; }
        .eyebrow { display:inline-flex; align-items:center; gap:8px; margin-bottom:12px; padding:8px 12px; border-radius:999px; background:rgba(255,250,240,.65); border:1px solid rgba(255,255,255,.75); color:var(--accent-strong); font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
        .subtitle { margin:0; max-width:58ch; color:var(--muted); line-height:1.6; }
        .hero-stats { display:grid; gap:12px; min-width:260px; }
        .stat { padding:14px 16px; border-radius:18px; background:rgba(255,250,240,.7); border:1px solid rgba(255,255,255,.8); }
        .stat-label { color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px; }
        .stat-value { font-size:14px; line-height:1.5; word-break:break-word; }
        .toolbar, .actions, form.inline { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .panel-header { display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:10px; }
        .panel-title p { margin:6px 0 0; color:var(--muted); font-size:14px; }
        .stack { display:grid; gap:18px; }
        .section { padding:18px; background:linear-gradient(180deg, rgba(255,250,240,.82), rgba(246,240,226,.86)); border:1px solid var(--line); border-radius:20px; }
        input[type="text"], input[type="password"], input[type="file"], textarea {
            width:100%; background:rgba(255,255,255,.72); color:var(--text); border:1px solid var(--line); border-radius:14px; padding:12px 14px; font:inherit;
        }
        textarea { min-height:380px; resize:vertical; font-family:"JetBrains Mono", monospace; font-size:14px; line-height:1.7; }
        button, .button {
            display:inline-block; background:linear-gradient(135deg, #0f766e, #14b8a6); color:#f8fffd; border:0; border-radius:14px; padding:11px 16px; font-weight:700; font-family:inherit; text-decoration:none; cursor:pointer;
        }
        .button.secondary, button.secondary { background:rgba(255,255,255,.6); color:var(--text); border:1px solid var(--line-strong); }
        .button.danger, button.danger { background:rgba(220,38,38,.1); color:var(--danger); border:1px solid rgba(220,38,38,.25); }
        .message { margin-bottom:18px; padding:15px 18px; border-radius:16px; border:1px solid rgba(15,118,110,.18); background:var(--success-soft); }
        .message.error { background:var(--danger-soft); border-color:rgba(220,38,38,.3); }
        .path { color:var(--muted); word-break:break-all; line-height:1.7; }
        .breadcrumbs { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin: 0 0 14px; padding: 12px 14px; background: rgba(255,255,255,.5); border:1px solid rgba(255,255,255,.6); border-radius: 18px; }
        .crumb { text-decoration:none; color:var(--accent-strong); font-weight:700; }
        .crumb-sep { color:var(--muted); }
        table { width:100%; border-collapse:collapse; margin-top:16px; background:rgba(255,255,255,.4); border:1px solid rgba(255,255,255,.65); border-radius:18px; overflow:hidden; }
        th, td { padding:12px 10px; border-top:1px solid rgba(108,82,56,.1); vertical-align:top; text-align:left; }
        thead tr { background:rgba(255,250,240,.82); }
        th { color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.08em; }
        tbody tr:hover { background:rgba(15,118,110,.05); }
        .name a { color:var(--accent-strong); text-decoration:none; font-weight:700; }
        .name strong { display:block; margin-bottom:2px; }
        .meta { color:var(--muted); font-size:12px; }
        .small { color:var(--muted); font-size:13px; line-height:1.6; }
        .editor-card, .preview-card { margin-top:24px; }
        .editor-head, .preview-head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; }
        .editor-badge { padding:8px 12px; border-radius:999px; background:var(--accent-soft); color:var(--accent-strong); font-size:12px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
        .preview-image { display:block; max-width:100%; max-height:620px; border-radius:18px; border:1px solid rgba(255,255,255,.4); background:#fff; margin:0 auto; }
        .preview-code { margin:0; padding:0; overflow:auto; border-radius:18px; }
        .preview-code code { font-family:"JetBrains Mono", monospace; font-size:13px; line-height:1.7; }
        .preview-empty { padding:16px; border-radius:16px; background:rgba(255,255,255,.5); color:var(--muted); }
        code { font-family:"JetBrains Mono", monospace; }
        @media (max-width:980px) {
            .grid { grid-template-columns:1fr; }
            .hero { flex-direction:column; align-items:stretch; }
            .hero-stats { min-width:0; }
            table { display:block; overflow-x:auto; }
        }
        @media (max-width:640px) {
            .container { padding:18px; }
            .card { padding:18px; border-radius:20px; }
            .section { padding:16px; }
            h1 { font-size:34px; }
            .panel-header { flex-direction:column; align-items:flex-start; }
        }
    </style>
</head>
<body>
    <div class="container" id="app">
        <div class="hero">
            <div class="hero-copy">
                <div class="eyebrow">Workspace Control Panel</div>
                <h1>PHP File Manager + Terminal</h1>
            </div>
            <div id="summary-panel"><?= render_path_summary($baseDir, $currentPath, $entries) ?></div>
        </div>

        <div id="message-panel"><?= render_message($message, $messageType) ?></div>

        <div class="grid">
            <div class="card" id="explorer-panel"><?= render_explorer($baseDir, $currentRelative, $currentPath, $entries) ?></div>
            <div class="card" id="actions-panel"><?= render_actions_panel($baseDir, $currentRelative) ?></div>
        </div>

        <div id="preview-mount"><?= render_preview($baseDir, $previewRelative, $previewData) ?></div>
        <div id="editor-mount"><?= render_editor($baseDir, $currentRelative, $editRelative, $editContents) ?></div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"></script>
    <script>
        const appState = {
            root: <?= json_encode($baseDir, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            path: <?= json_encode($currentRelative, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
        };

        function runHighlight() {
            if (window.hljs) {
                document.querySelectorAll('pre code').forEach((block) => window.hljs.highlightElement(block));
            }
        }

        async function requestWithFormData(formData) {
            formData.set('ajax', '1');
            const response = await fetch(window.location.pathname, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            return response.json();
        }

        function syncState(root, path) {
            appState.root = root;
            appState.path = path;
            const url = new URL(window.location.href);
            url.searchParams.set('root', root);
            url.searchParams.set('path', path);
            url.searchParams.delete('edit');
            url.searchParams.delete('preview_file');
            window.history.replaceState({}, '', url);
        }

        function applyPayload(payload) {
            if (payload.reload) {
                window.location.reload();
                return;
            }

            document.getElementById('message-panel').innerHTML = payload.messageHtml || '';
            document.getElementById('summary-panel').innerHTML = payload.summaryHtml || '';
            document.getElementById('explorer-panel').innerHTML = payload.explorerHtml || '';
            document.getElementById('actions-panel').innerHTML = payload.actionsHtml || '';
            document.getElementById('editor-mount').innerHTML = payload.editorHtml || '';
            document.getElementById('preview-mount').innerHTML = payload.previewHtml || '';
            syncState(payload.root, payload.path);
            runHighlight();
        }

        function fillSelectedPath(target) {
            document.querySelectorAll('.js-selected-target').forEach((input) => {
                input.value = target;
            });
            const label = document.getElementById('selected-item-label');
            if (label) {
                label.textContent = 'Selected: ' + target;
            }
        }

        document.addEventListener('submit', async (event) => {
            const form = event.target;
            if (!form.classList.contains('js-ajax-form')) {
                return;
            }

            event.preventDefault();
            const submitButton = form.querySelector('button[type="submit"]');
            const originalLabel = submitButton ? submitButton.textContent : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Processing...';
            }

            try {
                const formData = new FormData(form);
                if (!formData.has('root')) {
                    formData.set('root', appState.root);
                }
                if (!formData.has('path')) {
                    formData.set('path', appState.path);
                }
                const payload = await requestWithFormData(formData);
                applyPayload(payload);
            } catch (error) {
                document.getElementById('message-panel').innerHTML = '<div class="message error">Request failed.</div>';
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalLabel;
                }
            }
        });

        async function navigateTo(path) {
            const response = await fetch(window.location.pathname + '?ajax_nav=1&root=' + encodeURIComponent(appState.root) + '&path=' + encodeURIComponent(path), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await response.json();
            applyPayload(payload);
        }

        async function loadPreview(file) {
            const formData = new FormData();
            formData.set('action', 'load_preview');
            formData.set('root', appState.root);
            formData.set('path', appState.path);
            formData.set('file', file);
            const payload = await requestWithFormData(formData);
            applyPayload(payload);
            const panel = document.getElementById('preview-panel');
            if (panel) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        async function loadEditor(file) {
            const formData = new FormData();
            formData.set('action', 'load_editor');
            formData.set('root', appState.root);
            formData.set('path', appState.path);
            formData.set('file', file);
            const payload = await requestWithFormData(formData);
            applyPayload(payload);
            const panel = document.getElementById('editor-panel');
            if (panel) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        async function deleteItem(target, kind) {
            const okay = window.confirm('Delete this ' + kind + '?\n' + target);
            if (!okay) {
                return;
            }

            const formData = new FormData();
            formData.set('action', 'delete_item');
            formData.set('root', appState.root);
            formData.set('path', appState.path);
            formData.set('target', target);
            const payload = await requestWithFormData(formData);
            applyPayload(payload);
        }

        async function promptAction(action, sourceLabel, target, placeholder) {
            const value = window.prompt(placeholder, target);
            if (!value) {
                return;
            }
            const formData = new FormData();
            formData.set('action', action);
            formData.set('root', appState.root);
            formData.set('path', appState.path);
            if (action === 'rename_item') {
                formData.set('target', target);
                formData.set('new_name', value);
            } else {
                formData.set('source', target);
                formData.set('destination', value);
            }
            const payload = await requestWithFormData(formData);
            applyPayload(payload);
        }

        document.addEventListener('click', (event) => {
            const navLink = event.target.closest('.js-nav-link');
            if (navLink) {
                event.preventDefault();
                navigateTo(navLink.dataset.path || '');
                return;
            }

            const previewBtn = event.target.closest('.js-preview-btn');
            if (previewBtn) {
                event.preventDefault();
                fillSelectedPath(previewBtn.dataset.file || '');
                loadPreview(previewBtn.dataset.file || '');
                return;
            }

            const editBtn = event.target.closest('.js-edit-btn');
            if (editBtn) {
                event.preventDefault();
                fillSelectedPath(editBtn.dataset.file || '');
                loadEditor(editBtn.dataset.file || '');
                return;
            }

            const manageBtn = event.target.closest('.js-manage-btn');
            if (manageBtn) {
                event.preventDefault();
                fillSelectedPath(manageBtn.dataset.target || '');
                return;
            }

            const renameBtn = event.target.closest('.js-rename-btn');
            if (renameBtn) {
                event.preventDefault();
                fillSelectedPath(renameBtn.dataset.target || '');
                promptAction('rename_item', 'target', renameBtn.dataset.target || '', 'Enter new name');
                return;
            }

            const copyBtn = event.target.closest('.js-copy-btn');
            if (copyBtn) {
                event.preventDefault();
                fillSelectedPath(copyBtn.dataset.target || '');
                promptAction('copy_item', 'source', copyBtn.dataset.target || '', 'Enter destination path inside root');
                return;
            }

            const moveBtn = event.target.closest('.js-move-btn');
            if (moveBtn) {
                event.preventDefault();
                fillSelectedPath(moveBtn.dataset.target || '');
                promptAction('move_item', 'source', moveBtn.dataset.target || '', 'Enter destination path inside root');
                return;
            }

            const deleteBtn = event.target.closest('.js-delete-btn');
            if (deleteBtn) {
                event.preventDefault();
                fillSelectedPath(deleteBtn.dataset.target || '');
                deleteItem(deleteBtn.dataset.target || '', deleteBtn.dataset.kind || 'item');
            }
        });

        runHighlight();
    </script>
</body>
</html>
