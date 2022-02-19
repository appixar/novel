<?php
// Get products with categ id
$route = [
    'module' => 'product:getByCateg',
    'auth' => ['required' => false]
];
http::route($route);
