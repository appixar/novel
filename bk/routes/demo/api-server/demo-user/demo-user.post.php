<?php
$route = [
    'module' => 'demo-user',
    'auth' => ['required' => false]
];
http::route($route);