<?php
// Get products with title
$route = [
    'module' => 'product:getByTitle',
    'auth' => ['required' => false]
];
http::route($route);
