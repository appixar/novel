<?php
// Get districts with city id
$route = [
    'module' => 'addr:district',
    'auth' => ['required' => false]
];
http::route($route);
