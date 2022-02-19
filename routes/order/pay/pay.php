<?php
// Get payment options
$route = [
    'module' => 'order:pay',
    'auth' => ['required' => false]
];
http::route($route);
