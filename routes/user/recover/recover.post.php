<?php
// Recover pass
$route = [
    'module' => 'user:recover',
    'auth' => ['required' => false]
];
http::route($route);
