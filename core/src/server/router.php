<?php
// get file name
$public_dir = __DIR__ . '/../../../public/';
$fn = @explode("?", $_SERVER['REQUEST_URI'])[0]; // ignore "?" (get params)
$fn_path = $public_dir . $fn;
// include file
if (file_exists($fn_path) and is_file($fn_path)) {
    // get file extension
    $end = @end(explode("/", $fn));
    $ext = @end(explode(".", $end));
    // header
    if ($ext === 'css') header('Content-Type: text/css');
    if ($ext === 'js') header('Content-Type: application/javascript');
    // include
    include $fn_path;
    exit;
}
include_once $public_dir . 'index.php';
