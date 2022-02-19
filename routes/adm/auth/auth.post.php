<?php
// Disable addr with addr id
$route = [
    'module' => 'user:authAdmin', 
    'auth' => ['required' => false]
];
http::route($route);
