<?php
prex($_FILES);
$route = [
    'module' => 'banner',
    'upload' => true
];
http::route($route);