<?php
// Get full address with CEP
$route = [
    'module' => 'addr:cep',
    'data' => 'body',
    'auth' => [
        'required' => false,
        'allow' => 'adm:manager'
    ]
];
http::route($route);
